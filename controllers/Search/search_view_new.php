<link rel="stylesheet" href="/assets/CSS/search_result.css">
<link rel="stylesheet" href="/assets/CSS/search-view-new.css">
<?php
// Lấy các tham số filter từ URL để giữ lại trạng thái select box
$s_price     = $_GET['price_range'] ?? '';
$s_money     = $_GET['sort'] ?? '';
$s_num_shops = $_GET['num_shops'] ?? '';
?>
<link rel="icon" type="image/png" href="/public/images/logo_icon.png">
        
<link rel="shortcut icon" href="/public/images/logo_icon.png">
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
        <button type="submit" class="btn-filter">Lọc Ngay</button>
    </div>
</form>

<hr>

<?php if (!empty($error_message)): ?>
    <div class="alert-warning">
        <?= $error_message ?>
    </div>
<?php elseif (!empty($grouped_products)): ?>

    <p>Tìm thấy <strong><?= count($grouped_products) ?></strong> dòng máy phù hợp:</p>

    <?php foreach ($grouped_products as $key => $group): ?>
        <div class="product-group-card">
            
            <div class="group-header" onclick="toggleSellers('<?= $key ?>')">
                
                <div class="group-image">
                    <?php 
                        $img_src = !empty($group['image']) ? $group['image'] : 'https://via.placeholder.com/150x150.png?text=No+Image'; 
                    ?>
                    <img src="<?= htmlspecialchars($img_src) ?>" alt="<?= htmlspecialchars($group['display_name']) ?>" onerror="this.src='https://via.placeholder.com/150x150.png?text=Error'">
                </div>

                <div class="group-info">
                    <h3><?= htmlspecialchars($group['display_name']) ?></h3>
                    
                    <div class="specs-summary">
                        <?php if(!empty($group['specs']['cpu'])): ?>
                            <span class="specs-badge">CPU: <?= $group['specs']['cpu'] ?></span>
                        <?php endif; ?>
                        <?php if(!empty($group['specs']['ram'])): ?>
                            <span class="specs-badge">RAM: <?= $group['specs']['ram'] ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="shop-count">
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
                        // Sắp xếp giá thấp đến cao trong danh sách con
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
                                        CPU: <?= $seller['cpu'] ?> | RAM: <?= $seller['ram'] ?> | Ổ Cứng: <?= $seller['storage'] ?>
                                    </span>
                                </td>
                                <td style="color: #d70018; font-weight: bold;">
                                    <?= format_price($seller['price']) ?>
                                </td>
                                <td style="text-align: center;">
                                    <a href="<?= $seller['url'] ?>" target="_blank" class="btn-visit">Tới nơi bán</a>
                                    <?php 
    // Tạo chuỗi cấu hình tóm tắt để lưu vào DB
                                 $specs_str = "CPU: {$seller['cpu']} | RAM: {$seller['ram']} | SSD: {$seller['storage']}";
                                ?>
                                <span class='<?= $star_class ?>' 
                                    style="font-size: 22px; cursor: pointer; margin-left: 10px; vertical-align: middle;"
                                    data-url='<?= htmlspecialchars($seller['url']) ?>'
                                    data-name='<?= htmlspecialchars($seller['name']) ?>'
                                    data-site='<?= htmlspecialchars($seller['site']) ?>'
                                    data-price='<?= $seller['price'] ?>'
                                    
                                    data-old-price='<?= $seller['old_price'] ?? 0 ?>'
                                    
                                    data-specs-summary='<?= htmlspecialchars($specs_str) ?>'
                                    data-image='<?= htmlspecialchars($seller_img) ?>'
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

<?php
    // Logic: Nếu có session user -> Về trang Search nội bộ.
    // Nếu không -> Về trang chủ (Landing Page).
    $back_link = isset($_SESSION['user_id']) ? '../../public/Search/Search.php' : '/landing_page.php';
?>
<div style="margin-top: 30px;">
    <a href="<?= $back_link ?>" style="text-decoration: none; font-weight: bold; color: #555;">
        ← Quay lại trang tìm kiếm
    </a>
</div>

<script>
    function toggleSellers(id) {
        var el = document.getElementById('sellers-' + id);
        // Toggle hiển thị
        if (el.style.display === "block") {
            el.style.display = "none";
        } else {
            el.style.display = "block";
        }
    }
</script>

<link rel="stylesheet" href="/assets/CSS/bookmark_star.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    window.userLoggedIn = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;
</script>

<script src="/assets/JavaScript/bookmark_star.js"></script>

<script>
    $(document).ready(function() {
        // Sử dụng event delegation để bắt sự kiện click vào ngôi sao
        $(document).on('click', '.btn-bookmark-star', function(e) {
            // Nếu chưa đăng nhập
            if (!window.userLoggedIn) {
                // Ngăn chặn hành động Ajax mặc định
                e.preventDefault(); 
                e.stopImmediatePropagation(); 

                // Hỏi người dùng
                var confirmLogin = confirm("Chức năng 'Lưu yêu thích' chỉ dành cho thành viên.\nBạn có muốn đăng nhập ngay bây giờ không?");
                
                if (confirmLogin) {
                    // Chuyển hướng sang trang đăng nhập
                    window.location.href = '/public/user/login.php';
                }
                return false;
            }
        });
    });
</script>
<style 
