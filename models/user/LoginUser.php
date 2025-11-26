<?php
// auth.php - Đăng Nhập

function loginUser($email, $password) {
    $pdo = getPDO();
    
    // 1. Lấy thông tin người dùng từ DB
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        return ['success' => false, 'message' => 'Sai email hoặc mật khẩu.'];
    }
    
    // 2. Xác minh mật khẩu
    if (password_verify($password, $user['password_hash'])) {
        // Đăng nhập thành công: Khởi tạo session
        session_start();
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['email'] = $user['email'];
        return ['success' => true, 'message' => 'Đăng nhập thành công.', 'user' => $user];
    } else {
        return ['success' => false, 'message' => 'Sai email hoặc mật khẩu.'];
    }
}
?>