#!/usr/bin/env bash

#be very strict
#next line is for production use only
set -euo pipefail
#next line is for debugging only, comment in production
#set -euxo pipefail

# Parse command line arguments
ENVIRONMENT="dev"  # Default environment

# Function to show usage
show_usage() {
    echo "Usage: $0 [--env <dev|prod>]"
    echo "  --env: Environment to deploy (dev or prod), default: dev"
    exit 1
}

# Parse arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --env)
            ENVIRONMENT="$2"
            if [[ "$ENVIRONMENT" != "dev" && "$ENVIRONMENT" != "prod" ]]; then
                echo "Error: Environment must be 'dev' or 'prod'"
                show_usage
            fi
            shift 2
            ;;
        --help|-h)
            show_usage
            ;;
        *)
            echo "Error: Unknown argument '$1'"
            show_usage
            ;;
    esac
done

echo "[$(date '+%F %T')] Deploy start"

# Configuration based on environment
if [[ "$ENVIRONMENT" == "prod" ]]; then
    APP_DIR="/var/www/html/decade-matchmaking-service-portal"
    BRANCH="main"
else
    APP_DIR="/var/www/html/decade-matchmaking-service-portal_dev"
    BRANCH="develop"
fi

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/scripts"

echo "Environment: $ENVIRONMENT"

printf 'deploying %s in %s \n' "$BRANCH" "$APP_DIR"

# sanity check
[[ -d "$APP_DIR/.git" ]] || { echo "Not a git repo: $APP_DIR"; exit 1; }

# Make scripts executable
chmod +x "$SCRIPT_DIR"/*.sh

# Execute git operations
echo "[$(date '+%F %T')] Running git operations..."
set +e
"$SCRIPT_DIR/git-deploy.sh" "$APP_DIR" "$BRANCH"
GIT_EXIT_CODE=$?
set -e

if [[ $GIT_EXIT_CODE -eq 200 ]]; then
  echo "[$(date '+%F %T')] No changes to deploy — skipping Laravel/NPM steps."
  echo "[$(date '+%F %T')] Deploy finished OK (no-op)"
  exit 0
elif [[ $GIT_EXIT_CODE -ne 0 ]]; then
  echo "[$(date '+%F %T')] Git operations FAILED with exit code $GIT_EXIT_CODE"
  exit $GIT_EXIT_CODE
fi
echo "[$(date '+%F %T')] done git operations..."
# Execute Laravel and NPM operations in parallel
echo "[$(date '+%F %T')] Running Laravel and NPM operations in parallel..."

# Start Laravel operations in background
"$SCRIPT_DIR/laravel-deploy.sh" "$APP_DIR" "$ENVIRONMENT" &
LARAVEL_PID=$!

# Start NPM operations in background
"$SCRIPT_DIR/npm-deploy.sh" "$APP_DIR" &
NPM_PID=$!

# Wait for Laravel operations
echo "Waiting for Laravel operations to complete..."
wait $LARAVEL_PID
LARAVEL_EXIT_CODE=$?
if [[ $LARAVEL_EXIT_CODE -ne 0 ]]; then
  echo "Laravel operations failed with exit code $LARAVEL_EXIT_CODE"
  exit $LARAVEL_EXIT_CODE
fi
echo "✓ Laravel operations completed"

# Wait for NPM operations
echo "Waiting for NPM operations to complete..."
wait $NPM_PID
NPM_EXIT_CODE=$?
if [[ $NPM_EXIT_CODE -ne 0 ]]; then
  echo "NPM operations failed with exit code $NPM_EXIT_CODE"
  exit $NPM_EXIT_CODE
fi
echo "✓ NPM operations completed"

echo "[$(date '+%F %T')] Deploy finished OK"

