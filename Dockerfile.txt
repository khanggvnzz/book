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

# Cài đặt dependencies bằng Composer
WORKDIR /var/www/html
RUN composer clear-cache && composer install --no-dev --optimize-autoloader

# Cấp quyền cho mã nguồn
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Cấu hình Apache trỏ DocumentRoot về thư mục /var/www/html/view
ENV APACHE_DOCUMENT_ROOT /var/www/html/view

# Sửa cấu hình VirtualHost để trỏ đúng DocumentRoot
RUN sed -i "s|DocumentRoot /var/www/html|DocumentRoot ${APACHE_DOCUMENT_ROOT}|g" /etc/apache2/sites-available/000-default.conf \
    && sed -i "s|<Directory /var/www/>|<Directory ${APACHE_DOCUMENT_ROOT}>|g" /etc/apache2/apache2.conf

# Mở cổng HTTP
EXPOSE 80

# Khởi động Apache
CMD ["apache2-foreground"]
