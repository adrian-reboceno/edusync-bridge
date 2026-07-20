ARG PHP_VERSION=8.4

# ──────────────────────────────────────────────────────────────
# STAGE BASE — PHP CLI mínimo para Horizon
# Sin FPM, sin Nginx, sin Xdebug.
# ──────────────────────────────────────────────────────────────
FROM php:${PHP_VERSION}-cli-alpine AS base

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
        pcntl        # imprescindible — Horizon usa señales POSIX (SIGTERM, SIGUSR2)

RUN pecl install redis \
    && docker-php-ext-enable redis

COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

RUN addgroup -g 1000 -S appgroup \
    && adduser -u 1000 -S appuser -G appgroup

WORKDIR /var/www

# ──────────────────────────────────────────────────────────────
# STAGE DEVELOPMENT
# El código llega por volumen montado (mismo volumen que 'app').
# composer install lo corre make setup en el contenedor 'app'.
# Horizon lee vendor/ del volumen compartido — no necesita
# instalar dependencias por su cuenta.
# ──────────────────────────────────────────────────────────────
FROM base AS development

COPY docker/horizon/php.ini /usr/local/etc/php/conf.d/horizon.ini

USER appuser

CMD ["php", "artisan", "horizon"]

# ──────────────────────────────────────────────────────────────
# STAGE PRODUCTION
# ──────────────────────────────────────────────────────────────
FROM base AS production

COPY docker/horizon/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/horizon/php.ini     /usr/local/etc/php/conf.d/horizon.ini

COPY --chown=appuser:appgroup . .

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --classmap-authoritative

RUN php artisan config:cache

USER appuser

CMD ["php", "artisan", "horizon"]