# Sử dụng phiên bản PHP 8.2 có sẵn Apache (Server web)
FROM php:8.2-apache

# Cài đặt thư viện cURL để PHP có thể "đi cào" web
RUN apt-get update && apt-get install -y libcurl4-openssl-dev pkg-config libssl-dev

# Kích hoạt extension cURL trong PHP
RUN docker-php-ext-install curl

# Copy toàn bộ code từ thư mục src vào thư mục mặc định của Apache
COPY ./src /var/www/html/

# Phân quyền để server có thể đọc/ghi file (nếu bạn lưu kết quả ra file)
RUN chown -R www-data:www-data /var/www/html