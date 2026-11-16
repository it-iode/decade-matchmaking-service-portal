#!/usr/bin/env bash

# Laravel deployment operations
# This script handles all Laravel-related deployment tasks

set -euo pipefail

PHP="/usr/bin/php"
COMPOSER="/usr/bin/composer"
APP_DIR="$1"
ENVIRONMENT="${2:-dev}"  # Default to 'dev' if not provided
# Setup logging
LOG_FILE="$APP_DIR/storage/logs/laravel-deploy.log"
mkdir -p "$APP_DIR/storage/logs"
exec &>> "$LOG_FILE"

echo "[$(date '+%F %T')] Starting Laravel operations..."

# Change to app directory
cd "$APP_DIR"

echo "Environment: $ENVIRONMENT"
# .env is rebuilt by scripts/env-deploy.sh (called from deploy.sh) before this script runs.

# Install composer dependencies
echo "Installing composer dependencies..."
$COMPOSER install --prefer-dist --no-interaction --optimize-autoloader
$COMPOSER  dump-autoload
echo "Clearing caches..."
$PHP artisan config:clear
$PHP artisan event:clear
$PHP artisan route:clear
$PHP artisan optimize:clear
# Check if migrations are needed
echo "Applying database migrations if needed..."
$PHP artisan migrate -n --force
# Laravel optimization commands
echo "Running Laravel optimizations..."
# Rebuild config and route caches for production performance
#$PHP artisan config:cache
#$PHP artisan route:cache
# Generate Ziggy routes
$PHP artisan ziggy:generate
$PHP artisan storage:link
# Restart queue workers so they pick up the newly deployed code
$PHP artisan queue:restart

echo "[$(date '+%F %T')] Laravel operations completed successfully"