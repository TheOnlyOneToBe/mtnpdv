# docker/php/Dockerfile
FROM php:8.3-fpm

# Installation des dépendances système
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpq-dev \
        libicu-dev \
        libzip-dev \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        git \
        curl \
        unzip \
        default-mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_mysql intl zip gd opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Installation de Symfony CLI (optionnel)
RUN curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony5/bin/symfony /usr/local/bin/symfony

WORKDIR /var/www/symfony

# Copier les fichiers composer et installer les dépendances
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --no-progress \
    && composer clear-cache

# Copier le code source
COPY . .

# Optimiser l'autoloader
RUN composer dump-autoload --optimize --no-dev

# Créer les dossiers nécessaires
RUN mkdir -p var/cache var/log \
    && chown -R www-data:www-data var

USER www-data

CMD ["php-fpm"]