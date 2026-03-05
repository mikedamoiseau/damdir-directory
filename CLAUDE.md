# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**All Purpose Directory** is a WordPress plugin for building directory/listing websites. The plugin uses a modular architecture where the core provides generic directory functionality, with optional modules for specific use cases (business directories, job boards, real estate, classifieds).

- **Plugin Slug:** `all-purpose-directory`
- **Prefix:** `apd_` / `APD`
- **Requirements:** PHP 8.0+, WordPress 6.0+

**Current Status:** Module API added. Listing Type Selector feature complete (type-aware fields, dynamic JS switching, admin column). Automated Phase 17 artifacts (testing/docs) are in place. Runtime minimum remains PHP 8.0+; CI matrix currently validates PHP 8.1-8.3 (21 tests). Manual testing checklist is in docs/TESTING-CHECKLIST.md. Documentation: README.txt, CHANGELOG.md, docs/USER-GUIDE.md, docs/DEVELOPER.md. 2,660+ unit tests. Remaining: manual compatibility validation on WordPress versions and theme/plugin matrix. See PLAN.md for feature roadmap and TASKS.md for implementation checklist.

## Development Commands

```bash
# Docker test environment (from plugin root)
./bin/docker-test.sh build-image      # Build/update test image
./bin/docker-test.sh composer-install # Install Composer deps in container
./bin/docker-test.sh run "php -v"     # Run command in test container

# PHP tests (from plugin directory)
composer test:unit            # Run unit tests (fast, no WP)
composer test:integration     # Run integration tests (requires Docker)
composer test                 # Alias for test:integration

# E2E tests
npm run test:e2e              # Run Playwright tests
npm run test:e2e:ui           # Playwright with UI
npm run test:e2e:headed       # Headed browser mode

# WP-CLI commands (run inside Docker container with --allow-root)
wp apd demo generate          # Generate all demo data with defaults
wp apd demo generate --listings=50 --users=10  # Custom quantities
wp apd demo generate --types=categories,tags,listings  # Selective types
wp apd demo delete --yes      # Delete all demo data (skip prompt)
wp apd demo status            # Show current demo data counts
wp apd demo status --format=json  # Output as JSON

# Internationalization
npm run i18n:pot              # Generate POT file (languages/all-purpose-directory.pot)

# Sync plugin to test environment (excludes dev files for clean plugin check)
# Set paths for your machine before running:
#   APD_SOURCE_DIR=/path/to/all-purpose-directory
#   APD_TEST_PLUGIN_DIR=/path/to/wp-content/plugins/all-purpose-directory
rsync -av --delete \
  --exclude='vendor' \
  --exclude='node_modules' \
  --exclude='composer.lock' \
  --exclude='.phpunit.result.cache' \
  --exclude='.idea' \
  --exclude='.claude' \
  --exclude='.claude-bw' \
  --exclude='.git' \
  --exclude='.github' \
  --exclude='.gitignore' \
  --exclude='.distignore' \
  --exclude='.gitkeep' \
  --exclude='.DS_Store' \
  --exclude='phpcs.xml.dist' \
  --exclude='phpunit.xml.dist' \
  --exclude='phpunit-unit.xml' \
  --exclude='CLAUDE.md' \
  --exclude='PLAN.md' \
  --exclude='TASKS.md' \
  --exclude='tests' \
  --exclude='bin' \
  --exclude='research' \
  "$APD_SOURCE_DIR"/ \
  "$APD_TEST_PLUGIN_DIR"/

# Plugin Check (run after writing or modifying code)
docker exec wp-all-purpose-directory-web-1 wp plugin check all-purpose-directory \
  --exclude-directories=tests,bin,research,.git,.github \
  --exclude-files=.gitignore,.distignore,phpunit.xml.dist,phpunit-unit.xml,phpcs.xml.dist,CLAUDE.md,PLAN.md,TASKS.md,CHANGELOG.md,.gitkeep,.phpunit.result.cache,.DS_Store \
  --allow-root

# Build distribution zip (excludes dev files via .distignore)
wp dist-archive .
```

## Development Workflow

**After writing new code or modifying existing code, always:**

1. **Run tests** - `composer test:unit` for unit tests, `composer test` for integration tests
2. **Run Plugin Check** - Ensures code meets WordPress.org standards (see command above)
3. **Build with `wp dist-archive`** - Uses `.distignore` to create a clean zip without dev files
4. **Update documentation** - After completing a feature or phase:
    - Update `CLAUDE.md` with new functions, hooks, patterns, and current status
    - Update `TASKS.md` to mark completed tasks and update the Task Summary table

## Architecture

### Plugin Structure
```
all-purpose-directory/
├── all-purpose-directory.php    # Main plugin file, singleton bootstrap
├── src/
│   ├── Core/                    # Plugin, Activator, Deactivator, Assets, Template
│   ├── Admin/                   # ListingMetaBox, ListingTypeMetaBox, ReviewModeration
│   ├── Listing/                 # PostType, AdminColumns, ListingRepository, ListingQuery
│   ├── Fields/                  # FieldRegistry, FieldRenderer, FieldValidator, Types/
│   ├── Taxonomy/                # CategoryTaxonomy, TagTaxonomy
│   ├── Search/                  # SearchQuery, FilterRegistry, Filters/
│   ├── Frontend/                # Submission/, Display/, Dashboard/
│   ├── Shortcode/               # ShortcodeManager, AbstractShortcode, *Shortcode
│   ├── Blocks/                  # BlockManager, AbstractBlock, *Block
│   ├── User/                    # Favorites, Profile
│   ├── Review/                  # ReviewManager, RatingCalculator
│   ├── Contact/                 # ContactForm, ContactHandler
│   ├── Email/                   # EmailManager, Templates/
│   ├── Api/                     # RestController, Endpoints/
│   ├── CLI/                     # WP-CLI commands (DemoDataCommand)
│   ├── Module/                  # ModuleRegistry, ModuleInterface, ModulesAdminPage
│   └── Contracts/               # FieldTypeInterface, FilterInterface, ViewInterface
├── templates/                   # Theme-overrideable PHP templates
├── assets/                      # CSS, JS, images
└── tests/                       # unit/, integration/, e2e/, factories/, fixtures/
```

### Key Patterns

**Registry Pattern:** Fields, filters, views, shortcodes, and blocks use registry classes for registration/retrieval.

**Hook System:** Core hooks for extensibility:
- Actions: `apd_init`, `apd_loaded`, `apd_textdomain_loaded`, `apd_modules_init`, `apd_modules_loaded`, `apd_module_registered`, `apd_module_unregistered`, `apd_before_listing_save`, `apd_after_listing_save`, `apd_listing_status_changed`, `apd_before_submission`, `apd_after_submission`, `apd_validate_submission`, `apd_listing_saved`, `apd_listing_fields_saved`, `apd_listing_taxonomies_assigned`, `apd_spam_attempt_detected`, `apd_field_registered`, `apd_field_unregistered`, `apd_field_type_registered`, `apd_after_admin_fields`, `apd_after_frontend_fields`, `apd_after_validate_fields`, `apd_filter_registered`, `apd_filter_unregistered`, `apd_before_search_form`, `apd_after_search_form`, `apd_search_query_modified`, `apd_before_archive`, `apd_after_archive`, `apd_before_single_listing`, `apd_after_single_listing`, `apd_single_listing_meta`, `apd_listing_viewed`, `apd_views_init`, `apd_view_registered`, `apd_shortcodes_init`, `apd_shortcode_registered`, `apd_block_registered`, `apd_user_registered`, `apd_after_submission_success`, `apd_before_dashboard`, `apd_after_dashboard`, `apd_dashboard_start`, `apd_dashboard_end`, `apd_dashboard_before_content`, `apd_dashboard_after_content`, `apd_profile_start`, `apd_profile_end`, `apd_profile_saved`, `apd_before_save_profile`, `apd_after_save_profile`, `apd_avatar_uploaded`, `apd_favorite_added`, `apd_favorite_removed`, `apd_favorites_cleared`, `apd_favorites_init`, `apd_reviews_init`, `apd_before_review_create`, `apd_review_created`, `apd_before_review_update`, `apd_review_updated`, `apd_before_review_delete`, `apd_review_deleted`, `apd_review_approved`, `apd_review_rejected`, `apd_contact_form_init`, `apd_contact_handler_init`, `apd_contact_form_after_fields`, `apd_before_send_contact`, `apd_contact_sent`, `apd_inquiry_tracker_init`, `apd_inquiry_logged`, `apd_inquiry_marked_read`, `apd_inquiry_marked_unread`, `apd_before_inquiry_delete`, `apd_email_manager_init`, `apd_before_send_email`, `apd_after_send_email`, `apd_settings_init`, `apd_register_settings`, `apd_before_settings_page`, `apd_after_settings_page`, `apd_before_settings_tab`, `apd_after_settings_tab`, `apd_rest_api_init`, `apd_register_rest_routes`, `apd_rest_routes_registered`, `apd_rest_endpoint_registered`, `apd_rest_endpoint_unregistered`
- Filters: `apd_listing_fields`, `apd_submission_fields`, `apd_submission_form_data`, `apd_new_listing_post_data`, `apd_submission_default_status`, `apd_edit_listing_status`, `apd_user_can_submit_listing`, `apd_user_can_edit_listing`, `apd_submission_admin_notification`, `apd_submission_success_redirect`, `apd_submission_error_redirect`, `apd_submission_success_args`, `apd_edit_not_allowed_args`, `apd_submission_page_url`, `apd_edit_listing_url`, `apd_honeypot_field_name`, `apd_submission_min_time`, `apd_submission_rate_limit`, `apd_submission_rate_period`, `apd_bypass_spam_protection`, `apd_submission_spam_check`, `apd_search_filters`, `apd_listing_query_args`, `apd_listing_card_data`, `apd_register_field_config`, `apd_get_field`, `apd_get_fields`, `apd_listing_field_value`, `apd_render_field`, `apd_validate_field`, `apd_register_filter_config`, `apd_search_query_args`, `apd_filter_options`, `apd_locate_template`, `apd_shortcode_{tag}_atts`, `apd_shortcode_{tag}_output`, `apd_block_{name}_args`, `apd_block_{name}_output`, `apd_dashboard_tabs`, `apd_dashboard_stats`, `apd_dashboard_url`, `apd_dashboard_args`, `apd_dashboard_html`, `apd_dashboard_classes`, `apd_profile_args`, `apd_profile_user_data`, `apd_validate_profile`, `apd_user_social_links`, `apd_favorites_require_login`, `apd_guest_favorites_enabled`, `apd_favorite_listings_query_args`, `apd_reviews_require_login`, `apd_review_min_content_length`, `apd_review_data`, `apd_review_default_status`, `apd_contact_form_classes`, `apd_contact_form_args`, `apd_contact_form_html`, `apd_listing_can_receive_contact`, `apd_contact_validation_errors`, `apd_contact_email_to`, `apd_contact_email_subject`, `apd_contact_email_message`, `apd_contact_email_headers`, `apd_contact_send_admin_copy`, `apd_contact_admin_email`, `apd_track_inquiry`, `apd_inquiry_post_type_args`, `apd_inquiry_post_data`, `apd_listing_inquiries_query_args`, `apd_user_inquiries_query_args`, `apd_user_can_view_inquiry`, `apd_email_to`, `apd_email_subject`, `apd_email_message`, `apd_email_headers`, `apd_email_from_name`, `apd_email_from_email`, `apd_email_admin_email`, `apd_email_notification_enabled`, `apd_email_replace_placeholders`, `apd_email_plain_text_message`, `apd_email_header_color`, `apd_email_header_text_color`, `apd_email_button_color`, `apd_settings_tabs`, `apd_settings_defaults`, `apd_sanitize_settings`, `apd_register_module_config`, `apd_get_module`, `apd_get_modules`, `apd_should_display_field`, `apd_archive_content`, `apd_search_form_block_args`, `apd_search_form_shortcode_args`, `apd_search_form_classes`

**Field Types Interface:**
```php
interface FieldTypeInterface {
    public function getType(): string;
    public function render(array $field, mixed $value): string;
    public function sanitize(mixed $value): mixed;
    public function validate(mixed $value, array $field): bool|WP_Error;
    public function getDefaultValue(): mixed;
    public function supports(string $feature): bool;  // searchable, filterable, sortable, repeater
    public function formatValue(mixed $value, array $field): string;
    public function prepareValueForStorage(mixed $value): mixed;
    public function prepareValueFromStorage(mixed $value): mixed;
}
```

**Meta Keys:** All listing meta uses prefix `_apd_{field_name}`. Defined keys:
- `_apd_views_count` - Integer count of listing views
- `_apd_category_icon` - Dashicon class for category
- `_apd_category_color` - Hex color for category

### Post Type & Taxonomies

- **Post Type:** `apd_listing` with statuses: publish, pending, draft, expired
- **Taxonomies:**
    - `apd_category` - Hierarchical (like categories), rewrite: `/listing-category/`
    - `apd_tag` - Non-hierarchical (like tags), rewrite: `/listing-tag/`

### Taxonomy Helper Functions

```php
apd_get_listing_categories( int $listing_id ): WP_Term[]
apd_get_listing_tags( int $listing_id ): WP_Term[]
apd_get_category_listings( int $category_id, array $args = [] ): WP_Post[]
apd_get_categories_with_count( array $args = [] ): WP_Term[]
apd_get_category_icon( int|WP_Term $category ): string
apd_get_category_color( int|WP_Term $category ): string
```

### Single Listing Helper Functions

```php
apd_get_related_listings( int $listing_id, int $limit = 4, array $args = [] ): WP_Post[]
apd_get_listing_views( int $listing_id ): int
apd_increment_listing_views( int $listing_id ): int  // Atomic DB update (race-condition safe)
```

### String Utility Functions

Mbstring-safe wrappers — use these instead of `mb_strlen()`/`mb_substr()` for hosts without `ext-mbstring`:

```php
apd_strlen( string $string ): int                              // String length (UTF-8)
apd_substr( string $string, int $start, ?int $length ): string // Substring (UTF-8)
```

### Favorites System

User favorites management (`src/User/Favorites.php`). See `.claude-bw/docs/claude-favorites.md` for full API reference including:
- Meta keys (`_apd_favorites`, `_apd_favorite_count`)
- Helper functions (`apd_add_favorite()`, `apd_remove_favorite()`, `apd_toggle_favorite()`, etc.)
- Hooks for actions and filters
- Guest favorites configuration

### Review System

Review management using WordPress comments with custom meta (`src/Review/ReviewManager.php`). See `.claude-bw/docs/claude-reviews.md` for full API reference including:
- Comment type `apd_review` and meta keys (`_apd_rating`, `_apd_review_title`)
- Review data structure and helper functions
- Validation rules and hooks
- Review Moderation admin interface (`src/Admin/ReviewModeration.php`)

### Contact & Inquiry System

Contact form and inquiry tracking (`src/Contact/`). See `.claude-bw/docs/claude-contact.md` for full API reference including:
- ContactForm and ContactHandler classes
- Configuration options and helper functions
- Email customization hooks
- Inquiry Tracker (`apd_inquiry` post type) for logging submissions
- Dashboard integration for inquiry stats

### Email System

EmailManager provides centralized email notifications (`src/Email/EmailManager.php`). See `.claude-bw/docs/claude-email.md` for full API reference including:
- EmailManager singleton with configuration
- 7 notification types: listing_submitted, listing_approved, listing_rejected, listing_expiring, listing_expired, new_review, new_inquiry
- HTML templates with theme override support (`templates/emails/`)
- Placeholder replacement system ({site_name}, {listing_title}, {author_name}, etc.)
- Helper functions (`apd_email_manager()`, `apd_send_email()`, `apd_send_listing_approved_email()`, etc.)
- Hooks for customization (filters for recipient, subject, message, headers; color customization)

### Admin Settings

Settings class provides admin settings page with tabbed interface (`src/Admin/Settings.php`):
- **Page Slug:** `apd-settings` (under Listings menu)
- **Option Name:** `apd_options`
- **Capability:** `manage_options`
- **Tabs:** General, Listings, Submission, Display, Email, Advanced

**Available Settings:**
| Tab | Settings |
|-----|----------|
| General | currency_symbol, currency_position, date_format, distance_unit |
| Listings | listings_per_page, default_status, expiration_days, enable_reviews, enable_favorites, enable_contact_form |
| Submission | who_can_submit, guest_submission, terms_page, redirect_after |
| Display | default_view, grid_columns, show_thumbnail, show_excerpt, show_category, show_rating, show_favorite, archive_title, single_layout |
| Email | from_name, from_email, admin_email, notify_submission, notify_approved, notify_rejected, notify_expiring, notify_review, notify_inquiry |
| Advanced | delete_data, custom_css, debug_mode |

**Helper Functions:**
```php
apd_settings()                           // Get Settings instance
apd_get_setting( $key, $default )        // Get single setting
apd_set_setting( $key, $value )          // Update single setting
apd_get_all_settings()                   // Get all settings with defaults
apd_get_settings_url( $tab )             // Get settings page URL
apd_reviews_enabled()                    // Check if reviews enabled
apd_favorites_enabled()                  // Check if favorites enabled
apd_contact_form_enabled()               // Check if contact form enabled
apd_get_listings_per_page()              // Get listings per page count
apd_get_default_view()                   // Get default view (grid/list)
apd_get_default_grid_columns()           // Get grid columns count
apd_get_currency_symbol()                // Get currency symbol
apd_get_currency_position()              // Get currency position
apd_format_price( $amount )              // Format price with currency
apd_get_distance_unit()                  // Get distance unit
apd_is_debug_mode()                      // Check if debug mode enabled
apd_debug_log( $message, $context )      // Log debug message
```

**Hooks:**
- `apd_settings_init` - After settings initialized
- `apd_register_settings` - After settings registered (add custom sections/fields)
- `apd_settings_tabs` - Filter available tabs
- `apd_settings_defaults` - Filter default settings values
- `apd_sanitize_settings` - Filter sanitized settings before save
- `apd_before_settings_page` / `apd_after_settings_page` - Page render hooks
- `apd_before_settings_tab` / `apd_after_settings_tab` - Tab content hooks

**Tab-Aware Sanitization:** Settings form includes a hidden `_active_tab` field. Checkbox values are only read from the submitted tab; other tabs preserve existing values. This prevents saving one tab from resetting checkboxes on other tabs to defaults.

### REST API

RestController provides REST API functionality with namespace `apd/v1` (`src/Api/RestController.php`):
- **Namespace:** `apd/v1`
- **Nonce Action:** `wp_rest`
- **Authentication:** WordPress nonces/cookies

**Permission Callbacks:**
| Method | Description |
|--------|-------------|
| `permission_public()` | Always returns true (public access) |
| `permission_authenticated()` | Requires logged-in user |
| `permission_create_listing()` | Requires `edit_apd_listings` capability |
| `permission_edit_listing()` | Requires ownership or `edit_post` capability |
| `permission_delete_listing()` | Requires ownership or `delete_post` capability |
| `permission_admin()` | Requires `manage_options` capability |
| `permission_manage_listings()` | Requires `edit_others_apd_listings` capability |

**Helper Functions:**
```php
apd_rest_controller()                              // Get RestController instance
apd_get_rest_namespace()                           // Get namespace ('apd/v1')
apd_get_rest_url( $route )                         // Build full REST URL
apd_register_rest_endpoint( $name, $endpoint )     // Register endpoint controller
apd_unregister_rest_endpoint( $name )              // Unregister endpoint controller
apd_has_rest_endpoint( $name )                     // Check if endpoint exists
apd_get_rest_endpoint( $name )                     // Get endpoint controller
apd_rest_response( $data, $status, $headers )      // Create REST response
apd_rest_error( $code, $message, $status, $data )  // Create REST error
apd_rest_paginated_response( $items, $total, ... ) // Create paginated response
```

**Hooks:**
- `apd_rest_api_init` - After REST controller initialized
- `apd_register_rest_routes` - Before routes registered (register custom endpoints)
- `apd_rest_routes_registered` - After all routes registered
- `apd_rest_endpoint_registered` - After an endpoint controller is registered
- `apd_rest_endpoint_unregistered` - After an endpoint controller is unregistered

**REST API Endpoints:**

| Endpoint | Method | Permission | Description |
|----------|--------|------------|-------------|
| `/listings` | GET | Public | List listings with filters (category, tag, author, status, search) |
| `/listings/{id}` | GET | Public | Get single listing with categories, tags, meta |
| `/listings` | POST | Authenticated | Create listing |
| `/listings/{id}` | PUT | Owner/Admin | Update listing |
| `/listings/{id}` | DELETE | Owner/Admin | Delete listing |
| `/categories` | GET | Public | List categories with icon/color meta |
| `/categories/{id}` | GET | Public | Get single category |
| `/tags` | GET | Public | List tags |
| `/tags/{id}` | GET | Public | Get single tag |
| `/favorites` | GET | Authenticated | Get user's favorite listing IDs |
| `/favorites/listings` | GET | Authenticated | Get user's favorite listings with data |
| `/favorites` | POST | Authenticated | Add listing to favorites |
| `/favorites/{id}` | DELETE | Authenticated | Remove from favorites |
| `/favorites/toggle/{id}` | POST | Authenticated | Toggle favorite status |
| `/reviews` | GET | Public | List reviews with filters |
| `/reviews/{id}` | GET | Public | Get single review |
| `/reviews` | POST | Authenticated | Create review |
| `/reviews/{id}` | PUT | Author/Admin | Update review |
| `/reviews/{id}` | DELETE | Author/Admin | Delete review |
| `/listings/{id}/reviews` | GET | Public | Get listing reviews with rating summary |
| `/inquiries` | GET | Authenticated | Get user's inquiries |
| `/inquiries/{id}` | GET | Owner/Admin | Get single inquiry |
| `/inquiries/{id}` | DELETE | Owner/Admin | Delete inquiry |
| `/inquiries/{id}/read` | POST | Owner/Admin | Mark inquiry as read |
| `/inquiries/{id}/unread` | POST | Owner/Admin | Mark inquiry as unread |
| `/listings/{id}/inquiries` | GET | Owner/Admin | Get listing inquiries |

### Performance System

Performance class provides transient caching and optimization utilities (`src/Core/Performance.php`):
- **Cache Group:** `apd`
- **Transient Prefix:** `apd_cache_`
- **Default Expiration:** 1 hour (3600 seconds)

**Cached Operations:**
| Operation | Expiration | Function |
|-----------|------------|----------|
| Categories with counts | 1 hour | `apd_get_cached_categories()` |
| Related listings | 15 min | `apd_get_cached_related_listings()` |
| Dashboard stats | 5 min | `apd_get_cached_dashboard_stats()` |
| Popular listings | 30 min | `apd_get_popular_listings()` |

**Helper Functions:**
```php
apd_performance()                                    // Get Performance instance
apd_cache_remember( $key, $callback, $expiration )   // Cache callback result
apd_cache_get( $key )                                // Get cached value
apd_cache_set( $key, $value, $expiration )           // Set cached value
apd_cache_delete( $key )                             // Delete cached value
apd_cache_clear_all()                                // Clear all plugin caches
apd_get_cached_categories( $args )                   // Get categories (cached)
apd_get_cached_related_listings( $id, $limit, $args )// Get related listings (cached)
apd_get_cached_dashboard_stats( $user_id )           // Get dashboard stats (cached)
apd_get_popular_listings( $limit, $args )            // Get popular listings (cached)
apd_invalidate_category_cache()                      // Invalidate category cache
```

**Hooks:**
- `apd_cache_expiration` - Filter cache expiration times
- `apd_category_cache_invalidated` - Action when category cache is invalidated
- `apd_listing_cache_invalidated` - Action when listing caches are invalidated
- `apd_cache_cleared` - Action when all caches are cleared

**Auto-Invalidation:** Cache is automatically invalidated when:
- Listings are created, updated, or deleted
- Categories are created, edited, or deleted
- Category/tag assignments change on listings

### Module System

ModuleRegistry provides an API for external modules (separate plugins) to register themselves (`src/Module/`):
- **Page Slug:** `apd-modules` (under Listings menu)
- **Capability:** `manage_options`
- **Initialization Priority:** `init` hook priority 1 (after text domain, before post types)

**Module Configuration:**
```php
[
    'name'        => 'URL Directory',        // Required
    'description' => 'Website directory',    // Optional
    'version'     => '1.0.0',                // Optional (default: 1.0.0)
    'author'      => 'Developer',            // Optional
    'author_uri'  => 'https://...',          // Optional
    'requires'    => ['core' => '1.0.0'],    // Optional - version requirements
    'features'    => ['link_checker'],       // Optional - feature flags
    'icon'        => 'dashicons-admin-links',// Optional (default: dashicons-admin-plugins)
    'priority'    => 10,                     // Optional (default: 10)
]
```

**Helper Functions:**
```php
apd_module_registry()                        // Get ModuleRegistry instance
apd_register_module( $slug, $config )        // Register module with array config
apd_register_module_class( $module )         // Register class implementing ModuleInterface
apd_unregister_module( $slug )               // Unregister a module
apd_get_module( $slug )                      // Get module config
apd_get_modules( $args )                     // Get all modules (supports orderby, order, feature)
apd_has_module( $slug )                      // Check if module exists
apd_module_count()                           // Count registered modules
apd_module_requirements_met( $requires )     // Check if requirements are met
apd_get_modules_by_feature( $feature )       // Get modules with specific feature
apd_get_modules_page_url()                   // Get admin page URL
```

**Registration Example:**
```php
// In your module plugin:
add_action( 'apd_modules_init', function() {
    apd_register_module( 'url-directory', [
        'name'        => 'URL Directory',
        'description' => 'Turn your directory into a website/link directory.',
        'version'     => '1.0.0',
        'author'      => 'Your Name',
        'requires'    => [ 'core' => '1.0.0' ],
        'features'    => [ 'link_checker', 'favicon_fetcher' ],
        'icon'        => 'dashicons-admin-links',
    ] );
} );
```

**Hooks:**
- `apd_modules_init` - Action when modules can register (fires at init priority 1)
- `apd_modules_loaded` - Action after all modules loaded
- `apd_module_registered` - Action after a module is registered ($slug, $config)
- `apd_module_unregistered` - Action after a module is unregistered ($slug, $config)
- `apd_register_module_config` - Filter config before registration ($config, $slug)
- `apd_get_module` - Filter module on retrieval ($config, $slug)
- `apd_get_modules` - Filter modules array on retrieval ($modules, $args)

**ModuleInterface:**
```php
interface ModuleInterface {
    public function get_slug(): string;
    public function get_name(): string;
    public function get_description(): string;
    public function get_version(): string;
    public function get_config(): array;
    public function init(): void;
}
```

### Demo Data System

Demo data generator for testing (`src/Admin/DemoData/`):
- **Page Slug:** `apd-demo-data` (under Listings menu)
- **Capability:** `manage_options`
- **Meta Key:** `_apd_demo_data` (marks items as demo data)

**Default Quantities:**
| Type | Default | Notes |
|------|---------|-------|
| Users | 5 | Mixed roles (subscriber, contributor, author) |
| Categories | 21 | Fixed: 6 parent + 15 child with icons/colors |
| Tags | 10 | General purpose tags |
| Listings | 25 | Distributed across categories |
| Reviews | 2-4 per listing | Randomized, weighted toward positive |
| Inquiries | 0-2 per listing | Randomized |
| Favorites | 1-5 per user | Random listings |

**Helper Functions:**
```php
apd_demo_data_page()           // Get DemoDataPage instance
apd_demo_generator()           // Get DemoDataGenerator instance
apd_demo_tracker()             // Get DemoDataTracker instance
apd_get_demo_data_counts()     // Get counts by type
apd_delete_demo_data()         // Delete all demo data
apd_is_demo_data($type, $id)   // Check if item is demo data
apd_has_demo_data()            // Check if any demo data exists
apd_get_demo_data_url()        // Get admin page URL
```

**Hooks:**
- `apd_demo_data_init` - After demo data page initialized
- `apd_before_generate_demo_data` - Before generation starts
- `apd_after_generate_demo_data` - After generation completes (with counts)
- `apd_before_delete_demo_data` - Before deletion starts
- `apd_after_delete_demo_data` - After deletion completes (with counts)
- `apd_demo_default_counts` - Filter default quantity values
- `apd_demo_category_data` - Filter category hierarchy data
- `apd_demo_listing_data` - Filter listing data before creation

### WP-CLI Commands

The plugin registers CLI commands under `wp apd` (requires WP-CLI):

**`wp apd demo generate`** - Generate demo data.

| Option | Default | Description |
|--------|---------|-------------|
| `--types=<types>` | `all` | Comma-separated: users, categories, tags, listings, reviews, inquiries, favorites, all |
| `--users=<count>` | 5 | Number of users (max 20) |
| `--tags=<count>` | 10 | Number of tags (max 10) |
| `--listings=<count>` | 25 | Number of listings (max 100) |

**`wp apd demo delete`** - Delete all demo data.

| Option | Description |
|--------|-------------|
| `--yes` | Skip confirmation prompt |

**`wp apd demo status`** - Show current demo data counts.

| Option | Default | Description |
|--------|---------|-------------|
| `--format=<format>` | `table` | Output format: table, json, csv, yaml |

Module providers are automatically included in all three commands.

### Field System

Field Registry, Renderer, and Validator handle custom listing fields with 25+ field types. See `.claude-bw/docs/claude-fields.md` for full API reference including:
- Field configuration structure and registration functions
- Field Renderer contexts and functions
- Field Validator functions and validation arguments
- Admin Meta Box configuration and save process
- Available field types table with features
- Type-specific config options

### Listing Type Selector

ListingTypeMetaBox (`src/Admin/ListingTypeMetaBox.php`) provides type-aware field management:
- **Meta Box:** Sidebar radio buttons for selecting listing type (only when 2+ types exist)
- **Save Priority:** 20 on `save_post_apd_listing` (after fields at 10, before default at 99)
- **Field Display Filter:** `apd_should_display_field` checks field's `listing_type` and module `hidden_fields`
- **JS Mapping:** Hidden `#apd-field-type-mapping` element with JSON for dynamic field show/hide

**Field Config:**
- `listing_type` key in field config: `null` = all types, `string` = specific type, `array` = multiple types
- Module `hidden_fields` config: array of core field names to hide for that module's listing type

**Key Methods:**
```php
// ListingTypeMetaBox
filter_field_display( bool $display, array $field, string $context, int $listing_id ): bool
build_field_type_mapping(): array
has_multiple_listing_types(): bool
is_field_hidden_by_module( string $field_name, string $listing_type ): bool

// ListingMetaBox (private)
filter_values_by_listing_type( array $values, string $selected_type ): array
is_field_hidden_by_module( string $field_name, string $listing_type ): bool
```

**Admin Columns:** `listing_type` column in admin list table (conditional on 2+ types) with filter links.

**Data Preservation:** Switching types preserves meta data; hidden fields are not deleted, only hidden from display and validation.

### Search & Filter System

Filter Registry and Search Query handle listing search and filtering. See `.claude-bw/docs/claude-search.md` for full API reference including:
- Filter configuration structure
- Registry and renderer functions
- Available filter types (keyword, category, tag, range, date_range)
- Filter URL parameters
- Creating custom filters

### Frontend Display

View System and Template System manage listing display. See `.claude-bw/docs/claude-frontend.md` for full API reference including:
- ViewInterface contract
- GridView/ListView configuration
- Template lookup order and functions
- Creating custom views

### Shortcode System

ShortcodeManager provides WordPress shortcodes for listings, search forms, and categories. See `.claude-bw/docs/claude-shortcodes.md` for full API reference including:
- Available shortcodes: `[apd_listings]`, `[apd_search_form]`, `[apd_categories]`, `[apd_login_form]`, `[apd_register_form]`
- Shortcode attributes and helper functions
- Creating custom shortcodes

### Block System

BlockManager provides Gutenberg blocks for the WordPress block editor. See `.claude-bw/docs/claude-blocks.md` for full API reference including:
- Available blocks: `apd/listings`, `apd/search-form`, `apd/categories`
- Block attributes
- Creating custom blocks

### Dashboard & Profile System

Frontend user dashboard with tabs for listings and profile (`src/Frontend/Dashboard/`). See `.claude-bw/docs/claude-dashboard.md` for full API reference including:
- Dashboard helper functions and stats array
- Profile helper functions and user meta keys
- Hooks for customization

### Frontend Submission System

Frontend listing submission and editing (`src/Frontend/Submission/`). See `.claude-bw/docs/claude-submission.md` for full API reference including:
- SubmissionForm and SubmissionHandler classes
- `[apd_submission_form]` shortcode attributes
- Helper functions for submission and edit mode
- Templates and hooks
- Spam protection (honeypot, time-based, rate limiting, custom checks)

### Accessibility Standards

All UI follows WCAG 2.1 Level AA guidelines. See `.claude-bw/docs/claude-accessibility.md` for required patterns including:
- Keyboard accessibility for tooltips, collapsible sections
- ARIA attributes for forms, icons, dynamic content
- Color contrast requirements
- Translation functions (text domain: `all-purpose-directory`)

## Key Files

- `PLAN.md` - Feature roadmap with architectural decisions and monetization strategy
- `TASKS.md` - Implementation checklist organized by phase (0-17)
- `research/` - Analysis of 30+ competitor directory plugins

## Project Planning Structure

This project uses the Buzzwoo standard folder structure for Claude Code:

### `.claude-bw/`

| Location | Purpose | Owner |
|----------|---------|-------|
| `docs/` | API reference documentation for Claude Code | Dev |
| `planning/*.md` | Implementation plan files (`NM-123-plan.md`) | Dev |
| `planning/specs/` | Project specifications synced from Google/ClickUp Docs | PM |
| `planning/prd/` | PRD supplementary files (architecture, API design) | Dev |
| `planning/questions/` | PM handoff questions generated during new-cycle workflow | Dev → PM |
| `planning/notes/` | Session notes, research, scratch files | Anyone |

### Key Principles

- **Specs** describe WHAT to build (PM-owned, synced with external docs)
- **Plans** contain implementation steps with status tracking
- **PRD files** describe HOW to build (architecture, API design, research)
- **Docs** contain detailed API references for Claude Code
- Never mix user content with `.claude/` (Claude Code native config)
