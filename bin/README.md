# `bin/` helper scripts

Portable helper scripts for local testing and deploy-like packaging.

## `sync-to-test.sh`

Sync the plugin from this repo into a local WordPress test install.

### Usage

```bash
# Auto-detect plugin dir from default test root
bin/sync-to-test.sh

# Explicit plugin destination
bin/sync-to-test.sh /path/to/wp-content/plugins/all-purpose-directory
```

### Environment variables

- `APD_PLUGIN_SLUG` (default: `all-purpose-directory`)
- `APD_TEST_ROOT` (default: `$HOME/Documents/www/test/wordpress`)
- `APD_TEST_PLUGIN_DIR` (explicit destination; overrides auto-detection)

### Auto-detection paths

If no destination is passed, the script checks:

1. `$APD_TEST_ROOT/wp-content/plugins/$APD_PLUGIN_SLUG`
2. `$APD_TEST_ROOT/html/wp-content/plugins/$APD_PLUGIN_SLUG`

---

## `sync-to-wordpress-deploy.sh`

Sync a WordPress.org-style payload into a local `wordpress-deploy` environment.

### Usage

```bash
# Default deploy root
bin/sync-to-wordpress-deploy.sh

# Custom deploy root
bin/sync-to-wordpress-deploy.sh /path/to/wordpress-deploy
```

### Environment variables

- `APD_PLUGIN_SLUG` (default: `all-purpose-directory`)
- `APD_DEPLOY_ROOT` (default: `$HOME/Documents/www/test/wordpress-deploy`)
- `APD_DEPLOY_DEST` (explicit destination plugin dir; overrides root)

### Notes

- Requires `vendor/autoload.php` in repo root.
- Excludes are based on `.distignore` plus deploy-specific excludes in the script.
- Uses `rsync --delete --delete-excluded` to mirror source to destination.

---

## `build-release.sh`

Create a clean distribution folder + zip archive for production delivery.

### Usage

```bash
# Use version from plugin header
bin/build-release.sh

# Explicit version label for zip filename
bin/build-release.sh 1.0.1
```

### Environment variables

- `APD_PLUGIN_SLUG` (default: `all-purpose-directory`)
- `APD_OUTPUT_DIR` (default: `<repo>/dist`)
- `APD_VERSION` (overrides CLI version)
- `APD_KEEP_STAGE` (`1` keep staged dir, `0` remove it after zip)

---

## Typical examples

```bash
# Linux/macOS default local test env
bin/sync-to-test.sh

# Linux/macOS default wordpress-deploy env
bin/sync-to-wordpress-deploy.sh

# Build zip from current plugin header version
bin/build-release.sh

# Custom locations
APD_TEST_ROOT="$HOME/dev/wp-test" bin/sync-to-test.sh
APD_DEPLOY_ROOT="$HOME/dev/wordpress-deploy" bin/sync-to-wordpress-deploy.sh
APD_OUTPUT_DIR="$HOME/dev/apd-dist" bin/build-release.sh 1.0.1
```
