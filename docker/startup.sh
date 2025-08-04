#!/bin/bash

# Startup script for Laravel in Cloud Run

# Exit on error
set -e

# Set permissions
echo "Setting permissions..."
chown -R www-data:www-data /var/www/html/storage
chmod -R 755 /var/www/html/storage

# Clear caches
echo "Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Create storage link if needed
if [ ! -L /var/www/html/public/storage ]; then
    echo "Creating storage link..."
    php artisan storage:link
fi

# Start supervisor
echo "Starting services..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf