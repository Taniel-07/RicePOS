<?php
require_once 'db_connect.php';
require_once 'send_email.php'; // <--- BAG-O

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $buyer_id = $input['buyerId'] ?? 0;
    $total_amount = $input['total'] ?? 0;
    $address_city = $input['address']['city'] ?? '';
    $address_purok = $input['address']['purok'] ?? '';
    $items = $input['items'] ?? [];
    // Bag-ong fields gikan sa ERD (optional sa pag-add, pwede default)
    $fulfillment_type = $input['fulfillment_type'] ?? null; // e.g., 'deliver' or 'pickup'
    $payment_method = $input['payment_method'] ?? 'Cash on Delivery';

    // --- MOVED UP/IMPROVED FULFILLMENT TYPE DETERMINATION ---
     if ($fulfillment_type === null && !empty($items)) {
         $fulfillment_type = $items[0]['option'] ?? 'deliver'; // Default to deliver
     }
    // --- FIXED VALIDATION LOGIC START ---
    $isPickup = (strtolower($fulfillment_type) === 'pickup');

    // Check required fields, and conditionally check address for non-pickup orders
    if (empty($buyer_id) || empty($items) || (!$isPickup && (empty($address_city) || empty($address_purok)))) {
         $msg = 'Invalid order data.';
         if (!$isPickup && (empty($address_city) || empty($address_purok))) {
             $msg = 'City and Purok are required for delivery orders.';
         }
        json_response(null, false, $msg, 400);
    }
    // --- FIXED VALIDATION LOGIC END ---

    $conn->begin_transaction();
    
    // Gamiton ang `orders` table ug bag-ong fields
    $stmt_sale = $conn->prepare("INSERT INTO orders (buyer_id, total_amount, address_city, address_purok, fulfillment_type, payment_method) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt_sale === false) throw new Exception('SQL Prepare Error (orders): ' . $conn->error);
    
    // (Ang fulfillment type na-set na sa taas)

    $stmt_sale->bind_param("idssss", $buyer_id, $total_amount, $address_city, $address_purok, $fulfillment_type, $payment_method);
    $stmt_sale->execute();
    $order_id = $conn->insert_id; // Gamiton `order_id`
    $stmt_sale->close();

    // Gamiton ang `order_items` table ug bag-ong `subtotal` field
    $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, price_at_sale, product_name_at_sale, product_option, seller_id, quantity, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt_item === false) throw new Exception('SQL Prepare Error (order_items): ' . $conn->error);

    // Gamiton ang `products` table
    $stmt_seller = $conn->prepare("SELECT seller_id FROM products WHERE id = ?");
    if ($stmt_seller === false) throw new Exception('SQL Prepare Error (get_seller): ' . $conn->error);

    $first_seller_id = null; // <--- BAG-O
    $buyer_username = ""; // <--- BAG-O

    // Kuhaon ang buyer's username para sa email
    $stmt_get_buyer = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $stmt_get_buyer->bind_param("i", $buyer_id);
    $stmt_get_buyer->execute();
    $buyer_result = $stmt_get_buyer->get_result();
    if ($buyer_data = $buyer_result->fetch_assoc()) {
        $buyer_username = $buyer_data['username'];
    }
    $stmt_get_buyer->close();


    foreach ($items as $item) {
        $product_id = $item['id'] ?? null; // Allow null product ID if needed? Check ERD
        $quantity = $item['quantity'] ?? 1; // Assume quantity is 1 if not provided
        $price_at_sale = $item['price'] ?? 0;
        $subtotal = $price_at_sale * $quantity; // Calculate subtotal

         // Check if product_id is valid before fetching seller_id
         $seller_id = null;
         if ($product_id) {
             $stmt_seller->bind_param("i", $product_id);
             $stmt_seller->execute();
             $seller_result = $stmt_seller->get_result();
             if ($seller_data = $seller_result->fetch_assoc()) {
                $seller_id = $seller_data['seller_id'];
                if ($first_seller_id === null) { // <--- BAG-O
                    $first_seller_id = $seller_id;
                }
             } else {
                 // Handle case where product ID might be invalid or deleted
                  error_log("Warning: Product ID {$product_id} not found when adding order item.");
                 // You might want to skip this item or throw an error depending on requirements
                 // Since seller_id is NOT NULL in the schema, we MUST have a valid seller_id. Let's throw.
                 throw new Exception("Product ID {$product_id} not found. Cannot determine seller.");
             }
         } else {
              throw new Exception("Product ID is missing for an item in the order.");
         }


        // product_option comes from the item data sent from JS
        $product_option = $item['option'] ?? 'deliver'; // Default needed?

        // Check bind_param types: i=integer, d=double, s=string
        // order_id(i), product_id(i), price_at_sale(d), name(s), option(s), seller_id(i), quantity(i), subtotal(d)
        $stmt_item->bind_param("iidssiid", $order_id, $product_id, $price_at_sale, $item['name'], $product_option, $seller_id, $quantity, $subtotal);
        if(!$stmt_item->execute()) {
             throw new Exception("Failed to insert order item: " . $stmt_item->error);
        }
    }
    $stmt_item->close();
    $stmt_seller->close();
    
    $conn->commit();

    // -------------------------------------------------------------
    // --- BAG-O: SEND NOTIFICATION TO SELLER ---
    // -------------------------------------------------------------
    if ($first_seller_id !== null) {
        // Kuhaon ang Seller's info (Store Name, Email)
        $stmt_get_seller = $conn->prepare("SELECT store_name, email FROM users WHERE id = ?");
        $stmt_get_seller->bind_param("i", $first_seller_id);
        $stmt_get_seller->execute();
        $seller_result = $stmt_get_seller->get_result();
        if ($seller_data = $seller_result->fetch_assoc()) {
            $seller_email = $seller_data['email'];
            $seller_name = $seller_data['store_name'] ?: 'Seller';
            
            $subject = "⭐ NEW ORDER RECEIVED: Order #{$order_id} from {$buyer_username}";
            $email_body = "
                <h2>New Order Alert!</h2>
                <p>An order has been placed in your store <strong>{$seller_name}</strong>.</p>
                <p><strong>Order ID:</strong> #{$order_id}</p>
                <p><strong>Total Amount:</strong> ₱" . number_format($total_amount, 2) . "</p>
                <p><strong>Fulfillment:</strong> " . ($fulfillment_type ?? 'N/A') . "</p>
                <p><strong>Address:</strong> {$address_city}, Purok {$address_purok}</p>
                <p>Please check your dashboard to process the order!</p>";

            send_notification_email($seller_email, $seller_name, $subject, $email_body);
        }
        $stmt_get_seller->close();
    }
    // -------------------------------------------------------------

    json_response(['order_id' => $order_id], true, 'Order placed successfully.'); // Use order_id

} catch (Exception $e) {
    if ($conn->inTransaction()) { // Check if still in transaction before rollback
       $conn->rollback();
    }
    json_response(null, false, 'Transaction Failed: ' . $e->getMessage(), 500);
} finally {
     if (isset($conn)) {
        $conn->close();
     }
}
?>