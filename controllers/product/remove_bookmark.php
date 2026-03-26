<?php
// controllers/product/remove_bookmark.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('BASE_PATH', dirname(dirname(__DIR__))); 
require_once BASE_PATH . '/models/product/BookmarkModel.php'; 

// Chỉ chấp nhận POST và đã đăng nhập
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    header('Location: ../../public/Bookmark/Bookmark.php');
    exit;
}

// 1. Lấy dữ liệu đầu vào (AN TOÀN HƠN)
$bookmark_id = $_POST['bookmark_id'] ?? null;
$user_id = (int)$_SESSION['user_id'];

if (empty($bookmark_id)) {
    header('Location: ../../public/Bookmark/Bookmark.php?error=missing_id');
    exit;
}

try {
    // 2. Khởi tạo Model
    $bookmarkModel = new BookmarkModel();

    // 3. Gọi hàm xóa mới (rất đơn giản)
    $bookmarkModel->removeBookmarkById((int)$bookmark_id, $user_id);

    // 4. Chuyển hướng
    header('Location: ../../public/Bookmark/Bookmark.php?success=removed');
    exit;

} catch (Exception $e) {
    error_log("Lỗi khi xóa bookmark: " . $e->getMessage());
    header('Location: ../../public/Bookmark/Bookmark.php?error=unknown');
    exit;
}