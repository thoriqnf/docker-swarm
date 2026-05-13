#!/bin/sh
# =============================================================
# Docker Entrypoint — reads Docker Secrets into env vars
# =============================================================
set -e

# ---- Load Docker Secrets into environment variables ----
# Secrets are mounted at /run/secrets/<name>
# We export them as environment variables for Laravel to consume.

load_secret() {
    local secret_name="$1"
    local env_var="$2"
    local secret_file="/run/secrets/${secret_name}"

    if [ -f "${secret_file}" ]; then
        export "${env_var}"="$(cat "${secret_file}")"
        echo "[entrypoint] Loaded secret: ${secret_name} → ${env_var}"
    fi
}

load_secret "app_key"        "APP_KEY"
load_secret "db_password"    "DB_PASSWORD"
load_secret "db_database"    "DB_DATABASE"
load_secret "db_username"    "DB_USERNAME"
load_secret "redis_password" "REDIS_PASSWORD"

# ---- Run Laravel bootstrap tasks on app container only ----
if [ "$1" = "php-fpm" ]; then
    echo "[entrypoint] Waiting for ${DB_CONNECTION:-pgsql} to be ready at ${DB_HOST:-100.66.190.92}..."
    
    # Construct PDO DSN based on connection type
    if [ "${DB_CONNECTION}" = "pgsql" ]; then
        DSN="pgsql:host=${DB_HOST};port=${DB_PORT:-5432};dbname=${DB_DATABASE}"
    else
        DSN="mysql:host=${DB_HOST:-mysql};port=${DB_PORT:-3306};dbname=${DB_DATABASE}"
    fi

    until php -r "new PDO('${DSN}', '${DB_USERNAME}', '${DB_PASSWORD}');" 2>/dev/null; do
        sleep 2
        echo "[entrypoint] Waiting for database..."
    done

    echo "[entrypoint] Running migrations..."
    php artisan migrate --force --no-interaction

    echo "[entrypoint] Caching config & routes..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

    echo "[entrypoint] Laravel bootstrap complete."
fi

exec "$@"
