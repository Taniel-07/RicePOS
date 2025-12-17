<?php
//kweny
// seed_all.php
// I-ON ang FULL ERROR DISPLAY
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Tiyakin na tama ang path ng inyong db_connect.php
// Ang 'api/db_connect.php' ay tama kung ang seed_all.php ay nasa RPOS/
require_once 'api/db_connect.php'; 
// ...existing code...

// Make mysqli throw exceptions so failures are caught by the try/catch
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

echo "<h1>RPOS Database Seeder - Para sa Load/Stress Testing</h1>";

try {
    // Prevent script timeout for large inserts
    set_time_limit(0);

    // 1. CLEAR AND RESET TABLES (Ligtas na paraan para magsimula sa malinis na data)
    // Delete child tables first to avoid foreign key constraint errors
    $conn->query("DELETE FROM order_items");
    $conn->query("DELETE FROM orders");
    $conn->query("DELETE FROM products");
    $conn->query("DELETE FROM users");

    // Reset AUTO_INCREMENT for relevant tables
    $conn->query("ALTER TABLE users AUTO_INCREMENT = 1");
    $conn->query("ALTER TABLE products AUTO_INCREMENT = 1");
    $conn->query("ALTER TABLE orders AUTO_INCREMENT = 1");
    $conn->query("ALTER TABLE order_items AUTO_INCREMENT = 1");

    echo "<p style='color:green;'>1. SUCCESS: Lahat ng tables ay nilinis na.</p>";

    $conn->begin_transaction();

    // 2. INSERT TEST USERS (1 Admin, 4 Sellers, 95 Buyers = 100 Users)
    $conn->query("INSERT INTO users (username, email, password, role) VALUES ('admin_test', 'admin@rpos.test', 'password123', 'admin')");
    $conn->query("INSERT INTO users (username, email, password, role, store_name) VALUES
        ('seller_one', 'seller1@rpos.test', 'password123', 'seller', 'The Rice Trading Post'),
        ('seller_two', 'seller2@rpos.test', 'password123', 'seller', 'Pangasinan Rice Corner'),
        ('seller_three', 'seller3@rpos.test', 'password123', 'seller', 'Golden Harvest Store'),
        ('seller_four', 'seller4@rpos.test', 'password123', 'seller', 'Nueva Ecija Grains')");

    // Auto-generate ng 95 buyers (seq 1..95 -> will become IDs 6..100)
    $buyer_sql = "INSERT INTO users (username, email, password, role) 
        SELECT CONCAT('testuser', seq) AS username, CONCAT('buyer', seq, '@rpos.test') AS email, 'password123' AS password, 'buyer' AS role
        FROM (SELECT 1 AS seq";
    for ($i = 2; $i <= 95; $i++) {
        $buyer_sql .= " UNION SELECT {$i}";
    }
    $buyer_sql .= ") AS T;";
    $conn->query($buyer_sql);

    echo "<p style='color:green;'>2. SUCCESS: 100 Test Users (IDs 1-100) naipasok. Sellers: IDs 2-5.</p>";

    // 3. INSERT TEST PRODUCTS (1000 Products)
    $seller_ids = [2, 3, 4, 5]; // Ito ang IDs ng Sellers
    $records_to_insert = 1000; 
    
   $stmt = $conn->prepare("INSERT INTO products (name, description, price, seller_id) VALUES (?, ?, ?, ?)");
// Tandaan: Apat (4) na placeholder (?) na lang.
    if ($stmt === false) {
        throw new Exception('SQL Prepare Error (Products): ' . $conn->error);
    }
    
    // Correct bind types: name (s), description (s), price (d), quantity (i), seller_id (i)
    for ($i = 1; $i <= $records_to_insert; $i++) {
        $name = "Rice Variant {$i} - " . (rand(0, 1) ? 'Premium' : 'Standard');
        $description = "Test Description for product {$i}. For Load Testing purposes.";
        $price = round(rand(450, 650) / 10, 2); 
        $quantity = rand(100, 500);
        $seller_id = (int)$seller_ids[array_rand($seller_ids)]; 

        $stmt->bind_param("ssds", $name, $description, $price, $seller_id);
        $stmt->execute();
    }
    $stmt->close();
    
    echo "<p style='color:green;'>3. SUCCESS: {$records_to_insert} Products naipasok.</p>";

    $conn->commit();
    echo "<h2>ALL DONE! Handa na ang database para sa k6 testing.</h2>";

// BAGONG CODE (Line 87 pataas)
} catch (Exception $e) {
    // Ang 'mysqli::in_transaction()' ay inalis. 
    // Awtomatikong mag-ro-rollback ang PHP sa COMMIT/ROLLBACK, 
    // pero tatawagin pa rin natin ang rollback para masigurado.
    if (isset($conn)) {
       @$conn->rollback(); // Ginagamit ang @ para hindi mag-throw ng warning kung walang active transaction
    }
    echo "<h2 style='color:red;'>FATAL ERROR: Transaction Failed!</h2>";
    echo "<p><strong>Dahilan:</strong> " . $e->getMessage() . "</p>";
} finally {
     if (isset($conn)) {
        $conn->close();
     }
}
?>