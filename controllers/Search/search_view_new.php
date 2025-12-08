<link rel="stylesheet" href="/assets/CSS/search_result.css">

<style>
    /* CSS Giữ nguyên như cũ cho đẹp */
    .product-group-card {
        background: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        margin-bottom: 20px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        overflow: hidden;
        transition: transform 0.2s;
    }
    .product-group-card:hover {
        border-color: #007bff;
        transform: translateY(-2px);
    }
    .group-header {
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
        background: #fff;
    }
    .group-info h3 {
        margin: 0 0 10px 0;
        font-size: 1.2rem;
        color: #333;
    }
    /* Thêm style cho phần cấu hình chi tiết bên trong danh sách shop */
    .shop-specs-detail {
        font-size: 0.8rem;
        color: #666;
        display: block;
        margin-top: 4px;
    }
    .group-price-action {
        text-align: right;
        min-width: 220px;
    }
    .price-range {
        font-size: 1.25rem;
        font-weight: bold;
        color: #d70018;
        margin-bottom: 8px;
    }
    .btn-compare {
        background: #007bff;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 600;
    }
    .group-sellers {
        display: none;
        border-top: 1px solid #e0e0e0;
        background: #f8f9fa;
    }
    .seller-table { width: 100%; border-collapse: collapse; }
    .seller-table td, .seller-table th { padding: 12px 20px; text-align: left; border-bottom: 1px solid #eee; }
    .btn-visit { background: #ffffffff; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; }
</style>

<?php
$s_price     = $_GET['price_range'] ?? '';
$s_money     = $_GET['sort'] ?? '';
$s_num_shops = $_GET['num_shops'] ?? '';
?>

<h2>Kết quả tìm kiếm cho: <em><?= htmlspecialchars($query) ?></em></h2>

<form method="GET" style="margin:20px 0; display: flex; flex-wrap: wrap; gap: 15px; align-items: end;">
    <input type="hidden" name="products" value="<?= htmlspecialchars($query ?? '') ?>">

    <div class="filter-group">
        <label style="font-weight:bold; display:block">Mức giá:</label>
        <select name="price_range" style="padding: 5px;">
            <option value="">Tất cả</option>
            <option value="0-10000000"        <?= $s_price == '0-10000000' ? 'selected' : '' ?>>Dưới 10 triệu</option>
            <option value="10000000-15000000" <?= $s_price == '10000000-15000000' ? 'selected' : '' ?>>10 - 15 triệu</option>
            <option value="15000000-20000000" <?= $s_price == '15000000-20000000' ? 'selected' : '' ?>>15 - 20 triệu</option>
            <option value="20000000-30000000" <?= $s_price == '20000000-30000000' ? 'selected' : '' ?>>20 - 30 triệu</option>
            <option value="30000000-999999999" <?= $s_price == '30000000-999999999' ? 'selected' : '' ?>>Trên 30 triệu</option>
        </select>
    </div>

    <div class="filter-group">
        <label style="font-weight:bold; display:block">Độ phổ biến:</label>
        <select name="num_shops" style="padding: 5px;">
            <option value="">Tất cả</option>
            <option value="2" <?= $s_num_shops == '2' ? 'selected' : '' ?>>Từ 2 nơi bán trở lên</option>
            <option value="3" <?= $s_num_shops == '3' ? 'selected' : '' ?>>Từ 3 nơi bán trở lên</option>
        </select>
    </div>

    <div class="filter-group">
        <label style="font-weight:bold; display:block">Sắp xếp:</label>
        <select name="sort" style="padding: 5px;">
            <option value="">Mặc định</option>
            <option value="increase"  <?= $s_money == 'increase' ? 'selected' : '' ?>>Giá thấp trước</option>
            <option value="decreased" <?= $s_money == 'decreased' ? 'selected' : '' ?>>Giá cao trước</option>
        </select>
    </div>

    <div class="filter-group">
        <button type="submit" style="padding: 6px 15px; cursor:pointer; background: #007bff; color: white; border: none; border-radius: 4px;">
            Lọc Ngay
        </button>
    </div>
</form>

<hr>

<?php if (!empty($error_message)): ?>
    <div style="padding: 20px; background: #fff3cd; color: #856404; border-radius: 5px;">
        <?= $error_message ?>
    </div>
<?php elseif (!empty($grouped_products)): ?>

    <p>Tìm thấy <strong><?= count($grouped_products) ?></strong> dòng máy phù hợp:</p>

    <?php foreach ($grouped_products as $key => $group): ?>
        <div class="product-group-card">
            
            <div class="group-header" onclick="toggleSellers('<?= $key ?>')">
                <div class="group-info">
                    <h3><?= htmlspecialchars($group['display_name']) ?></h3>
                    <div style="font-size:0.9rem; color:#888;">
                        Có <strong><?= count($group['sellers']) ?></strong> lựa chọn cấu hình/giá bán
                    </div>
                </div>

                <div class="group-price-action">
                    <div class="price-range">
                        <?= format_price($group['min_price']) ?>
                        <?php if($group['min_price'] != $group['max_price']): ?>
                             - <?= format_price($group['max_price']) ?>
                        <?php endif; ?>
                    </div>
                    <?php 
                        $unique_sellers_count = count(array_unique(array_column($group['sellers'], 'site')));
                    ?>
                    <button class="btn-compare">
                        Xem <?= $unique_sellers_count ?> nơi bán ▼
                    </button>
                </div>
            </div>

            <div id="sellers-<?= $key ?>" class="group-sellers">
                <table class="seller-table">
                    <thead>
                        <tr>
                            <th width="15%">Cửa hàng</th>
                            <th width="45%">Thông tin chi tiết</th>
                            <th width="20%">Giá bán</th>
                            <th width="20%" style="text-align: center;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        usort($group['sellers'], fn($a, $b) => $a['price'] <=> $b['price']);
                        ?>
                        
                        <?php foreach ($group['sellers'] as $seller): ?>
                            <?php
                            $is_bookmarked = in_array(trim($seller['url']), $user_bookmarked_urls ?? [], true);
                            $star_class = $is_bookmarked ? 'btn-bookmark-star bookmarked' : 'btn-bookmark-star';
                            $star_html  = $is_bookmarked ? '&#9733;' : '&#9734;';
                            ?>
                            <tr>
                                <td style="font-weight:bold; color: #0056b3;">
                                    <?= htmlspecialchars($seller['site']) ?>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($seller['name']) ?></strong>
                                    <span class="shop-specs-detail">
                                        CPU: <?= $seller['cpu'] ?> | RAM: <?= $seller['ram'] ?> | SSD: <?= $seller['storage'] ?>
                                    </span>
                                </td>
                                <td style="color: #d70018; font-weight: bold;">
                                    <?= format_price($seller['price']) ?>
                                </td>
                                <td style="text-align: center;">
                                    <a href="<?= $seller['url'] ?>" target="_blank" class="btn-visit">Xem</a>
                                    <span class='<?= $star_class ?>' 
                                          style="font-size: 20px; cursor: pointer; margin-left: 10px; vertical-align: middle;"
                                          data-url='<?= htmlspecialchars($seller['url']) ?>'
                                          data-name='<?= htmlspecialchars($seller['name']) ?>'
                                          data-site='<?= htmlspecialchars($seller['site']) ?>'
                                          data-price='<?= $seller['price'] ?>'
                                          aria-pressed='<?= ($is_bookmarked ? 'true':'false') ?>'>
                                          <?= $star_html ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        </div>
    <?php endforeach; ?>

<?php endif; ?>

<br><a href='../../public/Search/Search.php'>← Quay lại</a>

<script>
    function toggleSellers(id) {
        var el = document.getElementById('sellers-' + id);
        el.style.display = (el.style.display === "block") ? "none" : "block";
    }
</script>

<link rel="stylesheet" href="/assets/CSS/bookmark_star.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    window.userLoggedIn = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;
</script>
<script src="/assets/JavaScript/bookmark_star.js"></script>