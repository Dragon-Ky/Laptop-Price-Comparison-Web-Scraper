<?php

require_once BASE_PATH . '/controllers/Search/Tag_search.php'; 
require_once BASE_PATH . '/controllers/Search/LastSearchInfo.php';
require_once BASE_PATH . '/models/product/PriceHistoryModel.php';

// --- 1. KHỞI TẠO DỮ LIỆU & HÀM HỖ TRỢ ---

$currentUserId = $_SESSION['user_id'] ?? null;
$lastInfo = getLastSearchInfo($currentUserId);
$lastSearchLabel = $lastInfo['label'] ?? 'Không Có';
$lastSearchTime  = $lastInfo['time']  ?? 'Không Có';

if (!function_exists('format_price')) {
    function format_price($p) {
        if ($p === null || $p === '') return '—';
        return number_format((float)$p, 0, ',', '.') . "₫";
    }
}

$bookmarkCount = 0;
if (isset($bookmarkedProducts) && is_array($bookmarkedProducts)) {
    $bookmarkCount = count($bookmarkedProducts);
}

$discount_count = 0;
$historyModel = null;

if (class_exists('PriceHistoryModel')) {
    try {
        $historyModel = new PriceHistoryModel(); 
        
        // --- SỬA LỖI: Chỉ đếm trong danh sách bookmark của user ---
        if (!empty($bookmarkedProducts)) {
            foreach ($bookmarkedProducts as $bp) {
                // Kiểm tra biến động giá cho từng sản phẩm trong bookmark
                $pc = $historyModel->getPriceChangePercent($bp['product_id']);
                
                // Nếu trạng thái là 'down' (giảm giá) thì tăng biến đếm
                if (isset($pc['status']) && $pc['status'] === 'down') {
                    $discount_count++;
                }
            }
        }

    } catch (Throwable $e) {}
}

// --- 2. XỬ LÝ DỮ LIỆU & LỌC (CORE LOGIC) ---

// A. Lấy tham số từ URL (Đổi tên biến cho dễ quản lý)
$filter_price   = $_GET['price_range'] ?? '';
$filter_cpu     = $_GET['cpu'] ?? '';
$filter_ram     = $_GET['ram'] ?? '';
$filter_storage = $_GET['storage'] ?? '';
$filter_gpu     = $_GET['gpu'] ?? '';
$filter_screen  = $_GET['screen'] ?? '';
$sort_option    = $_GET['sort'] ?? '';

if (!empty($bookmarkedProducts)) {
    
    // B. CHUẨN BỊ DỮ LIỆU (Parse Specs, Format Tên, Lấy giá biến động)
    foreach ($bookmarkedProducts as &$product) {
        // 1. Làm gọn tên sản phẩm
        $cleanName = $product['name'] ?? '';
        $pattern_cut = '/\s*(?:\||\\\\|\/|,|\(|-\s+).*|\s+\b(?:Core|Ryzen|i[3579]|R[3579]|Snapdragon|Intel|AMD)\b.*/i';
        $shortName = preg_replace($pattern_cut, '', $cleanName);
        if (strlen($shortName) < 5) { $shortName = $cleanName; }
        $product['display_name'] = htmlspecialchars(trim($shortName));

        // 2. Parse Specs
        $specs = [];
        if (function_exists('parse_laptop_specs')) {
            $specs = parse_laptop_specs($product['name']);
        }
        $product['specs'] = $specs; 

        // 3. Xử lý hiển thị biến động giá
        $changeHtml = '<span style="color:#999;">—</span>';
        if (isset($product['product_id']) && $historyModel) {
            try {
                $priceChange = $historyModel->getPriceChangePercent($product['product_id']);
                if ($priceChange['status'] === 'down') {
                    $changeHtml = sprintf('<span style="color:#22c55e; font-weight:bold;">▼ %.2f%% <br><small>-%s</small></span>', abs($priceChange['change_percent']), format_price($priceChange['change_amount']));
                } elseif ($priceChange['status'] === 'up') {
                    $changeHtml = sprintf('<span style="color:#ef4444; font-weight:bold;">▲ %.2f%% <br><small>+%s</small></span>', $priceChange['change_percent'], format_price($priceChange['change_amount']));
                } else {
                    $changeHtml = '<span style="color:#999;">Không đổi</span>';
                }
            } catch (Throwable $e) {}
        }
        $product['price_change_html'] = $changeHtml;
    }
    unset($product); // Ngắt tham chiếu

    // C. THỰC HIỆN LỌC (FILTER)
    // Lưu ý: Phải truyền đúng tên biến ($filter_...) vào mệnh đề use
    $bookmarkedProducts = array_filter($bookmarkedProducts, function($product) use ($filter_price, $filter_cpu, $filter_ram, $filter_storage, $filter_gpu, $filter_screen) {
        $specs = $product['specs'] ?? [];
        
        // 1. Lọc Giá
        if ($filter_price !== '') {
            $price = (float)($product['price'] ?? 0);
            $parts = explode('-', $filter_price);
            $min = (float)$parts[0];
            $max = (float)($parts[1] ?? 999999999);
            if ($price < $min || $price > $max) return false;
        }

        // 2. Lọc CPU
        if ($filter_cpu !== '') {
            if (stripos($specs['cpu'] ?? '', $filter_cpu) === false) return false;
        }

        // 3. Lọc RAM
        if ($filter_ram !== '') {
            if (strpos($specs['ram'] ?? '', $filter_ram) === false) return false;
        }

        // 4. Lọc Storage (Ổ cứng)
        if ($filter_storage !== '') {
            $hddSpec = $specs['storage'] ?? '';
            if ($filter_storage == '1024') {
                 // Tìm 1TB hoặc 1024GB
                 if (stripos($hddSpec, '1TB') === false && stripos($hddSpec, '1024') === false) return false;
            } else {
                 if (stripos($hddSpec, $filter_storage) === false) return false;
            }
        }

        // 5. Lọc GPU
        if ($filter_gpu !== '') {
            $gpuSpec = $specs['gpu'] ?? '';
            if ($filter_gpu == 'onboard') {
                // Nếu tìm thấy dấu hiệu card rời thì LOẠI
                if (stripos($gpuSpec, 'RTX') !== false || stripos($gpuSpec, 'GTX') !== false || stripos($gpuSpec, 'RX') !== false) return false;
            } elseif ($filter_gpu == 'nvidia_rtx') {
                if (stripos($gpuSpec, 'RTX') === false) return false;
            } elseif ($filter_gpu == 'nvidia_gtx') {
                if (stripos($gpuSpec, 'GTX') === false) return false;
            } elseif ($filter_gpu == 'amd_rx') {
                if (stripos($gpuSpec, 'RX') === false) return false;
            }
        }



        return true;
    });

    // D. SẮP XẾP (SORT) - Chỉ chạy khi người dùng chọn
    if ($sort_option === 'increase') {
        usort($bookmarkedProducts, function($a, $b) {
            return ($a['price'] ?? 0) <=> ($b['price'] ?? 0);
        });
    } elseif ($sort_option === 'decreased') {
        usort($bookmarkedProducts, function($a, $b) {
            return ($b['price'] ?? 0) <=> ($a['price'] ?? 0);
        });
    }
}
?>

<div class="dashboard-stats">
    <div class="stat-card card-blue" >
        <div class="stat-content">
            <h6 class="stat-title">Sản phẩm đã lưu</h6>
            <h2 class="stat-number"><?= $bookmarkCount ?></h2>
            <small class="stat-note"> <br></small>
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

<?php if (empty($bookmarkedProducts) && empty($filter_price) && empty($filter_cpu) && empty($filter_ram)): ?>
    <p style="text-align:center; margin-top:20px;">Bạn chưa bookmark sản phẩm nào.</p>
<?php else: ?>
    <div class="product-list">
        <h2 class="bookmark-title">
            <i class="fa-regular fa-bookmark"></i> Danh sách sản phẩm đã lưu
        </h2>

        <form method="GET" style="margin:20px 0; display: flex; flex-wrap: wrap; gap: 15px; align-items: end;" class="sort_form">
            <input type="hidden" name="products" value="<?= htmlspecialchars($_GET['products'] ?? '') ?>">

            <div class="filter-group">
                <label style="font-weight:bold; display:block">Mức giá:</label>
                <select name="price_range" style="padding: 5px;text-align: left;">
                    <option value="">Tất cả</option>
                    <option value="0-10000000"        <?= $filter_price == '0-10000000' ? 'selected' : '' ?>>Dưới 10 triệu</option>
                    <option value="10000000-15000000" <?= $filter_price == '10000000-15000000' ? 'selected' : '' ?>>10 - 15 triệu</option>
                    <option value="15000000-20000000" <?= $filter_price == '15000000-20000000' ? 'selected' : '' ?>>15 - 20 triệu</option>
                    <option value="20000000-30000000" <?= $filter_price == '20000000-30000000' ? 'selected' : '' ?>>20 - 30 triệu</option>
                    <option value="30000000-999999999" <?= $filter_price == '30000000-999999999' ? 'selected' : '' ?>>Trên 30 triệu</option>
                </select>
            </div>

            <div class="filter-group">
                <label style="font-weight:bold; display:block">Vi xử lý (CPU):</label>
                <select name="cpu" style="padding: 5px;text-align: left;">
                    <option value="">Tất cả</option>
                    <optgroup label="Intel">
                        <option value="i3"    <?= $filter_cpu == 'i3' ? 'selected' : '' ?>>Core i3 / Core 3</option>
                        <option value="i5"    <?= $filter_cpu == 'i5' ? 'selected' : '' ?>>Core i5 / Core 5</option>
                        <option value="i7"    <?= $filter_cpu == 'i7' ? 'selected' : '' ?>>Core i7 / Core 7</option>
                        <option value="i9"    <?= $filter_cpu == 'i9' ? 'selected' : '' ?>>Core i9 / Core 9</option>
                        <option value="ultra" <?= $filter_cpu == 'ultra' ? 'selected' : '' ?>>Core Ultra (AI)</option>
                    </optgroup>
                    <optgroup label="AMD">
                        <option value="ryzen3" <?= $filter_cpu == 'ryzen3' ? 'selected' : '' ?>>Ryzen 3</option>
                        <option value="ryzen5" <?= $filter_cpu == 'ryzen5' ? 'selected' : '' ?>>Ryzen 5</option>
                        <option value="ryzen7" <?= $filter_cpu == 'ryzen7' ? 'selected' : '' ?>>Ryzen 7</option>
                        <option value="ryzen9" <?= $filter_cpu == 'ryzen9' ? 'selected' : '' ?>>Ryzen 9</option>
                    </optgroup>
                    <optgroup label="Apple">
                        <option value="m1" <?= $filter_cpu == 'm1' ? 'selected' : '' ?>>Apple M1</option>
                        <option value="m2" <?= $filter_cpu == 'm2' ? 'selected' : '' ?>>Apple M2</option>
                        <option value="m3" <?= $filter_cpu == 'm3' ? 'selected' : '' ?>>Apple M3</option>
                    </optgroup>
                </select>
            </div>

            <div class="filter-group">
                <label style="font-weight:bold; display:block">RAM:</label>
                <select name="ram" style="padding: 5px;text-align: left;">
                    <option value="">Tất cả</option>
                    <option value="8"  <?= $filter_ram == '8' ? 'selected' : '' ?>>8 GB</option>
                    <option value="16" <?= $filter_ram == '16' ? 'selected' : '' ?>>16 GB</option>
                    <option value="32" <?= $filter_ram == '32' ? 'selected' : '' ?>>32 GB</option>
                    <option value="64" <?= $filter_ram == '64' ? 'selected' : '' ?>>64 GB trở lên</option>
                </select>
            </div>

            <div class="filter-group">
                <label style="font-weight:bold; display:block">Ổ cứng:</label>
                <select name="storage" style="padding: 5px;text-align: left;">
                    <option value="">Tất cả</option>
                    <option value="256"  <?= $filter_storage == '256' ? 'selected' : '' ?>>256 GB</option>
                    <option value="512"  <?= $filter_storage == '512' ? 'selected' : '' ?>>512 GB</option>
                    <option value="1024" <?= $filter_storage == '1024' ? 'selected' : '' ?>>1 TB</option>
                    <option value="2048" <?= $filter_storage == '2048' ? 'selected' : '' ?>>2 TB</option>
                </select>
            </div>

            <div class="filter-group">
                <label style="font-weight:bold; display:block">Card đồ họa:</label>
                <select name="gpu" style="padding: 5px; text-align: left;">
                    <option value="">Tất cả</option>
                    <option value="onboard"    <?= $filter_gpu == 'onboard' ? 'selected' : '' ?>>Onboard (Văn phòng)</option>
                    <option value="nvidia_rtx" <?= $filter_gpu == 'nvidia_rtx' ? 'selected' : '' ?>>NVIDIA RTX (Gaming/Đồ họa)</option>
                    <option value="nvidia_gtx" <?= $filter_gpu == 'nvidia_gtx' ? 'selected' : '' ?>>NVIDIA GTX (Gaming cũ)</option>
                    <option value="amd_rx"     <?= $filter_gpu == 'amd_rx' ? 'selected' : '' ?>>AMD Radeon RX</option>
                </select>
            </div>

            <div class="filter-group">
                <label style="font-weight:bold; display:block">Sắp xếp giá:</label>
                <select name="sort" style="padding: 5px;text-align: left;">
                    <option value="">Mặc định</option>
                    <option value="increase"  <?= $sort_option == 'increase' ? 'selected' : '' ?>>Thấp → Cao</option>
                    <option value="decreased" <?= $sort_option == 'decreased' ? 'selected' : '' ?>>Cao → Thấp</option>
                </select>
            </div>

            <div class="filter-group">
                <label style="display:block">&nbsp;</label>
                <button type="submit" style="padding: 6px 12px; cursor:pointer; background: #007bff; color: white; border: none; border-radius: 4px;">
                    ✔
                </button>
            </div>
        </form>

        <?php if (empty($bookmarkedProducts)): ?>
             <p style="text-align:center; margin: 20px; color: #666;">Không tìm thấy sản phẩm phù hợp với bộ lọc.</p>
        <?php else: ?>
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
                    <th style="text-align:center;">Gỡ Sản Phẩm</th>
                </tr>

                <?php foreach ($bookmarkedProducts as $product): 
                    $productUrl = isset($product['url']) ? trim($product['url']) : '';
                    $url        = htmlspecialchars($productUrl ?: '#');
                    $site       = htmlspecialchars($product['source_site'] ?? '—');
                    
                    $cpu     = $product['specs']['cpu'] ?? '-';
                    $ram     = $product['specs']['ram'] ?? '-';
                    $storage = $product['specs']['storage'] ?? '-';
                    $gpu     = $product['specs']['gpu'] ?? '-';
                    $priceShown = format_price($product['price'] ?? null);
                ?>
                    <tr class="border-product">
                        <td class="center"><?= $site ?></td>
                        <td><?= $product['display_name'] ?></td>

                        <td style="color:<?= ($cpu === '-') ? '#ccc' : '#666' ?> " class="center">
                            <?= $cpu ?>
                        </td>

                        <td class="center"><?= $ram ?></td>
                        <td class="center"><?= $storage ?></td>
                        <td style="font-size:0.9em;" class="center"><?= $gpu ?></td>
                        <td style="color:black" class="center"><?= $priceShown ?></td>
                        <td><?= $product['price_change_html'] ?></td>

                        <td>
                            <?php if ($productUrl): ?>
                                <a href='<?= $url ?>' target='_blank' rel="noopener noreferrer">Xem</a>
                            <?php else: ?>—<?php endif; ?>
                        </td>

                        <td class="action-cell" data-product-url="<?= htmlspecialchars($productUrl) ?>" style="text-align:center;">
                            <form method="POST" action="../../controllers/product/remove_bookmark.php" onsubmit="return confirm('Bạn có chắc muốn xóa không?');">
                                <input type="hidden" name="bookmark_id" value="<?= htmlspecialchars($product['bookmark_id'] ?? '') ?>">
                                <button type="submit" >⨉</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
<?php endif; ?>

<link rel="stylesheet" href="/assets/CSS/bookmark.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    window.userLoggedIn = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;
</script>
<script src="/assets/JavaScript/Bookmark_remote.js"></script>
<script src="/assets/JavaScript/bookmark.js"></script>