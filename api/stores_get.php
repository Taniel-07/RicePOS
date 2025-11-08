<?php
require_once 'db_connect.php';

try {
    // Gamiton ang `users` table ug bag-ong fields
    $sql = "SELECT id, username, email, contact, address, store_name, store_owner, store_contact, store_email, store_desc, fulfillment_option FROM users WHERE role = 'seller'";
    $result = $conn->query($sql);
     if ($result === false) {
        throw new Exception("Error fetching stores: " . $conn->error);
    }
    $stores = [];
    while($row = $result->fetch_assoc()) {
        $stores[] = $row;
    }
    json_response($stores, true, 'Stores fetched successfully.');
} catch (Exception $e) {
    json_response(null, false, $e->getMessage(), 500);
} finally {
     if (isset($conn)) {
        $conn->close();
     }
}
?>