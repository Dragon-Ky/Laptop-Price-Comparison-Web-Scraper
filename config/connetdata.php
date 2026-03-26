<?php
// db_config.php

// 1. Đổi 'localhost' thành tên service trong docker-compose 
define('DB_HOST', 'db'); 

// 2. Tên database 
define('DB_NAME', 'wed_compare_laptop_prices');

// 3. User mặc định của MySQL Docker thường là root
define('DB_USER', 'root');

// 4. Mật khẩu 
define('DB_PASS', 'giaky113');

function getPDO() {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    try {
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        throw new PDOException($e->getMessage(), (int)$e->getCode());
    }
}
?>