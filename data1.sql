-- create_schema.sql
-- Database: compare_price (tạo DB nếu chưa có)
CREATE DATABASE IF NOT EXISTS Wed_laptop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE Wed_laptop;

-- 1) users
CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  display_name VARCHAR(100),
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_login DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) sites (nguồn dữ liệu)
CREATE TABLE IF NOT EXISTS sites (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) NOT NULL UNIQUE, -- e.g. 'shopee'
  name VARCHAR(255) NOT NULL,
  base_url VARCHAR(500),
  active TINYINT(1) DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3) search_results_cache (kho cache JSON)
CREATE TABLE IF NOT EXISTS search_results_cache (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  query VARCHAR(500) NOT NULL,
  result_json LONGTEXT NOT NULL,
  version VARCHAR(50) DEFAULT 'v1',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_query (query(200)),
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4) searches (lịch sử người dùng)
CREATE TABLE IF NOT EXISTS searches (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  query VARCHAR(500) NOT NULL,
  result_cache_id BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (result_cache_id) REFERENCES search_results_cache(id) ON DELETE SET NULL,
  INDEX idx_user_created (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5) favorites (theo dõi sản phẩm)
CREATE TABLE IF NOT EXISTS favorites (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  site_id INT UNSIGNED NOT NULL,
  product_url VARCHAR(1000) NOT NULL,
  last_seen_price BIGINT,
  added_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
  UNIQUE KEY ux_user_site_url (user_id, site_id, product_url(500))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6) product_prices (lưu snapshot giá theo từng site)
CREATE TABLE IF NOT EXISTS product_prices (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id BIGINT UNSIGNED NULL,
  site_id INT UNSIGNED NOT NULL,
  product_name VARCHAR(500) NOT NULL,
  product_url VARCHAR(1000),
  image_url VARCHAR(1000),
  price BIGINT NOT NULL,
  currency VARCHAR(10) DEFAULT 'VND',
  scraped_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY ux_site_product_url (site_id, product_url(500)),
  INDEX idx_product_id (product_id),
  FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional: demo data for sites
INSERT IGNORE INTO sites (code, name, base_url) VALUES
('shopee', 'Shopee', 'https://shopee.vn'),
('lazada', 'Lazada', 'https://www.lazada.vn'),
('tiki', 'Tiki', 'https://tiki.vn'),
('cellphones', 'CellphoneS', 'https://cellphones.com.vn'),
('tiktokshop', 'TikTok Shop', 'https://tiktokshop.com');

