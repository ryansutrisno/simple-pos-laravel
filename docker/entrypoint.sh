#!/bin/sh
set -e

# Create storage symbolic link
echo "Linking storage..."
php artisan storage:link || true

# Run database migrations
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    echo "Running database migrations..."
    php artisan migrate --force
fi

# Optimize Laravel cache for production
echo "Caching Laravel configuration, routes, views, and events..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Execute main command (Supervisord)
exec "$@"
