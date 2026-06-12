#!/bin/sh
set -e

if [ -z "${APP_KEY}" ]; then
    echo "WARNING: APP_KEY is not set. Generating application key..."
    php artisan key:generate --force
fi

echo "Running storage:link..."
php artisan storage:link --force 2>/dev/null || true

echo "Caching config..."
php artisan config:cache --force

echo "Caching routes..."
php artisan route:cache --force

echo "Caching views..."
php artisan view:cache --force

echo "Running migrations..."
php artisan migrate --force --isolated

echo "Starting Apache..."
exec apache2-foreground
