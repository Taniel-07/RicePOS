<?php
require_once 'db_connect.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $user_id = $input['id'] ?? 0;
    // Gamiton ang mga field names gikan sa ERD kung applicable
    $storeName = $input['storeName'] ?? '';
    $storeOwner = $input['storeOwner'] ?? ''; // Keep if needed, or map to user's name
    $contact = $input['contact'] ?? ''; // New field from ERD
    $address = $input['address'] ?? ''; // New field from ERD
    $storeContact = $input['storeContact'] ?? ''; // Old field, maybe merge with contact
    $storeEmail = $input['storeEmail'] ?? ''; // Old field, maybe merge with user email
    $storeDesc = $input['storeDesc'] ?? ''; // Old field
    $fulfillmentOption = $input['fulfillmentOption'] ?? ''; // New field from ERD

    if (empty($user_id)) {
        json_response(null, false, 'User ID is required.', 400);
    }

    // Gamiton ang `users` table ug bag-ong fields
    $stmt = $conn->prepare("UPDATE users SET store_name=?, store_owner=?, contact=?, address=?, store_contact=?, store_email=?, store_desc=?, fulfillment_option=? WHERE id = ? AND role = 'seller'");
     if ($stmt === false) {
        throw new Exception('SQL Prepare Error: ' . $conn->error);
    }

    $stmt->bind_param("ssssssssi", $storeName, $storeOwner, $contact, $address, $storeContact, $storeEmail, $storeDesc, $fulfillmentOption, $user_id);

    if ($stmt->execute()) {
         if ($stmt->affected_rows > 0) {
            json_response(null, true, 'Store info updated successfully.');
         } else {
            // Check if user exists and is a seller but no data was changed
             $checkStmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'seller'");
             $checkStmt->bind_param("i", $user_id);
             $checkStmt->execute();
             $checkResult = $checkStmt->get_result();
             if ($checkResult->num_rows > 0) {
                json_response(null, true, 'No changes detected in store info.');
             } else {
                json_response(null, false, 'Seller not found or user ID is incorrect.', 404);
             }
             $checkStmt->close();
         }
    } else {
        throw new Exception('Failed to update store info: ' . $stmt->error);
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