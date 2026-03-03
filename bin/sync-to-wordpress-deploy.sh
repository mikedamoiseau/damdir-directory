#!/usr/bin/env bash
set -euo pipefail

# Sync a WordPress.org-style plugin payload into a local wordpress-deploy test env.
#
# Usage:
#   bin/sync-to-wordpress-deploy.sh
#   bin/sync-to-wordpress-deploy.sh /custom/wp-deploy/root
#
# Optional env vars:
#   APD_PLUGIN_SLUG    Plugin directory name (default: all-purpose-directory)
#   APD_DEPLOY_ROOT    Deploy root (default: $HOME/Documents/www/test/wordpress-deploy)
#   APD_DEPLOY_DEST    Explicit destination plugin directory
#
# If APD_DEPLOY_DEST is set, it takes precedence over APD_DEPLOY_ROOT.

SRC_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PLUGIN_SLUG="${APD_PLUGIN_SLUG:-all-purpose-directory}"
DEPLOY_ROOT="${1:-${APD_DEPLOY_ROOT:-$HOME/Documents/www/test/wordpress-deploy}}"

if [[ -n "${APD_DEPLOY_DEST:-}" ]]; then
  DEST_DIR="$APD_DEPLOY_DEST"
else
  DEST_DIR="$DEPLOY_ROOT/html/web/app/plugins/$PLUGIN_SLUG"
fi

DEST_PARENT="$(dirname "$DEST_DIR")"
if [[ ! -d "$DEST_PARENT" ]]; then
  echo "Error: deploy plugins directory not found: $DEST_PARENT" >&2
  echo "Pass a valid deploy root as arg 1 or set APD_DEPLOY_DEST." >&2
  exit 1
fi

if [[ ! -f "$SRC_DIR/.distignore" ]]; then
  echo "Error: .distignore not found in source directory: $SRC_DIR" >&2
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

echo "Syncing wp.org-style payload..."
echo "Source:      $SRC_DIR"
echo "Destination: $DEST_DIR"

rsync -a --delete --delete-excluded "${RSYNC_EXCLUDES[@]}" "$SRC_DIR/" "$DEST_DIR/"

echo "Synced plugin payload to: $DEST_DIR"
if [[ -f "$DEST_DIR/vendor/autoload.php" ]]; then
  echo "Verified: vendor/autoload.php present in deploy payload."
fi
