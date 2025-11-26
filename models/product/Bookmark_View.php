<?php

require_once BASE_PATH . '/controllers/Search/Tag_search.php'; 
require_once BASE_PATH . '/controllers/Search/LastSearchInfo.php';
require_once BASE_PATH . '/models/product/PriceHistoryModel.php';


// Lấy thông tin tìm kiếm gần nhất cho user hiện tại
$currentUserId = $_SESSION['user_id'] ?? null;
$lastInfo = getLastSearchInfo($currentUserId);
$lastSearchLabel = $lastInfo['label'] ?? 'Không Có';
$lastSearchTime  = $lastInfo['time']  ?? 'Không Có';

// Hàm định dạng tiền tệ (giữ nguyên)
if (!function_exists('format_price')) {
    function format_price($p) {
        if ($p === null || $p === '') return '—';
        return number_format((float)$p, 0, ',', '.') . "₫";
    }
}

// Đếm số sản phẩm đã bookmark (fallback về 0 nếu biến không tồn tại)
$bookmarkCount = 0;
if (isset($bookmarkedProducts) && is_array($bookmarkedProducts)) {
    $bookmarkCount = count($bookmarkedProducts);
}


// Lấy số lượng giảm giá (mặc định 0, an toàn nếu class hoặc DB không sẵn sàng)
$discount_count = 0;
if (class_exists('PriceHistoryModel')) {
    try {
        $historyModel = new PriceHistoryModel(); // Khởi tạo sẵn để dùng trong vòng lặp
        $discount_count = (int) $historyModel->countPriceDrops();
    } catch (Throwable $e) {
        // log lỗi, giữ $discount_count = 0
        error_log('PriceHistoryModel::countPriceDrops error: ' . $e->getMessage());
    }
}

?>
<div class="dashboard-stats">
    
    <div class="stat-card card-blue">
        <div class="stat-content">
            <h6 class="stat-title">Sản phẩm đã lưu</h6>
            <h2 class="stat-number"><?= $bookmarkCount ?></h2>
        </div>
        <div class="stat-icon">
            <i class="fa-solid fa-laptop"></i>
        </div>
    </div>

    <div class="stat-card card-green">
        <div class="stat-content">
            <h6 class="stat-title">Đang giảm giá</h6>
            
            <h2 class="stat-number"><?php echo $discount_count; ?></h2>
            
            <small class="stat-note">So với hôm qua</small>
        </div>
        <div class="stat-icon">
            <i class="fa-solid fa-arrow-trend-down"></i>
        </div>
    </div>

    <div class="stat-card card-yellow">
        <div class="stat-content">
            <h6 class="stat-title">Sản phẩm tìm lần cuối</h6>
            <h4 class="stat-time"><?= htmlspecialchars($lastSearchLabel) ?></h4>
            <small class="stat-note"><?= htmlspecialchars($lastSearchTime) ?></small>
        </div>
        <div class="stat-icon">
            <i class="fa-solid fa-clock-rotate-left"></i>
        </div>
    </div>

</div>
<?php if (empty($bookmarkedProducts)): ?>
    <p style="text-align:center; margin-top:20px;">Bạn chưa bookmark sản phẩm nào.</p>
<?php else: ?>
    <div class="product-list">
        <h2 class="bookmark-title">
            <i class="fa-regular fa-bookmark"></i> Danh sách sản phẩm đã lưu
        </h2>
        <table class="bookmark-table">
            <tr style='background:#f8f9fa; text-align: left;' class="border-specification">
                <th>Trang</th>
                <th>Tên sản phẩm</th>
                <th>CPU</th>
                <th>RAM</th>
                <th>Ổ Đĩa</th>
                <th>GPU</th>
                <th>Giá</th>
                <th>Biến động</th>
                <th>Link</th>
                <th style="text-align:center;">Chức Năng</th>
            </tr>

            <?php foreach ($bookmarkedProducts as $product): 
                // 1. Chuẩn bị dữ liệu cơ bản
                $productUrl = isset($product['url']) ? trim($product['url']) : '';
                $url        = htmlspecialchars($productUrl ?: '#');
                $name = htmlspecialchars($product['name'] ?? 'Không rõ tên');
                $priceRaw   = $product['price'] ?? null;
                $priceShown = format_price($priceRaw);
                $site       = htmlspecialchars($product['source_site'] ?? '—');
                $bookmark_id = $product['bookmark_id'] ?? null;
                $product_id = $product['product_id'] ?? null;

                // 2. BÓC TÁCH DỮ LIỆU
                $cleanName = $product['name'] ?? '';
                $pattern_cut = '/\s*(?:\||\\\|\/|\,|(?:-\s+)).*|\s+\b(?:Core|Ryzen|i[3579]|R[3579]|Snapdragon|Intel|AMD)\b.*/i';
    
                // Thực hiện cắt chuỗi
                $shortName = preg_replace($pattern_cut, '', $cleanName);
                
                // Xử lý phụ: Nếu cắt xong mà chuỗi quá ngắn (do lỗi format) thì lấy lại tên gốc
                if (strlen($shortName) < 5) {
                    $shortName = $cleanName; 
                }
                
                $shortName = htmlspecialchars(trim($shortName));
                $specs = [];
                if (function_exists('parse_laptop_specs')) {
                    $specs = parse_laptop_specs($product['name']);
                }
                
                $cpu     = $specs['cpu'] ?? '-';
                $ram     = $specs['ram'] ?? '-';
                $storage = $specs['storage'] ?? '-';
                $gpu     = $specs['gpu'] ?? '-';

                // 3. ✅ LẤY BIẾN ĐỘNG GIÁ
                $priceChange = [];
                $changeHtml = '—';
                if ($product_id && $historyModel) {
                    try {
                        $priceChange = $historyModel->getPriceChangePercent($product_id);
                        
                        if ($priceChange['status'] === 'down') {
                            // Giảm giá - Xanh lá
                            $changeHtml = sprintf(
                                '<span style="color:#22c55e; font-weight:bold;">▼ %.2f%% <br><small>-%s</small></span>',
                                abs($priceChange['change_percent']),
                                format_price($priceChange['change_amount'])
                            );
                        } elseif ($priceChange['status'] === 'up') {
                            // Tăng giá - Đỏ
                            $changeHtml = sprintf(
                                '<span style="color:#ef4444; font-weight:bold;">▲ %.2f%% <br><small>+%s</small></span>',
                                $priceChange['change_percent'],
                                format_price($priceChange['change_amount'])
                            );
                        } else {
                            // Không đổi - Xám
                            $changeHtml = '<span style="color:#999;">Không đổi</span>';
                        }
                    } catch (Throwable $e) {
                        error_log('getPriceChangePercent error: ' . $e->getMessage());
                    }
                }
            ?>
                <tr class="border-product">
                    <td><?= $site ?></td>

                    <td><?= $shortName ?></td>

                    <td style="color:#007bff; font-weight:500;"><?= $cpu ?></td>

                    <td><?= $ram ?></td>

                    <td><?= $storage ?></td>

                    <td style="font-size:0.9em;"><?= $gpu ?></td>

                    <td style="color:#d32f2f; font-weight:bold;"><?= $priceShown ?></td>

                    <td><?= $changeHtml ?></td>

                    <td>
                        <?php if ($productUrl): ?>
                            <a href='<?= $url ?>' target='_blank' rel="noopener noreferrer">Xem</a>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>

                    <td class="action-cell" data-product-url="<?= htmlspecialchars($productUrl) ?>" style="text-align:center;">
                        <form method="POST" action="../../controllers/product/remove_bookmark.php" onsubmit="return confirm('Bạn có chắc muốn xóa không?');">
                            <input type="hidden" name="bookmark_id" value="<?= htmlspecialchars($bookmark_id) ?>">
                            <button type="submit" style="background:#ff4d4f; color:white; border:none; padding:5px 10px; cursor:pointer; border-radius:4px;">Xóa</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>

        </table>
    </div>
<?php endif; ?>

<link rel="stylesheet" href="/assets/CSS/bookmark.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    window.userLoggedIn = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;
</script>

<script src="/assets/JavaScript/Bookmark_remote.js"></script>
<script src="/assets/JavaScript/bookmark.js"></script>

