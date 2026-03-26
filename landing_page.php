<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LaptopSS - So Sánh Giá Laptop Chính Hãng</title>
    <link rel="icon" type="image/png" href="/public/images/logo_icon.png">
        
    <link rel="shortcut icon" href="/public/images/logo_icon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        /* Hiệu ứng hover cho thẻ sản phẩm */
        .product-card:hover {
            transform: translateY(-5px);
        }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#2563EB', /* Màu xanh tiêu chuẩn công nghệ */
                        secondary: '#1E40AF',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 text-gray-800">

    <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center">
                    <a href="/public/Daskboard/Daskboard.php" class="text-2xl font-bold text-primary">Laptop<span class="text-gray-900">SS</span></a>
                    <div class="hidden md:flex ml-10 space-x-8">
                        <a href="/public/Daskboard/Daskboard.php" class="text-gray-700 hover:text-primary transition">Danh mục</a>
                        <a href="/public/Search/Search.php" class="text-gray-700 hover:text-primary transition">Tìm Sản Phẩm</a>
                        <a href="/public/Bookmark/Bookmark.php" class="text-gray-700 hover:text-primary transition">Sản Phẩm Yêu Thích</a>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="/public/user/login.php" class="text-gray-600 hover:text-primary">Đăng nhập</a>
                    <a href="/public/user/register.php" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-secondary transition">Đăng ký</a>
                </div>
            </div>
        </div>
    </nav>

    <section class="relative bg-white overflow-hidden">
        <div class="max-w-7xl mx-auto">
            <div class="relative z-10 pb-8 bg-white sm:pb-16 md:pb-20 lg:max-w-2xl lg:w-full lg:pb-28 xl:pb-32 pt-20 px-4 sm:px-6 lg:px-8">
                <main class="mt-10 mx-auto max-w-7xl sm:mt-12 md:mt-16 lg:mt-20 xl:mt-28 ">
                    <div class="sm:text-center lg:text-left">
                        <h1 class="text-4xl tracking-tight font-extrabold text-gray-900 sm:text-5xl md:text-6xl">
                            <span class="block xl:inline">Tìm Laptop Ưng Ý</span>
                            <span class="block text-primary">Với Giá Rẻ Nhất</span>
                        </h1>
                        <p class="mt-3 text-base text-gray-500 sm:mt-5 sm:text-lg sm:max-w-xl sm:mx-auto md:mt-5 md:text-xl lg:mx-0">
                            Hệ thống tự động so sánh giá từ các trang Web bán Laptop giúp bạn tiết kiệm hàng triệu đồng.
                        </p>
                        
                        <div class="mt-8 sm:max-w-lg sm:mx-auto sm:text-center lg:text-left lg:mx-0">
                            <form action="/controllers/Search/search_new.php" method="GET" class="mt-3 sm:flex shadow-md rounded-md">
                                <label for="search" class="sr-only">Tìm kiếm laptop</label>
                                <input type="text" name="keyword" id="search" class="block w-full rounded-l-md border border-gray-300 px-4 py-3 text-gray-900 placeholder-gray-500 focus:border-primary focus:ring-primary sm:text-sm" placeholder="Nhập tên máy, hãng (Dell, Asus...)...">
                                <button type="submit" class="mt-3 w-full inline-flex items-center justify-center rounded-r-md border border-transparent bg-primary px-6 py-3 text-base font-medium text-white shadow-sm hover:bg-secondary focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 sm:mt-0 sm:w-auto">
                                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                    Tìm Giá
                                </button>
                            </form>
                            <p class="mt-3 text-sm text-gray-400">Thử tìm kiếm: "Macbook Air M1", "Dell XPS 13", "Gaming Laptop"</p>
                        </div>
                        </div>
                </main>
            </div>
        </div>
        <div class="lg:absolute lg:inset-y-0 lg:right-0 lg:w-1/2 bg-gray-100 flex items-center justify-center">
            <img class="h-56 w-full object-cover sm:h-72 md:h-96 lg:w-full lg:h-full" src="https://images.unsplash.com/photo-1593642702821-c8da6771f0c6?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80" alt="Laptop trên bàn làm việc">
        </div>
    </section>

    <?php 
    require_once dirname(__FILE__) . '/public/Landing_page/bestcomputer_view.php';
    ?>

    <div class="py-12 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="lg:text-center">
                <h2 class="text-base text-primary font-semibold tracking-wide uppercase">Công cụ thông minh</h2>
                <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-gray-900 sm:text-4xl">
                    Tại sao nên dùng Web So Sánh?
                </p>
            </div>

            <div class="mt-10">
                <dl class="space-y-10 md:space-y-0 md:grid md:grid-cols-3 md:gap-x-8 md:gap-y-10">
                    <div class="relative">
                        <dt>
                            <div class="absolute flex items-center justify-center h-12 w-12 rounded-md bg-primary text-white">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" /></svg>
                            </div>
                            <p class="ml-16 text-lg leading-6 font-medium text-gray-900">Lịch sử giá</p>
                        </dt>
                        <dd class="mt-2 ml-16 text-base text-gray-500">
                            Theo dõi biến động giá trong vài tháng gần nhất để biết đâu là thời điểm vàng để mua.
                        </dd>
                    </div>

                    <div class="relative">
                        <dt>
                            <div class="absolute flex items-center justify-center h-12 w-12 rounded-md bg-primary text-white">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                            </div>
                            <p class="ml-16 text-lg leading-6 font-medium text-gray-900">Tìm nơi rẻ nhất</p>
                        </dt>
                        <dd class="mt-2 ml-16 text-base text-gray-500">
                            Thực hiện quét giá từ nhiều cửa hàng trên toàn quốc trong thời gian thực.
                        </dd>
                    </div>

                    <div class="relative">
                        <dt>
                            <div class="absolute flex items-center justify-center h-12 w-12 rounded-md bg-primary text-white">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" /></svg>
                            </div>
                            <p class="ml-16 text-lg leading-6 font-medium text-gray-900">So sánh cấu hình</p>
                        </dt>
                        <dd class="mt-2 ml-16 text-base text-gray-500">
                            So sánh giá tiền hoặc chi tiết CPU, RAM, SSD giữa các dòng máy để tìm ra hiệu năng tốt nhất.
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>



    <footer class="bg-gray-800 text-white py-10 mt-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-4 gap-8">
            <div class="col-span-1 md:col-span-2">
                <h3 class="text-2xl font-bold">LaptopCompare</h3>
                <p class="mt-4 text-gray-400">Công cụ so sánh giá laptop hàng đầu Việt Nam. Chúng tôi giúp bạn tìm được chiếc máy tính ưng ý với mức giá rẻ nhất thị trường.</p>
            </div>
            <div>
                <h4 class="font-bold uppercase tracking-wide">Liên kết</h4>
                <ul class="mt-4 space-y-2 text-gray-400">
                    <li><a href="#" class="hover:text-white">Về chúng tôi</a></li>
                    <li><a href="#" class="hover:text-white">Chính sách bảo mật</a></li>
                    <li><a href="#" class="hover:text-white">Điều khoản sử dụng</a></li>
                </ul>
            </div>
            <div>
                <h4 class="font-bold uppercase tracking-wide">Kết nối</h4>
                <div class="mt-4 flex space-x-4">
                    <a href="#" class="text-gray-400 hover:text-white">Facebook</a>
                    <a href="#" class="text-gray-400 hover:text-white">Youtube</a>
                </div>
            </div>
        </div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-8 pt-8 border-t border-gray-700 text-center text-gray-400">
            <p>&copy; 2024 LaptopCompare. All rights reserved.</p>
        </div>
    </footer>

</body>
</html>