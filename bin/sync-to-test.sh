#!/usr/bin/env bash
set -euo pipefail

# Sync plugin files to a local WordPress test environment.
#
# Usage:
#   bin/sync-to-test.sh
#   bin/sync-to-test.sh /path/to/wp-content/plugins/all-purpose-directory
#
# Optional env vars:
#   APD_PLUGIN_SLUG      Plugin directory name (default: all-purpose-directory)
#   APD_TEST_ROOT        WordPress test root used for auto-detection
#                        (default: $HOME/Documents/www/test/wordpress)
#   APD_TEST_PLUGIN_DIR  Explicit plugin target directory (overrides auto-detection)

SRC_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PLUGIN_SLUG="${APD_PLUGIN_SLUG:-all-purpose-directory}"
TEST_ROOT="${APD_TEST_ROOT:-$HOME/Documents/www/test/wordpress}"

if [[ ! -f "$SRC_DIR/.distignore" ]]; then
  echo "Error: .distignore not found in source directory: $SRC_DIR" >&2
  exit 1
fi

if [[ $# -gt 0 ]]; then
  DEST_DIR="$1"
elif [[ -n "${APD_TEST_PLUGIN_DIR:-}" ]]; then
  DEST_DIR="$APD_TEST_PLUGIN_DIR"
else
  # Try common local layouts.
  CANDIDATE1="$TEST_ROOT/wp-content/plugins/$PLUGIN_SLUG"
  CANDIDATE2="$TEST_ROOT/html/wp-content/plugins/$PLUGIN_SLUG"

  if [[ -d "$TEST_ROOT/wp-content/plugins" ]]; then
    DEST_DIR="$CANDIDATE1"
  elif [[ -d "$TEST_ROOT/html/wp-content/plugins" ]]; then
    DEST_DIR="$CANDIDATE2"
  else
    echo "Error: could not auto-detect a plugins directory under: $TEST_ROOT" >&2
    echo "Provide a destination directory as arg 1 or set APD_TEST_PLUGIN_DIR." >&2
    echo "Example:" >&2
    echo "  bin/sync-to-test.sh /path/to/wp-content/plugins/$PLUGIN_SLUG" >&2
    exit 1
  fi
fi

mkdir -p "$DEST_DIR"

echo "Syncing plugin to test environment..."
echo "Source:      $SRC_DIR"
echo "Destination: $DEST_DIR"

# Mirror production-like payload by excluding .distignore entries.
# vendor/ stays included unless explicitly listed in .distignore.
rsync -av --delete --delete-excluded \
  --exclude-from="$SRC_DIR/.distignore" \
  "$SRC_DIR/" \
  "$DEST_DIR/"

echo "Done! Plugin synced to: $DEST_DIR"
