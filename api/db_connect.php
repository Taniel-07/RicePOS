<?php
// --- I-edit ni kung lahi imong credentials ---
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "rice_pos_db";

// Function para mag-send og JSON response
function json_response($data, $success = true, $message = '', $statusCode = 200) {
    // Sigurohon nga wala pay na-send nga output
    if (headers_sent()) {
        // Log the error or handle it appropriately
        error_log("Headers already sent in json_response function.");
        // Optionally try to append if it's text, but JSON requires clean headers
        // echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
        exit(); // Exit might be the only safe option
    }
    header('Content-Type: application/json');
    http_response_code($statusCode);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

// Ibutang ang connection sulod sa try...catch block
try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // Enable error reporting for mysqli
    $conn = new mysqli($servername, $username, $password, $dbname);
    // Removed connection error check here as mysqli_report handles it
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    // Kung mo-fail ang connection, mo-send ta og limpyo nga JSON error
    // Check if headers already sent before calling json_response
     if (!headers_sent()) {
       json_response(null, false, 'Database Connection Error: ' . $e->getMessage(), 500);
     } else {
       error_log('Database Connection Error: ' . $e->getMessage());
       exit();
     }
}
?>