<link rel="stylesheet" href="/assets/CSS/search_result.css">
<?php

// Lấy giá trị từ URL để kiểm tra selected (nếu không có thì để rỗng)
$g_price   = $_GET['price_range'] ?? '';
$g_cpu     = $_GET['cpu'] ?? '';
$g_ram     = $_GET['ram'] ?? '';
$g_storage = $_GET['storage'] ?? '';
$g_gpu     = $_GET['gpu'] ?? '';
$g_screen  = $_GET['screen'] ?? '';
$g_sort    = $_GET['sort'] ?? '';  // ← Thêm dòng này
?>

<h2>Kết quả tìm kiếm cho: <em><?= htmlspecialchars($query) ?></em></h2>

<form method="GET" style="margin:20px 0; display: flex; flex-wrap: wrap; gap: 15px; align-items: end;">
    <input type="hidden" name="q" value="<?= htmlspecialchars($query ?? '') ?>">

    <div class="filter-group">
        <label style="font-weight:bold; display:block">Mức giá:</label>
        <select name="price_range" style="padding: 5px;">
            <option value="">--Tất cả--</option>
            <option value="0-10000000"        <?= $g_price == '0-10000000' ? 'selected' : '' ?>>Dưới 10 triệu</option>
            <option value="10000000-15000000" <?= $g_price == '10000000-15000000' ? 'selected' : '' ?>>10 - 15 triệu</option>
            <option value="15000000-20000000" <?= $g_price == '15000000-20000000' ? 'selected' : '' ?>>15 - 20 triệu</option>
            <option value="20000000-30000000" <?= $g_price == '20000000-30000000' ? 'selected' : '' ?>>20 - 30 triệu</option>
            <option value="30000000-999999999" <?= $g_price == '30000000-999999999' ? 'selected' : '' ?>>Trên 30 triệu</option>
        </select>
    </div>

    <div class="filter-group">
        <label style="font-weight:bold; display:block">Vi xử lý (CPU):</label>
        <select name="cpu" style="padding: 5px;">
            <option value="">--Tất cả--</option>
            <optgroup label="Intel">
                <option value="i3"    <?= $g_cpu == 'i3' ? 'selected' : '' ?>>Core i3 / Core 3</option>
                <option value="i5"    <?= $g_cpu == 'i5' ? 'selected' : '' ?>>Core i5 / Core 5</option>
                <option value="i7"    <?= $g_cpu == 'i7' ? 'selected' : '' ?>>Core i7 / Core 7</option>
                <option value="i9"    <?= $g_cpu == 'i9' ? 'selected' : '' ?>>Core i9 / Core 9</option>
                <option value="ultra" <?= $g_cpu == 'ultra' ? 'selected' : '' ?>>Core Ultra (AI)</option>
            </optgroup>
            <optgroup label="AMD">
                <option value="ryzen3" <?= $g_cpu == 'ryzen3' ? 'selected' : '' ?>>Ryzen 3</option>
                <option value="ryzen5" <?= $g_cpu == 'ryzen5' ? 'selected' : '' ?>>Ryzen 5</option>
                <option value="ryzen7" <?= $g_cpu == 'ryzen7' ? 'selected' : '' ?>>Ryzen 7</option>
                <option value="ryzen9" <?= $g_cpu == 'ryzen9' ? 'selected' : '' ?>>Ryzen 9</option>
            </optgroup>
            <optgroup label="Apple">
                <option value="m1" <?= $g_cpu == 'm1' ? 'selected' : '' ?>>Apple M1</option>
                <option value="m2" <?= $g_cpu == 'm2' ? 'selected' : '' ?>>Apple M2</option>
                <option value="m3" <?= $g_cpu == 'm3' ? 'selected' : '' ?>>Apple M3</option>
            </optgroup>
        </select>
    </div>

    <div class="filter-group">
        <label style="font-weight:bold; display:block">RAM:</label>
        <select name="ram" style="padding: 5px;">
            <option value="">--Tất cả--</option>
            <option value="8"  <?= $g_ram == '8' ? 'selected' : '' ?>>8 GB</option>
            <option value="16" <?= $g_ram == '16' ? 'selected' : '' ?>>16 GB</option>
            <option value="32" <?= $g_ram == '32' ? 'selected' : '' ?>>32 GB</option>
            <option value="64" <?= $g_ram == '64' ? 'selected' : '' ?>>64 GB trở lên</option>
        </select>
    </div>

    <div class="filter-group">
        <label style="font-weight:bold; display:block">Ổ cứng:</label>
        <select name="storage" style="padding: 5px;">
            <option value="">--Tất cả--</option>
            <option value="256"  <?= $g_storage == '256' ? 'selected' : '' ?>>256 GB</option>
            <option value="512"  <?= $g_storage == '512' ? 'selected' : '' ?>>512 GB</option>
            <option value="1024" <?= $g_storage == '1024' ? 'selected' : '' ?>>1 TB</option>
            <option value="2048" <?= $g_storage == '2048' ? 'selected' : '' ?>>2 TB</option>
        </select>
    </div>

    <div class="filter-group">
        <label style="font-weight:bold; display:block">Card đồ họa:</label>
        <select name="gpu" style="padding: 5px;">
            <option value="">--Tất cả--</option>
            <option value="onboard"    <?= $g_gpu == 'onboard' ? 'selected' : '' ?>>Onboard (Văn phòng)</option>
            <option value="nvidia_rtx" <?= $g_gpu == 'nvidia_rtx' ? 'selected' : '' ?>>NVIDIA RTX (Gaming/Đồ họa)</option>
            <option value="nvidia_gtx" <?= $g_gpu == 'nvidia_gtx' ? 'selected' : '' ?>>NVIDIA GTX (Gaming cũ)</option>
            <option value="amd_rx"     <?= $g_gpu == 'amd_rx' ? 'selected' : '' ?>>AMD Radeon RX</option>
        </select>
    </div>

    <div class="filter-group">
        <label style="font-weight:bold; display:block">Màn hình:</label>
        <select name="screen" style="padding: 5px;">
            <option value="">--Tất cả--</option>
            <option value="13-14" <?= $g_screen == '13-14' ? 'selected' : '' ?>>Nhỏ gọn (13 - 14 inch)</option>
            <option value="15-16" <?= $g_screen == '15-16' ? 'selected' : '' ?>>Tiêu chuẩn (15.6 - 16 inch)</option>
            <option value="17"    <?= $g_screen == '17' ? 'selected' : '' ?>>Lớn (Trên 17 inch)</option>
        </select>
    </div>

    <div class="filter-group">
        <label style="font-weight:bold; display:block">Sắp xếp giá:</label>
        <select name="sort" style="padding: 5px;">
            <option value="">--Mặc định--</option>
            <option value="increase"  <?= $g_sort == 'increase' ? 'selected' : '' ?>>Thấp → Cao</option>
            <option value="decreased" <?= $g_sort == 'decreased' ? 'selected' : '' ?>>Cao → Thấp</option>
        </select>
    </div>

    <div class="filter-group">
        <label style="display:block">&nbsp;</label>
        <button type="submit" style="padding: 6px 15px; cursor:pointer; background: #007bff; color: white; border: none; border-radius: 4px;">
            Lọc Sản Phẩm
        </button>
    </div>
</form>

<hr>
<?php if (!empty($error_message)): ?>
    <p><?= $error_message ?></p>
<?php elseif (!empty($filtered)): ?>
    <?php
    // ← Thêm logic sắp xếp tại đây
    if ($g_sort === 'increase') {
        usort($filtered, function($a, $b) {
            return $a['price'] <=> $b['price'];
        });
    } elseif ($g_sort === 'decreased') {
        usort($filtered, function($a, $b) {
            return $b['price'] <=> $a['price'];
        });
    }
    ?>
    <p>Tìm thấy <?= count($filtered) ?> sản phẩm:</p>
    <table border='1' cellpadding='8' cellspacing='0'>
    <tr style='background:#eee'>
        <th>Trang</th>
        <th>Tên sản phẩm</th>
        <th>CPU</th>
        <th>RAM</th>
        <th>Ổ Đĩa</th>
        <th>GPU</th>
        <th>Giá</th>
        <th>Link</th>
        <th>Yêu thích</th>
    </tr>

    <?php foreach ($filtered as $r): ?>
        <?php
        $url = htmlspecialchars($r['url']);
        $name = htmlspecialchars($r['name']);
        $site = htmlspecialchars($r['site']);
        $cpu = $r['cpu'] ?: '-';
        $ram = $r['ram'] ?: '-';
        $storage = $r['storage'] ?: '-';
        $gpu = $r['gpu'] ?: '-';
        $price = $r['price'];
        $is_bookmarked = in_array(trim($r['url']), $user_bookmarked_urls, true);
        $star_class = $is_bookmarked ? 'btn-bookmark-star bookmarked' : 'btn-bookmark-star';
        $star_html  = $is_bookmarked ? '&#9733;' : '&#9734;';
        ?>
        <tr>
            <td><?= $r['site'] ?></td>
            <td><?= $r['name'] ?></td>
            <td><?= $cpu ?></td>
            <td><?= $ram ?></td>
            <td><?= $storage ?></td>
            <td><?= $gpu ?></td>
            <td><?= format_price($r['price']) ?></td>
            <td><a href='<?= $r['url'] ?>' target='_blank'>Xem</a></td>
            <td style='text-align:center;'>
                <span class='<?= $star_class ?>' 
                      data-url='<?= $url ?>'
                      data-name='<?= $name ?>'
                      data-site='<?= $site ?>'
                      data-price='<?= $price ?>'
                      aria-pressed='<?= ($is_bookmarked ? 'true':'false') ?>'>
                      <?= $star_html ?>
                </span>
            </td>
        </tr>
    <?php endforeach; ?>
    </table>
<?php endif; ?>

<br><a href='../../public/Search/Search.php'>← Quay lại</a>

<link rel="stylesheet" href="/assets/CSS/bookmark_star.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    window.userLoggedIn = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;
</script>
<script src="/assets/JavaScript/bookmark_star.js"></script>