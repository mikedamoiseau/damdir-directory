#!/usr/bin/env bash
set -euo pipefail

# Build a clean, production-ready plugin payload + zip archive.
#
# Usage:
#   bin/build-release.sh
#   bin/build-release.sh 1.0.0
#
# Optional env vars:
#   APD_PLUGIN_SLUG      Plugin slug/directory (default: all-purpose-directory)
#   APD_OUTPUT_DIR       Output directory for build artifacts (default: <repo>/dist)
#   APD_VERSION          Version label for zip name (overrides CLI arg)
#   APD_KEEP_STAGE       Keep staged plugin directory after zip (1=yes, default: 1)

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
PLUGIN_SLUG="${APD_PLUGIN_SLUG:-all-purpose-directory}"
OUTPUT_DIR="${APD_OUTPUT_DIR:-$REPO_ROOT/dist}"
KEEP_STAGE="${APD_KEEP_STAGE:-1}"

DISTIGNORE="$REPO_ROOT/.distignore"
MAIN_PLUGIN_FILE="$REPO_ROOT/${PLUGIN_SLUG}.php"

if [[ ! -f "$DISTIGNORE" ]]; then
  echo "Error: .distignore not found at $DISTIGNORE" >&2
  exit 1
fi

if [[ ! -f "$MAIN_PLUGIN_FILE" ]]; then
  echo "Error: main plugin file not found: $MAIN_PLUGIN_FILE" >&2
  exit 1
fi

if [[ ! -f "$REPO_ROOT/vendor/autoload.php" ]]; then
  echo "Error: vendor/autoload.php is missing." >&2
  echo "Run composer install --no-dev --optimize-autoloader first." >&2
  exit 1
fi

if ! command -v rsync >/dev/null 2>&1; then
  echo "Error: rsync is required but not installed." >&2
  exit 1
fi

if ! command -v zip >/dev/null 2>&1; then
  echo "Error: zip is required but not installed." >&2
  exit 1
fi

DEFAULT_VERSION="$(grep -E '^\s*\*\s*Version:' "$MAIN_PLUGIN_FILE" | head -n1 | sed -E 's/^\s*\*\s*Version:\s*//; s/\s+$//')"
CLI_VERSION="${1:-}"
VERSION="${APD_VERSION:-${CLI_VERSION:-$DEFAULT_VERSION}}"

if [[ -z "$VERSION" ]]; then
  echo "Error: could not determine version from plugin header. Pass one explicitly:" >&2
  echo "  bin/build-release.sh 1.0.0" >&2
  exit 1
fi

STAGE_ROOT="$OUTPUT_DIR/.stage"
STAGE_DIR="$STAGE_ROOT/$PLUGIN_SLUG"
ZIP_PATH="$OUTPUT_DIR/${PLUGIN_SLUG}-${VERSION}.zip"

mkdir -p "$STAGE_ROOT" "$OUTPUT_DIR"
rm -rf "$STAGE_DIR"
mkdir -p "$STAGE_DIR"

echo "Building release payload..."
echo "- Repo:      $REPO_ROOT"
echo "- Version:   $VERSION"
echo "- Stage dir: $STAGE_DIR"
echo "- Zip file:  $ZIP_PATH"

rsync -a --delete --delete-excluded \
  --exclude-from="$DISTIGNORE" \
  "$REPO_ROOT/" "$STAGE_DIR/"

# Extra safety: remove any nested dist output that might have been copied.
rm -rf "$STAGE_DIR/dist" "$STAGE_DIR/.stage"

# Fresh zip
rm -f "$ZIP_PATH"
(
  cd "$STAGE_ROOT"
  zip -r -q "$ZIP_PATH" "$PLUGIN_SLUG"
)

echo "Done."
echo "- Staged plugin dir: $STAGE_DIR"
echo "- Release archive:   $ZIP_PATH"

if [[ "$KEEP_STAGE" != "1" ]]; then
  rm -rf "$STAGE_DIR"
  echo "- Stage directory removed (APD_KEEP_STAGE=$KEEP_STAGE)"
fi
