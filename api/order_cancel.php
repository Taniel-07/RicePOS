<?php
require_once 'db_connect.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $order_id = $input['orderId'] ?? 0;
    $buyer_id = $input['buyerId'] ?? 0;

    if (empty($order_id) || empty($buyer_id)) {
        json_response(null, false, 'Order ID and Buyer ID are required.', 400);
    }

    // Gamiton ang `orders` table
    $stmt = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ? AND buyer_id = ? AND status = 'pending'");
    
    if ($stmt === false) {
        throw new Exception('SQL Prepare Error: ' . $conn->error);
    }

    $stmt->bind_param("ii", $order_id, $buyer_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        json_response(null, true, 'Order has been successfully cancelled.');
    } else {
        // Check if order exists but wasn't pending or didn't match buyer
         $checkStmt = $conn->prepare("SELECT status, buyer_id FROM orders WHERE id = ?");
         $checkStmt->bind_param("i", $order_id);
         $checkStmt->execute();
         $checkResult = $checkStmt->get_result();
         if ($order = $checkResult->fetch_assoc()) {
             if ($order['buyer_id'] != $buyer_id) {
                 json_response(null, false, 'Permission denied. You are not the owner of this order.', 403);
             } elseif ($order['status'] != 'pending') {
                  json_response(null, false, 'Failed to cancel order. It is no longer in pending status.', 400);
             } else {
                  // This case shouldn't normally happen if affected_rows is 0
                   json_response(null, false, 'Failed to cancel order for an unknown reason.', 500);
             }
         } else {
             json_response(null, false, 'Order not found.', 404);
         }
         $checkStmt->close();
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