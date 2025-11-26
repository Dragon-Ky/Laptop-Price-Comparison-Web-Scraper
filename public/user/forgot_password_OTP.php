<?php
// public/user/forgot_password_OTP.php (File Form 1)

session_start();

// ===================================================================
// LOGIC HỦY: Khi người dùng truy cập trang này,
// chúng ta mặc định là họ muốn BẮT ĐẦU LẠI.
unset($_SESSION['reset_email']); 
// ===================================================================

// Lấy thông báo lỗi (nếu controller trả về)
$message = $_SESSION['success'] ?? ($_SESSION['error'] ?? '');
$is_error = isset($_SESSION['error']);
unset($_SESSION['success'], $_SESSION['error']); 
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="/assets/CSS/user.css">
    <title>Khôi phục Mật khẩu</title>
    
</head>
<body>
    <?php
    // --- CHỈ CẦN 1 DÒNG DUY NHẤT NÀY ---
    include '_display_messages.php'; 
    // ------------------------------------
    ?>
    <div class="form-container">
        <?php if (!empty($message)): ?>
            <p style="color: <?php echo $is_error ? 'red' : 'green'; ?>; font-weight: bold;"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <h2>Gửi Mã Khôi Phục (OTP)</h2>
        <form action="../../controllers/user/auth_process.php" method="POST">
            <input type="hidden" name="action" value="request_otp">
            <label for="request_email">Email đã đăng ký:</label>
            <input type="email" id="request_email" name="email" required placeholder="Nhập email">
            <button type="submit">Gửi Mã OTP</button>
        </form>
        <p><a href="login.php">Quay lại Đăng nhập</a></p>
        
    </div>
</body>
</html>