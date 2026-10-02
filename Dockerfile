# syntax=docker/dockerfile:1

# ---------- Front-end build ----------
FROM node:24-alpine AS assets
WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY tailwind.config.js postcss.config.js vite.config.* ./
COPY resources ./resources
RUN npm run build


# ---------- PHP dependencies ----------
FROM composer:2 AS vendor
WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader


# ---------- Application ----------
FROM php:8.3-fpm-alpine AS app
WORKDIR /app

# gd/bcmath/intl are required by the image pipeline, pdo_pgsql by the production DB.
RUN apk add --no-cache \
        bash curl git libzip-dev icu-dev oniguruma-dev \
        libpng-dev libjpeg-turbo-dev libwebp-dev freetype-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        pdo pdo_mysql pdo_pgsql bcmath intl opcache pcntl zip exif gd \
    && apk del --no-network .build-deps icu-dev libzip-dev oniguruma-dev

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

RUN mkdir -p storage/framework/{cache/data,sessions,views} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

USER www-data
EXPOSE 8000

ENTRYPOINT ["entrypoint"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]