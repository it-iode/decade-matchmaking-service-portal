#!/usr/bin/env bash

# Node.js/NPM deployment operations
# This script handles all frontend build-related deployment tasks

set -euo pipefail

NPM="/usr/bin/npm"
APP_DIR="$1"

# Setup logging
LOG_FILE="$APP_DIR/storage/logs/npm-deploy.log"
mkdir -p "$APP_DIR/storage/logs"
exec &>> "$LOG_FILE"

echo "[$(date '+%F %T')] Starting NPM operations..."

# Change to app directory
cd "$APP_DIR"

# Install npm dependencies
echo "Installing npm dependencies..."
if [[ -f package-lock.json ]]; then
  $NPM ci --no-audit --no-fund
else
  $NPM install --no-audit --no-fund
fi

# Build frontend assets
echo "Building frontend assets..."
$NPM run build

echo "[$(date '+%F %T')] NPM operations completed successfully"