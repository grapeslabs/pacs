#!/bin/sh
set -e

echo "Initializing application..."

APP_KEY_FILE=/var/www/html/storage/app/.app_key

if [ -z "$APP_KEY" ]; then
    if [ ! -f "$APP_KEY_FILE" ]; then
        echo "APP_KEY is not set. Generating new key..."
        php artisan key:generate --show --no-ansi > "$APP_KEY_FILE"
    fi
    APP_KEY=$(cat "$APP_KEY_FILE")
    export APP_KEY
    echo "APP_KEY loaded from $APP_KEY_FILE"
fi

echo "Caching config..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Running migrations..."
php artisan migrate --force

# Запуск основного процесса (PHP-FPM)
exec docker-php-entrypoint "$@"
