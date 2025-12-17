<?php
require_once 'db_connect.php';

try {
    // Kinahanglan nga POST request ang gamiton para sa deletion
    $input = json_decode(file_get_contents('php://input'), true);
    $user_id = $input['id'] ?? 0;

    // Check kung naay ID
    if (empty($user_id)) {
        json_response(null, false, 'User ID is required.', 400);
    }
    
    // Safety check: Ayaw tugoti ang admin nga i-delete ang iyang kaugalingong account (Assuming admin ID is 1, based on setup.php)
    // Kinahanglan kining ID i-check kung kinsa ang nag-logged in sa tinuod nga app, pero sa karon, atong i-exclude ang ID 1.
    if ($user_id == 1) {
         json_response(null, false, 'Cannot delete the primary admin account (ID: 1).', 403);
    }

    // Gamiton ang `users` table
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
    if ($stmt === false) throw new Exception('SQL Prepare Error: ' . $conn->error);
    
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
           json_response(null, true, 'User account deleted successfully.');
        } else {
           json_response(null, false, 'User not found, already deleted, or is an Admin.', 404);
        }
    } else {
        throw new Exception('Failed to delete user: ' . $stmt->error);
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