<?php 
    define('BASE_PATH2', dirname(dirname(dirname(__FILE__)))); 
    require_once BASE_PATH2 . '/controllers/landing_page/bestlaptop.php';

    // LỌC SẢN PHẨM: Chỉ lấy sản phẩm đầy đủ thông tin
    $display_products = array_filter($display_products, function($item) {
        
        // 1. Bỏ qua nguồn 'example'
        if (isset($item['source_site']) && $item['source_site'] === 'example') {
            return false;
        }

        // 2. BẮT BUỘC CÓ ẢNH (Nếu không có link ảnh -> Ẩn luôn)
        if (empty($item['image_url'])) {
            return false;
        }

        // 3. Bắt buộc có Tên sản phẩm
        if (empty($item['name'])) {
            return false;
        }

        // 4. Bắt buộc có Giá và Giá phải > 0
        if (empty($item['price']) || $item['price'] <= 0) {
            return false;
        }

        // Nếu thỏa mãn tất cả điều kiện trên thì giữ lại
        return true;
    });
    // ----------------------------------
?>
<section class="bg-gray-100 py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-end mb-8">
            <div>
                <h2 class="text-3xl font-extrabold text-gray-900">Sản Phẩm Nổi Bật</h2>
                <p class="mt-2 text-gray-600">Những mẫu laptop mới cập nhật từ các nguồn.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            
            <?php if (count($display_products) > 0): ?>
            <?php foreach ($display_products as $item): ?>
                <?php 
                    // Xử lý dữ liệu
                    $price_formatted = number_format($item['price'], 0, ',', '.') . '₫';
                    
                    // Tính % giảm giá
                    $discount_tag = '';
                    if (!empty($item['old_price']) && $item['old_price'] > $item['price']) {
                        $percent = round((($item['old_price'] - $item['price']) / $item['old_price']) * 100);
                        $discount_tag = '<span class="absolute top-2 right-2 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded">-' . $percent . '%</span>';
                    }

                    // Vì đã lọc ở trên, ta không cần dùng placeholder nữa, lấy thẳng URL
                    $image = $item['image_url']; 
                    
                    $brand = !empty($item['brand']) ? $item['brand'] : 'Laptop';
                    
                    $specs = [];
                    if(!empty($item['cpu'])) $specs[] = $item['cpu'];
                    if(!empty($item['ram'])) $specs[] = $item['ram'];
                    if(!empty($item['storage'])) $specs[] = $item['storage'];
                    $specs_str = implode(' • ', array_slice($specs, 0, 3)); 

                    $product_link = $item['url'];
                ?>

                <a href="<?php echo htmlspecialchars($product_link); ?>" target="_blank" class="product-card block bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition-all duration-300 cursor-pointer h-full flex flex-col">
                    
                    <div class="h-48 bg-gray-200 overflow-hidden relative flex-shrink-0">
                        <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="w-full h-full object-contain p-2 bg-white">
                        
                        <?php echo $discount_tag; ?>
                        
                        <span class="absolute top-2 left-2 bg-blue-600 text-white text-[10px] font-bold px-2 py-1 rounded opacity-90 uppercase">
                            <?php echo htmlspecialchars($item['source_site']); ?>
                        </span>
                    </div>
                    
                    <div class="p-5 flex flex-col flex-grow">
                        <div class="text-xs text-gray-500 uppercase tracking-wide font-semibold">
                            <?php echo htmlspecialchars($brand); ?>
                        </div>
                        
                        <h3 class="mt-1 text-lg font-bold text-gray-900 line-clamp-2" title="<?php echo htmlspecialchars($item['name']); ?>">
                            <?php echo htmlspecialchars($item['name']); ?>
                        </h3>
                        
                        <p class="mt-2 text-sm text-gray-600 line-clamp-1">
                            <?php echo htmlspecialchars($specs_str); ?>
                        </p>
                        
                        <div class="mt-auto pt-4 flex items-center justify-between">
                            <div>
                                <span class="text-lg font-bold text-red-600">
                                    <?php echo $price_formatted; ?>
                                </span>
                            </div>
                            <div class="p-2 bg-gray-100 rounded-full hover:bg-primary hover:text-white transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                            </div>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
        <?php else: ?>
            <p class="col-span-4 text-center text-gray-500">Chưa có dữ liệu sản phẩm nào.</p>
        <?php endif; ?>

        </div>
        
    </div>
</section>