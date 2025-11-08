<?php
require_once 'db_connect.php';

try {
    $seller_id = $_POST['seller_id'] ?? 0;
    $name = $_POST['name'] ?? '';
    $price = $_POST['price'] ?? 0.0;
    $price_per_kilo = $_POST['perKilo'] ?? null;
    $desc = $_POST['desc'] ?? '';
    $option = $_POST['option'] ?? 'deliver'; // Corresponds to fulfillment_type more or less
    $type = $_POST['type'] ?? null; // Bag-ong field gikan sa ERD
    $packaging = $_POST['packaging'] ?? null; // Bag-ong field gikan sa ERD
    $stock_quantity = $_POST['stock_quantity'] ?? null; // Bag-ong field gikan sa ERD

    $image_url = 'https://via.placeholder.com/150';

    if (empty($seller_id) || empty($name) || empty($price)) {
        json_response(null, false, 'Seller, Name, and Price are required.', 400);
    }

    if (isset($_FILES['riceImage']) && $_FILES['riceImage']['error'] == 0) {
        $target_dir = "../uploads/";
        if (!is_dir($target_dir)) {
             mkdir($target_dir, 0777, true); // Create directory if it doesn't exist
        }
        $file_extension = strtolower(pathinfo($_FILES["riceImage"]["name"], PATHINFO_EXTENSION));
        $new_filename = uniqid('rice_', true) . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;
        $check = getimagesize($_FILES["riceImage"]["tmp_name"]);
        if ($check === false) throw new Exception("File is not a valid image.");
        if ($_FILES["riceImage"]["size"] > 5000000) throw new Exception("Sorry, your file is too large (Max 5MB).");
        $allowed_types = ['jpg', 'png', 'jpeg', 'gif'];
        if (!in_array($file_extension, $allowed_types)) throw new Exception("Sorry, only JPG, JPEG, PNG & GIF files are allowed.");
        if (move_uploaded_file($_FILES["riceImage"]["tmp_name"], $target_file)) {
            $image_url = "uploads/" . $new_filename;
        } else {
            // Provide more specific error info if possible
             $upload_error = $_FILES['riceImage']['error'];
             throw new Exception("Sorry, there was an error uploading your file. Error code: {$upload_error}");
        }
    }

    // Gamiton ang `products` table ug bag-ong fields
    $stmt = $conn->prepare("INSERT INTO products (seller_id, name, price, price_per_kilo, description, `option`, image_url, type, packaging, stock_quantity) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt === false) throw new Exception('SQL Prepare Error: ' . $conn->error);

    $per_kilo_value = !empty($price_per_kilo) ? $price_per_kilo : null;
    $stock_qty_value = !empty($stock_quantity) ? (int)$stock_quantity : null;

    // Adjust bind_param types: i = integer, d = double, s = string
    // seller_id(i), name(s), price(d), price_per_kilo(d), desc(s), option(s), image_url(s), type(s), packaging(s), stock_quantity(i)
    $stmt->bind_param("isddsssssi", $seller_id, $name, $price, $per_kilo_value, $desc, $option, $image_url, $type, $packaging, $stock_qty_value);


    if ($stmt->execute()) {
        json_response(['id' => $conn->insert_id, 'image_url' => $image_url], true, 'Product added successfully.');
    } else {
        throw new Exception('Failed to add product to database: ' . $stmt->error);
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