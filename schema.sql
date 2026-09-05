-- BOXRETAIL Enterprise Database Schema
-- Optimized for MySQL 5.7+ / MySQL 8.x / MariaDB (cPanel / LiteSpeed)

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `role` ENUM('admin', 'employee') DEFAULT 'employee',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Products Table
CREATE TABLE IF NOT EXISTS `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sku` VARCHAR(100) NOT NULL UNIQUE,
  `title` VARCHAR(255) NOT NULL,
  `box_size` VARCHAR(100) NOT NULL,
  `category` VARCHAR(100) NOT NULL,
  `size_category` VARCHAR(50) DEFAULT 'Medium',
  `length` FLOAT NOT NULL DEFAULT 12,
  `width` FLOAT NOT NULL DEFAULT 12,
  `height` FLOAT NOT NULL DEFAULT 12,
  `wall_strength` VARCHAR(50) DEFAULT 'ECT-32',
  `description` TEXT,
  `price_inr` DECIMAL(10,2) NOT NULL DEFAULT 45.00,
  `stock_qty` INT NOT NULL DEFAULT 500,
  `image_url` TEXT,
  `discount_tiers_json` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Orders Table
CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_number` VARCHAR(50) NOT NULL UNIQUE,
  `user_id` INT DEFAULT NULL,
  `username` VARCHAR(100) NOT NULL,
  `user_name` VARCHAR(150) NOT NULL,
  `subtotal_amount_inr` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_savings_inr` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_amount_inr` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_quantity` INT NOT NULL DEFAULT 0,
  `shipping_notes` TEXT,
  `status` ENUM('Processing', 'Shipped', 'Delivered') DEFAULT 'Processing',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Order Items Table
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `product_id` INT DEFAULT NULL,
  `title` VARCHAR(255) NOT NULL,
  `box_size` VARCHAR(100) NOT NULL,
  `unit_price_inr` DECIMAL(10,2) NOT NULL,
  `quantity` INT NOT NULL,
  `discount_percent` INT NOT NULL DEFAULT 0,
  `discounted_unit_price_inr` DECIMAL(10,2) NOT NULL,
  `total_item_price_inr` DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert Master Admin, Sample Sanity Employee, and Default Accounts
-- Passwords hashed using Universal Salted HMAC SHA-256 Protocol
INSERT INTO `users` (`username`, `password_hash`, `name`, `role`) VALUES
('admin', 'sha256$defaultsalt2026$8c6976e5b5410415bde908bd4dee15dfb167a9c873fc4bb8a81f6f2ab448a918', 'Master Admin', 'admin'),
('sanity_emp', 'sha256$sanitysalt2026$3fe9f944f83419bb2f3a286793f777203d86e1c2e392e5aa638257d56eb86890', 'Sanity Test Employee', 'employee'),
('emp_john', 'sha256$defaultsalt2026$54c46f13e71781297e6be9a3b6d274bf4187fcebf88e13296c0989b5c30164c4', 'John Miller (Sales)', 'employee'),
('emp_sarah', 'sha256$defaultsalt2026$54c46f13e71781297e6be9a3b6d274bf4187fcebf88e13296c0989b5c30164c4', 'Sarah Jenkins (Logistics)', 'employee'),
('emp_alex', 'sha256$defaultsalt2026$54c46f13e71781297e6be9a3b6d274bf4187fcebf88e13296c0989b5c30164c4', 'Alex Rivera (Warehouse)', 'employee'),
('emp_david', 'sha256$defaultsalt2026$54c46f13e71781297e6be9a3b6d274bf4187fcebf88e13296c0989b5c30164c4', 'David Vance (Procurement)', 'employee')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);
