# Undocumented Hooks

Hooks present in the plugin source code but **not documented** on damoiseau.xyz.

Generated: 2026-02-25

**Summary:** 35 missing actions + 5 missing dynamic actions + 78 missing filters = **118 undocumented hooks**

Also found: 6 filters documented on the website that no longer exist in the plugin code (stale docs).

---

## Missing Action Hooks (35)

| Hook Name | File | Line | Description |
|-----------|------|------|-------------|
| `apd_after_categories_shortcode` | `src/Shortcode/CategoriesShortcode.php` | 202 | Fires after categories shortcode renders |
| `apd_after_edit_not_allowed` | `templates/submission/edit-not-allowed.php` | 63 | Fires after the "edit not allowed" message |
| `apd_after_listings_shortcode` | `src/Shortcode/ListingsShortcode.php` | 259 | Fires after listings shortcode renders |
| `apd_after_login_form_shortcode` | `src/Shortcode/LoginFormShortcode.php` | 180 | Fires after login form shortcode renders |
| `apd_after_register_form_shortcode` | `src/Shortcode/RegisterFormShortcode.php` | 165 | Fires after register form shortcode renders |
| `apd_after_search_form_shortcode` | `src/Shortcode/SearchFormShortcode.php` | 192 | Fires after search form shortcode renders |
| `apd_after_submission_form_shortcode` | `src/Shortcode/SubmissionFormShortcode.php` | 250 | Fires after submission form shortcode renders |
| `apd_all_ratings_recalculated` | `src/Review/RatingCalculator.php` | 314 | Fired after all listing ratings are recalculated |
| `apd_before_categories_shortcode` | `src/Shortcode/CategoriesShortcode.php` | 186 | Fires before categories shortcode renders |
| `apd_before_listings_shortcode` | `src/Shortcode/ListingsShortcode.php` | 236 | Fires before listings shortcode renders |
| `apd_before_login_form_shortcode` | `src/Shortcode/LoginFormShortcode.php` | 162 | Fires before login form shortcode renders |
| `apd_before_register_form_shortcode` | `src/Shortcode/RegisterFormShortcode.php` | 139 | Fires before register form shortcode renders |
| `apd_before_review_process` | `src/Review/ReviewHandler.php` | 337 | Fires before a review submission is processed |
| `apd_before_search_form_shortcode` | `src/Shortcode/SearchFormShortcode.php` | 172 | Fires before search form shortcode renders |
| `apd_before_submission_form_shortcode` | `src/Shortcode/SubmissionFormShortcode.php` | 236 | Fires before submission form shortcode renders |
| `apd_block_unregistered` | `src/Blocks/BlockManager.php` | 225 | Fired after a Gutenberg block is unregistered |
| `apd_demo_provider_registered` | `src/Admin/DemoData/DemoDataProviderRegistry.php` | 164 | Fired after a demo data provider is registered |
| `apd_demo_provider_unregistered` | `src/Admin/DemoData/DemoDataProviderRegistry.php` | 195 | Fired after a demo data provider is unregistered |
| `apd_demo_providers_init` | `src/Admin/DemoData/DemoDataProviderRegistry.php` | 113 | Fires when the demo data provider registry initializes |
| `apd_listing_processed` | `includes/functions.php` | 2021 | Fired after a listing submission is fully processed |
| `apd_listing_type_registered` | `src/Taxonomy/ListingTypeTaxonomy.php` | 197 | Fired when a listing type taxonomy term is registered |
| `apd_modules_admin_init` | `src/Module/ModulesAdminPage.php` | 108 | Fires when the Modules admin page initializes |
| `apd_rating_calculated` | `src/Review/RatingCalculator.php` | 197 | Fired after a single listing's rating is calculated |
| `apd_rating_calculator_init` | `src/Review/RatingCalculator.php` | 135 | Fires when the rating calculator initializes |
| `apd_rating_invalidated` | `src/Review/RatingCalculator.php` | 342 | Fired when a listing's rating cache is invalidated |
| `apd_register_filters` | `src/Core/Plugin.php` | 674 | Fires to allow registration of custom search filters |
| `apd_register_form_fields` | `src/Shortcode/RegisterFormShortcode.php` | 235 | Fires to add custom fields to the registration form |
| `apd_render_review_form` | `templates/review/reviews-section.php` | 114 | Fires to render the review submission form |
| `apd_review_display_init` | `src/Review/ReviewDisplay.php` | 117 | Fires when the review display component initializes |
| `apd_review_form_created` | `src/Review/ReviewHandler.php` | 408 | Fired after a new review is created via the frontend form |
| `apd_review_form_init` | `src/Review/ReviewForm.php` | 138 | Fires when the review form component initializes |
| `apd_review_form_updated` | `src/Review/ReviewHandler.php` | 372 | Fired after a review is updated via the frontend form |
| `apd_review_handler_init` | `src/Review/ReviewHandler.php` | 120 | Fires when the review handler initializes |
| `apd_shortcode_unregistered` | `src/Shortcode/ShortcodeManager.php` | 186 | Fired after a shortcode is unregistered |
| `apd_validate_review` | `src/Review/ReviewHandler.php` | 484 | Fires to validate review data before saving |

## Missing Dynamic Action Hooks (5)

These hooks use variable interpolation and are not covered by the docs.

| Hook Pattern | File | Line | Description |
|-------------|------|------|-------------|
| `apd_before_block_{$name}` | `src/Blocks/AbstractBlock.php` | 211 | Fires before a specific block renders |
| `apd_after_block_{$name}` | `src/Blocks/AbstractBlock.php` | 233 | Fires after a specific block renders |
| `apd_before_shortcode_{$tag}` | `src/Shortcode/AbstractShortcode.php` | 142 | Fires before a specific shortcode renders |
| `apd_after_shortcode_{$tag}` | `src/Shortcode/AbstractShortcode.php` | 167 | Fires after a specific shortcode renders |
| `apd_dashboard_{$tab}_content` | `src/Frontend/Dashboard/Dashboard.php` | 487 | Renders content for a specific dashboard tab |

## Missing Filter Hooks (78)

| Hook Name | File | Line | Description |
|-----------|------|------|-------------|
| `apd_ajax_filter_response` | `src/Api/AjaxHandler.php` | 180 | Filters the AJAX listing filter response data |
| `apd_before_validate_field` | `src/Fields/FieldValidator.php` | 150 | Filters field value before validation |
| `apd_categories_block_query_args` | `src/Blocks/CategoriesBlock.php` | 226 | Filters query args for the categories block |
| `apd_categories_shortcode_classes` | `src/Shortcode/CategoriesShortcode.php` | 420 | Filters CSS classes for the categories shortcode |
| `apd_categories_shortcode_query_args` | `src/Shortcode/CategoriesShortcode.php` | 254 | Filters query args for categories shortcode |
| `apd_categories_with_count_args` | `includes/functions.php` | 218 | Filters query args for categories with count |
| `apd_category_listings_query_args` | `includes/functions.php` | 194 | Filters query args for category listings |
| `apd_contact_form_classes` | `src/Contact/ContactForm.php` | 295 | Filters CSS classes for the contact form |
| `apd_contact_form_html` | `src/Contact/ContactForm.php` | 390 | Filters the complete contact form HTML |
| `apd_contact_trusted_proxies` | `src/Contact/ContactHandler.php` | 819 | Filters trusted proxy IPs for contact spam protection |
| `apd_dashboard_classes` | `src/Frontend/Dashboard/Dashboard.php` | 642 | Filters CSS classes for the dashboard wrapper |
| `apd_dashboard_register_url` | `templates/dashboard/login-required.php` | 29 | Filters the registration URL on the dashboard login page |
| `apd_dashboard_show_register` | `templates/dashboard/login-required.php` | 38 | Controls whether to show the register link on dashboard |
| `apd_default_listing_status` | `includes/functions.php` | 2042 | Filters the default status for new listings |
| `apd_default_listing_type` | `src/Taxonomy/ListingTypeTaxonomy.php` | 230 | Filters the default listing type |
| `apd_default_pages` | `src/Core/Activator.php` | 173 | Filters the default pages created on activation |
| `apd_edit_not_allowed_args` | `src/Shortcode/SubmissionFormShortcode.php` | 339 | Filters args for the "edit not allowed" template |
| `apd_email_admin_email` | `src/Email/EmailManager.php` | 496 | Filters the admin email address for notifications |
| `apd_email_plain_text_message` | `src/Email/EmailManager.php` | 999 | Filters the plain text version of email messages |
| `apd_expiration_cron_batch_size` | `src/Core/Plugin.php` | 452 | Filters the batch size for expiration cron processing |
| `apd_expiration_cron_lock_ttl` | `src/Core/Plugin.php` | 437 | Filters the lock TTL for the expiration cron job |
| `apd_favorite_listings_batch_size` | `includes/functions.php` | 3162 | Filters the batch size for favorite listings queries |
| `apd_favorite_listings_query_args` | `includes/functions.php` | 3155 | Filters query args for retrieving favorite listings |
| `apd_favorites_empty_browse_url` | `src/Frontend/Dashboard/FavoritesPage.php` | 391 | Filters the "Browse Listings" URL on empty favorites |
| `apd_favorites_enabled` | `src/Shortcode/FavoritesShortcode.php` | 129 | Controls whether the favorites feature is enabled |
| `apd_favorites_output` | `src/Shortcode/FavoritesShortcode.php` | 140 | Filters the favorites shortcode output HTML |
| `apd_favorites_page_args` | `src/Frontend/Dashboard/FavoritesPage.php` | 209 | Filters args for the favorites dashboard page |
| `apd_field_group_wrapper_class` | `src/Fields/FieldRenderer.php` | 697 | Filters CSS classes for field group wrappers |
| `apd_field_wrapper_class` | `src/Fields/FieldRenderer.php` | 312 | Filters CSS classes for individual field wrappers |
| `apd_filter_wrapper_class` | `src/Search/Filters/AbstractFilter.php` | 344 | Filters CSS classes for search filter wrappers |
| `apd_get_template_part` | `src/Core/Template.php` | 254 | Filters the template part path before loading |
| `apd_grid_responsive_columns` | `src/Frontend/Display/GridView.php` | 217 | Filters responsive column breakpoints for grid view |
| `apd_inquiry_post_data` | `src/Contact/InquiryTracker.php` | 244 | Filters inquiry post data before saving |
| `apd_inquiry_post_type_args` | `src/Contact/InquiryTracker.php` | 145 | Filters the inquiry CPT registration args |
| `apd_is_plugin_admin_screen` | `src/Core/Assets.php` | 232 | Filters whether the current screen is a plugin admin screen |
| `apd_list_responsive_layout` | `src/Frontend/Display/ListView.php` | 191 | Filters responsive layout config for list view |
| `apd_listing_inquiries_query_args` | `src/Contact/InquiryTracker.php` | 336 | Filters query args for listing inquiries |
| `apd_listings_block_pagination_args` | `src/Blocks/ListingsBlock.php` | 345 | Filters pagination args for the listings block |
| `apd_listings_shortcode_pagination_args` | `src/Shortcode/ListingsShortcode.php` | 390 | Filters pagination args for the listings shortcode |
| `apd_listings_shortcode_query_args` | `src/Shortcode/ListingsShortcode.php` | 194 | Filters query args for the listings shortcode |
| `apd_login_form_shortcode_args` | `src/Shortcode/LoginFormShortcode.php` | 239 | Filters args for the login form shortcode |
| `apd_my_listings_args` | `src/Frontend/Dashboard/MyListings.php` | 221 | Filters args for the My Listings dashboard tab |
| `apd_rating_precision` | `src/Review/RatingCalculator.php` | 378 | Filters the decimal precision for rating display |
| `apd_rating_star_count` | `src/Review/RatingCalculator.php` | 360 | Filters the number of stars in the rating system |
| `apd_rating_summary_data` | `src/Review/ReviewDisplay.php` | 247 | Filters the rating summary template data |
| `apd_register_default_fields` | `src/Fields/FieldRegistry.php` | 583 | Filters the default field configurations |
| `apd_register_form_errors` | `src/Shortcode/RegisterFormShortcode.php` | 300 | Filters registration form validation errors |
| `apd_related_listings` | `includes/functions.php` | 1363 | Filters the related listings result set |
| `apd_related_listings_args` | `includes/functions.php` | 1350 | Filters query args for related listings |
| `apd_render_display_fields` | `src/Fields/FieldRenderer.php` | 927 | Filters the rendered display fields HTML |
| `apd_render_field_group` | `src/Fields/FieldRenderer.php` | 769 | Filters the rendered field group HTML |
| `apd_rest_favorite_listing_data` | `src/Api/Endpoints/FavoritesEndpoint.php` | 488 | Filters REST API favorite listing response data |
| `apd_rest_taxonomy_query_args` | `src/Api/Endpoints/TaxonomiesEndpoint.php` | 242 | Filters REST API taxonomy query args |
| `apd_review_form_classes` | `src/Review/ReviewForm.php` | 388 | Filters CSS classes for the review form |
| `apd_review_form_data` | `src/Review/ReviewForm.php` | 246 | Filters review form template data |
| `apd_review_form_data_collected` | `src/Review/ReviewHandler.php` | 585 | Filters collected review form data before processing |
| `apd_review_success_message` | `src/Review/ReviewHandler.php` | 306 | Filters the success message after review submission |
| `apd_reviews_list_data` | `src/Review/ReviewDisplay.php` | 304 | Filters the reviews list template data |
| `apd_reviews_pagination_data` | `src/Review/ReviewDisplay.php` | 390 | Filters the reviews pagination template data |
| `apd_reviews_section_data` | `src/Review/ReviewDisplay.php` | 199 | Filters the reviews section template data |
| `apd_sanitized_fields` | `src/Fields/FieldValidator.php` | 350 | Filters sanitized field values after validation |
| `apd_search_form_block_args` | `src/Blocks/SearchFormBlock.php` | 146 | Filters args for the search form block |
| `apd_search_form_classes` | `src/Search/FilterRenderer.php` | 136 | Filters CSS classes for the search form |
| `apd_search_form_shortcode_args` | `src/Shortcode/SearchFormShortcode.php` | 151 | Filters args for the search form shortcode |
| `apd_set_listing_field_value` | `includes/functions.php` | 570 | Filters a field value before saving to a listing |
| `apd_show_empty_star_rating` | `src/Review/RatingCalculator.php` | 517 | Controls whether to show star rating when no reviews |
| `apd_single_review_data` | `src/Review/ReviewDisplay.php` | 350 | Filters individual review template data |
| `apd_submission_field_groups` | `src/Frontend/Submission/SubmissionForm.php` | 243 | Filters field groups on the submission form |
| `apd_submission_form_args` | `src/Frontend/Submission/SubmissionForm.php` | 579 | Filters args for the submission form |
| `apd_submission_form_classes` | `src/Frontend/Submission/SubmissionForm.php` | 639 | Filters CSS classes for the submission form |
| `apd_submission_form_html` | `src/Frontend/Submission/SubmissionForm.php` | 604 | Filters the complete submission form HTML |
| `apd_submission_form_shortcode_config` | `src/Shortcode/SubmissionFormShortcode.php` | 202 | Filters the submission form shortcode configuration |
| `apd_submission_page_url` | `includes/functions.php` | 2128 | Filters the submission page URL |
| `apd_submission_success_args` | `includes/functions.php` | 2252 | Filters args for the submission success template |
| `apd_submission_trusted_proxies` | `src/Frontend/Submission/SubmissionHandler.php` | 1418 | Filters trusted proxy IPs for submission spam protection |
| `apd_track_inquiry` | `src/Contact/InquiryTracker.php` | 167 | Controls whether to track an inquiry |
| `apd_user_can_view_inquiry` | `src/Contact/InquiryTracker.php` | 700 | Filters whether a user can view an inquiry |
| `apd_user_inquiries_query_args` | `src/Contact/InquiryTracker.php` | 410 | Filters query args for user inquiries |
| `apd_view_container_attributes` | `src/Frontend/Display/AbstractView.php` | 244 | Filters HTML attributes for the view container |
| `apd_view_listings_query_args` | `src/Frontend/Display/AbstractView.php` | 453 | Filters query args for view listings |

---

## Stale Documentation (6 filters)

These hooks are documented on damoiseau.xyz but **no longer exist** in the plugin source code. They should be removed from the docs or the code should be verified.

| Hook Name | Status |
|-----------|--------|
| `apd_cache_expiration` | Not found in plugin code |
| `apd_edit_listing_status` | Not found in plugin code |
| `apd_listing_card_data` | Not found in plugin code |
| `apd_listing_query_args` | Not found in plugin code |
| `apd_search_filters` | Not found in plugin code |
| `apd_submission_admin_notification` | Not found in plugin code |
