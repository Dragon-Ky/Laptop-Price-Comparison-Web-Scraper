<?php
// public/user/reset_password.php (File Form 2)

session_start();

// Giữ lại logic quan trọng này: Lấy email từ session
$email_to_reset = $_SESSION['reset_email'] ?? '';

// ===================================================================
// LOGIC BẢO VỆ: (Giữ nguyên)
// Nếu không có email trong session (chưa qua Bước 1), đuổi về
if (empty($email_to_reset)) {
    $_SESSION['error'] = 'Vui lòng nhập lại gmail.';
    header('Location: forgot_password_OTP.php');
    exit;
}
// ===================================================================
?>

<!DOCTYPE html>
<html>
<head>
    <title>Khôi Phục Mật Khẩu</title>
    <link rel="stylesheet" href="/assets/CSS/user.css">
</head>
<body>
    
    <div class="form-container">
        
        <?php
        // SỬA 1: Dùng file thông báo chung cho nhất quán
        include '_display_messages.php';
        ?>

        <h2>Xác Minh & Đặt Lại Mật Khẩu</h2>

        <form action="/controllers/user/auth_process.php" method="POST">
            
            <input type="hidden" name="action" value="reset_password_otp">
            
            <label for="reset_email">Email (đã gửi OTP):</label>
            <input type="email" id="reset_email" name="email" 
                   value="<?php echo htmlspecialchars($email_to_reset); ?>" readonly>
            
            <div class="label-resend-wrapper">
                <label for="otp_code">Mã OTP (gửi qua email):</label>

                <a class="resend-link-button"href="#" onclick="document.getElementById('resend_otp_form').submit(); return false;" class="resend-link">
                    Gửi lại mã?
                </a>
            </div>
            
            <input type="text" id="otp_code" name="otp_code" required placeholder="Nhập 6 số" maxlength="6" pattern="\d{6}">
            
            <label for="new_password">Mật khẩu mới (ít nhất 6 ký tự):</label>
            <input type="password" id="new_password" name="new_password" required>
            
            <label for="confirm_password">Xác nhận mật khẩu mới:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
            
            <button type="submit">Đặt Lại Mật Khẩu</button>
        </form>
        
        <p>
            <a href="login.php">Quay lại Đăng nhập</a>
            <a href="forgot_password_OTP.php" class="cancel-link">Nhập email khác?</a>
        </p>

    </div>

    <form id="resend_otp_form" action="/controllers/user/auth_process.php" method="POST" style="display: none;">
    <input type="hidden" name="action" value="resend_otp">
    <input type="hidden" name="email" value="<?php echo htmlspecialchars($email_to_reset); ?>">
</form>

</body>
</html>
</body>
</html>