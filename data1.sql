-- --------------------------------------------------------
-- Tên Database (Bạn có thể đổi tên tùy ý)
-- --------------------------------------------------------
CREATE DATABASE IF NOT EXISTS `WED_Compare_Laptop_Prices` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `WED_Compare_Laptop_Prices`;

-- --------------------------------------------------------
-- Cấu trúc Bảng: users (Thông tin người dùng và xác thực)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL COMMENT 'Mật khẩu đã được băm (hash)',
  `security_salt` VARCHAR(64) DEFAULT NULL COMMENT 'Salt (tùy chọn)',
  `is_active` BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'Trạng thái kích hoạt tài khoản',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lưu trữ thông tin người dùng';

-- --------------------------------------------------------
-- Cấu trúc Bảng: password_resets (Quản lý Mã OTP Khôi phục Mật khẩu)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  
  -- Mã OTP 6 chữ số. Chúng ta giữ tên cột là reset_token để code PHP ít phải thay đổi
  `reset_token` VARCHAR(6) NOT NULL UNIQUE COMMENT 'Mã OTP 6 chữ số duy nhất', 
  
  `expires_at` DATETIME NOT NULL COMMENT 'Thời điểm mã OTP hết hạn (thường là 5-10 phút)',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  -- Khóa ngoại liên kết với bảng users
  CONSTRAINT `fk_pr_user_id` 
    FOREIGN KEY (`user_id`) 
    REFERENCES `users`(`user_id`)
    ON DELETE CASCADE,
    
  -- Index để tìm kiếm mã OTP nhanh
  INDEX `idx_reset_token` (`reset_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lưu trữ mã OTP khôi phục mật khẩu';

-- --------------------------------------------------------
-- Cấu trúc Bảng: products_master (Lưu trữ vĩnh viễn các sản phẩm quan trọng)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `products_master` (
  `product_id` INT AUTO_INCREMENT PRIMARY KEY,
  `url` VARCHAR(255) NOT NULL UNIQUE COMMENT 'URL gốc của sản phẩm (Khóa chính)',
  `name` VARCHAR(255) NOT NULL,
  `price` INT NOT NULL COMMENT 'Giá bán hiện tại (đã làm sạch)',
  `old_price` INT DEFAULT NULL COMMENT 'Giá niêm yết/Giá cũ',
  `source_site` VARCHAR(50) NOT NULL COMMENT 'Nguồn scrape (TinhHocNgoiSao, PhucAnh, ...)',
  `specs_summary` TEXT DEFAULT NULL COMMENT 'Cấu hình tóm tắt',
  
  `last_updated` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Thời điểm cập nhật cuối cùng'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Master data sản phẩm được theo dõi/bookmark';

-- --------------------------------------------------------
-- Cấu trúc Bảng: price_history (Lịch sử biến động giá)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `price_history` (
  `history_id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `recorded_price` INT NOT NULL COMMENT 'Giá trị ghi nhận',
  `recorded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời điểm ghi nhận',
  `is_sale` BOOLEAN DEFAULT FALSE COMMENT 'Đánh dấu nếu là giá sale',
  
  -- Khóa ngoại
  CONSTRAINT `fk_ph_product_id` 
    FOREIGN KEY (`product_id`) 
    REFERENCES `products_master`(`product_id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Theo dõi lịch sử giá của sản phẩm Master';

-- --------------------------------------------------------
-- Cấu trúc Bảng: bookmarks (Quan hệ người dùng yêu thích sản phẩm)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `bookmarks` (
  `bookmark_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  -- Khóa ngoại
  CONSTRAINT `fk_bm_user_id` 
    FOREIGN KEY (`user_id`) 
    REFERENCES `users`(`user_id`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_bm_product_id` 
    FOREIGN KEY (`product_id`) 
    REFERENCES `products_master`(`product_id`)
    ON DELETE CASCADE,
    
  -- Đảm bảo mỗi người dùng chỉ bookmark 1 sản phẩm 1 lần
  UNIQUE KEY `uk_user_product` (`user_id`, `product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lưu trữ sản phẩm yêu thích của người dùng';

-- --------------------------------------------------------
-- Cấu trúc Bảng: search_history (Lịch sử tìm kiếm)
-- --------------------------------------------------------
-- --------------------------------------------------------
-- Cấu trúc Bảng: search_history (Lịch sử tìm kiếm)
-- (Bảng này phải được tạo TRƯỚC search_query_tags)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `search_history` (
  `history_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT DEFAULT NULL COMMENT 'NULL cho khách vãng lai',
  `query_text` VARCHAR(255) NOT NULL COMMENT 'Từ khóa tìm kiếm gốc',
  `searched_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  -- Khóa ngoại (user_id có thể là NULL)
  CONSTRAINT `fk_sh_user_id` 
    FOREIGN KEY (`user_id`) 
    REFERENCES `users`(`user_id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lịch sử tìm kiếm của người dùng';

-- --------------------------------------------------------
-- Cấu trúc Bảng: search_query_tags (Tags/Facet nâng cao)
-- 
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `search_query_tags` (
  `tag_id` INT AUTO_INCREMENT PRIMARY KEY,
  `history_id` INT NOT NULL,
  `facet_name` VARCHAR(50) NOT NULL COMMENT 'Tên yếu tố (VD: Brand, CPU, RAM)',
  `facet_value` VARCHAR(100) NOT NULL COMMENT 'Giá trị yếu tố (VD: Dell, Core i5, 16GB)',
  
  -- Khóa ngoại
  CONSTRAINT `fk_sqt_history_id` 
    FOREIGN KEY (`history_id`) 
    REFERENCES `search_history`(`history_id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lưu trữ các thành phần lọc (tags) của truy vấn';
