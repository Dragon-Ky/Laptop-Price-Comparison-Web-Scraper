<?php
// models/user/ResetPassword.php (Dùng OTP Code)

function resetPassword($email, $otp_code, $new_password) {

    // ================================================================
    // BẮT ĐẦU: KIỂM TRA ĐỘ PHỨC TẠP MẬT KHẨU MỚI
    // (Kiểm tra ngay lập tức trước khi truy vấn DB)
    // ================================================================

    // 1. Kiểm tra độ dài ( >= 6 ký tự)
    if (strlen($new_password) < 6) {
        return ['success' => false, 'message' => 'Mật khẩu mới phải có ít nhất 6 ký tự.'];
    }

    // 2. Kiểm tra có chữ Hoa (A-Z)
    if (!preg_match('/[A-Z]/', $new_password)) {
        return ['success' => false, 'message' => 'Mật khẩu mới phải có ít nhất một chữ Hoa (A-Z).'];
    }

    // 3. Kiểm tra có chữ thường (a-z)
    if (!preg_match('/[a-z]/', $new_password)) {
        return ['success' => false, 'message' => 'Mật khẩu mới phải có ít nhất một chữ thường (a-z).'];
    }

    // 4. Kiểm tra có số (0-9)
    if (!preg_match('/[0-9]/', $new_password)) { // Bạn cũng có thể dùng '/\d/'
        return ['success' => false, 'message' => 'Mật khẩu mới phải có ít nhất một số (0-9).'];
    }

    // 5. Kiểm tra có ký tự đặc biệt
    // [^a-zA-Z\d] nghĩa là: bất kỳ ký tự nào KHÔNG PHẢI là chữ cái hoặc số
    if (!preg_match('/[^a-zA-Z\d]/', $new_password)) {
        return ['success' => false, 'message' => 'Mật khẩu mới phải có ít nhất một ký tự đặc biệt (ví dụ: !@#$%^&*).'];
    }

    // ================================================================
    // KẾT THÚC: KIỂM TRA ĐỘ PHỨC TẠP MẬT KHẨU
    // ================================================================


    // Nếu mật khẩu mới hợp lệ, mới tiếp tục xử lý OTP và DB
    $pdo = getPDO();
    $now = date("Y-m-d H:i:s");

    // 1. Tìm User và Xác minh OTP Code còn hạn
    $stmt = $pdo->prepare("
        SELECT u.user_id 
        FROM users u
        JOIN password_resets pr ON u.user_id = pr.user_id
        WHERE u.email = ? AND pr.reset_token = ? AND pr.expires_at > ?
    ");
    $stmt->execute([$email, $otp_code, $now]);
    $reset_info = $stmt->fetch();

    if (!$reset_info) {
        return ['success' => false, 'message' => 'Mã OTP không đúng hoặc đã hết hạn. Vui lòng thử lại.'];
    }

    $user_id = $reset_info['user_id'];
    // Chỉ băm mật khẩu khi mọi thứ đã hợp lệ
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // 2. Cập nhật mật khẩu và xóa Code
    try {
        $pdo->beginTransaction();
        
        // Cập nhật mật khẩu
        $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?")->execute([$hashed_password, $user_id]);
        
        // Xóa code (để tránh sử dụng lại)
        $pdo->prepare("DELETE FROM password_resets WHERE user_id = ?")->execute([$user_id]);
        
        $pdo->commit();
        return ['success' => true, 'message' => 'Mật khẩu đã được đặt lại thành công.'];

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Password reset error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Lỗi hệ thống khi cập nhật mật khẩu.'];
    }
}
?>