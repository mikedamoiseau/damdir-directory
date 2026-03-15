<?php
/**
 * Hook registry and documentation for All Purpose Directory.
 *
 * @package APD\Core
 */

declare(strict_types=1);

namespace APD\Core;

/**
 * Class Hooks
 *
 * Central registry and documentation for all plugin hooks.
 * Use these constants to prevent typos when adding/removing hooks.
 *
 * @since 1.0.0
 */
final class Hooks {

	/*
	|--------------------------------------------------------------------------
	| Action Hooks - Plugin Lifecycle
	|--------------------------------------------------------------------------
	|
	| These hooks fire during plugin initialization and lifecycle events.
	|
	*/

	/**
	 * Fires after the plugin is fully initialized.
	 *
	 * Use this hook to add functionality that depends on the plugin being loaded.
	 *
	 * @since 1.0.0
	 *
	 * @example
	 * ```php
	 * add_action( APD\Core\Hooks::INIT, function() {
	 *     // Register custom field types, filters, etc.
	 * } );
	 * ```
	 */
	public const INIT = 'apd_init';

	/**
	 * Fires after plugin hooks are initialized but before full init.
	 *
	 * Use this hook for early initialization that needs to run
	 * before apd_init but after basic plugin setup.
	 *
	 * @since 1.0.0
	 *
	 * @example
	 * ```php
	 * add_action( APD\Core\Hooks::LOADED, function() {
	 *     // Early initialization code
	 * } );
	 * ```
	 */
	public const LOADED = 'apd_loaded';

	/**
	 * Fires after the plugin is activated.
	 *
	 * Use this hook to run code only on plugin activation.
	 * Note: This runs during the activation process.
	 *
	 * @since 1.0.0
	 *
	 * @example
	 * ```php
	 * add_action( APD\Core\Hooks::ACTIVATED, function() {
	 *     // Create custom database tables, set up initial data, etc.
	 * } );
	 * ```
	 */
	public const ACTIVATED = 'apd_activated';

	/**
	 * Fires after the plugin is deactivated.
	 *
	 * Use this hook to clean up temporary data on deactivation.
	 * Note: Permanent data removal should happen on uninstall, not here.
	 *
	 * @since 1.0.0
	 *
	 * @example
	 * ```php
	 * add_action( APD\Core\Hooks::DEACTIVATED, function() {
	 *     // Clear transients, temporary files, etc.
	 * } );
	 * ```
	 */
	public const DEACTIVATED = 'apd_deactivated';

	/*
	|--------------------------------------------------------------------------
	| Action Hooks - Listing Lifecycle
	|--------------------------------------------------------------------------
	|
	| These hooks fire during listing CRUD operations.
	|
	*/

	/**
	 * Fires before a listing is saved.
	 *
	 * Use this hook to modify or validate listing data before it's saved.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $listing_id Listing post ID.
	 * @param array $data       Listing data being saved.
	 *
	 * @example
	 * ```php
	 * add_action( APD\Core\Hooks::BEFORE_LISTING_SAVE, function( $listing_id, $data ) {
	 *     // Validate or modify data before save
	 * }, 10, 2 );
	 * ```
	 */
	public const BEFORE_LISTING_SAVE = 'apd_before_listing_save';

	/**
	 * Fires after a listing is saved.
	 *
	 * Use this hook to perform actions after a listing has been saved,
	 * such as clearing caches, sending notifications, or logging.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $listing_id Listing post ID.
	 * @param array $data       Listing data that was saved.
	 *
	 * @example
	 * ```php
	 * add_action( APD\Core\Hooks::AFTER_LISTING_SAVE, function( $listing_id, $data ) {
	 *     // Clear caches, send notifications, etc.
	 * }, 10, 2 );
	 * ```
	 */
	public const AFTER_LISTING_SAVE = 'apd_after_listing_save';

	/**
	 * Fires when a listing's status changes.
	 *
	 * Use this hook to react to status transitions (e.g., pending to published).
	 *
	 * @since 1.0.0
	 *
	 * @param int    $listing_id Listing post ID.
	 * @param string $new_status New post status.
	 * @param string $old_status Previous post status.
	 *
	 * @example
	 * ```php
	 * add_action( APD\Core\Hooks::LISTING_STATUS_CHANGED, function( $listing_id, $new, $old ) {
	 *     if ( $new === 'publish' && $old === 'pending' ) {
	 *         // Send approval notification
	 *     }
	 * }, 10, 3 );
	 * ```
	 */
	public const LISTING_STATUS_CHANGED = 'apd_listing_status_changed';

	/*
	|--------------------------------------------------------------------------
	| Action Hooks - Frontend Submission
	|--------------------------------------------------------------------------
	|
	| These hooks fire during frontend listing submission.
	|
	*/

	/**
	 * Fires before a frontend submission is processed.
	 *
	 * Use this hook to validate or modify submission data before processing.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data    Submitted form data.
	 * @param int   $user_id User ID (0 for guests).
	 *
	 * @example
	 * ```php
	 * add_action( APD\Core\Hooks::BEFORE_SUBMISSION, function( $data, $user_id ) {
	 *     // Custom validation, spam checking, etc.
	 * }, 10, 2 );
	 * ```
	 */
	public const BEFORE_SUBMISSION = 'apd_before_submission';

	/**
	 * Fires after a frontend submission is successfully processed.
	 *
	 * Use this hook for post-submission actions like notifications or redirects.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $listing_id Created listing post ID.
	 * @param array $data       Submitted form data.
	 * @param int   $user_id    User ID (0 for guests).
	 *
	 * @example
	 * ```php
	 * add_action( APD\Core\Hooks::AFTER_SUBMISSION, function( $listing_id, $data, $user_id ) {
	 *     // Send confirmation email, redirect to payment, etc.
	 * }, 10, 3 );
	 * ```
	 */
	public const AFTER_SUBMISSION = 'apd_after_submission';

	/*
	|--------------------------------------------------------------------------
	| Action Hooks - Reviews
	|--------------------------------------------------------------------------
	|
	| These hooks fire during review operations.
	|
	*/

	/**
	 * Fires after a review is submitted.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $review_id  Review (comment) ID.
	 * @param int   $listing_id Listing post ID.
	 * @param array $data       Review data.
	 *
	 * @example
	 * ```php
	 * add_action( APD\Core\Hooks::AFTER_REVIEW_SUBMITTED, function( $review_id, $listing_id, $data ) {
	 *     // Update listing rating cache, send notification
	 * }, 10, 3 );
	 * ```
	 */
	public const AFTER_REVIEW_SUBMITTED = 'apd_after_review_submitted';

	/**
	 * Fires after a review status changes (approved, spam, trash).
	 *
	 * @since 1.0.0
	 *
	 * @param int    $review_id  Review (comment) ID.
	 * @param string $new_status New status.
	 * @param string $old_status Previous status.
	 */
	public const REVIEW_STATUS_CHANGED = 'apd_review_status_changed';

	/*
	|--------------------------------------------------------------------------
	| Action Hooks - Favorites
	|--------------------------------------------------------------------------
	|
	| These hooks fire during favorite operations.
	|
	*/

	/**
	 * Fires after a listing is added to favorites.
	 *
	 * @since 1.0.0
	 *
	 * @param int $listing_id Listing post ID.
	 * @param int $user_id    User ID.
	 */
	public const FAVORITE_ADDED = 'apd_favorite_added';

	/**
	 * Fires after a listing is removed from favorites.
	 *
	 * @since 1.0.0
	 *
	 * @param int $listing_id Listing post ID.
	 * @param int $user_id    User ID.
	 */
	public const FAVORITE_REMOVED = 'apd_favorite_removed';

	/*
	|--------------------------------------------------------------------------
	| Filter Hooks - Fields
	|--------------------------------------------------------------------------
	|
	| These filters modify field definitions and values.
	|
	*/

	/**
	 * Filter the registered listing fields.
	 *
	 * Use this filter to add, remove, or modify listing fields.
	 *
	 * @since 1.0.0
	 *
	 * @param array $fields Array of field definitions.
	 * @return array Modified fields.
	 *
	 * @example
	 * ```php
	 * add_filter( APD\Core\Hooks::LISTING_FIELDS, function( $fields ) {
	 *     $fields['custom_field'] = [
	 *         'type'     => 'text',
	 *         'label'    => 'Custom Field',
	 *         'required' => false,
	 *     ];
	 *     return $fields;
	 * } );
	 * ```
	 */
	public const LISTING_FIELDS = 'apd_listing_fields';

	/**
	 * Filter the fields shown on the frontend submission form.
	 *
	 * Use this to control which fields appear on the submission form.
	 *
	 * @since 1.0.0
	 *
	 * @param array $fields Array of field definitions for the form.
	 * @return array Modified fields.
	 *
	 * @example
	 * ```php
	 * add_filter( APD\Core\Hooks::SUBMISSION_FIELDS, function( $fields ) {
	 *     // Remove admin-only fields from submission form
	 *     unset( $fields['internal_notes'] );
	 *     return $fields;
	 * } );
	 * ```
	 */
	public const SUBMISSION_FIELDS = 'apd_submission_fields';

	/*
	|--------------------------------------------------------------------------
	| Filter Hooks - Search & Display
	|--------------------------------------------------------------------------
	|
	| These filters modify search behavior and display output.
	|
	*/

	/**
	 * Filter the registered search filters.
	 *
	 * Use this filter to add or modify search/filter options.
	 *
	 * @since 1.0.0
	 *
	 * @param array $filters Array of filter definitions.
	 * @return array Modified filters.
	 *
	 * @example
	 * ```php
	 * add_filter( APD\Core\Hooks::SEARCH_FILTERS, function( $filters ) {
	 *     $filters['price_range'] = [
	 *         'type'  => 'range',
	 *         'label' => 'Price Range',
	 *         'min'   => 0,
	 *         'max'   => 10000,
	 *     ];
	 *     return $filters;
	 * } );
	 * ```
	 */
	public const SEARCH_FILTERS = 'apd_search_filters';

	/**
	 * Filter the WP_Query arguments for listing queries.
	 *
	 * Use this filter to modify how listings are queried.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args WP_Query arguments.
	 * @return array Modified arguments.
	 *
	 * @example
	 * ```php
	 * add_filter( APD\Core\Hooks::LISTING_QUERY_ARGS, function( $args ) {
	 *     // Only show featured listings
	 *     $args['meta_query'][] = [
	 *         'key'   => '_apd_featured',
	 *         'value' => '1',
	 *     ];
	 *     return $args;
	 * } );
	 * ```
	 */
	public const LISTING_QUERY_ARGS = 'apd_listing_query_args';

	/**
	 * Filter the data passed to listing card templates.
	 *
	 * Use this filter to add or modify data available in card templates.
	 *
	 * @since 1.0.0
	 *
	 * @param array    $data    Template data.
	 * @param \WP_Post $listing Listing post object.
	 * @return array Modified data.
	 *
	 * @example
	 * ```php
	 * add_filter( APD\Core\Hooks::LISTING_CARD_DATA, function( $data, $listing ) {
	 *     $data['custom_badge'] = get_post_meta( $listing->ID, '_apd_badge', true );
	 *     return $data;
	 * }, 10, 2 );
	 * ```
	 */
	public const LISTING_CARD_DATA = 'apd_listing_card_data';

	/*
	|--------------------------------------------------------------------------
	| Filter Hooks - Email
	|--------------------------------------------------------------------------
	|
	| These filters modify email notifications.
	|
	*/

	/**
	 * Filter the available email templates.
	 *
	 * Use this filter to register custom email templates.
	 *
	 * @since 1.0.0
	 *
	 * @param array $templates Array of template definitions.
	 * @return array Modified templates.
	 *
	 * @example
	 * ```php
	 * add_filter( APD\Core\Hooks::EMAIL_TEMPLATES, function( $templates ) {
	 *     $templates['custom_notification'] = [
	 *         'subject' => 'Custom Notification',
	 *         'file'    => 'emails/custom-notification.php',
	 *     ];
	 *     return $templates;
	 * } );
	 * ```
	 */
	public const EMAIL_TEMPLATES = 'apd_email_templates';

	/*
	|--------------------------------------------------------------------------
	| Filter Hooks - Assets
	|--------------------------------------------------------------------------
	|
	| These filters modify asset loading behavior.
	|
	*/

	/**
	 * Filter whether to load frontend assets.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $should_load Whether to load assets.
	 * @return bool
	 */
	public const SHOULD_LOAD_FRONTEND_ASSETS = 'apd_should_load_frontend_assets';

	/**
	 * Filter whether current screen is a plugin admin screen.
	 *
	 * @since 1.0.0
	 *
	 * @param bool       $is_plugin_screen Whether this is a plugin screen.
	 * @param \WP_Screen $screen           Current screen object.
	 * @return bool
	 */
	public const IS_PLUGIN_ADMIN_SCREEN = 'apd_is_plugin_admin_screen';

	/**
	 * Filter frontend script localization data.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Localization data.
	 * @return array
	 */
	public const FRONTEND_SCRIPT_DATA = 'apd_frontend_script_data';

	/**
	 * Filter admin script localization data.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Localization data.
	 * @return array
	 */
	public const ADMIN_SCRIPT_DATA = 'apd_admin_script_data';

	/*
	|--------------------------------------------------------------------------
	| Helper Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get all action hook names.
	 *
	 * @return array<string, string>
	 */
	public static function get_action_hooks(): array {
		return [
			'INIT'                   => self::INIT,
			'LOADED'                 => self::LOADED,
			'ACTIVATED'              => self::ACTIVATED,
			'DEACTIVATED'            => self::DEACTIVATED,
			'BEFORE_LISTING_SAVE'    => self::BEFORE_LISTING_SAVE,
			'AFTER_LISTING_SAVE'     => self::AFTER_LISTING_SAVE,
			'LISTING_STATUS_CHANGED' => self::LISTING_STATUS_CHANGED,
			'BEFORE_SUBMISSION'      => self::BEFORE_SUBMISSION,
			'AFTER_SUBMISSION'       => self::AFTER_SUBMISSION,
			'AFTER_REVIEW_SUBMITTED' => self::AFTER_REVIEW_SUBMITTED,
			'REVIEW_STATUS_CHANGED'  => self::REVIEW_STATUS_CHANGED,
			'FAVORITE_ADDED'         => self::FAVORITE_ADDED,
			'FAVORITE_REMOVED'       => self::FAVORITE_REMOVED,
		];
	}

	/**
	 * Get all filter hook names.
	 *
	 * @return array<string, string>
	 */
	public static function get_filter_hooks(): array {
		return [
			'LISTING_FIELDS'              => self::LISTING_FIELDS,
			'SUBMISSION_FIELDS'           => self::SUBMISSION_FIELDS,
			'SEARCH_FILTERS'              => self::SEARCH_FILTERS,
			'LISTING_QUERY_ARGS'          => self::LISTING_QUERY_ARGS,
			'LISTING_CARD_DATA'           => self::LISTING_CARD_DATA,
			'EMAIL_TEMPLATES'             => self::EMAIL_TEMPLATES,
			'SHOULD_LOAD_FRONTEND_ASSETS' => self::SHOULD_LOAD_FRONTEND_ASSETS,
			'IS_PLUGIN_ADMIN_SCREEN'      => self::IS_PLUGIN_ADMIN_SCREEN,
			'FRONTEND_SCRIPT_DATA'        => self::FRONTEND_SCRIPT_DATA,
			'ADMIN_SCRIPT_DATA'           => self::ADMIN_SCRIPT_DATA,
		];
	}

	/**
	 * Get all hook names.
	 *
	 * @return array<string, string>
	 */
	public static function get_all_hooks(): array {
		return array_merge( self::get_action_hooks(), self::get_filter_hooks() );
	}
}
