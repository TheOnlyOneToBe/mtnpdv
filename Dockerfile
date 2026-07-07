# syntax=docker/dockerfile:1

##############################################
# Stage 1 — build des assets front (Vite)
##############################################
FROM node:20-alpine AS frontend_builder

WORKDIR /app

COPY package.json package-lock.json* ./
RUN npm ci

COPY vite.config.js ./
COPY assets ./assets
COPY scripts ./scripts

RUN npm run build && npm run copy-vendor

##############################################
# Stage 2 — image PHP-FPM applicative
##############################################
FROM php:8.3-fpm AS app

# Dépendances système + extensions PHP requises par l'application
# (pdo_mysql: DB, intl/zip/gd: dompdf & uploads, opcache: perf)
RUN apt-get update && apt-get install -y --no-install-recommends \
        libicu-dev \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        unzip \
        git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql intl zip gd opcache \
    && apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Installation des dépendances PHP (couche cache Docker distincte du code source)
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --no-progress \
    && composer clear-cache

# Code applicatif
COPY . .

# Assets front construits à l'étape 1
COPY --from=frontend_builder /app/public/build ./public/build
COPY --from=frontend_builder /app/public/vendor ./public/vendor

RUN composer dump-autoload --optimize --no-dev \
    && mkdir -p var/cache var/log public/uploads/profils public/uploads/preuves public/uploads/pos \
    && chown -R www-data:www-data var public/uploads

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

USER www-data

EXPOSE 9000

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
