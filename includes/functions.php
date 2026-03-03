<?php
/**
 * Global helper functions for All Purpose Directory.
 *
 * @package APD
 */

declare(strict_types=1);

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the main plugin instance.
 *
 * @return \APD\Core\Plugin
 */
function apd(): \APD\Core\Plugin {
	return \APD\Core\Plugin::get_instance();
}

/**
 * Get a plugin option value.
 *
 * @param string $key     Option key.
 * @param mixed  $default Default value if option doesn't exist.
 * @return mixed
 */
function apd_get_option( string $key, mixed $default = null ): mixed {
	$options = get_option( \APD\Admin\Settings::OPTION_NAME, [] );

	return $options[ $key ] ?? $default;
}

/**
 * Check if the current user can manage listings.
 *
 * @return bool
 */
function apd_current_user_can_manage_listings(): bool {
	return current_user_can( 'edit_apd_listings' );
}

/**
 * Get the listing post type name.
 *
 * @return string
 */
function apd_get_listing_post_type(): string {
	return 'apd_listing';
}

/**
 * Get the category taxonomy name.
 *
 * @return string
 */
function apd_get_category_taxonomy(): string {
	return 'apd_category';
}

/**
 * Get the tag taxonomy name.
 *
 * @return string
 */
function apd_get_tag_taxonomy(): string {
	return 'apd_tag';
}

/**
 * Get the listing type taxonomy name.
 *
 * @since 1.1.0
 *
 * @return string
 */
function apd_get_listing_type_taxonomy(): string {
	return \APD\Taxonomy\ListingTypeTaxonomy::TAXONOMY;
}

/**
 * Check if a post is a listing.
 *
 * @param int|\WP_Post|null $post Post ID or post object.
 * @return bool
 */
function apd_is_listing( int|\WP_Post|null $post = null ): bool {
	$post = get_post( $post );

	if ( ! $post ) {
		return false;
	}

	return $post->post_type === apd_get_listing_post_type();
}

/**
 * Get listing meta value.
 *
 * @param int    $listing_id Listing post ID.
 * @param string $key        Meta key without prefix.
 * @param mixed  $default    Default value.
 * @return mixed
 */
function apd_get_listing_meta( int $listing_id, string $key, mixed $default = '' ): mixed {
	$value = get_post_meta( $listing_id, "_apd_{$key}", true );

	return $value !== '' ? $value : $default;
}

/**
 * Update listing meta value.
 *
 * @param int    $listing_id Listing post ID.
 * @param string $key        Meta key without prefix.
 * @param mixed  $value      Meta value.
 * @return int|bool
 */
function apd_update_listing_meta( int $listing_id, string $key, mixed $value ): int|bool {
	return update_post_meta( $listing_id, "_apd_{$key}", $value );
}

/**
 * Delete listing meta value.
 *
 * @param int    $listing_id Listing post ID.
 * @param string $key        Meta key without prefix.
 * @return bool
 */
function apd_delete_listing_meta( int $listing_id, string $key ): bool {
	return delete_post_meta( $listing_id, "_apd_{$key}" );
}


/**
 * Get categories assigned to a listing.
 *
 * @param int $listing_id Listing post ID.
 * @return \WP_Term[] Array of WP_Term objects, or empty array if none.
 */
function apd_get_listing_categories( int $listing_id ): array {
	$terms = get_the_terms( $listing_id, apd_get_category_taxonomy() );

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return [];
	}

	return $terms;
}

/**
 * Get tags assigned to a listing.
 *
 * @param int $listing_id Listing post ID.
 * @return \WP_Term[] Array of WP_Term objects, or empty array if none.
 */
function apd_get_listing_tags( int $listing_id ): array {
	$terms = get_the_terms( $listing_id, apd_get_tag_taxonomy() );

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return [];
	}

	return $terms;
}

/**
 * Get listings in a specific category.
 *
 * @param int   $category_id   Category term ID.
 * @param array $args          Optional. Additional WP_Query args.
 * @return \WP_Post[] Array of WP_Post objects.
 */
function apd_get_category_listings( int $category_id, array $args = [] ): array {
	$defaults = [
		'post_type'      => apd_get_listing_post_type(),
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'tax_query'      => [
			[
				'taxonomy' => apd_get_category_taxonomy(),
				'field'    => 'term_id',
				'terms'    => $category_id,
			],
		],
	];

	$query_args = wp_parse_args( $args, $defaults );

	// Allow filtering the query args.
	$query_args = apply_filters( 'apd_category_listings_query_args', $query_args, $category_id );

	$query = new \WP_Query( $query_args );

	return $query->posts;
}

/**
 * Get all categories with their listing counts.
 *
 * @param array $args Optional. Additional get_terms args.
 * @return \WP_Term[] Array of WP_Term objects with 'count' property.
 */
function apd_get_categories_with_count( array $args = [] ): array {
	$defaults = [
		'taxonomy'   => apd_get_category_taxonomy(),
		'hide_empty' => false,
		'orderby'    => 'name',
		'order'      => 'ASC',
	];

	$query_args = wp_parse_args( $args, $defaults );

	// Allow filtering the query args.
	$query_args = apply_filters( 'apd_categories_with_count_args', $query_args );

	$terms = get_terms( $query_args );

	if ( is_wp_error( $terms ) ) {
		return [];
	}

	return $terms;
}

/**
 * Get category icon class (dashicon).
 *
 * @param int|\WP_Term $category Category term ID or object.
 * @return string Dashicon class or empty string.
 */
function apd_get_category_icon( int|\WP_Term $category ): string {
	$term_id = $category instanceof \WP_Term ? $category->term_id : $category;

	return \APD\Taxonomy\CategoryTaxonomy::get_icon( $term_id );
}

/**
 * Get category color (hex).
 *
 * @param int|\WP_Term $category Category term ID or object.
 * @return string Hex color or empty string.
 */
function apd_get_category_color( int|\WP_Term $category ): string {
	$term_id = $category instanceof \WP_Term ? $category->term_id : $category;

	return \APD\Taxonomy\CategoryTaxonomy::get_color( $term_id );
}

// ============================================================================
// Listing Type Functions
// ============================================================================

/**
 * Get the listing type for a listing.
 *
 * @since 1.1.0
 *
 * @param int $listing_id Listing post ID.
 * @return string Listing type slug (e.g., 'general', 'url-directory').
 */
function apd_get_listing_type( int $listing_id ): string {
	$terms = wp_get_object_terms(
		$listing_id,
		\APD\Taxonomy\ListingTypeTaxonomy::TAXONOMY
	);

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return \APD\Taxonomy\ListingTypeTaxonomy::DEFAULT_TERM;
	}

	return $terms[0]->slug;
}

/**
 * Set the listing type for a listing.
 *
 * @since 1.1.0
 *
 * @param int    $listing_id Listing post ID.
 * @param string $type       Listing type slug (e.g., 'url-directory').
 * @return bool True on success, false on failure.
 */
function apd_set_listing_type( int $listing_id, string $type ): bool {
	$result = wp_set_object_terms(
		$listing_id,
		sanitize_key( $type ),
		\APD\Taxonomy\ListingTypeTaxonomy::TAXONOMY
	);

	return ! is_wp_error( $result );
}

/**
 * Check if a listing is of a specific type.
 *
 * @since 1.1.0
 *
 * @param int    $listing_id Listing post ID.
 * @param string $type       Listing type slug to check.
 * @return bool True if the listing is of the given type.
 */
function apd_listing_is_type( int $listing_id, string $type ): bool {
	return apd_get_listing_type( $listing_id ) === sanitize_key( $type );
}

/**
 * Get all registered listing types.
 *
 * @since 1.1.0
 *
 * @param bool $hide_empty Whether to hide types with no listings.
 * @return \WP_Term[] Array of term objects.
 */
function apd_get_listing_types( bool $hide_empty = false ): array {
	$terms = get_terms(
		[
			'taxonomy'   => \APD\Taxonomy\ListingTypeTaxonomy::TAXONOMY,
			'hide_empty' => $hide_empty,
		]
	);

	return is_wp_error( $terms ) ? [] : $terms;
}

/**
 * Get the listing type term object.
 *
 * @since 1.1.0
 *
 * @param string $type_slug Listing type slug.
 * @return \WP_Term|null Term object or null if not found.
 */
function apd_get_listing_type_term( string $type_slug ): ?\WP_Term {
	$term = get_term_by( 'slug', sanitize_key( $type_slug ), \APD\Taxonomy\ListingTypeTaxonomy::TAXONOMY );

	return $term instanceof \WP_Term ? $term : null;
}

/**
 * Get the count of listings for a given type.
 *
 * @since 1.1.0
 *
 * @param string $type_slug Listing type slug.
 * @return int Number of listings with that type.
 */
function apd_get_listing_type_count( string $type_slug ): int {
	$term = apd_get_listing_type_term( $type_slug );

	return $term ? $term->count : 0;
}

// ============================================================================
// Field Registry Functions
// ============================================================================

/**
 * Get the field registry instance.
 *
 * @since 1.0.0
 *
 * @return \APD\Fields\FieldRegistry
 */
function apd_field_registry(): \APD\Fields\FieldRegistry {
	return \APD\Fields\FieldRegistry::get_instance();
}

/**
 * Register a custom field for listings.
 *
 * @since 1.0.0
 *
 * @param string $name   Unique field identifier (will be sanitized).
 * @param array  $config Field configuration array.
 *                       - type: (string) Field type (text, select, etc.).
 *                       - label: (string) Display label.
 *                       - description: (string) Help text.
 *                       - required: (bool) Whether field is required.
 *                       - default: (mixed) Default value.
 *                       - placeholder: (string) Placeholder text.
 *                       - options: (array) Options for select/radio/checkbox types.
 *                       - validation: (array) Validation rules.
 *                       - searchable: (bool) Include in search queries.
 *                       - filterable: (bool) Show in filter UI.
 *                       - admin_only: (bool) Hide from frontend.
 *                       - priority: (int) Display order (lower = earlier).
 * @return bool True if registered successfully.
 */
function apd_register_field( string $name, array $config = [] ): bool {
	return apd_field_registry()->register_field( $name, $config );
}

/**
 * Unregister a custom field.
 *
 * @since 1.0.0
 *
 * @param string $name Field name to unregister.
 * @return bool True if unregistered successfully.
 */
function apd_unregister_field( string $name ): bool {
	return apd_field_registry()->unregister_field( $name );
}

/**
 * Get a registered field configuration.
 *
 * @since 1.0.0
 *
 * @param string $name Field name.
 * @return array|null Field configuration or null if not found.
 */
function apd_get_field( string $name ): ?array {
	return apd_field_registry()->get_field( $name );
}

/**
 * Get all registered fields.
 *
 * @since 1.0.0
 *
 * @param array $args Optional. Arguments to filter fields.
 *                    - type: (string) Filter by field type.
 *                    - searchable: (bool) Filter by searchable flag.
 *                    - filterable: (bool) Filter by filterable flag.
 *                    - admin_only: (bool) Filter by admin_only flag.
 *                    - orderby: (string) Order by 'priority' or 'name'.
 *                    - order: (string) 'ASC' or 'DESC'.
 * @return array Array of field configurations keyed by name.
 */
function apd_get_fields( array $args = [] ): array {
	return apd_field_registry()->get_fields( $args );
}

/**
 * Check if a field is registered.
 *
 * @since 1.0.0
 *
 * @param string $name Field name.
 * @return bool True if registered.
 */
function apd_has_field( string $name ): bool {
	return apd_field_registry()->has_field( $name );
}

/**
 * Register a field type handler.
 *
 * @since 1.0.0
 *
 * @param \APD\Contracts\FieldTypeInterface $field_type The field type handler instance.
 * @return bool True if registered successfully.
 */
function apd_register_field_type( \APD\Contracts\FieldTypeInterface $field_type ): bool {
	return apd_field_registry()->register_field_type( $field_type );
}

/**
 * Get a field type handler.
 *
 * @since 1.0.0
 *
 * @param string $type Field type identifier.
 * @return \APD\Contracts\FieldTypeInterface|null Field type handler or null.
 */
function apd_get_field_type( string $type ): ?\APD\Contracts\FieldTypeInterface {
	return apd_field_registry()->get_field_type( $type );
}

/**
 * Get the meta key for a field.
 *
 * @since 1.0.0
 *
 * @param string $field_name Field name.
 * @return string The meta key (prefixed with _apd_).
 */
function apd_get_field_meta_key( string $field_name ): string {
	return apd_field_registry()->get_meta_key( $field_name );
}

/**
 * Get a listing field value.
 *
 * Retrieves the value of a custom field for a listing, applying
 * any necessary transformations from the field type handler.
 *
 * @since 1.0.0
 *
 * @param int    $listing_id Listing post ID.
 * @param string $field_name Field name (without _apd_ prefix).
 * @param mixed  $default    Default value if not set.
 * @return mixed The field value.
 */
function apd_get_listing_field( int $listing_id, string $field_name, mixed $default = '' ): mixed {
	$field = apd_get_field( $field_name );

	if ( $field === null ) {
		// Field not registered, fall back to direct meta retrieval.
		return apd_get_listing_meta( $listing_id, $field_name, $default );
	}

	$meta_key = apd_get_field_meta_key( $field_name );
	$value    = get_post_meta( $listing_id, $meta_key, true );

	// Use field default if no value stored.
	if ( $value === '' || $value === null ) {
		$value = $field['default'] ?? $default;
	}

	// Apply field type transformation if available.
	$field_type = apd_get_field_type( $field['type'] );
	if ( $field_type !== null ) {
		$value = $field_type->prepareValueFromStorage( $value );
	}

	/**
	 * Filter the listing field value.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed  $value      The field value.
	 * @param int    $listing_id Listing post ID.
	 * @param string $field_name Field name.
	 * @param array  $field      Field configuration.
	 */
	return apply_filters( 'apd_listing_field_value', $value, $listing_id, $field_name, $field );
}

/**
 * Set a listing field value.
 *
 * Saves a custom field value for a listing, applying any necessary
 * sanitization and transformation from the field type handler.
 *
 * @since 1.0.0
 *
 * @param int    $listing_id Listing post ID.
 * @param string $field_name Field name (without _apd_ prefix).
 * @param mixed  $value      Value to save.
 * @return int|bool Meta ID on success, false on failure.
 */
function apd_set_listing_field( int $listing_id, string $field_name, mixed $value ): int|bool {
	$field = apd_get_field( $field_name );

	if ( $field !== null ) {
		// Apply field type sanitization and transformation.
		$field_type = apd_get_field_type( $field['type'] );
		if ( $field_type !== null ) {
			$value = $field_type->sanitize( $value );
			$value = $field_type->prepareValueForStorage( $value );
		}
	}

	$meta_key = apd_get_field_meta_key( $field_name );

	/**
	 * Filter the value before saving to post meta.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed  $value      The value to save.
	 * @param int    $listing_id Listing post ID.
	 * @param string $field_name Field name.
	 * @param array  $field      Field configuration (or null if not registered).
	 */
	$value = apply_filters( 'apd_set_listing_field_value', $value, $listing_id, $field_name, $field );

	return update_post_meta( $listing_id, $meta_key, $value );
}

// ============================================================================
// Field Renderer Functions
// ============================================================================

/**
 * Get the field renderer instance.
 *
 * @since 1.0.0
 *
 * @return \APD\Fields\FieldRenderer
 */
function apd_field_renderer(): \APD\Fields\FieldRenderer {
	static $renderer = null;

	if ( $renderer === null ) {
		$renderer = new \APD\Fields\FieldRenderer();
	}

	return $renderer;
}

/**
 * Render a single field.
 *
 * @since 1.0.0
 *
 * @param string $field_name Field name.
 * @param mixed  $value      Current value.
 * @param string $context    Render context (admin, frontend, display).
 * @param int    $listing_id Optional. Listing ID for context.
 * @return string Rendered HTML.
 */
function apd_render_field( string $field_name, mixed $value = null, string $context = 'admin', int $listing_id = 0 ): string {
	return apd_field_renderer()
		->set_context( $context )
		->render_field( $field_name, $value, $listing_id );
}

/**
 * Render multiple fields.
 *
 * @since 1.0.0
 *
 * @param array  $values     Field values keyed by name.
 * @param array  $args       Optional. Arguments (fields, exclude).
 * @param string $context    Render context (admin, frontend, display).
 * @param int    $listing_id Optional. Listing ID for context.
 * @return string Rendered HTML.
 */
function apd_render_fields( array $values = [], array $args = [], string $context = 'admin', int $listing_id = 0 ): string {
	return apd_field_renderer()
		->set_context( $context )
		->render_fields( $values, $args, $listing_id );
}

/**
 * Render admin meta box fields for a listing.
 *
 * @since 1.0.0
 *
 * @param int   $listing_id Listing post ID.
 * @param array $args       Optional. Arguments (nonce_action, nonce_name).
 * @return string Rendered HTML.
 */
function apd_render_admin_fields( int $listing_id, array $args = [] ): string {
	return apd_field_renderer()->render_admin_fields( $listing_id, $args );
}

/**
 * Render frontend submission form fields.
 *
 * @since 1.0.0
 *
 * @param int   $listing_id Optional. Listing ID for editing.
 * @param array $args       Optional. Arguments (nonce_action, nonce_name, submitted_values).
 * @return string Rendered HTML.
 */
function apd_render_frontend_fields( int $listing_id = 0, array $args = [] ): string {
	return apd_field_renderer()->render_frontend_fields( $listing_id, $args );
}

/**
 * Render fields for display (single listing view).
 *
 * @since 1.0.0
 *
 * @param int   $listing_id Listing post ID.
 * @param array $args       Optional. Arguments (fields, exclude).
 * @return string Rendered HTML.
 */
function apd_render_display_fields( int $listing_id, array $args = [] ): string {
	return apd_field_renderer()->render_display_fields( $listing_id, $args );
}

/**
 * Register a field group/section.
 *
 * @since 1.0.0
 *
 * @param string $group_id Group identifier.
 * @param array  $config   Group configuration.
 *                         - title: (string) Group title.
 *                         - description: (string) Group description.
 *                         - priority: (int) Display order.
 *                         - collapsible: (bool) Whether group can collapse.
 *                         - collapsed: (bool) Initial collapsed state.
 *                         - fields: (array) Field names in this group.
 * @return void
 */
function apd_register_field_group( string $group_id, array $config ): void {
	apd_field_renderer()->register_group( $group_id, $config );
}

/**
 * Unregister a field group.
 *
 * @since 1.0.0
 *
 * @param string $group_id Group identifier.
 * @return void
 */
function apd_unregister_field_group( string $group_id ): void {
	apd_field_renderer()->unregister_group( $group_id );
}

/**
 * Set field validation errors to display.
 *
 * @since 1.0.0
 *
 * @param array|\WP_Error $errors Errors keyed by field name, or WP_Error.
 * @return void
 */
function apd_set_field_errors( array|\WP_Error $errors ): void {
	apd_field_renderer()->set_errors( $errors );
}

/**
 * Clear field validation errors.
 *
 * @since 1.0.0
 *
 * @return void
 */
function apd_clear_field_errors(): void {
	apd_field_renderer()->clear_errors();
}

// ============================================================================
// Field Validator Functions
// ============================================================================

/**
 * Get the field validator instance.
 *
 * @since 1.0.0
 *
 * @return \APD\Fields\FieldValidator
 */
function apd_field_validator(): \APD\Fields\FieldValidator {
	static $validator = null;

	if ( $validator === null ) {
		$validator = new \APD\Fields\FieldValidator();
	}

	return $validator;
}

/**
 * Validate a single field value.
 *
 * @since 1.0.0
 *
 * @param string $field_name The field name.
 * @param mixed  $value      The value to validate.
 * @param bool   $sanitize   Optional. Whether to sanitize before validation. Default true.
 * @return bool|\WP_Error True if valid, WP_Error on failure.
 */
function apd_validate_field( string $field_name, mixed $value, bool $sanitize = true ): bool|\WP_Error {
	return apd_field_validator()->validate_field( $field_name, $value, $sanitize );
}

/**
 * Validate multiple field values.
 *
 * @since 1.0.0
 *
 * @param array $values Field values keyed by field name.
 * @param array $args   Optional. Arguments.
 *                      - 'fields': (array) Specific field names to validate.
 *                      - 'exclude': (array) Field names to exclude.
 *                      - 'sanitize': (bool) Whether to sanitize. Default true.
 *                      - 'skip_unregistered': (bool) Skip unknown fields. Default true.
 * @return bool|\WP_Error True if all valid, WP_Error with all errors on failure.
 */
function apd_validate_fields( array $values, array $args = [] ): bool|\WP_Error {
	return apd_field_validator()->validate_fields( $values, $args );
}

/**
 * Sanitize a single field value.
 *
 * @since 1.0.0
 *
 * @param string $field_name The field name.
 * @param mixed  $value      The value to sanitize.
 * @return mixed The sanitized value.
 */
function apd_sanitize_field( string $field_name, mixed $value ): mixed {
	return apd_field_validator()->sanitize_field( $field_name, $value );
}

/**
 * Sanitize multiple field values.
 *
 * @since 1.0.0
 *
 * @param array $values Field values keyed by field name.
 * @param array $args   Optional. Arguments.
 *                      - 'fields': (array) Specific field names to sanitize.
 *                      - 'exclude': (array) Field names to exclude.
 *                      - 'skip_unregistered': (bool) Skip unknown fields. Default true.
 * @return array Sanitized values.
 */
function apd_sanitize_fields( array $values, array $args = [] ): array {
	return apd_field_validator()->sanitize_fields( $values, $args );
}

/**
 * Validate and sanitize field values in one operation.
 *
 * @since 1.0.0
 *
 * @param array $values Field values keyed by field name.
 * @param array $args   Optional. Arguments (same as validate_fields).
 * @return array{valid: bool, values: array, errors: \WP_Error|null}
 */
function apd_process_fields( array $values, array $args = [] ): array {
	return apd_field_validator()->process_fields( $values, $args );
}

// ============================================================================
// Filter Registry Functions
// ============================================================================

/**
 * Get the filter registry instance.
 *
 * @since 1.0.0
 *
 * @return \APD\Search\FilterRegistry
 */
function apd_filter_registry(): \APD\Search\FilterRegistry {
	return \APD\Search\FilterRegistry::get_instance();
}

/**
 * Register a search filter.
 *
 * @since 1.0.0
 *
 * @param \APD\Contracts\FilterInterface $filter The filter instance.
 * @return bool True if registered successfully.
 */
function apd_register_filter( \APD\Contracts\FilterInterface $filter ): bool {
	return apd_filter_registry()->register_filter( $filter );
}

/**
 * Unregister a search filter.
 *
 * @since 1.0.0
 *
 * @param string $name Filter name to unregister.
 * @return bool True if unregistered successfully.
 */
function apd_unregister_filter( string $name ): bool {
	return apd_filter_registry()->unregister_filter( $name );
}

/**
 * Get a registered filter by name.
 *
 * @since 1.0.0
 *
 * @param string $name Filter name.
 * @return \APD\Contracts\FilterInterface|null Filter instance or null.
 */
function apd_get_filter( string $name ): ?\APD\Contracts\FilterInterface {
	return apd_filter_registry()->get_filter( $name );
}

/**
 * Get all registered filters.
 *
 * @since 1.0.0
 *
 * @param array $args Optional. Filter arguments.
 *                    - 'type': Filter by filter type.
 *                    - 'source': Filter by source (taxonomy, field, custom).
 *                    - 'active_only': Only return filters marked as active.
 *                    - 'orderby': Order by 'priority' or 'name'.
 *                    - 'order': 'ASC' or 'DESC'.
 * @return array Array of filter instances keyed by name.
 */
function apd_get_filters( array $args = [] ): array {
	return apd_filter_registry()->get_filters( $args );
}

/**
 * Check if a filter is registered.
 *
 * @since 1.0.0
 *
 * @param string $name Filter name.
 * @return bool True if registered.
 */
function apd_has_filter( string $name ): bool {
	return apd_filter_registry()->has_filter( $name );
}

// ============================================================================
// Search Query Functions
// ============================================================================

/**
 * Get the search query instance.
 *
 * @since 1.0.0
 *
 * @param array<string, mixed>|null $request_params Optional request/query params.
 * @return \APD\Search\SearchQuery
 */
function apd_search_query( ?array $request_params = null ): \APD\Search\SearchQuery {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$request_params = $request_params ?? $_GET;

	return new \APD\Search\SearchQuery( null, $request_params );
}

/**
 * Get filtered listings query.
 *
 * Runs a WP_Query with active filters applied.
 *
 * @since 1.0.0
 *
 * @param array<string, mixed>      $args           Additional query arguments.
 * @param array<string, mixed>|null $request_params Optional request/query params.
 * @return \WP_Query The query result.
 */
function apd_get_filtered_listings( array $args = [], ?array $request_params = null ): \WP_Query {
	return apd_search_query( $request_params )->get_filtered_listings( $args, $request_params );
}

/**
 * Get orderby options for listing queries.
 *
 * @since 1.0.0
 *
 * @return array Orderby options with labels.
 */
function apd_get_orderby_options(): array {
	return apd_search_query()->get_orderby_options();
}

// ============================================================================
// Filter Renderer Functions
// ============================================================================

/**
 * Get the filter renderer instance.
 *
 * @since 1.0.0
 *
 * @return \APD\Search\FilterRenderer
 */
function apd_filter_renderer(): \APD\Search\FilterRenderer {
	static $renderer = null;

	if ( $renderer === null ) {
		$renderer = new \APD\Search\FilterRenderer();
	}

	return $renderer;
}

/**
 * Render the search form with filters.
 *
 * @since 1.0.0
 *
 * @param array $args Render arguments.
 *                    - 'filters': Array of filter names to include.
 *                    - 'exclude': Array of filter names to exclude.
 *                    - 'show_orderby': Whether to show orderby dropdown.
 *                    - 'show_submit': Whether to show submit button.
 *                    - 'action': Form action URL.
 *                    - 'method': Form method (get/post).
 *                    - 'ajax': Whether to use AJAX.
 *                    - 'class': Additional CSS classes.
 * @return string The rendered form HTML.
 */
function apd_render_search_form( array $args = [] ): string {
	return apd_filter_renderer()->render_search_form( $args );
}

/**
 * Render a single filter control.
 *
 * @since 1.0.0
 *
 * @param string     $name    Filter name.
 * @param array|null $request Request data for value.
 * @return string The rendered filter HTML.
 */
function apd_render_filter( string $name, ?array $request = null ): string {
	return apd_filter_renderer()->render_filter( $name, $request );
}

/**
 * Render active filter chips.
 *
 * @since 1.0.0
 *
 * @param array|null $request Request data.
 * @return string The rendered HTML.
 */
function apd_render_active_filters( ?array $request = null ): string {
	return apd_filter_renderer()->render_active_filters( $request );
}

/**
 * Render the no results message.
 *
 * @since 1.0.0
 *
 * @param array $args Render arguments.
 * @return string The rendered HTML.
 */
function apd_render_no_results( array $args = [] ): string {
	return apd_filter_renderer()->render_no_results( $args );
}

// ============================================================================
// Template Functions
// ============================================================================

/**
 * Get the template loader instance.
 *
 * @since 1.0.0
 *
 * @return \APD\Core\Template
 */
function apd_template(): \APD\Core\Template {
	return \APD\Core\Template::get_instance();
}

/**
 * Locate a template file.
 *
 * Searches for templates in the following order:
 * 1. Theme: `{theme}/all-purpose-directory/{template_name}`
 * 2. Plugin: `{plugin}/templates/{template_name}`
 *
 * @since 1.0.0
 *
 * @param string $template_name Template file name (e.g., 'listing-card.php').
 * @return string|false Full path to template file or false if not found.
 */
function apd_locate_template( string $template_name ): string|false {
	return apd_template()->locate_template( $template_name );
}

/**
 * Load a template file with variables.
 *
 * Variables are extracted into the template's scope as individual variables.
 * They are also available as an $args array within the template.
 *
 * @since 1.0.0
 *
 * @param string $template_name Template file name (e.g., 'listing-card.php').
 * @param array  $args          Variables to pass to the template.
 * @param bool   $require_once  Whether to use require_once (default: false).
 * @return void
 */
function apd_get_template( string $template_name, array $args = [], bool $require_once = false ): void {
	apd_template()->get_template( $template_name, $args, $require_once );
}

/**
 * Load and return a template as HTML.
 *
 * @since 1.0.0
 *
 * @param string $template_name Template file name.
 * @param array  $args          Variables to pass to the template.
 * @return string The template HTML.
 */
function apd_get_template_html( string $template_name, array $args = [] ): string {
	return apd_template()->get_template_html( $template_name, $args );
}

/**
 * Load a template part.
 *
 * Works similarly to WordPress's get_template_part() but with theme override support.
 * Will try to load templates in this order:
 * 1. `{slug}-{name}.php`
 * 2. `{slug}.php`
 *
 * Example: apd_get_template_part('listing-card', 'grid') will look for:
 * - `{theme}/all-purpose-directory/listing-card-grid.php`
 * - `{theme}/all-purpose-directory/listing-card.php`
 * - `{plugin}/templates/listing-card-grid.php`
 * - `{plugin}/templates/listing-card.php`
 *
 * @since 1.0.0
 *
 * @param string      $slug The slug name for the generic template.
 * @param string|null $name The name of the specialized template (optional).
 * @param array       $args Variables to pass to the template.
 * @return void
 */
function apd_get_template_part( string $slug, ?string $name = null, array $args = [] ): void {
	apd_template()->get_template_part( $slug, $name, $args );
}

/**
 * Load and return a template part as HTML.
 *
 * @since 1.0.0
 *
 * @param string      $slug The slug name for the generic template.
 * @param string|null $name The name of the specialized template (optional).
 * @param array       $args Variables to pass to the template.
 * @return string The template HTML.
 */
function apd_get_template_part_html( string $slug, ?string $name = null, array $args = [] ): string {
	return apd_template()->get_template_part_html( $slug, $name, $args );
}

/**
 * Check if a template exists.
 *
 * @since 1.0.0
 *
 * @param string $template_name Template file name.
 * @return bool True if template exists.
 */
function apd_template_exists( string $template_name ): bool {
	return apd_template()->template_exists( $template_name );
}

/**
 * Check if a template is being overridden by the theme.
 *
 * @since 1.0.0
 *
 * @param string $template_name Template file name.
 * @return bool True if template is overridden in theme.
 */
function apd_is_template_overridden( string $template_name ): bool {
	return apd_template()->is_template_overridden( $template_name );
}

/**
 * Get the plugin's template path.
 *
 * @since 1.0.0
 *
 * @return string Plugin template path.
 */
function apd_get_plugin_template_path(): string {
	return apd_template()->get_plugin_template_path();
}

/**
 * Get the theme template directory name.
 *
 * @since 1.0.0
 *
 * @return string Theme template directory (e.g., 'all-purpose-directory/').
 */
function apd_get_theme_template_dir(): string {
	return apd_template()->get_theme_template_dir();
}

// ============================================================================
// Template Loader Functions
// ============================================================================

/**
 * Get the template loader instance.
 *
 * @since 1.0.0
 *
 * @return \APD\Core\TemplateLoader
 */
function apd_template_loader(): \APD\Core\TemplateLoader {
	static $loader = null;

	if ( $loader === null ) {
		$loader = new \APD\Core\TemplateLoader();
	}

	return $loader;
}

/**
 * Get the current view mode (grid or list).
 *
 * @since 1.0.0
 *
 * @return string View mode ('grid' or 'list').
 */
function apd_get_current_view(): string {
	return apd_template_loader()->get_current_view();
}

/**
 * Get the current grid columns setting.
 *
 * @since 1.0.0
 *
 * @return int Number of columns (2, 3, or 4).
 */
function apd_get_grid_columns(): int {
	return apd_template_loader()->get_grid_columns();
}

/**
 * Get URL for switching to a specific view.
 *
 * @since 1.0.0
 *
 * @param string $view The view to switch to ('grid' or 'list').
 * @return string URL with view parameter.
 */
function apd_get_view_url( string $view ): string {
	return apd_template_loader()->get_view_url( $view );
}

/**
 * Render the view switcher HTML.
 *
 * @since 1.0.0
 *
 * @return string The HTML for the view switcher.
 */
function apd_render_view_switcher(): string {
	return apd_template_loader()->render_view_switcher();
}

/**
 * Render results count.
 *
 * @since 1.0.0
 *
 * @param \WP_Query|null $query Optional. Query to get count from.
 * @return string The HTML for the results count.
 */
function apd_render_results_count( ?\WP_Query $query = null ): string {
	return apd_template_loader()->render_results_count( $query );
}

/**
 * Render pagination.
 *
 * @since 1.0.0
 *
 * @param \WP_Query|null $query Optional. Query to paginate.
 * @return string The pagination HTML.
 */
function apd_render_pagination( ?\WP_Query $query = null ): string {
	return apd_template_loader()->render_pagination( $query );
}

/**
 * Get the archive title.
 *
 * @since 1.0.0
 *
 * @return string The archive title.
 */
function apd_get_archive_title(): string {
	return apd_template_loader()->get_archive_title();
}

/**
 * Get the archive description.
 *
 * @since 1.0.0
 *
 * @return string The archive description.
 */
function apd_get_archive_description(): string {
	return apd_template_loader()->get_archive_description();
}

// ============================================================================
// Single Listing Functions
// ============================================================================

/**
 * Get related listings for a single listing.
 *
 * Related listings are determined by shared categories first,
 * then by shared tags if not enough are found.
 *
 * @since 1.0.0
 *
 * @param int   $listing_id The listing to get related posts for.
 * @param int   $limit      Maximum number of related listings. Default 4.
 * @param array $args       Optional. Additional query arguments.
 * @return \WP_Post[] Array of related listing posts.
 */
function apd_get_related_listings( int $listing_id, int $limit = 4, array $args = [] ): array {
	$categories = apd_get_listing_categories( $listing_id );
	$tags       = apd_get_listing_tags( $listing_id );

	// If no categories or tags, return empty.
	if ( empty( $categories ) && empty( $tags ) ) {
		return [];
	}

	$category_ids = array_map( fn( $term ) => $term->term_id, $categories );
	$tag_ids      = array_map( fn( $term ) => $term->term_id, $tags );

	// Build tax query.
	$tax_query = [
		'relation' => 'OR',
	];

	if ( ! empty( $category_ids ) ) {
		$tax_query[] = [
			'taxonomy' => apd_get_category_taxonomy(),
			'field'    => 'term_id',
			'terms'    => $category_ids,
		];
	}

	if ( ! empty( $tag_ids ) ) {
		$tax_query[] = [
			'taxonomy' => apd_get_tag_taxonomy(),
			'field'    => 'term_id',
			'terms'    => $tag_ids,
		];
	}

	$defaults = [
		'post_type'           => apd_get_listing_post_type(),
		'post_status'         => 'publish',
		'posts_per_page'      => $limit,
		'post__not_in'        => [ $listing_id ],
		'ignore_sticky_posts' => true,
		'orderby'             => 'rand',
		'tax_query'           => $tax_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		'no_found_rows'       => true, // Performance: skip counting total rows.
	];

	$query_args = wp_parse_args( $args, $defaults );

	/**
	 * Filter the related listings query arguments.
	 *
	 * @since 1.0.0
	 *
	 * @param array $query_args The query arguments.
	 * @param int   $listing_id The current listing ID.
	 * @param int   $limit      The limit of related listings.
	 */
	$query_args = apply_filters( 'apd_related_listings_args', $query_args, $listing_id, $limit );

	$query = new \WP_Query( $query_args );

	/**
	 * Filter the related listings.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_Post[] $posts      The related posts.
	 * @param int        $listing_id The current listing ID.
	 * @param int        $limit      The limit of related listings.
	 */
	return apply_filters( 'apd_related_listings', $query->posts, $listing_id, $limit );
}

/**
 * Get the listing view count.
 *
 * @since 1.0.0
 *
 * @param int $listing_id Listing post ID.
 * @return int View count.
 */
function apd_get_listing_views( int $listing_id ): int {
	return absint( get_post_meta( $listing_id, '_apd_views_count', true ) );
}

/**
 * Increment the listing view count.
 *
 * @since 1.0.0
 *
 * @param int $listing_id Listing post ID.
 * @return int The new view count.
 */
function apd_increment_listing_views( int $listing_id ): int {
	global $wpdb;

	$meta_key = '_apd_views_count';

	// Atomic increment: single UPDATE avoids race conditions from concurrent requests.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$updated = $wpdb->query(
		$wpdb->prepare(
			"UPDATE {$wpdb->postmeta} SET meta_value = meta_value + 1 WHERE post_id = %d AND meta_key = %s",
			$listing_id,
			$meta_key
		)
	);

	if ( ! $updated ) {
		// No row existed yet — first view for this listing.
		add_post_meta( $listing_id, $meta_key, 1, true );
	}

	// Read back the current count for the action hook and return value.
	$views = apd_get_listing_views( $listing_id );

	/**
	 * Fires after a listing's view count is incremented.
	 *
	 * @since 1.0.0
	 *
	 * @param int $listing_id The listing post ID.
	 * @param int $views      The new view count.
	 */
	do_action( 'apd_listing_viewed', $listing_id, $views );

	return $views;
}

// ============================================================================
// View Registry Functions
// ============================================================================

/**
 * Get the view registry instance.
 *
 * @since 1.0.0
 *
 * @return \APD\Frontend\Display\ViewRegistry
 */
function apd_view_registry(): \APD\Frontend\Display\ViewRegistry {
	return \APD\Frontend\Display\ViewRegistry::get_instance();
}

/**
 * Register a listing display view.
 *
 * @since 1.0.0
 *
 * @param \APD\Contracts\ViewInterface $view The view instance.
 * @return bool True if registered successfully.
 */
function apd_register_view( \APD\Contracts\ViewInterface $view ): bool {
	return apd_view_registry()->register_view( $view );
}

/**
 * Unregister a listing display view.
 *
 * @since 1.0.0
 *
 * @param string $type View type to unregister.
 * @return bool True if unregistered successfully.
 */
function apd_unregister_view( string $type ): bool {
	return apd_view_registry()->unregister_view( $type );
}

/**
 * Get a registered view by type.
 *
 * @since 1.0.0
 *
 * @param string $type View type (e.g., 'grid', 'list').
 * @return \APD\Contracts\ViewInterface|null View instance or null.
 */
function apd_get_view( string $type ): ?\APD\Contracts\ViewInterface {
	return apd_view_registry()->get_view( $type );
}

/**
 * Get all registered views.
 *
 * @since 1.0.0
 *
 * @return array<string, \APD\Contracts\ViewInterface> Array of views.
 */
function apd_get_views(): array {
	return apd_view_registry()->get_views();
}

/**
 * Check if a view type is registered.
 *
 * @since 1.0.0
 *
 * @param string $type View type.
 * @return bool True if registered.
 */
function apd_has_view( string $type ): bool {
	return apd_view_registry()->has_view( $type );
}

/**
 * Create a new view instance with configuration.
 *
 * @since 1.0.0
 *
 * @param string $type   View type (e.g., 'grid', 'list').
 * @param array  $config Configuration options.
 * @return \APD\Contracts\ViewInterface|null View instance or null.
 */
function apd_create_view( string $type, array $config = [] ): ?\APD\Contracts\ViewInterface {
	return apd_view_registry()->create_view( $type, $config );
}

/**
 * Get available view options for select fields.
 *
 * @since 1.0.0
 *
 * @return array<string, string> Type => label mapping.
 */
function apd_get_view_options(): array {
	return apd_view_registry()->get_view_options();
}

/**
 * Get the grid view instance.
 *
 * @since 1.0.0
 *
 * @param array $config Optional. Configuration options.
 * @return \APD\Frontend\Display\GridView
 */
function apd_grid_view( array $config = [] ): \APD\Frontend\Display\GridView {
	return new \APD\Frontend\Display\GridView( $config );
}

/**
 * Get the list view instance.
 *
 * @since 1.0.0
 *
 * @param array $config Optional. Configuration options.
 * @return \APD\Frontend\Display\ListView
 */
function apd_list_view( array $config = [] ): \APD\Frontend\Display\ListView {
	return new \APD\Frontend\Display\ListView( $config );
}

/**
 * Render listings in grid view.
 *
 * @since 1.0.0
 *
 * @param \WP_Query|array<int> $listings WP_Query or array of listing IDs.
 * @param array                $args     Render arguments.
 *                                       - columns: (int) Number of columns (2, 3, 4).
 *                                       - show_image: (bool) Show featured image.
 *                                       - show_excerpt: (bool) Show excerpt.
 *                                       - excerpt_length: (int) Excerpt word count.
 *                                       - show_category: (bool) Show categories.
 *                                       - show_price: (bool) Show price field.
 *                                       - show_rating: (bool) Show rating.
 *                                       - show_favorite: (bool) Show favorite button.
 *                                       - show_container: (bool) Wrap in container.
 *                                       - show_no_results: (bool) Show no results message.
 * @return string Rendered HTML.
 */
function apd_render_grid( \WP_Query|array $listings, array $args = [] ): string {
	// Extract view config from args.
	$config      = [];
	$config_keys = [
		'columns',
		'show_image',
		'show_excerpt',
		'excerpt_length',
		'show_category',
		'show_badge',
		'show_price',
		'show_rating',
		'show_favorite',
		'show_view_details',
		'image_size',
	];
	foreach ( $config_keys as $key ) {
		if ( isset( $args[ $key ] ) ) {
			$config[ $key ] = $args[ $key ];
		}
	}

	$view = apd_grid_view( $config );
	return $view->renderListings( $listings, $args );
}

/**
 * Render listings in list view.
 *
 * @since 1.0.0
 *
 * @param \WP_Query|array<int> $listings WP_Query or array of listing IDs.
 * @param array                $args     Render arguments.
 *                                       - show_image: (bool) Show featured image.
 *                                       - show_excerpt: (bool) Show excerpt.
 *                                       - excerpt_length: (int) Excerpt word count.
 *                                       - show_category: (bool) Show categories.
 *                                       - show_tags: (bool) Show tags.
 *                                       - max_tags: (int) Maximum tags to show.
 *                                       - show_date: (bool) Show date.
 *                                       - show_price: (bool) Show price field.
 *                                       - show_rating: (bool) Show rating.
 *                                       - show_favorite: (bool) Show favorite button.
 *                                       - show_container: (bool) Wrap in container.
 *                                       - show_no_results: (bool) Show no results message.
 * @return string Rendered HTML.
 */
function apd_render_list( \WP_Query|array $listings, array $args = [] ): string {
	// Extract view config from args.
	$config      = [];
	$config_keys = [
		'show_image',
		'show_excerpt',
		'excerpt_length',
		'show_category',
		'show_tags',
		'max_tags',
		'show_date',
		'show_price',
		'show_rating',
		'show_favorite',
		'show_view_details',
		'image_size',
		'image_width',
	];
	foreach ( $config_keys as $key ) {
		if ( isset( $args[ $key ] ) ) {
			$config[ $key ] = $args[ $key ];
		}
	}

	$view = apd_list_view( $config );
	return $view->renderListings( $listings, $args );
}

/**
 * Render listings in the specified view.
 *
 * @since 1.0.0
 *
 * @param \WP_Query|array<int> $listings WP_Query or array of listing IDs.
 * @param string               $view_type View type ('grid' or 'list').
 * @param array                $args      Render arguments.
 * @return string Rendered HTML.
 */
function apd_render_listings( \WP_Query|array $listings, string $view_type = 'grid', array $args = [] ): string {
	$view = apd_create_view( $view_type, $args );

	if ( $view === null ) {
		// Fall back to grid view.
		$view = apd_grid_view( $args );
	}

	return $view->renderListings( $listings, $args );
}

// ============================================================================
// SHORTCODE FUNCTIONS
// ============================================================================

/**
 * Get the shortcode manager instance.
 *
 * @since 1.0.0
 *
 * @return \APD\Shortcode\ShortcodeManager
 */
function apd_shortcode_manager(): \APD\Shortcode\ShortcodeManager {
	return \APD\Shortcode\ShortcodeManager::get_instance();
}

/**
 * Get a registered shortcode.
 *
 * @since 1.0.0
 *
 * @param string $tag Shortcode tag.
 * @return \APD\Shortcode\AbstractShortcode|null
 */
function apd_get_shortcode( string $tag ): ?\APD\Shortcode\AbstractShortcode {
	return apd_shortcode_manager()->get( $tag );
}

/**
 * Check if a shortcode is registered.
 *
 * @since 1.0.0
 *
 * @param string $tag Shortcode tag.
 * @return bool
 */
function apd_has_shortcode( string $tag ): bool {
	return apd_shortcode_manager()->has( $tag );
}

/**
 * Get all registered shortcodes.
 *
 * @since 1.0.0
 *
 * @return array<string, \APD\Shortcode\AbstractShortcode>
 */
function apd_get_shortcodes(): array {
	return apd_shortcode_manager()->get_all();
}

/**
 * Get shortcode documentation.
 *
 * @since 1.0.0
 *
 * @return array<string, array>
 */
function apd_get_shortcode_docs(): array {
	return apd_shortcode_manager()->get_documentation();
}

// =============================================================================
// Block Manager Functions
// =============================================================================

/**
 * Get the block manager instance.
 *
 * @since 1.0.0
 *
 * @return \APD\Blocks\BlockManager
 */
function apd_block_manager(): \APD\Blocks\BlockManager {
	return \APD\Blocks\BlockManager::get_instance();
}

/**
 * Get a registered block.
 *
 * @since 1.0.0
 *
 * @param string $name Block name (without namespace).
 * @return \APD\Blocks\AbstractBlock|null
 */
function apd_get_block( string $name ): ?\APD\Blocks\AbstractBlock {
	return apd_block_manager()->get( $name );
}

/**
 * Check if a block is registered.
 *
 * @since 1.0.0
 *
 * @param string $name Block name (without namespace).
 * @return bool
 */
function apd_has_block( string $name ): bool {
	return apd_block_manager()->has( $name );
}

/**
 * Get all registered blocks.
 *
 * @since 1.0.0
 *
 * @return array<string, \APD\Blocks\AbstractBlock>
 */
function apd_get_blocks(): array {
	return apd_block_manager()->get_all();
}

// ============================================================================
// Submission Form Functions
// ============================================================================

/**
 * Get a new submission form instance.
 *
 * @since 1.0.0
 *
 * @param array $config Optional. Form configuration.
 *                      - redirect: (string) URL to redirect after submission.
 *                      - show_title: (bool) Show title field.
 *                      - show_content: (bool) Show content field.
 *                      - show_excerpt: (bool) Show excerpt field.
 *                      - show_categories: (bool) Show category selector.
 *                      - show_tags: (bool) Show tag selector.
 *                      - show_featured_image: (bool) Show featured image upload.
 *                      - show_terms: (bool) Show terms acceptance checkbox.
 *                      - terms_text: (string) Terms checkbox text.
 *                      - terms_link: (string) URL to terms page.
 *                      - terms_required: (bool) Whether terms are required.
 *                      - submit_text: (string) Submit button text.
 *                      - class: (string) Additional CSS classes.
 *                      - listing_id: (int) Listing ID for editing (0 for new).
 *                      - submitted_values: (array) Previously submitted values.
 * @return \APD\Frontend\Submission\SubmissionForm
 */
function apd_submission_form( array $config = [] ): \APD\Frontend\Submission\SubmissionForm {
	return new \APD\Frontend\Submission\SubmissionForm( $config );
}

/**
 * Render the submission form.
 *
 * @since 1.0.0
 *
 * @param array $config Optional. Form configuration (see apd_submission_form()).
 * @return string Rendered form HTML.
 */
function apd_render_submission_form( array $config = [] ): string {
	$form = apd_submission_form( $config );
	return $form->render();
}

/**
 * Get fields configured for frontend submission.
 *
 * Returns all fields that are not admin-only and should be shown
 * on the frontend submission form.
 *
 * @since 1.0.0
 *
 * @param int $listing_id Optional. Listing ID for editing context.
 * @return array<string, array<string, mixed>> Fields keyed by name.
 */
function apd_get_submission_fields( int $listing_id = 0 ): array {
	$form = apd_submission_form( [ 'listing_id' => $listing_id ] );
	return $form->get_submission_fields();
}

/**
 * Store submission errors for display after redirect.
 *
 * Used by the form handler to store validation errors that will
 * be displayed when the form is re-rendered.
 *
 * @since 1.0.0
 *
 * @param array|\WP_Error $errors Errors keyed by field name, or WP_Error.
 * @param int             $user_id Optional. User ID. Defaults to current user.
 * @return bool True on success.
 */
function apd_set_submission_errors( array|\WP_Error $errors, int $user_id = 0 ): bool {
	if ( $user_id <= 0 ) {
		$user_id = get_current_user_id();
	}

	if ( $user_id <= 0 ) {
		return false;
	}

	$error_array = [];

	if ( is_wp_error( $errors ) ) {
		foreach ( $errors->get_error_codes() as $code ) {
			$error_array[ $code ] = $errors->get_error_messages( $code );
		}
	} else {
		$error_array = $errors;
	}

	$transient_key = 'apd_submission_errors_' . $user_id;
	return set_transient( $transient_key, $error_array, 5 * MINUTE_IN_SECONDS );
}

/**
 * Store submitted values for display after redirect.
 *
 * Used by the form handler to preserve form values that will
 * be re-populated when the form is re-rendered after validation failure.
 *
 * @since 1.0.0
 *
 * @param array $values Submitted values keyed by field name.
 * @param int   $user_id Optional. User ID. Defaults to current user.
 * @return bool True on success.
 */
function apd_set_submission_values( array $values, int $user_id = 0 ): bool {
	if ( $user_id <= 0 ) {
		$user_id = get_current_user_id();
	}

	if ( $user_id <= 0 ) {
		return false;
	}

	$transient_key = 'apd_submission_values_' . $user_id;
	return set_transient( $transient_key, $values, 5 * MINUTE_IN_SECONDS );
}

// ============================================================================
// Submission Handler Functions
// ============================================================================

/**
 * Get the submission handler instance.
 *
 * Returns a singleton instance of the submission handler that is
 * initialized and ready to process form submissions.
 *
 * @since 1.0.0
 *
 * @param array $config Optional. Configuration options.
 *                      - default_status: (string) Default status for new listings.
 *                      - require_login: (bool) Require user to be logged in.
 *                      - require_title: (bool) Require listing title.
 *                      - require_content: (bool) Require listing content.
 *                      - require_category: (bool) Require at least one category.
 *                      - require_featured_image: (bool) Require featured image.
 *                      - send_admin_notification: (bool) Send email to admin.
 * @return \APD\Frontend\Submission\SubmissionHandler
 */
function apd_submission_handler( array $config = [] ): \APD\Frontend\Submission\SubmissionHandler {
	static $handler = null;

	if ( $handler === null || ! empty( $config ) ) {
		$handler = new \APD\Frontend\Submission\SubmissionHandler( $config );
	}

	return $handler;
}

/**
 * Process a listing submission programmatically.
 *
 * This function allows you to submit a listing without going through
 * the form submission process. Useful for API endpoints or imports.
 *
 * @since 1.0.0
 *
 * @param array $data        Listing data.
 *                           - listing_title: (string) The listing title.
 *                           - listing_content: (string) The listing description.
 *                           - listing_excerpt: (string) Optional. Short description.
 *                           - listing_categories: (array) Category term IDs.
 *                           - listing_tags: (array) Tag term IDs.
 *                           - featured_image: (int) Attachment ID for featured image.
 *                           - custom_fields: (array) Custom field values.
 *                           - post_status: (string) Override default status.
 *                           - post_author: (int) Override author.
 * @param int   $listing_id  Optional. Existing listing ID for updates.
 * @return int|\WP_Error     The listing ID on success, WP_Error on failure.
 */
function apd_process_submission( array $data, int $listing_id = 0 ): int|\WP_Error {
	$errors = new \WP_Error();

	// Validate required data.
	if ( empty( $data['listing_title'] ) ) {
		$errors->add( 'listing_title', __( 'Listing title is required.', 'all-purpose-directory' ) );
	}

	if ( empty( $data['listing_content'] ) ) {
		$errors->add( 'listing_content', __( 'Listing description is required.', 'all-purpose-directory' ) );
	}

	if ( $errors->has_errors() ) {
		return $errors;
	}

	// Prepare post data.
	$post_data = [
		'post_type'    => 'apd_listing',
		'post_title'   => sanitize_text_field( $data['listing_title'] ),
		'post_content' => wp_kses_post( $data['listing_content'] ),
		'post_excerpt' => isset( $data['listing_excerpt'] ) ? sanitize_textarea_field( $data['listing_excerpt'] ) : '',
		'post_status'  => $data['post_status'] ?? apd_get_default_listing_status(),
	];

	// Set author.
	if ( ! empty( $data['post_author'] ) ) {
		$post_data['post_author'] = absint( $data['post_author'] );
	} elseif ( $listing_id === 0 ) {
		$post_data['post_author'] = get_current_user_id();
	}

	// Create or update.
	if ( $listing_id > 0 ) {
		$post_data['ID'] = $listing_id;
		$result          = wp_update_post( $post_data, true );
	} else {
		$result = wp_insert_post( $post_data, true );
	}

	if ( is_wp_error( $result ) ) {
		return $result;
	}

	$listing_id = $result;

	// Assign categories.
	if ( ! empty( $data['listing_categories'] ) ) {
		wp_set_object_terms( $listing_id, array_map( 'absint', $data['listing_categories'] ), 'apd_category' );
	}

	// Assign tags.
	if ( ! empty( $data['listing_tags'] ) ) {
		wp_set_object_terms( $listing_id, array_map( 'absint', $data['listing_tags'] ), 'apd_tag' );
	}

	// Set featured image.
	if ( ! empty( $data['featured_image'] ) ) {
		set_post_thumbnail( $listing_id, absint( $data['featured_image'] ) );
	}

	// Save custom fields.
	if ( ! empty( $data['custom_fields'] ) && is_array( $data['custom_fields'] ) ) {
		$processed = apd_process_fields( $data['custom_fields'] );
		foreach ( $processed['values'] as $field_name => $value ) {
			apd_set_listing_field( $listing_id, $field_name, $value );
		}
	}

	/**
	 * Fires after a listing has been processed programmatically.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $listing_id The listing ID.
	 * @param array $data       The submitted data.
	 * @param bool  $is_update  Whether this was an update.
	 */
	do_action( 'apd_listing_processed', $listing_id, $data, $listing_id === $result );

	return $listing_id;
}

/**
 * Get the default status for new listing submissions.
 *
 * @since 1.0.0
 *
 * @return string Default status (publish, pending, draft).
 */
function apd_get_default_listing_status(): string {
	/**
	 * Filter the default status for new listing submissions.
	 *
	 * @since 1.0.0
	 *
	 * @param string $status  The default status.
	 * @param int    $user_id The current user ID.
	 */
	return apply_filters( 'apd_default_listing_status', 'pending', get_current_user_id() );
}

/**
 * Check if a user can edit a listing.
 *
 * Verifies ownership or appropriate capabilities.
 *
 * @since 1.0.0
 *
 * @param int      $listing_id The listing post ID.
 * @param int|null $user_id    Optional. User ID to check. Defaults to current user.
 * @return bool True if user can edit the listing.
 */
function apd_user_can_edit_listing( int $listing_id, ?int $user_id = null ): bool {
	if ( $user_id === null ) {
		$user_id = get_current_user_id();
	}

	if ( $user_id <= 0 ) {
		return false;
	}

	$post = get_post( $listing_id );

	if ( ! $post || $post->post_type !== apd_get_listing_post_type() ) {
		return false;
	}

	// Check if user is the author.
	if ( (int) $post->post_author === $user_id ) {
		return true;
	}

	// Check if user has capability to edit others' listings.
	if ( user_can( $user_id, 'edit_others_apd_listings' ) ) {
		return true;
	}

	// Check specific post capability.
	if ( user_can( $user_id, 'edit_apd_listing', $listing_id ) ) {
		return true;
	}

	/**
	 * Filter whether the user can edit this listing.
	 *
	 * Use this filter for custom permission logic, such as
	 * allowing specific roles or subscription-based access.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $can_edit   Whether user can edit. Default false.
	 * @param int  $listing_id The listing ID.
	 * @param int  $user_id    The user ID.
	 */
	return apply_filters( 'apd_user_can_edit_listing', false, $listing_id, $user_id );
}

/**
 * Get the URL to edit a listing on the frontend.
 *
 * @since 1.0.0
 *
 * @param int    $listing_id    The listing post ID.
 * @param string $submission_url Optional. The base submission URL.
 *                               Defaults to the page with [apd_submission_form] shortcode.
 * @return string The edit URL, or empty string if no submission page found.
 */
function apd_get_edit_listing_url( int $listing_id, string $submission_url = '' ): string {
	if ( $listing_id <= 0 ) {
		return '';
	}

	// If no submission URL provided, try to find the submission page.
	if ( empty( $submission_url ) ) {
		/**
		 * Filter the default submission page URL.
		 *
		 * Use this to specify the page URL that contains the submission form.
		 *
		 * @since 1.0.0
		 *
		 * @param string $url        The submission page URL.
		 * @param int    $listing_id The listing ID being edited.
		 */
		$submission_url = apply_filters( 'apd_submission_page_url', '', $listing_id );

		if ( empty( $submission_url ) ) {
			// Try to find a page with the shortcode.
			$pages = get_pages(
				[
					'post_status' => 'publish',
					'number'      => 1,
					's'           => '[apd_submission_form',
				]
			);

			if ( ! empty( $pages ) ) {
				$submission_url = get_permalink( $pages[0]->ID );
			}
		}
	}

	if ( empty( $submission_url ) ) {
		return '';
	}

	/**
	 * Filter the edit listing URL.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url        The edit URL.
	 * @param int    $listing_id The listing ID.
	 */
	return apply_filters(
		'apd_edit_listing_url',
		add_query_arg( 'edit_listing', $listing_id, $submission_url ),
		$listing_id
	);
}

/**
 * Check if the current request is in edit mode.
 *
 * Checks for the `edit_listing` URL parameter.
 *
 * @since 1.0.0
 *
 * @return bool True if in edit mode.
 */
function apd_is_edit_mode(): bool {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Just checking for mode.
	return isset( $_GET['edit_listing'] ) && absint( $_GET['edit_listing'] ) > 0;
}

/**
 * Get the listing ID being edited from the URL.
 *
 * @since 1.0.0
 *
 * @return int Listing ID or 0 if not in edit mode.
 */
function apd_get_edit_listing_id(): int {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Just getting listing ID.
	return isset( $_GET['edit_listing'] ) ? absint( $_GET['edit_listing'] ) : 0;
}

/**
 * Check if a submission was successful.
 *
 * Checks the URL parameters to determine if we're displaying
 * a success state after form submission.
 *
 * @since 1.0.0
 *
 * @return bool True if submission was successful.
 */
function apd_is_submission_success(): bool {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Just checking for success state.
	return isset( $_GET['apd_submission'] ) && $_GET['apd_submission'] === 'success';
}

/**
 * Get the listing ID from a successful submission.
 *
 * @since 1.0.0
 *
 * @return int Listing ID or 0 if not available.
 */
function apd_get_submitted_listing_id(): int {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Just getting listing ID.
	return isset( $_GET['listing_id'] ) ? absint( $_GET['listing_id'] ) : 0;
}

/**
 * Render the submission success message.
 *
 * @since 1.0.0
 *
 * @param int    $listing_id Optional. The listing ID. Defaults to URL parameter.
 * @param string $submit_url Optional. URL to submit another listing.
 * @param bool   $is_update  Optional. Whether this was an update.
 * @return string The success message HTML.
 */
function apd_render_submission_success( int $listing_id = 0, string $submit_url = '', bool $is_update = false ): string {
	if ( $listing_id <= 0 ) {
		$listing_id = apd_get_submitted_listing_id();
	}

	$post = get_post( $listing_id );

	$args = [
		'listing_id'  => $listing_id,
		'listing_url' => $post && $post->post_status === 'publish' ? get_permalink( $listing_id ) : '',
		'status'      => $post ? $post->post_status : 'pending',
		'title'       => $post ? $post->post_title : '',
		'submit_url'  => $submit_url,
		'is_update'   => $is_update,
	];

	/**
	 * Filter the submission success template arguments.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args       Template arguments.
	 * @param int   $listing_id The listing ID.
	 */
	$args = apply_filters( 'apd_submission_success_args', $args, $listing_id );

	return apd_get_template_html( 'submission/submission-success.php', $args );
}

// ============================================================================
// Spam Protection Functions
// ============================================================================

/**
 * Get the submission rate limit.
 *
 * Returns the maximum number of submissions allowed in the time period.
 *
 * @since 1.0.0
 *
 * @return int Maximum submissions allowed.
 */
function apd_get_submission_rate_limit(): int {
	/**
	 * Filter the submission rate limit.
	 *
	 * @since 1.0.0
	 *
	 * @param int $limit Maximum submissions allowed. Default 5.
	 */
	return apply_filters( 'apd_submission_rate_limit', 5 );
}

/**
 * Get the submission rate limit time period.
 *
 * Returns the time period in seconds for rate limiting.
 *
 * @since 1.0.0
 *
 * @return int Time period in seconds.
 */
function apd_get_submission_rate_period(): int {
	/**
	 * Filter the submission rate limit time period.
	 *
	 * @since 1.0.0
	 *
	 * @param int $period Time period in seconds. Default 3600 (1 hour).
	 */
	return apply_filters( 'apd_submission_rate_period', HOUR_IN_SECONDS );
}

/**
 * Check if a user/IP is rate limited for submissions.
 *
 * @since 1.0.0
 *
 * @param string $identifier User ID prefixed with 'user_' or hashed IP prefixed with 'ip_'.
 * @return bool True if within limit, false if rate limited.
 */
function apd_check_submission_rate_limit( string $identifier ): bool {
	$limit         = apd_get_submission_rate_limit();
	$transient_key = 'apd_submission_count_' . $identifier;
	$count         = (int) get_transient( $transient_key );

	return $count < $limit;
}

/**
 * Get the current submission count for a user/IP.
 *
 * @since 1.0.0
 *
 * @param string $identifier User ID prefixed with 'user_' or hashed IP prefixed with 'ip_'.
 * @return int Current submission count.
 */
function apd_get_submission_count( string $identifier ): int {
	$transient_key = 'apd_submission_count_' . $identifier;

	return (int) get_transient( $transient_key );
}

/**
 * Increment the submission count for a user/IP.
 *
 * @since 1.0.0
 *
 * @param string $identifier User ID prefixed with 'user_' or hashed IP prefixed with 'ip_'.
 * @return int New submission count.
 */
function apd_increment_submission_count( string $identifier ): int {
	$period        = apd_get_submission_rate_period();
	$transient_key = 'apd_submission_count_' . $identifier;
	$count         = (int) get_transient( $transient_key );
	$new_count     = $count + 1;

	set_transient( $transient_key, $new_count, $period );

	return $new_count;
}

/**
 * Reset the submission count for a user/IP.
 *
 * Useful for testing or admin override.
 *
 * @since 1.0.0
 *
 * @param string $identifier User ID prefixed with 'user_' or hashed IP prefixed with 'ip_'.
 * @return bool True on success.
 */
function apd_reset_submission_count( string $identifier ): bool {
	$transient_key = 'apd_submission_count_' . $identifier;

	return delete_transient( $transient_key );
}

/**
 * Get the rate limit identifier for the current user/visitor.
 *
 * @since 1.0.0
 *
 * @return string Rate limit identifier.
 */
function apd_get_rate_limit_identifier(): string {
	$user_id = get_current_user_id();

	if ( $user_id > 0 ) {
		return 'user_' . $user_id;
	}

	// Use IP address for guests.
	$ip = apd_get_client_ip();

	// Hash the IP for privacy and to create a safe transient key.
	return 'ip_' . md5( $ip );
}

/**
 * Get the client IP address.
 *
 * @since 1.0.0
 *
 * @return string Client IP address.
 */
function apd_get_client_ip(): string {
	$ip = '';

	// Check for proxy headers first.
	$headers = [
		'HTTP_CF_CONNECTING_IP', // Cloudflare.
		'HTTP_X_FORWARDED_FOR',
		'HTTP_X_REAL_IP',
		'REMOTE_ADDR',
	];

	foreach ( $headers as $header ) {
		if ( ! empty( $_SERVER[ $header ] ) ) {
			// Take the first IP if comma-separated.
			$ip = strtok( sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) ), ',' );
			break;
		}
	}

	// Validate the IP.
	$ip = trim( (string) $ip );
	if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
		return $ip;
	}

	return '0.0.0.0';
}

/**
 * Check if a submission appears to be spam.
 *
 * Runs all spam protection checks. This is a convenience function
 * that can be used outside the normal form submission flow.
 *
 * @since 1.0.0
 *
 * @param array $post_data Optional. POST data to check. Defaults to $_POST.
 * @return bool|WP_Error True if not spam, WP_Error if spam detected.
 */
function apd_is_submission_spam( array $post_data = [] ): bool|\WP_Error {
	if ( empty( $post_data ) ) {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$post_data = $_POST;
	}

	$user_id = get_current_user_id();

	/**
	 * Filter whether to bypass spam protection entirely.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $bypass  Whether to bypass. Default false.
	 * @param int  $user_id The current user ID.
	 */
	$bypass = apply_filters( 'apd_bypass_spam_protection', false, $user_id );

	if ( $bypass ) {
		return true;
	}

	// Check honeypot.
	$honeypot_field = apply_filters( 'apd_honeypot_field_name', 'website_url' );
	if ( isset( $post_data[ $honeypot_field ] ) && ! hash_equals( '', (string) $post_data[ $honeypot_field ] ) ) {
		return new \WP_Error(
			'submission_failed',
			__( 'Submission failed. Please try again.', 'all-purpose-directory' )
		);
	}

	// Check timing.
	if ( isset( $post_data['apd_form_token'] ) ) {
		$decoded = base64_decode( (string) $post_data['apd_form_token'], true );
		if ( $decoded !== false ) {
			$form_load_time = (int) $decoded;
			$current_time   = time();

			// Check if timestamp is valid.
			if ( $form_load_time <= $current_time && $form_load_time >= ( $current_time - DAY_IN_SECONDS ) ) {
				$elapsed  = $current_time - $form_load_time;
				$min_time = apply_filters( 'apd_submission_min_time', 3 );

				if ( $elapsed < $min_time ) {
					return new \WP_Error(
						'submission_failed',
						__( 'Submission failed. Please try again.', 'all-purpose-directory' )
					);
				}
			}
		}
	}

	// Check rate limit.
	$identifier = apd_get_rate_limit_identifier();
	if ( ! apd_check_submission_rate_limit( $identifier ) ) {
		return new \WP_Error(
			'rate_limited',
			__( 'You have submitted too many listings. Please try again later.', 'all-purpose-directory' )
		);
	}

	/**
	 * Filter to run custom spam checks.
	 *
	 * @since 1.0.0
	 *
	 * @param bool|WP_Error $result    Current result. True if passed.
	 * @param array         $post_data The POST data.
	 * @param int           $user_id   The current user ID.
	 */
	return apply_filters( 'apd_submission_spam_check', true, $post_data, $user_id );
}

/**
 * Get the minimum time required for form submission.
 *
 * @since 1.0.0
 *
 * @return int Minimum seconds before submission is allowed.
 */
function apd_get_submission_min_time(): int {
	/**
	 * Filter the minimum time required for form submission.
	 *
	 * @since 1.0.0
	 *
	 * @param int $min_time Minimum seconds before submission is allowed. Default 3.
	 */
	return apply_filters( 'apd_submission_min_time', 3 );
}

/**
 * Get the honeypot field name.
 *
 * @since 1.0.0
 *
 * @return string The honeypot field name.
 */
function apd_get_honeypot_field_name(): string {
	/**
	 * Filter the honeypot field name.
	 *
	 * @since 1.0.0
	 *
	 * @param string $field_name The honeypot field name.
	 */
	return apply_filters( 'apd_honeypot_field_name', 'website_url' );
}

// ============================================================================
// Dashboard Functions
// ============================================================================

/**
 * Get the dashboard instance.
 *
 * @since 1.0.0
 *
 * @param array $config Optional. Dashboard configuration.
 * @return \APD\Frontend\Dashboard\Dashboard
 */
function apd_dashboard( array $config = [] ): \APD\Frontend\Dashboard\Dashboard {
	return \APD\Frontend\Dashboard\Dashboard::get_instance( $config );
}

/**
 * Render the dashboard.
 *
 * @since 1.0.0
 *
 * @param array $config Optional. Dashboard configuration.
 *                      - default_tab: (string) Default tab slug.
 *                      - show_stats: (bool) Show statistics section.
 *                      - class: (string) Additional CSS classes.
 * @return string Rendered dashboard HTML.
 */
function apd_render_dashboard( array $config = [] ): string {
	$dashboard = new \APD\Frontend\Dashboard\Dashboard( $config );
	return $dashboard->render();
}

/**
 * Get the dashboard URL.
 *
 * Returns the URL to the page containing the dashboard shortcode.
 *
 * @since 1.0.0
 *
 * @return string Dashboard URL.
 */
function apd_get_dashboard_url(): string {
	/**
	 * Filter the dashboard page URL.
	 *
	 * Use this to specify the page URL that contains the dashboard.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url The dashboard page URL.
	 */
	$url = apply_filters( 'apd_dashboard_url', '' );

	if ( empty( $url ) ) {
		// Try to find a page with the shortcode.
		$pages = get_pages(
			[
				'post_status' => 'publish',
				'number'      => 1,
				's'           => '[apd_dashboard',
			]
		);

		if ( ! empty( $pages ) ) {
			$url = get_permalink( $pages[0]->ID );
		}
	}

	return $url ?: '';
}

/**
 * Get the URL to a specific dashboard tab.
 *
 * @since 1.0.0
 *
 * @param string $tab Tab slug (my-listings, add-new, favorites, profile).
 * @return string Tab URL.
 */
function apd_get_dashboard_tab_url( string $tab ): string {
	$base_url = apd_get_dashboard_url();

	if ( empty( $base_url ) ) {
		return '';
	}

	if ( $tab === \APD\Frontend\Dashboard\Dashboard::DEFAULT_TAB ) {
		return $base_url;
	}

	return add_query_arg( \APD\Frontend\Dashboard\Dashboard::TAB_PARAM, $tab, $base_url );
}

/**
 * Get user's listing statistics.
 *
 * Returns an array of statistics for the specified user's listings.
 *
 * @since 1.0.0
 *
 * @param int $user_id Optional. User ID. Defaults to current user.
 * @return array<string, int> Statistics array with keys:
 *                            - total: Total listing count.
 *                            - published: Published listings count.
 *                            - pending: Pending listings count.
 *                            - draft: Draft listings count.
 *                            - expired: Expired listings count.
 *                            - views: Total views across all listings.
 */
function apd_get_user_listing_stats( int $user_id = 0 ): array {
	$dashboard = apd_dashboard();
	return $dashboard->get_user_stats( $user_id );
}

/**
 * Count user's listings by status.
 *
 * @since 1.0.0
 *
 * @param int    $user_id User ID.
 * @param string $status  Post status (publish, pending, draft, expired).
 *                        Use 'any' for all statuses.
 * @return int Listing count.
 */
function apd_get_user_listings_count( int $user_id, string $status = 'any' ): int {
	if ( $user_id <= 0 ) {
		return 0;
	}

	$post_status = $status === 'any' ? [ 'publish', 'pending', 'draft', 'expired' ] : $status;

	$query = new \WP_Query(
		[
			'post_type'      => 'apd_listing',
			'post_status'    => $post_status,
			'author'         => $user_id,
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		]
	);

	return $query->post_count;
}

/**
 * Check if we're currently on the dashboard page.
 *
 * @since 1.0.0
 *
 * @return bool True if on dashboard page.
 */
function apd_is_dashboard(): bool {
	if ( ! is_page() ) {
		return false;
	}

	$post = get_post();

	if ( ! $post ) {
		return false;
	}

	return has_shortcode( $post->post_content, 'apd_dashboard' );
}

/**
 * Get the current dashboard tab.
 *
 * @since 1.0.0
 *
 * @return string Current tab slug or empty string if not on dashboard.
 */
function apd_get_current_dashboard_tab(): string {
	if ( ! apd_is_dashboard() ) {
		return '';
	}

	$dashboard = apd_dashboard();
	return $dashboard->get_current_tab();
}

// ============================================================================
// My Listings Functions
// ============================================================================

/**
 * Get the My Listings instance.
 *
 * @since 1.0.0
 *
 * @param array $config Optional. Configuration options.
 * @return \APD\Frontend\Dashboard\MyListings
 */
function apd_my_listings( array $config = [] ): \APD\Frontend\Dashboard\MyListings {
	return \APD\Frontend\Dashboard\MyListings::get_instance( $config );
}

/**
 * Get listings for a specific user with optional filters.
 *
 * @since 1.0.0
 *
 * @param int   $user_id Optional. User ID. Defaults to current user.
 * @param array $args    Optional. Query arguments.
 *                       - status: (string) Filter by status (all, publish, pending, draft, expired).
 *                       - orderby: (string) Order by field (date, title, views).
 *                       - order: (string) Order direction (ASC, DESC).
 *                       - paged: (int) Page number.
 *                       - per_page: (int) Items per page.
 * @return \WP_Query Query result.
 */
function apd_get_user_listings( int $user_id = 0, array $args = [] ): \WP_Query {
	if ( $user_id <= 0 ) {
		$user_id = get_current_user_id();
	}

	if ( $user_id <= 0 ) {
		// Return empty query.
		return new \WP_Query( [ 'post__in' => [ 0 ] ] );
	}

	$my_listings = apd_my_listings();
	$my_listings->set_user_id( $user_id );

	return $my_listings->get_listings( $args );
}

/**
 * Delete a user's listing with ownership verification.
 *
 * @since 1.0.0
 *
 * @param int      $listing_id Listing post ID.
 * @param int|null $user_id    Optional. User ID to verify ownership. Defaults to current user.
 * @param bool     $permanent  Optional. Whether to permanently delete. Default false (trash).
 * @return bool True on success, false on failure.
 */
function apd_delete_user_listing( int $listing_id, ?int $user_id = null, bool $permanent = false ): bool {
	if ( $user_id === null ) {
		$user_id = get_current_user_id();
	}

	$my_listings = apd_my_listings();
	$my_listings->set_user_id( $user_id );

	if ( $permanent ) {
		return $my_listings->delete_listing( $listing_id, $user_id );
	}

	return $my_listings->trash_listing( $listing_id, $user_id );
}

/**
 * Check if a user can delete a specific listing.
 *
 * @since 1.0.0
 *
 * @param int      $listing_id Listing post ID.
 * @param int|null $user_id    Optional. User ID to check. Defaults to current user.
 * @return bool True if user can delete, false otherwise.
 */
function apd_can_delete_listing( int $listing_id, ?int $user_id = null ): bool {
	if ( $user_id === null ) {
		$user_id = get_current_user_id();
	}

	$my_listings = apd_my_listings();
	$my_listings->set_user_id( $user_id );

	return $my_listings->can_delete_listing( $listing_id, $user_id );
}

/**
 * Update a user's listing status with ownership verification.
 *
 * @since 1.0.0
 *
 * @param int      $listing_id Listing post ID.
 * @param string   $status     New status (publish, draft, pending, expired).
 * @param int|null $user_id    Optional. User ID to verify ownership. Defaults to current user.
 * @return bool True on success, false on failure.
 */
function apd_update_user_listing_status( int $listing_id, string $status, ?int $user_id = null ): bool {
	if ( $user_id === null ) {
		$user_id = get_current_user_id();
	}

	$my_listings = apd_my_listings();
	$my_listings->set_user_id( $user_id );

	return $my_listings->update_listing_status( $listing_id, $status, $user_id );
}

// ============================================================================
// Profile Functions
// ============================================================================

/**
 * Get the Profile instance.
 *
 * @since 1.0.0
 *
 * @param array $config Optional. Configuration options.
 * @return \APD\Frontend\Dashboard\Profile
 */
function apd_profile( array $config = [] ): \APD\Frontend\Dashboard\Profile {
	return \APD\Frontend\Dashboard\Profile::get_instance( $config );
}

/**
 * Get user's profile data.
 *
 * Returns an array containing the user's profile information including
 * core WordPress user data and custom APD fields.
 *
 * @since 1.0.0
 *
 * @param int $user_id Optional. User ID. Defaults to current user.
 * @return array<string, mixed> User profile data array containing:
 *                              - display_name: (string) Public display name.
 *                              - first_name: (string) First name.
 *                              - last_name: (string) Last name.
 *                              - user_email: (string) Email address.
 *                              - description: (string) Bio/description.
 *                              - user_url: (string) Website URL.
 *                              - phone: (string) Phone number.
 *                              - avatar_id: (int) Custom avatar attachment ID.
 *                              - social: (array) Social media links.
 */
function apd_get_user_profile_data( int $user_id = 0 ): array {
	if ( $user_id <= 0 ) {
		$user_id = get_current_user_id();
	}

	$profile = apd_profile();
	return $profile->get_user_data( $user_id );
}

/**
 * Save user's profile data.
 *
 * Validates and saves user profile data to the database.
 *
 * @since 1.0.0
 *
 * @param array    $data    Profile data to save.
 *                          - display_name: (string) Public display name.
 *                          - first_name: (string) First name.
 *                          - last_name: (string) Last name.
 *                          - user_email: (string) Email address.
 *                          - description: (string) Bio/description.
 *                          - user_url: (string) Website URL.
 *                          - phone: (string) Phone number.
 *                          - avatar_id: (int) Custom avatar attachment ID.
 *                          - social: (array) Social media links.
 * @param int|null $user_id Optional. User ID. Defaults to current user.
 * @return bool|\WP_Error True on success, WP_Error on failure.
 */
function apd_save_user_profile( array $data, ?int $user_id = null ): bool|\WP_Error {
	if ( $user_id === null ) {
		$user_id = get_current_user_id();
	}

	if ( $user_id <= 0 ) {
		return new \WP_Error( 'invalid_user', __( 'Invalid user ID.', 'all-purpose-directory' ) );
	}

	$profile = apd_profile();
	$profile->set_user_id( $user_id );

	// Validate the data.
	$validation = $profile->validate_profile( $data );

	if ( is_wp_error( $validation ) ) {
		return $validation;
	}

	return $profile->save_profile( $data );
}

/**
 * Get user's avatar URL.
 *
 * Returns the user's custom avatar if set, otherwise falls back to Gravatar.
 *
 * @since 1.0.0
 *
 * @param int $user_id Optional. User ID. Defaults to current user.
 * @param int $size    Optional. Avatar size in pixels. Default 96.
 * @return string Avatar URL.
 */
function apd_get_user_avatar_url( int $user_id = 0, int $size = 96 ): string {
	if ( $user_id <= 0 ) {
		$user_id = get_current_user_id();
	}

	if ( $user_id <= 0 ) {
		return '';
	}

	$profile = apd_profile();
	return $profile->get_avatar_url( $user_id, $size );
}

/**
 * Get user's social media links.
 *
 * Returns an array of the user's social media profile URLs.
 *
 * @since 1.0.0
 *
 * @param int $user_id Optional. User ID. Defaults to current user.
 * @return array<string, string> Social links keyed by platform (facebook, twitter, linkedin, instagram).
 */
function apd_get_user_social_links( int $user_id = 0 ): array {
	if ( $user_id <= 0 ) {
		$user_id = get_current_user_id();
	}

	if ( $user_id <= 0 ) {
		return [];
	}

	$profile = apd_profile();
	return $profile->get_social_links( $user_id );
}

/**
 * Check if a user has a custom avatar.
 *
 * @since 1.0.0
 *
 * @param int $user_id Optional. User ID. Defaults to current user.
 * @return bool True if user has a custom avatar.
 */
function apd_user_has_custom_avatar( int $user_id = 0 ): bool {
	if ( $user_id <= 0 ) {
		$user_id = get_current_user_id();
	}

	if ( $user_id <= 0 ) {
		return false;
	}

	$profile = apd_profile();
	return $profile->has_custom_avatar( $user_id );
}

// ============================================================================
// Favorites Functions
// ============================================================================

/**
 * Get the Favorites instance.
 *
 * @since 1.0.0
 *
 * @return \APD\User\Favorites
 */
function apd_favorites(): \APD\User\Favorites {
	return \APD\User\Favorites::get_instance();
}

/**
 * Add a listing to user's favorites.
 *
 * @since 1.0.0
 *
 * @param int      $listing_id Listing post ID.
 * @param int|null $user_id    Optional. User ID. Defaults to current user.
 * @return bool True if added successfully.
 */
function apd_add_favorite( int $listing_id, ?int $user_id = null ): bool {
	return apd_favorites()->add( $listing_id, $user_id );
}

/**
 * Remove a listing from user's favorites.
 *
 * @since 1.0.0
 *
 * @param int      $listing_id Listing post ID.
 * @param int|null $user_id    Optional. User ID. Defaults to current user.
 * @return bool True if removed successfully.
 */
function apd_remove_favorite( int $listing_id, ?int $user_id = null ): bool {
	return apd_favorites()->remove( $listing_id, $user_id );
}

/**
 * Toggle a listing's favorite status.
 *
 * @since 1.0.0
 *
 * @param int      $listing_id Listing post ID.
 * @param int|null $user_id    Optional. User ID. Defaults to current user.
 * @return bool|null The new state (true = favorited, false = unfavorited), null on error.
 */
function apd_toggle_favorite( int $listing_id, ?int $user_id = null ): ?bool {
	return apd_favorites()->toggle( $listing_id, $user_id );
}

/**
 * Check if a listing is in user's favorites.
 *
 * @since 1.0.0
 *
 * @param int      $listing_id Listing post ID.
 * @param int|null $user_id    Optional. User ID. Defaults to current user.
 * @return bool True if listing is favorited.
 */
function apd_is_favorite( int $listing_id, ?int $user_id = null ): bool {
	return apd_favorites()->is_favorite( $listing_id, $user_id );
}

/**
 * Get all favorite listing IDs for a user.
 *
 * @since 1.0.0
 *
 * @param int|null $user_id Optional. User ID. Defaults to current user.
 * @return int[] Array of listing IDs.
 */
function apd_get_user_favorites( ?int $user_id = null ): array {
	return apd_favorites()->get_favorites( $user_id );
}

/**
 * Get the total count of favorites for a user.
 *
 * @since 1.0.0
 *
 * @param int|null $user_id Optional. User ID. Defaults to current user.
 * @return int Favorites count.
 */
function apd_get_favorites_count( ?int $user_id = null ): int {
	return apd_favorites()->get_count( $user_id );
}

/**
 * Get the number of users who have favorited a listing.
 *
 * @since 1.0.0
 *
 * @param int $listing_id Listing post ID.
 * @return int Favorite count.
 */
function apd_get_listing_favorites_count( int $listing_id ): int {
	return apd_favorites()->get_listing_favorite_count( $listing_id );
}

/**
 * Check if login is required for favorites.
 *
 * @since 1.0.0
 *
 * @return bool True if login is required.
 */
function apd_favorites_require_login(): bool {
	return apd_favorites()->requires_login();
}

/**
 * Clear all favorites for a user.
 *
 * @since 1.0.0
 *
 * @param int|null $user_id Optional. User ID. Defaults to current user.
 * @return bool True on success.
 */
function apd_clear_favorites( ?int $user_id = null ): bool {
	return apd_favorites()->clear( $user_id );
}

/**
 * Get user's favorite listings as WP_Post objects.
 *
 * @since 1.0.0
 *
 * @param int|null $user_id Optional. User ID. Defaults to current user.
 * @param array    $args    Optional. Additional WP_Query arguments.
 * @return \WP_Post[] Array of listing posts.
 */
function apd_get_favorite_listings( ?int $user_id = null, array $args = [] ): array {
	$favorite_ids = apd_get_user_favorites( $user_id );

	if ( empty( $favorite_ids ) ) {
		return [];
	}

	$defaults = [
		'post_type'      => 'apd_listing',
		'post_status'    => 'publish',
		'post__in'       => $favorite_ids,
		'orderby'        => 'post__in',
		'posts_per_page' => count( $favorite_ids ),
		'no_found_rows'  => true,
	];

	$requested_posts_per_page = isset( $args['posts_per_page'] ) ? (int) $args['posts_per_page'] : 0;
	if ( $requested_posts_per_page > 0 ) {
		$query_args = wp_parse_args( $args, $defaults );

		/**
		 * Filter the favorite listings query arguments.
		 *
		 * @since 1.0.0
		 *
		 * @param array $query_args Query arguments.
		 * @param int[] $favorite_ids Favorite listing IDs.
		 */
		$query_args = apply_filters( 'apd_favorite_listings_query_args', $query_args, $favorite_ids );

		$query = new \WP_Query( $query_args );

		return $query->posts;
	}

	$batch_size = max( 1, (int) apply_filters( 'apd_favorite_listings_batch_size', 100 ) );
	$posts      = [];

	foreach ( array_chunk( $favorite_ids, $batch_size ) as $favorite_id_batch ) {
		$batch_defaults                   = $defaults;
		$batch_defaults['post__in']       = $favorite_id_batch;
		$batch_defaults['posts_per_page'] = count( $favorite_id_batch );

		$query_args                   = wp_parse_args( $args, $batch_defaults );
		$query_args['post__in']       = $favorite_id_batch;
		$query_args['posts_per_page'] = count( $favorite_id_batch );

		/**
		 * Filter the favorite listings query arguments.
		 *
		 * @since 1.0.0
		 *
		 * @param array $query_args Query arguments.
		 * @param int[] $favorite_ids Favorite listing IDs.
		 */
		$query_args = apply_filters( 'apd_favorite_listings_query_args', $query_args, $favorite_ids );

		$query = new \WP_Query( $query_args );
		$posts = array_merge( $posts, $query->posts );
	}

	return $posts;
}

/**
 * Get the favorite toggle instance.
 *
 * @since 1.0.0
 *
 * @return \APD\User\FavoriteToggle
 */
function apd_favorite_toggle(): \APD\User\FavoriteToggle {
	return \APD\User\FavoriteToggle::get_instance();
}

/**
 * Render the favorite button HTML.
 *
 * Outputs the favorite toggle button for a listing. Can be placed anywhere
 * in templates to allow users to add/remove listings from their favorites.
 *
 * @since 1.0.0
 *
 * @param int   $listing_id Listing post ID.
 * @param array $args       Optional. Button arguments.
 *                          - show_count: bool Whether to show favorite count. Default false.
 *                          - size: string Button size (small, medium, large). Default 'medium'.
 *                          - class: string Additional CSS classes. Default ''.
 * @return void
 */
function apd_render_favorite_button( int $listing_id, array $args = [] ): void {
	apd_favorite_toggle()->render_button( $listing_id, $args );
}

/**
 * Get the favorite button HTML as a string.
 *
 * Returns the favorite toggle button HTML for a listing. Useful when you need
 * to manipulate the HTML before outputting.
 *
 * @since 1.0.0
 *
 * @param int   $listing_id Listing post ID.
 * @param array $args       Optional. Button arguments.
 *                          - show_count: bool Whether to show favorite count. Default false.
 *                          - size: string Button size (small, medium, large). Default 'medium'.
 *                          - class: string Additional CSS classes. Default ''.
 * @return string Button HTML.
 */
function apd_get_favorite_button( int $listing_id, array $args = [] ): string {
	return apd_favorite_toggle()->get_button( $listing_id, $args );
}

/**
 * Get the FavoritesPage instance.
 *
 * @since 1.0.0
 *
 * @param array<string, mixed> $config Optional. Configuration options.
 * @return \APD\Frontend\Dashboard\FavoritesPage
 */
function apd_favorites_page( array $config = [] ): \APD\Frontend\Dashboard\FavoritesPage {
	return \APD\Frontend\Dashboard\FavoritesPage::get_instance( $config );
}

/**
 * Render the favorites page.
 *
 * Outputs the favorites page content for the current user.
 * Typically used within the dashboard or a custom page template.
 *
 * @since 1.0.0
 *
 * @param array<string, mixed> $config Optional. Configuration options.
 *                                     - per_page: int Number of favorites per page. Default 12.
 *                                     - show_view_toggle: bool Show grid/list toggle. Default true.
 *                                     - columns: int Number of grid columns. Default 4.
 * @return void
 */
function apd_render_favorites_page( array $config = [] ): void {
	$page = apd_favorites_page( $config );
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped in template.
	echo $page->render();
}

// ============================================================================
// Review Functions
// ============================================================================

/**
 * Get the Review Manager instance.
 *
 * @since 1.0.0
 *
 * @return \APD\Review\ReviewManager
 */
function apd_review_manager(): \APD\Review\ReviewManager {
	return \APD\Review\ReviewManager::get_instance();
}

/**
 * Create a new review for a listing.
 *
 * @since 1.0.0
 *
 * @param int   $listing_id Listing post ID.
 * @param array $data       Review data.
 *                          - rating: (int) Required. Rating 1-5.
 *                          - content: (string) Required. Review text.
 *                          - title: (string) Optional. Review title.
 *                          - author_name: (string) Guest name (if not logged in).
 *                          - author_email: (string) Guest email (if not logged in).
 *                          - user_id: (int) Override user ID.
 * @return int|\WP_Error Review ID on success, WP_Error on failure.
 */
function apd_create_review( int $listing_id, array $data ): int|\WP_Error {
	return apd_review_manager()->create( $listing_id, $data );
}

/**
 * Get a review by ID.
 *
 * @since 1.0.0
 *
 * @param int $review_id Review (comment) ID.
 * @return array|null Review data array or null if not found.
 */
function apd_get_review( int $review_id ): ?array {
	return apd_review_manager()->get( $review_id );
}

/**
 * Get reviews for a listing.
 *
 * @since 1.0.0
 *
 * @param int   $listing_id Listing post ID.
 * @param array $args       Query arguments.
 *                          - status: (string) Review status (approved, pending, all). Default 'approved'.
 *                          - orderby: (string) Order by field (date, rating). Default 'date'.
 *                          - order: (string) Order direction (ASC, DESC). Default 'DESC'.
 *                          - number: (int) Number of reviews. Default 10.
 *                          - offset: (int) Number to skip. Default 0.
 *                          - rating: (int) Filter by specific rating.
 * @return array{reviews: array[], total: int, pages: int} Reviews data with pagination info.
 */
function apd_get_listing_reviews( int $listing_id, array $args = [] ): array {
	return apd_review_manager()->get_listing_reviews( $listing_id, $args );
}

/**
 * Get a user's review for a specific listing.
 *
 * @since 1.0.0
 *
 * @param int $listing_id Listing post ID.
 * @param int $user_id    User ID.
 * @return array|null Review data or null if not found.
 */
function apd_get_user_review( int $listing_id, int $user_id ): ?array {
	return apd_review_manager()->get_user_review( $listing_id, $user_id );
}

/**
 * Check if a user has reviewed a listing.
 *
 * @since 1.0.0
 *
 * @param int $listing_id Listing post ID.
 * @param int $user_id    User ID.
 * @return bool True if user has reviewed the listing.
 */
function apd_has_user_reviewed( int $listing_id, int $user_id ): bool {
	return apd_review_manager()->has_user_reviewed( $listing_id, $user_id );
}

/**
 * Get the review count for a listing.
 *
 * @since 1.0.0
 *
 * @param int    $listing_id Listing post ID.
 * @param string $status     Review status (approved, pending, all). Default 'approved'.
 * @return int Review count.
 */
function apd_get_review_count( int $listing_id, string $status = 'approved' ): int {
	return apd_review_manager()->get_review_count( $listing_id, $status );
}

/**
 * Delete a review.
 *
 * @since 1.0.0
 *
 * @param int  $review_id    Review (comment) ID.
 * @param bool $force_delete Whether to permanently delete. Default false (move to trash).
 * @return bool True on success, false on failure.
 */
function apd_delete_review( int $review_id, bool $force_delete = false ): bool {
	return apd_review_manager()->delete( $review_id, $force_delete );
}

/**
 * Approve a pending review.
 *
 * @since 1.0.0
 *
 * @param int $review_id Review (comment) ID.
 * @return bool True on success, false on failure.
 */
function apd_approve_review( int $review_id ): bool {
	return apd_review_manager()->approve( $review_id );
}

/**
 * Check if login is required to submit reviews.
 *
 * @since 1.0.0
 *
 * @return bool True if login is required.
 */
function apd_reviews_require_login(): bool {
	return apd_review_manager()->requires_login();
}

// ============================================================================
// Rating Calculator Functions
// ============================================================================

/**
 * Get the Rating Calculator instance.
 *
 * @since 1.0.0
 *
 * @return \APD\Review\RatingCalculator
 */
function apd_rating_calculator(): \APD\Review\RatingCalculator {
	return \APD\Review\RatingCalculator::get_instance();
}

/**
 * Get the average rating for a listing.
 *
 * @since 1.0.0
 *
 * @param int $listing_id Listing post ID.
 * @return float Average rating (0 if no ratings).
 */
function apd_get_listing_rating( int $listing_id ): float {
	return apd_rating_calculator()->get_average( $listing_id );
}

/**
 * Get the rating count for a listing.
 *
 * @since 1.0.0
 *
 * @param int $listing_id Listing post ID.
 * @return int Number of ratings.
 */
function apd_get_listing_rating_count( int $listing_id ): int {
	return apd_rating_calculator()->get_count( $listing_id );
}

/**
 * Get the rating distribution for a listing.
 *
 * Returns an array of counts per star rating.
 *
 * @since 1.0.0
 *
 * @param int $listing_id Listing post ID.
 * @return array<int, int> Distribution array keyed by star rating.
 */
function apd_get_rating_distribution( int $listing_id ): array {
	return apd_rating_calculator()->get_distribution( $listing_id );
}

/**
 * Render star rating HTML.
 *
 * @since 1.0.0
 *
 * @param float $rating The rating value (0-5).
 * @param array $args   Display options.
 *                      - size: (string) Size variant (small, medium, large). Default 'medium'.
 *                      - show_count: (bool) Whether to show rating count. Default false.
 *                      - show_average: (bool) Whether to show average number. Default false.
 *                      - count: (int) Number of ratings (for show_count).
 *                      - inline: (bool) Display inline. Default true.
 * @return void Outputs HTML directly.
 */
function apd_render_star_rating( float $rating, array $args = [] ): void {
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped in render method.
	echo apd_rating_calculator()->render_stars( $rating, $args );
}

/**
 * Get star rating HTML as a string.
 *
 * @since 1.0.0
 *
 * @param float $rating The rating value (0-5).
 * @param array $args   Display options (see apd_render_star_rating).
 * @return string HTML output.
 */
function apd_get_star_rating( float $rating, array $args = [] ): string {
	return apd_rating_calculator()->render_stars( $rating, $args );
}

/**
 * Render star rating HTML for a listing.
 *
 * Convenience function that fetches rating data for a listing.
 *
 * @since 1.0.0
 *
 * @param int   $listing_id Listing post ID.
 * @param array $args       Display options (see apd_render_star_rating).
 * @return void Outputs HTML directly.
 */
function apd_render_listing_star_rating( int $listing_id, array $args = [] ): void {
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped in render method.
	echo apd_rating_calculator()->render_listing_stars( $listing_id, $args );
}

/**
 * Get star rating HTML for a listing as a string.
 *
 * Convenience function that fetches rating data for a listing.
 *
 * @since 1.0.0
 *
 * @param int   $listing_id Listing post ID.
 * @param array $args       Display options (see apd_render_star_rating).
 * @return string HTML output.
 */
function apd_get_listing_star_rating( int $listing_id, array $args = [] ): string {
	return apd_rating_calculator()->render_listing_stars( $listing_id, $args );
}

/**
 * Force recalculate rating statistics for a listing.
 *
 * @since 1.0.0
 *
 * @param int $listing_id Listing post ID.
 * @return array{average: float, count: int, distribution: array<int, int>} Rating statistics.
 */
function apd_recalculate_listing_rating( int $listing_id ): array {
	return apd_rating_calculator()->recalculate( $listing_id );
}

/**
 * Invalidate cached rating for a listing.
 *
 * Clears the cached values. Next call to get functions will trigger recalculation.
 *
 * @since 1.0.0
 *
 * @param int $listing_id Listing post ID.
 * @return void
 */
function apd_invalidate_listing_rating( int $listing_id ): void {
	apd_rating_calculator()->invalidate( $listing_id );
}

// ============================================================================
// Review Form Functions
// ============================================================================

/**
 * Get the Review Form instance.
 *
 * @since 1.0.0
 *
 * @return \APD\Review\ReviewForm
 */
function apd_review_form(): \APD\Review\ReviewForm {
	return \APD\Review\ReviewForm::get_instance();
}

/**
 * Render the review submission form for a listing.
 *
 * @since 1.0.0
 *
 * @param int $listing_id Listing post ID.
 * @return void Outputs HTML directly.
 */
function apd_render_review_form( int $listing_id ): void {
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped in render method.
	echo apd_review_form()->render( $listing_id );
}

/**
 * Get the review form HTML as a string.
 *
 * @since 1.0.0
 *
 * @param int $listing_id Listing post ID.
 * @return string HTML output.
 */
function apd_get_review_form( int $listing_id ): string {
	return apd_review_form()->render( $listing_id );
}

/**
 * Get the Review Handler instance.
 *
 * @since 1.0.0
 *
 * @return \APD\Review\ReviewHandler
 */
function apd_review_handler(): \APD\Review\ReviewHandler {
	return \APD\Review\ReviewHandler::get_instance();
}

/**
 * Check if the current user has reviewed a listing.
 *
 * Convenience function that uses the current user ID.
 *
 * @since 1.0.0
 *
 * @param int $listing_id Listing post ID.
 * @return bool True if current user has reviewed the listing.
 */
function apd_current_user_has_reviewed( int $listing_id ): bool {
	$user_id = get_current_user_id();

	if ( $user_id <= 0 ) {
		return false;
	}

	return apd_has_user_reviewed( $listing_id, $user_id );
}

/**
 * Get the current user's review for a listing.
 *
 * Convenience function that uses the current user ID.
 *
 * @since 1.0.0
 *
 * @param int $listing_id Listing post ID.
 * @return array|null Review data or null if not found.
 */
function apd_get_current_user_review( int $listing_id ): ?array {
	$user_id = get_current_user_id();

	if ( $user_id <= 0 ) {
		return null;
	}

	return apd_get_user_review( $listing_id, $user_id );
}

/**
 * Update an existing review.
 *
 * @since 1.0.0
 *
 * @param int   $review_id Review (comment) ID.
 * @param array $data      Review data to update.
 *                         - rating: (int) Rating 1-5.
 *                         - content: (string) Review text.
 *                         - title: (string) Review title.
 * @return bool|\WP_Error True on success, WP_Error on failure.
 */
function apd_update_review( int $review_id, array $data ): bool|\WP_Error {
	return apd_review_manager()->update( $review_id, $data );
}

// ============================================================================
// Review Display Functions
// ============================================================================

/**
 * Get the Review Display instance.
 *
 * @since 1.0.0
 *
 * @return \APD\Review\ReviewDisplay
 */
function apd_review_display(): \APD\Review\ReviewDisplay {
	return \APD\Review\ReviewDisplay::get_instance();
}

/**
 * Render the full reviews section for a listing.
 *
 * Includes rating summary, review form, reviews list, and pagination.
 *
 * @since 1.0.0
 *
 * @param int   $listing_id Listing post ID.
 * @param array $args       Optional display arguments.
 *                          - per_page: (int) Reviews per page. Default 10.
 *                          - show_summary: (bool) Show rating summary. Default true.
 *                          - show_form: (bool) Show review form. Default true.
 *                          - show_pagination: (bool) Show pagination. Default true.
 * @return void Outputs HTML directly.
 */
function apd_render_reviews_section( int $listing_id, array $args = [] ): void {
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped in render method.
	echo apd_review_display()->render( $listing_id, $args );
}

/**
 * Get the reviews section HTML as a string.
 *
 * @since 1.0.0
 *
 * @param int   $listing_id Listing post ID.
 * @param array $args       Optional display arguments (see apd_render_reviews_section).
 * @return string HTML output.
 */
function apd_get_reviews_section( int $listing_id, array $args = [] ): string {
	return apd_review_display()->render( $listing_id, $args );
}

/**
 * Render the rating summary box for a listing.
 *
 * Shows average rating and distribution bars.
 *
 * @since 1.0.0
 *
 * @param int $listing_id Listing post ID.
 * @return void Outputs HTML directly.
 */
function apd_render_rating_summary( int $listing_id ): void {
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped in render method.
	echo apd_review_display()->render_summary( $listing_id );
}

/**
 * Get the rating summary HTML as a string.
 *
 * @since 1.0.0
 *
 * @param int $listing_id Listing post ID.
 * @return string HTML output.
 */
function apd_get_rating_summary( int $listing_id ): string {
	return apd_review_display()->render_summary( $listing_id );
}

/**
 * Render the reviews list for a listing.
 *
 * @since 1.0.0
 *
 * @param int   $listing_id Listing post ID.
 * @param array $args       Query arguments.
 *                          - number: (int) Number of reviews. Default 10.
 *                          - offset: (int) Offset. Default 0.
 *                          - orderby: (string) Order by (date, rating). Default 'date'.
 *                          - order: (string) Order direction. Default 'DESC'.
 * @return void Outputs HTML directly.
 */
function apd_render_reviews_list( int $listing_id, array $args = [] ): void {
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped in render method.
	echo apd_review_display()->render_reviews_list( $listing_id, $args );
}

/**
 * Get the reviews list HTML as a string.
 *
 * @since 1.0.0
 *
 * @param int   $listing_id Listing post ID.
 * @param array $args       Query arguments (see apd_render_reviews_list).
 * @return string HTML output.
 */
function apd_get_reviews_list( int $listing_id, array $args = [] ): string {
	return apd_review_display()->render_reviews_list( $listing_id, $args );
}

// ============================================================================
// Contact Form Functions
// ============================================================================

/**
 * Get the ContactForm instance.
 *
 * @since 1.0.0
 *
 * @param array $config Optional configuration.
 * @return \APD\Contact\ContactForm ContactForm instance.
 */
function apd_contact_form( array $config = [] ): \APD\Contact\ContactForm {
	if ( empty( $config ) ) {
		return \APD\Contact\ContactForm::get_instance();
	}
	return new \APD\Contact\ContactForm( $config );
}

/**
 * Get the ContactHandler instance.
 *
 * @since 1.0.0
 *
 * @param array                 $config         Optional configuration.
 * @param \APD\Core\Config|null $config_service Optional configuration service.
 * @return \APD\Contact\ContactHandler ContactHandler instance.
 */
function apd_contact_handler( array $config = [], ?\APD\Core\Config $config_service = null ): \APD\Contact\ContactHandler {
	return \APD\Contact\ContactHandler::get_instance( $config, $config_service );
}

/**
 * Render the contact form for a listing.
 *
 * @since 1.0.0
 *
 * @param int   $listing_id Listing post ID.
 * @param array $config     Optional configuration.
 * @return void
 */
function apd_render_contact_form( int $listing_id, array $config = [] ): void {
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped in template.
	echo apd_get_contact_form( $listing_id, $config );
}

/**
 * Get the contact form HTML.
 *
 * @since 1.0.0
 *
 * @param int   $listing_id Listing post ID.
 * @param array $config     Optional configuration.
 * @return string HTML output.
 */
function apd_get_contact_form( int $listing_id, array $config = [] ): string {
	$form = apd_contact_form( $config );
	return $form->get_html( $listing_id );
}

/**
 * Check if a listing can receive contact messages.
 *
 * @since 1.0.0
 *
 * @param int $listing_id Listing post ID.
 * @return bool True if listing can receive messages.
 */
function apd_can_receive_contact( int $listing_id ): bool {
	return apd_contact_form()->can_receive_contact( $listing_id );
}

/**
 * Process a contact form submission.
 *
 * @since 1.0.0
 *
 * @param array $data    Form data.
 * @param array $config  Handler configuration.
 * @return true|\WP_Error True on success, WP_Error on failure.
 */
function apd_process_contact( array $data, array $config = [] ): bool|\WP_Error {
	$handler = apd_contact_handler( $config );

	// Manually set $_POST for the handler.
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Intentional programmatic use.
	$original_post = $_POST;
	$_POST         = $data;

	$result = $handler->process();

	// Restore $_POST.
	$_POST = $original_post;

	return $result;
}

/**
 * Send a contact email for a listing.
 *
 * @since 1.0.0
 *
 * @param int   $listing_id Listing post ID.
 * @param array $data       Contact data (contact_name, contact_email, contact_message, etc.).
 * @param array $config     Handler configuration.
 * @return bool True on success, false on failure.
 */
function apd_send_contact_email( int $listing_id, array $data, array $config = [] ): bool {
	$listing = get_post( $listing_id );
	if ( ! $listing || 'apd_listing' !== $listing->post_type ) {
		return false;
	}

	$owner = get_userdata( $listing->post_author );
	if ( ! $owner ) {
		return false;
	}

	$handler = apd_contact_handler( $config );
	return $handler->send_email( $data, $listing, $owner );
}

// =============================================================================
// Inquiry Tracker Functions
// =============================================================================

/**
 * Get the InquiryTracker instance.
 *
 * @since 1.0.0
 *
 * @return \APD\Contact\InquiryTracker InquiryTracker instance.
 */
function apd_inquiry_tracker(): \APD\Contact\InquiryTracker {
	return \APD\Contact\InquiryTracker::get_instance();
}

/**
 * Log an inquiry from contact form data.
 *
 * @since 1.0.0
 *
 * @param array    $data    Form data.
 * @param \WP_Post $listing Listing post.
 * @param \WP_User $owner   Listing owner.
 * @return int|false Inquiry ID on success, false on failure.
 */
function apd_log_inquiry( array $data, \WP_Post $listing, \WP_User $owner ): int|false {
	return apd_inquiry_tracker()->log_inquiry( $data, $listing, $owner );
}

/**
 * Save an inquiry to the database.
 *
 * @since 1.0.0
 *
 * @param array $data Inquiry data (listing_id, sender_name, sender_email, sender_phone, subject, message).
 * @return int|false Inquiry ID on success, false on failure.
 */
function apd_save_inquiry( array $data ): int|false {
	return apd_inquiry_tracker()->save_inquiry( $data );
}

/**
 * Get a single inquiry by ID.
 *
 * @since 1.0.0
 *
 * @param int $inquiry_id Inquiry ID.
 * @return array|null Inquiry data or null if not found.
 */
function apd_get_inquiry( int $inquiry_id ): ?array {
	return apd_inquiry_tracker()->get_inquiry( $inquiry_id );
}

/**
 * Get inquiries for a listing.
 *
 * @since 1.0.0
 *
 * @param int   $listing_id Listing ID.
 * @param array $args       Query arguments (number, offset, orderby, order, status).
 * @return array Array of inquiry data.
 */
function apd_get_listing_inquiries( int $listing_id, array $args = [] ): array {
	return apd_inquiry_tracker()->get_listing_inquiries( $listing_id, $args );
}

/**
 * Get inquiries for a user (all their listings).
 *
 * @since 1.0.0
 *
 * @param int   $user_id User ID. Defaults to current user.
 * @param array $args    Query arguments (number, offset, orderby, order, status, listing_id).
 * @return array Array of inquiry data.
 */
function apd_get_user_inquiries( int $user_id = 0, array $args = [] ): array {
	if ( $user_id <= 0 ) {
		$user_id = get_current_user_id();
	}
	return apd_inquiry_tracker()->get_user_inquiries( $user_id, $args );
}

/**
 * Get inquiry count for a listing.
 *
 * @since 1.0.0
 *
 * @param int    $listing_id Listing ID.
 * @param string $status     Status filter (all, read, unread).
 * @return int Inquiry count.
 */
function apd_get_listing_inquiry_count( int $listing_id, string $status = 'all' ): int {
	return apd_inquiry_tracker()->get_listing_inquiry_count( $listing_id, $status );
}

/**
 * Get inquiry count for a user.
 *
 * @since 1.0.0
 *
 * @param int    $user_id User ID. Defaults to current user.
 * @param string $status  Status filter (all, read, unread).
 * @return int Inquiry count.
 */
function apd_get_user_inquiry_count( int $user_id = 0, string $status = 'all' ): int {
	if ( $user_id <= 0 ) {
		$user_id = get_current_user_id();
	}
	return apd_inquiry_tracker()->count_user_inquiries( $user_id, $status );
}

/**
 * Mark an inquiry as read.
 *
 * @since 1.0.0
 *
 * @param int $inquiry_id Inquiry ID.
 * @return bool True on success.
 */
function apd_mark_inquiry_read( int $inquiry_id ): bool {
	return apd_inquiry_tracker()->mark_as_read( $inquiry_id );
}

/**
 * Mark an inquiry as unread.
 *
 * @since 1.0.0
 *
 * @param int $inquiry_id Inquiry ID.
 * @return bool True on success.
 */
function apd_mark_inquiry_unread( int $inquiry_id ): bool {
	return apd_inquiry_tracker()->mark_as_unread( $inquiry_id );
}

/**
 * Delete an inquiry.
 *
 * @since 1.0.0
 *
 * @param int  $inquiry_id   Inquiry ID.
 * @param bool $force_delete Whether to bypass trash.
 * @return bool True on success.
 */
function apd_delete_inquiry( int $inquiry_id, bool $force_delete = false ): bool {
	return apd_inquiry_tracker()->delete_inquiry( $inquiry_id, $force_delete );
}

/**
 * Check if a user can view an inquiry.
 *
 * @since 1.0.0
 *
 * @param int $inquiry_id Inquiry ID.
 * @param int $user_id    User ID. Defaults to current user.
 * @return bool True if user can view.
 */
function apd_can_view_inquiry( int $inquiry_id, int $user_id = 0 ): bool {
	if ( $user_id <= 0 ) {
		$user_id = get_current_user_id();
	}
	return apd_inquiry_tracker()->can_user_view( $inquiry_id, $user_id );
}

/**
 * Recalculate inquiry count for a listing.
 *
 * @since 1.0.0
 *
 * @param int $listing_id Listing ID.
 * @return int Recalculated count.
 */
function apd_recalculate_listing_inquiry_count( int $listing_id ): int {
	return apd_inquiry_tracker()->recalculate_listing_count( $listing_id );
}

// -----------------------------------------------------------------------------
// Email Manager Functions
// -----------------------------------------------------------------------------

/**
 * Get the EmailManager instance.
 *
 * @since 1.0.0
 *
 * @param array                 $config         Optional configuration.
 * @param \APD\Core\Config|null $config_service Optional configuration service.
 * @return \APD\Email\EmailManager
 */
function apd_email_manager( array $config = [], ?\APD\Core\Config $config_service = null ): \APD\Email\EmailManager {
	return \APD\Email\EmailManager::get_instance( $config, $config_service );
}

/**
 * Send an email using the EmailManager.
 *
 * @since 1.0.0
 *
 * @param string $to          Recipient email address.
 * @param string $subject     Email subject (supports placeholders).
 * @param string $message     Email body (supports placeholders).
 * @param array  $headers     Optional. Additional headers.
 * @param array  $context     Optional. Placeholder context data.
 * @param array  $attachments Optional. File attachments.
 * @return bool True if email was sent successfully.
 */
function apd_send_email(
	string $to,
	string $subject,
	string $message,
	array $headers = [],
	array $context = [],
	array $attachments = []
): bool {
	return apd_email_manager()->send( $to, $subject, $message, $headers, $context, $attachments );
}

/**
 * Send a listing submitted notification to admin.
 *
 * @since 1.0.0
 *
 * @param int $listing_id Listing ID.
 * @return bool True if email was sent successfully.
 */
function apd_send_listing_submitted_email( int $listing_id ): bool {
	return apd_email_manager()->send_listing_submitted( $listing_id );
}

/**
 * Send a listing approved notification to author.
 *
 * @since 1.0.0
 *
 * @param int $listing_id Listing ID.
 * @return bool True if email was sent successfully.
 */
function apd_send_listing_approved_email( int $listing_id ): bool {
	return apd_email_manager()->send_listing_approved( $listing_id );
}

/**
 * Send a listing rejected notification to author.
 *
 * @since 1.0.0
 *
 * @param int    $listing_id Listing ID.
 * @param string $reason     Optional rejection reason.
 * @return bool True if email was sent successfully.
 */
function apd_send_listing_rejected_email( int $listing_id, string $reason = '' ): bool {
	return apd_email_manager()->send_listing_rejected( $listing_id, $reason );
}

/**
 * Send a listing expiring soon notification to author.
 *
 * @since 1.0.0
 *
 * @param int $listing_id Listing ID.
 * @param int $days_left  Days until expiration.
 * @return bool True if email was sent successfully.
 */
function apd_send_listing_expiring_email( int $listing_id, int $days_left = 7 ): bool {
	return apd_email_manager()->send_listing_expiring( $listing_id, $days_left );
}

/**
 * Send a listing expired notification to author.
 *
 * @since 1.0.0
 *
 * @param int $listing_id Listing ID.
 * @return bool True if email was sent successfully.
 */
function apd_send_listing_expired_email( int $listing_id ): bool {
	return apd_email_manager()->send_listing_expired( $listing_id );
}

/**
 * Send a new review notification to listing author.
 *
 * @since 1.0.0
 *
 * @param int $review_id Review comment ID.
 * @return bool True if email was sent successfully.
 */
function apd_send_new_review_email( int $review_id ): bool {
	return apd_email_manager()->send_new_review( $review_id );
}

/**
 * Send a new inquiry notification to listing author.
 *
 * @since 1.0.0
 *
 * @param int   $listing_id Listing ID.
 * @param array $inquiry    Inquiry data (name, email, phone, message).
 * @return bool True if email was sent successfully.
 */
function apd_send_new_inquiry_email( int $listing_id, array $inquiry ): bool {
	return apd_email_manager()->send_new_inquiry( $listing_id, $inquiry );
}

/**
 * Check if an email notification type is enabled.
 *
 * @since 1.0.0
 *
 * @param string $type Notification type.
 * @return bool True if enabled.
 */
function apd_is_email_notification_enabled( string $type ): bool {
	return apd_email_manager()->is_notification_enabled( $type );
}

/**
 * Enable or disable an email notification type.
 *
 * @since 1.0.0
 *
 * @param string $type    Notification type.
 * @param bool   $enabled Whether to enable.
 * @return void
 */
function apd_set_email_notification_enabled( string $type, bool $enabled ): void {
	apd_email_manager()->set_notification_enabled( $type, $enabled );
}

/**
 * Register a custom email placeholder.
 *
 * @since 1.0.0
 *
 * @param string   $name     Placeholder name (without braces).
 * @param callable $callback Callback that returns the replacement value.
 * @return void
 */
function apd_register_email_placeholder( string $name, callable $callback ): void {
	apd_email_manager()->register_placeholder( $name, $callback );
}

/**
 * Replace placeholders in text using the EmailManager.
 *
 * @since 1.0.0
 *
 * @param string $text    Text containing placeholders.
 * @param array  $context Additional context data.
 * @return string Processed text.
 */
function apd_replace_email_placeholders( string $text, array $context = [] ): string {
	return apd_email_manager()->replace_placeholders( $text, $context );
}

/**
 * Get listing context for email placeholders.
 *
 * @since 1.0.0
 *
 * @param int|\WP_Post $listing Listing ID or post object.
 * @return array Context array with listing data.
 */
function apd_get_email_listing_context( int|\WP_Post $listing ): array {
	return apd_email_manager()->get_listing_context( $listing );
}

/**
 * Get user context for email placeholders.
 *
 * @since 1.0.0
 *
 * @param int|\WP_User $user User ID or object.
 * @return array Context array with user data.
 */
function apd_get_email_user_context( int|\WP_User $user ): array {
	return apd_email_manager()->get_user_context( $user );
}

/**
 * Get review context for email placeholders.
 *
 * @since 1.0.0
 *
 * @param int|\WP_Comment $review Review ID or comment object.
 * @return array Context array with review data.
 */
function apd_get_email_review_context( int|\WP_Comment $review ): array {
	return apd_email_manager()->get_review_context( $review );
}

/**
 * Get the admin email for notifications.
 *
 * @since 1.0.0
 *
 * @return string Admin email address.
 */
function apd_get_notification_admin_email(): string {
	return apd_email_manager()->get_admin_email();
}

/**
 * Get the from name for emails.
 *
 * @since 1.0.0
 *
 * @return string From name.
 */
function apd_get_email_from_name(): string {
	return apd_email_manager()->get_from_name();
}

/**
 * Get the from email for emails.
 *
 * @since 1.0.0
 *
 * @return string From email address.
 */
function apd_get_email_from_email(): string {
	return apd_email_manager()->get_from_email();
}

// =============================================================================
// Admin Settings Functions
// =============================================================================

/**
 * Get the Settings instance.
 *
 * @since 1.0.0
 *
 * @return \APD\Admin\Settings
 */
function apd_settings(): \APD\Admin\Settings {
	return \APD\Admin\Settings::get_instance();
}

/**
 * Get a single setting value.
 *
 * @since 1.0.0
 *
 * @param string $key     Setting key.
 * @param mixed  $default Default value if not set.
 * @return mixed
 */
function apd_get_setting( string $key, mixed $default = null ): mixed {
	return apd_settings()->get( $key, $default );
}

/**
 * Update a single setting value.
 *
 * @since 1.0.0
 *
 * @param string $key   Setting key.
 * @param mixed  $value Setting value.
 * @return bool True if updated, false otherwise.
 */
function apd_set_setting( string $key, mixed $value ): bool {
	return apd_settings()->set( $key, $value );
}

/**
 * Get all settings with defaults applied.
 *
 * @since 1.0.0
 *
 * @return array<string, mixed> All settings.
 */
function apd_get_all_settings(): array {
	return apd_settings()->get_all();
}

/**
 * Get the default settings.
 *
 * @since 1.0.0
 *
 * @return array<string, mixed> Default settings.
 */
function apd_get_default_settings(): array {
	return apd_settings()->get_defaults();
}

/**
 * Get the settings page URL.
 *
 * @since 1.0.0
 *
 * @param string $tab Optional tab to link to.
 * @return string Settings page URL.
 */
function apd_get_settings_url( string $tab = '' ): string {
	return apd_settings()->get_settings_url( $tab );
}

/**
 * Check if reviews are enabled.
 *
 * @since 1.0.0
 *
 * @return bool True if reviews are enabled.
 */
function apd_reviews_enabled(): bool {
	return (bool) apd_get_setting( 'enable_reviews', true );
}

/**
 * Check if favorites are enabled.
 *
 * @since 1.0.0
 *
 * @return bool True if favorites are enabled.
 */
function apd_favorites_enabled(): bool {
	return (bool) apd_get_setting( 'enable_favorites', true );
}

/**
 * Check if the contact form is enabled.
 *
 * @since 1.0.0
 *
 * @return bool True if contact form is enabled.
 */
function apd_contact_form_enabled(): bool {
	return (bool) apd_get_setting( 'enable_contact_form', true );
}

/**
 * Get the number of listings per page.
 *
 * @since 1.0.0
 *
 * @return int Number of listings per page.
 */
function apd_get_listings_per_page(): int {
	return (int) apd_get_setting( 'listings_per_page', 12 );
}

/**
 * Get the default listing view.
 *
 * @since 1.0.0
 *
 * @return string View type (grid or list).
 */
function apd_get_default_view(): string {
	return apd_get_setting( 'default_view', 'grid' );
}

/**
 * Get the number of grid columns.
 *
 * @since 1.0.0
 *
 * @return int Number of columns (2-4).
 */
function apd_get_default_grid_columns(): int {
	return (int) apd_get_setting( 'grid_columns', 3 );
}

/**
 * Get the currency symbol.
 *
 * @since 1.0.0
 *
 * @return string Currency symbol.
 */
function apd_get_currency_symbol(): string {
	return apd_get_setting( 'currency_symbol', '$' );
}

/**
 * Get the currency position.
 *
 * @since 1.0.0
 *
 * @return string Currency position (before or after).
 */
function apd_get_currency_position(): string {
	return apd_get_setting( 'currency_position', 'before' );
}

/**
 * Format a price with currency symbol.
 *
 * @since 1.0.0
 *
 * @param float|int|string $amount Amount to format.
 * @return string Formatted price with currency.
 */
function apd_format_price( float|int|string $amount ): string {
	$symbol   = apd_get_currency_symbol();
	$position = apd_get_currency_position();
	$amount   = number_format( (float) $amount, 2 );

	if ( $position === 'after' ) {
		return $amount . $symbol;
	}

	return $symbol . $amount;
}

/**
 * Get the distance unit.
 *
 * @since 1.0.0
 *
 * @return string Distance unit (km or miles).
 */
function apd_get_distance_unit(): string {
	return apd_get_setting( 'distance_unit', 'km' );
}

/**
 * Get the listing date format.
 *
 * Returns the plugin's configured date format, or falls back to the
 * WordPress general date format setting.
 *
 * @since 1.0.0
 *
 * @return string PHP date format string.
 */
function apd_get_listing_date_format(): string {
	$format = apd_get_setting( 'date_format', 'default' );

	return ( 'default' === $format || empty( $format ) )
		? (string) get_option( 'date_format', 'Y-m-d' )
		: $format;
}

/**
 * Get a formatted listing date.
 *
 * @since 1.0.0
 *
 * @param int|\WP_Post|null $post Optional. Post ID or object. Default current post.
 * @return string Formatted date string.
 */
function apd_get_listing_date( int|\WP_Post|null $post = null ): string {
	return get_the_date( apd_get_listing_date_format(), $post );
}

/**
 * Check if debug mode is enabled.
 *
 * @since 1.0.0
 *
 * @return bool True if debug mode is enabled.
 */
function apd_is_debug_mode(): bool {
	return (bool) apd_get_setting( 'debug_mode', false );
}

/**
 * Log a debug message if debug mode is enabled.
 *
 * @since 1.0.0
 *
 * @param string $message Message to log.
 * @param array  $context Optional context data.
 * @return void
 */
function apd_debug_log( string $message, array $context = [] ): void {
	if ( ! apd_is_debug_mode() ) {
		return;
	}

	$log_message = '[APD Debug] ' . $message;

	if ( ! empty( $context ) ) {
		$log_message .= ' | Context: ' . wp_json_encode( $context );
	}

    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	error_log( $log_message );
}

// =============================================================================
// REST API Functions
// =============================================================================

/**
 * Get the REST API controller instance.
 *
 * @since 1.0.0
 *
 * @return \APD\Api\RestController
 */
function apd_rest_controller(): \APD\Api\RestController {
	return \APD\Api\RestController::get_instance();
}

/**
 * Get the REST API namespace.
 *
 * @since 1.0.0
 *
 * @return string The namespace (e.g., 'apd/v1').
 */
function apd_get_rest_namespace(): string {
	return \APD\Api\RestController::NAMESPACE;
}

/**
 * Get a REST API URL for an endpoint.
 *
 * Builds a full URL to a REST API endpoint using the plugin's namespace.
 *
 * @since 1.0.0
 *
 * @param string $route Endpoint route (without namespace).
 * @return string Full REST URL.
 */
function apd_get_rest_url( string $route = '' ): string {
	return apd_rest_controller()->get_rest_url( $route );
}

/**
 * Register a REST API endpoint controller.
 *
 * Registers an endpoint controller that will have its register_routes()
 * method called when the REST API is initialized.
 *
 * @since 1.0.0
 *
 * @param string $name     Unique endpoint identifier.
 * @param object $endpoint Endpoint controller instance.
 * @return void
 */
function apd_register_rest_endpoint( string $name, object $endpoint ): void {
	apd_rest_controller()->register_endpoint( $name, $endpoint );
}

/**
 * Unregister a REST API endpoint controller.
 *
 * @since 1.0.0
 *
 * @param string $name Endpoint identifier to remove.
 * @return bool True if removed, false if not found.
 */
function apd_unregister_rest_endpoint( string $name ): bool {
	return apd_rest_controller()->unregister_endpoint( $name );
}

/**
 * Check if a REST API endpoint is registered.
 *
 * @since 1.0.0
 *
 * @param string $name Endpoint identifier.
 * @return bool True if registered, false otherwise.
 */
function apd_has_rest_endpoint( string $name ): bool {
	return apd_rest_controller()->has_endpoint( $name );
}

/**
 * Get a registered REST API endpoint controller.
 *
 * @since 1.0.0
 *
 * @param string $name Endpoint identifier.
 * @return object|null Endpoint controller or null if not found.
 */
function apd_get_rest_endpoint( string $name ): ?object {
	return apd_rest_controller()->get_endpoint( $name );
}

/**
 * Create a standardized REST API response.
 *
 * Helper function to create consistent API responses across endpoints.
 *
 * @since 1.0.0
 *
 * @param mixed $data    Response data.
 * @param int   $status  HTTP status code. Default 200.
 * @param array $headers Optional additional headers.
 * @return \WP_REST_Response
 */
function apd_rest_response( mixed $data, int $status = 200, array $headers = [] ): \WP_REST_Response {
	return apd_rest_controller()->create_response( $data, $status, $headers );
}

/**
 * Create a standardized REST API error response.
 *
 * Helper function to create consistent error responses across endpoints.
 *
 * @since 1.0.0
 *
 * @param string $code    Error code.
 * @param string $message Error message.
 * @param int    $status  HTTP status code. Default 400.
 * @param array  $data    Optional additional error data.
 * @return \WP_Error
 */
function apd_rest_error( string $code, string $message, int $status = 400, array $data = [] ): \WP_Error {
	return apd_rest_controller()->create_error( $code, $message, $status, $data );
}

/**
 * Create a paginated REST API response.
 *
 * Creates a response with pagination metadata and headers.
 *
 * @since 1.0.0
 *
 * @param array $items      Items for the current page.
 * @param int   $total      Total number of items.
 * @param int   $page       Current page number.
 * @param int   $per_page   Items per page.
 * @param array $extra_data Optional additional data to include.
 * @return \WP_REST_Response
 */
function apd_rest_paginated_response(
	array $items,
	int $total,
	int $page,
	int $per_page,
	array $extra_data = []
): \WP_REST_Response {
	return apd_rest_controller()->create_paginated_response(
		$items,
		$total,
		$page,
		$per_page,
		$extra_data
	);
}

// ============================================================================
// String Utility Functions
// ============================================================================

/**
 * Get the length of a string, with mbstring fallback.
 *
 * Uses mb_strlen() when available for proper multi-byte character counting,
 * falls back to strlen() on hosts without ext-mbstring.
 *
 * @since 1.0.0
 *
 * @param string $string The string to measure.
 * @return int String length in characters.
 */
function apd_strlen( string $string ): int {
	if ( function_exists( 'mb_strlen' ) ) {
		return mb_strlen( $string, 'UTF-8' );
	}

	return strlen( $string );
}

/**
 * Get part of a string, with mbstring fallback.
 *
 * Uses mb_substr() when available for proper multi-byte character handling,
 * falls back to substr() on hosts without ext-mbstring.
 *
 * @since 1.0.0
 *
 * @param string   $string The string to extract from.
 * @param int      $start  Start position.
 * @param int|null $length Maximum characters to return. Null for remainder of string.
 * @return string The extracted substring.
 */
function apd_substr( string $string, int $start, ?int $length = null ): string {
	if ( function_exists( 'mb_substr' ) ) {
		return mb_substr( $string, $start, $length, 'UTF-8' );
	}

	if ( $length === null ) {
		return substr( $string, $start );
	}

	return substr( $string, $start, $length );
}

// ============================================================================
// Performance Functions
// ============================================================================

/**
 * Get the Performance instance.
 *
 * @since 1.0.0
 *
 * @return \APD\Core\Performance
 */
function apd_performance(): \APD\Core\Performance {
	return \APD\Core\Performance::get_instance();
}

/**
 * Get a cached value or compute and cache it.
 *
 * This is the primary caching function. Pass a callback that generates
 * the value, and it will be cached for subsequent calls.
 *
 * @since 1.0.0
 *
 * @param string   $key        Cache key.
 * @param callable $callback   Function to generate value if not cached.
 * @param int      $expiration Cache expiration in seconds. Default 3600 (1 hour).
 * @return mixed Cached or generated value.
 */
function apd_cache_remember( string $key, callable $callback, int $expiration = HOUR_IN_SECONDS ): mixed {
	return apd_performance()->remember( $key, $callback, $expiration );
}

/**
 * Get a cached value.
 *
 * @since 1.0.0
 *
 * @param string $key Cache key.
 * @return mixed|false Cached value or false if not found.
 */
function apd_cache_get( string $key ): mixed {
	return apd_performance()->get( $key );
}

/**
 * Set a cached value.
 *
 * @since 1.0.0
 *
 * @param string $key        Cache key.
 * @param mixed  $value      Value to cache.
 * @param int    $expiration Cache expiration in seconds. Default 3600 (1 hour).
 * @return bool True on success.
 */
function apd_cache_set( string $key, mixed $value, int $expiration = HOUR_IN_SECONDS ): bool {
	return apd_performance()->set( $key, $value, $expiration );
}

/**
 * Delete a cached value.
 *
 * @since 1.0.0
 *
 * @param string $key Cache key.
 * @return bool True on success.
 */
function apd_cache_delete( string $key ): bool {
	return apd_performance()->delete( $key );
}

/**
 * Clear all plugin caches.
 *
 * Clears all transients and object cache entries created by the plugin.
 *
 * @since 1.0.0
 *
 * @return int Number of deleted cache entries.
 */
function apd_cache_clear_all(): int {
	return apd_performance()->clear_all();
}

/**
 * Get categories with counts (cached).
 *
 * Caches the result of get_terms for category queries to reduce database load.
 *
 * @since 1.0.0
 *
 * @param array $args Query arguments for get_terms.
 * @return array Array of WP_Term objects.
 */
function apd_get_cached_categories( array $args = [] ): array {
	return apd_performance()->get_categories_with_counts( $args );
}

/**
 * Get related listings (cached).
 *
 * Returns listings in the same category as the given listing.
 * Results are cached to reduce database queries.
 *
 * @since 1.0.0
 *
 * @param int   $listing_id Listing ID.
 * @param int   $limit      Number of related listings. Default 4.
 * @param array $args       Additional query arguments.
 * @return array Array of WP_Post objects.
 */
function apd_get_cached_related_listings( int $listing_id, int $limit = 4, array $args = [] ): array {
	return apd_performance()->get_related_listings( $listing_id, $limit, $args );
}

/**
 * Get dashboard stats (cached).
 *
 * Returns user's dashboard statistics including listing counts, views, and favorites.
 * Results are cached to reduce expensive aggregate queries.
 *
 * @since 1.0.0
 *
 * @param int $user_id User ID.
 * @return array Dashboard stats array with keys: listings, views, favorites.
 */
function apd_get_cached_dashboard_stats( int $user_id ): array {
	return apd_performance()->get_dashboard_stats( $user_id );
}

/**
 * Get popular listings (cached).
 *
 * Returns listings ordered by view count.
 * Results are cached to reduce database queries.
 *
 * @since 1.0.0
 *
 * @param int   $limit Number of listings. Default 10.
 * @param array $args  Additional query arguments.
 * @return array Array of WP_Post objects.
 */
function apd_get_popular_listings( int $limit = 10, array $args = [] ): array {
	return apd_performance()->get_popular_listings( $limit, $args );
}

/**
 * Invalidate category-related caches.
 *
 * Call this when category data changes to ensure fresh data.
 *
 * @since 1.0.0
 *
 * @return void
 */
function apd_invalidate_category_cache(): void {
	apd_performance()->invalidate_category_cache();
}
