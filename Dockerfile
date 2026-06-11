FROM php:8.3-apache

# 1. Install dependensi sistem dan PHP extension yang dibutuhkan Laravel
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql gd bcmath

# 2. Aktifkan Apache mod_rewrite untuk routing Laravel (.htaccess)
RUN a2enmod rewrite

# 3. Ubah DocumentRoot Apache agar mengarah ke folder /public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 4. Tentukan working directory
WORKDIR /var/www/html

# 5. Salin semua file project ke dalam container
COPY . .

# 6. Salin Composer terbaru dan jalankan install dependencies
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader

# 7. Berikan hak akses (permission) folder storage & cache ke Apache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# 8. Expose port 80 (Render akan otomatis mendeteksi port ini)
EXPOSE 80
