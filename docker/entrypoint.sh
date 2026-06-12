#!/bin/sh
set -e

if [ -z "${APP_KEY}" ]; then
    echo "WARNING: APP_KEY is not set. Generating application key..."
    php artisan key:generate
fi

echo "Running storage:link..."
php artisan storage:link 2>/dev/null || true

echo "Caching config..."
php artisan config:cache

echo "Caching routes..."
php artisan route:cache || echo "Route caching skipped (may not be needed)"

echo "Caching views..."
php artisan view:cache

echo "Running migrations..."
php artisan migrate --force --isolated

echo "Starting Apache..."
exec apache2-foreground
