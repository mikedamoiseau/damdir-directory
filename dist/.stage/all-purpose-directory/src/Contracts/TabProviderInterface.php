<?php
/**
 * Tab Provider Interface.
 *
 * Defines the contract for demo data page tabs. Both the General tab
 * and module-specific tabs implement this interface for polymorphic
 * rendering and AJAX handling.
 *
 * @package APD\Contracts
 * @since   1.2.0
 */

declare(strict_types=1);

namespace APD\Contracts;

use APD\Admin\DemoData\DemoDataTracker;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface TabProviderInterface
 *
 * @since 1.2.0
 */
interface TabProviderInterface {

	/**
	 * Get the unique tab slug.
	 *
	 * @since 1.2.0
	 *
	 * @return string Tab slug (e.g., 'general', 'url-directory').
	 */
	public function get_slug(): string;

	/**
	 * Get the tab display name.
	 *
	 * @since 1.2.0
	 *
	 * @return string Display name (e.g., 'General', 'URL Directory').
	 */
	public function get_name(): string;

	/**
	 * Get the dashicon class for the tab label.
	 *
	 * @since 1.2.0
	 *
	 * @return string Dashicon class (e.g., 'dashicons-admin-generic').
	 */
	public function get_icon(): string;

	/**
	 * Get tab display priority.
	 *
	 * Lower numbers display first. General tab uses 0.
	 *
	 * @since 1.2.0
	 *
	 * @return int Priority value.
	 */
	public function get_priority(): int;

	/**
	 * Get current demo data counts for this tab's scope.
	 *
	 * @since 1.2.0
	 *
	 * @param DemoDataTracker $tracker Tracker instance.
	 * @return array<string, int> Counts keyed by data type.
	 */
	public function get_counts( DemoDataTracker $tracker ): array;

	/**
	 * Get the total demo data count for this tab.
	 *
	 * @since 1.2.0
	 *
	 * @param DemoDataTracker $tracker Tracker instance.
	 * @return int Total count.
	 */
	public function get_total( DemoDataTracker $tracker ): int;

	/**
	 * Render the status counts table rows for this tab.
	 *
	 * Outputs HTML <tr> elements.
	 *
	 * @since 1.2.0
	 *
	 * @param DemoDataTracker $tracker Tracker instance.
	 * @return void
	 */
	public function render_status_section( DemoDataTracker $tracker ): void;

	/**
	 * Render the generation form fields for this tab.
	 *
	 * Outputs HTML form rows within a <fieldset>.
	 *
	 * @since 1.2.0
	 *
	 * @param array<string, int> $defaults Default quantities.
	 * @return void
	 */
	public function render_generate_form( array $defaults ): void;

	/**
	 * Render the delete section for this tab.
	 *
	 * Outputs HTML delete button or no-data message.
	 *
	 * @since 1.2.0
	 *
	 * @param DemoDataTracker $tracker Tracker instance.
	 * @return void
	 */
	public function render_delete_section( DemoDataTracker $tracker ): void;

	/**
	 * Handle AJAX generate request for this tab.
	 *
	 * @since 1.2.0
	 *
	 * @param array<string, mixed> $post_data Sanitized POST data.
	 * @return array{created: array<string, int>, counts: array<string, int>} Results.
	 */
	public function handle_generate( array $post_data ): array;

	/**
	 * Handle AJAX delete request for this tab.
	 *
	 * @since 1.2.0
	 *
	 * @param DemoDataTracker $tracker Tracker instance.
	 * @return array<string, int> Deleted counts keyed by type.
	 */
	public function handle_delete( DemoDataTracker $tracker ): array;
}
