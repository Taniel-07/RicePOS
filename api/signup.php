<?php
require_once 'db_connect.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $username = $input['username'] ?? '';
    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';
    $role = $input['role'] ?? '';

    if (empty($username) || empty($email) || empty($password) || empty($role)) {
        json_response(null, false, 'All fields are required.', 400);
    }

    // Gamiton ang `users` table
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    if ($stmt === false) throw new Exception('SQL Prepare Error (select): ' . $conn->error);
    
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close();
        json_response(null, false, 'Username or email already exists.', 409); // 409 Conflict
    }
    $stmt->close();

    // Gamiton ang `users` table
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
    if ($stmt === false) throw new Exception('SQL Prepare Error (insert): ' . $conn->error);

    // NOTE: Sa tinuod nga app, i-hash ang password.
    $stmt->bind_param("ssss", $username, $email, $password, $role);

    if ($stmt->execute()) {
        json_response(['username' => $username], true, 'Account created successfully.');
    } else {
        throw new Exception('Failed to create account: ' . $stmt->error);
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