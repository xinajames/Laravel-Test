#!/bin/bash

# Laravel post-deployment setup script
set -e

APP_DIR="/var/app/current"
LOG_FILE="/var/log/eb-hooks.log"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a $LOG_FILE
}

log "Starting Laravel post-deployment setup..."

# Ensure we're in the right directory
cd $APP_DIR

# Set proper permissions
log "Setting storage permissions..."
chown -R webapp:webapp storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Create storage directories if they don't exist
log "Creating storage directories..."
mkdir -p storage/app/private/jform-production-filesystem
mkdir -p storage/framework/{cache/data,sessions,views}
mkdir -p storage/logs

# Set permissions for new directories
chown -R webapp:webapp storage
chmod -R 775 storage

# Clear and cache configurations
log "Optimizing Laravel application..."
php artisan config:clear || true
php artisan view:clear || true
php artisan route:clear || true

# Skip cache:clear since cache table doesn't exist yet
# php artisan cache:clear || true

php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Create storage link if it doesn't exist
log "Creating storage link..."
php artisan storage:link || true

# Restart queue workers
log "Restarting queue workers..."
php artisan queue:restart || true

log "Laravel post-deployment setup completed successfully!"