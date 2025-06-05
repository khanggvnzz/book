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

# Đảm bảo file .htaccess được sao chép vào thư mục gốc của container
COPY .htaccess /var/www/html/.htaccess

# Cài đặt dependencies bằng Composer
WORKDIR /var/www/html
RUN composer clear-cache && composer install --no-dev --optimize-autoloader

# Cấp quyền cho mã nguồn
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Cấu hình Apache trỏ DocumentRoot về thư mục /var/www/html/view
ENV APACHE_DOCUMENT_ROOT /var/www/html/view

# Sửa cấu hình VirtualHost để trỏ đúng DocumentRoot và cho phép sử dụng .htaccess
RUN sed -i "s|DocumentRoot /var/www/html|DocumentRoot ${APACHE_DOCUMENT_ROOT}|g" /etc/apache2/sites-available/000-default.conf \
    && sed -i "s|<Directory /var/www/>|<Directory ${APACHE_DOCUMENT_ROOT}>|g" /etc/apache2/apache2.conf \
    && sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Bật hiển thị lỗi PHP bằng cách tạo file cấu hình trong /usr/local/etc/php/conf.d/
RUN echo "display_errors = On" > /usr/local/etc/php/conf.d/custom.ini \
    && echo "display_startup_errors = On" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "error_reporting = E_ALL" >> /usr/local/etc/php/conf.d/custom.ini

# Thêm thông tin ngày và giờ
# Today's date and time is 08:48 PM +07 on Thursday, June 05, 2025

# Mở cổng HTTP
EXPOSE 80

# Khởi động Apache
CMD ["apache2-foreground"]