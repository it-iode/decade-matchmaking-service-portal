#!/usr/bin/env bash

# Environment file deployment
# Rebuilds .env atomically from the tracked template (.env.stg / .env.prod)
# plus the server-only .env.db (gitignored, holds the real DB credentials).
# Runs on every deploy, including "no changes" runs, so an interrupted
# previous deploy can never leave a stale/broken .env behind.

set -euo pipefail

APP_DIR="$1"
ENVIRONMENT="${2:-dev}"

# Setup logging
LOG_FILE="$APP_DIR/storage/logs/env-deploy.log"
mkdir -p "$APP_DIR/storage/logs"
exec &>> "$LOG_FILE"

echo "[$(date '+%F %T')] Rebuilding .env..."

cd "$APP_DIR"

if [[ "$ENVIRONMENT" == "prod" ]]; then
    ENV_SOURCE=".env.prod"
else
    ENV_SOURCE=".env.stg"
fi

echo "Environment: $ENVIRONMENT (template: $ENV_SOURCE)"

if [[ ! -f "$ENV_SOURCE" ]]; then
  echo "✗ ERROR: template $ENV_SOURCE not found in $APP_DIR — keeping current .env and aborting."
  exit 1
fi

if [[ ! -f .env.db ]]; then
  echo "✗ ERROR: .env.db not found in $APP_DIR — it holds the DB credentials and must exist on the server."
  echo "  Keeping current .env and aborting."
  exit 1
fi

# Build to a temp file in the same directory, then mv (atomic on the same
# filesystem) so live requests never see a partial or template-only .env.
# The extra echo guards against a template without a trailing newline, which
# would glue .env.db's first line onto the template's last line.
TMP_ENV=".env.tmp.$$"
trap 'rm -f "$TMP_ENV"' EXIT
{ cat "$ENV_SOURCE"; echo; cat .env.db; } > "$TMP_ENV"

# Sanity check: the merged file must contain the DB connection settings.
for REQUIRED in DB_CONNECTION DB_HOST DB_DATABASE; do
  if ! grep -q "^${REQUIRED}=" "$TMP_ENV"; then
    echo "✗ ERROR: merged env file is missing ${REQUIRED} — keeping current .env and aborting."
    exit 1
  fi
done

# Preserve the permissions of the existing .env (the web server must keep read access).
if [[ -f .env ]]; then
  chmod --reference=.env "$TMP_ENV"
fi

mv -f "$TMP_ENV" .env
trap - EXIT
echo "✓ .env rebuilt from $ENV_SOURCE + .env.db"

echo "[$(date '+%F %T')] Environment file deployment completed successfully"
