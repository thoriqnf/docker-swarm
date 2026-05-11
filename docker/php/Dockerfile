# =============================================================
# Stage 1: Composer dependencies
# =============================================================
FROM composer:2.8 AS vendor

WORKDIR /app

COPY src/composer.json src/composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-plugins \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader \
    --ignore-platform-reqs

# Copy full src to run post-install scripts (breeze/horizon discovery)
COPY src .
RUN composer dump-autoload --optimize

# =============================================================
# Stage 2: Node.js — build frontend assets
# =============================================================
FROM node:20-alpine AS assets

WORKDIR /app

COPY src/package.json src/package-lock.json* src/vite.config.js ./
COPY src/resources ./resources

RUN npm ci --prefer-offline 2>/dev/null || npm install
RUN npm run build

# =============================================================
# Stage 3: Final PHP-FPM production image
# =============================================================
FROM php:8.4-fpm-alpine AS app

LABEL maintainer="docker-swarm-demo"
LABEL org.opencontainers.image.title="Laravel Todo App"

# ---- System dependencies ----
RUN apk add --no-cache \
    curl \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    shadow \
    mysql-client \
    icu-dev \
    oniguruma-dev

# ---- PHP extensions ----
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl \
    opcache

# ---- Redis PHP extension (PECL) ----
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# ---- PHP config ----
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

WORKDIR /var/www/html

# ---- Copy application ----
COPY src .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

# ---- Permissions ----
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# ---- Docker secrets helper script ----
COPY docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# ---- Health check ----
HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD nc -z localhost 9000 || exit 1

EXPOSE 9000

USER www-data

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]
