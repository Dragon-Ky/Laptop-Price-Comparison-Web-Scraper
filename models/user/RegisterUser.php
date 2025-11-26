<?php
// auth.php - Đăng Ký Người Dùng Mới

// require_once 'db_config.php'; 

function registerUser($email, $password) {
    // 1. Kiểm tra tính hợp lệ của input
    if (empty($email) || empty($password) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Email hoặc mật khẩu không hợp lệ.'];
    }

    // ================================================================
    // BẮT ĐẦU: KIỂM TRA ĐỘ PHỨC TẠP MẬT KHẨU
    // ================================================================

    // 1. Kiểm tra độ dài ( >= 6 ký tự)
    if (strlen($password) < 6) {
        return ['success' => false, 'message' => 'Mật khẩu phải có ít nhất 6 ký tự.'];
    }

    // 2. Kiểm tra có chữ Hoa (A-Z)
    if (!preg_match('/[A-Z]/', $password)) {
        return ['success' => false, 'message' => 'Mật khẩu phải có ít nhất một chữ Hoa (A-Z).'];
    }

    // 3. Kiểm tra có chữ thường (a-z)
    if (!preg_match('/[a-z]/', $password)) {
        return ['success' => false, 'message' => 'Mật khẩu phải có ít nhất một chữ thường (a-z).'];
    }

    // 4. Kiểm tra có số (0-9)
    if (!preg_match('/[0-9]/', $password)) { // Bạn cũng có thể dùng '/\d/'
        return ['success' => false, 'message' => 'Mật khẩu phải có ít nhất một số (0-9).'];
    }

    // 5. Kiểm tra có ký tự đặc biệt
    // [^a-zA-Z\d] nghĩa là: bất kỳ ký tự nào KHÔNG PHẢI là chữ cái (a-z, A-Z) hoặc số (\d)
    if (!preg_match('/[^a-zA-Z\d]/', $password)) {
        return ['success' => false, 'message' => 'Mật khẩu phải có ít nhất một ký tự đặc biệt (ví dụ: !@#$%^&*).'];
    }

    // ================================================================
    // KẾT THÚC: KIỂM TRA ĐỘ PHỨC TẠP MẬT KHẨU
    // ================================================================


    // Nếu vượt qua tất cả kiểm tra, mới tiếp tục xử lý với DB
    $pdo = getPDO();
    
    // Kiểm tra trùng email
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetchColumn() > 0) {
        return ['success' => false, 'message' => 'Email đã được đăng ký.'];
    }

    // 2. Băm (Hash) Mật Khẩu
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Ghi dữ liệu vào DB
    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
        $stmt->execute([$email, $email, $hashed_password]); // Sử dụng email làm username tạm thời
        return ['success' => true, 'message' => 'Đăng ký thành công.'];
    } catch (PDOException $e) {
        // Ghi log lỗi thay vì hiển thị trực tiếp
        error_log("Registration error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Đã xảy ra lỗi hệ thống.'];
    }
}
?>