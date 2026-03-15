<?php
/**
 * Listing Type taxonomy registration and management.
 *
 * Hidden taxonomy to associate listings with module types.
 *
 * @package APD\Taxonomy
 * @since   1.1.0
 */

declare(strict_types=1);

namespace APD\Taxonomy;

use APD\Listing\PostType;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ListingTypeTaxonomy
 *
 * Handles registration and management of the apd_listing_type taxonomy.
 * This is a hidden, non-hierarchical taxonomy used internally to associate
 * listings with module types (e.g., 'url-directory', 'venue', 'general').
 *
 * @since 1.1.0
 */
final class ListingTypeTaxonomy {

	/**
	 * Taxonomy slug.
	 */
	public const TAXONOMY = 'apd_listing_type';

	/**
	 * Default term slug for untyped listings.
	 */
	public const DEFAULT_TERM = 'general';

	/**
	 * Register the listing type taxonomy.
	 *
	 * @since 1.1.0
	 *
	 * @return void
	 */
	public function register(): void {
		register_taxonomy(
			self::TAXONOMY,
			PostType::POST_TYPE,
			$this->get_args()
		);
	}

	/**
	 * Initialize hooks for auto-term creation and default assignment.
	 *
	 * @since 1.1.0
	 *
	 * @return void
	 */
	public function init(): void {
		// Create default "General" term.
		$this->ensure_default_term();

		// Create terms for modules that already registered (before taxonomy was ready).
		$this->sync_existing_modules();

		// Auto-create terms when future modules register.
		add_action( 'apd_module_registered', [ $this, 'on_module_registered' ], 10, 2 );

		// Auto-assign default term to new listings that have no listing type.
		add_action( 'save_post_' . PostType::POST_TYPE, [ $this, 'assign_default_term' ], 99 );
	}

	/**
	 * Get taxonomy labels.
	 *
	 * @since 1.1.0
	 *
	 * @return array<string, string> Taxonomy labels.
	 */
	private function get_labels(): array {
		return [
			'name'          => _x( 'Listing Types', 'taxonomy general name', 'all-purpose-directory' ),
			'singular_name' => _x( 'Listing Type', 'taxonomy singular name', 'all-purpose-directory' ),
			'all_items'     => __( 'All Listing Types', 'all-purpose-directory' ),
			'edit_item'     => __( 'Edit Listing Type', 'all-purpose-directory' ),
			'update_item'   => __( 'Update Listing Type', 'all-purpose-directory' ),
			'add_new_item'  => __( 'Add New Listing Type', 'all-purpose-directory' ),
			'new_item_name' => __( 'New Listing Type Name', 'all-purpose-directory' ),
			'search_items'  => __( 'Search Listing Types', 'all-purpose-directory' ),
			'not_found'     => __( 'No listing types found.', 'all-purpose-directory' ),
		];
	}

	/**
	 * Get taxonomy registration arguments.
	 *
	 * @since 1.1.0
	 *
	 * @return array<string, mixed> Taxonomy arguments.
	 */
	private function get_args(): array {
		return [
			'labels'             => $this->get_labels(),
			'description'        => __( 'Internal taxonomy to associate listings with module types.', 'all-purpose-directory' ),
			'hierarchical'       => false,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => false,
			'show_in_menu'       => false,
			'show_in_nav_menus'  => false,
			'show_tagcloud'      => false,
			'show_in_quick_edit' => false,
			'show_admin_column'  => false,
			'rewrite'            => false,
			'query_var'          => false,
			'show_in_rest'       => true,
			'rest_base'          => 'apd_listing_type',
		];
	}

	/**
	 * Ensure the default "General" term exists.
	 *
	 * @since 1.1.0
	 *
	 * @return void
	 */
	public function ensure_default_term(): void {
		if ( ! term_exists( self::DEFAULT_TERM, self::TAXONOMY ) ) {
			wp_insert_term(
				__( 'General', 'all-purpose-directory' ),
				self::TAXONOMY,
				[ 'slug' => self::DEFAULT_TERM ]
			);
		}
	}

	/**
	 * Create terms for modules that registered before the taxonomy existed.
	 *
	 * Modules register at init priority 1, but the taxonomy is registered at
	 * priority 5. This method retroactively creates terms for already-registered
	 * modules.
	 *
	 * @since 1.1.0
	 *
	 * @return void
	 */
	private function sync_existing_modules(): void {
		if ( ! function_exists( 'apd_get_modules' ) ) {
			return;
		}

		$modules = apd_get_modules();
		foreach ( $modules as $slug => $config ) {
			$this->on_module_registered( $slug, $config );
		}
	}

	/**
	 * Create a term when a module registers.
	 *
	 * @since 1.1.0
	 *
	 * @param string               $slug   Module slug.
	 * @param array<string, mixed> $config Module configuration.
	 * @return void
	 */
	public function on_module_registered( string $slug, array $config ): void {
		if ( ! taxonomy_exists( self::TAXONOMY ) ) {
			return;
		}

		if ( ! term_exists( $slug, self::TAXONOMY ) ) {
			$result = wp_insert_term(
				$config['name'] ?? $slug,
				self::TAXONOMY,
				[ 'slug' => $slug ]
			);

			if ( ! is_wp_error( $result ) ) {
				/**
				 * Fires after a listing type term is created from a module registration.
				 *
				 * @since 1.1.0
				 *
				 * @param string               $slug    The listing type slug (module slug).
				 * @param int                   $term_id The created term ID.
				 * @param array<string, mixed>  $config  The module configuration.
				 */
				do_action( 'apd_listing_type_registered', $slug, $result['term_id'], $config );
			}
		}
	}

	/**
	 * Assign the default "General" term to listings that have no listing type.
	 *
	 * Fires at priority 99 on save_post_apd_listing to give modules a chance
	 * to set their own type at earlier priorities.
	 *
	 * @since 1.1.0
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function assign_default_term( int $post_id ): void {
		// Skip revisions and autosaves.
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		$terms = wp_get_object_terms( $post_id, self::TAXONOMY );

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			/**
			 * Filter the default listing type assigned to new listings.
			 *
			 * @since 1.1.0
			 *
			 * @param string $default_type The default type slug.
			 * @param int    $post_id      The listing post ID.
			 */
			$default_type = apply_filters( 'apd_default_listing_type', self::DEFAULT_TERM, $post_id );
			wp_set_object_terms( $post_id, sanitize_key( $default_type ), self::TAXONOMY );
		}
	}
}
