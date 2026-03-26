FROM php:8.2-apache

# Cài đặt thư viện hệ thống
RUN apt-get update && apt-get install -y \
    libcurl4-openssl-dev \
    pkg-config \
    libssl-dev \
    && rm -rf /var/lib/apt/lists/*

# Cài đặt driver MySQL và cURL
RUN docker-php-ext-install curl pdo pdo_mysql mysqli

# Bật Output Buffering để tránh lỗi "Headers already sent"
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini" \
    && sed -i 's/output_buffering = Off/output_buffering = 4096/g' "$PHP_INI_DIR/php.ini"

# Copy code và phân quyền
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html

# Thiết lập file chạy chính
RUN echo "DirectoryIndex landing_page.php index.php" >> /etc/apache2/apache2.conf