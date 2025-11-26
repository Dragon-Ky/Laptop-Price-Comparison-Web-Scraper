<?php
// Khởi động session để lấy thông tin phiên hiện tại
session_start();

// 1. Xóa tất cả các biến trong session (user_id, email,...)
$_SESSION = [];

// 2. Hủy cookie của session (để trình duyệt quên phiên này đi)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Hủy hoàn toàn session trên server
session_destroy();

// 4. Chuyển hướng về trang đăng nhập
header('Location: /public/user/login.php');
exit;
?>