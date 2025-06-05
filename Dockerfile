FROM php:8.1-apache

# Install required utilities and PHP extensions
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

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy source code into the container
COPY . /var/www/html

# Install dependencies with Composer
WORKDIR /var/www/html
RUN composer clear-cache && composer install --no-dev --optimize-autoloader

# Set permissions for the source code
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Configure Apache DocumentRoot
ENV APACHE_DOCUMENT_ROOT /var/www/html/view

# Update Apache configuration to use the new DocumentRoot
RUN sed -i "s|DocumentRoot /var/www/html|DocumentRoot ${APACHE_DOCUMENT_ROOT}|g" /etc/apache2/sites-available/000-default.conf \
    && sed -i "s|<Directory /var/www/>|<Directory ${APACHE_DOCUMENT_ROOT}/>|g" /etc/apache2/apache2.conf

# Expose HTTP port
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]