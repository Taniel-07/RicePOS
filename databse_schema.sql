-- SQL Schema for Rice POS System
-- Bersyon 5.0: Gi-update base sa gihatag nga ERD

-- Siguraduha nga ang database `rice_pos_db` kay selected.
-- CREATE DATABASE IF NOT EXISTS `rice_pos_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE `rice_pos_db`;

-- =================================================================
-- Lamesa para sa tanang Users (Buyers, Sellers, ug Admin)
-- Gi-combine gikan sa Buyer, Seller, Admin sa ERD. Gidugang ang fields.
-- =================================================================
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL,            -- Gikan sa ERD (generic Name/Username)
    `email` VARCHAR(100) NOT NULL,             -- Gikan sa ERD
    `password` VARCHAR(255) NOT NULL,          -- Gikan sa ERD (Admin)
    `role` ENUM('buyer', 'seller', 'admin') NOT NULL, -- Gikan sa ERD (Admin, implicit sa Buyer/Seller)
    `contact` VARCHAR(50) NULL DEFAULT NULL,     -- Gikan sa ERD (Buyer, Seller)
    `address` TEXT NULL DEFAULT NULL,            -- Gikan sa ERD (Buyer, Seller)
    -- Seller-specific fields
    `store_name` VARCHAR(100) NULL DEFAULT NULL, -- Gikan sa ERD (Seller)
    `fulfillment_option` TEXT NULL DEFAULT NULL, -- Gikan sa ERD (Seller - pwede multiple options like 'Delivery,Pickup')
    -- Daan nga fields nga wala sa ERD pero gikinahanglan nato
    `store_owner` VARCHAR(100) NULL DEFAULT NULL,
    `store_contact` VARCHAR(50) NULL DEFAULT NULL, -- Pwede ni i-merge sa `contact` unya
    `store_email` VARCHAR(100) NULL DEFAULT NULL,  -- Pwede ni i-merge sa `email` unya
    `store_desc` TEXT NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `username` (`username`),
    UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =================================================================
-- Lamesa para sa tanang Produkto (Bugas)
-- Gidugangan og fields gikan sa ERD.
-- =================================================================
CREATE TABLE IF NOT EXISTS `products` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,          -- ProductID
    `seller_id` INT(11) UNSIGNED NOT NULL,                 -- SellerID (FK)
    `name` VARCHAR(100) NOT NULL,                          -- Daan (gikinahanglan)
    `type` VARCHAR(100) NULL DEFAULT NULL,                 -- Gikan sa ERD
    `packaging` VARCHAR(100) NULL DEFAULT NULL,            -- Gikan sa ERD
    `price` DECIMAL(10, 2) NOT NULL,                       -- Gikan sa ERD
    `stock_quantity` INT(11) NULL DEFAULT NULL,            -- Gikan sa ERD
    -- Daan nga fields
    `price_per_kilo` DECIMAL(10, 2) NULL DEFAULT NULL,
    `description` TEXT NULL DEFAULT NULL,
    `image_url` VARCHAR(255) NULL DEFAULT 'https://via.placeholder.com/150',
    `option` ENUM('deliver', 'pickup') NOT NULL,           -- Mas klaro kaysa FulfillmentOption diri
    `created_at` TIMESTAMP NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `seller_id` (`seller_id`),
    CONSTRAINT `products_users_fk` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =================================================================
-- Lamesa para sa Cart (Base sa ERD - Wala pa gigamit sa code)
-- =================================================================
CREATE TABLE IF NOT EXISTS `cart` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,         -- CartID
    `buyer_id` INT(11) UNSIGNED NOT NULL,                -- BuyerID (FK)
    `created_date` TIMESTAMP NOT NULL DEFAULT current_timestamp(), -- CreatedDate
    PRIMARY KEY (`id`),
    KEY `buyer_id` (`buyer_id`),
    CONSTRAINT `cart_users_fk` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =================================================================
-- Lamesa para sa Cart Items (Base sa ERD - Wala pa gigamit sa code)
-- =================================================================
CREATE TABLE IF NOT EXISTS `cart_items` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,       -- CartItemID
    `cart_id` INT(11) UNSIGNED NOT NULL,                 -- CartID (FK)
    `product_id` INT(11) UNSIGNED NOT NULL,              -- ProductID (FK)
    `quantity` INT(11) NOT NULL DEFAULT 1,               -- Quantity
    PRIMARY KEY (`id`),
    KEY `cart_id` (`cart_id`),
    KEY `product_id` (`product_id`),
    CONSTRAINT `cartitems_cart_fk` FOREIGN KEY (`cart_id`) REFERENCES `cart` (`id`) ON DELETE CASCADE,
    CONSTRAINT `cartitems_products_fk` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =================================================================
-- Lamesa para sa mga Orders (Gikan sa `sales_orders` gi-update)
-- =================================================================
CREATE TABLE IF NOT EXISTS `orders` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,            -- OrderID
    `buyer_id` INT(11) UNSIGNED NOT NULL,                     -- BuyerID (FK)
    `total_amount` DECIMAL(10, 2) NOT NULL,                    -- TotalAmount
    `status` ENUM('pending', 'completed', 'cancelled') NOT NULL DEFAULT 'pending', -- Status (Gidugangan og cancelled)
    `fulfillment_type` ENUM('deliver', 'pickup') NULL DEFAULT NULL, -- FulfillmentType (Gikan sa ERD, murag parehas sa 'option')
    `payment_method` VARCHAR(50) NULL DEFAULT 'Cash on Delivery',  -- PaymentMethod (Gikan sa ERD)
    `order_date` TIMESTAMP NOT NULL DEFAULT current_timestamp(), -- OrderDate
    -- Address gikan sa daan nga schema kay mas praktikal diri
    `address_city` VARCHAR(100) NOT NULL,
    `address_purok` VARCHAR(100) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `buyer_id` (`buyer_id`),
    CONSTRAINT `orders_users_fk` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =================================================================
-- Lamesa para sa Order Items (Gikan sa `sales_order_items` gi-update)
-- =================================================================
CREATE TABLE IF NOT EXISTS `order_items` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,        -- OrderItemID
    `order_id` INT(11) UNSIGNED NOT NULL,                  -- OrderID (FK)
    `product_id` INT(11) UNSIGNED NULL DEFAULT NULL,       -- ProductID (FK)
    `quantity` INT(11) NOT NULL DEFAULT 1,                 -- Quantity
    `subtotal` DECIMAL(10, 2) NULL DEFAULT NULL,           -- Subtotal (Gikan sa ERD)
    -- Daan nga fields nga importante para sa history
    `price_at_sale` DECIMAL(10, 2) NOT NULL,
    `product_name_at_sale` VARCHAR(100) NOT NULL,
    `product_option` ENUM('deliver', 'pickup') NOT NULL,
    `seller_id` INT(11) UNSIGNED NOT NULL,                 -- SellerID (FK - gikan sa daan, importante ni)
    PRIMARY KEY (`id`),
    KEY `order_id` (`order_id`),
    KEY `product_id` (`product_id`),
    KEY `seller_id` (`seller_id`),
    CONSTRAINT `orderitems_orders_fk` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `orderitems_products_fk` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
    CONSTRAINT `orderitems_users_fk` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =================================================================
-- Lamesa para sa Payment (Base sa ERD)
-- =================================================================
CREATE TABLE IF NOT EXISTS `payments` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,       -- PaymentID
    `order_id` INT(11) UNSIGNED NOT NULL,                -- OrderID (FK)
    `payment_method` VARCHAR(50) NOT NULL,               -- PaymentMethod
    `amount` DECIMAL(10, 2) NOT NULL,                    -- Amount
    `payment_date` TIMESTAMP NOT NULL DEFAULT current_timestamp(), -- PaymentDate
    PRIMARY KEY (`id`),
    KEY `order_id` (`order_id`),
    CONSTRAINT `payments_orders_fk` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =================================================================
-- Isal-ot ang Default Admin User
-- =================================================================
INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`)
VALUES (1, 'admin', 'admin@system.local', 'admin12pos', 'admin')
ON DUPLICATE KEY UPDATE 
    `username` = 'admin', 
    `email` = 'admin@gmail.com', 
    `password` = 'admin123', 
    `role` = 'admin';