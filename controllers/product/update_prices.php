<?php
// admin/update_prices.php
// Tăng thời gian chạy tối đa (vì cập nhật nhiều sản phẩm sẽ lâu)
set_time_limit(300); 

session_start();
define('BASE_PATH', dirname(__DIR__)); // Sửa lại đường dẫn tùy cấu trúc folder của bạn
require_once BASE_PATH . '/core/helpers.php';
require_once BASE_PATH . '/models/product/PriceHistoryModel.php';

// Kiểm tra quyền Admin (Tùy chọn)
// if (!isset($_SESSION['is_admin'])) die("Bạn không có quyền truy cập!");

$pdo = getPDO();
$historyModel = new PriceHistoryModel();

// ✅ THAY ĐỔI: Lấy chỉ các sản phẩm đã bookmark
$sql = "SELECT DISTINCT pm.product_id, pm.name, pm.original_url, pm.price 
        FROM products_master pm
        INNER JOIN bookmarks b ON pm.product_id = b.product_id";

$stmt = $pdo->query($sql);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$count_updated = 0;
$count_error = 0;

echo "<h1>Đang cập nhật giá (Chỉ sản phẩm đã bookmark)...</h1>";
echo "<ul>";

foreach ($products as $prod) {
    $current_price = 0;
    
    try {
        $current_price = getPriceFromUrl($prod['original_url']); 
    } catch (Exception $e) {
        echo "<li style='color:red'>Lỗi link: {$prod['name']}</li>";
        $count_error++;
        continue;
    }

    if ($current_price > 0) {
        $saved = $historyModel->recordPrice($prod['product_id'], $current_price);
        
        if ($saved) {
             $stmtUpdate = $pdo->prepare("UPDATE products_master SET price = ? WHERE product_id = ?");
             $stmtUpdate->execute([$current_price, $prod['product_id']]);
             
             echo "<li style='color:green'>✓ Cập nhật: <b>{$prod['name']}</b> - Giá mới: " . number_format($current_price) . "₫</li>";
             $count_updated++;
        } else {
             echo "<li>→ Không đổi: {$prod['name']}</li>";
        }
    }
}

echo "</ul>";
echo "<h3>Hoàn tất! Cập nhật $count_updated sản phẩm. Lỗi $count_error.</h3>";
echo "<a href='dashboard.php'>Quay lại Dashboard</a>";


/* =======================================================
   HÀM HỖ TRỢ GIẢ LẬP (Bạn cần thay bằng logic crawl thực tế của bạn)
   ======================================================= */
function getPriceFromUrl($url) {
    // Logic dùng cURL hoặc file_get_contents để tải HTML từ $url
    // Sau đó dùng DOMDocument hoặc Regex để lấy giá
    // Ví dụ cơ bản:
    // $html = file_get_contents($url);
    // ... Regex bóc giá ...
    // return $price;
    
    return 0; // Trả về 0 nếu chưa viết logic
}


?>