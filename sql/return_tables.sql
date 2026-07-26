-- Product Return System Tables
-- Run this SQL to create the required tables

-- Returns table
CREATE TABLE IF NOT EXISTS `returns` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `order_item_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `product_title` VARCHAR(255) NOT NULL,
    `return_reason` ENUM('wrong_product','damaged','not_as_described','size_issue','quality_issue','other') NOT NULL,
    `return_description` TEXT NOT NULL,
    `return_status` ENUM('requested','under_review','approved','rejected','pickup_scheduled','returned','refund_completed') DEFAULT 'requested',
    `admin_remarks` TEXT DEFAULT NULL,
    `refund_status` ENUM('pending','processing','completed') DEFAULT 'pending',
    `refund_amount` DECIMAL(10,2) DEFAULT NULL,
    `pickup_date` DATE DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`order_item_id`) REFERENCES `order_items`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_active_return` (`order_item_id`, `user_id`, `return_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Return images table
CREATE TABLE IF NOT EXISTS `return_images` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `return_id` INT NOT NULL,
    `image_name` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`return_id`) REFERENCES `returns`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Return status history table
CREATE TABLE IF NOT EXISTS `return_status_history` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `return_id` INT NOT NULL,
    `old_status` VARCHAR(50) DEFAULT NULL,
    `new_status` VARCHAR(50) NOT NULL,
    `remark` TEXT DEFAULT NULL,
    `changed_by` INT DEFAULT NULL,
    `changed_by_type` ENUM('user','admin') DEFAULT 'admin',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`return_id`) REFERENCES `returns`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
