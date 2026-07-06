#!/bin/sh
# Siembra la base de datos SOLO si AUTORUN_LARAVEL_SEED=true.
# Úsalo en el primer despliegue para poblar catálogos, proyectos y usuario demo;
# luego ponlo en false para no sobrescribir datos editados desde la UI.
set -e

if [ "${AUTORUN_LARAVEL_SEED:-false}" = "true" ]; then
    if [ -f /var/www/html/artisan ]; then
        echo "[entrypoint] AUTORUN_LARAVEL_SEED=true → ejecutando db:seed --force"
        php /var/www/html/artisan db:seed --force
    fi
fi
