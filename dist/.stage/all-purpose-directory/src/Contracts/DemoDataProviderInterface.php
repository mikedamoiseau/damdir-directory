<?php
/**
 * Demo Data Provider Interface.
 *
 * Defines the contract for module plugins that want to provide
 * demo data through the demo data generator system.
 *
 * @package APD\Contracts
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Contracts;

use APD\Admin\DemoData\DemoDataTracker;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface DemoDataProviderInterface
 *
 * Module plugins implement this interface to register demo data providers
 * with the core plugin's demo data system. Providers can enhance existing
 * demo listings with module-specific meta, create new data types, and
 * handle cleanup when demo data is deleted.
 *
 * @since 1.0.0
 */
interface DemoDataProviderInterface {

	/**
	 * Get the unique provider slug.
	 *
	 * Should match the module slug for consistency.
	 *
	 * @since 1.0.0
	 *
	 * @return string The provider slug (e.g., 'url-directory').
	 */
	public function get_slug(): string;

	/**
	 * Get the display name for the admin UI.
	 *
	 * @since 1.0.0
	 *
	 * @return string The provider name (e.g., 'URL Directory').
	 */
	public function get_name(): string;

	/**
	 * Get the provider description.
	 *
	 * Shown next to the checkbox in the generate form.
	 *
	 * @since 1.0.0
	 *
	 * @return string The description text.
	 */
	public function get_description(): string;

	/**
	 * Get the dashicon class for the stats table.
	 *
	 * @since 1.0.0
	 *
	 * @return string The dashicon class (e.g., 'dashicons-admin-links').
	 */
	public function get_icon(): string;

	/**
	 * Get additional form fields for the generate form.
	 *
	 * Returns an array of field definitions that appear alongside the
	 * provider's checkbox in the generate form. Useful for quantity controls.
	 *
	 * Each field definition:
	 * - type: (string) 'number' - the input type
	 * - name: (string) Field name (will be prefixed with module_{slug}_)
	 * - label: (string) Field label text
	 * - default: (int) Default value
	 * - min: (int) Minimum value (default: 1)
	 * - max: (int) Maximum value (default: 100)
	 *
	 * Return an empty array if no additional fields are needed.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, array{type: string, name: string, label: string, default: int, min?: int, max?: int}>
	 */
	public function get_form_fields(): array;

	/**
	 * Generate demo data.
	 *
	 * Called after core demo data has been generated. Receives the full
	 * context of what was created so the provider can enhance listings
	 * or create its own data types.
	 *
	 * Use the tracker to mark any new posts, terms, users, or comments
	 * as demo data for proper cleanup.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $context Generation context from core.
	 * @param DemoDataTracker      $tracker Tracker instance for marking data as demo.
	 * @return array<string, int> Map of data type labels to counts created.
	 */
	public function generate( array $context, DemoDataTracker $tracker ): array;

	/**
	 * Delete all demo data created by this provider.
	 *
	 * Called before core demo data deletion. Module data that is stored
	 * as post meta on listings does not need explicit deletion since it
	 * will be removed when the listing is deleted.
	 *
	 * @since 1.0.0
	 *
	 * @param DemoDataTracker $tracker Tracker instance for querying demo data.
	 * @return array<string, int> Map of data type labels to counts deleted.
	 */
	public function delete( DemoDataTracker $tracker ): array;

	/**
	 * Count existing demo data created by this provider.
	 *
	 * Used by the admin page to display current demo data status.
	 *
	 * @since 1.0.0
	 *
	 * @param DemoDataTracker $tracker Tracker instance for querying demo data.
	 * @return array<string, int> Map of data type labels to counts.
	 */
	public function count( DemoDataTracker $tracker ): array;
}
