<?php
require_once 'db_connect.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $buyer_id = $input['buyer_id'] ?? 0; // Gikinahanglan para sa security check
    $cart_item_id = $input['cart_item_id'] ?? 0;

    if (empty($buyer_id) || empty($cart_item_id)) {
        json_response(null, false, 'Buyer ID and Cart Item ID are required.', 400);
    }

    // Pangitaon ang cart ID base sa buyer ID
    $stmt_find_cart = $conn->prepare("SELECT id FROM cart WHERE buyer_id = ?");
     if(!$stmt_find_cart) throw new Exception("SQL Prepare Error (find cart): " . $conn->error);
    $stmt_find_cart->bind_param("i", $buyer_id);
    $stmt_find_cart->execute();
    $result_cart = $stmt_find_cart->get_result();
    if (!$cart_row = $result_cart->fetch_assoc()) {
        json_response(null, false, 'Cart not found for this buyer.', 404);
    }
    $cart_id = $cart_row['id'];
    $stmt_find_cart->close();

    // I-delete ang item kung naa sa sakto nga cart
    $stmt_delete = $conn->prepare("DELETE FROM cart_items WHERE id = ? AND cart_id = ?");
     if(!$stmt_delete) throw new Exception("SQL Prepare Error (delete item): " . $conn->error);
    $stmt_delete->bind_param("ii", $cart_item_id, $cart_id);

    if ($stmt_delete->execute()) {
        if ($stmt_delete->affected_rows > 0) {
            json_response(null, true, 'Item removed from cart.');
        } else {
            json_response(null, false, 'Cart item not found or does not belong to this cart.', 404);
        }
    } else {
        throw new Exception('Failed to remove item: ' . $stmt_delete->error);
    }
    $stmt_delete->close();

} catch (Exception $e) {
    json_response(null, false, $e->getMessage(), 500);
} finally {
     if (isset($conn)) $conn->close();
}
?>