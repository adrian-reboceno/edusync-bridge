ARG PHP_VERSION=8.4

# ──────────────────────────────────────────────────────────────
# STAGE BASE — entorno PHP compartido
# ──────────────────────────────────────────────────────────────
FROM php:${PHP_VERSION}-fpm-alpine AS base

RUN apk add --no-cache \
    bash \
    curl \
    git \
    unzip \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    oniguruma-dev \
    libxml2-dev \
    libzip-dev \
    icu-dev \
    postgresql-dev \
    linux-headers \
    $PHPIZE_DEPS

RUN docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        pgsql \
        mbstring \
        xml \
        zip \
        gd \
        intl \
        opcache \
        bcmath \
        pcntl

RUN pecl install redis \
    && docker-php-ext-enable redis

COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

RUN addgroup -g 1000 -S appgroup \
    && adduser -u 1000 -S appuser -G appgroup

WORKDIR /var/www

# ──────────────────────────────────────────────────────────────
# STAGE DEVELOPMENT
# El código fuente llega por volumen montado (no por COPY).
# composer install se ejecuta en make setup, no en el build.
# ──────────────────────────────────────────────────────────────
FROM base AS development

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

COPY docker/app/php.ini /usr/local/etc/php/conf.d/custom.ini

USER appuser

EXPOSE 9000

CMD ["php-fpm"]

# ──────────────────────────────────────────────────────────────
# STAGE PRODUCTION
# Aquí sí se copia el código y se instalan dependencias,
# porque en producción no hay volumen montado.
# ──────────────────────────────────────────────────────────────
FROM base AS production

COPY docker/app/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

COPY --chown=appuser:appgroup . .

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --classmap-authoritative

RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

USER appuser

EXPOSE 9000

CMD ["php-fpm"]