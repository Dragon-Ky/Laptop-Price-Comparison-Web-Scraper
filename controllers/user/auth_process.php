<?php
// /project_root/controllers/user/auth_process.php 

session_start();

// Sửa lỗi BASE_PATH: Lùi 3 cấp để trỏ về thư mục gốc Project
if (!defined('BASE_PATH')) { // 💡 Thêm kiểm tra
    define('BASE_PATH', dirname(dirname(dirname(__FILE__)))); 
}

// 1. Nhúng các file cần thiết
require_once BASE_PATH . '/config/connetdata.php'; 
require_once BASE_PATH . '/core/mail_helper.php'; 

require_once BASE_PATH . '/models/user/LoginUser.php';     
require_once BASE_PATH . '/models/user/RegisterUser.php';  
require_once BASE_PATH . '/models/user/ResetByEmail.php';  
require_once BASE_PATH . '/models/user/reset_password.php'; 

// 2. Kiểm tra action
if (!isset($_POST['action'])) {
    header('Location: /public/index.php'); 
    exit;
}

$action = $_POST['action'];

// 3. Xử lý logic
switch ($action) {
    case 'register':
        // ... (Code đăng ký của bạn - giữ nguyên)
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';

        if ($password !== $password_confirm) {
            $_SESSION['error'] = 'Mật khẩu xác nhận không khớp.';
        } else {
            $result = registerUser($email, $password);
            if ($result['success']) {
                $_SESSION['success'] = 'Đăng ký thành công! Vui lòng đăng nhập.';
                header('Location: /public/user/login.php'); 
                exit;
            } else {
                $_SESSION['error'] = $result['message'];
            }
        }
        header('Location: /public/user/register.php'); 
        break;

    case 'login':
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        // 1. Đổi tên biến $user_id thành $result cho rõ nghĩa
        $result = loginUser($email, $password);

        // 2. Kiểm tra chính xác vào 'success' trong mảng $result
        if ($result['success']) {
            
            // 3. Gán user_id vào session một cách chính xác
            // (Hàm loginUser của bạn trả về $user trong 'user')
            $_SESSION['user_id'] = $result['user']['user_id'];
            $_SESSION['email'] = $result['user']['email']; // Thêm email nếu cần
            
            $_SESSION['success'] = 'Chào mừng bạn trở lại!';
            header('Location: /public/Daskboard.php'); 
        } else {
            // 4. Lấy thông báo lỗi cụ thể từ mảng $result
            $_SESSION['error'] = $result['message'];
            header('Location: /public/user/login.php'); 
        }
        break; // break này đã có

    case 'request_otp':
        // Xử lý Form 1: Yêu cầu gửi mã OTP
        $email = $_POST['email'] ?? '';
        $result = initiatePasswordReset($email); 
        
        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
            $_SESSION['reset_email'] = $email;
            
            // *** SỬA LỖI 1 TẠI ĐÂY ***
            // Nếu thành công, chuyển hướng đến trang NHẬP OTP (Form 2)
            header('Location: /public/user/reset_password.php'); 

        } else {
            $_SESSION['error'] = $result['message'];
            unset($_SESSION['reset_email']);
            
            // Nếu thất bại, ở lại trang YÊU CẦU OTP (Form 1)
            header('Location: /public/user/forgot_password_OTP.php'); 
        }
        break; // Phải có break ở đây

    case 'resend_otp':
        // Gửi lại mã OTP (Resend)
        // Lấy email từ POST hoặc từ session (nếu trước đó đã lưu)
        $email = $_POST['email'] ?? $_SESSION['reset_email'] ?? '';

        if (empty($email)) {
            $_SESSION['error'] = 'Không có email để gửi mã. Vui lòng thử lại.';
            header('Location: /public/user/forgot_password_OTP.php');
            break;
        }

        // Cooldown: tránh spam resend (ví dụ 60 giây)
        $now = time();
        $last = $_SESSION['otp_last_sent'] ?? 0;
        if ($now - $last < 60) {
            $_SESSION['error'] = 'Vui lòng chờ ' . (60 - ($now - $last)) . ' giây trước khi gửi lại mã.';
            header('Location: /public/user/reset_password.php');
            break;
        }

        // Gọi lại hàm khởi tạo/gửi OTP (reuse initiatePasswordReset nếu nó tạo & gửi mới)
        $result = initiatePasswordReset($email);
        if ($result['success']) {
            $_SESSION['success'] = 'Mã đã được gửi lại. Vui lòng kiểm tra email.';
            $_SESSION['reset_email'] = $email;
            $_SESSION['otp_last_sent'] = $now;
            header('Location: /public/user/reset_password.php');
        } else {
            $_SESSION['error'] = $result['message'] ?? 'Gửi mã thất bại. Vui lòng thử lại.';
            header('Location: /public/user/reset_password.php');
        }
        break;

    case 'reset_password_otp': 
        // Xử lý Form 2: Xác minh OTP và Đặt lại MK
        $email = $_POST['email'] ?? '';
        $otp_code = $_POST['otp_code'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Giữ lại email trong session phòng khi có lỗi
        $_SESSION['reset_email'] = $email;

        if ($new_password !== $confirm_password) {
            $_SESSION['error'] = 'Mật khẩu mới và xác nhận không khớp.';
        } elseif (strlen($new_password) < 6) {
             $_SESSION['error'] = 'Mật khẩu phải có ít nhất 6 ký tự.';
        } else {
            $result = resetPassword($email, $otp_code, $new_password);
            
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
                unset($_SESSION['reset_email']); 
                header('Location: /public/user/login.php'); // Thành công -> Về login
                exit;
            } else {
                $_SESSION['error'] = $result['message'];
            }
        }
        
        // *** SỬA LỖI 2 TẠI ĐÂY ***
        // Nếu có lỗi (sai OTP, sai mk...), phải ở lại trang NHẬP OTP (Form 2)
        header('Location: /public/user/reset_password.php'); 
        break;

    default:
        header('Location: /public/user/login.php');
        break;
}

exit;