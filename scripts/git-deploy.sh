#!/usr/bin/env bash

# Git deployment operations
# This script handles all git-related deployment tasks

set -euo pipefail

REMOTE="origin"
APP_DIR="$1"
BRANCH="${2:-main}"

# Setup logging
LOG_FILE="$APP_DIR/storage/logs/git-deploy.log"
mkdir -p "$APP_DIR/storage/logs"
exec &>> "$LOG_FILE"

echo "[$(date '+%F %T')] Starting git operations..."

# Change to app directory
cd "$APP_DIR"

# Fetch latest refs
echo "git fetch --prune '$REMOTE'"
git fetch --prune "$REMOTE"

# Which branch is currently checked out (may differ from target on first switch,
# and may be "HEAD" if detached). Compared against $BRANCH below.
CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)
echo "current branch: $CURRENT_BRANCH"

# Compare the currently-deployed commit (HEAD) against the target remote branch.
LOCAL=$(git rev-parse HEAD)
echo "local HEAD: $LOCAL"
REMOTE_BRANCH=$(git rev-parse "$REMOTE/$BRANCH")
echo "remote $REMOTE/$BRANCH: $REMOTE_BRANCH"

# Skip ONLY when we are already on the target branch AND it is up to date.
# The branch check matters: if we're on a different branch (e.g. main while
# deploying develop) we must still switch, even when the commits happen to match.
if [[ "$CURRENT_BRANCH" == "$BRANCH" && "$LOCAL" == "$REMOTE_BRANCH" ]]; then
  echo "[$(date '+%F %T')] Already on $BRANCH and up to date with $REMOTE/$BRANCH — nothing to deploy."
  exit 200  # sentinel: "no changes" (distinct from a real git failure)
else
  echo "[$(date '+%F %T')] Deploying $BRANCH (branch switch or new commits) — proceeding…"
fi

# Checkout and update
git checkout "$BRANCH"

# Clean + hard reset to ensure pristine working tree
git reset --hard
git pull --ff-only "$REMOTE" "$BRANCH"

echo "[$(date '+%F %T')] Git operations completed successfully"