<?php
require_once 'db_connect.php';
require_once 'send_email.php'; // <--- BAG-O

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $order_id = $input['orderId'] ?? 0;
    $status = $input['status'] ?? ''; // e.g., 'completed' or 'cancelled'

    // Allow 'completed' or 'cancelled'
    if (empty($order_id) || empty($status) || !in_array($status, ['completed', 'cancelled'])) {
        json_response(null, false, 'Order ID and a valid status ("completed" or "cancelled") are required.', 400);
    }

    $conn->begin_transaction(); // Sugdan ang transaction

    // Gamiton ang `orders` table
    // Apan una, kuhaon nato ang kasamtangan nga status para sa stock logic
    $stmt_get_status = $conn->prepare("SELECT status FROM orders WHERE id = ?");
    $stmt_get_status->bind_param("i", $order_id);
    $stmt_get_status->execute();
    $current_status_result = $stmt_get_status->get_result();
    $current_status_row = $current_status_result->fetch_assoc();
    $current_status = $current_status_row['status'] ?? null;
    $stmt_get_status->close();

    // 1. Update sa Order Status
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ? AND status != ?");
     if ($stmt === false) {
        throw new Exception('SQL Prepare Error: ' . $conn->error);
    }
    $stmt->bind_param("sis", $status, $order_id, $status);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        
        // 2. STOCK DEDUCTION LOGIC: I-decrement lang kung gikan sa 'pending' paingon sa 'completed'
        if ($current_status === 'pending' && $status === 'completed') {
            $stmt_items = $conn->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
            if ($stmt_items === false) throw new Exception('SQL Prepare Error (items): ' . $conn->error);
            $stmt_items->bind_param("i", $order_id);
            $stmt_items->execute();
            $items_result = $stmt_items->get_result();
            $stmt_items->close();

            // I-loop ang tanang items ug i-update ang stock
            $stmt_update_stock = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ? AND stock_quantity IS NOT NULL");
            if ($stmt_update_stock === false) throw new Exception('SQL Prepare Error (update stock): ' . $conn->error);

            while ($item = $items_result->fetch_assoc()) {
                $product_id = $item['product_id'];
                $quantity = $item['quantity'];
                
                // I-apply ang deduction
                $stmt_update_stock->bind_param("ii", $quantity, $product_id);
                $stmt_update_stock->execute();
            }
            $stmt_update_stock->close();
        }
        
        // 3. Commit sa Transaction
        $conn->commit();
        
        // 4. SEND NOTIFICATION TO BUYER (Gipabalik ang email logic)
        if ($status === 'completed' || $status === 'cancelled') {
            // Step 1: Kuhaon ang Buyer ID ug fulfillment type gikan sa order
            $stmt_order_info = $conn->prepare("SELECT buyer_id, fulfillment_type FROM orders WHERE id = ?");
            $stmt_order_info->bind_param("i", $order_id);
            $stmt_order_info->execute();
            $order_result = $stmt_order_info->get_result();
            if ($order_info = $order_result->fetch_assoc()) {
                $buyer_id = $order_info['buyer_id'];
                $fulfillment_type = $order_info['fulfillment_type'];

                // Step 2: Kuhaon ang Buyer's info (Username, Email)
                $stmt_buyer = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
                $stmt_buyer->bind_param("i", $buyer_id);
                $stmt_buyer->execute();
                $buyer_result = $stmt_buyer->get_result();
                if ($buyer_data = $buyer_result->fetch_assoc()) {
                    $buyer_email = $buyer_data['email'];
                    $buyer_name = $buyer_data['username'];
                    
                    // Set up email content based on status
                    $subject = "✅ Order #{$order_id} Status Update: " . ucfirst($status);
                    $email_body = "<h2>Order Status Updated!</h2>";
                    
                    if ($status === 'completed') {
                        if ($fulfillment_type === 'pickup') {
                            $email_body .= "<p>Good news, <strong>{$buyer_name}</strong>! Your order <strong>#{$order_id}</strong> is now <strong>ready for pickup</strong> at the seller's location.</p>";
                        } else { // 'deliver'
                            $email_body .= "<p>Your order <strong>#{$order_id}</strong> is now <strong>accepted and ready for delivery</strong>! The seller has completed the preparation.</p>";
                        }
                    } elseif ($status === 'cancelled') {
                         $email_body .= "<p>Your order <strong>#{$order_id}</strong> has been <strong>cancelled</strong>. If you have any questions, please contact the seller.</p>";
                    }
                    
                    $email_body .= "<p>Thank you for choosing Rice POS System.</p>";

                    // Assuming send_notification_email is now correctly configured to use autoloader
                    send_notification_email($buyer_email, $buyer_name, $subject, $email_body);
                }
                $stmt_buyer->close();
            }
            $stmt_order_info->close();
        }
        // -------------------------------------------------------------

        json_response(null, true, 'Order status updated to ' . $status . '.');
    } else {
         // Rollback if status update failed before notification/stock
         $conn->rollback(); 
         // Check if the order exists but already has the target status
         $checkStmt = $conn->prepare("SELECT status FROM orders WHERE id = ?");
         $checkStmt->bind_param("i", $order_id);
         $checkStmt->execute();
         $checkResult = $checkStmt->get_result();
         if ($order = $checkResult->fetch_assoc()) {
             if ($order['status'] === $status) {
                 json_response(null, true, 'Order status is already ' . $status . '.');
             } else {
                  json_response(null, false, 'Order exists but status could not be updated (e.g., wrong current status).', 400);
             }
         } else {
             json_response(null, false, 'Order not found.', 404);
         }
         $checkStmt->close();
    }
    $stmt->close();

} catch (Exception $e) {
    if ($conn->inTransaction()) {
       $conn->rollback();
    }
    json_response(null, false, 'Transaction Failed: ' . $e->getMessage(), 500);
} finally {
     if (isset($conn)) {
        $conn->close();
     }
}
?>