FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

COPY app app
COPY bootstrap bootstrap
COPY config config
COPY database database
COPY resources resources
COPY routes routes
COPY artisan artisan
RUN composer dump-autoload --optimize

FROM node:22-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json vite.config.js ./
COPY resources resources
COPY public public
RUN npm ci --ignore-scripts
RUN npm run build

FROM php:8.4-apache AS app

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN apt-get update \
    && apt-get install -y --no-install-recommends ca-certificates libpq-dev libzip-dev unzip \
    && docker-php-ext-install pdo_mysql pdo_pgsql zip \
    && a2enmod rewrite headers \
    && sed -ri -e "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY --from=vendor /app ./
COPY . ./
COPY --from=assets /app/public/build ./public/build
COPY docker/render-entrypoint.sh /usr/local/bin/render-entrypoint

RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod +x /usr/local/bin/render-entrypoint

EXPOSE 10000

ENTRYPOINT ["render-entrypoint"]
CMD ["apache2-foreground"]
