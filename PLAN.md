# Global Feature Plan - WordPress Directory Plugin

This document consolidates features from 30 analyzed directory/listing plugins to create a comprehensive feature roadmap for a new WordPress Directory plugin.

---

## Part 1: Features That Make Sense

### 1. Listing Management

#### Core Listing Features
- Custom post type for listings with full WordPress integration
- Frontend listing submission with customizable forms
- Listing editing from frontend and backend
- Listing status workflow (Draft, Pending, Published, Expired, Filled/Sold)
- Listing expiration with configurable duration
- Listing renewal system
- Featured/Promoted listings with priority display
- Listing duplication functionality
- Bulk listing management (import/export CSV, JSON, Excel)
- Listing approval workflow with admin moderation
- Listing view/impression counter
- Mark as Sold/Filled (keep visible but disable communications)

#### Listing Content
- Title, description (rich text editor support)
- Multiple images with gallery and drag-drop sorting
- Video embedding (YouTube, Vimeo)
- Document/file attachments
- Price field with currency formatting
- Custom fields system (see Custom Fields section)

### 2. Custom Fields System

#### Field Types (25+)
- Text, Textarea, Rich Text Editor
- Number, Decimal, Currency
- Email, Phone, URL
- Date, Time, Date Range
- Select/Dropdown, Multi-select
- Checkbox, Radio Buttons, Switch/Toggle
- File Upload, Image Upload
- Color Picker
- Hidden Field
- Repeater Field (multiple entries)

#### Field Configuration
- Required/optional settings
- Field validation with regex patterns
- Placeholder text
- Default values
- Field ordering via drag-and-drop
- Category-specific field assignments
- Conditional logic (show/hide based on other field values)
- Field visibility controls (frontend, search, admin-only)
- Searchable/filterable flags

### 3. Taxonomies & Organization

#### Categories
- Hierarchical categories with unlimited nesting
- Category images/icons
- Category descriptions
- Category-specific custom fields
- Empty category hiding option
- Category listing counts

#### Locations
- Hierarchical locations (Country > State > City > Neighborhood)
- Location-based filtering
- Auto-population from address geocoding

#### Tags
- Non-hierarchical tags for flexible organization
- Tag filtering and search

#### Features/Amenities
- Checkbox-style amenities/features taxonomy
- Feature icons

#### Labels/Badges
- Status labels (New, Featured, Hot, Open House, etc.)
- Custom badge system

### 4. Search & Filtering

#### Search Features
- Full-text keyword search
- AJAX-powered real-time search
- Search autocomplete/suggestions
- Advanced search with multiple criteria
- Search results with pagination
- Saved searches with email alerts
- Search by custom fields

#### Filter Types
- Category filter
- Location filter (hierarchical dropdowns)
- Tag filter
- Price range slider
- Date range picker
- Custom field filters
- Feature/amenity checkboxes
- A-Z alphabetical filter

#### Geolocation Search
- Radius/proximity search (km or miles)
- "Near Me" button using browser geolocation
- Distance sorting
- Address autocomplete

### 5. Map Integration

#### Map Providers
- Google Maps (with API key)
- OpenStreetMap/Leaflet (free, no API key)
- Mapbox support

#### Map Features
- Interactive maps with markers
- Marker clustering for dense areas
- Custom marker icons
- Info windows/popups with listing details
- Map on single listing pages
- Map on archive/search results
- Half-map / split view (listings + map side by side)
- Directions integration
- Street View support
- Multiple map styles/themes

### 6. User Management & Authentication

#### Authentication
- Frontend login form
- Frontend registration form
- Password reset/recovery
- Email verification for registrations
- Social login (Google, Facebook)
- Two-factor authentication support
- GDPR/Terms acceptance during registration

#### User Profiles
- Public user profile pages
- Profile avatar/photo upload
- Cover/banner image
- User bio and contact information
- Social media links
- User's listings display on profile

#### User Roles
- Custom roles (e.g., Listing Owner, Agent, Admin)
- Role-based capabilities and permissions
- Guest submission support (with access keys)

### 7. User Dashboard

#### Dashboard Features
- My Listings management
- Add/Edit/Delete listings
- Listing statistics (views, inquiries)
- Favorites/Wishlist management
- Saved searches
- Messages/Inquiries
- Payment/Order history
- Profile settings

### 8. Favorites/Wishlist System

- Add/remove listings from favorites
- Favorites page in user dashboard
- Heart icon toggle on listings
- Persistent storage (logged-in users)
- Session-based for guests

### 9. Reviews & Ratings

#### Review Features
- Star rating system (1-5 stars)
- Written review text
- Review moderation/approval workflow
- Average rating calculation and display
- Review count per listing
- Review replies by listing owner
- Review images/attachments
- Anonymous review option
- Multi-criteria ratings (optional)

### 10. Messaging System

#### Messaging Features
- Private messaging between users
- Contact form on listing pages
- Message threads/conversations
- Read/unread status
- Message notifications (email)
- File attachments in messages
- Message moderation options
- Keyword filtering

### 11. Comparison Feature

- Add listings to compare
- Side-by-side comparison table
- Compare custom field values
- Print comparison results

### 12. Payment & Monetization

#### Payment Models
- Free listings
- Pay-per-listing submission
- Featured/promoted listing upsells
- Subscription/membership plans
- Listing packages with limits

#### Payment Gateways
- Stripe
- PayPal
- Offline/Bank Transfer
- WooCommerce integration

#### Payment Features
- Order management
- Invoice generation
- Transaction history
- Coupon/discount codes
- Recurring payments for subscriptions

### 13. Email Notifications

#### Notification Types
- New listing submitted (admin)
- Listing approved/published (user)
- Listing expiring soon
- Listing expired
- New message received
- New review received
- Payment completed
- Password reset
- Registration confirmation

#### Email Features
- Customizable email templates
- HTML email support
- Dynamic placeholders/merge tags
- Custom from address

### 14. Shortcodes & Blocks

#### Essential Shortcodes
- Listings display (grid, list, map views)
- Single listing display
- Categories display
- Locations display
- Search form
- User dashboard
- Login/Register forms
- Listing submission form
- Favorites page

#### Gutenberg Blocks
- Native block editor support for all shortcodes
- Block settings and customization

### 15. Widgets

- Recent/Latest listings
- Featured listings
- Popular listings
- Categories widget
- Locations widget
- Search form widget
- Login/User menu widget
- Map widget

### 16. Page Builder Integration

#### Supported Builders
- Elementor (custom widgets)
- Gutenberg (native blocks)
- Divi Builder
- Visual Composer/WPBakery
- Beaver Builder
- Bricks Builder

### 17. Display & Layout

#### Layout Options
- Grid view
- List view
- Table view
- Masonry layout
- Carousel/Slider
- Map view

#### Customization
- Columns configuration (1-4+)
- Items per page
- Sorting options (date, title, price, rating, views, random)
- Responsive design
- Custom CSS support

### 18. SEO Features

- SEO-friendly URLs/permalinks
- Schema.org/JSON-LD structured data
- Meta title and description customization
- Open Graph tags for social sharing
- XML sitemap integration
- Yoast SEO / Rank Math compatibility
- Breadcrumb support

### 19. Import/Export

#### Import Features
- CSV import with field mapping
- Bulk listing creation
- Import validation and error reporting
- Update existing listings option

#### Export Features
- CSV export
- JSON export
- Excel export
- Filter before export

### 20. Security Features

- reCAPTCHA (v2 and v3) support
- Honeypot spam protection
- Nonce verification
- Input sanitization
- File upload validation
- Report abuse/flag listing functionality
- Content moderation

### 21. Internationalization

- Full translation support
- WPML/Polylang compatibility
- RTL language support
- Multi-currency support

### 22. REST API

- Full REST API for listings
- API for categories, locations, users
- API authentication
- Endpoints for mobile app development

### 23. Performance

- Pagination for large datasets
- AJAX-based filtering (no page reloads)
- Image lazy loading
- Query optimization
- Caching support (LiteSpeed, Redis compatible)
- Background processing for imports

### 24. Admin Features

#### Dashboard
- Overview statistics
- Recent activity
- Quick actions
- Pending approvals count

#### Settings Organization
- General settings
- Listing settings
- Payment settings
- Email settings
- Appearance settings
- Advanced/Developer settings

#### Tools
- System status/info
- Debug mode
- Data cleanup utilities

---

## Part 2: Features Probably Not Interesting for Our Project

### 1. Industry-Specific Features

These are too specialized for a general directory plugin:

#### Real Estate Specific
- Mortgage/loan calculator
- Floor plans display
- Property comparison with room counts
- MLS/RETS import
- 360° virtual tours (complex to implement)
- Nearby places/POI display (schools, hospitals)

#### Job Board Specific
- Resume/CV management system
- Job applications with status tracking
- Employer dashboard separate from job seeker
- VIN decoder (automotive)
- Test drive booking (automotive)
- Trade-in value calculator

#### Vehicle Specific
- VIN decoder integration
- Vehicle history reports
- Loan calculators specific to vehicles

### 2. Overly Complex AI Features

- AI-powered form generation
- AI semantic search with vector embeddings
- AI content writing/generation
- AI image enhancement
- These add significant complexity and cost

### 3. Niche Social Features

- Friends/Followers system (better handled by BuddyPress)
- Social activity feeds
- Groups/Communities
- Points/Gamification (myCRED)
- Private content system

### 4. Complex Scheduling Features

- Booking/Appointment scheduling
- Availability calendars
- Time slot management
- These are better as separate plugins

### 5. Overly Specialized Display

- Timeline view
- Accordion view
- Zigzag layouts
- 80+ skin variations (maintenance nightmare)

### 6. Legacy Features

- Link library features (RSS aggregation, bookmarklets)
- Phone directory with tap-to-call as primary focus
- Staff directory internal-only features
- Name directory alphabetical-only navigation

### 7. External Service Dependencies

- Live chat integration (too many providers)
- SMS/Mobile verification (complex, requires paid services)
- Social login for 10+ providers
- Multiple AI provider integrations

### 8. Complex Multi-Site Features

- Multi-vendor marketplace (WooCommerce territory)
- Franchise management
- Multi-location chains
- Agency with multiple agents hierarchy

### 9. Excessive Addon Ecosystem

- Building for 30+ paid addons from day one
- Complex licensing/activation systems
- Feature fragmentation across addons

### 10. Print/PDF Features

- PDF export of listings
- Printable versions
- PDF invoice generation (better with dedicated invoice plugins)

---

## Part 3: Modular Architecture Design

To support different directory types (URL directories, venues/places, real estate, jobs, etc.) without bloating the core plugin, we adopt a modular architecture where the core provides the foundation and type-specific modules extend it.

### Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                         CORE PLUGIN                              │
├─────────────────────────────────────────────────────────────────┤
│  Infrastructure        │  Generic Features    │  Framework       │
│  ─────────────────     │  ────────────────    │  ─────────       │
│  • CPT Registration    │  • Categories        │  • Search/Filter │
│  • Custom Fields Engine│  • Tags              │  • Import/Export │
│  • Taxonomy Framework  │  • Reviews/Ratings   │  • REST API Base │
│  • User Management     │  • Favorites         │  • Hooks/Filters │
│  • Frontend Forms      │  • Email System      │  • Settings API  │
│  • Payment System      │  • Display Views     │  • Template Sys  │
└─────────────────────────────────────────────────────────────────┘
                                   │
            ┌──────────────────────┼──────────────────────┐
            │                      │                      │
            ▼                      ▼                      ▼
    ┌───────────────┐      ┌───────────────┐      ┌───────────────┐
    │    MODULE:    │      │    MODULE:    │      │    MODULE:    │
    │ URL Directory │      │ Venues/Places │      │  Job Board    │
    ├───────────────┤      ├───────────────┤      ├───────────────┤
    │ • URL field   │      │ • Address     │      │ • Salary      │
    │ • Screenshots │      │ • Location tax│      │ • Company     │
    │ • Link checker│      │ • Maps/Markers│      │ • Apply system│
    │ • Click track │      │ • Hours       │      │ • Resume DB   │
    │ • Broken badge│      │ • Radius srch │      │ • Job types   │
    └───────────────┘      └───────────────┘      └───────────────┘
```

### Core Plugin Components

#### 1. Custom Post Type Framework
```php
// Core registers a base CPT that modules can extend
register_post_type('wpdr_listing', [
    'public' => true,
    'supports' => ['title', 'editor', 'thumbnail', 'author'],
    'has_archive' => true,
    // ... base configuration
]);
```

#### 2. Custom Fields Engine
The core provides a field registration and rendering system:

**Supported Field Types (in core):**
- Text, Textarea, Rich Text Editor
- Number, Decimal, Currency
- Email, Phone, URL
- Date, Time, Date Range
- Select/Dropdown, Multi-select
- Checkbox, Radio Buttons, Switch/Toggle
- File Upload, Image Upload
- Color Picker
- Hidden Field
- Repeater Field

**Field API:**
```php
// Modules register their fields
wpdr_register_field('module_url', 'website_url', [
    'type'        => 'url',
    'label'       => 'Website URL',
    'required'    => true,
    'searchable'  => true,
    'validation'  => 'url',
]);
```

#### 3. Taxonomy Framework
Core provides generic Categories and Tags. Modules register additional taxonomies:

```php
// Core taxonomies (always available)
'wpdr_category' => hierarchical, generic categories
'wpdr_tag'      => non-hierarchical tags

// Module-registered taxonomies
wpdr_register_taxonomy('module_venues', 'wpdr_location', [
    'hierarchical' => true,
    'levels'       => ['country', 'state', 'city', 'neighborhood'],
]);
```

#### 4. Search & Filter Framework
Core provides the filtering infrastructure; modules register their filterable fields:

```php
wpdr_register_filter('module_venues', 'radius_search', [
    'type'     => 'location_radius',
    'label'    => 'Search Nearby',
    'requires' => ['wpdr_location'], // dependency
]);
```

#### 5. Display System
Core provides base views (grid, list, table, carousel). Modules can:
- Add custom views
- Override templates
- Add view-specific data

#### 6. Status Workflow
Core provides base statuses; modules extend:

```php
// Core statuses
'draft', 'pending', 'published', 'expired'

// Module can add
wpdr_register_status('module_url', 'broken', [
    'label'       => 'Broken Link',
    'public'      => true,
    'show_badge'  => true,
    'badge_color' => '#e74c3c',
]);
```

### Module API Specification

#### Module Registration
```php
// In module's main file
add_action('wpdr_init', function() {
    wpdr_register_module('venues', [
        'name'         => 'Venues & Places',
        'description'  => 'Location-based directory with maps',
        'version'      => '1.0.0',
        'author'       => 'Developer Name',
        'requires'     => ['core' => '1.0.0'],
        'features'     => [
            'maps',
            'geolocation',
            'radius_search',
            'opening_hours',
        ],
    ]);
});
```

#### Core Hooks & Filters

**Listing Lifecycle:**
```php
do_action('wpdr_before_listing_save', $listing_id, $data, $module);
do_action('wpdr_after_listing_save', $listing_id, $data, $module);
do_action('wpdr_listing_status_changed', $listing_id, $old, $new);
do_action('wpdr_listing_expired', $listing_id);
```

**Search & Display:**
```php
apply_filters('wpdr_search_query_args', $args, $module, $filters);
apply_filters('wpdr_listing_card_data', $data, $listing_id, $view);
apply_filters('wpdr_single_listing_sections', $sections, $listing_id);
```

**Forms:**
```php
apply_filters('wpdr_submission_fields', $fields, $module, $category);
apply_filters('wpdr_field_validation', $valid, $field, $value);
do_action('wpdr_after_frontend_submission', $listing_id, $user_id);
```

**Admin:**
```php
apply_filters('wpdr_admin_columns', $columns, $module);
apply_filters('wpdr_settings_sections', $sections);
```

### Example Module: URL Directory

```php
<?php
/**
 * Module: URL Directory
 * Adds website/link directory functionality
 */

add_action('wpdr_init', 'wpdr_url_module_init');

function wpdr_url_module_init() {

    // Register module
    wpdr_register_module('url_directory', [
        'name'        => 'URL Directory',
        'description' => 'Website and link directory',
        'features'    => ['link_checker', 'screenshots', 'click_tracking'],
    ]);

    // Register fields
    wpdr_register_field('url_directory', 'website_url', [
        'type'       => 'url',
        'label'      => 'Website URL',
        'required'   => true,
        'searchable' => false,
        'icon'       => 'dashicons-admin-links',
    ]);

    wpdr_register_field('url_directory', 'screenshot', [
        'type'       => 'image',
        'label'      => 'Screenshot',
        'auto_generate' => true, // Module handles generation
    ]);

    // Register statuses
    wpdr_register_status('url_directory', 'broken', [
        'label'       => 'Broken Link',
        'badge_color' => '#e74c3c',
    ]);

    wpdr_register_status('url_directory', 'redirect', [
        'label'       => 'Redirects',
        'badge_color' => '#f39c12',
    ]);

    // Register filters
    wpdr_register_filter('url_directory', 'link_status', [
        'type'    => 'select',
        'label'   => 'Link Status',
        'options' => ['all', 'working', 'broken', 'redirect'],
    ]);
}

// Module-specific features
add_action('wpdr_daily_cron', 'wpdr_url_check_links');
function wpdr_url_check_links() {
    // Check all URLs and update status
}

add_action('wpdr_listing_viewed', 'wpdr_url_track_click');
function wpdr_url_track_click($listing_id) {
    // Track outbound clicks
}
```

### Example Module: Venues/Places

```php
<?php
/**
 * Module: Venues & Places
 * Adds location-based directory with maps
 */

add_action('wpdr_init', 'wpdr_venues_module_init');

function wpdr_venues_module_init() {

    // Register module
    wpdr_register_module('venues', [
        'name'        => 'Venues & Places',
        'description' => 'Location-based business directory',
        'features'    => ['maps', 'geolocation', 'radius_search', 'hours'],
    ]);

    // Register location taxonomy
    wpdr_register_taxonomy('venues', 'wpdr_location', [
        'hierarchical' => true,
        'labels'       => [
            'name'     => 'Locations',
            'singular' => 'Location',
        ],
    ]);

    // Register fields
    wpdr_register_field('venues', 'address', [
        'type'        => 'address',
        'label'       => 'Address',
        'geocode'     => true, // Auto-geocode to lat/lng
        'searchable'  => true,
    ]);

    wpdr_register_field('venues', 'coordinates', [
        'type'   => 'latlng',
        'label'  => 'Map Location',
        'hidden' => true, // Auto-populated from address
    ]);

    wpdr_register_field('venues', 'phone', [
        'type'  => 'phone',
        'label' => 'Phone Number',
    ]);

    wpdr_register_field('venues', 'opening_hours', [
        'type'  => 'hours',
        'label' => 'Opening Hours',
    ]);

    // Register statuses
    wpdr_register_status('venues', 'temporarily_closed', [
        'label'       => 'Temporarily Closed',
        'badge_color' => '#f39c12',
    ]);

    wpdr_register_status('venues', 'permanently_closed', [
        'label'       => 'Permanently Closed',
        'badge_color' => '#e74c3c',
    ]);

    // Register filters
    wpdr_register_filter('venues', 'radius', [
        'type'    => 'radius',
        'label'   => 'Distance',
        'units'   => ['km', 'miles'],
        'options' => [5, 10, 25, 50, 100],
    ]);

    wpdr_register_filter('venues', 'open_now', [
        'type'  => 'toggle',
        'label' => 'Open Now',
    ]);
}

// Add map display
add_filter('wpdr_single_listing_sections', 'wpdr_venues_add_map', 10, 2);
function wpdr_venues_add_map($sections, $listing_id) {
    $sections['map'] = [
        'title'    => 'Location',
        'template' => 'venues/map-section',
        'priority' => 20,
    ];
    return $sections;
}
```

### Module Feature Comparison

| Feature | Core | URL Module | Venues Module | Jobs Module |
|---------|------|------------|---------------|-------------|
| **Basic Fields** | ✓ | | | |
| **Categories/Tags** | ✓ | | | |
| **User Dashboard** | ✓ | | | |
| **Reviews/Ratings** | ✓ | | | |
| **Favorites** | ✓ | | | |
| **Search Framework** | ✓ | | | |
| **Payments** | ✓ | | | |
| **Email System** | ✓ | | | |
| **URL Field** | | ✓ | | |
| **Screenshot Gen** | | ✓ | | |
| **Link Checker** | | ✓ | | |
| **Click Tracking** | | ✓ | | |
| **Address/Geocoding** | | | ✓ | |
| **Maps Integration** | | | ✓ | |
| **Radius Search** | | | ✓ | |
| **Opening Hours** | | | ✓ | |
| **Salary Fields** | | | | ✓ |
| **Application System** | | | | ✓ |
| **Resume Upload** | | | | ✓ |

### Directory Switching Behavior

Users should be able to:
1. **Single type:** Install core + one module for a focused directory
2. **Multi-type:** Install multiple modules for a hybrid directory
3. **Switch types:** Change module without losing core data

```php
// Settings page
'directory_mode' => 'single' | 'multi',
'active_modules' => ['venues', 'url_directory'], // if multi
'primary_module' => 'venues', // default for new listings
```

### Module Distribution Strategy

All modules are **free** on WordPress.org. Revenue comes from premium **features** that work across all modules.

| Module | Availability | Rationale |
|--------|-------------|-----------|
| Core Plugin | Free | Foundation for all modules |
| URL Directory | Free | Simple, great for adoption |
| Venues/Places | Free | Most common use case |
| Real Estate | Free | Maximizes WordPress.org exposure |
| Job Board | Free | Attracts business users |
| Events | Free | Common directory type |
| Classifieds | Free | Broad appeal |

**Premium Features** (sold separately, work with any module):
- Paid Listings, Claim Listings, Featured Listings
- Radius Search, Maps Pro, Advanced Filters
- Analytics, Private Messaging, Bookings
- See Part 4 for full feature catalog and pricing

---

## Part 4: Monetization Strategy

### Recommended Approach: Free Modules + Premium Features

Instead of making some modules free and others premium, **all directory modules are free** while cross-cutting **features** are sold as premium add-ons. This maximizes WordPress.org exposure and provides a clearer value proposition.

```
FREE (WordPress.org)                 PREMIUM (Your Site)
────────────────────                 ───────────────────
All Directory Modules:               Cross-Module Features:
• Core Plugin                        • Paid Listings
• URL Directory                      • Claim Listings
• Venues/Places                      • Radius Search
• Real Estate                        • Google Maps Pro
• Job Board                          • Frontend Submissions Pro
• Events                             • Private Messaging
• Classifieds                        • Analytics Dashboard
                                     • And more...
```

### Launch Strategy: Trust First, Monetize Later

**Critical:** The initial releases (v1.x) will be **100% free with zero mention of premium features**. This avoids the "freemium stigma" where users assume the free version is a crippled teaser for paid features.

#### The Problem with Early Monetization

Many WordPress plugins fail because:
- Users see "Free version of Premium Plugin" and think "crippled teaser"
- Reviews complain "just an upsell machine" or "useless without paying"
- Trust is never established before asking for money
- The free version is designed to frustrate, not help

#### The Solution: Phased Launch

```
┌─────────────────────────────────────────────────────────────────────────┐
│  PHASE 1: BUILD TRUST (v1.0 - v1.x, ~6-12 months)                       │
├─────────────────────────────────────────────────────────────────────────┤
│  • 100% free, genuinely useful, no asterisks                            │
│  • NO "Pro" badges, NO disabled features, NO upsell banners             │
│  • NO settings grayed out with "Available in Pro"                       │
│  • NO premium features section in admin                                 │
│  • Users experience a complete, quality plugin                          │
│                                                                         │
│  Goals:                                                                 │
│  • 1,000+ active installs                                               │
│  • 4.5+ star rating on WordPress.org                                    │
│  • Organic recommendations and word-of-mouth                            │
│  • Community feedback on what features users actually want              │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│  PHASE 2: INTRODUCE PREMIUM (v2.0+)                                     │
├─────────────────────────────────────────────────────────────────────────┤
│  • Premium features introduced as "new add-ons we built"                │
│  • NOT "unlock what was hidden" — genuinely new code                    │
│  • Free version remains exactly as capable as before                    │
│  • Early adopters get loyalty discounts (30-50% lifetime)               │
│  • Transparent messaging: "Premium helps us keep developing"            │
└─────────────────────────────────────────────────────────────────────────┘
```

#### What Phase 1 Looks Like

**Admin UI in v1.x:**
```
┌─────────────────────────────────────────────────────────┐
│  WP Directory Research                                   │
├─────────────────────────────────────────────────────────┤
│  📋 Listings                                             │
│  📁 Categories                                           │
│  📍 Locations                                            │
│  ⚙️  Settings                                            │
│  📊 Tools                                                │
│                                                         │
│  [No "Pro", no "Upgrade", no grayed-out features]       │
└─────────────────────────────────────────────────────────┘
```

**What NOT to include in v1.x:**
- ❌ "Upgrade to Pro" buttons
- ❌ "Premium" or "Pro" labels anywhere
- ❌ Grayed-out settings with lock icons
- ❌ Feature comparison tables (free vs pro)
- ❌ Upsell notices or banners
- ❌ "More features coming in Pro" messaging
- ❌ Links to pricing pages

#### Technical Preparation During Phase 1

Even though premium features aren't visible, **build the architecture to support them**:

```php
// Core includes hooks that premium features will use later
// But nothing in v1.x uses them — they're just good extensibility

do_action('wpdr_before_submission', $listing, $module);
do_action('wpdr_after_listing_published', $listing);
do_action('wpdr_listing_query_args', $args, $module);

$filters = apply_filters('wpdr_search_filters', $filters, $module);
$fields = apply_filters('wpdr_submission_fields', $fields, $module);

// These hooks exist for extensibility (a normal plugin practice)
// When Paid Listings ships in v2.0, it hooks in cleanly
// No core changes needed, no user disruption
```

#### How to Introduce Premium (Phase 2) Without Backlash

1. **Frame as expansion, not restriction**
   - ✅ "We've built new tools for users who want to monetize"
   - ❌ "Upgrade to unlock features"

2. **Keep free version exactly as capable**
   - Nothing removed, nothing disabled
   - Premium = additional functionality, not replacement

3. **Reward early adopters**
   - "You've been with us since v1.0 — here's 50% off lifetime"
   - Consider free access to one premium feature for v1.x users

4. **Be transparent about sustainability**
   - "We're a small team. Premium features help us keep improving the free plugin."
   - Users respect honesty; they resent manipulation

5. **Announce with a changelog, not a sales pitch**
   - "v2.0 introduces optional add-ons for advanced use cases"
   - Not: "UPGRADE NOW! Limited time offer!"

#### Why This Works

| Approach | User Perception | Result |
|----------|-----------------|--------|
| Freemium from day 1 | "Another upsell trap" | Skepticism, poor reviews |
| Free first, premium later | "This is genuinely good... and now they have extras I might want" | Trust, loyalty, conversions |

**Real-world validation:**
- Many successful plugins started fully free and added premium after building trust
- Users who feel helped are happy to pay; users who feel tricked leave 1-star reviews
- A 4.8-star free plugin converts better than a 3.5-star freemium plugin

### Why Feature-Based Premium?

| Factor | Module-Based Premium | Feature-Based Premium |
|--------|---------------------|----------------------|
| **WordPress.org exposure** | 1-2 free modules | ALL modules listed |
| **Barrier to entry** | Must buy module to try | Full functionality free |
| **User clarity** | "Which module do I need?" | "What do I want to do?" |
| **Development efficiency** | Duplicate features per module | Build once, works everywhere |
| **Cross-selling** | Limited | High (features work with any module) |
| **Value proposition** | "Buy Real Estate module" | "Add Paid Listings to any directory" |

### Alternative Approaches Considered

| Approach | How It Works | Why Not Chosen |
|----------|--------------|----------------|
| **Premium Modules** | Free core, paid modules | Limits WordPress.org exposure |
| **Separate Plugins** | Free on .org, Pro replaces it | Doesn't fit modular design |
| **License Unlock** | Single plugin, key unlocks features | Ships premium code to free users |
| **Freemius SDK** | Built-in upgrades, managed platform | 7% revenue share |

### Premium Features Catalog

#### Monetization Features
| Feature | Description | Price |
|---------|-------------|-------|
| **Paid Listings** | Charge for submissions, payment packages, listing limits | $79 |
| **Featured Listings** | Boost visibility, sticky posts, highlighted cards | $49 |
| **Claim Listings** | Let businesses claim & manage their listing | $59 |
| **Subscriptions** | Recurring payments for listings, membership plans | $79 |

#### Enhanced Functionality
| Feature | Description | Price |
|---------|-------------|-------|
| **Maps Pro** | Google Maps, marker clustering, directions, Street View | $49 |
| **Radius Search** | "Near me" button, distance filtering, geolocation | $49 |
| **Advanced Filters** | Ajax filtering, faceted search, saved searches | $59 |
| **Comparison Tool** | Compare listings side-by-side | $39 |

#### User & Communication
| Feature | Description | Price |
|---------|-------------|-------|
| **Frontend Pro** | Enhanced submission forms, multi-step wizard, drafts | $59 |
| **Private Messaging** | User-to-user or user-to-listing owner messaging | $59 |
| **Bookings** | Appointment scheduling integrated with listings | $79 |
| **Lead Forms** | Contact forms with lead tracking & CRM integration | $49 |

#### Business Tools
| Feature | Description | Price |
|---------|-------------|-------|
| **Analytics** | Views, clicks, leads per listing, owner dashboard | $59 |
| **Import/Export Pro** | CSV, XML imports with field mapping, scheduled imports | $49 |
| **Multi-Location** | Multiple addresses per listing with separate maps | $39 |

### Feature Bundles

| Bundle | Includes | Price |
|--------|----------|-------|
| **Starter** | Paid Listings + Featured + Claim | $149/yr |
| **Business** | Starter + Analytics + Lead Forms | $199/yr |
| **Complete** | All features | $349/yr |
| **Lifetime Complete** | All features, forever, unlimited sites | $699 |

### Technical Architecture: Cross-Module Features

Premium features hook into **any module** via the core's hook system:

```php
<?php
/**
 * Plugin Name: WPDR Paid Listings
 * Description: Charge for listing submissions on any directory type
 * Requires Plugins: wp-directory-research
 * Version: 1.0.0
 */

defined('ABSPATH') || exit;

// Check for core plugin
if (!function_exists('wpdr_register_feature')) {
    add_action('admin_notices', function() {
        echo '<div class="error"><p>';
        echo '<strong>WPDR Paid Listings</strong> requires the WP Directory Research core plugin.';
        echo '</p></div>';
    });
    return;
}

// Initialize license checking for updates
add_action('admin_init', function() {
    if (!class_exists('EDD_SL_Plugin_Updater')) {
        require_once __DIR__ . '/includes/EDD_SL_Plugin_Updater.php';
    }

    new EDD_SL_Plugin_Updater(
        'https://your-site.com',
        __FILE__,
        [
            'version'   => '1.0.0',
            'license'   => get_option('wpdr_paid_listings_license_key'),
            'item_id'   => 456,
            'author'    => 'Your Name',
            'url'       => home_url(),
        ]
    );
});

// Register the premium feature
add_action('wpdr_init', function() {
    wpdr_register_feature('paid_listings', [
        'name'        => 'Paid Listings',
        'version'     => '1.0.0',
        'description' => 'Charge for listing submissions',
        'settings'    => true,  // Has settings page
    ]);
});

// Hook into ANY module's submission flow
add_action('wpdr_before_submission', function($listing, $module) {
    // Check if paid listings is enabled for this module
    if (!wpdr_feature_enabled('paid_listings', $module)) {
        return;
    }

    // Get the selected package
    $package_id = $_POST['listing_package'] ?? null;
    if (!$package_id) {
        wp_die('Please select a listing package.');
    }

    // Create pending payment and redirect to checkout
    $order_id = wpdr_paid_listings_create_order($listing, $package_id);
    wp_redirect(wpdr_paid_listings_checkout_url($order_id));
    exit;
}, 10, 2);

// Add package selection to ANY module's submission form
add_filter('wpdr_submission_fields', function($fields, $module) {
    if (!wpdr_feature_enabled('paid_listings', $module)) {
        return $fields;
    }

    $fields['listing_package'] = [
        'type'     => 'radio',
        'label'    => 'Choose Package',
        'options'  => wpdr_paid_listings_get_packages($module),
        'required' => true,
        'priority' => 5,  // Show near the top
    ];

    return $fields;
}, 10, 2);

// Admin settings: enable/disable per module
add_action('wpdr_feature_settings_paid_listings', function() {
    $modules = wpdr_get_registered_modules();

    echo '<h3>Enable Paid Listings For:</h3>';
    foreach ($modules as $module_id => $module) {
        $enabled = get_option("wpdr_paid_listings_{$module_id}", false);
        printf(
            '<label><input type="checkbox" name="wpdr_paid_listings_%s" value="1" %s> %s</label><br>',
            esc_attr($module_id),
            checked($enabled, true, false),
            esc_html($module['name'])
        );
    }
});
```

### Radius Search Feature Example

Shows how a feature works across all location-aware modules:

```php
// Register radius search feature
add_action('wpdr_init', function() {
    wpdr_register_feature('radius_search', [
        'name'     => 'Radius Search',
        'version'  => '1.0.0',
        'requires' => ['geolocation'],  // Requires modules with location fields
    ]);
});

// Add radius filter to any module that has location data
add_filter('wpdr_search_filters', function($filters, $module) {
    // Only add if module has location field AND feature is enabled
    if (!wpdr_module_has_field($module, 'location')) {
        return $filters;
    }

    if (!wpdr_feature_enabled('radius_search', $module)) {
        return $filters;
    }

    $filters['radius'] = [
        'type'    => 'radius',
        'label'   => __('Distance', 'wpdr'),
        'options' => [5, 10, 25, 50, 100],
        'unit'    => get_option('wpdr_distance_unit', 'km'),
    ];

    $filters['near_me'] = [
        'type'  => 'geolocation_button',
        'label' => __('Near Me', 'wpdr'),
    ];

    return $filters;
}, 10, 2);

// Modify query for radius search
add_filter('wpdr_listing_query', function($query_args, $filters, $module) {
    if (empty($filters['radius']) || empty($filters['lat']) || empty($filters['lng'])) {
        return $query_args;
    }

    // Add geo query using Haversine formula
    $query_args['geo_query'] = [
        'lat'    => $filters['lat'],
        'lng'    => $filters['lng'],
        'radius' => $filters['radius'],
        'unit'   => get_option('wpdr_distance_unit', 'km'),
    ];

    return $query_args;
}, 10, 3);
```

### License Management in Core (v2.0+ only)

**Note:** License management UI only appears when premium features are installed. In v1.x, this code exists but is never triggered — users see no indication of premium features.

The core plugin provides a unified interface for all premium features:

```php
// Core provides license management UI
add_action('wpdr_settings_sections', function($sections) {
    $sections['licenses'] = [
        'title'    => 'Licenses',
        'callback' => 'wpdr_render_licenses_page',
    ];
    return $sections;
});

function wpdr_render_licenses_page() {
    // Get all registered premium features
    $premium_features = apply_filters('wpdr_premium_features', []);

    foreach ($premium_features as $feature_id => $feature) {
        $license_key = get_option("wpdr_{$feature_id}_license_key");
        $status = get_option("wpdr_{$feature_id}_license_status");

        // Render license input field and status
        echo '<div class="wpdr-license-row">';
        echo '<h4>' . esc_html($feature['name']) . '</h4>';
        echo '<input type="text" name="wpdr_' . esc_attr($feature_id) . '_license_key" ';
        echo 'value="' . esc_attr($license_key) . '" class="regular-text">';
        echo '<span class="wpdr-license-status ' . esc_attr($status) . '">' . esc_html($status) . '</span>';
        echo '</div>';
    }
}

// Premium features register themselves
add_filter('wpdr_premium_features', function($features) {
    $features['paid_listings'] = [
        'name'      => 'Paid Listings',
        'store_url' => 'https://your-site.com',
        'item_id'   => 456,
    ];
    return $features;
});
```

### Upsell Touchpoints (v2.0+ only)

**Note:** Zero upsell touchpoints in v1.x. The following only applies after premium features are released.

Strategic placement of upgrade prompts (non-intrusive, respectful):

1. **Feature Settings Panel**
   - Show available features with "Get This Feature" buttons
   - Contextual: only show relevant features for active modules

2. **Submission Form**
   - "Want to charge for listings? Get Paid Listings add-on"

3. **Search/Filter Area**
   - "Add radius search to help users find nearby listings"

4. **Analytics Gap**
   - "See how your listings perform with the Analytics add-on"

```php
// Example: Contextual upsell when viewing listings without analytics
add_action('wpdr_after_listing_stats', function($listing_id) {
    if (!wpdr_feature_active('analytics')) {
        echo '<div class="wpdr-upsell">';
        echo '<p>Want to see views, clicks, and leads for this listing?</p>';
        echo '<a href="https://your-site.com/analytics" class="button">Get Analytics</a>';
        echo '</div>';
    }
});
```

### Technical Implementation: EDD + Software Licensing

Self-hosted solution with no revenue share:

```
┌─────────────────────────────────────────────────────────┐
│                    YOUR WEBSITE                          │
├─────────────────────────────────────────────────────────┤
│  Easy Digital Downloads (free plugin)                    │
│  + Software Licensing Extension (~$100/yr)               │
│  + Recurring Payments Extension (~$100/yr)               │
├─────────────────────────────────────────────────────────┤
│  Features:                                               │
│  • Sell feature add-ons as digital downloads             │
│  • License key generation & validation                   │
│  • Automatic update delivery to licensed users           │
│  • Subscription renewals for annual licenses             │
│  • Customer account dashboard                            │
│  • Discount codes & bundle pricing                       │
└─────────────────────────────────────────────────────────┘
```

### Infrastructure Requirements

| Component | Tool | Cost |
|-----------|------|------|
| Plugin sales | Easy Digital Downloads | Free |
| License management | EDD Software Licensing | ~$100/yr |
| Recurring payments | EDD Recurring Payments | ~$100/yr |
| Payment processing | Stripe | 2.9% + $0.30 per transaction |
| Hosting (sales site) | Any good WordPress host | ~$20-50/mo |
| Documentation | GitBook, ReadMe, or self-hosted | Free-$20/mo |
| Support | Help Scout, or EDD built-in | Free-$50/mo |

**Total startup cost:** ~$200/yr + hosting + payment fees

### Revenue Projections (Conservative)

| Metric | Year 1 (Trust) | Year 2 (Launch) | Year 3 (Growth) |
|--------|----------------|-----------------|-----------------|
| Phase | v1.x (free only) | v2.0 (premium launch) | v2.x (expansion) |
| Free installs | 1,500 | 7,500 | 20,000 |
| Premium available | No | Yes (month 6+) | Yes |
| Conversion rate | - | 3% | 4% |
| Paid customers | 0 | 150 | 800 |
| Avg. features/customer | - | 1.5 | 2.5 |
| Avg. revenue/customer | - | $100 | $160 |
| Gross revenue | $0 | $15,000 | $128,000 |
| Renewal rate | - | - | 75% |

**Year 1:** Focus entirely on building trust, reputation, and user base. No revenue expected.

**Year 2:** Launch premium features mid-year. Conservative conversion from established, trusting user base.

**Year 3:** Word-of-mouth from satisfied users, mature premium feature set, strong renewals.

*The "trust first" approach delays revenue but results in higher conversion rates, better reviews, and sustainable long-term growth.*

### Benefits Summary

1. **Trust before monetization** - v1.x is 100% free, no premium mentions, builds genuine reputation
2. **Maximum WordPress.org exposure** - ALL modules listed free, not "lite" versions
3. **Avoids freemium stigma** - Users discover a quality plugin, not an upsell funnel
4. **Clear value proposition** - When premium launches: "new tools for power users"
5. **Build once, works everywhere** - Premium features work across all modules
6. **Higher conversion rates** - Users who trust you are happy to support you
7. **Sustainable long-term growth** - Good reviews compound; bad reviews kill plugins

---

## Implementation Priority Recommendation

```
═══════════════════════════════════════════════════════════════════════════
  PHASE 1-4: TRUST BUILDING (v1.0 - v1.x)
  100% free, no premium mentions, build reputation
  Target: 6-12 months, 1,000+ installs, 4.5+ stars
═══════════════════════════════════════════════════════════════════════════
```

### Phase 1: Core Foundation (v1.0)
1. Plugin architecture & module API
2. Custom post type framework
3. Custom fields engine (all 15+ field types)
4. Generic taxonomies (Categories, Tags)
5. Basic frontend submission form framework
6. Simple search and filtering framework
7. Grid/List display views
8. Basic user dashboard structure
9. Admin settings framework
10. Hook/filter system for future extensibility (no premium mentions)

### Phase 2: Core Features Completion (v1.1 - v1.2)
1. Favorites system
2. Reviews and ratings
3. Contact/inquiry form
4. Email notification system
5. User authentication (login/register)
6. Import/export framework (CSV)
7. REST API base endpoints
8. Template system for overrides

### Phase 3: First Modules (v1.3 - v1.4)
**URL Directory Module:**
1. URL field with validation
2. Screenshot generation (optional service)
3. Link status checker (cron-based)
4. Click/visit tracking
5. Broken link badge display

**Venues/Places Module:**
1. Address field with geocoding
2. Location taxonomy (hierarchical)
3. Map integration (OpenStreetMap — free, no API key needed)
4. Basic proximity sorting (if address provided)
5. Opening hours field (basic)

### Phase 4: Additional Modules (v1.5 - v1.x)
**Real Estate Module:**
1. Property-specific fields (beds, baths, sqft)
2. Price formatting & ranges
3. Property types taxonomy
4. Sold/Under Contract statuses
5. Agent assignment

**Job Board Module:**
1. Salary field with ranges
2. Job types taxonomy
3. Application system
4. Resume uploads
5. Employer profiles

**Polish & Community:**
1. Gutenberg blocks for all components
2. Elementor widgets (basic)
3. SEO enhancements (Schema.org)
4. Performance optimization
5. Gather user feedback, feature requests
6. Build community (support forums, documentation)

```
═══════════════════════════════════════════════════════════════════════════
  PHASE 5-6: MONETIZATION (v2.0+)
  Introduce premium features as "new add-ons"
  Only after trust established, 1,000+ installs, positive reviews
═══════════════════════════════════════════════════════════════════════════
```

### Phase 5: Premium Features Launch (v2.0)

**Prerequisites before launching premium:**
- [ ] 1,000+ active installs
- [ ] 4.5+ star rating
- [ ] Positive community feedback
- [ ] Documentation complete
- [ ] Sales site ready (EDD + Software Licensing)

**Monetization Features:**
1. Paid Listings (payment packages, submission fees)
2. Featured Listings (boost visibility, sticky)
3. Claim Listings (business owner verification)
4. Subscription plans & recurring payments

**Enhanced Functionality:**
1. Maps Pro (Google Maps, clustering, directions, Street View)
2. Radius Search (proximity slider, "Near Me" geolocation)
3. Advanced Filters (faceted Ajax filtering, saved searches)
4. Analytics Dashboard (views, clicks, leads per listing)

**Communication Features:**
1. Private Messaging
2. Lead Forms with CRM integration
3. Bookings/Appointments

**Launch strategy:**
- Announce as "new optional add-ons for power users"
- Early adopter discount (30-50% for v1.x users)
- Free version unchanged — nothing removed or disabled

### Phase 6: Premium Expansion (v2.x+)
1. Elementor widgets
2. Full Gutenberg blocks
3. Comparison feature
4. SEO enhancements (Schema.org)
5. Performance optimization
6. Messaging system
7. Advanced search builder

---

## Summary

**Total Features Analyzed:** 200+
**Core Plugin Features:** ~50 (framework + generic features)
**Module-Specific Features:** ~30 per module
**Features to Avoid:** ~40

### Architecture Benefits

| Approach | Monolithic | Modular (Chosen) |
|----------|-----------|------------------|
| Install size | Large | Lean core + needed modules |
| Learning curve | Overwhelming | Progressive |
| Performance | Loaded features unused | Only active code |
| Maintenance | All features coupled | Independent updates |
| Monetization | One price fits all | Free modules + premium features |
| Flexibility | One-size-fits-all | Tailored to use case |
| WordPress.org | Single listing | Multiple free module listings |

### Key Principles

1. **Core is generic** - No assumptions about directory type in core
2. **Modules are focused** - Each module serves one directory type well
3. **API-first design** - Stable hooks/filters for module integration
4. **Progressive complexity** - Simple installs stay simple
5. **User choice** - Single-type or multi-type directories supported

The key is building a solid, extensible core with a well-documented module API, then shipping focused modules that excel at their specific use case rather than a bloated plugin trying to do everything.
