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

    // Gamiton ang `orders` table
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
     if ($stmt === false) {
        throw new Exception('SQL Prepare Error: ' . $conn->error);
    }
    $stmt->bind_param("si", $status, $order_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {

            // -------------------------------------------------------------
            // --- BAG-O: SEND NOTIFICATION TO BUYER ---
            // -------------------------------------------------------------
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

                        send_notification_email($buyer_email, $buyer_name, $subject, $email_body);
                    }
                    $stmt_buyer->close();
                }
                $stmt_order_info->close();
            }
            // -------------------------------------------------------------

            json_response(null, true, 'Order status updated to ' . $status . '.');
        } else {
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
    } else {
        throw new Exception('Failed to update order status: ' . $stmt->error);
    }
    $stmt->close();

} catch (Exception $e) {
    json_response(null, false, $e->getMessage(), 500);
} finally {
     if (isset($conn)) {
        $conn->close();
     }
}
?>  