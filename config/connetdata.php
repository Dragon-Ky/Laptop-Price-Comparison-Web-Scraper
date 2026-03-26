<?php
function getPDO() {
    $host = 'db'; // Tên service trong Docker
    $db   = 'wed_compare_laptop_prices';
    $user = 'root';
    $pass = 'giaky113';
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    
    // Sử dụng biến static để tránh tạo nhiều kết nối gây chậm hệ thống
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (\PDOException $e) {
            throw new \PDOException($e->getMessage(), (int)$e->getCode());
        }
    }
    return $pdo;
}