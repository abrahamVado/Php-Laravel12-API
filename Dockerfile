# syntax=docker/dockerfile:1.5

# //1.- Build the vendor directory in an isolated stage to keep the final image slim.
FROM composer:2 AS vendor

# //2.- Configure the workdir so Composer installs into the expected Laravel path.
WORKDIR /var/www/html

# //3.- Copy only the composer manifests required for dependency resolution.
COPY composer.json composer.lock ./

# //4.- Install PHP dependencies without dev packages for a production-friendly image.
RUN composer install \
    --no-dev \
    --no-progress \
    --prefer-dist \
    --no-interaction

# //5.- Build the application container that will run PHP-FPM.
FROM php:8.2-fpm

# //6.- Install system packages and PHP extensions required by the Laravel API.
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

# //7.- Copy the Composer binary so the container can run install/update commands if needed.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# //8.- Set the working directory where the Laravel app will live.
WORKDIR /var/www/html

# //9.- Copy the installed vendor directory from the vendor stage before the application code.
COPY --from=vendor /var/www/html/vendor ./vendor

# //10.- Copy the full application source into the container image.
COPY . .

# //11.- Ensure writable directories have the right ownership for the www-data user.
RUN chown -R www-data:www-data storage bootstrap/cache

# //12.- Switch to the less-privileged runtime user to improve container security.
USER www-data

# //13.- Expose port 9000 for PHP-FPM and start the default process.
EXPOSE 9000
CMD ["php-fpm"]
