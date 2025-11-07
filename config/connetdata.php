<?php
$DT_HOST = 'localhost';
$DT_USER = 'root';
$DT_PASS = 'giaky113';
$DT_NAME   = 'wed_laptop';

// tạo chuỗi kết nối dns
$dns = "mysql:host=$DT_HOST;dbname=$DT_NAME;charset=utf8";

//Cấu hình giúp an toàn và tiện lợi hơn khi làm việc với PDO
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Hiển thị lỗi dưới dạng ngoại lệ
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,// Thiết lập chế độ lấy dữ liệu mặc định là mảng kết hợp
];
try {
    $pdo = new PDO($dns, $DT_USER, $DT_PASS, $options);
} catch (PDOException $e) {
    die("Kết nối thất bại: " . $e->getMessage());
}
?>