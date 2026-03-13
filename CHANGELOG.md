# Changelog

All notable changes to All Purpose Directory will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-03-12

### Added

#### Core Plugin Infrastructure (Phase 0-1)
- Plugin scaffolding with PSR-4 autoloading via Composer
- Main plugin file with singleton bootstrap pattern
- Activation/deactivation hooks with version checks
- Uninstall handler for clean removal
- Asset management system with conditional loading
- Central hook registration system
- PHPUnit test infrastructure (unit and integration)
- Playwright E2E test setup
- Test factories for listings, categories, reviews, users
- SQL fixtures for realistic test data

#### Listing Post Type (Phase 2)
- `apd_listing` custom post type with REST API support
- Custom post statuses: publish, pending, draft, expired
- Capability mapping via `Capabilities::get_listing_caps()`
- Admin columns: thumbnail, category, status badge, views count
- Sortable columns with color-coded status badges
- Admin filters by category and status

#### Custom Fields Engine (Phase 3)
- Field Type Interface with 8 contract methods
- Abstract Field Type base class with validation helpers
- Field Registry with registration/retrieval functions
- Field Renderer for admin, frontend, and display contexts
- Field Validator with sanitize + validate workflow
- Field storage in post meta with `_apd_` prefix
- Admin Meta Box with nonce verification and autosave handling
- 25 field types implemented:
  - Text, Textarea, RichText (WYSIWYG)
  - Number, Decimal, Currency
  - Email, URL, Phone
  - Date, Time, DateTime, DateRange
  - Select, MultiSelect
  - Checkbox, CheckboxGroup, Radio, Switch
  - File, Image, Gallery
  - Color, Hidden

#### Taxonomies (Phase 4)
- `apd_category` hierarchical taxonomy with meta fields (icon, color)
- `apd_tag` non-hierarchical taxonomy
- Category admin UI with color picker and dashicon selector
- Custom admin columns for category meta
- Taxonomy helper functions:
  - `apd_get_listing_categories()`
  - `apd_get_listing_tags()`
  - `apd_get_category_listings()`
  - `apd_get_categories_with_count()`
  - `apd_get_category_icon()`
  - `apd_get_category_color()`

#### Search & Filtering (Phase 5)
- `SearchQuery` class with `pre_get_posts` integration
- `FilterRegistry` for registering custom filters
- `FilterRenderer` for generating filter UI
- AJAX filtering endpoint with JSON responses
- URL state persistence via History API
- Filter types implemented:
  - Keyword filter (title, content, searchable fields)
  - Category filter (hierarchical dropdown)
  - Tag filter (checkbox list)
  - Range filter (min/max number inputs)
  - Date range filter (start/end date pickers)
- Orderby options: date, title, views, random
- 114 unit tests for search system

#### Display System (Phase 6)
- Template loader with theme override support
- Archive template with search form and pagination
- Single listing template with all data sections
- View system with ViewInterface contract
- GridView: configurable columns (2, 3, 4), responsive
- ListView: horizontal card layout
- View switcher for users
- View count tracking with bot filtering
- Related listings function
- Template functions:
  - `apd_get_template()`
  - `apd_get_template_part()`
  - `apd_template_exists()`
  - `apd_locate_template()`

#### Shortcodes (Phase 6.6)
- ShortcodeManager with registration system
- AbstractShortcode base class
- Shortcodes implemented:
  - `[apd_listings]` - Display listings with all view options
  - `[apd_search_form]` - Search and filter form
  - `[apd_categories]` - Category grid or list
  - `[apd_submission_form]` - Frontend submission
  - `[apd_dashboard]` - User dashboard
  - `[apd_favorites]` - User favorites
  - `[apd_login_form]` - Login form
  - `[apd_register_form]` - Registration form
- 85 unit tests for shortcode system

#### Gutenberg Blocks (Phase 6.7)
- BlockManager with registration system
- AbstractBlock base class
- Server-side rendering
- Blocks implemented:
  - `apd/listings` - Listings display block
  - `apd/search-form` - Search form block
  - `apd/categories` - Categories block
- Block settings panels with live preview

#### Frontend Submission (Phase 7)
- `SubmissionForm` class with field rendering
- `SubmissionHandler` class with form processing
- Guest submission support (configurable)
- Edit existing listings from frontend
- Field groups and sections
- Category/tag selectors
- Featured image upload via media library
- Terms acceptance checkbox
- Client-side validation
- Server-side validation with error display
- Spam protection:
  - Honeypot fields (CSS hidden, constant-time comparison)
  - Time-based protection (minimum 3 seconds)
  - Rate limiting (5 submissions/hour per user/IP)
  - Custom spam check filter for reCAPTCHA
- Admin notification on submission
- Success/error redirect handling
- 66 unit tests for submission system

#### User Dashboard (Phase 8)
- `Dashboard` class with tab navigation
- `MyListings` class with listing management
- `Profile` class with user settings
- Dashboard stats: total listings, views, inquiries, favorites
- Listing actions: edit, delete, mark as sold
- Pagination and status filtering
- Profile fields:
  - Display name, email
  - Avatar/photo upload
  - Bio/description
  - Contact information
  - Social links (Facebook, Twitter, LinkedIn, Instagram, YouTube, Website)
- 50+ unit tests for dashboard system

#### Favorites System (Phase 9)
- `Favorites` class (singleton) for favorites management
- User meta storage: `_apd_favorites`
- Guest favorites via cookies (30-day expiry)
- Listing favorite counts: `_apd_favorite_count`
- `FavoriteToggle` class for AJAX handling
- Heart icon button on listing cards
- Optimistic UI updates
- `FavoritesPage` class for dashboard tab
- Grid/list view toggle with preference saving
- 92 unit tests for favorites system

#### Reviews & Ratings (Phase 10)
- `ReviewManager` class with WordPress comments integration
- Comment type: `apd_review`
- Review meta: `_apd_rating`, `_apd_review_title`
- `RatingCalculator` class for average rating
- Cached rating in `_apd_average_rating` meta
- `ReviewForm` class with star input component
- `ReviewHandler` class for AJAX submission
- One review per user per listing enforcement
- Edit own review support
- Review display with pagination
- `ReviewModeration` admin page:
  - Pending reviews notification badge
  - Approve/reject/spam/trash actions
  - Bulk actions support
  - Listing and rating filters
- 80+ unit tests for review system

#### Contact & Inquiries (Phase 11)
- `ContactForm` class with form rendering
- `ContactHandler` class with form processing
- Fields: name, email, phone (optional), message
- Email to listing owner
- Optional admin copy
- Success/error messages
- `InquiryTracker` class for database logging
- `apd_inquiry` custom post type
- Inquiry stats in dashboard
- Inquiry history for listing owners
- 45+ unit tests for contact system

#### Email Notifications (Phase 12)
- `EmailManager` class (singleton)
- HTML email template wrapper
- Placeholder replacement system
- 7 notification types:
  - `listing_submitted` - To admin
  - `listing_approved` - To author
  - `listing_rejected` - To author with reason
  - `listing_expiring` - To author with days left
  - `listing_expired` - To author
  - `new_review` - To listing author
  - `new_inquiry` - To listing author
- Theme override support for templates
- Placeholders: {site_name}, {listing_title}, {author_name}, {review_rating}, etc.
- Color customization filters
- 54 unit tests for email system

#### Admin Settings (Phase 13)
- `Settings` class with WordPress Settings API
- Tabbed interface with 6 tabs:
  - General: currency, date format, distance units
  - Listings: per page, default status, expiration, feature toggles
  - Submission: who can submit, guest mode, terms page, redirect
  - Display: default view, grid columns, card elements, layouts
  - Email: from name/email, admin email, notification toggles
  - Advanced: delete data, custom CSS, debug mode
- CSS and JavaScript for conditional fields
- 43 unit tests for settings

#### REST API (Phase 14)
- `RestController` class with namespace `apd/v1`
- Permission callbacks for all access levels
- Response helpers with pagination metadata
- Endpoints implemented:
  - `/listings` - CRUD for listings
  - `/categories` - List categories with meta
  - `/tags` - List tags
  - `/favorites` - User favorites management
  - `/reviews` - CRUD for reviews
  - `/inquiries` - Inquiry management
- 151 unit tests for REST API

#### Internationalization (Phase 15)
- Text domain: `all-purpose-directory`
- 938 translatable strings
- POT file with translator comments
- Plural forms support
- Context strings for disambiguation

#### Security (Phase 16.1)
- Nonce verification on all forms
- Capability checks on all actions
- Input sanitization functions
- Output escaping throughout
- SQL injection prevention via prepared statements
- XSS prevention via escaping
- CSRF protection via nonces
- File upload validation (type, size)
- 110 security unit tests

#### Performance (Phase 16.2)
- Transient caching with object cache support
- Cached operations: categories, related listings, dashboard stats, popular listings
- Auto-invalidation on data changes
- Image lazy loading with decoding="async"
- Conditional asset loading (frontend only where needed)
- Query optimizations: `no_found_rows`, `fields => 'ids'`
- 41 performance unit tests

#### Demo Data Generator (Phase 17.4)
- Admin page at Listings → Demo Data for generating/deleting test content
- `DemoDataGenerator` creates realistic users, categories, tags, listings, reviews, inquiries, favorites
- `DemoDataTracker` marks items with `_apd_demo_data` meta for clean deletion
- DataSets provide realistic business names, addresses, review content, category hierarchies
- AJAX-powered progress indicator during generation
- Helper functions: `apd_demo_generator()`, `apd_demo_tracker()`, `apd_delete_demo_data()`

### Changed
- REST mutating endpoint auth policy clarified and hardened:
  - Cookie-authenticated requests require `X-WP-Nonce` (CSRF protection)
  - Authorization-based non-cookie clients (e.g. Application Passwords) do not require nonce
  - Mixed auth signal edge cases (Authorization + WP cookies) are treated as cookie-auth and still require nonce
- Updated "Tested up to" to WordPress 6.9
- All PHP files have ABSPATH direct access protection
- Removed deprecated `load_plugin_textdomain()` (handled by WordPress.org since WP 4.6)

### Security
- All user input sanitized before use
- All output escaped before display
- Nonce verification on all form submissions
- Capability checks on all privileged actions
- Prepared statements for all database queries

### Developer Notes

#### Minimum Requirements
- PHP 8.0+
- WordPress 6.0+

#### Test Coverage
- 2,660+ unit tests
- 110 security tests
- 41 performance tests
- E2E test suite with Playwright

#### Hooks Reference
See DEVELOPER.md for complete list of 100+ action and filter hooks.

---

## [Unreleased]

No changes yet.
