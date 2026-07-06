# syntax=docker/dockerfile:1

# 1) Dependencias PHP
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock* ./
RUN composer install \
    --no-dev --optimize-autoloader --no-interaction \
    --no-scripts --prefer-dist --ignore-platform-reqs || true
COPY . .
RUN composer dump-autoload --no-dev --optimize

# 2) Build de assets
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json* ./
# laravel-vite-plugin@3 pide Vite 8 y @vitejs/plugin-vue@5 pide Vite 5/6:
# conviven en runtime pero los peer deps estrictos lo rechazan.
RUN npm install --legacy-peer-deps
COPY . .
# Copiamos vendor para que Vite resuelva imports a paquetes Composer (Ziggy)
COPY --from=vendor /app/vendor ./vendor
RUN npm run build

# 3) Runtime
FROM serversideup/php:8.4-fpm-nginx AS runtime

# gd para DomPDF, mariadb-client para backups
USER root
RUN install-php-extensions gd \
    && apk add --no-cache mariadb-client
USER www-data

ENV AUTORUN_ENABLED=true \
    AUTORUN_LARAVEL_MIGRATION=true \
    AUTORUN_LARAVEL_SCHEDULE_RUN=true \
    AUTORUN_LARAVEL_QUEUE_WORKER=false \
    AUTORUN_LARAVEL_STORAGE_LINK=true \
    PHP_OPCACHE_ENABLE=1 \
    SSL_MODE=off

WORKDIR /var/www/html

COPY --chown=www-data:www-data . .
COPY --from=vendor --chown=www-data:www-data /app/vendor ./vendor
COPY --from=assets --chown=www-data:www-data /app/public/build ./public/build

# Horizon como servicio s6-overlay longrun (reemplaza queue:work nativo)
COPY --chown=root:root docker/s6-overlay/s6-rc.d /etc/s6-overlay/s6-rc.d
# La subida web de GitHub no conserva el bit de ejecucion: lo forzamos aqui.
USER root
RUN chmod +x /etc/s6-overlay/s6-rc.d/horizon/run
USER www-data
