#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
IMAGE_TAG="${APD_TEST_IMAGE:-apd/test-tools:local}"
DOCKERFILE_PATH="${APD_TEST_DOCKERFILE:-$REPO_ROOT/docker/php-tools/Dockerfile}"

usage() {
  cat <<'EOF'
Usage: bin/docker-test.sh <command>

Commands:
  build-image        Build/update the local test image
  shell              Open a shell inside the test image
  composer-install   Run composer install
  lint               Run composer lint
  phpcs              Run composer phpcs
  test-unit          Run composer test:unit
  test-integration   Run composer test:integration
  run <cmd...>       Run arbitrary command inside the container

Environment:
  APD_TEST_IMAGE       Docker image tag (default: apd/test-tools:local)
  APD_TEST_DOCKERFILE  Dockerfile path
EOF
}

ensure_image() {
  if ! docker image inspect "$IMAGE_TAG" >/dev/null 2>&1; then
    echo "> Building test image: $IMAGE_TAG"
    docker build -t "$IMAGE_TAG" -f "$DOCKERFILE_PATH" "$REPO_ROOT"
  fi
}

run_in_image() {
  docker run --rm -t \
    -u "$(id -u):$(id -g)" \
    -v "$REPO_ROOT:/work" \
    -w /work \
    "$IMAGE_TAG" \
    bash -lc "$*"
}

cmd="${1:-}"
shift || true

case "$cmd" in
  build-image)
    docker build -t "$IMAGE_TAG" -f "$DOCKERFILE_PATH" "$REPO_ROOT"
    ;;
  shell)
    ensure_image
    run_in_image "bash"
    ;;
  composer-install)
    ensure_image
    run_in_image "composer install"
    ;;
  lint)
    ensure_image
    run_in_image "composer lint"
    ;;
  phpcs)
    ensure_image
    run_in_image "composer phpcs"
    ;;
  test-unit)
    ensure_image
    run_in_image "composer test:unit -- --cache-result-file=/tmp/phpunit.result.cache"
    ;;
  test-integration)
    ensure_image
    run_in_image "composer test:integration -- --cache-result-file=/tmp/phpunit.result.cache"
    ;;
  run)
    ensure_image
    if [[ $# -eq 0 ]]; then
      echo "Error: missing command for 'run'" >&2
      exit 1
    fi
    run_in_image "$*"
    ;;
  ""|-h|--help|help)
    usage
    ;;
  *)
    echo "Error: unknown command '$cmd'" >&2
    usage
    exit 1
    ;;
esac
