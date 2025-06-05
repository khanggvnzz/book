# Sử dụng image PHP chính thức với Apache
FROM php:8.2-apache

# Cài đặt các tiện ích cần thiết và extensions
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libxml2-dev \
    libonig-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring xml mysqli \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Sao chép toàn bộ mã nguồn vào container
COPY . /var/www/html

# Cấp quyền cho thư mục web
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Cài đặt các thư viện PHP qua Composer
WORKDIR /var/www/html
RUN composer install --no-dev --optimize-autoloader

# Cấu hình Apache cho phép sử dụng .htaccess
RUN echo "<Directory /var/www/html>\n\
    AllowOverride All\n\
</Directory>" > /etc/apache2/sites-available/000-default.conf

# Kích hoạt rewrite module và reload Apache config
RUN a2enmod rewrite

# Mở cổng 80
EXPOSE 80

# Khởi động Apache
CMD ["apache2-foreground"]