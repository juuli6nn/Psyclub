FROM node:20-bookworm-slim AS frontend

WORKDIR /app

COPY package.json package-lock.json vite.config.js ./
COPY resources ./resources
COPY public ./public

RUN npm ci && npm run build

FROM php:8.2-apache

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN apt-get update \
    && apt-get install -y --no-install-recommends libicu-dev libzip-dev unzip \
    && docker-php-ext-install bcmath intl mbstring pdo_mysql opcache zip \
    && a2enmod rewrite headers \
    && sed -ri "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && sed -ri 's/:80>/:10000>/g' /etc/apache2/sites-available/*.conf \
    && sed -ri 's/^Listen 80$/Listen 10000/' /etc/apache2/ports.conf \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./

RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

COPY . .
COPY --from=frontend /app/public/build ./public/build

RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache

EXPOSE 10000

CMD ["apache2-foreground"]
