<?php
require_once 'db_connect.php';

try {
    $buyer_id = $_GET['buyer_id'] ?? 0;
    if (empty($buyer_id)) {
        json_response(null, false, 'Buyer ID is required.', 400);
    }

    // Pangitaon ang cart ID sa buyer (o maghimo kung wala pa)
    $cart_id = null;
    $stmt_find_cart = $conn->prepare("SELECT id FROM cart WHERE buyer_id = ?");
    if(!$stmt_find_cart) throw new Exception("SQL Prepare Error (find cart): " . $conn->error);
    $stmt_find_cart->bind_param("i", $buyer_id);
    $stmt_find_cart->execute();
    $result_cart = $stmt_find_cart->get_result();
    if ($cart_row = $result_cart->fetch_assoc()) {
        $cart_id = $cart_row['id'];
    } else {
        // Maghimo og bag-ong cart kung wala pa
        $stmt_create_cart = $conn->prepare("INSERT INTO cart (buyer_id) VALUES (?)");
         if(!$stmt_create_cart) throw new Exception("SQL Prepare Error (create cart): " . $conn->error);
        $stmt_create_cart->bind_param("i", $buyer_id);
        $stmt_create_cart->execute();
        $cart_id = $conn->insert_id;
        $stmt_create_cart->close();
    }
    $stmt_find_cart->close();

    // Kuhaon ang tanang items sa cart, i-apil ang product details
    $sql_items = "
        SELECT ci.id as cart_item_id, ci.quantity, p.id as product_id, p.name, p.price, p.image_url, p.option, p.packaging, p.stock_quantity
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        WHERE ci.cart_id = ?
    ";
    $stmt_items = $conn->prepare($sql_items);
     if(!$stmt_items) throw new Exception("SQL Prepare Error (get items): " . $conn->error);
    $stmt_items->bind_param("i", $cart_id);
    $stmt_items->execute();
    $result_items = $stmt_items->get_result();
    $cart_items = [];
    while ($item = $result_items->fetch_assoc()) {
        $cart_items[] = $item;
    }
    $stmt_items->close();

    json_response($cart_items, true, 'Cart fetched successfully.');

} catch (Exception $e) {
    json_response(null, false, $e->getMessage(), 500);
} finally {
     if (isset($conn)) $conn->close();
}
?>