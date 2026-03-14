#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
IMAGE_TAG="${APD_TEST_IMAGE:-apd/test-tools:local}"
PLUGIN_SLUG="all-purpose-directory"
BUILD_DIR="${BUILD_DIR:-$(mktemp -d -t apd-plugin-check-build-XXXXXX)}"
WP_DIR="${WP_DIR:-$(mktemp -d -t apd-plugin-check-wp-XXXXXX)}"
STACK_ID="apd-check-$(date +%s)-$RANDOM"
NETWORK="${APD_CHECK_NETWORK:-$STACK_ID-net}"
DB_CONTAINER="${APD_CHECK_DB_CONTAINER:-$STACK_ID-db}"
WP_CONTAINER="${APD_CHECK_WP_CONTAINER:-$STACK_ID-wp}"
TIMEOUT_SECS="${PLUGIN_CHECK_TIMEOUT:-300}"
KEEP_STACK="${KEEP_STACK:-0}"

cleanup() {
  if [[ "$KEEP_STACK" == "1" ]]; then
    return
  fi

  docker rm -f "$WP_CONTAINER" >/dev/null 2>&1 || true
  docker rm -f "$DB_CONTAINER" >/dev/null 2>&1 || true
  docker network rm "$NETWORK" >/dev/null 2>&1 || true

  if [[ -d "$WP_DIR" ]]; then
    docker run --rm -v "$WP_DIR:/target" alpine:3.20 sh -lc 'rm -rf /target/* /target/.[!.]* /target/..?* 2>/dev/null || true' >/dev/null 2>&1 || true
  fi

  rm -rf "$BUILD_DIR" "$WP_DIR" >/dev/null 2>&1 || true
}
trap cleanup EXIT

wait_for() {
  local description="$1"
  local retries="$2"
  local sleep_secs="$3"
  shift 3

  for ((i=1; i<=retries; i++)); do
    if "$@" >/dev/null 2>&1; then
      return 0
    fi
    sleep "$sleep_secs"
  done

  echo "Timed out waiting for: $description" >&2
  return 1
}

run_wp() {
  docker run --rm --network "$NETWORK" -u "$(id -u):$(id -g)" \
    -e HOME=/tmp \
    -v "$WP_DIR:/var/www/html" -w /var/www/html \
    "$IMAGE_TAG" bash -lc "$*"
}

run_wp_root() {
  docker run --rm --network "$NETWORK" \
    -v "$WP_DIR:/var/www/html" -w /var/www/html \
    "$IMAGE_TAG" bash -lc "$*"
}

echo "> Building/checking local test image"
"$SCRIPT_DIR/docker-test.sh" build-image >/dev/null

echo "> Preparing plugin payload"
mkdir -p "$BUILD_DIR"
rsync -a --delete \
  --exclude '.git' \
  --exclude 'dist' \
  --exclude '.stage' \
  --exclude '*.zip' \
  --exclude-from "$REPO_ROOT/.distignore" \
  "$REPO_ROOT/" "$BUILD_DIR/$PLUGIN_SLUG/"

echo "> Creating isolated Docker network: $NETWORK"
docker network create "$NETWORK" >/dev/null

echo "> Starting MySQL container"
docker run -d --name "$DB_CONTAINER" --network "$NETWORK" \
  -e MYSQL_DATABASE=wordpress \
  -e MYSQL_USER=wp \
  -e MYSQL_PASSWORD=wp \
  -e MYSQL_ROOT_PASSWORD=root \
  mysql:8.0 >/dev/null

echo "> Waiting for MySQL readiness"
wait_for "MySQL readiness" 60 2 docker exec "$DB_CONTAINER" mysqladmin ping -h127.0.0.1 -uroot -proot --silent

echo "> Preparing WordPress files"
mkdir -p "$WP_DIR"
docker run --rm -u "$(id -u):$(id -g)" \
  -v "$WP_DIR:/var/www/html" \
  wordpress:php8.2-apache \
  bash -lc 'tar -C /usr/src/wordpress -cf - . | tar -C /var/www/html -xf -'
mkdir -p "$WP_DIR/wp-content/plugins/$PLUGIN_SLUG"
rsync -a --delete "$BUILD_DIR/$PLUGIN_SLUG/" "$WP_DIR/wp-content/plugins/$PLUGIN_SLUG/"

echo "> Starting WordPress container"
docker run -d --name "$WP_CONTAINER" --network "$NETWORK" \
  -e WORDPRESS_DB_HOST="$DB_CONTAINER:3306" \
  -e WORDPRESS_DB_USER=wp \
  -e WORDPRESS_DB_PASSWORD=wp \
  -e WORDPRESS_DB_NAME=wordpress \
  -v "$WP_DIR:/var/www/html" \
  wordpress:php8.2-apache >/dev/null

echo "> Waiting for WordPress HTTP endpoint"
for _ in {1..60}; do
  HTTP_CODE="$(docker run --rm --network "$NETWORK" curlimages/curl:8.6.0 -s -o /dev/null -w '%{http_code}' "http://$WP_CONTAINER/" || true)"
  if [[ "$HTTP_CODE" != "000" && -n "$HTTP_CODE" ]]; then
    break
  fi
  sleep 2
done

if [[ "${HTTP_CODE:-000}" == "000" ]]; then
  echo "Timed out waiting for: WordPress HTTP endpoint" >&2
  exit 1
fi

echo "> Writing wp-config.php for this stack"
run_wp_root "wp config create --dbname=wordpress --dbuser=wp --dbpass=wp --dbhost=$DB_CONTAINER:3306 --skip-check --force --allow-root >/dev/null"
run_wp_root "chown -R $(id -u):$(id -g) /var/www/html"

echo "> Ensuring WordPress core is installed"
run_wp "if ! wp core is-installed --allow-root >/dev/null 2>&1; then wp core install --url=http://$WP_CONTAINER --title='APD Test' --admin_user=admin --admin_password=admin --admin_email=admin@example.com --skip-email --allow-root >/dev/null; else echo 'WordPress already installed, skipping wp core install'; fi"

echo "> Ensuring plugin-check plugin is installed + active"
run_wp "if ! wp plugin is-installed plugin-check --allow-root >/dev/null 2>&1; then wp plugin install plugin-check --activate --allow-root >/dev/null; else wp plugin activate plugin-check --allow-root >/dev/null || true; fi"

echo "> Ensuring $PLUGIN_SLUG is active"
run_wp "wp plugin activate $PLUGIN_SLUG --allow-root >/dev/null || true"

echo "> Running wp plugin check (timeout: ${TIMEOUT_SECS}s)"
set +e
# macOS lacks GNU timeout; fall back to running without a wrapper.
if command -v timeout >/dev/null 2>&1; then
  TIMEOUT_PREFIX="timeout $TIMEOUT_SECS"
elif command -v gtimeout >/dev/null 2>&1; then
  TIMEOUT_PREFIX="gtimeout $TIMEOUT_SECS"
else
  TIMEOUT_PREFIX=""
fi
$TIMEOUT_PREFIX docker run --rm --network "$NETWORK" -u "$(id -u):$(id -g)" \
  -e HOME=/tmp \
  -v "$WP_DIR:/var/www/html" -w /var/www/html \
  "$IMAGE_TAG" bash -lc "wp plugin check $PLUGIN_SLUG --allow-root --path=/var/www/html --skip-themes --format=table"
RC=$?
set -e

if [[ $RC -eq 124 ]]; then
  echo "Plugin check timed out after ${TIMEOUT_SECS}s" >&2
  exit 124
fi

exit $RC
