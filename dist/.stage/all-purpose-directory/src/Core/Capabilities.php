<?php
/**
 * Capability constants for All Purpose Directory.
 *
 * @package APD\Core
 */

declare(strict_types=1);

namespace APD\Core;

/**
 * Class Capabilities
 *
 * Central registry for all plugin capabilities.
 * Use these constants to prevent typos and ensure consistency.
 *
 * @since 1.0.0
 */
final class Capabilities {

	/*
	|--------------------------------------------------------------------------
	| Singular Listing Capabilities
	|--------------------------------------------------------------------------
	|
	| These capabilities control access to individual listings.
	|
	*/

	/**
	 * Edit a single listing.
	 */
	public const EDIT_LISTING = 'edit_apd_listing';

	/**
	 * Read a single listing.
	 */
	public const READ_LISTING = 'read_apd_listing';

	/**
	 * Delete a single listing.
	 */
	public const DELETE_LISTING = 'delete_apd_listing';

	/*
	|--------------------------------------------------------------------------
	| Plural Listing Capabilities
	|--------------------------------------------------------------------------
	|
	| These capabilities control bulk access to listings.
	|
	*/

	/**
	 * Edit own listings.
	 */
	public const EDIT_LISTINGS = 'edit_apd_listings';

	/**
	 * Edit other users' listings.
	 */
	public const EDIT_OTHERS_LISTINGS = 'edit_others_apd_listings';

	/**
	 * Publish listings.
	 */
	public const PUBLISH_LISTINGS = 'publish_apd_listings';

	/**
	 * Read private listings.
	 */
	public const READ_PRIVATE_LISTINGS = 'read_private_apd_listings';

	/**
	 * Delete own listings.
	 */
	public const DELETE_LISTINGS = 'delete_apd_listings';

	/**
	 * Delete private listings.
	 */
	public const DELETE_PRIVATE_LISTINGS = 'delete_private_apd_listings';

	/**
	 * Delete published listings.
	 */
	public const DELETE_PUBLISHED_LISTINGS = 'delete_published_apd_listings';

	/**
	 * Delete other users' listings.
	 */
	public const DELETE_OTHERS_LISTINGS = 'delete_others_apd_listings';

	/**
	 * Edit private listings.
	 */
	public const EDIT_PRIVATE_LISTINGS = 'edit_private_apd_listings';

	/**
	 * Edit published listings.
	 */
	public const EDIT_PUBLISHED_LISTINGS = 'edit_published_apd_listings';

	/*
	|--------------------------------------------------------------------------
	| Taxonomy Capabilities
	|--------------------------------------------------------------------------
	|
	| These capabilities control access to listing taxonomies.
	|
	*/

	/**
	 * Manage listing categories.
	 */
	public const MANAGE_CATEGORIES = 'manage_apd_categories';

	/**
	 * Manage listing tags.
	 */
	public const MANAGE_TAGS = 'manage_apd_tags';

	/*
	|--------------------------------------------------------------------------
	| Helper Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get all listing capabilities.
	 *
	 * Used during plugin activation to grant capabilities to roles,
	 * and during uninstall to remove them.
	 *
	 * @return array<string>
	 */
	public static function get_all(): array {
		return [
			self::EDIT_LISTING,
			self::READ_LISTING,
			self::DELETE_LISTING,
			self::EDIT_LISTINGS,
			self::EDIT_OTHERS_LISTINGS,
			self::PUBLISH_LISTINGS,
			self::READ_PRIVATE_LISTINGS,
			self::DELETE_LISTINGS,
			self::DELETE_PRIVATE_LISTINGS,
			self::DELETE_PUBLISHED_LISTINGS,
			self::DELETE_OTHERS_LISTINGS,
			self::EDIT_PRIVATE_LISTINGS,
			self::EDIT_PUBLISHED_LISTINGS,
			self::MANAGE_CATEGORIES,
			self::MANAGE_TAGS,
		];
	}

	/**
	 * Get editor role capabilities.
	 *
	 * @return array<string>
	 */
	public static function get_editor_capabilities(): array {
		return [
			self::EDIT_LISTINGS,
			self::EDIT_OTHERS_LISTINGS,
			self::PUBLISH_LISTINGS,
			self::READ_PRIVATE_LISTINGS,
			self::DELETE_LISTINGS,
		];
	}

	/**
	 * Get author role capabilities.
	 *
	 * @return array<string>
	 */
	public static function get_author_capabilities(): array {
		return [
			self::EDIT_LISTINGS,
			self::PUBLISH_LISTINGS,
			self::DELETE_LISTINGS,
		];
	}

	/**
	 * Get the capability mapping for the listing post type.
	 *
	 * Maps WordPress capability types to our custom capabilities.
	 * Used when registering the apd_listing post type.
	 *
	 * @return array<string, string>
	 */
	public static function get_listing_caps(): array {
		return [
			// Singular capabilities.
			'edit_post'              => self::EDIT_LISTING,
			'read_post'              => self::READ_LISTING,
			'delete_post'            => self::DELETE_LISTING,
			// Plural capabilities.
			'edit_posts'             => self::EDIT_LISTINGS,
			'edit_others_posts'      => self::EDIT_OTHERS_LISTINGS,
			'publish_posts'          => self::PUBLISH_LISTINGS,
			'read_private_posts'     => self::READ_PRIVATE_LISTINGS,
			'delete_posts'           => self::DELETE_LISTINGS,
			'delete_private_posts'   => self::DELETE_PRIVATE_LISTINGS,
			'delete_published_posts' => self::DELETE_PUBLISHED_LISTINGS,
			'delete_others_posts'    => self::DELETE_OTHERS_LISTINGS,
			'edit_private_posts'     => self::EDIT_PRIVATE_LISTINGS,
			'edit_published_posts'   => self::EDIT_PUBLISHED_LISTINGS,
		];
	}
}
