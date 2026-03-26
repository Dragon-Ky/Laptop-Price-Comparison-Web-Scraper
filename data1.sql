-- --------------------------------------------------------
-- Tên Database
-- --------------------------------------------------------
CREATE DATABASE IF NOT EXISTS `WED_Compare_Laptop_Prices` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `WED_Compare_Laptop_Prices`;

-- --------------------------------------------------------
-- 1. Bảng users
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `is_active` BOOLEAN NOT NULL DEFAULT TRUE,--
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 2. Bảng password_resets
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `reset_token` VARCHAR(6) NOT NULL UNIQUE, 
  `expires_at` DATETIME NOT NULL, 
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_pr_user_id` FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  INDEX `idx_reset_token` (`reset_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 3. Bảng products_master
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `products_master` (
  `product_id` INT AUTO_INCREMENT PRIMARY KEY,
  `url` VARCHAR(255) NOT NULL UNIQUE,
  `name` VARCHAR(255) NOT NULL,
  `price` INT NOT NULL,
  `old_price` INT DEFAULT NULL,
  `source_site` VARCHAR(50) NOT NULL,
  `image_url` VARCHAR(500) DEFAULT NULL,
  `brand` VARCHAR(50) DEFAULT NULL,
  `cpu` VARCHAR(100) DEFAULT NULL,
  `ram` VARCHAR(50) DEFAULT NULL,
  `storage` VARCHAR(100) DEFAULT NULL,
  `vga` VARCHAR(100) DEFAULT NULL,
  `screen` VARCHAR(100) DEFAULT NULL,
  `last_updated` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 4. Bảng price_history
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `price_history` (
  `price_history_id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `recorded_price` INT NOT NULL,
  `recorded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_sale` BOOLEAN DEFAULT FALSE,
  CONSTRAINT `fk_ph_product_id` FOREIGN KEY (`product_id`) REFERENCES `products_master`(`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 5. Bảng bookmarks
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `bookmarks` (
  `bookmark_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_bm_user_id` FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bm_product_id` FOREIGN KEY (`product_id`) REFERENCES `products_master`(`product_id`) ON DELETE CASCADE,
  UNIQUE KEY `uk_user_product` (`user_id`, `product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 6. Bảng search_history
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `search_history` (
  `search_history_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT DEFAULT NULL,
  `query_text` VARCHAR(255) NOT NULL,
  `searched_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_sh_user_id` FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 7. Bảng chat_history (MỚI THÊM VÀO)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `chat_history` (
    `chat_id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL COMMENT 'ID người dùng chat',
    `sender` ENUM('user', 'ai') NOT NULL COMMENT 'Ai là người nhắn: user hoặc ai',
    `message` TEXT NOT NULL COMMENT 'Nội dung tin nhắn',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    -- Tạo khóa ngoại để liên kết với bảng users
    -- Nếu xóa user, xóa luôn lịch sử chat của họ (ON DELETE CASCADE)
    CONSTRAINT `fk_chat_user_id` 
        FOREIGN KEY (`user_id`) 
        REFERENCES `users`(`user_id`) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lưu lịch sử chat AI';