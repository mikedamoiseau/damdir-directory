# All Purpose Directory - v1.0 Implementation Tasks

## Project Overview

- **Plugin Name:** All Purpose Directory
- **Slug:** `all-purpose-directory`
- **Prefix:** `apd_` / `APD`
- **Min PHP:** 8.0+
- **Min WordPress:** 6.0+
- **Scope:** Core only (no modules)

---

## Phase 0: Development Environment & Testing Infrastructure

### 0.1 Docker Test Environment ✅
- [x] Create test environment folder: `/Users/mike/Documents/www/test/wp-all-purpose-directory/`
- [x] Create `docker-compose.yml` (PHP 8.3-apache + MariaDB 10.11)
  - Use isolated volume name: `apd_db`
  - Port 80 (exclusive use, not shared with other test envs)
- [x] Create `development/docker/vhost.conf`
- [x] Create `development/docker/php/php-ini-overrides.ini`
- [x] Create `development/docker/php/scripts/entrypoint.sh`
  - Simplified version (no extra plugins)
  - Install MariaDB client, Composer, WP-CLI
  - Set up Bedrock WordPress
- [x] Create `README.md` with setup instructions
- [x] Test: `docker-compose up -d` starts successfully

### 0.2 PHPUnit Setup (Plugin) ✅
- [x] Add PHPUnit dependencies to plugin `composer.json`:
  - `phpunit/phpunit: ^9.6`
  - `yoast/phpunit-polyfills: ^2.0`
  - `brain/monkey: ^2.6`
- [x] Create `phpunit.xml.dist` (integration tests config)
- [x] Create `phpunit-unit.xml` (unit tests config)
- [x] Create `bin/install-wp-tests.sh` for WordPress test suite setup
- [x] Add composer scripts:
  - `test:unit` - Run unit tests (fast, no WP)
  - `test:integration` - Run integration tests (requires Docker)
  - `test` - Alias for `test:integration`

### 0.3 Test Bootstrap Files ✅
- [x] Create `tests/bootstrap.php` (integration test bootstrap)
  - Load WordPress test suite
  - Activate plugin
- [x] Create `tests/unit/bootstrap.php` (unit test bootstrap)
  - Initialize Brain Monkey
  - Set up WordPress function mocks

### 0.4 Test Directory Structure ✅
- [x] Create unit test skeleton files (Fields/, Listing/)
- [x] Create integration test skeleton files
- [x] Create test factories (ListingFactory, CategoryFactory, ReviewFactory, UserFactory)
- [x] Create SQL fixture placeholders
- [x] Create E2E test skeleton files with Playwright config
- [x] Create package.json with E2E test scripts

Created the following structure in the plugin:
```
tests/
├── bootstrap.php                 # Integration test bootstrap
├── unit/
│   ├── bootstrap.php             # Unit test bootstrap (Brain Monkey)
│   ├── Fields/
│   │   ├── FieldRegistryTest.php
│   │   ├── FieldValidatorTest.php
│   │   └── Types/
│   │       ├── TextFieldTest.php
│   │       └── ...
│   ├── Listing/
│   │   └── ListingQueryTest.php
│   └── ...
├── integration/
│   ├── PostTypeTest.php
│   ├── TaxonomyTest.php
│   ├── SubmissionTest.php
│   ├── SearchQueryTest.php
│   ├── RestApiTest.php
│   └── ...
├── fixtures/
│   ├── sample-listings.sql       # SQL dump with realistic data
│   ├── sample-categories.sql
│   └── sample-reviews.sql
├── factories/
│   ├── ListingFactory.php        # Generate test listings
│   ├── CategoryFactory.php       # Generate test categories
│   ├── ReviewFactory.php         # Generate test reviews
│   └── UserFactory.php           # Generate test users
└── e2e/
    ├── playwright.config.ts
    ├── submission.spec.ts        # Frontend submission flow
    ├── search-filter.spec.ts     # Search and AJAX filtering
    ├── dashboard.spec.ts         # User dashboard flows
    ├── favorites.spec.ts         # Favorite toggle/list
    └── admin.spec.ts             # Admin listing management
```

### 0.5 Test Factories ✅
- [x] Create `ListingFactory.php`:
  - `create()` - Create and insert listing
  - `make()` - Create listing data array (no insert)
  - Support for overriding fields
  - Auto-generate realistic fake data
- [x] Create `CategoryFactory.php`:
  - Create hierarchical categories
  - With optional meta (icon, color)
- [x] Create `ReviewFactory.php`:
  - Create reviews linked to listings
  - With configurable ratings
- [x] Create `UserFactory.php`:
  - Create users with different roles
  - Subscriber, contributor, author, admin

### 0.6 SQL Fixtures ✅
- [x] Create `fixtures/sample-listings.sql`:
  - 25 listings with varied content
  - Status mix: 19 published, 3 pending, 2 draft, 1 expired
  - Different categories and tags assigned
  - Different authors, custom field values
- [x] Create `fixtures/sample-categories.sql`:
  - 6 parent categories, 15 child categories
  - 10 tags for amenities/features
  - With icons and colors
- [x] Create `fixtures/sample-reviews.sql`:
  - 31 reviews across multiple listings
  - Mix of ratings (1-5 stars)
  - Approved and pending reviews
- [x] Create `fixtures/load-fixtures.sh` script

### 0.7 Playwright E2E Setup ✅
- [x] Create `tests/e2e/playwright.config.ts`:
  - Base URL: `http://localhost`
  - Browser: Chromium (headless by default)
  - Screenshots on failure
- [x] Create `tests/e2e/global-setup.ts`:
  - Login as test user
  - Store auth state
- [x] Create `tests/e2e/fixtures.ts`:
  - Page object models
  - Common test utilities
- [x] Add npm scripts to plugin `package.json`:
  - `test:e2e` - Run all E2E tests
  - `test:e2e:ui` - Run with Playwright UI
  - `test:e2e:headed` - Run in headed mode

### 0.8 E2E Test Specs ✅
- [x] Create `submission.spec.ts`:
  - Guest submission (if enabled)
  - Logged-in submission
  - Form validation errors
  - File upload
  - Success redirect
- [x] Create `search-filter.spec.ts`:
  - Keyword search
  - Category filter
  - AJAX results update
  - URL state persistence
  - Clear filters
- [x] Create `dashboard.spec.ts`:
  - View my listings
  - Edit listing
  - Delete listing
  - View favorites
- [x] Create `favorites.spec.ts`:
  - Add to favorites (logged in)
  - Remove from favorites
  - Favorites page display
- [x] Create `admin.spec.ts`:
  - Create listing in admin
  - Edit listing meta fields
  - Approve pending listing
  - Admin column sorting/filtering

### 0.9 Test Environment Sync Script ✅
- [x] Create `bin/sync-to-test.sh` script:
  - Syncs plugin to Docker test environment
  - Excludes vendor, node_modules, .phpunit.result.cache, etc.
  - Shows helpful activation command after sync
- [x] Document sync workflow in CLAUDE.md (already in Development Commands)

### 0.10 Test Documentation ✅
- [x] Document test environment setup in CLAUDE.md:
  - Docker commands (up, down, exec)
  - How to run unit tests
  - How to run integration tests
  - How to run E2E tests
  - How to load fixtures
- [x] Create `tests/README.md` with:
  - Test philosophy (what to test)
  - Factory usage examples
  - Fixture usage examples
  - Writing new tests guide
  - Troubleshooting section

---

## Phase 1: Project Setup & Architecture

### 1.1 Project Scaffolding ✅
- [x] Create plugin folder structure
- [x] Create main plugin file (`all-purpose-directory.php`)
- [x] Add plugin header with correct metadata
- [x] Create `composer.json` with PSR-4 autoloading
- [x] Create `.gitignore`
- [x] Create `README.txt` (WordPress.org format)

**Folder Structure:**
```
all-purpose-directory/
├── all-purpose-directory.php    # Main plugin file
├── composer.json
├── README.txt
├── uninstall.php
├── assets/
│   ├── css/
│   │   ├── admin.css
│   │   └── frontend.css
│   ├── js/
│   │   ├── admin.js
│   │   └── frontend.js
│   └── images/
├── includes/
│   └── functions.php            # Global helper functions
├── src/
│   ├── Core/
│   │   ├── Plugin.php           # Main plugin class
│   │   ├── Activator.php
│   │   ├── Deactivator.php
│   │   └── Assets.php
│   ├── Admin/
│   │   ├── Admin.php
│   │   ├── Settings.php
│   │   └── ListingMetaBox.php
│   ├── Listing/
│   │   ├── PostType.php
│   │   ├── ListingRepository.php
│   │   └── ListingQuery.php
│   ├── Fields/
│   │   ├── FieldRegistry.php
│   │   ├── FieldRenderer.php
│   │   ├── FieldValidator.php
│   │   └── Types/
│   │       ├── TextField.php
│   │       ├── TextareaField.php
│   │       ├── NumberField.php
│   │       ├── EmailField.php
│   │       ├── UrlField.php
│   │       ├── PhoneField.php
│   │       ├── DateField.php
│   │       ├── SelectField.php
│   │       ├── CheckboxField.php
│   │       ├── RadioField.php
│   │       ├── FileField.php
│   │       ├── ImageField.php
│   │       └── ... (more field types)
│   ├── Taxonomy/
│   │   ├── CategoryTaxonomy.php
│   │   └── TagTaxonomy.php
│   ├── Search/
│   │   ├── SearchQuery.php
│   │   ├── FilterRegistry.php
│   │   └── Filters/
│   ├── Frontend/
│   │   ├── Submission/
│   │   │   ├── SubmissionForm.php
│   │   │   └── SubmissionHandler.php
│   │   ├── Display/
│   │   │   ├── GridView.php
│   │   │   ├── ListView.php
│   │   │   └── SingleListing.php
│   │   └── Dashboard/
│   │       ├── Dashboard.php
│   │       └── MyListings.php
│   ├── User/
│   │   ├── Favorites.php
│   │   └── Profile.php
│   ├── Review/
│   │   ├── ReviewManager.php
│   │   └── RatingCalculator.php
│   ├── Email/
│   │   ├── EmailManager.php
│   │   └── Templates/
│   ├── Api/
│   │   ├── RestController.php
│   │   └── Endpoints/
│   └── Contracts/
│       ├── FieldTypeInterface.php
│       ├── FilterInterface.php
│       └── ViewInterface.php
├── templates/
│   ├── archive-listing.php
│   ├── single-listing.php
│   ├── listing-card.php
│   ├── listing-card-list.php
│   ├── search-form.php
│   ├── submission-form.php
│   ├── dashboard/
│   │   ├── dashboard.php
│   │   ├── my-listings.php
│   │   └── favorites.php
│   └── emails/
│       ├── listing-submitted.php
│       ├── listing-approved.php
│       └── ...
└── languages/
    └── all-purpose-directory.pot
```

### 1.2 Plugin Bootstrap ✅
- [x] Create `Plugin.php` main class with singleton pattern
- [x] Implement activation hook (`Activator.php`)
  - Create database tables (if needed)
  - Set default options
  - Flush rewrite rules
- [x] Implement deactivation hook (`Deactivator.php`)
- [x] Create `uninstall.php` for clean removal
- [x] Set up autoloading via Composer
- [x] Define plugin constants (`APD_VERSION`, `APD_PLUGIN_DIR`, `APD_PLUGIN_URL`)

### 1.3 Hook System Foundation ✅
- [x] Create central hook registration system (`src/Core/Hooks.php`)
- [x] Define core action hooks:
  - `apd_init` - After plugin loads
  - `apd_loaded` - All components ready
  - `apd_before_listing_save`
  - `apd_after_listing_save`
  - `apd_listing_status_changed`
  - `apd_before_submission`
  - `apd_after_submission`
- [x] Define core filter hooks:
  - `apd_listing_fields`
  - `apd_submission_fields`
  - `apd_search_filters`
  - `apd_listing_query_args`
  - `apd_listing_card_data`
  - `apd_email_templates`

### 1.4 Assets Management ✅
- [x] Create `Assets.php` class (`src/Core/Assets.php`)
- [x] Register and enqueue admin CSS/JS
- [x] Register and enqueue frontend CSS/JS
- [x] Implement conditional loading (only on relevant pages)
- [x] Add inline script data via `wp_localize_script`

---

## Phase 2: Listing Custom Post Type

### 2.1 Post Type Registration ✅
- [x] Create `PostType.php` class (`src/Listing/PostType.php`)
- [x] Register `apd_listing` post type with:
  - Labels (singular, plural, menu name)
  - Supports: title, editor, thumbnail, author, excerpt
  - Public, has_archive, rewrite rules (`/listings/`)
  - Show in REST API (`rest_base: apd_listing`)
  - Custom menu icon (`dashicons-location-alt`)
- [x] Add custom capabilities for listings (via `Capabilities::get_listing_caps()`)
- [x] Register post statuses:
  - `publish` (standard)
  - `pending` (awaiting review)
  - `draft` (user draft)
  - `expired` (custom status, excluded from search)

### 2.2 Admin Columns ✅
- [x] Add custom columns to listing admin list:
  - Featured image thumbnail
  - Category
  - Status badge
  - Views count
  - Date
- [x] Make columns sortable
- [ ] Add quick edit fields (if applicable) - Deferred to Phase 3

### 2.3 Admin Filters ✅
- [x] Add dropdown filters to admin list:
  - Filter by category
  - Filter by status
- [x] Implement filter query modifications

---

## Phase 3: Custom Fields Engine

### 3.1 Field Type Interface ✅
- [x] Create `FieldTypeInterface.php` (`src/Contracts/FieldTypeInterface.php`):
  ```php
  interface FieldTypeInterface {
      public function getType(): string;
      public function render(array $field, mixed $value): string;
      public function sanitize(mixed $value): mixed;
      public function validate(mixed $value, array $field): bool|WP_Error;
      public function getDefaultValue(): mixed;
      public function supports(string $feature): bool;
      public function formatValue(mixed $value, array $field): string;
      public function prepareValueForStorage(mixed $value): mixed;
      public function prepareValueFromStorage(mixed $value): mixed;
  }
  ```
- [x] Create `AbstractFieldType.php` base class (`src/Fields/AbstractFieldType.php`):
  - Default implementations for sanitize, validate, formatValue
  - Helper methods: isRequired, isEmpty, getLabel, buildAttributes
  - Validation rules: min_length, max_length, pattern (regex), callback
  - Feature support: searchable, filterable, sortable, repeater
- [x] Create unit tests (`tests/unit/Fields/AbstractFieldTypeTest.php`)
- [x] Add WP_Error mock to unit test bootstrap

### 3.2 Field Registry ✅
- [x] Create `FieldRegistry.php` class
- [x] Implement field registration: `apd_register_field()`
- [x] Implement field retrieval: `apd_get_field()`, `apd_get_fields()`
- [x] Store fields in structured array with:
  - `name` (unique identifier)
  - `type` (text, select, etc.)
  - `label`
  - `description`
  - `required`
  - `default`
  - `placeholder`
  - `options` (for select/radio/checkbox)
  - `validation` (rules)
  - `searchable`
  - `filterable`
  - `admin_only`
  - `priority` (order)
- [x] Additional helper functions:
  - `apd_unregister_field()` - Unregister a field
  - `apd_has_field()` - Check if field exists
  - `apd_register_field_type()` - Register field type handler
  - `apd_get_field_type()` - Get field type handler
  - `apd_get_field_meta_key()` - Get meta key for field
  - `apd_get_listing_field()` - Get listing field value
  - `apd_set_listing_field()` - Set listing field value
- [x] Unit tests for FieldRegistry (`tests/unit/Fields/FieldRegistryTest.php`)

### 3.3 Core Field Types ✅
Implemented 24 field type classes in `src/Fields/Types/`:

- [x] **TextField** - Single line text input (sortable, searchable)
- [x] **TextareaField** - Multi-line text (searchable, nl2br formatting)
- [x] **RichTextField** - WYSIWYG editor (wp_editor, wp_kses_post sanitization)
- [x] **NumberField** - Integer input with min/max/step (filterable, sortable)
- [x] **DecimalField** - Decimal numbers with configurable precision (filterable, sortable)
- [x] **CurrencyField** - Price with currency symbol and position (filterable, sortable)
- [x] **EmailField** - Email with is_email validation, mailto links (searchable, sortable)
- [x] **UrlField** - URL with validation, external link handling (searchable)
- [x] **PhoneField** - Phone number with sanitization, tel links (searchable)
- [x] **DateField** - HTML5 date picker with min/max range (filterable, sortable)
- [x] **TimeField** - HTML5 time picker with validation (sortable)
- [x] **DateTimeField** - Combined datetime-local input (sortable)
- [x] **DateRangeField** - Start/end date pair with JSON storage (filterable)
- [x] **SelectField** - Dropdown select with option validation (filterable)
- [x] **MultiSelectField** - Multiple selection with JSON storage (filterable, repeater)
- [x] **CheckboxField** - Single boolean checkbox (filterable)
- [x] **CheckboxGroupField** - Multiple checkboxes with fieldset/legend (filterable, repeater)
- [x] **RadioField** - Radio button group with accessible fieldset (filterable)
- [x] **SwitchField** - Toggle switch with role="switch" (filterable)
- [x] **FileField** - File upload via media library (attachment ID storage)
- [x] **ImageField** - Image upload with thumbnail preview
- [x] **GalleryField** - Multiple images with drag-drop sorting (repeater)
- [x] **ColorField** - HTML5 color picker with hex validation
- [x] **HiddenField** - Hidden input (non-interactive, always valid)

All field types include comprehensive unit tests (790 tests, 1636 assertions passing).

### 3.4 Field Renderer ✅
- [x] Create `FieldRenderer.php` class
- [x] Implement admin rendering (meta boxes)
- [x] Implement frontend rendering (forms)
- [x] Support field groups/sections
- [x] Add conditional display logic hooks

### 3.5 Field Validator ✅
- [x] Create `FieldValidator.php` class
- [x] Implement validation rules:
  - Required (via field type validators)
  - Min/max length (via AbstractFieldType)
  - Min/max value (via NumberField, DecimalField)
  - Email format (via EmailField)
  - URL format (via UrlField)
  - Phone format (via PhoneField)
  - Regex pattern (via AbstractFieldType)
  - File type/size (via FileField, ImageField)
- [x] Return `WP_Error` on validation failure
- [x] Support custom validation callbacks
- [x] Helper functions: `apd_field_validator()`, `apd_validate_field()`, `apd_validate_fields()`, `apd_sanitize_field()`, `apd_sanitize_fields()`, `apd_process_fields()`
- [x] Hooks: `apd_before_validate_field`, `apd_validate_field`, `apd_after_validate_fields`, `apd_sanitized_fields`

Field Validator includes 64 unit tests covering all validation scenarios.

### 3.6 Field Storage ✅
- [x] Save field values as post meta
- [x] Use prefixed meta keys: `_apd_{field_name}`
- [x] Handle serialization for complex values
- [x] Implement `apd_get_listing_field()` helper
- [x] Implement `apd_set_listing_field()` helper

Field storage is fully implemented via:
- `apd_get_listing_field()` - retrieves values with field type transformations
- `apd_set_listing_field()` - saves values with sanitization and transformations
- `apd_get_field_meta_key()` - returns prefixed meta key
- Complex field types (multiselect, gallery, daterange, checkboxgroup) use JSON encoding

### 3.7 Admin Meta Box ✅
- [x] Create `ListingMetaBox.php` class (`src/Admin/ListingMetaBox.php`)
- [x] Register meta box for listing edit screen (`apd_listing_fields` meta box)
- [x] Render all registered fields (via `apd_render_admin_fields()`)
- [x] Save field values on post save (via `apd_process_fields()` and `apd_set_listing_field()`)
- [x] Add nonce verification (`apd_save_listing_fields` action, `apd_fields_nonce` field)
- [x] Handle autosave (skips saving during autosave)
- [x] Fire hooks: `apd_before_listing_save`, `apd_after_listing_save`
- [x] Store validation errors in transient for display after redirect
- [x] Display field errors via `admin_notices` hook on post-save redirect
- [x] Unit tests (21 tests, 46 assertions)

---

## Phase 4: Taxonomies ✅

### 4.1 Category Taxonomy ✅
- [x] Create `CategoryTaxonomy.php` class
- [x] Register `apd_category` taxonomy:
  - Hierarchical (like categories)
  - Public, show in REST
  - Custom labels
  - Rewrite rules (`/listing-category/`)
- [x] Add category meta fields:
  - Icon (dashicon selector)
  - Color (hex color picker)
  - Description (built-in)
- [x] Create category admin UI enhancements:
  - Custom admin columns (icon, color)
  - Add/edit form fields for meta
  - Color picker integration

### 4.2 Tag Taxonomy ✅
- [x] Create `TagTaxonomy.php` class
- [x] Register `apd_tag` taxonomy:
  - Non-hierarchical (like tags)
  - Public, show in REST
  - Custom labels
  - Rewrite rules (`/listing-tag/`)

### 4.3 Taxonomy Helpers ✅
- [x] `apd_get_listing_categories($listing_id)`
- [x] `apd_get_listing_tags($listing_id)`
- [x] `apd_get_category_listings($category_id, $args)`
- [x] `apd_get_categories_with_count($args)`
- [x] `apd_get_category_icon($category)` - bonus helper
- [x] `apd_get_category_color($category)` - bonus helper

---

## Phase 5: Search & Filtering ✅

### 5.1 Search Query ✅
- [x] Create `SearchQuery.php` class (`src/Search/SearchQuery.php`)
- [x] Hook into `pre_get_posts` for archive/search
- [x] Implement keyword search across:
  - Title
  - Content
  - Custom fields (marked searchable)
- [x] Add orderby options:
  - Date (newest/oldest)
  - Title (A-Z, Z-A)
  - Views
  - Random

### 5.2 Filter Registry ✅
- [x] Create `FilterRegistry.php` class (`src/Search/FilterRegistry.php`)
- [x] Implement `apd_register_filter()`
- [x] Define filter structure:
  - `name`
  - `type` (select, checkbox, range, etc.)
  - `label`
  - `options` or `source`
  - `query_callback` (how to modify WP_Query)

### 5.3 Core Filters ✅
- [x] **Category Filter** - Hierarchical dropdown (`src/Search/Filters/CategoryFilter.php`)
- [x] **Tag Filter** - Checkbox list (`src/Search/Filters/TagFilter.php`)
- [x] **Keyword Filter** - Text search (`src/Search/Filters/KeywordFilter.php`)
- [x] **Range Filter** - Min/max number inputs (`src/Search/Filters/RangeFilter.php`)
- [x] **Date Range Filter** - Start/end date inputs (`src/Search/Filters/DateRangeFilter.php`)

### 5.4 Filter UI ✅
- [x] Create search form template (`templates/search/search-form.php`)
- [x] Render filters based on registry (`src/Search/FilterRenderer.php`)
- [x] Handle filter state in URL parameters
- [x] Create theme-overridable templates:
  - `filter-select.php`
  - `filter-checkbox.php`
  - `filter-range.php`
  - `filter-date-range.php`
  - `active-filters.php`
  - `no-results.php`

### 5.5 AJAX Filtering ✅
- [x] Create AJAX endpoint for filtering (`wp_ajax_apd_filter_listings`)
- [x] Return filtered listings HTML + metadata
- [x] Update results without page reload
- [x] Update URL with filter state (History API)
- [x] Show loading state
- [x] Frontend JavaScript module (`assets/js/frontend.js`)
- [x] Frontend CSS styles (`assets/css/frontend.css`)

### 5.6 Unit Tests ✅
- [x] FilterRegistry tests (`tests/unit/Search/FilterRegistryTest.php`)
- [x] KeywordFilter tests (`tests/unit/Search/Filters/KeywordFilterTest.php`)
- [x] CategoryFilter tests (`tests/unit/Search/Filters/CategoryFilterTest.php`)
- [x] TagFilter tests (`tests/unit/Search/Filters/TagFilterTest.php`)
- [x] RangeFilter tests (`tests/unit/Search/Filters/RangeFilterTest.php`)
- [x] DateRangeFilter tests (`tests/unit/Search/Filters/DateRangeFilterTest.php`)

**Total: 114 tests for Search & Filtering, 967 total unit tests passing**

---

## Phase 6: Display System

### 6.1 Template System ✅
- [x] Create template loader utility (`src/Core/Template.php`)
- [x] Support theme template overrides:
  - Theme can override in `all-purpose-directory/` folder
  - Child theme templates take priority over parent theme
  - Template cache for performance
- [x] Implement `apd_get_template_part()` - works like WP's get_template_part()
- [x] Pass data to templates via `apd_get_template()` - args extracted as variables
- [x] Additional template functions:
  - `apd_template()` - get Template instance
  - `apd_get_template_html()` - return template as string
  - `apd_get_template_part_html()` - return template part as string
  - `apd_template_exists()` - check if template exists
  - `apd_is_template_overridden()` - check if theme overrides template
  - `apd_get_plugin_template_path()` - get plugin templates path
  - `apd_get_theme_template_dir()` - get theme template folder name
- [x] Unit tests (26 tests, 26 assertions)

### 6.2 Archive Template ✅
- [x] Create `archive-listing.php` template
- [x] Include search/filter form
- [x] Display listings in selected view
- [x] Add pagination
- [x] Support view switching (grid/list)
- [x] Create `TemplateLoader.php` class for WordPress template hierarchy integration
- [x] Create `listing-card.php` (grid view) template
- [x] Create `listing-card-list.php` (list view) template
- [x] Add helper functions: `apd_template_loader()`, `apd_get_current_view()`, `apd_get_grid_columns()`, `apd_get_view_url()`, `apd_render_view_switcher()`, `apd_render_results_count()`, `apd_render_pagination()`, `apd_get_archive_title()`, `apd_get_archive_description()`
- [x] Unit tests (37 tests)

### 6.3 Single Listing Template ✅
- [x] Create `single-listing.php` template
- [x] Display all listing data:
  - Title, featured image
  - Description/content
  - Custom fields (via `apd_render_display_fields()`)
  - Categories with icons/colors
  - Tags
  - Author info with avatar and listing count
  - Reviews section (hook: `apd_single_listing_reviews`)
  - Contact form (hook: `apd_single_listing_contact_form`)
- [x] Support template sections via hooks (20+ action hooks)
- [x] Add related listings (`apd_get_related_listings()`)
- [x] View count tracking (bot filtering, admin skip option)
- [x] Helper functions: `apd_get_related_listings()`, `apd_get_listing_views()`, `apd_increment_listing_views()`
- [x] CSS styles for single listing layout (sidebar, fields, tags, related)
- [x] Unit tests (7 tests for view tracking)

### 6.4 Grid View ✅
- [x] Create `GridView.php` class
- [x] Create `listing-card.php` template
- [x] Configurable columns (2, 3, 4)
- [x] Card content:
  - Thumbnail
  - Title
  - Excerpt/short description
  - Category badge
  - Price (if applicable)
  - Rating stars
  - Favorite button
- [x] Responsive design
- [x] Unit tests (36 tests for GridView)

### 6.5 List View ✅
- [x] Create `ListView.php` class
- [x] Create `listing-card-list.php` template
- [x] Horizontal card layout
- [x] More details visible than grid
- [x] Unit tests (32 tests for ListView)
- [x] ViewRegistry with 20 unit tests
- [x] ViewInterface contract and AbstractView base class
- [x] Helper functions in includes/functions.php

### 6.6 Shortcodes ✅
- [x] `[apd_listings]` - Display listings
  - Attributes: view, columns, count, category, tag, orderby, order, ids, exclude, author, show_image, show_excerpt, show_pagination, class
- [x] `[apd_search_form]` - Search form
  - Attributes: filters, show_keyword, show_category, show_tag, show_submit, layout, action, class
- [x] `[apd_categories]` - Category list/grid
  - Attributes: layout, columns, count, parent, include, exclude, hide_empty, orderby, show_count, show_icon, show_description, class
- [x] `[apd_submission_form]` - Frontend submission (placeholder - Phase 7)
- [x] `[apd_dashboard]` - User dashboard (placeholder - Phase 8)
- [x] `[apd_favorites]` - User favorites (placeholder - Phase 9)
- [x] `[apd_login_form]` - Login form
  - Attributes: redirect, show_remember, show_register, show_lost_password, label_username, label_password, class
- [x] `[apd_register_form]` - Registration form
  - Attributes: redirect, show_login, class
- [x] ShortcodeManager with registration/unregistration
- [x] AbstractShortcode base class with attribute parsing/sanitization
- [x] Unit tests (85 tests for shortcode system)
- [x] CSS styles for shortcodes

### 6.7 Gutenberg Blocks ✅
- [x] Create block registration system
- [x] **Listings Block** - Display listings with preview
- [x] **Search Form Block** - Search/filter form
- [x] **Categories Block** - Category display
- [x] Block settings panels in editor
- [x] Live preview in editor

---

## Phase 7: Frontend Submission

### 7.1 Submission Form ✅
- [x] Create `SubmissionForm.php` class
- [x] Render form with all applicable fields
- [x] Group fields into sections
- [x] Add category/tag selection
- [x] Add featured image upload
- [x] Add terms acceptance checkbox
- [x] Form validation (client-side)
- [x] `[apd_submission_form]` shortcode with attributes
- [x] Templates: submission-form.php, category-selector.php, tag-selector.php, image-upload.php
- [x] Helper functions: `apd_submission_form()`, `apd_render_submission_form()`, `apd_get_submission_fields()`, `apd_set_submission_errors()`, `apd_set_submission_values()`
- [x] Unit tests (49 tests for SubmissionForm and shortcode)

### 7.2 Submission Handler ✅
- [x] Create `SubmissionHandler.php` class
- [x] Hook into form submission via init hook
- [x] Verify nonce (`apd_submit_listing` / `apd_submission_nonce`)
- [x] Check user permissions (configurable login requirement)
- [x] Server-side validation using FieldValidator
- [x] Sanitize all input data
- [x] Create listing post (`wp_insert_post`)
- [x] Set post status (configurable, default: 'pending')
- [x] Assign categories and tags (`wp_set_object_terms`)
- [x] Handle file uploads (`media_handle_upload`, `set_post_thumbnail`)
- [x] Save custom field values (`apd_set_listing_field`)
- [x] Fire `apd_before_submission` and `apd_after_submission` hooks
- [x] Send admin notification email (basic)
- [x] Redirect after success with success state
- [x] Display success template (submission-success.php)
- [x] Store errors via transients for form re-display
- [x] Helper functions: `apd_submission_handler()`, `apd_process_submission()`, `apd_get_default_listing_status()`, `apd_is_submission_success()`, `apd_get_submitted_listing_id()`, `apd_render_submission_success()`
- [x] Unit tests (17 tests for SubmissionHandler)

### 7.3 Edit Listing ✅
- [x] Allow listing owners to edit their listings
- [x] Pre-populate form with existing data (title, content, excerpt, categories, tags, featured image, custom fields)
- [x] Update listing on save (`wp_update_post`)
- [x] Handle status transitions (filterable via `apd_edit_listing_status`)
- [x] URL structure: `/submit-listing/?edit_listing=123`
- [x] Shortcode attribute: `[apd_submission_form listing_id="123"]`
- [x] Permission verification (post author or `edit_others_apd_listings` capability)
- [x] Template: edit-not-allowed.php for permission denied
- [x] Helper functions: `apd_user_can_edit_listing()`, `apd_get_edit_listing_url()`, `apd_is_edit_mode()`, `apd_get_edit_listing_id()`
- [x] Hooks: `apd_before_listing_update`, `apd_edit_listing_status`, `apd_user_can_edit_listing`
- [x] Unit tests (16 tests for edit mode)

### 7.4 Spam Protection ✅
- [x] Honeypot field (CSS-hidden, constant-time comparison)
- [x] Nonce verification (already in handler, verified working)
- [x] Time-based protection (minimum 3 seconds to submit)
- [x] Rate limiting (5 submissions/hour per user or IP)
- [x] Optional reCAPTCHA integration hook (`apd_submission_spam_check` filter)
- [x] Spam attempt logging (`apd_spam_attempt_detected` action)
- [x] Bypass filter for trusted users (`apd_bypass_spam_protection`)
- [x] Configuration filters: `apd_honeypot_field_name`, `apd_submission_min_time`, `apd_submission_rate_limit`, `apd_submission_rate_period`
- [x] Helper functions: `apd_check_submission_rate_limit()`, `apd_get_submission_rate_limit()`, `apd_is_submission_spam()`, `apd_get_client_ip()`, plus 7 more
- [x] Unit tests (22 tests for spam protection)

---

## Phase 8: User Dashboard

### 8.1 Dashboard Page ✅
- [x] Create `Dashboard.php` class
- [x] Register dashboard page/endpoint
- [x] Dashboard layout with navigation:
  - My Listings
  - Add New Listing
  - Favorites
  - Profile
- [x] Stats overview (total listings, views)

### 8.2 My Listings ✅
- [x] Create `MyListings.php` class
- [x] List user's own listings
- [x] Show status, views, date
- [x] Actions: Edit, Delete, Mark as Sold
- [x] Pagination
- [x] Filter by status

### 8.3 Profile Settings ✅
- [x] Display name, email
- [x] Avatar/photo upload
- [x] Bio/description
- [x] Contact information
- [x] Social links
- [x] Save profile changes

---

## Phase 9: Favorites System

### 9.1 Favorites Manager ✅
- [x] Create `src/User/Favorites.php` class (singleton pattern)
- [x] Store favorites in user meta (`_apd_favorites`)
- [x] For guests: cookie storage with 30-day expiry (`apd_guest_favorites`)
- [x] Store listing favorite counts in post meta (`_apd_favorite_count`)
- [x] Methods implemented:
  - `add($listing_id, $user_id)` - Add to favorites
  - `remove($listing_id, $user_id)` - Remove from favorites
  - `toggle($listing_id, $user_id)` - Toggle and return new state
  - `is_favorite($listing_id, $user_id)` - Check if favorited
  - `get_favorites($user_id)` - Get all favorite IDs
  - `get_count($user_id)` - Get user's favorite count
  - `get_listing_favorite_count($listing_id)` - Get listing's total favorites
  - `clear($user_id)` - Clear all user favorites
  - `recalculate_listing_count($listing_id)` - Recalculate listing count
  - `merge_guest_favorites_on_login()` - Merge guest favorites on login
  - `requires_login()` / `guest_favorites_enabled()` - Login requirement checks
- [x] Helper functions in `includes/functions.php`:
  - `apd_favorites()` - Get Favorites instance
  - `apd_add_favorite()`, `apd_remove_favorite()`, `apd_toggle_favorite()`
  - `apd_is_favorite()`, `apd_get_user_favorites()`, `apd_get_favorites_count()`
  - `apd_get_listing_favorites_count()`, `apd_favorites_require_login()`
  - `apd_clear_favorites()`, `apd_get_favorite_listings()`
- [x] Hooks:
  - `apd_favorite_added` - Action when favorite added
  - `apd_favorite_removed` - Action when favorite removed
  - `apd_favorites_cleared` - Action when all favorites cleared
  - `apd_favorites_require_login` - Filter login requirement
  - `apd_guest_favorites_enabled` - Filter to enable guest favorites
- [x] Listing validation (post type, status, ownership)
- [x] Initialize in Plugin.php
- [x] Unit tests: `tests/unit/User/FavoritesTest.php` (38 tests, 47 assertions)

### 9.2 Favorite Toggle ✅
- [x] Create `src/User/FavoriteToggle.php` class
- [x] Heart icon button on listing cards (image overlay + footer fallback)
- [x] SVG heart icons (filled/outline) for visual feedback
- [x] AJAX add/remove with nonce verification
- [x] Update UI state immediately (optimistic updates)
- [x] Show login prompt for guests (when required)
- [x] Configurable button sizes (small, medium, large)
- [x] Optional favorite count display
- [x] Button on single listing pages
- [x] Localized i18n strings for JavaScript
- [x] Helper functions: `apd_favorite_toggle()`, `apd_render_favorite_button()`, `apd_get_favorite_button()`
- [x] Unit tests: `tests/unit/User/FavoriteToggleTest.php` (28 tests, 35 assertions)

### 9.3 Favorites Page ✅
- [x] Create `src/Frontend/Dashboard/FavoritesPage.php` class
- [x] Dashboard tab showing user's favorited listings
- [x] Grid/list view toggle with preference saved to user meta
- [x] Pagination (12 per page by default)
- [x] Uses existing listing card templates with working favorite buttons
- [x] Empty state message with link to browse listings
- [x] Templates: `templates/dashboard/favorites.php`, `templates/dashboard/favorites-empty.php`
- [x] CSS styles for favorites page layout, view toggle, empty state
- [x] Helper functions: `apd_favorites_page()`, `apd_render_favorites_page()`
- [x] Dashboard tab count badge shows actual favorites count
- [x] Unit tests: `tests/unit/Frontend/Dashboard/FavoritesPageTest.php` (26 tests, 42 assertions)

---

## Phase 10: Reviews & Ratings

### 10.1 Review Manager ✅
- [x] Create `ReviewManager.php` class
- [x] Store reviews as comments with meta
- [x] Review data:
  - Rating (1-5 stars)
  - Title
  - Content
  - Author
  - Date
  - Status (pending/approved)

### 10.2 Rating Calculator ✅
- [x] Create `RatingCalculator.php` class
- [x] Calculate average rating per listing
- [x] Store in post meta for performance
- [x] Update on review add/edit/delete
- [x] Method: `apd_get_listing_rating($listing_id)`

### 10.3 Review Form ✅
- [x] Star rating input component
- [x] Review title and content
- [x] Submit review via AJAX
- [x] One review per user per listing
- [x] Edit own review
- [x] Create `ReviewForm.php` class
- [x] Create `ReviewHandler.php` class for form processing
- [x] Create `templates/review/review-form.php`
- [x] Create `templates/review/star-input.php`
- [x] Add interactive star rating JavaScript
- [x] Add review form CSS styles
- [x] Add helper functions:
  - `apd_review_form()` - Get ReviewForm instance
  - `apd_render_review_form($listing_id)` - Render the form
  - `apd_review_handler()` - Get ReviewHandler instance
  - `apd_current_user_has_reviewed($listing_id)` - Check if current user reviewed
  - `apd_get_current_user_review($listing_id)` - Get current user's review
  - `apd_update_review($review_id, $data)` - Update an existing review
- [x] Write unit tests (17 new tests for ReviewForm and ReviewHandler)

### 10.4 Review Display ✅
- [x] Reviews list on single listing
- [x] Star rating display component
- [x] Review author, date, content
- [x] Pagination for reviews
- [x] Average rating summary

### 10.5 Review Moderation ✅
- [x] Admin approval workflow (`src/Admin/ReviewModeration.php`)
- [x] Pending reviews notification (count badge on menu)
- [x] Approve/reject/spam/trash actions (row and bulk)
- [x] Status filtering (All, Pending, Approved, Spam, Trash)
- [x] Listing/rating filters and search
- [x] View listing link in row actions
- [x] Unit tests (25 tests in `tests/unit/Admin/ReviewModerationTest.php`)

---

## Phase 11: Contact & Inquiries

### 11.1 Contact Form ✅
- [x] Simple contact form on listings
- [x] Fields: Name, Email, Phone (optional), Message
- [x] Send email to listing owner
- [x] Copy to admin (optional)
- [x] Success/error messages

### 11.2 Inquiry Tracking ✅
- [x] Log inquiries in database (optional)
- [x] Show inquiry count to listing owner
- [x] Inquiry history in dashboard

---

## Phase 12: Email Notifications ✅

### 12.1 Email Manager ✅
- [x] Create `EmailManager.php` class (singleton with config)
- [x] HTML email template wrapper (`templates/emails/email-wrapper.php`)
- [x] Placeholder replacement system (context-based + registered callbacks)
- [x] Methods for each notification type
- [x] Helper functions in `includes/functions.php`
- [x] Initialize in Plugin.php
- [x] Unit tests (54 tests)

### 12.2 Notification Types ✅
- [x] **New Listing Submitted** (to admin) - `send_listing_submitted()`
- [x] **Listing Approved** (to author) - `send_listing_approved()`
- [x] **Listing Rejected** (to author) - `send_listing_rejected()` with optional reason
- [x] **Listing Expiring Soon** (to author) - `send_listing_expiring()` with days_left
- [x] **Listing Expired** (to author) - `send_listing_expired()`
- [x] **New Review** (to listing author) - `send_new_review()`
- [x] **New Inquiry** (to listing author) - `send_new_inquiry()`

### 12.3 Email Templates ✅
- [x] Create template files for each type (7 templates + wrapper)
- [x] Support theme overrides (`all-purpose-directory/emails/`)
- [x] Dynamic placeholders:
  - `{site_name}`, `{site_url}`, `{admin_email}`, `{current_date}`, `{current_time}`
  - `{listing_id}`, `{listing_title}`, `{listing_url}`, `{listing_edit_url}`, `{listing_status}`
  - `{author_name}`, `{author_email}`, `{author_id}`, `{admin_url}`
  - `{user_id}`, `{user_name}`, `{user_email}`, `{user_login}`, `{user_first_name}`, `{user_last_name}`
  - `{review_id}`, `{review_author}`, `{review_email}`, `{review_content}`, `{review_rating}`, `{review_title}`, `{review_date}`
  - `{inquiry_name}`, `{inquiry_email}`, `{inquiry_phone}`, `{inquiry_message}`
  - `{days_left}`, `{rejection_reason}` (context-specific)
- [x] Color customization filters (`apd_email_header_color`, `apd_email_button_color`)

### 12.4 Email Settings ✅
- [x] Enable/disable each notification (`is_notification_enabled()`, `set_notification_enabled()`)
- [x] Custom from name/email (config + filters)
- [x] Admin notification email(s) (config + `apd_email_admin_email` filter)

---

## Phase 13: Admin Settings ✅

### 13.1 Settings Page ✅
- [x] Create `Settings.php` class (singleton with WordPress Settings API)
- [x] Register settings page under APD menu (`edit.php?post_type=apd_listing`)
- [x] Use tabbed interface:
  - General (currency, date format, distance units)
  - Listings (per page, default status, expiration, features)
  - Submission (who can submit, guest submission, terms, redirect)
  - Display (default view, grid columns, card elements, layouts)
  - Email (from name/email, admin email, notification toggles)
  - Advanced (delete data, custom CSS, debug mode)
- [x] CSS styling for settings page (`assets/css/admin-settings.css`)
- [x] JavaScript for conditional fields (`assets/js/admin-settings.js`)
- [x] Helper functions in `includes/functions.php`
- [x] Unit tests (43 tests, 159 assertions)

### 13.2 General Settings ✅
- [x] Currency symbol & position
- [x] Date format
- [x] Distance units (km/miles)

### 13.3 Listing Settings ✅
- [x] Listings per page
- [x] Default listing status (pending/publish/draft)
- [x] Listing expiration (days, 0 = never)
- [x] Enable/disable features:
  - Reviews
  - Favorites
  - Contact form

### 13.4 Submission Settings ✅
- [x] Who can submit (anyone, logged-in, specific roles)
- [x] Guest submission (with email)
- [x] Terms & conditions page selection
- [x] Redirect after submission (listing, dashboard, custom)

### 13.5 Display Settings ✅
- [x] Default view (grid/list)
- [x] Grid columns (2, 3, 4)
- [x] Card elements to show/hide (thumbnail, excerpt, category, rating, favorite)
- [x] Archive page title (custom)
- [x] Single listing layout (full/sidebar)

### 13.6 Email Settings ✅
- [x] From name
- [x] From email
- [x] Admin email(s)
- [x] Toggle each notification (submission, approved, rejected, expiring, review, inquiry)

### 13.7 Advanced Settings ✅
- [x] Delete data on uninstall
- [x] Custom CSS field
- [x] Debug mode

---

## Phase 14: REST API

### 14.1 API Controller ✅
- [x] Create `RestController.php` class (`src/Api/RestController.php`)
- [x] Register REST namespace: `apd/v1`
- [x] Authentication via WordPress nonces/cookies
- [x] Permission callbacks (public, authenticated, create_listing, edit_listing, delete_listing, admin, manage_listings)
- [x] Endpoint registration system (register, unregister, get, has)
- [x] Response helpers (create_response, create_error, create_paginated_response)
- [x] Helper functions in `includes/functions.php`
- [x] Unit tests (55 tests, 85 assertions)

### 14.2 Endpoints ✅
- [x] **ListingsEndpoint** (`src/Api/Endpoints/ListingsEndpoint.php`)
  - [x] `GET /listings` - List listings with filters (category, tag, author, status, search)
  - [x] `GET /listings/{id}` - Single listing with categories, tags, meta
  - [x] `POST /listings` - Create listing (authenticated)
  - [x] `PUT /listings/{id}` - Update listing (owner/admin)
  - [x] `DELETE /listings/{id}` - Delete listing (owner/admin)
- [x] **TaxonomiesEndpoint** (`src/Api/Endpoints/TaxonomiesEndpoint.php`)
  - [x] `GET /categories` - List categories with icon/color meta
  - [x] `GET /categories/{id}` - Single category
  - [x] `GET /tags` - List tags
  - [x] `GET /tags/{id}` - Single tag
- [x] **FavoritesEndpoint** (`src/Api/Endpoints/FavoritesEndpoint.php`)
  - [x] `GET /favorites` - User's favorite IDs
  - [x] `GET /favorites/listings` - User's favorite listings with full data
  - [x] `POST /favorites` - Add favorite
  - [x] `DELETE /favorites/{id}` - Remove favorite
  - [x] `POST /favorites/toggle/{id}` - Toggle favorite
- [x] **ReviewsEndpoint** (`src/Api/Endpoints/ReviewsEndpoint.php`)
  - [x] `GET /reviews` - List reviews with filters
  - [x] `GET /reviews/{id}` - Single review
  - [x] `POST /reviews` - Create review (authenticated)
  - [x] `PUT /reviews/{id}` - Update review (author/admin)
  - [x] `DELETE /reviews/{id}` - Delete review (author/admin)
  - [x] `GET /listings/{id}/reviews` - Listing reviews with rating summary
- [x] **InquiriesEndpoint** (`src/Api/Endpoints/InquiriesEndpoint.php`)
  - [x] `GET /inquiries` - User's inquiries
  - [x] `GET /inquiries/{id}` - Single inquiry
  - [x] `DELETE /inquiries/{id}` - Delete inquiry
  - [x] `POST /inquiries/{id}/read` - Mark as read
  - [x] `POST /inquiries/{id}/unread` - Mark as unread
  - [x] `GET /listings/{id}/inquiries` - Listing inquiries (owner/admin)
- [x] Endpoint registration in `Plugin::register_rest_endpoints()`
- [x] Unit tests (151 endpoint tests: 43 Listings + 24 Taxonomies + 27 Favorites + 30 Reviews + 27 Inquiries)

### 14.3 Response Format ✅
- [x] Consistent JSON structure (via RestController helpers)
- [x] Pagination metadata (items, total, page, per_page, total_pages)
- [x] Error responses with codes (via create_error helper)

---

## Phase 15: Internationalization

### 15.1 Text Domain ✅
- [x] Load text domain: `all-purpose-directory`
- [x] Wrap all strings with `__()` or `_e()`
- [x] Use context with `_x()` where needed
- [x] Added translator comments for all placeholder strings
- [x] Fixed multi-line string concatenation in EmailManager

### 15.2 Translation Files ✅
- [x] Generate POT file (`languages/all-purpose-directory.pot`)
  - 938 translatable strings
  - 122 translator comments
  - 18 plural forms
  - Context strings (msgctxt) for disambiguation
- [x] Include in `languages/` folder
- [x] Document translation process (`languages/TRANSLATING.md`)
- [x] Add npm script: `npm run i18n:pot`
- [x] Unit tests (25 tests in `tests/unit/Core/I18nTest.php`)

---

## Phase 16: Security & Performance

### 16.1 Security ✅
- [x] Nonce verification on all forms
  - ContactHandler, SubmissionHandler, ReviewHandler, Profile, FavoriteToggle, ListingMetaBox
  - Nonce constants: `NONCE_ACTION`, `NONCE_NAME` defined in each handler
  - Unit tests: `tests/unit/Security/NonceVerificationTest.php` (16 tests)
- [x] Capability checks on all actions
  - REST API: `permission_public`, `permission_authenticated`, `permission_create_listing`, `permission_edit_listing`, `permission_delete_listing`, `permission_admin`, `permission_manage_listings`
  - Core/Capabilities class defines listing capabilities
  - Unit tests: `tests/unit/Security/CapabilityCheckTest.php` (19 tests)
- [x] Data sanitization (input)
  - `sanitize_text_field()`, `sanitize_email()`, `sanitize_textarea_field()`, `absint()`, `sanitize_key()`
  - ContactHandler::get_sanitized_data() demonstrates pattern
  - Unit tests: `tests/unit/Security/InputSanitizationTest.php` (21 tests)
- [x] Data escaping (output)
  - `esc_html()`, `esc_attr()`, `esc_url()`, `esc_js()`, `wp_kses_post()`
  - Used consistently in templates and output
  - Unit tests: `tests/unit/Security/OutputEscapingTest.php` (24 tests)
- [x] SQL injection prevention (prepared statements)
  - WP_Query used for listings (inherently safe)
  - wpdb->prepare() used for direct queries (SearchQuery, Dashboard)
  - Unit tests: `tests/unit/Security/SqlInjectionTest.php` (15 tests)
- [x] XSS prevention
  - Output escaping with esc_* functions
  - wp_kses_post for rich content
  - Covered in OutputEscapingTest
- [x] CSRF protection
  - Nonce verification on all forms
  - Covered in NonceVerificationTest
- [x] File upload validation
  - SubmissionHandler: ALLOWED_IMAGE_TYPES, MAX_FILE_SIZE constants
  - wp_check_filetype() for MIME validation
  - media_handle_upload() for secure processing
  - Unit tests: `tests/unit/Security/FileUploadSecurityTest.php` (15 tests)

**Total Security Tests: 110 tests, 305 assertions**

### 16.2 Performance ✅
- [x] Efficient database queries
  - `no_found_rows` optimization in RatingCalculator, ReviewModeration, FavoritesEndpoint, Dashboard, Performance class
  - `fields => 'ids'` where full objects not needed
  - Prepared statements for all SQL
- [x] Use transients for expensive operations
  - Created `Performance` class (`src/Core/Performance.php`)
  - Transient + object cache (wp_cache) dual-layer caching
  - Cached operations: categories, related listings, dashboard stats, popular listings
  - Automatic cache invalidation on listing/category changes
  - Helper functions: `apd_cache_remember()`, `apd_cache_get()`, `apd_cache_set()`, `apd_cache_delete()`, `apd_cache_clear_all()`
  - Specialized: `apd_get_cached_categories()`, `apd_get_cached_related_listings()`, `apd_get_cached_dashboard_stats()`, `apd_get_popular_listings()`
- [x] Lazy load images
  - All listing card thumbnails use `loading="lazy"` + `decoding="async"`
  - Dashboard listing row thumbnails use lazy loading
  - Review avatars use lazy loading with explicit dimensions
  - Single listing main image NOT lazy loaded (above the fold)
- [x] Conditional asset loading
  - Frontend assets only on: listing archive, single listing, category/tag taxonomies
  - Admin assets only on plugin-related screens
  - Extensible via `apd_should_load_frontend_assets` filter
  - Scripts loaded in footer for performance
  - Version-based cache busting
- [x] Minify CSS/JS for production
  - Note: Minification handled by WordPress.org build process or build tools
  - Plugin uses unminified source with proper version cache busting
- [x] Database indexes on custom tables
  - Plugin uses WordPress post meta (no custom tables)
  - Meta queries optimized with appropriate patterns

**Total Performance Tests: 41 tests (17 Performance + 10 Query + 6 Image + 8 Asset)**

---

## Phase 17: Testing & Documentation

### 17.1 Testing ✅
- [x] Manual testing checklist - `docs/TESTING-CHECKLIST.md` (20 sections, 200+ checkpoints)
- [x] Test on PHP 8.0, 8.1, 8.2, 8.3 - Automated via GitHub Actions CI
  - PHP compatibility tests: `tests/unit/Compatibility/PhpVersionTest.php` (21 tests)
  - CI workflow: `.github/workflows/tests.yml` (unit tests, syntax check, compatibility)
- [ ] Test on WP 6.0, 6.4, latest - Manual testing required
- [ ] Test popular theme compatibility - Manual testing required
- [ ] Test with common plugins (Yoast, WooCommerce) - Manual testing required

### 17.2 Documentation ✅
- [x] Inline code documentation (PHPDoc) - All classes have PHPDoc headers
- [x] README.txt for WordPress.org - Complete with all sections, FAQ, privacy policy
- [x] User documentation (how to use) - `docs/USER-GUIDE.md` with full reference
- [x] Developer documentation (hooks, filters) - `docs/DEVELOPER.md` with 100+ hooks
- [x] Changelog - `CHANGELOG.md` following Keep a Changelog format
- [x] Documentation tests (29 tests, 130 assertions)

### 17.3 Developer Hooks Documentation ✅
Comprehensive hooks documentation in `docs/DEVELOPER.md` covering:
- 50+ action hooks with parameters and examples
- 50+ filter hooks with parameters and examples
- Code examples for extending the plugin
- Custom field types, filters, views documentation
- REST API documentation
- Database schema reference
- Coding standards guide

### 17.4 Demo Data Generator ✅
- [x] `DemoDataTracker` - Mark and track demo data items with `_apd_demo_data` meta
- [x] `DemoDataGenerator` - Generate users, categories, tags, listings, reviews, inquiries, favorites
- [x] `DemoDataPage` - Admin UI at Listings → Demo Data with AJAX generation/deletion
- [x] DataSets - CategoryData, TagData, BusinessNames, Addresses, ReviewContent
- [x] Helper functions - `apd_demo_generator()`, `apd_demo_tracker()`, `apd_delete_demo_data()`, etc.
- [x] Admin assets - `admin-demo-data.css`, `admin-demo-data.js`

**Files Created:**
- `src/Admin/DemoData/DemoDataPage.php`
- `src/Admin/DemoData/DemoDataGenerator.php`
- `src/Admin/DemoData/DemoDataTracker.php`
- `src/Admin/DemoData/DataSets/CategoryData.php`
- `src/Admin/DemoData/DataSets/TagData.php`
- `src/Admin/DemoData/DataSets/BusinessNames.php`
- `src/Admin/DemoData/DataSets/Addresses.php`
- `src/Admin/DemoData/DataSets/ReviewContent.php`
- `assets/css/admin-demo-data.css`
- `assets/js/admin-demo-data.js`
- `includes/demo-data-functions.php`

---

## Pre-Launch Fixes ✅

Identified via multi-perspective code review. All 5 milestones completed with 2,575 total tests passing.

### PL.1 Cache Key Registry Fix ✅
- [x] Buffer new cache keys in memory instead of calling `update_option()` per key
- [x] Add `flush_registry()` method that writes all pending keys in a single `update_option()` call
- [x] Register `shutdown` hook to call `flush_registry()`
- [x] Update `clear_all()` / `delete_pattern()` to also clear pending keys
- [x] Unit tests updated in `tests/unit/Core/PerformanceTest.php`

### PL.2 Register Default Listing Fields ✅
- [x] Add `register_default_fields()` to `FieldRegistry.php` (9 fields: phone, email, website, address, city, state, zip, hours, price_range)
- [x] Wrap in `apd_register_default_fields` filter for customization
- [x] Call from `Plugin.php` at init priority 3 (after field types registered at priority 2)
- [x] Unit tests in `tests/unit/Fields/FieldRegistryTest.php`

### PL.3 Auto-Create Pages on Activation ✅
- [x] Add `create_default_pages()` to `Activator.php` (Directory, Submit a Listing, My Dashboard)
- [x] Store page IDs in `apd_options` (directory_page, submit_page, dashboard_page)
- [x] Skip creation if pages already exist or settings already set (re-activation safe)
- [x] Add `apd_default_pages` filter for page config customization
- [x] Unit tests in `tests/unit/Core/ActivatorTest.php`

### PL.4 Extract Shared Query Building Logic ✅
- [x] Create `src/Listing/ListingQueryBuilder.php` with `build( array $params ): array` method
- [x] Consistent sanitization: `absint()` on count/IDs, `sanitize_key()` on taxonomy terms
- [x] Update `ListingsBlock.php` to delegate to `ListingQueryBuilder`
- [x] Update `ListingsShortcode.php` to delegate to `ListingQueryBuilder`
- [x] 28 unit tests in `tests/unit/Listing/ListingQueryBuilderTest.php`

### PL.5 Meta Box Validation Error Display ✅
- [x] Store validation errors in user-specific transient after `apd_process_fields()` fails
- [x] Add `admin_notices` hook handler to display errors on post-save redirect
- [x] Delete transient after display (one-time show)
- [x] 6 new tests added to `tests/unit/Admin/ListingMetaBoxTest.php`

---

## Listing Type Selector & Type-Aware Fields ✅

Adds a sidebar meta box for selecting listing type, type-aware field filtering, and dynamic JS switching. See `.claude-bw/planning/listing-type-selector-plan.md` for full plan.

### LTS.1 Field Config Extension ✅
- [x] Add `listing_type` to `FieldRegistry::DEFAULT_FIELD_CONFIG` (null = all types, string = specific, array = multiple)
- [x] Add `listing_type` filter arg to `FieldRegistry::get_fields()`
- [x] Add `hidden_fields` to `ModuleRegistry::DEFAULT_CONFIG`
- [x] Unit tests for `listing_type` filter in `tests/unit/Fields/FieldRegistryTest.php`

### LTS.2 ListingTypeMetaBox Class ✅
- [x] Create `src/Admin/ListingTypeMetaBox.php` (sidebar meta box, radio buttons, field-to-type JSON mapping)
- [x] Conditional registration: only when 2+ listing type terms exist
- [x] Save handler at priority 20 (after fields at 10, before default at 99)
- [x] Filter hook: `apd_should_display_field` for type-aware field visibility
- [x] Initialize in `src/Core/Plugin.php`
- [x] Create `tests/unit/Admin/ListingTypeMetaBoxTest.php` (27 tests)

### LTS.3 Type-Aware Save Filtering ✅
- [x] Add `data-listing-types` attribute to field wrapper divs in `FieldRenderer::render_field_input()`
- [x] Add `filter_values_by_listing_type()` to `ListingMetaBox` for save-time field filtering
- [x] Add `is_field_hidden_by_module()` to `ListingMetaBox` for module hidden_fields check
- [x] Unit tests for save filtering in `tests/unit/Admin/ListingMetaBoxTest.php` (8 new tests)
- [x] Unit tests for data-listing-types in `tests/unit/Fields/FieldRendererTest.php` (3 new tests)

### LTS.4 Admin JS + List Table Column ✅
- [x] Replace `assets/js/admin.js` placeholder with listing type switching logic
- [x] Add `listing_type` column to `AdminColumns::add_columns()` (conditional on 2+ types)
- [x] Add `render_listing_type_column()` and `has_multiple_listing_types()` to `AdminColumns`

---

## Accessibility & Search Fixes ✅

Identified via multi-perspective code review (2026-02-08). All 6 items completed with 2,644 total tests passing.

### AX.1 Star Rating Focus Indicator ✅
- [x] Add `.apd-star-input__star--focused` CSS class with visible outline
- [x] Add `setStarFocus()` and `clearStarFocus()` JS methods to APDReviewForm
- [x] Toggle focus class from radio focus/blur handlers
- [x] Container `:focus-within` uses transparent outline for High Contrast Mode

### AX.2 ARIA Live Region for AJAX Search Results ✅
- [x] Add `announceResults()` method to APDFilter JS module
- [x] Create `#apd-filter-live-region` element with `role="status"` and `aria-live="polite"`
- [x] Use clear-then-set pattern (100ms delay) for reliable screen reader announcements

### AX.3 CSS Custom Properties for Error/Success Colors ✅
- [x] Add 8 CSS custom properties to `:root` (error: color, bg, border, dark; success: color, bg, border, dark)
- [x] Replace ~30 hardcoded color instances with `var()` references
- [x] Leave `rgba()` opacity variants as-is (can't decompose without `color-mix()`)

### AX.4 Contact Form novalidate + JS Validation ✅
- [x] Add `novalidate` attribute to contact form template
- [x] Create `APDContactForm` JS module with: required, email, minlength validation
- [x] Inline error display with `apd-field--has-error` class
- [x] Screen reader error announcements via `#apd-contact-live-region`
- [x] 2 new tests in `tests/unit/Contact/ContactFormTest.php`

### AX.5 Image Upload Loading State ✅
- [x] Add `aria-busy` attribute toggling on image upload container
- [x] Add `announceImageStatus()` helper with `role="status"` element
- [x] Screen reader announcements: "Loading image preview..." / "Image preview ready."
- [x] 3 new tests in `tests/unit/Frontend/Submission/SubmissionFormTest.php`

### AX.6 Search Meta Injection Fix ✅
- [x] Replace `posts_where` hook with `posts_search` filter in `SearchQuery.php`
- [x] Use `strrpos` to inject meta OR inside search clause parentheses (no fragile regex)
- [x] Preserves all other WHERE conditions (post_type, post_status)
- [x] 16 new tests in `tests/unit/Search/SearchQueryTest.php`

### AX.7 JS Module Guard Checks ✅
- [x] Add `initialized` flags to APDMyListings, APDCharCounter, APDFavorites, APDReviewForm, APDProfile
- [x] Add DOM-presence guard to APDFavorites (check for `.apd-favorite-button` before binding global listener)
- [x] Add early-return guard to APDCharCounter (skip if no `.apd-char-counter` elements)
- [x] 2 new tests in `tests/unit/Core/AssetOptimizationTest.php`

### AX.8 CSS `color-mix()` Fallback Documentation ✅
- [x] Verify all 4 `color-mix()` usages already have `rgba()` fallbacks (progressive enhancement pattern)
- [x] Add CSS comment documenting the pattern
- [x] 1 new test verifying every `color-mix()` line has a preceding `rgba()` fallback

### AX.9 Shared View Render Args ✅
- [x] Add `buildRenderArgs()` to AbstractView mapping 9 shared config keys to template args
- [x] Refactor GridView::renderListing() to use `buildRenderArgs()` + grid-specific `show_badge`
- [x] Refactor ListView::renderListing() to use `buildRenderArgs()` + list-specific `show_tags`, `max_tags`, `show_date`
- [x] 7 new tests in `GridViewTest.php` and `ListViewTest.php`

### AX.10 Flexible Field Display Format ✅
- [x] Add `display_format` field config option ('default', 'inline', 'value-only')
- [x] Extract rendering into 3 private methods in FieldRenderer
- [x] Add CSS for inline and value-only display formats
- [x] 6 new tests in `tests/unit/Fields/FieldRendererTest.php`

---

## Task Summary

| Phase | Tasks | Priority | Status |
|-------|-------|----------|--------|
| 0. Dev Environment & Testing | 28 | Critical | ✅ Complete |
| 1. Setup & Architecture | 18 | Critical | ✅ Complete |
| 2. Listing CPT | 8 | Critical | ✅ Complete |
| 3. Custom Fields | 28 | Critical | ✅ Complete |
| 4. Taxonomies | 7 | Critical | ✅ Complete |
| 5. Search & Filtering | 18 | High | ✅ Complete |
| 6. Display System | 20 | High | ✅ Complete |
| 7. Frontend Submission | 10 | High | ✅ Complete |
| 8. User Dashboard | 8 | High | ✅ Complete |
| 9. Favorites | 7 | Medium | ✅ Complete |
| 10. Reviews & Ratings | 12 | Medium | ✅ Complete |
| 11. Contact & Inquiries | 5 | Medium | ✅ Complete |
| 12. Email Notifications | 11 | Medium | ✅ Complete |
| 13. Admin Settings | 18 | High | ✅ Complete |
| 14. REST API | 10 | Medium | ✅ Complete |
| 15. i18n | 3 | Medium | ✅ Complete |
| 16. Security & Performance | 14 | Critical | ✅ Complete (151 tests) |
| 17. Testing & Docs | 7 | High | 17.1-17.3 Complete (50 tests) |
| Pre-Launch Fixes | 5 | Critical | ✅ Complete (34 new tests) |
| Listing Type Selector | 11 | High | ✅ Complete (11 new tests) |
| Accessibility & Search Fixes | 10 | High | ✅ Complete (37 new tests) |

**Total: ~258 tasks | 2,660 unit tests passing**

---

## Implementation Order

### Sprint 0: Infrastructure (Before Development) ✅ Complete
- ✅ Phase 0.1-0.3: Docker test environment + PHPUnit setup
- ✅ Phase 0.4-0.5: Test structure, factories (skeleton)
- ✅ Phase 0.6: SQL fixtures (25 listings, categories, reviews)
- ✅ Phase 0.7-0.8: Playwright setup + E2E specs (skeleton)
- ✅ Phase 0.9-0.10: Sync script + documentation

> **Note:** Phase 0 establishes the testing infrastructure. Test factories and E2E specs
> are created as skeletons initially, then populated as each feature is implemented.
> Write tests alongside features, not after.

### Sprint 1: Foundation ✅ Complete
- ✅ Phase 1: Project Setup
- ✅ Phase 2: Listing CPT
- ✅ Phase 3.1: Field Type Interface & AbstractFieldType
- ✅ Phase 3.2: Field Registry
- ✅ Phase 3.3: Core Field Types (24 types implemented)
- ✅ Phase 3.4: Field Renderer
- ✅ Phase 3.5: Field Validator
- ✅ Phase 3.6: Field Storage
- ✅ Phase 3.7: Admin Meta Box
- **Tests:** Unit tests for fields (868 tests passing), integration tests for CPT

### Sprint 2: Core Features
- Phase 3.6-3.7: Field Storage & Admin
- Phase 4: Taxonomies ✅
- Phase 13: Admin Settings (basic)
- **Tests:** Integration tests for meta box, taxonomy helpers

### Sprint 3: Display
- Phase 6: Display System
- Phase 5: Search & Filtering
- **Tests:** Integration tests for search query, E2E for search/filter UI

### Sprint 4: User Features
- Phase 7: Frontend Submission
- Phase 8: User Dashboard
- Phase 9: Favorites
- **Tests:** E2E for submission flow, dashboard, favorites toggle

### Sprint 5: Engagement
- Phase 10: Reviews & Ratings
- Phase 11: Contact Form
- Phase 12: Email Notifications
- **Tests:** Integration tests for review calculations, E2E for review form

### Sprint 6: Polish
- Phase 14: REST API
- Phase 15: i18n
- Phase 16: Security & Performance
- Phase 17: Final testing & Documentation
- **Tests:** REST API integration tests, full E2E regression suite

---

## Definition of Done (v1.0)

### Code Quality
- [ ] No PHP errors or warnings
- [ ] Passes WordPress coding standards (PHPCS)
- [ ] Works on PHP 8.0, 8.1, 8.2, 8.3
- [ ] Works on WordPress 6.0+

### Testing
- [ ] Unit tests pass (`composer test:unit`)
- [ ] Integration tests pass (`composer test:integration`)
- [ ] E2E tests pass (`npm run test:e2e`)
- [ ] All critical user flows covered by E2E tests:
  - [ ] Listing submission (guest & logged-in)
  - [ ] Search & filter
  - [ ] User dashboard
  - [ ] Favorites
  - [ ] Admin listing management

### Compatibility
- [ ] Tested on Twenty Twenty-Four theme
- [ ] Tested on Twenty Twenty-Three theme
- [ ] No conflicts with Yoast SEO
- [ ] No conflicts with WooCommerce (if installed)

### User Experience
- [ ] Admin UI is intuitive
- [ ] Frontend is responsive (mobile, tablet, desktop)
- [ ] All critical and high priority tasks complete

### Release Readiness
- [ ] All strings translatable
- [ ] README.txt complete
- [ ] Inline code documentation (PHPDoc)
- [ ] CHANGELOG.md up to date
- [ ] Ready for WordPress.org submission
