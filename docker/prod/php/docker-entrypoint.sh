#!/bin/sh
set -e

echo "Initializing application..."

echo "Caching config..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Running migrations..."
php artisan migrate --force

# Запуск основного процесса (PHP-FPM)
exec docker-php-entrypoint "$@"
