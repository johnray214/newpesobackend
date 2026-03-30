FROM php:8.2-fpm

WORKDIR /var/www/html

# Install dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libzip-dev \
        zip \
        unzip \
        curl \
        git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Copy application code
COPY . .

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Increase Composer timeout and prefer dist to avoid git issues
RUN composer config --global process-timeout 2000
RUN composer install --no-dev --optimize-autoloader --prefer-dist

# Expose port and run the application
EXPOSE 8080
CMD php artisan serve --host=0.0.0.0 --port=${PORT:-8080}