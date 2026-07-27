# ==========================================
# STAGE 1: Build Frontend (Node.js)
# ==========================================
FROM node:22-alpine AS frontend-builder
WORKDIR /app
COPY package.json ./
RUN npm install
COPY . .
RUN npm run build

# ==========================================
# STAGE 2: Build Dependencies (Composer)
# ==========================================
FROM composer:2 AS vendor-builder

# Install dependensi isstem dan ekstensi intl  untuk Composer/Filament
RUN apk add --no-cache \
    icu-dev \
    && docker-php-ext-install intl

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --optimize-autoloader \
    --no-scripts

# ==========================================
# STAGE 3: Production PHP-FPM Image
# ==========================================
FROM php:8.4-fpm-bookworm AS production

# Install system dependencies & PHP extensions for Laravel + Filament (MySQL & Redis)
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    unzip \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Copy source code yang sudah di-clone manual di host
COPY . .

# Copy hasil build vendor dari stage composer
COPY --from=vendor-builder /app/vendor ./vendor

# Copy hasil build asset frontend dari stage node
COPY --from=frontend-builder /app/public/build ./public/build

# Atur permission storage dan bootstrap cache agar bisa ditulis web server
RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]
