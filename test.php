<?php
session_start();
$active_page = 'Dashboard'; // Đổi active page để highlight đúng menu
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Phân tích thị trường Laptop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .sidebar-placeholder { width: 250px; background: #343a40; min-height: 100vh; color: white; } /* Giả lập sidebar */
        .main-content { flex: 1; padding: 25px; }
        
        /* Style cho các thẻ Card */
        .stat-card { border: none; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-3px); }
        
        /* Style cho bảng giá */
        .price-up { color: #dc3545; font-weight: 600; } /* Đỏ = Tăng giá (Xấu) */
        .price-down { color: #198754; font-weight: 600; } /* Xanh = Giảm giá (Tốt) */
        
        /* Style cho Top List */
        .top-item { display: flex; align-items: center; padding: 10px 0; border-bottom: 1px solid #eee; }
        .top-item:last-child { border-bottom: none; }
        .rank-badge { width: 25px; height: 25px; background: #e9ecef; border-radius: 50%; text-align: center; line-height: 25px; font-size: 0.8rem; font-weight: bold; margin-right: 10px; }
        .rank-1 { background-color: #ffd700; color: #000; } /* Vàng cho top 1 */
        .rank-2 { background-color: #c0c0c0; color: #000; } /* Bạc cho top 2 */
        .rank-3 { background-color: #cd7f32; color: #fff; } /* Đồng cho top 3 */
    </style>
</head>
<body>

<div class="d-flex">
    
    <div class="sidebar-placeholder d-none d-md-block p-3">
        <?php 
        // include './includes/sidebar.php'; 
        ?>
        <h4>Admin Panel</h4>
        <hr>
        Menu mẫu...
    </div>

    <div class="main-content">
        <h3 class="mb-4 fw-bold text-secondary"><i class="fa-solid fa-chart-line"></i> Tổng quan thị trường</h3>

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stat-card bg-primary text-white">
                    <div class="card-body">
                        <h6>Tổng Laptop theo dõi</h6>
                        <h3>1,240</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-success text-white">
                    <div class="card-body">
                        <h6>Giảm giá hôm nay</h6>
                        <h3>15 <small class="fs-6">sản phẩm</small></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-info text-white">
                    <div class="card-body">
                        <h6>Lượt tìm kiếm (24h)</h6>
                        <h3>352</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-warning text-dark">
                    <div class="card-body">
                        <h6>Lượt Bookmark (24h)</h6>
                        <h3>48</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8 mb-4">
                <div class="card stat-card h-100">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="m-0 fw-bold text-primary"><i class="fa-solid fa-money-bill-trend-up"></i> Biến động giá mới nhất</h5>
                        <span class="badge bg-light text-dark border">Cập nhật: 5 phút trước</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Sản phẩm</th>
                                        <th>Giá cũ</th>
                                        <th>Giá mới</th>
                                        <th>Thay đổi</th>
                                        <th>Thời gian</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="https://via.placeholder.com/40" class="rounded me-2">
                                                <div>
                                                    <span class="d-block fw-bold">Dell XPS 13 Plus</span>
                                                    <small class="text-muted">FPT Shop</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-decoration-line-through text-muted">45.000.000</td>
                                        <td class="fw-bold">42.500.000</td>
                                        <td><span class="badge bg-success bg-opacity-10 text-success">- 5.5%</span></td>
                                        <td class="text-muted small">10:05 AM</td>
                                    </tr>
                                    
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="https://via.placeholder.com/40" class="rounded me-2">
                                                <div>
                                                    <span class="d-block fw-bold">Asus TUF Gaming F15</span>
                                                    <small class="text-muted">GearVN</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-decoration-line-through text-muted">19.000.000</td>
                                        <td class="fw-bold">20.500.000</td>
                                        <td><span class="badge bg-danger bg-opacity-10 text-danger">+ 7.8%</span></td>
                                        <td class="text-muted small">09:45 AM</td>
                                    </tr>

                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="https://via.placeholder.com/40" class="rounded me-2">
                                                <div>
                                                    <span class="d-block fw-bold">MacBook Air M2</span>
                                                    <small class="text-muted">Tiki Trading</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-decoration-line-through text-muted">27.000.000</td>
                                        <td class="fw-bold">26.800.000</td>
                                        <td><span class="badge bg-success bg-opacity-10 text-success">- 200k</span></td>
                                        <td class="text-muted small">08:30 AM</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white text-center">
                        <a href="#" class="text-decoration-none small">Xem toàn bộ lịch sử giá <i class="fa-solid fa-arrow-right"></i></a>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                
                <div class="card stat-card mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="m-0 fw-bold"><i class="fa-solid fa-fire text-danger"></i> Được tìm kiếm nhiều nhất</h6>
                    </div>
                    <div class="card-body">
                        
                        <div class="top-item">
                            <div class="rank-badge rank-1">1</div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0 text-truncate" style="max-width: 180px;">MacBook Pro M3</h6>
                                <small class="text-muted">1,205 lượt tìm</small>
                            </div>
                            <i class="fa-solid fa-arrow-trend-up text-success"></i>
                        </div>

                        <div class="top-item">
                            <div class="rank-badge rank-2">2</div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0">Acer Nitro 5</h6>
                                <small class="text-muted">980 lượt tìm</small>
                            </div>
                            <i class="fa-solid fa-minus text-secondary"></i>
                        </div>

                         <div class="top-item">
                            <div class="rank-badge rank-3">3</div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0">Lenovo Legion 5</h6>
                                <small class="text-muted">850 lượt tìm</small>
                            </div>
                            <i class="fa-solid fa-arrow-trend-up text-success"></i>
                        </div>
                    </div>
                </div>

                <div class="card stat-card">
                    <div class="card-header bg-white py-3">
                        <h6 class="m-0 fw-bold"><i class="fa-solid fa-heart text-danger"></i> Được Bookmark nhiều nhất</h6>
                    </div>
                    <div class="card-body">
                        <div class="top-item">
                            <div class="rank-badge bg-light text-dark">1</div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0">Dell XPS 13</h6>
                                <div class="progress mt-1" style="height: 5px;">
                                    <div class="progress-bar bg-danger" style="width: 85%"></div>
                                </div>
                            </div>
                            <span class="badge bg-secondary ms-2">85</span>
                        </div>

                        <div class="top-item">
                            <div class="rank-badge bg-light text-dark">2</div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0">HP Envy 13</h6>
                                <div class="progress mt-1" style="height: 5px;">
                                    <div class="progress-bar bg-danger" style="width: 60%"></div>
                                </div>
                            </div>
                            <span class="badge bg-secondary ms-2">60</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>