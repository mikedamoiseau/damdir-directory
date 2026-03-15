<?php
/**
 * Listing post type registration.
 *
 * @package APD\Listing
 */

declare(strict_types=1);

namespace APD\Listing;

use APD\Core\Capabilities;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PostType
 *
 * Handles registration of the apd_listing custom post type
 * and custom post statuses.
 *
 * @since 1.0.0
 */
final class PostType {

	/**
	 * Post type slug.
	 */
	public const POST_TYPE = 'apd_listing';

	/**
	 * Expired status slug.
	 */
	public const STATUS_EXPIRED = 'expired';

	/**
	 * Rejected status slug.
	 */
	public const STATUS_REJECTED = 'rejected';

	/**
	 * Register the listing post type.
	 *
	 * @return void
	 */
	public function register(): void {
		register_post_type( self::POST_TYPE, $this->get_args() );
	}

	/**
	 * Register custom post statuses.
	 *
	 * @return void
	 */
	public function register_statuses(): void {
		register_post_status(
			self::STATUS_EXPIRED,
			[
				'label'                     => _x( 'Expired', 'post status', 'all-purpose-directory' ),
				'public'                    => false,
				'internal'                  => false,
				'protected'                 => true,
				'exclude_from_search'       => true,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: Number of expired listings */
				'label_count'               => _n_noop(
					'Expired <span class="count">(%s)</span>',
					'Expired <span class="count">(%s)</span>',
					'all-purpose-directory'
				),
			]
		);

		register_post_status(
			self::STATUS_REJECTED,
			[
				'label'                     => _x( 'Rejected', 'post status', 'all-purpose-directory' ),
				'public'                    => false,
				'internal'                  => false,
				'protected'                 => true,
				'exclude_from_search'       => true,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: Number of rejected listings */
				'label_count'               => _n_noop(
					'Rejected <span class="count">(%s)</span>',
					'Rejected <span class="count">(%s)</span>',
					'all-purpose-directory'
				),
			]
		);
	}

	/**
	 * Get post type labels.
	 *
	 * @return array<string, string>
	 */
	private function get_labels(): array {
		return [
			'name'                  => _x( 'Listings', 'post type general name', 'all-purpose-directory' ),
			'singular_name'         => _x( 'Listing', 'post type singular name', 'all-purpose-directory' ),
			'menu_name'             => _x( 'Listings', 'admin menu', 'all-purpose-directory' ),
			'name_admin_bar'        => _x( 'Listing', 'add new on admin bar', 'all-purpose-directory' ),
			'add_new'               => _x( 'Add New', 'listing', 'all-purpose-directory' ),
			'add_new_item'          => __( 'Add New Listing', 'all-purpose-directory' ),
			'new_item'              => __( 'New Listing', 'all-purpose-directory' ),
			'edit_item'             => __( 'Edit Listing', 'all-purpose-directory' ),
			'view_item'             => __( 'View Listing', 'all-purpose-directory' ),
			'all_items'             => __( 'All Listings', 'all-purpose-directory' ),
			'search_items'          => __( 'Search Listings', 'all-purpose-directory' ),
			'parent_item_colon'     => __( 'Parent Listings:', 'all-purpose-directory' ),
			'not_found'             => __( 'No listings found.', 'all-purpose-directory' ),
			'not_found_in_trash'    => __( 'No listings found in Trash.', 'all-purpose-directory' ),
			'featured_image'        => _x( 'Listing Image', 'Overrides the "Featured Image" phrase', 'all-purpose-directory' ),
			'set_featured_image'    => _x( 'Set listing image', 'Overrides the "Set featured image" phrase', 'all-purpose-directory' ),
			'remove_featured_image' => _x( 'Remove listing image', 'Overrides the "Remove featured image" phrase', 'all-purpose-directory' ),
			'use_featured_image'    => _x( 'Use as listing image', 'Overrides the "Use as featured image" phrase', 'all-purpose-directory' ),
			'archives'              => _x( 'Listing archives', 'The post type archive label', 'all-purpose-directory' ),
			'insert_into_item'      => _x( 'Insert into listing', 'Overrides the "Insert into post" phrase', 'all-purpose-directory' ),
			'uploaded_to_this_item' => _x( 'Uploaded to this listing', 'Overrides the "Uploaded to this post" phrase', 'all-purpose-directory' ),
			'filter_items_list'     => _x( 'Filter listings list', 'Screen reader text', 'all-purpose-directory' ),
			'items_list_navigation' => _x( 'Listings list navigation', 'Screen reader text', 'all-purpose-directory' ),
			'items_list'            => _x( 'Listings list', 'Screen reader text', 'all-purpose-directory' ),
		];
	}

	/**
	 * Get post type arguments.
	 *
	 * @return array<string, mixed>
	 */
	private function get_args(): array {
		return [
			'labels'             => $this->get_labels(),
			'description'        => __( 'Directory listings', 'all-purpose-directory' ),
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => [
				'slug'       => 'listings',
				'with_front' => false,
			],
			'capability_type'    => 'post',
			'capabilities'       => Capabilities::get_listing_caps(),
			'map_meta_cap'       => true,
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => 5,
			'menu_icon'          => 'dashicons-location-alt',
			'supports'           => [
				'title',
				'editor',
				'thumbnail',
				'author',
				'excerpt',
			],
			'show_in_rest'       => true,
		];
	}
}
