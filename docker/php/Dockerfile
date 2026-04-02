FROM php:8.5-fpm-alpine AS base

RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    unzip \
    bash \
    git \
    && docker-php-ext-install \
        pdo_pgsql \
        zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

WORKDIR /var/www/html

# -------------------------------------------------------------------
FROM base AS dev

RUN apk add --no-cache $PHPIZE_DEPS linux-headers \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del $PHPIZE_DEPS linux-headers

COPY docker/php/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# -------------------------------------------------------------------
FROM base AS prod

COPY backend/ /var/www/html/

RUN composer install --no-dev --optimize-autoloader \
    && php bin/console cache:warmup

ENV APP_ENV=prod