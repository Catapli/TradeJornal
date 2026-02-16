FROM php:8.3-fpm-alpine AS base

# Instalar dependencias del sistema + POSTGRESQL-DEV (SOLO ESTA LÍNEA CAMBIA)
RUN apk add --no-cache \
    nginx supervisor curl git \
    postgresql-dev \
    libpng-dev libjpeg-turbo-dev libwebp-dev \
    libzip-dev oniguruma-dev libxml2-dev \
    nodejs npm yarn

# Extensiones PHP para Laravel
RUN docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install \
        pdo pdo_pgsql mbstring exif pcntl bcmath gd \
        zip xml intl opcache

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copiar composer primero (cache)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --optimize-autoloader --no-interaction

# Copiar código
COPY . .

# Instalar y build frontend
RUN npm ci \
    && npm run build \
    && npm ci --production \
    && rm -rf node_modules

# Permisos
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 storage bootstrap/cache

# Nginx config
COPY nginx.conf /etc/nginx/nginx.conf

EXPOSE 8080

# ÚLTIMA LÍNEA del Dockerfile:
CMD php artisan migrate --force && php-fpm -D && nginx -g 'daemon off;'


