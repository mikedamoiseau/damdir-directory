# All Purpose Directory - Developer Documentation

This guide covers extending and customizing All Purpose Directory for developers.

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Action Hooks](#action-hooks)
3. [Filter Hooks](#filter-hooks)
4. [Custom Fields](#custom-fields)
5. [Custom Filters](#custom-filters)
6. [Custom Views](#custom-views)
7. [Template System](#template-system)
8. [REST API](#rest-api)
9. [Helper Functions](#helper-functions)
10. [WP-CLI Commands](#wp-cli-commands)
11. [Database Schema](#database-schema)
12. [Coding Standards](#coding-standards)

---

## Architecture Overview

### Plugin Structure

```
all-purpose-directory/
├── src/
│   ├── Core/           # Plugin bootstrap, assets, templates
│   ├── Admin/          # Admin pages, meta boxes, settings
│   ├── Listing/        # Post type, repository, queries
│   ├── Fields/         # Field registry, renderer, validator, types
│   ├── Taxonomy/       # Categories and tags
│   ├── Search/         # Search query, filters
│   ├── Frontend/       # Submission, display, dashboard
│   ├── Shortcode/      # Shortcode manager and implementations
│   ├── Blocks/         # Gutenberg blocks
│   ├── User/           # Favorites, profile
│   ├── Review/         # Reviews and ratings
│   ├── Contact/        # Contact forms, inquiry tracking
│   ├── Email/          # Email manager, templates
│   ├── Api/            # REST API controller, endpoints
│   ├── CLI/            # WP-CLI commands
│   └── Contracts/      # Interfaces
├── templates/          # Theme-overridable templates
├── assets/             # CSS, JS, images
└── includes/           # Global helper functions
```

### Key Patterns

- **Singleton Pattern**: Core classes use `get_instance()` method
- **Registry Pattern**: Fields, filters, views, shortcodes, blocks
- **Template Override**: Theme can override any template
- **Hook System**: 150+ actions and 180+ filters for extensibility

### Naming Conventions

- **Prefix**: `apd_` for functions, `APD` for classes
- **Post Type**: `apd_listing`
- **Taxonomies**: `apd_category`, `apd_tag`
- **Meta Keys**: `_apd_{field_name}`
- **Options**: `apd_options`
- **Nonces**: `apd_{action}_nonce`
- **Text Domain**: `all-purpose-directory`

---

## Action Hooks

The table below is the hook inventory currently present in the codebase. `Dynamic` entries are literal runtime hook patterns from the source and should be treated as exact strings when matching plugin integrations against this API version.

| Hook | Type | Source |
| --- | --- | --- |
| `apd_activated` | Static | `src/Core/Activator.php:48` |
| `apd_after_admin_fields` | Static | `src/Fields/FieldRenderer.php:817` |
| `apd_after_ajax_filter` | Static | `src/Api/AjaxHandler.php:192` |
| `apd_after_archive` | Static | `src/Core/TemplateLoader.php:649` |
| `apd_after_archive_loop` | Static | `src/Core/TemplateLoader.php:615` |
| `apd_after_archive_search_form` | Static | `src/Core/TemplateLoader.php:546` |
| `apd_after_block_{$this->name}` | Dynamic | `src/Blocks/AbstractBlock.php:233` |
| `apd_after_categories_shortcode` | Static | `src/Shortcode/CategoriesShortcode.php:202` |
| `apd_after_change_listing_status` | Static | `src/Frontend/Dashboard/MyListings.php:664` |
| `apd_after_dashboard` | Static | `src/Frontend/Dashboard/Dashboard.php:426` |
| `apd_after_delete_demo_data` | Static | `src/Admin/DemoData/DemoDataPage.php:691`, `src/Admin/DemoData/DemoDataTracker.php:780` |
| `apd_after_delete_listing` | Static | `src/Frontend/Dashboard/MyListings.php:556` |
| `apd_after_edit_not_allowed` | Static | `templates/submission/edit-not-allowed.php:63` |
| `apd_after_favorites_shortcode` | Static | `src/Shortcode/FavoritesShortcode.php:241` |
| `apd_after_filters` | Static | `src/Search/FilterRenderer.php:228`, `templates/search/search-form.php:58` |
| `apd_after_frontend_fields` | Static | `src/Fields/FieldRenderer.php:880` |
| `apd_after_generate_demo_data` | Static | `src/CLI/DemoDataCommand.php:331`, `src/Admin/DemoData/DemoDataPage.php:628` |
| `apd_after_get_template` | Static | `src/Core/Template.php:200` |
| `apd_after_listing_save` | Static | `src/Admin/ListingMetaBox.php:228` |
| `apd_after_listings_shortcode` | Static | `src/Shortcode/ListingsShortcode.php:276` |
| `apd_after_login_form_shortcode` | Static | `src/Shortcode/LoginFormShortcode.php:180` |
| `apd_after_register_form_shortcode` | Static | `src/Shortcode/RegisterFormShortcode.php:165` |
| `apd_after_related_listings` | Static | `src/Core/TemplateLoader.php:797`, `templates/single-listing.php:407` |
| `apd_after_review_form` | Static | `templates/review/review-form.php:328` |
| `apd_after_reviews_section` | Static | `src/Review/ReviewForm.php:182` |
| `apd_after_save_profile` | Static | `src/Frontend/Dashboard/Profile.php:538` |
| `apd_after_search_form` | Static | `src/Search/FilterRenderer.php:181` |
| `apd_after_search_form_shortcode` | Static | `src/Shortcode/SearchFormShortcode.php:190` |
| `apd_after_send_email` | Static | `src/Email/EmailManager.php:403` |
| `apd_after_settings_page` | Static | `src/Admin/Settings.php:1374` |
| `apd_after_settings_tab` | Static | `src/Admin/Settings.php:1450` |
| `apd_after_shortcode_{$this->tag}` | Dynamic | `src/Shortcode/AbstractShortcode.php:167` |
| `apd_after_single_listing` | Static | `templates/single-listing.php:431` |
| `apd_after_submission` | Static | `src/Frontend/Submission/SubmissionHandler.php:298` |
| `apd_after_submission_form` | Static | `src/Frontend/Submission/SubmissionForm.php:594` |
| `apd_after_submission_form_shortcode` | Static | `src/Shortcode/SubmissionFormShortcode.php:271` |
| `apd_after_submission_success` | Static | `templates/submission/submission-success.php:111` |
| `apd_after_trash_listing` | Static | `src/Frontend/Dashboard/MyListings.php:597` |
| `apd_after_validate_fields` | Static | `src/Fields/FieldValidator.php:257` |
| `apd_all_ratings_recalculated` | Static | `src/Review/RatingCalculator.php:314` |
| `apd_archive_wrapper_end` | Static | `src/Core/TemplateLoader.php:638` |
| `apd_archive_wrapper_start` | Static | `src/Core/TemplateLoader.php:508` |
| `apd_avatar_uploaded` | Static | `src/Frontend/Dashboard/Profile.php:608` |
| `apd_before_ajax_filter` | Static | `src/Api/AjaxHandler.php:87` |
| `apd_before_archive` | Static | `src/Core/TemplateLoader.php:497` |
| `apd_before_archive_loop` | Static | `src/Core/TemplateLoader.php:575` |
| `apd_before_archive_search_form` | Static | `src/Core/TemplateLoader.php:530` |
| `apd_before_block_{$this->name}` | Dynamic | `src/Blocks/AbstractBlock.php:211` |
| `apd_before_categories_shortcode` | Static | `src/Shortcode/CategoriesShortcode.php:186` |
| `apd_before_change_listing_status` | Static | `src/Frontend/Dashboard/MyListings.php:643` |
| `apd_before_dashboard` | Static | `src/Frontend/Dashboard/Dashboard.php:402` |
| `apd_before_delete_demo_data` | Static | `src/Admin/DemoData/DemoDataPage.php:680`, `src/Admin/DemoData/DemoDataTracker.php:751` |
| `apd_before_delete_listing` | Static | `src/Frontend/Dashboard/MyListings.php:543` |
| `apd_before_favorites_shortcode` | Static | `src/Shortcode/FavoritesShortcode.php:218` |
| `apd_before_filters` | Static | `src/Search/FilterRenderer.php:213`, `templates/search/search-form.php:40` |
| `apd_before_generate_demo_data` | Static | `src/CLI/DemoDataCommand.php:199`, `src/Admin/DemoData/DemoDataPage.php:606` |
| `apd_before_get_template` | Static | `src/Core/Template.php:187` |
| `apd_before_inquiry_delete` | Static | `src/Contact/InquiryTracker.php:557` |
| `apd_before_listing_save` | Static | `src/Admin/ListingMetaBox.php:200` |
| `apd_before_listing_update` | Static | `src/Frontend/Submission/SubmissionHandler.php:771` |
| `apd_before_listings_shortcode` | Static | `src/Shortcode/ListingsShortcode.php:253` |
| `apd_before_login_form_shortcode` | Static | `src/Shortcode/LoginFormShortcode.php:162` |
| `apd_before_register_form_shortcode` | Static | `src/Shortcode/RegisterFormShortcode.php:139` |
| `apd_before_related_listings` | Static | `src/Core/TemplateLoader.php:777`, `templates/single-listing.php:381` |
| `apd_before_review_create` | Static | `src/Review/ReviewManager.php:269` |
| `apd_before_review_delete` | Static | `src/Review/ReviewManager.php:435` |
| `apd_before_review_form` | Static | `templates/review/review-form.php:74` |
| `apd_before_review_process` | Static | `src/Review/ReviewHandler.php:342` |
| `apd_before_review_update` | Static | `src/Review/ReviewManager.php:374` |
| `apd_before_reviews_section` | Static | `src/Review/ReviewForm.php:170` |
| `apd_before_save_profile` | Static | `src/Frontend/Dashboard/Profile.php:487` |
| `apd_before_search_form` | Static | `src/Search/FilterRenderer.php:116` |
| `apd_before_search_form_shortcode` | Static | `src/Shortcode/SearchFormShortcode.php:170` |
| `apd_before_send_contact` | Static | `src/Contact/ContactHandler.php:227` |
| `apd_before_send_email` | Static | `src/Email/EmailManager.php:389` |
| `apd_before_settings_page` | Static | `src/Admin/Settings.php:1318` |
| `apd_before_settings_tab` | Static | `src/Admin/Settings.php:1431` |
| `apd_before_shortcode_{$this->tag}` | Dynamic | `src/Shortcode/AbstractShortcode.php:142` |
| `apd_before_single_listing` | Static | `templates/single-listing.php:26` |
| `apd_before_submission` | Static | `src/Frontend/Submission/SubmissionHandler.php:261` |
| `apd_before_submission_form` | Static | `src/Frontend/Submission/SubmissionForm.php:570` |
| `apd_before_submission_form_shortcode` | Static | `src/Shortcode/SubmissionFormShortcode.php:257` |
| `apd_before_trash_listing` | Static | `src/Frontend/Dashboard/MyListings.php:584` |
| `apd_block_registered` | Static | `src/Blocks/BlockManager.php:193` |
| `apd_block_unregistered` | Static | `src/Blocks/BlockManager.php:225` |
| `apd_blocks_init` | Static | `src/Blocks/BlockManager.php:162` |
| `apd_cache_cleared` | Static | `src/Core/Performance.php:726` |
| `apd_category_cache_invalidated` | Static | `src/Core/Performance.php:595` |
| `apd_contact_form_after_fields` | Static | `templates/contact/contact-form.php:186` |
| `apd_contact_form_init` | Static | `src/Contact/ContactForm.php:135` |
| `apd_contact_handler_init` | Static | `src/Contact/ContactHandler.php:116` |
| `apd_contact_sent` | Static | `src/Contact/ContactHandler.php:247` |
| `apd_contact_spam_attempt_detected` | Static | `src/Contact/ContactHandler.php:924` |
| `apd_dashboard_after_content` | Static | `src/Frontend/Dashboard/Dashboard.php:513` |
| `apd_dashboard_before_content` | Static | `src/Frontend/Dashboard/Dashboard.php:475` |
| `apd_dashboard_end` | Static | `templates/dashboard/dashboard.php:89` |
| `apd_dashboard_start` | Static | `templates/dashboard/dashboard.php:39` |
| `apd_dashboard_{$tab}_content` | Dynamic | `src/Frontend/Dashboard/Dashboard.php:487` |
| `apd_deactivated` | Static | `src/Core/Deactivator.php:33` |
| `apd_demo_data_init` | Static | `src/Admin/DemoData/DemoDataPage.php:146` |
| `apd_demo_provider_registered` | Static | `src/Admin/DemoData/DemoDataProviderRegistry.php:164` |
| `apd_demo_provider_unregistered` | Static | `src/Admin/DemoData/DemoDataProviderRegistry.php:195` |
| `apd_demo_providers_init` | Static | `src/Admin/DemoData/DemoDataProviderRegistry.php:113` |
| `apd_email_manager_init` | Static | `src/Email/EmailManager.php:144` |
| `apd_favorite_added` | Static | `src/User/Favorites.php:189`, `src/User/Favorites.php:800` |
| `apd_favorite_removed` | Static | `src/User/Favorites.php:244`, `src/User/Favorites.php:837` |
| `apd_favorites_cleared` | Static | `src/User/Favorites.php:397`, `src/User/Favorites.php:911` |
| `apd_favorites_end` | Static | `templates/dashboard/favorites.php:134` |
| `apd_favorites_init` | Static | `src/User/Favorites.php:131` |
| `apd_favorites_start` | Static | `templates/dashboard/favorites.php:39` |
| `apd_field_registered` | Static | `src/Fields/FieldRegistry.php:258` |
| `apd_field_type_registered` | Static | `src/Fields/FieldRegistry.php:147` |
| `apd_field_unregistered` | Static | `src/Fields/FieldRegistry.php:289` |
| `apd_filter_registered` | Static | `src/Search/FilterRegistry.php:153` |
| `apd_filter_unregistered` | Static | `src/Search/FilterRegistry.php:184` |
| `apd_init` | Static | `src/Core/Plugin.php:93` |
| `apd_inquiry_logged` | Static | `src/Contact/InquiryTracker.php:196` |
| `apd_inquiry_marked_read` | Static | `src/Contact/InquiryTracker.php:505` |
| `apd_inquiry_marked_unread` | Static | `src/Contact/InquiryTracker.php:530` |
| `apd_inquiry_tracker_init` | Static | `src/Contact/InquiryTracker.php:104` |
| `apd_listing_cache_invalidated` | Static | `src/Core/Performance.php:624` |
| `apd_listing_card_body` | Static | `templates/listing-card-list.php:197`, `templates/listing-card.php:195` |
| `apd_listing_card_end` | Static | `templates/listing-card-list.php:250`, `templates/listing-card.php:228` |
| `apd_listing_card_footer` | Static | `templates/listing-card-list.php:232`, `templates/listing-card.php:211` |
| `apd_listing_card_image` | Static | `templates/listing-card-list.php:127`, `templates/listing-card.php:133` |
| `apd_listing_card_start` | Static | `templates/listing-card-list.php:101`, `templates/listing-card.php:93` |
| `apd_listing_fields_saved` | Static | `src/Frontend/Submission/SubmissionHandler.php:999` |
| `apd_listing_processed` | Static | `includes/functions.php:2023` |
| `apd_listing_saved` | Static | `src/Frontend/Submission/SubmissionHandler.php:812` |
| `apd_listing_status_changed` | Static | `src/Core/Plugin.php:432` |
| `apd_listing_taxonomies_assigned` | Static | `src/Frontend/Submission/SubmissionHandler.php:901` |
| `apd_listing_type_registered` | Static | `src/Taxonomy/ListingTypeTaxonomy.php:197` |
| `apd_listing_viewed` | Static | `includes/functions.php:1419` |
| `apd_loaded` | Static | `src/Core/Plugin.php:293` |
| `apd_module_registered` | Static | `src/Module/ModuleRegistry.php:230` |
| `apd_module_unregistered` | Static | `src/Module/ModuleRegistry.php:289` |
| `apd_modules_admin_init` | Static | `src/Module/ModulesAdminPage.php:108` |
| `apd_modules_init` | Static | `src/Module/ModuleRegistry.php:127` |
| `apd_modules_loaded` | Static | `src/Module/ModuleRegistry.php:136` |
| `apd_my_listings_end` | Static | `templates/dashboard/my-listings.php:196` |
| `apd_my_listings_start` | Static | `templates/dashboard/my-listings.php:46` |
| `apd_profile_end` | Static | `templates/dashboard/profile.php:249` |
| `apd_profile_saved` | Static | `src/Frontend/Dashboard/Profile.php:366` |
| `apd_profile_start` | Static | `templates/dashboard/profile.php:44` |
| `apd_rating_calculated` | Static | `src/Review/RatingCalculator.php:197` |
| `apd_rating_calculator_init` | Static | `src/Review/RatingCalculator.php:135` |
| `apd_rating_invalidated` | Static | `src/Review/RatingCalculator.php:342` |
| `apd_register_filters` | Static | `src/Core/Plugin.php:692` |
| `apd_register_form_fields` | Static | `src/Shortcode/RegisterFormShortcode.php:235` |
| `apd_register_rest_routes` | Static | `src/Api/RestController.php:165` |
| `apd_register_settings` | Static | `src/Admin/Settings.php:245` |
| `apd_render_review_form` | Static | `templates/review/reviews-section.php:114` |
| `apd_rest_after_create_listing` | Static | `src/Api/Endpoints/ListingsEndpoint.php:370` |
| `apd_rest_after_delete_listing` | Static | `src/Api/Endpoints/ListingsEndpoint.php:559` |
| `apd_rest_after_update_listing` | Static | `src/Api/Endpoints/ListingsEndpoint.php:498` |
| `apd_rest_api_init` | Static | `src/Api/RestController.php:130` |
| `apd_rest_before_create_listing` | Static | `src/Api/Endpoints/ListingsEndpoint.php:316` |
| `apd_rest_before_delete_listing` | Static | `src/Api/Endpoints/ListingsEndpoint.php:536` |
| `apd_rest_before_update_listing` | Static | `src/Api/Endpoints/ListingsEndpoint.php:440` |
| `apd_rest_endpoint_registered` | Static | `src/Api/RestController.php:226` |
| `apd_rest_endpoint_unregistered` | Static | `src/Api/RestController.php:251` |
| `apd_rest_routes_registered` | Static | `src/Api/RestController.php:181` |
| `apd_review_approved` | Static | `src/Review/ReviewManager.php:670` |
| `apd_review_created` | Static | `src/Review/ReviewManager.php:298` |
| `apd_review_deleted` | Static | `src/Review/ReviewManager.php:448` |
| `apd_review_display_init` | Static | `src/Review/ReviewDisplay.php:117` |
| `apd_review_form_before_submit` | Static | `templates/review/review-form.php:285` |
| `apd_review_form_created` | Static | `src/Review/ReviewHandler.php:413` |
| `apd_review_form_end` | Static | `templates/review/review-form.php:311` |
| `apd_review_form_init` | Static | `src/Review/ReviewForm.php:138` |
| `apd_review_form_start` | Static | `templates/review/review-form.php:166` |
| `apd_review_form_updated` | Static | `src/Review/ReviewHandler.php:377` |
| `apd_review_handler_init` | Static | `src/Review/ReviewHandler.php:120` |
| `apd_review_item_footer` | Static | `templates/review/review-item.php:113` |
| `apd_review_rejected` | Static | `src/Review/ReviewManager.php:701` |
| `apd_review_updated` | Static | `src/Review/ReviewManager.php:406` |
| `apd_reviews_init` | Static | `src/Review/ReviewManager.php:138` |
| `apd_reviews_section_after_form` | Static | `templates/review/reviews-section.php:127` |
| `apd_reviews_section_after_header` | Static | `templates/review/reviews-section.php:79` |
| `apd_reviews_section_before_form` | Static | `templates/review/reviews-section.php:99` |
| `apd_reviews_section_end` | Static | `templates/review/reviews-section.php:166` |
| `apd_reviews_section_start` | Static | `templates/review/reviews-section.php:52` |
| `apd_search_query_modified` | Static | `src/Search/SearchQuery.php:191` |
| `apd_settings_init` | Static | `src/Admin/Settings.php:142` |
| `apd_shortcode_registered` | Static | `src/Shortcode/ShortcodeManager.php:159` |
| `apd_shortcode_unregistered` | Static | `src/Shortcode/ShortcodeManager.php:191` |
| `apd_shortcodes_init` | Static | `src/Shortcode/ShortcodeManager.php:128` |
| `apd_single_listing_after_content` | Static | `src/Core/TemplateLoader.php:701`, `templates/single-listing.php:198` |
| `apd_single_listing_after_fields` | Static | `src/Core/TemplateLoader.php:730`, `templates/single-listing.php:225` |
| `apd_single_listing_author` | Static | `templates/single-listing.php:316` |
| `apd_single_listing_before_content` | Static | `templates/single-listing.php:180` |
| `apd_single_listing_contact_form` | Static | `src/Core/TemplateLoader.php:756`, `templates/single-listing.php:330` |
| `apd_single_listing_end` | Static | `templates/single-listing.php:357` |
| `apd_single_listing_header` | Static | `templates/single-listing.php:144` |
| `apd_single_listing_image` | Static | `templates/single-listing.php:167` |
| `apd_single_listing_meta` | Static | `src/Core/TemplateLoader.php:705`, `templates/single-listing.php:132` |
| `apd_single_listing_reviews` | Static | `src/Core/TemplateLoader.php:878`, `templates/comments-listing.php:42` (+1 more) |
| `apd_single_listing_sidebar_end` | Static | `templates/single-listing.php:341` |
| `apd_single_listing_sidebar_start` | Static | `templates/single-listing.php:270` |
| `apd_single_listing_start` | Static | `templates/single-listing.php:88` |
| `apd_single_wrapper_end` | Static | `templates/single-listing.php:420` |
| `apd_single_wrapper_start` | Static | `templates/single-listing.php:37` |
| `apd_spam_attempt_detected` | Static | `src/Frontend/Submission/SubmissionHandler.php:1568` |
| `apd_submission_form_after_basic_fields` | Static | `templates/submission/submission-form.php:250` |
| `apd_submission_form_after_custom_fields` | Static | `templates/submission/submission-form.php:274` |
| `apd_submission_form_after_image` | Static | `templates/submission/submission-form.php:343` |
| `apd_submission_form_after_taxonomies` | Static | `templates/submission/submission-form.php:317` |
| `apd_submission_form_before_submit` | Static | `templates/submission/submission-form.php:397` |
| `apd_submission_form_end` | Static | `templates/submission/submission-form.php:415` |
| `apd_submission_form_start` | Static | `templates/submission/submission-form.php:81` |
| `apd_textdomain_loaded` | Static | `src/Core/Plugin.php:326` |
| `apd_user_registered` | Static | `src/Shortcode/RegisterFormShortcode.php:326` |
| `apd_validate_review` | Static | `src/Review/ReviewHandler.php:489` |
| `apd_validate_submission` | Static | `src/Frontend/Submission/SubmissionHandler.php:628` |
| `apd_view_registered` | Static | `src/Frontend/Display/ViewRegistry.php:153` |
| `apd_view_unregistered` | Static | `src/Frontend/Display/ViewRegistry.php:182` |
| `apd_views_init` | Static | `src/Frontend/Display/ViewRegistry.php:126` |

---

## Filter Hooks

The table below is the filter inventory currently present in the codebase. `Dynamic` entries are literal runtime hook patterns from the source.

| Hook | Type | Source |
| --- | --- | --- |
| `apd_admin_script_data` | Static | `src/Core/Assets.php:303` |
| `apd_ajax_filter_response` | Static | `src/Api/AjaxHandler.php:185` |
| `apd_archive_content` | Static | `src/Core/TemplateLoader.php:660` |
| `apd_archive_description` | Static | `src/Core/TemplateLoader.php:308` |
| `apd_archive_title` | Static | `src/Core/TemplateLoader.php:281` |
| `apd_author_can_review_own_listing` | Static | `src/Review/ReviewForm.php:343` |
| `apd_before_validate_field` | Static | `src/Fields/FieldValidator.php:150` |
| `apd_block_args` | Static | `src/Blocks/AbstractBlock.php:169` |
| `apd_block_{$this->name}_args` | Dynamic | `src/Blocks/AbstractBlock.php:170` |
| `apd_block_{$this->name}_output` | Dynamic | `src/Blocks/AbstractBlock.php:224` |
| `apd_blocks_editor_data` | Static | `src/Blocks/BlockManager.php:393` |
| `apd_bypass_spam_protection` | Static | `src/Frontend/Submission/SubmissionHandler.php:1192`, `includes/functions.php:2451` |
| `apd_cache_expiration` | Static | `src/Core/Performance.php:577` |
| `apd_can_show_review_form` | Static | `src/Review/ReviewForm.php:358` |
| `apd_categories_block_no_results_message` | Static | `src/Blocks/CategoriesBlock.php:378` |
| `apd_categories_block_query_args` | Static | `src/Blocks/CategoriesBlock.php:226` |
| `apd_categories_shortcode_classes` | Static | `src/Shortcode/CategoriesShortcode.php:420` |
| `apd_categories_shortcode_no_results_message` | Static | `src/Shortcode/CategoriesShortcode.php:440` |
| `apd_categories_shortcode_query_args` | Static | `src/Shortcode/CategoriesShortcode.php:254` |
| `apd_categories_with_count_args` | Static | `includes/functions.php:218` |
| `apd_category_listings_query_args` | Static | `includes/functions.php:194` |
| `apd_contact_admin_email` | Static | `src/Contact/ContactHandler.php:513` |
| `apd_contact_bypass_spam_protection` | Static | `src/Contact/ContactHandler.php:561` |
| `apd_contact_email_headers` | Static | `src/Contact/ContactHandler.php:407` |
| `apd_contact_email_message` | Static | `src/Contact/ContactHandler.php:389` |
| `apd_contact_email_subject` | Static | `src/Contact/ContactHandler.php:376` |
| `apd_contact_email_to` | Static | `src/Contact/ContactHandler.php:357` |
| `apd_contact_form_args` | Static | `src/Contact/ContactForm.php:359` |
| `apd_contact_form_classes` | Static | `src/Contact/ContactForm.php:295` |
| `apd_contact_form_html` | Static | `src/Contact/ContactForm.php:390` |
| `apd_contact_honeypot_field_name` | Static | `src/Contact/ContactHandler.php:627` |
| `apd_contact_min_time` | Static | `src/Contact/ContactHandler.php:689` |
| `apd_contact_rate_limit` | Static | `src/Contact/ContactHandler.php:713` |
| `apd_contact_rate_period` | Static | `src/Contact/ContactHandler.php:723` |
| `apd_contact_send_admin_copy` | Static | `src/Contact/ContactHandler.php:492` |
| `apd_contact_spam_check` | Static | `src/Contact/ContactHandler.php:599` |
| `apd_contact_trusted_proxies` | Static | `src/Contact/ContactHandler.php:819` |
| `apd_contact_validation_errors` | Static | `src/Contact/ContactHandler.php:329` |
| `apd_dashboard_args` | Static | `src/Frontend/Dashboard/Dashboard.php:411` |
| `apd_dashboard_classes` | Static | `src/Frontend/Dashboard/Dashboard.php:642` |
| `apd_dashboard_html` | Static | `src/Frontend/Dashboard/Dashboard.php:436` |
| `apd_dashboard_register_url` | Static | `templates/dashboard/login-required.php:29` |
| `apd_dashboard_show_register` | Static | `templates/dashboard/login-required.php:38` |
| `apd_dashboard_stat_items` | Static | `templates/dashboard/stats.php:31` |
| `apd_dashboard_stats` | Static | `src/Frontend/Dashboard/Dashboard.php:280` |
| `apd_dashboard_tabs` | Static | `src/Frontend/Dashboard/Dashboard.php:219` |
| `apd_dashboard_url` | Static | `src/Frontend/Dashboard/Dashboard.php:664`, `includes/functions.php:2596` |
| `apd_default_listing_status` | Static | `includes/functions.php:2044` |
| `apd_default_listing_type` | Static | `src/Taxonomy/ListingTypeTaxonomy.php:230` |
| `apd_default_pages` | Static | `src/Core/Activator.php:173` |
| `apd_demo_category_data` | Static | `src/Admin/DemoData/DemoDataGenerator.php:180`, `src/Admin/DemoData/DataSets/CategoryData.php:197` |
| `apd_demo_default_counts` | Static | `src/Admin/DemoData/DemoDataPage.php:802` |
| `apd_demo_listing_data` | Static | `src/Admin/DemoData/DemoDataGenerator.php:397` |
| `apd_edit_listing_status` | Static | `src/Frontend/Submission/SubmissionHandler.php:846` |
| `apd_edit_listing_url` | Static | `includes/functions.php:2160` |
| `apd_edit_not_allowed_args` | Static | `src/Shortcode/SubmissionFormShortcode.php:360` |
| `apd_email_admin_email` | Static | `src/Email/EmailManager.php:496` |
| `apd_email_button_color` | Static | `templates/emails/email-wrapper.php:46` |
| `apd_email_from_email` | Static | `src/Email/EmailManager.php:475` |
| `apd_email_from_name` | Static | `src/Email/EmailManager.php:454` |
| `apd_email_header_color` | Static | `templates/emails/email-wrapper.php:30` |
| `apd_email_header_text_color` | Static | `templates/emails/email-wrapper.php:38` |
| `apd_email_headers` | Static | `src/Email/EmailManager.php:377` |
| `apd_email_message` | Static | `src/Email/EmailManager.php:366` |
| `apd_email_notification_enabled` | Static | `src/Email/EmailManager.php:515` |
| `apd_email_plain_text_message` | Static | `src/Email/EmailManager.php:999` |
| `apd_email_replace_placeholders` | Static | `src/Email/EmailManager.php:307` |
| `apd_email_subject` | Static | `src/Email/EmailManager.php:355` |
| `apd_email_to` | Static | `src/Email/EmailManager.php:345` |
| `apd_expiration_cron_batch_size` | Static | `src/Core/Plugin.php:470` |
| `apd_expiration_cron_lock_ttl` | Static | `src/Core/Plugin.php:455` |
| `apd_favorite_button_classes` | Static | `src/User/FavoriteToggle.php:373` |
| `apd_favorite_button_html` | Static | `src/User/FavoriteToggle.php:415` |
| `apd_favorite_listings_batch_size` | Static | `includes/functions.php:3164` |
| `apd_favorite_listings_query_args` | Static | `includes/functions.php:3157`, `includes/functions.php:3184` |
| `apd_favorites_empty_browse_url` | Static | `src/Frontend/Dashboard/FavoritesPage.php:391` |
| `apd_favorites_enabled` | Static | `src/Shortcode/FavoritesShortcode.php:147` |
| `apd_favorites_output` | Static | `src/Shortcode/FavoritesShortcode.php:160` |
| `apd_favorites_page_args` | Static | `src/Frontend/Dashboard/FavoritesPage.php:209` |
| `apd_favorites_page_query_args` | Static | `src/Frontend/Dashboard/FavoritesPage.php:265` |
| `apd_favorites_require_login` | Static | `src/User/Favorites.php:420` |
| `apd_favorites_shortcode_no_results_message` | Static | `src/Shortcode/FavoritesShortcode.php:408` |
| `apd_favorites_shortcode_pagination_args` | Static | `src/Shortcode/FavoritesShortcode.php:370` |
| `apd_favorites_shortcode_query_args` | Static | `src/Shortcode/FavoritesShortcode.php:194` |
| `apd_field_group_wrapper_class` | Static | `src/Fields/FieldRenderer.php:698` |
| `apd_field_wrapper_class` | Static | `src/Fields/FieldRenderer.php:313` |
| `apd_filter_options` | Static | `src/Search/Filters/AbstractFilter.php:175` |
| `apd_filter_wrapper_class` | Static | `src/Search/Filters/AbstractFilter.php:344` |
| `apd_frontend_script_data` | Static | `src/Core/Assets.php:276` |
| `apd_get_field` | Static | `src/Fields/FieldRegistry.php:317` |
| `apd_get_fields` | Static | `src/Fields/FieldRegistry.php:415` |
| `apd_get_module` | Static | `src/Module/ModuleRegistry.php:317` |
| `apd_get_modules` | Static | `src/Module/ModuleRegistry.php:379` |
| `apd_get_template_part` | Static | `src/Core/Template.php:254` |
| `apd_grid_responsive_columns` | Static | `src/Frontend/Display/GridView.php:238` |
| `apd_guest_favorites_enabled` | Static | `src/User/Favorites.php:438` |
| `apd_honeypot_field_name` | Static | `src/Frontend/Submission/SubmissionForm.php:695`, `src/Frontend/Submission/SubmissionHandler.php:1260` (+2 more) |
| `apd_inquiry_post_data` | Static | `src/Contact/InquiryTracker.php:244` |
| `apd_inquiry_post_type_args` | Static | `src/Contact/InquiryTracker.php:145` |
| `apd_is_plugin_admin_screen` | Static | `src/Core/Assets.php:238` |
| `apd_list_responsive_layout` | Static | `src/Frontend/Display/ListView.php:211` |
| `apd_listing_can_receive_contact` | Static | `src/Contact/ContactForm.php:425` |
| `apd_listing_card_classes` | Static | `templates/listing-card-list.php:88`, `templates/listing-card.php:80` |
| `apd_listing_card_data` | Static | `templates/listing-card-list.php:54`, `templates/listing-card.php:48` |
| `apd_listing_field_value` | Static | `includes/functions.php:532` |
| `apd_listing_fields` | Static | `src/Fields/FieldRegistry.php:624` |
| `apd_listing_inquiries_query_args` | Static | `src/Contact/InquiryTracker.php:336` |
| `apd_listings_block_no_results_message` | Static | `src/Blocks/ListingsBlock.php:377` |
| `apd_listings_block_pagination_args` | Static | `src/Blocks/ListingsBlock.php:347` |
| `apd_listings_block_query_args` | Static | `src/Blocks/ListingsBlock.php:171` |
| `apd_listings_shortcode_no_results_message` | Static | `src/Shortcode/ListingsShortcode.php:440` |
| `apd_listings_shortcode_pagination_args` | Static | `src/Shortcode/ListingsShortcode.php:409` |
| `apd_listings_shortcode_query_args` | Static | `src/Shortcode/ListingsShortcode.php:211` |
| `apd_locate_template` | Static | `src/Core/Template.php:150` |
| `apd_login_form_shortcode_args` | Static | `src/Shortcode/LoginFormShortcode.php:239` |
| `apd_my_listings_actions` | Static | `src/Frontend/Dashboard/MyListings.php:948` |
| `apd_my_listings_args` | Static | `src/Frontend/Dashboard/MyListings.php:221` |
| `apd_my_listings_query_args` | Static | `src/Frontend/Dashboard/MyListings.php:311` |
| `apd_new_listing_post_data` | Static | `src/Frontend/Submission/SubmissionHandler.php:783` |
| `apd_orderby_options` | Static | `src/Search/SearchQuery.php:531` |
| `apd_pagination_args` | Static | `src/Core/TemplateLoader.php:457` |
| `apd_profile_args` | Static | `src/Frontend/Dashboard/Profile.php:207` |
| `apd_profile_user_data` | Static | `src/Frontend/Dashboard/Profile.php:262` |
| `apd_rating_precision` | Static | `src/Review/RatingCalculator.php:378` |
| `apd_rating_star_count` | Static | `src/Review/RatingCalculator.php:360` |
| `apd_rating_summary_data` | Static | `src/Review/ReviewDisplay.php:247` |
| `apd_register_default_fields` | Static | `src/Fields/FieldRegistry.php:583` |
| `apd_register_field_config` | Static | `src/Fields/FieldRegistry.php:246` |
| `apd_register_form_errors` | Static | `src/Shortcode/RegisterFormShortcode.php:300` |
| `apd_register_module_config` | Static | `src/Module/ModuleRegistry.php:218` |
| `apd_related_listings` | Static | `includes/functions.php:1365` |
| `apd_related_listings_args` | Static | `includes/functions.php:1352` |
| `apd_render_display_fields` | Static | `src/Fields/FieldRenderer.php:928` |
| `apd_render_field` | Static | `src/Fields/FieldRenderer.php:366` |
| `apd_render_field_display` | Static | `src/Fields/FieldRenderer.php:420` |
| `apd_render_field_group` | Static | `src/Fields/FieldRenderer.php:770` |
| `apd_render_filter` | Static | `src/Search/FilterRenderer.php:305` |
| `apd_rest_favorite_listing_data` | Static | `src/Api/Endpoints/FavoritesEndpoint.php:488` |
| `apd_rest_inquiry_data` | Static | `src/Api/Endpoints/InquiriesEndpoint.php:641` |
| `apd_rest_listing_data` | Static | `src/Api/Endpoints/ListingsEndpoint.php:641` |
| `apd_rest_listings_query_args` | Static | `src/Api/Endpoints/ListingsEndpoint.php:208` |
| `apd_rest_review_data` | Static | `src/Api/Endpoints/ReviewsEndpoint.php:820` |
| `apd_rest_taxonomy_query_args` | Static | `src/Api/Endpoints/TaxonomiesEndpoint.php:242` |
| `apd_rest_term_data` | Static | `src/Api/Endpoints/TaxonomiesEndpoint.php:344` |
| `apd_review_data` | Static | `src/Review/ReviewManager.php:259` |
| `apd_review_default_status` | Static | `src/Review/ReviewManager.php:892` |
| `apd_review_form_classes` | Static | `src/Review/ReviewForm.php:388` |
| `apd_review_form_data` | Static | `src/Review/ReviewForm.php:246` |
| `apd_review_form_data_collected` | Static | `src/Review/ReviewHandler.php:590` |
| `apd_review_guidelines_text` | Static | `src/Review/ReviewForm.php:414` |
| `apd_review_min_content_length` | Static | `src/Review/ReviewManager.php:742` |
| `apd_review_success_message` | Static | `src/Review/ReviewHandler.php:311` |
| `apd_reviews_list_data` | Static | `src/Review/ReviewDisplay.php:304` |
| `apd_reviews_pagination_data` | Static | `src/Review/ReviewDisplay.php:390` |
| `apd_reviews_per_page` | Static | `src/Review/ReviewDisplay.php:461` |
| `apd_reviews_require_login` | Static | `src/Review/ReviewManager.php:724` |
| `apd_reviews_section_data` | Static | `src/Review/ReviewDisplay.php:199` |
| `apd_sanitize_settings` | Static | `src/Admin/Settings.php:1836` |
| `apd_sanitized_fields` | Static | `src/Fields/FieldValidator.php:350` |
| `apd_search_form_block_args` | Static | `src/Blocks/SearchFormBlock.php:144` |
| `apd_search_form_classes` | Static | `src/Search/FilterRenderer.php:157` |
| `apd_search_form_shortcode_args` | Static | `src/Shortcode/SearchFormShortcode.php:149` |
| `apd_search_query_args` | Static | `src/Search/SearchQuery.php:418` |
| `apd_searchable_meta_keys` | Static | `src/Search/SearchQuery.php:384` |
| `apd_set_listing_field_value` | Static | `includes/functions.php:572` |
| `apd_settings_defaults` | Static | `src/Admin/Settings.php:1588` |
| `apd_settings_tabs` | Static | `src/Admin/Settings.php:189` |
| `apd_shortcode_{$this->tag}_atts` | Dynamic | `src/Shortcode/AbstractShortcode.php:132` |
| `apd_shortcode_{$this->tag}_output` | Dynamic | `src/Shortcode/AbstractShortcode.php:156` |
| `apd_should_display_field` | Static | `src/Fields/FieldRenderer.php:958` |
| `apd_should_load_frontend_assets` | Static | `src/Core/Assets.php:153` |
| `apd_show_empty_star_rating` | Static | `src/Review/RatingCalculator.php:517` |
| `apd_single_listing_data` | Static | `templates/single-listing.php:59` |
| `apd_single_review_data` | Static | `src/Review/ReviewDisplay.php:350` |
| `apd_skip_admin_view_count` | Static | `src/Core/TemplateLoader.php:956` |
| `apd_submission_admin_notification` | Static | `src/Frontend/Submission/SubmissionHandler.php:1053` |
| `apd_submission_default_status` | Static | `src/Frontend/Submission/SubmissionHandler.php:864` |
| `apd_submission_error_redirect` | Static | `src/Frontend/Submission/SubmissionHandler.php:1161` |
| `apd_submission_field_groups` | Static | `src/Frontend/Submission/SubmissionForm.php:243` |
| `apd_submission_fields` | Static | `src/Frontend/Submission/SubmissionForm.php:222` |
| `apd_submission_form_args` | Static | `src/Frontend/Submission/SubmissionForm.php:579` |
| `apd_submission_form_classes` | Static | `src/Frontend/Submission/SubmissionForm.php:639` |
| `apd_submission_form_data` | Static | `src/Frontend/Submission/SubmissionHandler.php:452` |
| `apd_submission_form_html` | Static | `src/Frontend/Submission/SubmissionForm.php:604` |
| `apd_submission_form_shortcode_config` | Static | `src/Shortcode/SubmissionFormShortcode.php:223` |
| `apd_submission_min_time` | Static | `src/Frontend/Submission/SubmissionHandler.php:1327`, `includes/functions.php:2476` (+1 more) |
| `apd_submission_page_url` | Static | `src/Frontend/Dashboard/Dashboard.php:706`, `includes/functions.php:2130` (+1 more) |
| `apd_submission_rate_limit` | Static | `src/Frontend/Submission/SubmissionHandler.php:1351`, `includes/functions.php:2280` |
| `apd_submission_rate_period` | Static | `src/Frontend/Submission/SubmissionHandler.php:1361`, `includes/functions.php:2300` |
| `apd_submission_spam_check` | Static | `src/Frontend/Submission/SubmissionHandler.php:1230`, `includes/functions.php:2506` |
| `apd_submission_success_args` | Static | `includes/functions.php:2254` |
| `apd_submission_success_redirect` | Static | `src/Frontend/Submission/SubmissionHandler.php:1127` |
| `apd_submission_trusted_proxies` | Static | `src/Frontend/Submission/SubmissionHandler.php:1460` |
| `apd_track_inquiry` | Static | `src/Contact/InquiryTracker.php:167` |
| `apd_user_can_delete_listing` | Static | `src/Frontend/Dashboard/MyListings.php:715` |
| `apd_user_can_edit_listing` | Static | `src/Frontend/Submission/SubmissionHandler.php:551`, `includes/functions.php:2100` |
| `apd_user_can_edit_review` | Static | `src/Review/ReviewHandler.php:630` |
| `apd_user_can_submit_listing` | Static | `src/Frontend/Submission/SubmissionHandler.php:374` |
| `apd_user_can_view_inquiry` | Static | `src/Contact/InquiryTracker.php:700` |
| `apd_user_inquiries_query_args` | Static | `src/Contact/InquiryTracker.php:410` |
| `apd_user_social_links` | Static | `src/Frontend/Dashboard/Profile.php:661` |
| `apd_validate_field` | Static | `src/Fields/FieldValidator.php:168` |
| `apd_validate_profile` | Static | `src/Frontend/Dashboard/Profile.php:461` |
| `apd_view_container_attributes` | Static | `src/Frontend/Display/AbstractView.php:244` |
| `apd_view_container_classes` | Static | `src/Frontend/Display/AbstractView.php:217` |
| `apd_view_listing_args` | Static | `src/Frontend/Display/AbstractView.php:345` |
| `apd_view_listings_query_args` | Static | `src/Frontend/Display/AbstractView.php:458` |

---

## Custom Fields

### Registering Fields

```php
add_filter( 'apd_listing_fields', function( $fields ) {
    // Simple text field
    $fields['business_hours'] = [
        'type'        => 'textarea',
        'label'       => 'Business Hours',
        'description' => 'Enter your operating hours',
        'required'    => false,
        'rows'        => 4,
    ];

    // Select field with options
    $fields['property_type'] = [
        'type'        => 'select',
        'label'       => 'Property Type',
        'required'    => true,
        'options'     => [
            'house'     => 'House',
            'apartment' => 'Apartment',
            'condo'     => 'Condo',
            'land'      => 'Land',
        ],
        'filterable'  => true,
    ];

    // Price field with currency
    $fields['price'] = [
        'type'            => 'currency',
        'label'           => 'Price',
        'required'        => true,
        'min'             => 0,
        'currency_symbol' => '$',
        'filterable'      => true,
        'sortable'        => true,
    ];

    // Gallery field
    $fields['gallery'] = [
        'type'       => 'gallery',
        'label'      => 'Photo Gallery',
        'max_images' => 10,
    ];

    return $fields;
});
```

### Field Configuration Options

```php
[
    'name'        => 'field_name',      // Auto-generated from key
    'type'        => 'text',            // Field type
    'label'       => 'Field Label',
    'description' => 'Help text',
    'required'    => false,
    'default'     => '',
    'placeholder' => '',
    'options'     => [],                // For select/radio/checkbox
    'validation'  => [                  // Custom validation
        'min_length' => 10,
        'max_length' => 500,
        'pattern'    => '/^[A-Z]/',     // Regex
        'callback'   => 'my_validator', // Custom function
    ],
    'searchable'  => false,             // Include in search
    'filterable'  => false,             // Show in filters
    'sortable'    => false,             // Allow sorting
    'admin_only'  => false,             // Hide from frontend
    'priority'    => 10,                // Display order
    'class'       => '',                // CSS classes
    'attributes'  => [],                // HTML attributes
]
```

### Creating Custom Field Types

```php
use APD\Contracts\FieldTypeInterface;
use APD\Fields\AbstractFieldType;

class CustomFieldType extends AbstractFieldType {
    public function getType(): string {
        return 'custom';
    }

    public function render( array $field, mixed $value ): string {
        return sprintf(
            '<input type="text" name="%s" value="%s" class="custom-input" />',
            esc_attr( $field['name'] ),
            esc_attr( $value )
        );
    }

    public function sanitize( mixed $value ): mixed {
        return sanitize_text_field( $value );
    }

    public function validate( mixed $value, array $field ): bool|WP_Error {
        if ( $field['required'] && empty( $value ) ) {
            return new WP_Error( 'required', 'This field is required.' );
        }
        return true;
    }

    public function supports( string $feature ): bool {
        return in_array( $feature, [ 'searchable', 'sortable' ], true );
    }

    public function formatValue( mixed $value, array $field ): string {
        return esc_html( $value );
    }
}

// Register the field type
add_action( 'apd_init', function() {
    apd_register_field_type( new CustomFieldType() );
});
```

---

## Custom Filters

### Registering Filters

```php
use APD\Search\Filters\RangeFilter;

add_action( 'apd_register_filters', function( $registry ) {
    $registry->register(
        'price_range',
        new RangeFilter(
            [
                'label'    => 'Price Range',
                'field'    => 'price',
                'meta_key' => '_apd_price',
                'min'      => 0,
                'max'      => 1000000,
                'step'     => 1000,
            ]
        )
    );
}, 10, 1 );
```


### Filter Types

- `keyword` - Text search input
- `select` - Dropdown selection
- `checkbox` - Multiple checkboxes
- `range` - Min/max numeric inputs
- `date_range` - Start/end date inputs

---

## Custom Views

### Creating a Custom View

```php
use APD\Contracts\ViewInterface;
use APD\Frontend\Display\AbstractView;

class MapView extends AbstractView {
    public function get_name(): string {
        return 'map';
    }

    public function get_label(): string {
        return __( 'Map View', 'my-plugin' );
    }

    public function get_icon(): string {
        return 'dashicons-location';
    }

    public function render( array $listings, array $args = [] ): string {
        ob_start();
        // Render map with listings
        echo '<div class="apd-map-view" data-listings="' . esc_attr( json_encode( $this->get_map_data( $listings ) ) ) . '"></div>';
        return ob_get_clean();
    }

    public function supports( string $feature ): bool {
        return in_array( $feature, [ 'ajax' ], true );
    }
}

// Register the view
add_action( 'apd_views_init', function() {
    apd_register_view( new MapView() );
});
```

---

## Template System

### Template Hierarchy

1. Child theme: `wp-content/themes/child-theme/all-purpose-directory/`
2. Parent theme: `wp-content/themes/parent-theme/all-purpose-directory/`
3. Plugin: `wp-content/plugins/all-purpose-directory/templates/`

### Using Templates

```php
// Get template with data
apd_get_template( 'listing-card.php', [
    'listing' => $post,
    'view'    => 'grid',
] );

// Get template as string
$html = apd_get_template_html( 'listing-card.php', [ 'listing' => $post ] );

// Check if template exists
if ( apd_template_exists( 'custom-template.php' ) ) {
    apd_get_template( 'custom-template.php' );
}

// Check if theme overrides template
if ( apd_is_template_overridden( 'single-listing.php' ) ) {
    // Theme has custom template
}
```

### Template Data

Templates receive data as extracted variables:

```php
// In your code
apd_get_template( 'listing-card.php', [
    'listing'     => $post,
    'show_image'  => true,
    'custom_data' => 'value',
] );

// In listing-card.php
echo $listing->post_title;
if ( $show_image ) {
    echo get_the_post_thumbnail( $listing->ID );
}
echo $custom_data;
```

---

## REST API

### Namespace

All endpoints use namespace `apd/v1`:
- Base URL: `/wp-json/apd/v1/`

### Authentication

Authenticated requests support two modes:

1. **Cookie-authenticated browser requests** (wp-admin / frontend JS)
   - Must be logged in
   - Must send `X-WP-Nonce` with a valid `wp_rest` nonce
   - This protects mutating endpoints from CSRF

2. **Non-cookie authenticated API requests** (Application Passwords / Authorization-based clients)
   - Must provide valid auth credentials
   - `X-WP-Nonce` is not required

> For mutating endpoints, nonce enforcement is applied to cookie-authenticated requests.

### Endpoints

| Endpoint | Methods | Permission |
|----------|---------|------------|
| `/listings` | GET, POST | Public / Auth |
| `/listings/{id}` | GET, PUT, DELETE | Public / Owner |
| `/categories` | GET | Public |
| `/categories/{id}` | GET | Public |
| `/tags` | GET | Public |
| `/tags/{id}` | GET | Public |
| `/favorites` | GET, POST | Authenticated |
| `/favorites/{id}` | DELETE | Authenticated |
| `/favorites/toggle/{id}` | POST | Authenticated |
| `/reviews` | GET, POST | Public / Auth |
| `/reviews/{id}` | GET, PUT, DELETE | Public / Author |
| `/listings/{id}/reviews` | GET | Public |
| `/inquiries` | GET | Authenticated |
| `/inquiries/{id}` | GET, DELETE | Owner |
| `/inquiries/{id}/read` | POST | Owner |
| `/listings/{id}/inquiries` | GET | Owner |

### Adding Custom Endpoints

```php
add_action( 'apd_register_rest_routes', function( $controller ) {
    register_rest_route( 'apd/v1', '/custom', [
        'methods'             => 'GET',
        'callback'            => 'my_custom_endpoint',
        'permission_callback' => [ $controller, 'permission_authenticated' ],
    ] );
});

function my_custom_endpoint( WP_REST_Request $request ) {
    return apd_rest_response( [ 'data' => 'value' ] );
}
```

### Response Helpers

```php
// Success response
return apd_rest_response( $data, 200, $headers );

// Error response
return apd_rest_error( 'error_code', 'Error message', 400 );

// Paginated response
return apd_rest_paginated_response( $items, $total, $page, $per_page );
```

---

## Helper Functions

### Listings

```php
apd_get_listing_field( $listing_id, $field_name, $default );
apd_set_listing_field( $listing_id, $field_name, $value );
apd_get_listing_views( $listing_id );
apd_increment_listing_views( $listing_id );
apd_get_related_listings( $listing_id, $limit, $args );
```

### Taxonomies

```php
apd_get_listing_categories( $listing_id );
apd_get_listing_tags( $listing_id );
apd_get_category_listings( $category_id, $args );
apd_get_categories_with_count( $args );
apd_get_category_icon( $category );
apd_get_category_color( $category );
```

### Favorites

```php
apd_add_favorite( $listing_id, $user_id );
apd_remove_favorite( $listing_id, $user_id );
apd_toggle_favorite( $listing_id, $user_id );
apd_is_favorite( $listing_id, $user_id );
apd_get_user_favorites( $user_id );
apd_get_favorites_count( $user_id );
apd_get_listing_favorites_count( $listing_id );
```

### Reviews

```php
apd_get_listing_reviews( $listing_id, $args );
apd_get_listing_rating( $listing_id );
apd_get_listing_review_count( $listing_id );
apd_create_review( $listing_id, $data );
apd_update_review( $review_id, $data );
apd_delete_review( $review_id );
apd_current_user_has_reviewed( $listing_id );
```

### Settings

```php
apd_get_setting( $key, $default );
apd_set_setting( $key, $value );
apd_get_all_settings();
apd_reviews_enabled();
apd_favorites_enabled();
apd_contact_form_enabled();
apd_format_price( $amount );
```

### Templates

```php
apd_get_template( $template, $args );
apd_get_template_part( $slug, $name, $args );
apd_get_template_html( $template, $args );
apd_template_exists( $template );
apd_locate_template( $template );
```

### Caching

```php
apd_cache_remember( $key, $callback, $expiration );
apd_cache_get( $key );
apd_cache_set( $key, $value, $expiration );
apd_cache_delete( $key );
apd_cache_clear_all();
```

---

## WP-CLI Commands

All Purpose Directory provides WP-CLI commands for managing demo data without the browser.

### Demo Data

```bash
# Generate all demo data with default quantities
wp apd demo generate

# Generate with custom quantities
wp apd demo generate --users=10 --listings=50

# Generate only specific data types
wp apd demo generate --types=categories,tags,listings

# Show current demo data counts
wp apd demo status

# Show counts as JSON
wp apd demo status --format=json

# Delete all demo data (with confirmation)
wp apd demo delete

# Delete without confirmation prompt
wp apd demo delete --yes
```

### Generate Options

| Option | Default | Description |
|--------|---------|-------------|
| `--types=<types>` | `all` | Comma-separated list of data types to generate. Options: `users`, `categories`, `tags`, `listings`, `reviews`, `inquiries`, `favorites`, `all` |
| `--users=<count>` | `5` | Number of demo users to create (max 20) |
| `--tags=<count>` | `10` | Number of tags to create (max 10) |
| `--listings=<count>` | `25` | Number of listings to create (max 100) |

### Status Options

| Option | Default | Description |
|--------|---------|-------------|
| `--format=<format>` | `table` | Output format: `table`, `json`, `csv`, `yaml` |

### Delete Options

| Option | Description |
|--------|-------------|
| `--yes` | Skip the confirmation prompt |

### Notes

- Reviews require listings to exist (2-4 per listing, automatically generated).
- Inquiries require listings (0-2 per listing, random).
- Favorites require both listings and users.
- Module providers (from external modules) are automatically included in generate, delete, and status commands.
- All demo data is tracked with the `_apd_demo_data` meta key for clean removal.

---

## Database Schema

The plugin uses WordPress native tables:

### Posts Table (`wp_posts`)

- Post type: `apd_listing`
- Post type: `apd_inquiry` (for tracked inquiries)

### Post Meta Table (`wp_postmeta`)

All custom fields use prefix `_apd_`:

| Meta Key | Type | Description |
|----------|------|-------------|
| `_apd_views_count` | int | View count |
| `_apd_average_rating` | float | Cached average rating |
| `_apd_favorite_count` | int | Times favorited |
| `_apd_{field_name}` | mixed | Custom field values |

### Terms Tables (`wp_terms`, `wp_term_taxonomy`)

- Taxonomy: `apd_category`
- Taxonomy: `apd_tag`

### Term Meta Table (`wp_termmeta`)

| Meta Key | Type | Description |
|----------|------|-------------|
| `_apd_category_icon` | string | Dashicon class |
| `_apd_category_color` | string | Hex color |

### Comments Table (`wp_comments`)

- Comment type: `apd_review`

### Comment Meta Table (`wp_commentmeta`)

| Meta Key | Type | Description |
|----------|------|-------------|
| `_apd_rating` | int | Star rating (1-5) |
| `_apd_review_title` | string | Review title |

### User Meta Table (`wp_usermeta`)

| Meta Key | Type | Description |
|----------|------|-------------|
| `_apd_favorites` | array | Favorited listing IDs |
| `_apd_phone` | string | Phone number |
| `_apd_avatar` | int | Custom avatar attachment ID |
| `_apd_social_*` | string | Social media URLs |

### Options Table (`wp_options`)

| Option Name | Type | Description |
|-------------|------|-------------|
| `apd_options` | array | Plugin settings |
| `apd_version` | string | Installed version |

---

## Coding Standards

### PHP Standards

- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)
- Use strict types: `declare(strict_types=1);`
- PHP 8.0+ features allowed
- All functions and classes must have PHPDoc

### JavaScript Standards

- Follow [WordPress JavaScript Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/javascript/)
- ES6+ features allowed
- Use `wp.i18n` for translations

### CSS Standards

- Follow [WordPress CSS Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/css/)
- Use BEM naming: `.apd-block__element--modifier`
- Prefix all classes with `apd-`

### Security

- Always escape output: `esc_html()`, `esc_attr()`, `esc_url()`
- Always sanitize input: `sanitize_text_field()`, `absint()`
- Always verify nonces: `wp_verify_nonce()`
- Always check capabilities: `current_user_can()`
- Always use prepared statements: `$wpdb->prepare()`

### Testing

- Unit tests in `tests/unit/`
- Integration tests in `tests/integration/`
- E2E tests in `tests/e2e/`
- Run tests: `composer test:unit`
