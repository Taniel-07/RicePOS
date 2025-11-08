<?php
require_once 'db_connect.php'; // Includes $conn and json_response

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $username = $input['username'] ?? '';
    $password = $input['password'] ?? '';

    // Gamiton na nato ang bag-ong table name `users`
    $stmt = $conn->prepare("SELECT id, username, email, password, role, store_name, store_owner, contact, address, store_contact, store_email, store_desc, fulfillment_option FROM users WHERE (username = ? OR email = ?)");
    if ($stmt === false) {
        throw new Exception('SQL Prepare Error: ' . $conn->error);
    }

    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        // I-check kung ang gi-type nga password motakdo ba sa naa sa database
        if ($password === $user['password']) { // NOTE: Gamit og password_verify() sa tinuod nga app
            unset($user['password']); // Ayaw i-apil ang password sa i-send pabalik
            json_response($user, true, 'Login successful.');
        } else {
            // Sayop ang password
            json_response(null, false, 'Invalid credentials.', 401);
        }
    } else {
        // Wala nakita ang username
        json_response(null, false, 'Invalid credentials.', 401);
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