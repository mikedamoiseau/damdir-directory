<?php
/**
 * Listing Type Meta Box.
 *
 * Provides a sidebar meta box for selecting the listing type on the
 * apd_listing post type edit screen. Only appears when 2+ listing
 * types exist (i.e., at least one module is active alongside "General").
 *
 * @package APD\Admin
 * @since   1.1.0
 */

declare(strict_types=1);

namespace APD\Admin;

use APD\Taxonomy\ListingTypeTaxonomy;
use WP_Post;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ListingTypeMetaBox
 *
 * Manages the listing type selector meta box and type-aware field display.
 *
 * @since 1.1.0
 */
final class ListingTypeMetaBox {

	/**
	 * Meta box ID.
	 */
	public const META_BOX_ID = 'apd_listing_type_selector';

	/**
	 * Nonce action for saving the listing type.
	 */
	public const NONCE_ACTION = 'apd_save_listing_type';

	/**
	 * Nonce field name.
	 */
	public const NONCE_NAME = 'apd_listing_type_nonce';

	/**
	 * Post type for the listing.
	 */
	public const POST_TYPE = 'apd_listing';

	/**
	 * Initialize the meta box hooks.
	 *
	 * @since 1.1.0
	 *
	 * @return void
	 */
	public function init(): void {
		// Field display filter runs on both admin and frontend so type-specific
		// fields are correctly hidden when viewing a single listing.
		add_filter( 'apd_should_display_field', [ $this, 'filter_field_display' ], 10, 4 );

		if ( ! is_admin() ) {
			return;
		}

		add_action( 'add_meta_boxes', [ $this, 'register_meta_box' ] );
		add_action( 'save_post_' . self::POST_TYPE, [ $this, 'save_meta_box' ], 20, 2 );
	}

	/**
	 * Register the listing type meta box (only when 2+ types exist).
	 *
	 * @since 1.1.0
	 *
	 * @return void
	 */
	public function register_meta_box(): void {
		if ( ! $this->has_multiple_listing_types() ) {
			return;
		}

		add_meta_box(
			self::META_BOX_ID,
			__( 'Listing Type', 'all-purpose-directory' ),
			[ $this, 'render_meta_box' ],
			self::POST_TYPE,
			'side',
			'default'
		);
	}

	/**
	 * Render the meta box content.
	 *
	 * Outputs radio buttons for each listing type and a hidden element
	 * containing the field-to-type JSON mapping for JavaScript consumption.
	 *
	 * @since 1.1.0
	 *
	 * @param WP_Post $post The current post object.
	 * @return void
	 */
	public function render_meta_box( WP_Post $post ): void {
		$types        = $this->get_listing_types();
		$current_type = apd_get_listing_type( $post->ID );

		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		echo '<fieldset>';
		printf(
			'<legend class="screen-reader-text">%s</legend>',
			esc_html__( 'Select listing type', 'all-purpose-directory' )
		);

		foreach ( $types as $term ) {
			printf(
				'<label class="apd-listing-type-option" style="display:block;margin:4px 0;">'
				. '<input type="radio" name="apd_listing_type" value="%s" %s /> %s'
				. '</label>',
				esc_attr( $term->slug ),
				checked( $current_type, $term->slug, false ),
				esc_html( $term->name )
			);
		}

		echo '</fieldset>';

		// Output field-to-type mapping for JS.
		$mapping = $this->build_field_type_mapping();
		if ( ! empty( $mapping ) ) {
			printf(
				'<div id="apd-field-type-mapping" data-field-types="%s" style="display:none;"></div>',
				esc_attr( wp_json_encode( $mapping ) )
			);

			// Output inline CSS to pre-hide fields that don't match the current
			// listing type. This prevents a flash of all fields before JS runs.
			$hide_selectors = $this->get_hidden_field_selectors( $current_type, $mapping );
			if ( ! empty( $hide_selectors ) ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- selectors use esc_attr() internally.
				echo '<style id="apd-type-initial-hide">' . implode( ',', $hide_selectors ) . '{display:none}</style>';
			}
		}
	}

	/**
	 * Save the listing type on post save.
	 *
	 * Runs at priority 20 (after field save at 10, before default at 99).
	 *
	 * @since 1.1.0
	 *
	 * @param int     $post_id The post ID being saved.
	 * @param WP_Post $post    The post object being saved.
	 * @return void
	 */
	public function save_meta_box( int $post_id, WP_Post $post ): void {
		// Verify nonce.
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verification.
		if ( ! wp_verify_nonce( wp_unslash( $_POST[ self::NONCE_NAME ] ), self::NONCE_ACTION ) ) {
			return;
		}

		// Skip autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check user capabilities.
		if ( ! current_user_can( 'edit_apd_listing', $post_id ) ) {
			return;
		}

		// Verify post type.
		if ( $post->post_type !== self::POST_TYPE ) {
			return;
		}

		// Get submitted type.
		if ( ! isset( $_POST['apd_listing_type'] ) ) {
			return;
		}

		$type = sanitize_key( wp_unslash( $_POST['apd_listing_type'] ) );

		// Validate that the type exists as a term.
		if ( ! $this->is_valid_listing_type( $type ) ) {
			return;
		}

		apd_set_listing_type( $post_id, $type );
	}

	/**
	 * Filter field display based on listing type.
	 *
	 * Hooked to apd_should_display_field. Checks:
	 * 1. Does the field's listing_type config match the listing's type?
	 * 2. Is the field hidden by a module for this listing's type?
	 *
	 * @since 1.1.0
	 *
	 * @param bool                 $display    Whether to display the field.
	 * @param array<string, mixed> $field      Field configuration.
	 * @param string               $context    Render context.
	 * @param int                  $listing_id Listing ID.
	 * @return bool
	 */
	public function filter_field_display( bool $display, array $field, string $context, int $listing_id ): bool {
		// If already hidden by another filter, respect that.
		if ( ! $display ) {
			return false;
		}

		// In admin context, always render all fields so JavaScript can toggle
		// them dynamically when the listing type radio changes. Returning false
		// here would remove the field's HTML from the DOM entirely, making it
		// impossible for JS to show it again after a type switch.
		if ( $context === 'admin' ) {
			return true;
		}

		// For new listings (ID 0), show all fields.
		if ( $listing_id <= 0 ) {
			return true;
		}

		// Only filter when 2+ types exist.
		if ( ! $this->has_multiple_listing_types() ) {
			return true;
		}

		$listing_type = apd_get_listing_type( $listing_id );

		// Check field's listing_type config.
		$field_type = $field['listing_type'] ?? null;

		if ( $field_type !== null ) {
			if ( is_string( $field_type ) && $field_type !== $listing_type ) {
				return false;
			}

			if ( is_array( $field_type ) && ! in_array( $listing_type, $field_type, true ) ) {
				return false;
			}
		}

		// Check if a module hides this field for the current listing type.
		if ( $this->is_field_hidden_by_module( $field['name'] ?? '', $listing_type ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if a field is hidden by a module's hidden_fields config.
	 *
	 * @since 1.1.0
	 *
	 * @param string $field_name   Field name.
	 * @param string $listing_type Current listing type slug.
	 * @return bool True if hidden.
	 */
	public function is_field_hidden_by_module( string $field_name, string $listing_type ): bool {
		if ( empty( $field_name ) || empty( $listing_type ) ) {
			return false;
		}

		if ( ! function_exists( 'apd_get_modules' ) ) {
			return false;
		}

		$modules = apd_get_modules();

		foreach ( $modules as $slug => $config ) {
			if ( $slug !== $listing_type ) {
				continue;
			}

			$hidden_fields = $config['hidden_fields'] ?? [];
			if ( in_array( $field_name, $hidden_fields, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Build the field-to-type mapping for JavaScript.
	 *
	 * Only includes fields that have type restrictions or are hidden by modules.
	 * Global fields with no restrictions are omitted.
	 *
	 * @since 1.1.0
	 *
	 * @return array<string, mixed> Mapping of field names to type config.
	 */
	public function build_field_type_mapping(): array {
		$mapping  = [];
		$registry = \APD\Fields\FieldRegistry::get_instance();
		$fields   = $registry->get_fields();

		// Add type-specific fields.
		foreach ( $fields as $name => $field ) {
			$listing_type = $field['listing_type'] ?? null;
			if ( $listing_type !== null ) {
				$mapping[ $name ] = $listing_type;
			}
		}

		// Add hidden-by-module entries.
		if ( function_exists( 'apd_get_modules' ) ) {
			$modules = apd_get_modules();
			foreach ( $modules as $slug => $config ) {
				$hidden_fields = $config['hidden_fields'] ?? [];
				foreach ( $hidden_fields as $field_name ) {
					if ( ! isset( $mapping[ $field_name ] ) ) {
						$mapping[ $field_name ] = [ 'hidden_by' => [ $slug ] ];
					} elseif ( is_array( $mapping[ $field_name ] ) && isset( $mapping[ $field_name ]['hidden_by'] ) ) {
						$mapping[ $field_name ]['hidden_by'][] = $slug;
					}
				}
			}
		}

		return $mapping;
	}

	/**
	 * Build CSS selectors for fields that should be initially hidden.
	 *
	 * Uses the same logic as the JS toggleFieldsByType() function to determine
	 * which fields to hide for the given listing type.
	 *
	 * @since 1.1.0
	 *
	 * @param string               $current_type Current listing type slug.
	 * @param array<string, mixed> $mapping      Field-to-type mapping.
	 * @return string[] Array of CSS selectors.
	 */
	private function get_hidden_field_selectors( string $current_type, array $mapping ): array {
		$selectors = [];

		foreach ( $mapping as $field_name => $config ) {
			$visible = true;

			if ( is_array( $config ) && isset( $config['hidden_by'] ) ) {
				// Global field hidden by specific modules.
				$visible = ! in_array( $current_type, $config['hidden_by'], true );
			} elseif ( $config === null ) {
				// Global field, always visible.
				$visible = true;
			} elseif ( is_array( $config ) ) {
				// Field visible for multiple types.
				$visible = in_array( $current_type, $config, true );
			} else {
				// Field visible for a single type.
				$visible = ( $config === $current_type );
			}

			if ( ! $visible ) {
				$selectors[] = '[data-field-name="' . esc_attr( $field_name ) . '"]';
			}
		}

		return $selectors;
	}

	/**
	 * Check whether 2+ listing type terms exist.
	 *
	 * @since 1.1.0
	 *
	 * @return bool
	 */
	public function has_multiple_listing_types(): bool {
		$types = $this->get_listing_types();
		return count( $types ) >= 2;
	}

	/**
	 * Get all listing type terms.
	 *
	 * @since 1.1.0
	 *
	 * @return \WP_Term[] Array of term objects.
	 */
	private function get_listing_types(): array {
		return apd_get_listing_types( false );
	}

	/**
	 * Check if a type slug is a valid listing type term.
	 *
	 * @since 1.1.0
	 *
	 * @param string $type Type slug.
	 * @return bool
	 */
	private function is_valid_listing_type( string $type ): bool {
		if ( empty( $type ) ) {
			return false;
		}

		return term_exists( $type, ListingTypeTaxonomy::TAXONOMY ) !== null
			&& term_exists( $type, ListingTypeTaxonomy::TAXONOMY ) !== 0;
	}
}
