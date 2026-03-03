#!/usr/bin/env bash
set -euo pipefail

# Sync a WordPress.org-style plugin payload into the wordpress-deploy test env
# Usage:
#   bin/sync-to-wordpress-deploy.sh
#   bin/sync-to-wordpress-deploy.sh /custom/wp-deploy/path

SRC_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DEPLOY_ROOT="${1:-/home/mike/Documents/www/test/wordpress-deploy}"
DEST_DIR="$DEPLOY_ROOT/html/web/app/plugins/all-purpose-directory"

if [[ ! -d "$DEPLOY_ROOT/html/web/app/plugins" ]]; then
  echo "Error: deploy plugins directory not found: $DEPLOY_ROOT/html/web/app/plugins" >&2
  exit 1
fi

mkdir -p "$DEST_DIR"

if [[ ! -f "$SRC_DIR/vendor/autoload.php" ]]; then
  echo "Error: $SRC_DIR/vendor/autoload.php is missing." >&2
  echo "Build the plugin autoloader first (e.g. composer install --no-dev --optimize-autoloader)." >&2
  exit 1
fi

# Base excludes from repo-maintained distignore + deployment-specific extras.
# (We keep README.txt/CHANGELOG.md for wp.org metadata.)
RSYNC_EXCLUDES=(
  --exclude-from="$SRC_DIR/.distignore"
  --exclude="docs"
  --exclude="phpcs.xml.dist"
  --exclude="phpunit*.xml*"
  --exclude="playwright.config.ts"
)

rsync -a --delete --delete-excluded "${RSYNC_EXCLUDES[@]}" "$SRC_DIR/" "$DEST_DIR/"

echo "Synced plugin payload to: $DEST_DIR"
if [[ -f "$DEST_DIR/vendor/autoload.php" ]]; then
  echo "Verified: vendor/autoload.php present in deploy payload."
fi
