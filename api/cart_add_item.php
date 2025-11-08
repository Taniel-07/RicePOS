<?php
require_once 'db_connect.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $buyer_id = $input['buyer_id'] ?? 0;
    $product_id = $input['product_id'] ?? 0;
    $quantity_to_add = $input['quantity'] ?? 1; // Default kay 1

    if (empty($buyer_id) || empty($product_id) || $quantity_to_add <= 0) {
        json_response(null, false, 'Buyer ID, Product ID, and positive Quantity are required.', 400);
    }

    // Pangitaon o himuon ang cart ID
    $cart_id = null;
    $stmt_find_cart = $conn->prepare("SELECT id FROM cart WHERE buyer_id = ?");
     if(!$stmt_find_cart) throw new Exception("SQL Prepare Error (find cart): " . $conn->error);
    $stmt_find_cart->bind_param("i", $buyer_id);
    $stmt_find_cart->execute();
    $result_cart = $stmt_find_cart->get_result();
    if ($cart_row = $result_cart->fetch_assoc()) {
        $cart_id = $cart_row['id'];
    } else {
        $stmt_create_cart = $conn->prepare("INSERT INTO cart (buyer_id) VALUES (?)");
         if(!$stmt_create_cart) throw new Exception("SQL Prepare Error (create cart): " . $conn->error);
        $stmt_create_cart->bind_param("i", $buyer_id);
        $stmt_create_cart->execute();
        $cart_id = $conn->insert_id;
        $stmt_create_cart->close();
    }
    $stmt_find_cart->close();

    // I-check kung naa na ang item sa cart
    $existing_item_id = null;
    $current_quantity = 0;
    $stmt_check_item = $conn->prepare("SELECT id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ?");
     if(!$stmt_check_item) throw new Exception("SQL Prepare Error (check item): " . $conn->error);
    $stmt_check_item->bind_param("ii", $cart_id, $product_id);
    $stmt_check_item->execute();
    $result_item = $stmt_check_item->get_result();
    if ($item_row = $result_item->fetch_assoc()) {
        $existing_item_id = $item_row['id'];
        $current_quantity = $item_row['quantity'];
    }
    $stmt_check_item->close();

    if ($existing_item_id) {
        // Kung naa na, i-update ang quantity
        $new_quantity = $current_quantity + $quantity_to_add;
        $stmt_update = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
         if(!$stmt_update) throw new Exception("SQL Prepare Error (update qty): " . $conn->error);
        $stmt_update->bind_param("ii", $new_quantity, $existing_item_id);
        if ($stmt_update->execute()) {
             json_response(['cart_item_id' => $existing_item_id, 'new_quantity' => $new_quantity], true, 'Item quantity updated.');
        } else {
             throw new Exception('Failed to update item quantity: ' . $stmt_update->error);
        }
        $stmt_update->close();
    } else {
        // Kung wala pa, i-insert
        $stmt_insert = $conn->prepare("INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, ?)");
         if(!$stmt_insert) throw new Exception("SQL Prepare Error (insert item): " . $conn->error);
        $stmt_insert->bind_param("iii", $cart_id, $product_id, $quantity_to_add);
         if ($stmt_insert->execute()) {
             json_response(['cart_item_id' => $conn->insert_id, 'new_quantity' => $quantity_to_add], true, 'Item added to cart.');
        } else {
             throw new Exception('Failed to add item to cart: ' . $stmt_insert->error);
        }
        $stmt_insert->close();
    }

} catch (Exception $e) {
    json_response(null, false, $e->getMessage(), 500);
} finally {
     if (isset($conn)) $conn->close();
}
?>