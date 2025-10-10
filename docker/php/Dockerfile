FROM php:8.2-fpm

# //1.- Install system packages and PHP extensions required by Laravel.
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libpq-dev \
        libpng-dev \
        libonig-dev \
        libxml2-dev \
        libzip-dev \
        zip \
    && docker-php-ext-install pdo_pgsql pgsql mbstring exif pcntl bcmath gd \
    && docker-php-ext-install zip \
    && rm -rf /var/lib/apt/lists/*

# //2.- Copy the Composer binary from the official image to handle dependencies.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# //3.- Configure the working directory for the application code.
WORKDIR /var/www/html

# //4.- Ensure proper permissions when mounting volumes on Linux hosts.
RUN chown -R www-data:www-data /var/www/html

# //5.- Use the www-data user for better security at runtime.
USER www-data

# //6.- Boot the PHP-FPM process when the container starts.
CMD ["php-fpm"]
