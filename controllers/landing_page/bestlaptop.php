<?php
// 1. ĐỊNH NGHĨA ĐƯỜNG DẪN VÀ KẾT NỐI
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(dirname(dirname(__FILE__)))); 
}

// Nhúng file cấu hình kết nối (Chứa hàm getPDO)
require_once BASE_PATH . '/config/connetdata.php';

// Khởi tạo kết nối PDO
try {
    $conn = getPDO(); 
} catch (Exception $e) {
    die("Lỗi kết nối: " . $e->getMessage());
}

// 2. LẤY DỮ LIỆU (Tăng giới hạn lên 100 để tìm kiếm nguồn đa dạng dễ hơn)
$sql = "SELECT * FROM products_master ORDER BY last_updated DESC LIMIT 100";
$stmt = $conn->query($sql); 
$products_pool = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. THUẬT TOÁN LỌC: ƯU TIÊN 4 NGUỒN KHÁC NHAU
$display_products = [];
$used_sources = [];
$backup_products = []; 

if ($products_pool) {
    foreach ($products_pool as $product) {
        $source = $product['source_site'];
        
        // KIỂM TRA: Nếu nguồn này chưa từng xuất hiện trong danh sách hiển thị
        if (!in_array($source, $used_sources)) {
            $display_products[] = $product; // Thêm vào danh sách chính
            $used_sources[] = $source;      // Đánh dấu nguồn này đã dùng
        } else {
            // Nếu trùng nguồn, đẩy vào danh sách dự phòng
            $backup_products[] = $product;
        }
        
        // Nếu đã tìm đủ 4 sản phẩm khác nguồn -> DỪNG NGAY
        if (count($display_products) >= 10) {
            break;
        }
    }
}

// 4. LẤP ĐẦY (FALLBACK)
// Nếu sau khi quét hết mà vẫn chưa đủ 4 (do DB chỉ có 2-3 nguồn), lấy thêm từ backup
while (count($display_products) < 4 && !empty($backup_products)) {
    $display_products[] = array_shift($backup_products);
}

// Kết quả: $display_products bây giờ chứa tối đa 4 sản phẩm, ưu tiên nguồn khác nhau nhất có thể.
?>