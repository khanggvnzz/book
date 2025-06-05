# Sử dụng image PHP chính thức với Apache
FROM php:8.1-apache

# Cài đặt các tiện ích và extensions cần thiết
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql mbstring xml mysqli gd \
    && a2enmod rewrite

# Cài đặt Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Sao chép mã nguồn vào container
COPY . /var/www/html

# Cài đặt dependencies bằng Composer (nếu có file composer.json)
WORKDIR /var/www/html
RUN if [ -f composer.json ]; then composer clear-cache && composer install --no-dev --optimize-autoloader; fi

# Cấp quyền cho mã nguồn
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Bỏ phần thay đổi DocumentRoot vì sẽ routing bằng index.php
# Giữ nguyên DocumentRoot là /var/www/html

# Tạo file .htaccess để bật rewrite URL cho MVC routing
RUN echo '<IfModule mod_rewrite.c>\n\
    RewriteEngine On\n\
    RewriteCond %{REQUEST_FILENAME} !-f\n\
    RewriteCond %{REQUEST_FILENAME} !-d\n\
    RewriteRule ^(.*)$ index.php?page=$1 [QSA,L]\n\
</IfModule>' > /var/www/html/.htaccess

# Mở cổng HTTP
EXPOSE 80

# Khởi động Apache
CMD ["apache2-foreground"]
