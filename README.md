# All Purpose Directory

A flexible WordPress plugin for building directory and listing websites with frontend submission, reviews, favorites, and advanced search.

**Contributors:** mdamoiseau
**Tags:** directory, listings, business directory, classifieds, job board
**Requires at least:** WordPress 6.0
**Tested up to:** WordPress 6.9
**Requires PHP:** 8.0
**Stable tag:** 1.0.0
**License:** GPLv2 or later

## Description

All Purpose Directory is a powerful and flexible plugin for creating any type of directory or listing website. Whether you need a business directory, job board, real estate listings, or classifieds site, this plugin provides all the features you need out of the box.

### Core Features

**Listings Management**

- Custom post type with full WordPress integration
- 25+ custom field types (text, email, phone, URL, date, select, checkbox, file upload, gallery, and more)
- Hierarchical categories with icons and colors
- Non-hierarchical tags for flexible labeling
- Custom post statuses including "expired" for time-limited listings
- View count tracking with bot filtering

**Frontend Submission**

- Allow users to submit listings without admin access
- Customizable submission forms with field groups
- Guest submission support (optional)
- Edit existing listings from the frontend
- Configurable default status (pending review, published, draft)
- Spam protection: honeypot fields, time-based checks, rate limiting

**User Dashboard**

- Frontend dashboard for users to manage their listings
- View listing statistics (views, favorites, inquiries)
- Edit, delete, and manage listing status
- Profile settings with avatar upload and social links
- Favorites management

**Search & Filtering**

- AJAX-powered search without page reloads
- Multiple filter types: keyword, category, tag, range, date range
- URL state persistence for shareable filtered results
- Customizable filter UI with template overrides
- Orderby options: date, title, views, random

**Reviews & Ratings**

- Star rating system (1-5 stars)
- Review title and content
- One review per user per listing
- Admin moderation workflow
- Average rating calculation cached for performance
- Rating display on listing cards

**Favorites System**

- Heart icon toggle on listing cards
- Guest favorites via cookies (optional)
- User favorites in database
- Favorites count per listing
- Merge guest favorites on login

**Contact & Inquiries**

- Contact form on single listings
- Email notifications to listing owners
- Optional admin copy
- Inquiry tracking in database
- Inquiry stats in user dashboard

**Email Notifications**

- New listing submitted (to admin)
- Listing approved/rejected (to author)
- Listing expiring soon/expired (to author)
- New review received (to listing author)
- New inquiry received (to listing author)
- Customizable HTML templates with theme override support
- Placeholder system for dynamic content

**Display Options**

- Grid view with configurable columns (2, 3, 4)
- List view with horizontal layout
- View switcher for users
- Responsive design for all screen sizes
- Template override system for theme developers

**Shortcodes**

- `[apd_listings]` - Display listings with filters
- `[apd_search_form]` - Search and filter form
- `[apd_categories]` - Category grid or list
- `[apd_submission_form]` - Frontend submission form
- `[apd_dashboard]` - User dashboard
- `[apd_favorites]` - User favorites list
- `[apd_login_form]` - Login form
- `[apd_register_form]` - Registration form

**Gutenberg Blocks**

- Listings Block - Display listings with preview
- Search Form Block - Search and filter interface
- Categories Block - Category display options

**REST API**

- Full REST API with namespace `apd/v1`
- Endpoints for listings, categories, tags, favorites, reviews, inquiries
- Authentication via WordPress nonces/cookies
- Paginated responses with metadata

**Admin Features**

- Settings page with tabbed interface
- Custom admin columns (thumbnail, category, status, views)
- Sortable and filterable listing admin
- Review moderation with bulk actions
- Debug mode for troubleshooting

**Developer Features**

- 100+ action and filter hooks
- Template override system
- Extensible field type system
- Custom filter type support
- Comprehensive PHPDoc documentation
- 2600+ unit tests

### Use Cases

- Business directories
- Job boards
- Real estate listings
- Classified ads
- Restaurant directories
- Event listings
- Service provider directories
- Member directories
- Product catalogs
- And much more!

## Installation

### Automatic Installation

1. Go to **Plugins > Add New** in your WordPress admin
2. Search for "All Purpose Directory"
3. Click "Install Now" and then "Activate"

### Manual Installation

1. Download the plugin zip file
2. Go to **Plugins > Add New > Upload Plugin**
3. Upload the zip file and click "Install Now"
4. Activate the plugin

### After Activation

1. Go to **Listings > Settings** to configure the plugin
2. Create listing categories under **Listings > Categories**
3. Add pages with shortcodes:
   - Create a page with `[apd_listings]` for the listings archive
   - Create a page with `[apd_submission_form]` for frontend submission
   - Create a page with `[apd_dashboard]` for the user dashboard
4. Start adding listings!

### Minimum Requirements

- WordPress 6.0 or higher
- PHP 8.0 or higher

## Developer Quality Checks (Docker-first)

You only need Docker installed locally. No machine-specific docker-compose stack is required.

```bash
# 1) Build test image (first run or after Dockerfile changes)
./bin/docker-test.sh build-image

# 2) Install/update Composer dependencies inside the container
./bin/docker-test.sh composer-install

# 3) Run standard quality gates
./bin/docker-test.sh lint
./bin/docker-test.sh phpcs
./bin/docker-test.sh test-unit

# 4) Run WordPress Plugin Check in an isolated ephemeral WP+MySQL stack
./bin/plugin-check-local.sh
```

Useful shortcuts:

```bash
# Run any custom command in the test container
./bin/docker-test.sh run "php -v"

# Keep plugin-check containers/resources around for debugging
KEEP_STACK=1 ./bin/plugin-check-local.sh
```

## Frequently Asked Questions

### How do I customize the listing display?

All templates can be overridden in your theme. Copy templates from `plugins/all-purpose-directory/templates/` to `your-theme/all-purpose-directory/` and modify as needed. Available templates include:

- `archive-listing.php` - Listings archive page
- `single-listing.php` - Single listing page
- `listing-card.php` - Grid view card
- `listing-card-list.php` - List view card
- `submission-form.php` - Frontend submission form
- `dashboard/*.php` - Dashboard templates
- `review/*.php` - Review templates
- `search/*.php` - Search form templates
- `emails/*.php` - Email templates

### Does this plugin work with page builders?

Yes! The plugin provides shortcodes and Gutenberg blocks that work with all major page builders including Elementor, Beaver Builder, Divi, and the native WordPress block editor.

### Can users submit listings from the frontend?

Yes, frontend submission is a core feature. Configure who can submit (anyone, logged-in users, specific roles) in **Settings > Submission**. You can also enable guest submission with email capture.

### How do I require approval for new listings?

Go to **Listings > Settings > Listings** and set "Default Listing Status" to "Pending Review". New submissions will require admin approval before becoming visible.

### Can I add custom fields to listings?

Yes! The plugin includes 25+ field types. To add custom fields, use the `apd_listing_fields` filter in your theme's `functions.php`:

```php
add_filter( 'apd_listing_fields', function( $fields ) {
    $fields['business_hours'] = [
        'type'     => 'textarea',
        'label'    => 'Business Hours',
        'required' => false,
    ];
    return $fields;
});
```

### Is the plugin translation ready?

Yes, the plugin is fully translation ready with a POT file in the `languages/` folder. All 900+ strings are properly internationalized with translator comments.

### How do I enable reviews?

Reviews are enabled by default. To disable them, go to **Listings > Settings > Listings** and uncheck "Enable Reviews". You can moderate reviews under **Listings > Reviews**.

### How do I customize email notifications?

Email templates can be overridden in your theme at `your-theme/all-purpose-directory/emails/`. You can also customize the from name, email, and which notifications are sent in **Settings > Email**.

### Can I import existing listings?

The plugin uses standard WordPress post types, so you can use any WordPress import tool. WP All Import works great for CSV/XML imports. The post type is `apd_listing` and meta keys use the `_apd_` prefix.

### Is there a REST API?

Yes, the plugin provides a full REST API at `/wp-json/apd/v1/` with endpoints for listings, categories, tags, favorites, reviews, and inquiries. Authentication uses WordPress nonces for logged-in users.

### How do I add a contact form to listings?

The contact form is automatically displayed on single listing pages when enabled. Go to **Listings > Settings > Listings** and check "Enable Contact Form". Customize behavior with the `apd_contact_*` filters.

### Can users save favorite listings?

Yes! The favorites system is enabled by default. Users can click the heart icon on any listing card. Logged-in users have favorites saved to their account; guests can optionally save favorites via cookies.

### How do I change the number of columns in grid view?

Go to **Listings > Settings > Display** and set "Grid Columns" to 2, 3, or 4. You can also use the `columns` attribute on the shortcode: `[apd_listings columns="3"]`.

### Does the plugin create custom database tables?

No, the plugin uses WordPress native tables (posts, postmeta, terms, comments) for maximum compatibility. Inquiry tracking uses a custom post type `apd_inquiry`.

## Changelog

### 1.0.0

Initial release with full feature set:

- Custom post type for listings with 25+ field types
- Hierarchical categories and non-hierarchical tags
- Frontend submission with spam protection
- User dashboard with profile management
- Favorites system with guest support
- Reviews and ratings with moderation
- Contact forms with inquiry tracking
- Email notifications (7 types)
- Admin settings with 6 configuration tabs
- REST API with full CRUD operations
- Gutenberg blocks and shortcodes
- Template override system
- 100+ hooks for developers
- Full internationalization support

## Credits

- Icons: [Dashicons](https://developer.wordpress.org/resource/dashicons/)
- Development: [Michael Damoiseau](https://damoiseau.xyz)

## Privacy Policy

All Purpose Directory stores the following data:

- **Listings**: Stored as WordPress posts with metadata
- **Reviews**: Stored as WordPress comments with rating metadata
- **Favorites**: Stored in user meta for logged-in users, cookies for guests
- **Inquiries**: Stored as custom posts (optional, configurable)
- **View counts**: Stored in post metadata (anonymized, no user tracking)

The plugin does not send any data to external servers. All data remains in your WordPress database.

For GDPR compliance, listing owners can export their data via WordPress's built-in export tool, and users can request deletion of their reviews, favorites, and inquiries through standard WordPress privacy tools.

## License

This project is licensed under the GPLv2 or later - see the [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html) for details.
