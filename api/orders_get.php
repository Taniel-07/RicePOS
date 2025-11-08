<?php
require_once 'db_connect.php';

try {
    $user_id = $_GET['user_id'] ?? 0;
    $role = $_GET['role'] ?? '';
    $orders = [];

    // Gamiton ang bag-ong table names: orders, users, order_items, products
    $sql = "
        SELECT
            o.id AS order_id, o.total_amount, o.status, o.address_city, o.address_purok, o.order_date,
            o.fulfillment_type, o.payment_method,
            b.username AS buyer_username,
            oi.id AS item_id, oi.quantity, oi.subtotal, oi.price_at_sale, oi.product_name_at_sale, oi.product_option,
            p.id AS product_id, p.image_url, p.type AS product_type, p.packaging AS product_packaging,
            s.username AS seller_username, s.store_name AS seller_store_name
        FROM orders o
        JOIN users b ON o.buyer_id = b.id
        JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.id
        JOIN users s ON oi.seller_id = s.id
    ";

    $conditions = [];
    if ($role === 'buyer' && !empty($user_id)) {
        $conditions[] = "o.buyer_id = " . intval($user_id);
    } elseif ($role === 'seller' && !empty($user_id)) {
        $conditions[] = "oi.seller_id = " . intval($user_id);
    } elseif ($role === 'admin') {
         // No conditions needed for admin to get all orders
    } else {
         // If role is invalid or user_id is missing for buyer/seller, return empty
         json_response([], true, 'Orders fetched successfully (No matching criteria).');
    }


    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }

    $sql .= " ORDER BY o.order_date DESC, o.id DESC";

    $result = $conn->query($sql);
     if ($result === false) {
        throw new Exception("Error fetching orders: " . $conn->error);
    }

    $temp_orders = [];
    while ($row = $result->fetch_assoc()) {
        $order_id = $row['order_id'];
        if (!isset($temp_orders[$order_id])) {
            $temp_orders[$order_id] = [
                'id' => $order_id, // Match JS 'id'
                'total' => $row['total_amount'],
                'status' => $row['status'],
                'address' => ['city' => $row['address_city'], 'purok' => $row['address_purok']],
                'createdAt' => $row['order_date'], // Match JS 'createdAt'
                'buyer' => $row['buyer_username'],
                'fulfillmentType' => $row['fulfillment_type'],
                'paymentMethod' => $row['payment_method'],
                'items' => []
            ];
        }
        $temp_orders[$order_id]['items'][] = [
            'itemId' => $row['item_id'], // Use a different name from product id
            'id' => $row['product_id'], // Product ID
            'name' => $row['product_name_at_sale'],
            'price' => $row['price_at_sale'],
            'quantity' => $row['quantity'],
            'subtotal' => $row['subtotal'],
            'option' => $row['product_option'], // Match JS 'option'
            'imageUrl' => $row['image_url'],
            'productType' => $row['product_type'],
            'productPackaging' => $row['product_packaging'],
            'sellerInfo' => [ // Match JS 'sellerInfo'
                 'username' => $row['seller_username'],
                 'storeName' => $row['seller_store_name']
            ]
        ];
    }
    $orders = array_values($temp_orders);

    json_response($orders, true, 'Orders fetched successfully.');

} catch (Exception $e) {
    json_response(null, false, $e->getMessage(), 500);
} finally {
     if (isset($conn)) {
        $conn->close();
     }
}
?>