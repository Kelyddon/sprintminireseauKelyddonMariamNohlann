FROM php:8.2-fpm

USER root

RUN apt-get update && apt-get install -y \
    git unzip \
    libicu-dev libzip-dev \
    libjpeg-dev libpng-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install intl pdo_mysql zip gd opcache \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
RUN chown -R www-data:www-data /var/www/html
USER www-data