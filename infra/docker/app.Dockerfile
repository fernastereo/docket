# syntax=docker/dockerfile:1.7
#
# Imagen de la aplicación (Laravel / PHP-FPM) para CuraduriAPP · repo "Docket".
# Multi-stage. Dos targets finales:
#   - dev  : con Composer, Node y Xdebug; el código se monta como volumen.
#   - prod : autocontenida (vendor + assets Vite horneados), sin herramientas de build.
#
# Contexto de build esperado: la RAÍZ del repo (usa rutas docket/ e infra/).

ARG PHP_VERSION=8.4

# ------------------------------------------------------------------ frontend
FROM node:22-alpine AS frontend
WORKDIR /app
COPY docket/package.json docket/package-lock.json* ./
RUN npm ci --no-audit --no-fund
COPY docket/ ./
RUN npm run build

# ------------------------------------------------------------------ vendor
FROM composer:2.9 AS vendor
WORKDIR /app
ARG COMPOSER_FLAGS="--no-dev --optimize-autoloader --no-interaction --no-progress"
COPY docket/composer.json docket/composer.lock* ./
RUN composer install --no-scripts --no-autoloader ${COMPOSER_FLAGS} || true
COPY docket/ ./
RUN composer install ${COMPOSER_FLAGS} \
 && composer dump-autoload --optimize --classmap-authoritative

# ------------------------------------------------------------------ runtime base
FROM php:${PHP_VERSION}-fpm-alpine AS runtime

RUN set -eux; \
    apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
        postgresql-dev icu-dev libzip-dev oniguruma-dev linux-headers; \
    apk add --no-cache \
        postgresql-libs icu-libs libzip fcgi git; \
    docker-php-ext-configure intl; \
    docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql pgsql intl zip bcmath pcntl opcache; \
    pecl install redis; \
    docker-php-ext-enable redis; \
    apk del .build-deps; \
    rm -rf /tmp/pear

COPY infra/docker/php/php.ini      /usr/local/etc/php/conf.d/zz-app.ini
COPY infra/docker/php/opcache.ini  /usr/local/etc/php/conf.d/zz-opcache.ini
COPY infra/docker/php/www.conf     /usr/local/etc/php-fpm.d/zz-www.conf
COPY infra/docker/entrypoint.sh    /usr/local/bin/entrypoint
COPY infra/docker/php/healthcheck.sh /usr/local/bin/php-fpm-healthcheck
RUN chmod +x /usr/local/bin/entrypoint /usr/local/bin/php-fpm-healthcheck

WORKDIR /var/www/html
ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]

# ------------------------------------------------------------------ dev
FROM runtime AS dev
RUN set -eux; \
    apk add --no-cache --virtual .build-deps $PHPIZE_DEPS linux-headers; \
    pecl install xdebug; \
    docker-php-ext-enable xdebug; \
    apk del .build-deps; \
    apk add --no-cache nodejs npm
COPY --from=composer:2.9 /usr/bin/composer /usr/bin/composer
# el código llega por bind-mount (ver docker-compose.dev.yml)
USER www-data

# ------------------------------------------------------------------ prod
FROM runtime AS prod
COPY --chown=www-data:www-data docket/ ./
COPY --from=vendor   --chown=www-data:www-data /app/vendor        ./vendor
COPY --from=frontend --chown=www-data:www-data /app/public/build  ./public/build
# permisos de escritura solo donde Laravel los necesita
RUN chown -R www-data:www-data storage bootstrap/cache
USER www-data
