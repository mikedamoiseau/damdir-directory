<?php
/**
 * Profile Dashboard Tab Controller.
 *
 * Handles the Profile tab in the user dashboard, including
 * profile display, editing, validation, and avatar management.
 *
 * @package APD\Frontend\Dashboard
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Frontend\Dashboard;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Profile
 *
 * @since 1.0.0
 */
class Profile {

	/**
	 * Nonce action for profile form.
	 *
	 * @var string
	 */
	public const NONCE_ACTION = 'apd_profile_save';

	/**
	 * Nonce field name.
	 *
	 * @var string
	 */
	public const NONCE_NAME = '_apd_profile_nonce';

	/**
	 * Valid social platforms.
	 *
	 * @var array<string>
	 */
	public const SOCIAL_PLATFORMS = [ 'facebook', 'twitter', 'linkedin', 'instagram' ];

	/**
	 * Maximum avatar file size in bytes (2MB).
	 *
	 * @var int
	 */
	public const MAX_AVATAR_SIZE = 2097152;

	/**
	 * Allowed avatar MIME types.
	 *
	 * @var array<string>
	 */
	public const ALLOWED_AVATAR_TYPES = [ 'image/jpeg', 'image/png', 'image/gif', 'image/webp' ];

	/**
	 * Current user ID.
	 *
	 * @var int
	 */
	private int $user_id = 0;

	/**
	 * Configuration options.
	 *
	 * @var array<string, mixed>
	 */
	private array $config = [];

	/**
	 * Default configuration.
	 *
	 * @var array<string, mixed>
	 */
	private const DEFAULTS = [
		'show_avatar' => true,
		'show_social' => true,
		'avatar_size' => 150,
	];

	/**
	 * Singleton instance.
	 *
	 * @var Profile|null
	 */
	private static ?Profile $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $config Optional. Configuration options.
	 * @return Profile
	 */
	public static function get_instance( array $config = [] ): Profile {
		if ( self::$instance === null || ! empty( $config ) ) {
			self::$instance = new self( $config );
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $config Configuration options.
	 */
	private function __construct( array $config = [] ) {
		$this->config  = wp_parse_args( $config, self::DEFAULTS );
		$this->user_id = get_current_user_id();
	}

	/**
	 * Prevent unserialization.
	 *
	 * @throws \Exception Always throws exception.
	 */
	public function __wakeup(): void {
		throw new \Exception( 'Cannot unserialize singleton.' );
	}

	/**
	 * Reset singleton instance (for testing).
	 *
	 * @return void
	 */
	public static function reset_instance(): void {
		self::$instance = null;
	}

	/**
	 * Initialize hooks for action handling.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'init', [ $this, 'handle_save' ] );
	}

	/**
	 * Get the current user ID.
	 *
	 * @since 1.0.0
	 *
	 * @return int User ID.
	 */
	public function get_user_id(): int {
		return $this->user_id;
	}

	/**
	 * Set the user ID.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id User ID.
	 * @return void
	 */
	public function set_user_id( int $user_id ): void {
		$this->user_id = $user_id;
	}

	/**
	 * Render the Profile tab content.
	 *
	 * @since 1.0.0
	 *
	 * @return string Rendered HTML.
	 */
	public function render(): string {
		if ( $this->user_id <= 0 ) {
			return '';
		}

		$user_data = $this->get_user_data();
		$message   = $this->get_message();

		$args = [
			'profile'    => $this,
			'user_data'  => $user_data,
			'config'     => $this->config,
			'message'    => $message,
			'nonce'      => wp_create_nonce( self::NONCE_ACTION ),
			'user_id'    => $this->user_id,
			'avatar_url' => $this->get_avatar_url( $this->user_id, $this->config['avatar_size'] ),
		];

		/**
		 * Filter the Profile template arguments.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, mixed> $args Template arguments.
		 */
		$args = apply_filters( 'apd_profile_args', $args );

		ob_start();
		\apd_get_template( 'dashboard/profile.php', $args );
		return ob_get_clean();
	}

	/**
	 * Get user profile data.
	 *
	 * @since 1.0.0
	 *
	 * @param int|null $user_id Optional. User ID. Defaults to current user.
	 * @return array<string, mixed> User data array.
	 */
	public function get_user_data( ?int $user_id = null ): array {
		if ( $user_id === null ) {
			$user_id = $this->user_id;
		}

		if ( $user_id <= 0 ) {
			return $this->get_empty_user_data();
		}

		$user = get_userdata( $user_id );

		if ( ! $user ) {
			return $this->get_empty_user_data();
		}

		$data = [
			'display_name' => $user->display_name,
			'first_name'   => $user->first_name,
			'last_name'    => $user->last_name,
			'user_email'   => $user->user_email,
			'description'  => $user->description,
			'user_url'     => $user->user_url,
			'phone'        => get_user_meta( $user_id, '_apd_phone', true ),
			'avatar_id'    => (int) get_user_meta( $user_id, '_apd_avatar', true ),
			'social'       => [
				'facebook'  => get_user_meta( $user_id, '_apd_social_facebook', true ),
				'twitter'   => get_user_meta( $user_id, '_apd_social_twitter', true ),
				'linkedin'  => get_user_meta( $user_id, '_apd_social_linkedin', true ),
				'instagram' => get_user_meta( $user_id, '_apd_social_instagram', true ),
			],
		];

		/**
		 * Filter the user profile data.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, mixed> $data    User profile data.
		 * @param int                  $user_id User ID.
		 */
		return apply_filters( 'apd_profile_user_data', $data, $user_id );
	}

	/**
	 * Get empty user data structure.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed> Empty user data.
	 */
	private function get_empty_user_data(): array {
		return [
			'display_name' => '',
			'first_name'   => '',
			'last_name'    => '',
			'user_email'   => '',
			'description'  => '',
			'user_url'     => '',
			'phone'        => '',
			'avatar_id'    => 0,
			'social'       => [
				'facebook'  => '',
				'twitter'   => '',
				'linkedin'  => '',
				'instagram' => '',
			],
		];
	}

	/**
	 * Handle profile form submission.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function handle_save(): void {
		// Check for form submission.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified below.
		if ( ! isset( $_POST['apd_profile_action'] ) || $_POST['apd_profile_action'] !== 'save' ) {
			return;
		}

		// Verify nonce.
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) {
			$this->set_message( 'error', __( 'Security verification failed. Please try again.', 'all-purpose-directory' ) );
			return;
		}

		// Check user is logged in.
		$user_id = get_current_user_id();
		if ( $user_id <= 0 ) {
			return;
		}

		$this->user_id = $user_id;

		// Collect form data.
		$data = $this->collect_form_data();

		// Validate the data.
		$validation = $this->validate_profile( $data );

		if ( is_wp_error( $validation ) ) {
			$this->set_message( 'error', $validation->get_error_message() );
			return;
		}

		// Handle avatar upload if present.
		if ( isset( $_FILES['apd_avatar'] ) && ! empty( $_FILES['apd_avatar']['name'] ) ) {
			$avatar_result = $this->handle_avatar_upload();

			if ( is_wp_error( $avatar_result ) ) {
				$this->set_message( 'error', $avatar_result->get_error_message() );
				return;
			}

			if ( $avatar_result > 0 ) {
				$data['avatar_id'] = $avatar_result;
			}
		}

		// Check for avatar removal.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Already verified above.
		if ( isset( $_POST['apd_remove_avatar'] ) && $_POST['apd_remove_avatar'] === '1' ) {
			$data['avatar_id'] = 0;
		}

		// Save the profile.
		$result = $this->save_profile( $data );

		if ( is_wp_error( $result ) ) {
			$this->set_message( 'error', $result->get_error_message() );
			return;
		}

		/**
		 * Fires after a profile has been saved successfully.
		 *
		 * @since 1.0.0
		 *
		 * @param int   $user_id User ID.
		 * @param array $data    Saved profile data.
		 */
		do_action( 'apd_profile_saved', $user_id, $data );

		$this->set_message( 'success', __( 'Profile updated successfully.', 'all-purpose-directory' ) );

		// Redirect to remove POST data.
		$redirect_url = remove_query_arg( 'apd_profile_updated' );
		$redirect_url = add_query_arg( 'apd_profile_updated', '1', $redirect_url );
		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Collect and sanitize form data.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed> Sanitized form data.
	 */
	private function collect_form_data(): array {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Already verified in handle_save().
		return [
			'display_name' => isset( $_POST['display_name'] ) ? sanitize_text_field( wp_unslash( $_POST['display_name'] ) ) : '',
			'first_name'   => isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '',
			'last_name'    => isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '',
			'user_email'   => isset( $_POST['user_email'] ) ? sanitize_email( wp_unslash( $_POST['user_email'] ) ) : '',
			'description'  => isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '',
			'user_url'     => isset( $_POST['user_url'] ) ? esc_url_raw( wp_unslash( $_POST['user_url'] ) ) : '',
			'phone'        => isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '',
			'social'       => [
				'facebook'  => isset( $_POST['social_facebook'] ) ? esc_url_raw( wp_unslash( $_POST['social_facebook'] ) ) : '',
				'twitter'   => isset( $_POST['social_twitter'] ) ? esc_url_raw( wp_unslash( $_POST['social_twitter'] ) ) : '',
				'linkedin'  => isset( $_POST['social_linkedin'] ) ? esc_url_raw( wp_unslash( $_POST['social_linkedin'] ) ) : '',
				'instagram' => isset( $_POST['social_instagram'] ) ? esc_url_raw( wp_unslash( $_POST['social_instagram'] ) ) : '',
			],
		];
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Validate profile data.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $data Profile data to validate.
	 * @return bool|\WP_Error True on success, WP_Error on failure.
	 */
	public function validate_profile( array $data ): bool|\WP_Error {
		$errors = new \WP_Error();

		// Display name is required.
		if ( empty( $data['display_name'] ) ) {
			$errors->add( 'display_name', __( 'Display name is required.', 'all-purpose-directory' ) );
		}

		// Email validation.
		if ( empty( $data['user_email'] ) ) {
			$errors->add( 'user_email', __( 'Email is required.', 'all-purpose-directory' ) );
		} elseif ( ! is_email( $data['user_email'] ) ) {
			$errors->add( 'user_email', __( 'Please enter a valid email address.', 'all-purpose-directory' ) );
		} else {
			// Check if email is already used by another user.
			$existing_user = email_exists( $data['user_email'] );
			if ( $existing_user && $existing_user !== $this->user_id ) {
				$errors->add( 'user_email', __( 'This email is already registered to another account.', 'all-purpose-directory' ) );
			}
		}

		// URL validation.
		if ( ! empty( $data['user_url'] ) && ! filter_var( $data['user_url'], FILTER_VALIDATE_URL ) ) {
			$errors->add( 'user_url', __( 'Please enter a valid website URL.', 'all-purpose-directory' ) );
		}

		// Social URL validation.
		foreach ( self::SOCIAL_PLATFORMS as $platform ) {
			if ( ! empty( $data['social'][ $platform ] ) && ! filter_var( $data['social'][ $platform ], FILTER_VALIDATE_URL ) ) {
				$errors->add(
					'social_' . $platform,
					sprintf(
						/* translators: %s: Social platform name */
						__( 'Please enter a valid %s URL.', 'all-purpose-directory' ),
						ucfirst( $platform )
					)
				);
			}
		}

		/**
		 * Filter the profile validation errors.
		 *
		 * @since 1.0.0
		 *
		 * @param \WP_Error            $errors  Validation errors.
		 * @param array<string, mixed> $data    Profile data being validated.
		 * @param int                  $user_id User ID.
		 */
		$errors = apply_filters( 'apd_validate_profile', $errors, $data, $this->user_id );

		if ( $errors->has_errors() ) {
			return $errors;
		}

		return true;
	}

	/**
	 * Save profile data to database.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $data Profile data to save.
	 * @return bool|\WP_Error True on success, WP_Error on failure.
	 */
	public function save_profile( array $data ): bool|\WP_Error {
		/**
		 * Fires before profile data is saved.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, mixed> $data    Profile data to save.
		 * @param int                  $user_id User ID.
		 */
		do_action( 'apd_before_save_profile', $data, $this->user_id );

		// Update WordPress user data.
		$user_data = [
			'ID'           => $this->user_id,
			'display_name' => $data['display_name'],
			'first_name'   => $data['first_name'],
			'last_name'    => $data['last_name'],
			'user_email'   => $data['user_email'],
			'description'  => $data['description'],
			'user_url'     => $data['user_url'],
		];

		$result = wp_update_user( $user_data );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Update custom user meta.
		update_user_meta( $this->user_id, '_apd_phone', $data['phone'] );

		// Update avatar.
		if ( isset( $data['avatar_id'] ) ) {
			if ( $data['avatar_id'] > 0 ) {
				update_user_meta( $this->user_id, '_apd_avatar', $data['avatar_id'] );
			} else {
				delete_user_meta( $this->user_id, '_apd_avatar' );
			}
		}

		// Update social links.
		foreach ( self::SOCIAL_PLATFORMS as $platform ) {
			$meta_key = '_apd_social_' . $platform;
			$value    = $data['social'][ $platform ] ?? '';

			if ( ! empty( $value ) ) {
				update_user_meta( $this->user_id, $meta_key, $value );
			} else {
				delete_user_meta( $this->user_id, $meta_key );
			}
		}

		/**
		 * Fires after profile data is saved.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, mixed> $data    Saved profile data.
		 * @param int                  $user_id User ID.
		 */
		do_action( 'apd_after_save_profile', $data, $this->user_id );

		return true;
	}

	/**
	 * Handle avatar file upload.
	 *
	 * @since 1.0.0
	 *
	 * @return int|\WP_Error Attachment ID on success, WP_Error on failure.
	 */
	public function handle_avatar_upload(): int|\WP_Error {
		// Check if file was uploaded.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in handle_save() before calling this method.
		if ( ! isset( $_FILES['apd_avatar'] ) || empty( $_FILES['apd_avatar']['name'] ) ) {
			return 0;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verified in handle_save().
		$file = $_FILES['apd_avatar'];

		// Check for upload errors.
		if ( $file['error'] !== UPLOAD_ERR_OK ) {
			return new \WP_Error( 'upload_error', __( 'There was an error uploading the file. Please try again.', 'all-purpose-directory' ) );
		}

		// Validate file size.
		if ( $file['size'] > self::MAX_AVATAR_SIZE ) {
			return new \WP_Error(
				'file_too_large',
				sprintf(
					/* translators: %s: Maximum file size */
					__( 'Avatar file size must be less than %s.', 'all-purpose-directory' ),
					size_format( self::MAX_AVATAR_SIZE )
				)
			);
		}

		// Validate file type.
		$file_type = wp_check_filetype( $file['name'] );
		$mime_type = $file_type['type'];

		if ( ! in_array( $mime_type, self::ALLOWED_AVATAR_TYPES, true ) ) {
			return new \WP_Error(
				'invalid_file_type',
				__( 'Invalid file type. Please upload a JPEG, PNG, GIF, or WebP image.', 'all-purpose-directory' )
			);
		}

		// Require WordPress file handling functions.
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		// Upload the file.
		$attachment_id = media_handle_upload( 'apd_avatar', 0 );

		if ( is_wp_error( $attachment_id ) ) {
			return $attachment_id;
		}

		/**
		 * Fires after an avatar has been uploaded.
		 *
		 * @since 1.0.0
		 *
		 * @param int $attachment_id The attachment ID.
		 * @param int $user_id       The user ID.
		 */
		do_action( 'apd_avatar_uploaded', $attachment_id, $this->user_id );

		return $attachment_id;
	}

	/**
	 * Get the user's avatar URL.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id User ID.
	 * @param int $size    Avatar size in pixels.
	 * @return string Avatar URL.
	 */
	public function get_avatar_url( int $user_id, int $size = 150 ): string {
		// Check for custom avatar first.
		$avatar_id = (int) get_user_meta( $user_id, '_apd_avatar', true );

		if ( $avatar_id > 0 ) {
			$image = wp_get_attachment_image_url( $avatar_id, [ $size, $size ] );
			if ( $image ) {
				return $image;
			}
		}

		// Fall back to Gravatar.
		return get_avatar_url( $user_id, [ 'size' => $size ] );
	}

	/**
	 * Get user's social links.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id User ID.
	 * @return array<string, string> Social links keyed by platform.
	 */
	public function get_social_links( int $user_id ): array {
		$links = [];

		foreach ( self::SOCIAL_PLATFORMS as $platform ) {
			$value              = get_user_meta( $user_id, '_apd_social_' . $platform, true );
			$links[ $platform ] = is_string( $value ) ? $value : '';
		}

		/**
		 * Filter the user's social links.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, string> $links   Social links.
		 * @param int                   $user_id User ID.
		 */
		return apply_filters( 'apd_user_social_links', $links, $user_id );
	}

	/**
	 * Set a message transient for display.
	 *
	 * @since 1.0.0
	 *
	 * @param string $type    Message type (success, error, warning, info).
	 * @param string $message Message text.
	 * @return bool True on success.
	 */
	private function set_message( string $type, string $message ): bool {
		$transient_key = 'apd_profile_message_' . $this->user_id;

		return set_transient(
			$transient_key,
			[
				'type'    => $type,
				'message' => $message,
			],
			30
		);
	}

	/**
	 * Get any pending message for display.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string>|null Message array with 'type' and 'message', or null.
	 */
	public function get_message(): ?array {
		$transient_key = 'apd_profile_message_' . $this->user_id;
		$message       = get_transient( $transient_key );

		if ( $message ) {
			delete_transient( $transient_key );
			return $message;
		}

		return null;
	}

	/**
	 * Check if a user has a custom avatar.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id User ID.
	 * @return bool True if user has a custom avatar.
	 */
	public function has_custom_avatar( int $user_id ): bool {
		$avatar_id = (int) get_user_meta( $user_id, '_apd_avatar', true );
		return $avatar_id > 0;
	}

	/**
	 * Get social platform labels.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string> Platform labels keyed by slug.
	 */
	public function get_social_labels(): array {
		return [
			'facebook'  => __( 'Facebook', 'all-purpose-directory' ),
			'twitter'   => __( 'X (Twitter)', 'all-purpose-directory' ),
			'linkedin'  => __( 'LinkedIn', 'all-purpose-directory' ),
			'instagram' => __( 'Instagram', 'all-purpose-directory' ),
		];
	}

	/**
	 * Get social platform icons (dashicons).
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string> Dashicon classes keyed by platform slug.
	 */
	public function get_social_icons(): array {
		return [
			'facebook'  => 'dashicons-facebook',
			'twitter'   => 'dashicons-twitter',
			'linkedin'  => 'dashicons-linkedin',
			'instagram' => 'dashicons-instagram',
		];
	}
}
