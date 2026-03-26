<?php
file_put_contents(__DIR__.'/price_job.log', date('c')." START\n", FILE_APPEND);
// Tăng thời gian chạy lên 5 phút để script không bị timeout giữa chừng
set_time_limit(300); 

session_start();
// Định nghĩa đường dẫn gốc, tùy cấu trúc folder mà sửa lại cho đúng
define('BASE_PATH', dirname(__DIR__)); 

require_once BASE_PATH . '/core/helpers.php';
require_once BASE_PATH . '/models/product/PriceHistoryModel.php';

// Kết nối Database và khởi tạo Model xử lý lịch sử giá
$pdo = getPDO();
$historyModel = new PriceHistoryModel();

// ✅ QUAN TRỌNG: Chỉ lấy những sản phẩm nào ĐÃ ĐÁNH DẤU (bookmark) để cập nhật
// Dùng Inner Join để lọc, đỡ tốn tài nguyên server quét mấy cái không cần thiết
$sql = "SELECT DISTINCT pm.product_id, pm.name, pm.original_url, pm.price 
        FROM products_master pm
        INNER JOIN bookmarks b ON pm.product_id = b.product_id";

$stmt = $pdo->query($sql);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$count_updated = 0;
$count_error = 0;

echo "<h1>Đang cập nhật giá (Chỉ sản phẩm đã bookmark)...</h1>";
echo "<ul>";

// Bắt đầu duyệt qua từng sản phẩm
foreach ($products as $prod) {
    // Ghi log lại ID sản phẩm đang xử lý để debug nếu có lỗi
    // Lưu ý: biến $current_price ở dòng này có thể chưa có dữ liệu (nếu chạy lần đầu), chỉ để track ID
    file_put_contents(__DIR__.'/price_job.log', date('c')." {$prod['product_id']} => processing...\n", FILE_APPEND);

    $current_price = 0;
    
    try {
        // Gọi hàm cào giá từ URL gốc của sản phẩm
        $current_price = getPriceFromUrl($prod['original_url']); 
    } catch (Exception $e) {
        echo "<li style='color:red'>Lỗi link: {$prod['name']}</li>";
        $count_error++;
        continue; // Lỗi thì bỏ qua, nhảy sang sản phẩm kế tiếp
    }

    // Nếu cào được giá (lớn hơn 0) thì mới xử lý tiếp
    if ($current_price > 0) {
        // 1. Lưu vào bảng lịch sử giá trước
        $saved = $historyModel->recordPrice($prod['product_id'], $current_price);
        
        if ($saved) {
             // 2. Nếu giá có biến động, cập nhật luôn vào bảng master
             $stmtUpdate = $pdo->prepare("UPDATE products_master SET price = ? WHERE product_id = ?");
             $stmtUpdate->execute([$current_price, $prod['product_id']]);
             
             echo "<li style='color:green'>✓ Cập nhật: <b>{$prod['name']}</b> - Giá mới: " . number_format($current_price) . "₫</li>";
             $count_updated++;
        } else {
             // Giá không đổi thì thôi, báo nhẹ một câu
             echo "<li>→ Không đổi: {$prod['name']}</li>";
        }
    }
}

echo "</ul>";
echo "<h3>Hoàn tất! Cập nhật $count_updated sản phẩm. Lỗi $count_error.</h3>";
echo "<a href='dashboard.php'>Quay lại Dashboard</a>";


/* =======================================================
   HÀM HỖ TRỢ CÀO DATA (Logic crawl giữ nguyên)
   ======================================================= */
function getPriceFromUrl($url) {
    // Khởi tạo cURL để giả lập trình duyệt truy cập web
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true, // Cho phép tự động chuyển hướng (redirect)
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CONNECTTIMEOUT => 10,
        // Fake User Agent để giống người thật, tránh bị chặn
        CURLOPT_USERAGENT => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120 Safari/537.36",
        CURLOPT_HTTPHEADER => [
            "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
            "Accept-Language: vi-VN,vi;q=0.9,en;q=0.8"
        ],
    ]);

    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Nếu không lấy được nội dung hoặc lỗi server (404, 500...) thì trả về 0
    if ($html === false || $httpCode >= 400) {
        curl_close($ch);
        return 0;
    }
    curl_close($ch);

    // CÁCH 1: Thử tìm trong JSON-LD (Schema.org) - Cách này chuẩn nhất
    if (preg_match_all('/<script[^>]*type="application\/ld\+json"[^>]*>(.*?)<\/script>/is', $html, $matches)) {
        foreach ($matches[1] as $json) {
            $data = json_decode(trim($json), true);
            if (!$data) continue;

            $price = extractPriceFromJsonLd($data);
            if ($price > 0) return $price;
        }
    }

    // CÁCH 2: Thử tìm trong thẻ Meta (Open Graph)
    if (preg_match('/property="product:price:amount"\s+content="([^"]+)"/i', $html, $m)) {
        return normalizePrice($m[1]);
    }
    if (preg_match('/property="og:price:amount"\s+content="([^"]+)"/i', $html, $m)) {
        return normalizePrice($m[1]);
    }

    // CÁCH 3: Đường cùng thì dùng Regex quét text thô (Hên xui)
    if (preg_match('/([0-9]{1,3}(?:[.,][0-9]{3})+)\s*(₫|đ|vnd)/iu', $html, $m)) {
        return normalizePrice($m[1]);
    }

    return 0;
}

// Hàm đệ quy để mò tìm giá trong đống dữ liệu JSON đa cấp
function extractPriceFromJsonLd($data) {
    if (isset($data[0])) {
        foreach ($data as $item) {
            $p = extractPriceFromJsonLd($item);
            if ($p > 0) return $p;
        }
        return 0;
    }

    if (isset($data['@graph']) && is_array($data['@graph'])) {
        foreach ($data['@graph'] as $item) {
            $p = extractPriceFromJsonLd($item);
            if ($p > 0) return $p;
        }
        return 0;
    }

    if (isset($data['offers'])) {
        $offers = $data['offers'];

        if (isset($offers[0])) {
            foreach ($offers as $off) {
                if (isset($off['price'])) return normalizePrice($off['price']);
            }
        } else {
            if (isset($offers['price'])) return normalizePrice($offers['price']);
        }
    }

    if (isset($data['price'])) return normalizePrice($data['price']);

    return 0;
}

// Hàm làm sạch giá: bỏ hết ký tự lạ, chỉ giữ lại số
function normalizePrice($str) {
    $s = preg_replace('/[^0-9]/', '', (string)$str);
    if ($s === '') return 0;
    return (int)$s;
}
?>