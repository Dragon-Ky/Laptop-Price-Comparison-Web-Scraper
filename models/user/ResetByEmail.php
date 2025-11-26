<?php
// models/user/ResetByEmail.php (ĐÃ SỬA LẠI ĐỂ GỬI MÃ OTP 6 SỐ)

define('BASE_PATH', dirname(dirname(dirname(__FILE__)))); 
require_once BASE_PATH . '/core/mail_helper.php'; 

/**
 * Khởi tạo và gửi mã OTP 6 số qua email
 * @param string $email Email của người dùng
 * @return array Mảng kết quả ['success' => bool, 'message' => string]
 */
function initiatePasswordReset($email) {
    $pdo = getPDO(); 

    $stmt = $pdo->prepare("SELECT user_id, email, username FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        // Vẫn trả về thành công để tránh lộ email nào tồn tại/không tồn tại
        // Sửa tin nhắn cho đúng luồng OTP
        return ['success' => true, 'message' => 'Nếu email tồn tại, mã OTP khôi phục đã được gửi.'];
    }

    $user_id = $user['user_id'];
    $username = $user['username'] ?? 'Bạn';
    
    // 💡 THAY ĐỔI 1: Tạo mã OTP 6 số, không dùng token dài
    $otp_code = random_int(100000, 999999); 
    
    // Mã chỉ có hiệu lực 10 phút (600 giây)
    $expires = date("Y-m-d H:i:s", time() + 600); 

    try {
        $pdo->beginTransaction();
        
        // Xóa các mã/token cũ (nếu có)
        $pdo->prepare("DELETE FROM password_resets WHERE user_id = ?")->execute([$user_id]);
        
        // 💡 THAY ĐỔI 2: Thêm mã OTP vào CSDL
        $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, reset_token, expires_at) VALUES (?, ?, ?)");
        // Chúng ta vẫn dùng cột 'reset_token' để lưu mã OTP 6 số
        $stmt->execute([$user_id, $otp_code, $expires]);
        
        // 💡 THAY ĐỔI 3: GỬI EMAIL CHỨA MÃ OTP (Không gửi link)
        $subject = "Mã OTP Khôi phục Mật khẩu Hệ thống So Sánh Giá";
        $body = "Chào {$username},<br><br>
                 Bạn đã yêu cầu đặt lại mật khẩu. Mã xác thực (OTP) của bạn là:<br><br>
                 <h2 style=\"font-size: 32px; letter-spacing: 5px; text-align: center; color: #333;\">
                     {$otp_code}
                 </h2>
                 <br>
                 Mã này sẽ hết hạn sau 10 phút.<br><br>
                 Nếu bạn không yêu cầu hành động này, vui lòng bỏ qua email này.";

        // GỌI HÀM sendEmail TỪ mail_helper.php
        if (sendEmail($user['email'], $username, $subject, $body)) {
             $pdo->commit(); // Chỉ commit khi gửi mail thành công
             
             // 💡 THAY ĐỔI 4: Cập nhật tin nhắn thành công
             return ['success' => true, 'message' => 'Mã OTP đã được gửi. Vui lòng kiểm tra email.'];
        } else {
             $pdo->rollBack(); // Rollback nếu gửi mail thất bại
             return ['success' => false, 'message' =>'Lỗi hệ thống: Không thể gửi email khôi phục. Vui lòng thử lại sau.'];
        }

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Password reset (OTP) initiation error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Lỗi hệ thống khi tạo mã khôi phục.'];
    }
}
?>