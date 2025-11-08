<?php
require_once 'db_connect.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $buyer_id = $input['buyer_id'] ?? 0;
    $cart_item_id = $input['cart_item_id'] ?? 0;
    $new_quantity = $input['quantity'] ?? -1;

    if (empty($buyer_id) || empty($cart_item_id) || $new_quantity < 0) {
        json_response(null, false, 'Buyer ID, Cart Item ID, and Quantity >= 0 are required.', 400);
    }

    // Step 1: Find the cart ID for this buyer (for security check)
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

    if ($new_quantity == 0) {
        // Step 2a: If quantity is 0, delete the item
        $stmt_delete = $conn->prepare("DELETE FROM cart_items WHERE id = ? AND cart_id = ?");
        if(!$stmt_delete) throw new Exception("SQL Prepare Error (delete item): " . $conn->error);
        $stmt_delete->bind_param("ii", $cart_item_id, $cart_id);

        if ($stmt_delete->execute()) {
             if ($stmt_delete->affected_rows > 0) {
                json_response(null, true, 'Item removed from cart (Quantity reached 0).');
             } else {
                 json_response(null, false, 'Cart item not found or does not belong to this cart.', 404);
             }
        } else {
            throw new Exception('Failed to remove item: ' . $stmt_delete->error);
        }
        $stmt_delete->close();
    } else {
        // Step 2b: If quantity > 0, update the quantity
        $stmt_update = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE id = ? AND cart_id = ?");
        if(!$stmt_update) throw new Exception("SQL Prepare Error (update item): " . $conn->error);
        $stmt_update->bind_param("iii", $new_quantity, $cart_item_id, $cart_id);

        if ($stmt_update->execute()) {
            if ($stmt_update->affected_rows > 0) {
                json_response(['new_quantity' => $new_quantity], true, 'Item quantity updated.');
            } else {
                // Check if item exists but no data was changed (e.g., trying to set quantity to its current value)
                $check_stmt = $conn->prepare("SELECT quantity FROM cart_items WHERE id = ? AND cart_id = ?");
                $check_stmt->bind_param("ii", $cart_item_id, $cart_id);
                $check_stmt->execute();
                if($check_stmt->get_result()->num_rows > 0) {
                    json_response(['new_quantity' => $new_quantity], true, 'No change in quantity detected.');
                } else {
                    json_response(null, false, 'Cart item not found or does not belong to this cart.', 404);
                }
                $check_stmt->close();
            }
        } else {
            throw new Exception('Failed to update item quantity: ' . $stmt_update->error);
        }
        $stmt_update->close();
    }

} catch (Exception $e) {
    json_response(null, false, $e->getMessage(), 500);
} finally {
     if (isset($conn)) $conn->close();
}
?>