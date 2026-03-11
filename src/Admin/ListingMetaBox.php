<?php
/**
 * Listing Meta Box.
 *
 * Handles the meta box for custom listing fields on the apd_listing post type
 * edit screen. Renders registered fields and saves field values with proper
 * validation and sanitization.
 *
 * @package APD\Admin
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Admin;

use WP_Post;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ListingMetaBox
 *
 * Manages the listing fields meta box in the WordPress admin.
 *
 * @since 1.0.0
 */
final class ListingMetaBox {

	/**
	 * Meta box ID.
	 */
	public const META_BOX_ID = 'apd_listing_fields';

	/**
	 * Nonce action for saving fields.
	 */
	public const NONCE_ACTION = 'apd_save_listing_fields';

	/**
	 * Nonce field name.
	 */
	public const NONCE_NAME = 'apd_fields_nonce';

	/**
	 * Post type for the listing.
	 */
	public const POST_TYPE = 'apd_listing';

	/**
	 * Transient key prefix for field validation errors.
	 */
	private const ERROR_TRANSIENT_PREFIX = 'apd_field_errors_';

	/**
	 * Initialize the meta box hooks.
	 *
	 * Registers the meta box and save handlers.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init(): void {
		// Only run in admin context.
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'add_meta_boxes', [ $this, 'register_meta_box' ] );
		add_action( 'save_post_' . self::POST_TYPE, [ $this, 'save_meta_box' ], 10, 2 );
		add_action( 'admin_notices', [ $this, 'display_field_errors' ] );
	}

	/**
	 * Register the listing fields meta box.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_meta_box(): void {
		add_meta_box(
			self::META_BOX_ID,
			__( 'Listing Fields', 'all-purpose-directory' ),
			[ $this, 'render_meta_box' ],
			self::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Render the meta box content.
	 *
	 * Outputs all registered listing fields using the field renderer.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $post The current post object.
	 * @return void
	 */
	public function render_meta_box( WP_Post $post ): void {
		// Check if any fields are registered.
		$fields = apd_get_fields();

		if ( empty( $fields ) ) {
			printf(
				'<p class="apd-no-fields">%s</p>',
				esc_html__( 'No custom fields have been registered for listings.', 'all-purpose-directory' )
			);
			return;
		}

		// Render the admin fields with nonce.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output escaped in apd_render_admin_fields().
		echo apd_render_admin_fields(
			$post->ID,
			[
				'nonce_action' => self::NONCE_ACTION,
				'nonce_name'   => self::NONCE_NAME,
			]
		);
	}

	/**
	 * Save the meta box field values.
	 *
	 * Validates, sanitizes, and saves all submitted field values.
	 * Includes nonce verification, autosave handling, and capability checks.
	 *
	 * @since 1.0.0
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

		// Verify this is the correct post type.
		if ( $post->post_type !== self::POST_TYPE ) {
			return;
		}

		// Get registered fields.
		$fields = apd_get_fields();

		if ( empty( $fields ) ) {
			return;
		}

		// Extract field values from POST data.
		$values = $this->extract_field_values( $fields );

		// Get the selected listing type from POST data (set before taxonomy save at priority 20).
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce already verified above.
		$selected_type = isset( $_POST['apd_listing_type'] )
			// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verified, sanitized by sanitize_key.
			? sanitize_key( wp_unslash( $_POST['apd_listing_type'] ) )
			: apd_get_listing_type( $post_id );

		// Filter values to only include fields matching the listing type.
		// Prevents required-field validation failures for hidden type-specific fields.
		if ( $selected_type ) {
			$values = $this->filter_values_by_listing_type( $values, $selected_type );
		}

		/**
		 * Fires before listing field values are saved.
		 *
		 * Allows modification of values or additional processing before save.
		 *
		 * @since 1.0.0
		 *
		 * @param int                  $post_id The listing post ID.
		 * @param array<string, mixed> $values  Field values keyed by field name.
		 */
		do_action( 'apd_before_listing_save', $post_id, $values );

		// Process (sanitize and validate) field values.
		$result = apd_process_fields( $values );

		// If validation fails, store errors for display on redirect.
		if ( ! $result['valid'] && $result['errors'] !== null ) {
			apd_set_field_errors( $result['errors'] );
			$this->store_validation_errors( $result['errors'] );
		}

		// Save sanitized values (even if some validation failed, save what we can).
		$sanitized_values = $result['values'];

		foreach ( $sanitized_values as $field_name => $value ) {
			apd_set_listing_field( $post_id, $field_name, $value );
		}

		/**
		 * Fires after listing field values are saved.
		 *
		 * Allows additional processing after fields have been saved.
		 *
		 * @since 1.0.0
		 *
		 * @param int                  $post_id The listing post ID.
		 * @param array<string, mixed> $values  Sanitized field values keyed by field name.
		 */
		do_action( 'apd_after_listing_save', $post_id, $sanitized_values );
	}

	/**
	 * Filter field values to only include fields matching the listing type.
	 *
	 * Removes values for fields that don't belong to the selected listing type,
	 * preventing required-field validation failures for hidden type-specific fields.
	 * The field data is preserved in meta from previous saves; it just isn't
	 * re-validated on this save.
	 *
	 * @since 1.1.0
	 *
	 * @param array<string, mixed> $values        Field values keyed by field name.
	 * @param string               $selected_type The selected listing type slug.
	 * @return array<string, mixed> Filtered field values.
	 */
	private function filter_values_by_listing_type( array $values, string $selected_type ): array {
		$filtered = [];

		foreach ( $values as $field_name => $value ) {
			$field = apd_get_field( $field_name );

			if ( $field === null ) {
				// Unknown field, keep it.
				$filtered[ $field_name ] = $value;
				continue;
			}

			$field_type = $field['listing_type'] ?? null;

			// Global fields (listing_type is null) are always included.
			if ( $field_type === null ) {
				// Check if a module hides this field for the selected type.
				if ( $this->is_field_hidden_by_module( $field_name, $selected_type ) ) {
					continue;
				}
				$filtered[ $field_name ] = $value;
				continue;
			}

			// Type-specific field: include only if it matches the selected type.
			if ( is_string( $field_type ) && $field_type === $selected_type ) {
				$filtered[ $field_name ] = $value;
			} elseif ( is_array( $field_type ) && in_array( $selected_type, $field_type, true ) ) {
				$filtered[ $field_name ] = $value;
			}
		}

		return $filtered;
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
	private function is_field_hidden_by_module( string $field_name, string $listing_type ): bool {
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
	 * Extract field values from POST data.
	 *
	 * Looks for field values in the POST data based on registered field names.
	 * Field values are expected in the format apd_field[field_name] or
	 * directly as the field name.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, array<string, mixed>> $fields Registered fields.
	 * @return array<string, mixed> Field values keyed by field name.
	 */
	private function extract_field_values( array $fields ): array {
		$values = [];

		// Check for apd_field array format first.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce already verified in save_meta_box.
		$apd_fields = isset( $_POST['apd_field'] ) && is_array( $_POST['apd_field'] )
			// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verified, sanitization happens in apd_process_fields.
			? wp_unslash( $_POST['apd_field'] )
			: [];

		foreach ( $fields as $field_name => $field_config ) {
			// Try apd_field[field_name] format first.
			if ( isset( $apd_fields[ $field_name ] ) ) {
				$values[ $field_name ] = $apd_fields[ $field_name ];
				continue;
			}

			// Try apd_field_{name} format (field types render with this prefix).
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce already verified.
			if ( isset( $_POST[ 'apd_field_' . $field_name ] ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verified, sanitization happens in apd_process_fields.
				$values[ $field_name ] = wp_unslash( $_POST[ 'apd_field_' . $field_name ] );
				continue;
			}

			// Try direct field name (for checkbox fields that might not be set).
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce already verified.
			if ( isset( $_POST[ $field_name ] ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verified, sanitization happens in apd_process_fields.
				$values[ $field_name ] = wp_unslash( $_POST[ $field_name ] );
				continue;
			}

			// Handle unchecked checkboxes - they don't appear in POST data.
			// Set to empty string so the field type can handle it.
			if ( isset( $field_config['type'] ) && in_array( $field_config['type'], [ 'checkbox', 'switch' ], true ) ) {
				$values[ $field_name ] = '';
			}
		}

		return $values;
	}

	/**
	 * Store validation errors in a transient for display after redirect.
	 *
	 * @since 1.0.0
	 *
	 * @param array|\WP_Error $errors Validation errors.
	 * @return void
	 */
	private function store_validation_errors( $errors ): void {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return;
		}

		$messages = [];
		if ( is_wp_error( $errors ) ) {
			$messages = $errors->get_error_messages();
		} elseif ( is_array( $errors ) ) {
			foreach ( $errors as $field_name => $error ) {
				if ( is_wp_error( $error ) ) {
					foreach ( $error->get_error_messages() as $msg ) {
						$messages[] = $msg;
					}
				} elseif ( is_string( $error ) ) {
					$messages[] = $error;
				}
			}
		}

		if ( ! empty( $messages ) ) {
			set_transient(
				self::ERROR_TRANSIENT_PREFIX . $user_id,
				$messages,
				60
			);
		}
	}

	/**
	 * Display field validation errors as admin notices.
	 *
	 * Reads errors from a user-specific transient, displays them,
	 * and deletes the transient so errors are shown only once.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function display_field_errors(): void {
		$screen = get_current_screen();
		if ( ! $screen || $screen->post_type !== self::POST_TYPE ) {
			return;
		}

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return;
		}

		$transient_key = self::ERROR_TRANSIENT_PREFIX . $user_id;
		$messages      = get_transient( $transient_key );

		if ( empty( $messages ) || ! is_array( $messages ) ) {
			return;
		}

		delete_transient( $transient_key );

		foreach ( $messages as $message ) {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html( $message )
			);
		}
	}
}
