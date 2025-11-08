<?php
// ============== DATABASE SETUP SCRIPT (DETAILED VERSION) ==============
// I-RUN LANG NI KAUSA SA IMONG BROWSER

header('Content-Type: text/plain');

// --- I-edit ni kung lahi imong credentials ---
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "rice_pos_db"; // Ngalan sa imong database

// 1. Create Connection to MySQL Server
$conn = new mysqli($servername, $username, $password);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . "\n");
}
echo "Successfully connected to MySQL Server.\n";

// 2. Create Database
$sql_create_db = "CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if ($conn->query($sql_create_db) === TRUE) {
    echo "Database '$dbname' created successfully or already exists.\n";
} else {
    die("Error creating database: " . $conn->error . "\n");
}

// 3. Select the database
$conn->select_db($dbname);

// 4. SQL statements to create tables with detailed comments
$sql_tables = "

-- =================================================================
-- Lamesa para sa tanang Users (Buyers ug Sellers)
-- Ania i-save ang tanang account information.
-- =================================================================
CREATE TABLE IF NOT EXISTS users (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,          -- Pangalan para login
    email VARCHAR(100) NOT NULL UNIQUE,             -- Email sa user, para login pud
    password VARCHAR(255) NOT NULL,                 -- Password (Dapat i-hash ni sa tinuod nga app)
    role ENUM('buyer', 'seller') NOT NULL,         -- Klase sa user: mamalitay o mamaligyaay
    
    -- Kini nga mga kolum para lang sa mga 'seller'
    store_name VARCHAR(100),                        -- Pangalan sa tindahan sa seller
    store_owner VARCHAR(100),                       -- Pangalan sa tag-iya
    store_contact VARCHAR(50),                      -- Contact number sa tindahan
    store_email VARCHAR(100),                       -- Email sa tindahan
    store_desc TEXT,                                -- Deskripsyon sa tindahan
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP  -- Petsa kung kanus-a nag-register
);

-- =================================================================
-- Lamesa para sa tanang Produkto (Bugas)
-- Ania i-save ang detalye sa matag bugas nga gibaligya.
-- =================================================================
CREATE TABLE IF NOT EXISTS products (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    seller_id INT(11) UNSIGNED NOT NULL,            -- KINI ANG LINK paingon sa 'users' table (kinsay seller)
    name VARCHAR(100) NOT NULL,                     -- Pangalan sa bugas (e.g., Ganador)
    price DECIMAL(10, 2) NOT NULL,                  -- Presyo kada 25kg
    price_per_kilo DECIMAL(10, 2),                  -- Presyo kada kilo (optional)
    description TEXT,                               -- Deskripsyon sa bugas
    image_url VARCHAR(255) DEFAULT 'https://via.placeholder.com/150', -- Link sa hulagway sa produkto
    `option` ENUM('deliver', 'pickup') NOT NULL,    -- Pilian kung i-deliver ba o i-pickup
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Petsa kung kanus-a gidugang ang produkto
    
    -- Kini ang nag-konektar sa product ngadto sa seller niini
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =================================================================
-- Lamesa para sa mga Transaksyon o Orders (Sales)
-- Kini ang resibo sa matag order.
-- =================================================================
CREATE TABLE IF NOT EXISTS sales (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    buyer_id INT(11) UNSIGNED NOT NULL,             -- KINI ANG LINK paingon sa 'users' table (kinsay buyer)
    total_amount DECIMAL(10, 2) NOT NULL,           -- Ang kinatibuk-ang bayranan
    status ENUM('pending', 'completed') NOT NULL DEFAULT 'pending', -- Status sa order
    address_city VARCHAR(100) NOT NULL,             -- Address sa buyer (City)
    address_purok VARCHAR(100) NOT NULL,            -- Address sa buyer (Purok)
    sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  -- Petsa kung kanus-a gihimo ang order
    
    -- Kini ang nag-konektar sa sale ngadto sa buyer
    FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =================================================================
-- Lamesa para sa Detalye sa matag Order (Sale Items)
-- KINI ANG PINAKA-IMPORTANTE NGA PART. Ania ang lista sa mga produkto
-- sulod sa usa ka transaksyon.
-- =================================================================
CREATE TABLE IF NOT EXISTS sale_items (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_id INT(11) UNSIGNED NOT NULL,              -- KINI ANG LINK paingon sa 'sales' table (para mahibaw-an asa ni nga order)
    product_id INT(11) UNSIGNED,                    -- KINI ANG LINK paingon sa 'products' table (para mahibaw-an unsa nga produkto)
    quantity INT(11) NOT NULL DEFAULT 1,            -- Pila kabuok gipalit (default kay 1 ka sako)
    price_at_sale DECIMAL(10, 2) NOT NULL,          -- Presyo sa produkto AT THE TIME OF SALE (importante ni para sa history)
    product_name_at_sale VARCHAR(100) NOT NULL,     -- Pangalan sa produkto at the time of sale
    product_option ENUM('deliver', 'pickup') NOT NULL, -- Option sa produkto at the time of sale
    seller_id INT(11) UNSIGNED NOT NULL,            -- KINI ANG LINK paingon sa 'users' table (kinsay seller aning item)
    
    -- Mga koneksyon sa ubang lamesa
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL, -- Kung ma-delete ang produkto, dili ma-delete ang order history
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE
);
";

// 5. Execute multi-query for creating tables
if ($conn->multi-query($sql_tables)) {
    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->more_results() && $conn->next_result());
    echo "All tables created successfully or already exist.\n";
} else {
    die("Error creating tables: " . $conn->error . "\n");
}

echo "\nDATABASE SETUP COMPLETE AND DETAILED!\n";
$conn->close();

?>