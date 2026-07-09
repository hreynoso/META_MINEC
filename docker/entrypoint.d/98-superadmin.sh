#!/bin/sh
# Sincroniza la cuenta local Super Admin (break-glass) desde las variables
# SUPERADMIN_EMAIL / SUPERADMIN_PASSWORD. Idempotente y sin efecto si faltan.
# Corre tras las migraciones de AUTORUN, antes del seed opcional (99).
set -e

if [ -f /var/www/html/artisan ]; then
    echo "[entrypoint] Sincronizando cuenta Super Admin (si hay credenciales)"
    php /var/www/html/artisan superadmin:sync || true
fi
