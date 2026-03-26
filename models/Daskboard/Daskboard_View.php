<?php
// 1. Gọi file Model
require_once BASE_PATH . '/controllers/Daskboard/Daskboard_controler.php';
require_once BASE_PATH . '/controllers/Search/Tag_search.php';
require_once BASE_PATH . '/models/product/PriceHistoryModel.php';

// 2. Khởi tạo đối tượng từ Class
$dashboardModel = new Daskboard_controler();
$priceHistoryModel = new PriceHistoryModel();

// 3. Gọi các hàm từ đối tượng đó 
$totalLaptops = $dashboardModel->getTotalTrackedLaptops();
$saleToday    = $dashboardModel->getPriceDropsToday();
$search24h    = $dashboardModel->getSearchCountLast24h();
$bookmark24h  = $dashboardModel->getBookmarkCountLast24h();

$topSearches  = $dashboardModel->getTopSearchKeywords(3);
$topBookmarks = $dashboardModel->getTopBookmarkedProducts(3);

$recentChanges = $priceHistoryModel->getRecentPriceChanges(5);
$maxBookmarkCount = 0;
if (!empty($topBookmarks)) {
    $maxBookmarkCount = $topBookmarks[0]['total_bookmarks'];
}
?>



<link rel="stylesheet" href="/assets/CSS/dashboard.css"> 

    <div class="main-content">
        <h3 class="heading-main"><i class="fa-solid fa-chart-line"></i> Tổng quan thị trường</h3>

        <div class="row">
            <div class="col-3 info" >
                <div class="stat-card bg-primary-dark">
                    <div class="card-body">
                        <h6 class="name">Tổng Laptop theo dõi</h6>
                        <h3 style="margin: 0; padding-top: 5px;" class="number">
                            <?= number_format($totalLaptops) ?>
                        </h3>
                        <br>
                        
                    </div>
                    <div class="stat-icon">
                        <i class="fa-solid fa-laptop"></i>
                    </div>
                </div>
            </div>
            <div class="col-3 info">
                <div class="stat-card bg-success-dark">
                    <div class="card-body">
                        <h6 class="name">Giảm giá hôm nay</h6>
                        <h3 style="margin: 0; padding-top: 5px;" class="number"><?= number_format($saleToday) ?> </h3>
                        <br>
                    </div>
                    <div class="stat-icon">
                        <i class="fa-solid fa-arrow-trend-down"></i>
                    </div>
                </div>
            </div>
            <div class="col-3 info">
                <div class="stat-card bg-info-dark">
                    <div class="card-body">
                        <h6 class="name">Lượt tìm kiếm (24h)</h6>
                        <h3 style="margin: 0; padding-top: 5px;" class="number">
                            <?= number_format($search24h) ?>
                        </h3>
                        <br>
                    </div>
                    <div class="stat-icon">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </div>
                </div>
            </div>
            <div class="col-3 info">
                <div class="stat-card bg-warning-dark">
                    <div class="card-body">
                        <h6 class="name">Lượt Bookmark (24h)</h6>
                        <h3 style="margin: 0; padding-top: 5px;" class="number">
                            <?= number_format($bookmark24h) ?>
                        </h3>
                        <br>
                    </div>
                        <div class="stat-icon">
                            <i class="fa-solid fa-heart"></i>
                        </div>
                </div>
            </div>
        </div>

        <div class="row"  style="padding-right: 5px; height: 590px">
            <div class="stat-card" style="height: 590px; overflow-y: auto; background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); width: 66.1%; margin-right :5px;">
            <div class="card-header" style="padding: 15px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                <h5 style="margin: 0; font-weight: bold; color: #0d6efd;">
                    <i class="fa-solid fa-money-bill-trend-up"></i> Biến động giá mới nhất
                </h5>
                <span style="font-size: 0.8rem; border: 1px solid #ccc; padding: 4px 8px; border-radius: 5px; color: #666;">
                    Cập nhật: <?= date('H:i') ?>
                </span>
            </div>

                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead style="background-color: #f8f9fa; color: #333;">
                            <tr class="menu">
                                <th style="text-align:  center; padding: 12px 15px; font-size: 0.9rem;color: #666;">Sản phẩm</th>
                                <th style="text-align:  center; padding: 12px 15px; font-size: 0.9rem; color: #666;">Giá cũ</th>
                                <th style="text-align:  center; padding: 12px 15px; font-size: 0.9rem;color: #666;">Giá mới</th>
                                <th style="text-align: center; padding: 12px 15px; font-size: 0.9rem;color: #666;">Thay đổi</th>
                                <th style="text-align:  center; padding: 12px 15px; font-size: 0.9rem;color: #666;">Thời gian</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recentChanges)): ?>
                                <?php foreach ($recentChanges as $item): ?>
                                    <tr style="border-bottom: 1px solid #f1f1f1;">
                                        <td style="padding: 12px 15px;">
                                            <div style="display: flex; flex-direction: column;">
                                                <a href="<?= htmlspecialchars($item['url']) ?>" target="_blank" 
                                                style="font-weight: bold; color: #333; text-decoration: none; font-size: 0.95rem; margin-bottom: 4px;">
                                                    <?= htmlspecialchars(clean_product_name($item['name'])) ?>
                                                </a>
                                                <small style="color: #999; font-size: 0.8rem;">
                                                    <?= htmlspecialchars($item['source_site']) ?>
                                                </small>
                                            </div>
                                        </td>

                                        <td style="padding: 12px 15px; text-align: right; color: #999; text-decoration: line-through; font-size: 0.9rem;">
                                            <?= ($item['old_price'] != $item['new_price']) ? number_format($item['old_price']) : '' ?>
                                        </td>

                                        <td style="padding: 12px 15px; text-align: right; font-weight: bold; font-size: 1rem;">
                                            <?= number_format($item['new_price']) ?>
                                        </td>

                                        <td style="padding: 12px 15px; text-align: center;">
                                            <?php if ($item['status'] == 'decrease'): ?>
                                                <span style="background-color: #d1e7dd; color: #0f5132; padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; font-weight: bold;">
                                                    <?= $item['percent_change'] ?>%
                                                </span>
                                            <?php elseif ($item['status'] == 'increase'): ?>
                                                <span style="background-color: #f8d7da; color: #842029; padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; font-weight: bold;">
                                                    +<?= $item['percent_change'] ?>%
                                                </span>
                                            <?php else: ?>
                                                <span style="color: #999;">-</span>
                                            <?php endif; ?>
                                        </td>

                                        <td style="padding: 12px 15px; text-align: right; color: #666; font-size: 0.85rem;">
                                            <?= date('h:i A', strtotime($item['recorded_at'])) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" style="text-align:center; padding: 20px;">Chưa có dữ liệu biến động giá</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
        
    </div>

            <div class="col-4">
                
                <div class="stat-card" style="margin-bottom: 5px;">

                    <div class="card-header">
                        <h6 style="margin: 0; font-weight: bold; color: #666;"><i class="fa-solid fa-fire" style="color: #dc3545;"></i> Được tìm kiếm nhiều nhất</h6>
                    </div>
                    <div class="card-body" style="padding: 10px 15px;">
                        
                        <?php if (!empty($topSearches)): ?>
                                <?php foreach ($topSearches as $index => $item): ?>
                                    <?php 
                                        // 1. Xử lý màu sắc cho số thứ hạng (Rank 1, 2, 3...)
                                        $rank = $index + 1;
                                        
                                        $rankClass = '';
                                        if ($rank == 1) $rankClass = 'rank-1';       
                                        elseif ($rank == 2) $rankClass = 'rank-2';  
                                        elseif ($rank == 3) $rankClass = 'rank-3';   
                                        elseif ($rank == 4) $rankClass = 'rank-4';
                                        elseif ($rank == 5) $rankClass = 'rank-5';   
                                    ?>

                                    <div class="top-item">
                                        <div class="rank-badge <?= $rankClass ?>"><?= $rank ?></div>
                                        
                                        <div style="flex-grow: 1;">
                                            <h6 style="margin: 0;" class="text-truncate">
                                                <?= htmlspecialchars($item['query_text']) ?>
                                            </h6>
                                            <small class="text-muted">
                                                <?= number_format($item['search_count']) ?> lượt tìm
                                            </small>
                                        </div>
                                       <a href="/controllers/Search/search_new.php?products=<?= urlencode($item['query_text'])?>"
                                        class="fa-solid fa-magnifying-glass btn-search-icon" 
                                        title="Tìm kiếm ngay: <?= htmlspecialchars($item['query_text']) ?>">
                                        </a>
                                            
                                    </div>

                                <?php endforeach; ?>
                            <?php else: ?>
                                <p style="text-align:center; color: #999; margin-top: 20px;">Chưa có dữ liệu tìm kiếm</p>
                            <?php endif; ?>
                    </div>
                
                </div>
                <div class="mb-5 bg-white rounded-lg shadow-sm">
                    <div class="stat-card mb-4"> <div class="card-header">
                        <h6 style="margin: 0; font-weight: bold; color: #666;">
                            <i class="fa-solid fa-heart" style="color: #dc3545;"></i> Được Bookmark nhiều nhất
                        </h6>
                    </div>
                    
                    <div class="card-body pt-3">
                        <?php if (!empty($topBookmarks)): ?>
                            <?php foreach ($topBookmarks as $index => $product): ?>
                                <?php 
                                    $rank = $index + 1;
                                    // Tính % thanh bar
                                    $percent = ($maxBookmarkCount > 0) ? ($product['total_bookmarks'] / $maxBookmarkCount) * 100 : 0;
                                ?>

                                <div class="bookmark-item">
                                    
                                    <div class="item-top-row">
                                        <div class="rank-box">
                                            <?php if ($rank == 1): ?>
                                                <i class="fa-solid fa-crown rank-crown"></i>
                                            <?php else: ?>
                                                <span class="rank-number"><?= $rank ?></span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="product-name" title="<?= htmlspecialchars($product['name']) ?>">
                                            <?= htmlspecialchars(clean_product_name($product['name'])) ?>
                                        </div>
                                    </div>

                                    <div class="item-bottom-row">
                                        <div class="progress-track">
                                            <div class="progress-fill" role="progressbar" 
                                                style="width: <?= $percent ?>%" 
                                                aria-valuenow="<?= $product['total_bookmarks'] ?>" 
                                                aria-valuemin="0" 
                                                aria-valuemax="<?= $maxBookmarkCount ?>">
                                            </div>
                                        </div>

                                        <span class="bookmark-count">
                                            <?= $product['total_bookmarks'] ?>
                                        </span>

                                        <a href="<?= htmlspecialchars($product['url']) ?>" 
                                        target="_blank"
                                        class="fa-solid fa-eye view-btn"
                                        title="Xem chi tiết">
                                        </a>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-center text-muted py-3">Chưa có dữ liệu bookmark</p>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
             
                                    
         
    </div>

