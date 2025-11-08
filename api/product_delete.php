<?php
require_once 'db_connect.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $product_id = $input['id'] ?? 0;
    if (empty($product_id)) {
        json_response(null, false, 'Product ID is required.', 400);
    }
    // Gamiton ang `products` table
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    if ($stmt === false) throw new Exception('SQL Prepare Error: ' . $conn->error);
    $stmt->bind_param("i", $product_id);
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
           json_response(null, true, 'Product deleted successfully.');
        } else {
           json_response(null, false, 'Product not found or already deleted.', 404);
        }
    } else {
        throw new Exception('Failed to delete product: ' . $stmt->error);
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