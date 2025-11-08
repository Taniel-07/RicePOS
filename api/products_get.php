<?php
require_once 'db_connect.php';

try {
    // Gamiton ang `products` ug `users` tables
    $sql = "SELECT p.*, u.store_name, u.store_owner, u.contact, u.address, u.store_contact, u.store_email, u.store_desc, u.fulfillment_option, u.username as seller_username
            FROM products p
            JOIN users u ON p.seller_id = u.id
            ORDER BY p.created_at DESC";

    $result = $conn->query($sql);
    if($result === false) {
        throw new Exception("Error fetching products: " . $conn->error);
    }

    $products = [];
    while($row = $result->fetch_assoc()) {
        // Make sure all expected fields exist, provide defaults if necessary
         $row['seller'] = [ // Create the nested seller object JS expects
            'storeName' => $row['store_name'] ?? null,
            'storeOwner' => $row['store_owner'] ?? null,
            'contact' => $row['contact'] ?? null, // From ERD
            'address' => $row['address'] ?? null, // From ERD
            'storeContact' => $row['store_contact'] ?? null, // Old field
            'storeEmail' => $row['store_email'] ?? null, // Old field
            'storeDesc' => $row['store_desc'] ?? null, // Old field
            'fulfillmentOption' => $row['fulfillment_option'] ?? null, // From ERD
            'username' => $row['seller_username'] ?? null
        ];
        $products[] = $row;
    }
    json_response($products, true, 'Products fetched successfully.');

} catch (Exception $e) {
    json_response(null, false, $e->getMessage(), 500);
} finally {
     if (isset($conn)) {
        $conn->close();
     }
}
?>