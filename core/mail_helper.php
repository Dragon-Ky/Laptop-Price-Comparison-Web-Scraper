<?php
// mail_helper.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Sửa đường dẫn phù hợp thư mục PHPMailer
require_once 'PHPMailer-master/src/Exception.php';
require_once 'PHPMailer-master/src/PHPMailer.php';
require_once 'PHPMailer-master/src/SMTP.php';

/**
 * Hàm gửi email chung
 * @param string $toEmail Email người nhận
 * @param string $toName Tên người nhận
 * @param string $subject Tiêu đề email
 * @param string $body Nội dung HTML của email
 * @return bool True nếu gửi thành công, False nếu thất bại
 */
function sendEmail($toEmail, $toName, $subject, $body) {
    $mail = new PHPMailer(true);
    try {
        // Cấu hình SMTP (Giữ nguyên của bạn)
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'luugiaky100@gmail.com'; // Email của bạn
        $mail->Password   = 'amuf eexl lwsb vxjm';    // Mật khẩu ứng dụng
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;
        
        $mail->CharSet = 'UTF-8'; // Thêm để hỗ trợ Tiếng Việt
        $mail->setFrom('luugiaky100@gmail.com', 'Hệ Thống So Sánh Giá'); // Sửa lại email gửi
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Lỗi gửi email: " . $mail->ErrorInfo);
        return false;
    }
}

// *** XÓA TOÀN BỘ CODE TỪ "if ($_SERVER..." Ở ĐÂY ***
// *** FILE HELPER CHỈ NÊN CHỨA HÀM ***
?>