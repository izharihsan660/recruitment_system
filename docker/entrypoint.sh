#!/bin/sh
set -e

mkdir -p /app/storage/app/public /app/storage/framework/cache /app/storage/framework/sessions /app/storage/framework/views /app/storage/logs /app/bootstrap/cache

php artisan storage:link --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
