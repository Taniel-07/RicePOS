<?php
require_once 'db_connect.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $buyer_id = $input['buyer_id'] ?? 0;

    if (empty($buyer_id)) {
        json_response(null, false, 'Buyer ID is required.', 400);
    }

    // Pangitaon ang cart ID
    $stmt_find_cart = $conn->prepare("SELECT id FROM cart WHERE buyer_id = ?");
     if(!$stmt_find_cart) throw new Exception("SQL Prepare Error (find cart): " . $conn->error);
    $stmt_find_cart->bind_param("i", $buyer_id);
    $stmt_find_cart->execute();
    $result_cart = $stmt_find_cart->get_result();
    if (!$cart_row = $result_cart->fetch_assoc()) {
        json_response(null, true, 'Cart already empty or not found.'); // Not an error if cart doesn't exist
    }
    $cart_id = $cart_row['id'];
    $stmt_find_cart->close();

    // I-delete tanan items anang cart_id
    $stmt_clear = $conn->prepare("DELETE FROM cart_items WHERE cart_id = ?");
     if(!$stmt_clear) throw new Exception("SQL Prepare Error (clear items): " . $conn->error);
    $stmt_clear->bind_param("i", $cart_id);

    if ($stmt_clear->execute()) {
        json_response(null, true, 'Cart cleared successfully.');
    } else {
        throw new Exception('Failed to clear cart: ' . $stmt_clear->error);
    }
    $stmt_clear->close();

} catch (Exception $e) {
    json_response(null, false, $e->getMessage(), 500);
} finally {
     if (isset($conn)) $conn->close();
}
?>