<?php
/**
 * Demo Data Module Provider Interface.
 *
 * Extended contract for module plugins that create their own listings
 * and categories through the demo data system.
 *
 * @package APD\Contracts
 * @since   1.2.0
 */

declare(strict_types=1);

namespace APD\Contracts;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface DemoDataModuleProviderInterface
 *
 * Extends DemoDataProviderInterface with module-specific capabilities:
 * - Custom category datasets for module-appropriate categories
 * - Listing type identification for taxonomy scoping
 *
 * Module plugins implement this interface when they need their own
 * tab in the demo data page with independent generation/deletion.
 *
 * @since 1.2.0
 */
interface DemoDataModuleProviderInterface extends DemoDataProviderInterface {

	/**
	 * Get category hierarchy data for this module's listing type.
	 *
	 * Returns an array matching the structure of CategoryData::get_categories():
	 * [
	 *     'slug' => [
	 *         'name'        => 'Category Name',
	 *         'description' => 'Description',
	 *         'icon'        => 'dashicons-class',
	 *         'color'       => '#hex',
	 *         'children'    => [ ... ],
	 *     ],
	 * ]
	 *
	 * Return an empty array if the module does not provide custom categories.
	 *
	 * @since 1.2.0
	 *
	 * @return array<string, array{name: string, description: string, icon: string, color: string, children?: array}> Category hierarchy.
	 */
	public function get_category_data(): array;

	/**
	 * Get the listing type slug for this module.
	 *
	 * Used for `_apd_listing_type` term meta on categories and
	 * `apd_listing_type` taxonomy term on listings.
	 *
	 * @since 1.2.0
	 *
	 * @return string Listing type slug (e.g., 'url-directory').
	 */
	public function get_listing_type(): string;
}
