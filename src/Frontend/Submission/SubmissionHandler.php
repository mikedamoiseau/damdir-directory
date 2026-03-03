<?php
/**
 * Submission Handler Class.
 *
 * Handles processing of frontend listing submission forms.
 * Validates input, creates/updates listings, handles file uploads,
 * assigns taxonomies, and manages redirects with success/error states.
 *
 * @package APD\Frontend\Submission
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Frontend\Submission;

use APD\Fields\FieldRegistry;
use APD\Fields\FieldValidator;
use WP_Error;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SubmissionHandler
 *
 * Processes frontend listing submissions.
 *
 * @since 1.0.0
 */
class SubmissionHandler {

	/**
	 * Action name for form submission.
	 */
	public const ACTION = 'apd_submit_listing';

	/**
	 * Nonce action for form submission.
	 */
	public const NONCE_ACTION = 'apd_submit_listing';

	/**
	 * Nonce field name.
	 */
	public const NONCE_NAME = 'apd_submission_nonce';

	/**
	 * Post type for listings.
	 */
	public const POST_TYPE = 'apd_listing';

	/**
	 * Allowed featured image MIME types.
	 *
	 * @var array<string>
	 */
	private const ALLOWED_IMAGE_TYPES = [
		'image/jpeg',
		'image/png',
		'image/gif',
		'image/webp',
	];

	/**
	 * Maximum file upload size in bytes (5MB).
	 *
	 * @var int
	 */
	private const MAX_FILE_SIZE = 5 * 1024 * 1024;

	/**
	 * The field registry instance.
	 *
	 * @var FieldRegistry
	 */
	private FieldRegistry $field_registry;

	/**
	 * The field validator instance.
	 *
	 * @var FieldValidator
	 */
	private FieldValidator $field_validator;

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
		'default_status'          => 'pending',
		'require_login'           => true,
		'require_title'           => true,
		'require_content'         => true,
		'require_category'        => false,
		'require_featured_image'  => false,
		'send_admin_notification' => true,
		'enable_spam_protection'  => true,
	];

	/**
	 * Collected validation errors.
	 *
	 * @var WP_Error
	 */
	private WP_Error $errors;

	/**
	 * Submitted form data.
	 *
	 * @var array<string, mixed>
	 */
	private array $submitted_data = [];

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $config          Optional. Configuration options.
	 * @param FieldRegistry|null   $field_registry  Optional. Field registry instance.
	 * @param FieldValidator|null  $field_validator Optional. Field validator instance.
	 */
	public function __construct(
		array $config = [],
		?FieldRegistry $field_registry = null,
		?FieldValidator $field_validator = null
	) {
		$this->config          = wp_parse_args( $config, self::DEFAULTS );
		$this->field_registry  = $field_registry ?? FieldRegistry::get_instance();
		$this->field_validator = $field_validator ?? new FieldValidator( $this->field_registry );
		$this->errors          = new WP_Error();
	}

	/**
	 * Initialize the submission handler hooks.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init(): void {
		// Handle form submission via init hook (form posts to same page).
		add_action( 'init', [ $this, 'handle_submission' ] );
	}

	/**
	 * Handle the form submission.
	 *
	 * This method is called on the init hook to process form submissions
	 * that POST to the same page (action="").
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function handle_submission(): void {
		// Check if this is our form submission.
		if ( ! $this->is_submission_request() ) {
			return;
		}

		// Process the submission.
		$result = $this->process();

		// Handle the result.
		if ( is_wp_error( $result ) ) {
			$this->handle_error( $result );
		} else {
			$this->handle_success( $result );
		}
	}

	/**
	 * Check if the current request is a submission request.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if this is a submission request.
	 */
	public function is_submission_request(): bool {
		// Must be a POST request.
		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
			return false;
		}

		// Must have our action.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in process().
		if ( ! isset( $_POST['apd_action'] ) || $_POST['apd_action'] !== 'submit_listing' ) {
			return false;
		}

		return true;
	}

	/**
	 * Process the form submission.
	 *
	 * @since 1.0.0
	 *
	 * @return int|WP_Error The created/updated listing ID on success, WP_Error on failure.
	 */
	public function process(): int|WP_Error {
		// Verify nonce.
		if ( ! $this->verify_nonce() ) {
			return new WP_Error(
				'invalid_nonce',
				__( 'Security check failed. Please try again.', 'all-purpose-directory' )
			);
		}

		// Run spam protection checks.
		if ( $this->config['enable_spam_protection'] ) {
			$spam_check = $this->check_spam_protection();
			if ( is_wp_error( $spam_check ) ) {
				return $spam_check;
			}
		}

		// Check user permissions.
		$permission_check = $this->check_permissions();
		if ( is_wp_error( $permission_check ) ) {
			return $permission_check;
		}

		// Collect and sanitize form data.
		$this->submitted_data = $this->collect_form_data();

		// Get listing ID if editing.
		$listing_id = $this->get_listing_id();
		$is_update  = $listing_id > 0;

		// Check edit permissions if updating.
		if ( $is_update ) {
			if ( ! $this->can_edit_listing( $listing_id ) ) {
				return new WP_Error(
					'permission_denied',
					__( 'You do not have permission to edit this listing.', 'all-purpose-directory' )
				);
			}
		}

		/**
		 * Fires before submission validation.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, mixed> $data       The submitted form data.
		 * @param int                  $listing_id The listing ID (0 for new).
		 */
		do_action( 'apd_before_submission', $this->submitted_data, $listing_id );

		// Validate all fields.
		$validation_result = $this->validate_submission();
		if ( is_wp_error( $validation_result ) ) {
			return $validation_result;
		}

		// Handle file upload.
		$image_id = $this->handle_featured_image_upload();
		if ( is_wp_error( $image_id ) ) {
			$this->errors->merge_from( $image_id );
		}

		// If we have errors at this point, return them.
		if ( $this->errors->has_errors() ) {
			return $this->errors;
		}

		// Create or update the listing.
		$result = $this->save_listing( $listing_id, $image_id );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$listing_id = $result;

		/**
		 * Fires after a listing has been successfully submitted.
		 *
		 * @since 1.0.0
		 *
		 * @param int                  $listing_id The created/updated listing ID.
		 * @param array<string, mixed> $data       The submitted form data.
		 * @param bool                 $is_new     Whether this is a new listing.
		 */
		do_action( 'apd_after_submission', $listing_id, $this->submitted_data, ! $is_update );

		// Send admin notification for new submissions.
		if ( $this->config['send_admin_notification'] && ! $is_update ) {
			$this->send_admin_notification( $listing_id );
		}

		return $listing_id;
	}

	/**
	 * Verify the form nonce.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if nonce is valid.
	 */
	private function verify_nonce(): bool {
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verification.
		return (bool) wp_verify_nonce( wp_unslash( $_POST[ self::NONCE_NAME ] ), self::NONCE_ACTION );
	}

	/**
	 * Check user permissions.
	 *
	 * @since 1.0.0
	 *
	 * @return bool|WP_Error True if permitted, WP_Error on failure.
	 */
	private function check_permissions(): bool|WP_Error {
		$who_can_submit = \apd_get_option( 'who_can_submit', 'anyone' );
		$guest_allowed  = (bool) \apd_get_option( 'guest_submission', false );
		$is_logged_in   = is_user_logged_in();

		// Determine if login is required based on settings.
		if ( 'logged_in' === $who_can_submit || 'specific_roles' === $who_can_submit ) {
			if ( ! $is_logged_in ) {
				return new WP_Error(
					'not_logged_in',
					__( 'You must be logged in to submit a listing.', 'all-purpose-directory' )
				);
			}
		} elseif ( 'anyone' === $who_can_submit && ! $guest_allowed && ! $is_logged_in ) {
			return new WP_Error(
				'not_logged_in',
				__( 'You must be logged in to submit a listing.', 'all-purpose-directory' )
			);
		}

		// Check specific roles (admins always pass).
		if ( 'specific_roles' === $who_can_submit && $is_logged_in ) {
			$user = wp_get_current_user();
			if ( ! in_array( 'administrator', (array) $user->roles, true ) ) {
				$allowed_roles = (array) \apd_get_option( 'submission_roles', [] );
				$has_role      = ! empty( array_intersect( (array) $user->roles, $allowed_roles ) );
				if ( ! $has_role ) {
					return new WP_Error(
						'role_not_allowed',
						__( 'Your user role does not have permission to submit listings.', 'all-purpose-directory' )
					);
				}
			}
		}

		/**
		 * Filter whether the current user can submit listings.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $can_submit Whether user can submit.
		 * @param int  $user_id    The current user ID.
		 */
		$can_submit = apply_filters( 'apd_user_can_submit_listing', true, get_current_user_id() );

		if ( ! $can_submit ) {
			return new WP_Error(
				'permission_denied',
				__( 'You do not have permission to submit listings.', 'all-purpose-directory' )
			);
		}

		return true;
	}

	/**
	 * Collect and sanitize form data.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed> Collected form data.
	 */
	private function collect_form_data(): array {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified earlier.
		$data = [];

		// Listing title.
		if ( isset( $_POST['listing_title'] ) ) {
			$data['listing_title'] = sanitize_text_field( wp_unslash( $_POST['listing_title'] ) );
		}

		// Listing content.
		if ( isset( $_POST['listing_content'] ) ) {
			$data['listing_content'] = wp_kses_post( wp_unslash( $_POST['listing_content'] ) );
		}

		// Listing excerpt.
		if ( isset( $_POST['listing_excerpt'] ) ) {
			$data['listing_excerpt'] = sanitize_textarea_field( wp_unslash( $_POST['listing_excerpt'] ) );
		}

		// Categories.
		if ( isset( $_POST['listing_categories'] ) && is_array( $_POST['listing_categories'] ) ) {
			$data['listing_categories'] = array_map( 'absint', $_POST['listing_categories'] );
		} else {
			$data['listing_categories'] = [];
		}

		// Tags.
		if ( isset( $_POST['listing_tags'] ) && is_array( $_POST['listing_tags'] ) ) {
			$data['listing_tags'] = array_map( 'absint', $_POST['listing_tags'] );
		} else {
			$data['listing_tags'] = [];
		}

		// Existing featured image ID.
		if ( isset( $_POST['featured_image'] ) ) {
			$data['featured_image'] = absint( $_POST['featured_image'] );
		}

		// Terms accepted (checkbox, sanitized as boolean).
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Boolean checkbox value.
		$data['terms_accepted'] = ! empty( $_POST['terms_accepted'] );

		// Redirect URL.
		if ( isset( $_POST['apd_redirect'] ) ) {
			$data['redirect'] = esc_url_raw( wp_unslash( $_POST['apd_redirect'] ) );
		}

		// Custom fields.
		$data['custom_fields'] = $this->collect_custom_field_data();

		// phpcs:enable WordPress.Security.NonceVerification.Missing

		/**
		 * Filter the collected form data.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, mixed> $data The collected form data.
		 */
		return apply_filters( 'apd_submission_form_data', $data );
	}

	/**
	 * Collect custom field data from POST.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed> Custom field values.
	 */
	private function collect_custom_field_data(): array {
		$values = [];
		$fields = $this->field_registry->get_fields(
			[
				'admin_only' => false,
			]
		);

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified earlier.

		// Check for apd_field array format.
		$apd_fields = isset( $_POST['apd_field'] ) && is_array( $_POST['apd_field'] )
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized by field validator.
			? wp_unslash( $_POST['apd_field'] )
			: [];

		foreach ( $fields as $field_name => $field_config ) {
			if ( isset( $apd_fields[ $field_name ] ) ) {
				$values[ $field_name ] = $apd_fields[ $field_name ];
			} elseif ( isset( $_POST[ $field_name ] ) ) {
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized by field validator.
				$values[ $field_name ] = wp_unslash( $_POST[ $field_name ] );
			} elseif ( in_array( $field_config['type'] ?? '', [ 'checkbox', 'switch' ], true ) ) {
				// Unchecked checkboxes don't appear in POST.
				$values[ $field_name ] = '';
			}
		}

		// phpcs:enable WordPress.Security.NonceVerification.Missing

		return $values;
	}

	/**
	 * Get the listing ID from form data.
	 *
	 * @since 1.0.0
	 *
	 * @return int Listing ID or 0 for new.
	 */
	private function get_listing_id(): int {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified in process().
		if ( isset( $_POST['apd_listing_id'] ) ) {
			return absint( $_POST['apd_listing_id'] );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		return 0;
	}

	/**
	 * Check if user can edit a listing.
	 *
	 * @since 1.0.0
	 *
	 * @param int $listing_id The listing ID.
	 * @return bool True if user can edit.
	 */
	private function can_edit_listing( int $listing_id ): bool {
		$post = get_post( $listing_id );

		if ( ! $post || $post->post_type !== self::POST_TYPE ) {
			return false;
		}

		// Check if user is the author or has edit capability.
		$user_id = get_current_user_id();

		if ( (int) $post->post_author === $user_id ) {
			return true;
		}

		if ( current_user_can( 'edit_apd_listing', $listing_id ) ) {
			return true;
		}

		/**
		 * Filter whether the user can edit this listing.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $can_edit   Whether user can edit.
		 * @param int  $listing_id The listing ID.
		 * @param int  $user_id    The current user ID.
		 */
		return apply_filters( 'apd_user_can_edit_listing', false, $listing_id, $user_id );
	}

	/**
	 * Validate the submission data.
	 *
	 * @since 1.0.0
	 *
	 * @return bool|WP_Error True if valid, WP_Error with all errors.
	 */
	private function validate_submission(): bool|WP_Error {
		$this->errors = new WP_Error();

		// Validate title.
		if ( $this->config['require_title'] ) {
			if ( empty( $this->submitted_data['listing_title'] ) ) {
				$this->errors->add(
					'listing_title',
					__( 'Listing title is required.', 'all-purpose-directory' )
				);
			}
		}

		// Validate content.
		if ( $this->config['require_content'] ) {
			if ( empty( $this->submitted_data['listing_content'] ) ) {
				$this->errors->add(
					'listing_content',
					__( 'Description is required.', 'all-purpose-directory' )
				);
			}
		}

		// Validate category.
		if ( $this->config['require_category'] ) {
			if ( empty( $this->submitted_data['listing_categories'] ) ) {
				$this->errors->add(
					'listing_categories',
					__( 'Please select at least one category.', 'all-purpose-directory' )
				);
			}
		}

		// Validate terms acceptance when a terms page is configured.
		$terms_page_id = (int) \apd_get_option( 'terms_page', 0 );
		if ( $terms_page_id > 0 ) {
			if ( empty( $this->submitted_data['terms_accepted'] ) ) {
				$this->errors->add(
					'terms_accepted',
					__( 'You must accept the terms and conditions.', 'all-purpose-directory' )
				);
			}
		}

		// Validate custom fields.
		$this->field_validator->set_context( 'frontend' );
		$field_result = $this->field_validator->validate_fields(
			$this->submitted_data['custom_fields'] ?? [],
			[
				'skip_unregistered' => true,
			]
		);

		if ( is_wp_error( $field_result ) ) {
			$this->errors->merge_from( $field_result );
		}

		/**
		 * Fires after submission validation.
		 *
		 * Use this hook to add custom validation logic.
		 *
		 * @since 1.0.0
		 *
		 * @param WP_Error             $errors The errors object.
		 * @param array<string, mixed> $data   The submitted form data.
		 */
		do_action( 'apd_validate_submission', $this->errors, $this->submitted_data );

		return $this->errors->has_errors() ? $this->errors : true;
	}

	/**
	 * Handle featured image upload.
	 *
	 * @since 1.0.0
	 *
	 * @return int|WP_Error Attachment ID on success, 0 if no upload, WP_Error on failure.
	 */
	private function handle_featured_image_upload(): int|WP_Error {
		// Check if a file was uploaded.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in process().
		if ( empty( $_FILES['featured_image_file'] ) || empty( $_FILES['featured_image_file']['name'] ) ) {
			// Check if required.
			if ( $this->config['require_featured_image'] ) {
				$existing_id = $this->submitted_data['featured_image'] ?? 0;
				if ( $existing_id <= 0 ) {
					return new WP_Error(
						'featured_image',
						__( 'A featured image is required.', 'all-purpose-directory' )
					);
				}
			}
			return 0;
		}

		// Get file info.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput,WordPress.Security.NonceVerification.Missing -- File data, nonce verified in process().
		$file = $_FILES['featured_image_file'];

		// Validate file type.
		$file_type = wp_check_filetype( $file['name'] );
		if ( empty( $file_type['type'] ) || ! in_array( $file_type['type'], self::ALLOWED_IMAGE_TYPES, true ) ) {
			return new WP_Error(
				'featured_image',
				__( 'Invalid image type. Please upload a JPG, PNG, GIF, or WebP image.', 'all-purpose-directory' )
			);
		}

		// Validate file size.
		$max_size = min( self::MAX_FILE_SIZE, wp_max_upload_size() );
		if ( $file['size'] > $max_size ) {
			return new WP_Error(
				'featured_image',
				sprintf(
					/* translators: %s: maximum file size */
					__( 'Image file is too large. Maximum size is %s.', 'all-purpose-directory' ),
					size_format( $max_size )
				)
			);
		}

		// Check for upload errors.
		if ( $file['error'] !== UPLOAD_ERR_OK ) {
			return new WP_Error(
				'featured_image',
				$this->get_upload_error_message( $file['error'] )
			);
		}

		// Require media handling files.
		if ( ! function_exists( 'media_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
		}

		// Handle the upload.
		$attachment_id = media_handle_upload( 'featured_image_file', 0 );

		if ( is_wp_error( $attachment_id ) ) {
			return new WP_Error(
				'featured_image',
				$attachment_id->get_error_message()
			);
		}

		return $attachment_id;
	}

	/**
	 * Get human-readable upload error message.
	 *
	 * @since 1.0.0
	 *
	 * @param int $error_code PHP upload error code.
	 * @return string Error message.
	 */
	private function get_upload_error_message( int $error_code ): string {
		$messages = [
			UPLOAD_ERR_INI_SIZE   => __( 'The uploaded file exceeds the maximum file size.', 'all-purpose-directory' ),
			UPLOAD_ERR_FORM_SIZE  => __( 'The uploaded file exceeds the maximum file size.', 'all-purpose-directory' ),
			UPLOAD_ERR_PARTIAL    => __( 'The uploaded file was only partially uploaded.', 'all-purpose-directory' ),
			UPLOAD_ERR_NO_FILE    => __( 'No file was uploaded.', 'all-purpose-directory' ),
			UPLOAD_ERR_NO_TMP_DIR => __( 'Server configuration error: Missing temporary folder.', 'all-purpose-directory' ),
			UPLOAD_ERR_CANT_WRITE => __( 'Failed to write file to disk.', 'all-purpose-directory' ),
			UPLOAD_ERR_EXTENSION  => __( 'A PHP extension stopped the file upload.', 'all-purpose-directory' ),
		];

		return $messages[ $error_code ] ?? __( 'Unknown upload error.', 'all-purpose-directory' );
	}

	/**
	 * Save the listing (create or update).
	 *
	 * @since 1.0.0
	 *
	 * @param int $listing_id Existing listing ID (0 for new).
	 * @param int $image_id   Featured image attachment ID.
	 * @return int|WP_Error The listing ID on success, WP_Error on failure.
	 */
	private function save_listing( int $listing_id, int $image_id ): int|WP_Error {
		$is_update = $listing_id > 0;

		// Prepare post data.
		$post_data = [
			'post_type'    => self::POST_TYPE,
			'post_title'   => $this->submitted_data['listing_title'] ?? '',
			'post_content' => $this->submitted_data['listing_content'] ?? '',
			'post_excerpt' => $this->submitted_data['listing_excerpt'] ?? '',
			'post_status'  => $this->get_post_status( $listing_id ),
		];

		// Set author for new listings.
		if ( ! $is_update ) {
			$post_data['post_author'] = get_current_user_id();
		}

		// Update existing or create new.
		if ( $is_update ) {
			$post_data['ID'] = $listing_id;

			/**
			 * Fires before a listing is updated.
			 *
			 * @since 1.0.0
			 *
			 * @param int   $listing_id The listing ID.
			 * @param array $post_data  The post data.
			 */
			do_action( 'apd_before_listing_update', $listing_id, $post_data );

			$result = wp_update_post( $post_data, true );
		} else {
			/**
			 * Filter the post data before creating a new listing.
			 *
			 * @since 1.0.0
			 *
			 * @param array<string, mixed> $post_data The post data.
			 * @param array<string, mixed> $form_data The submitted form data.
			 */
			$post_data = apply_filters( 'apd_new_listing_post_data', $post_data, $this->submitted_data );

			$result = wp_insert_post( $post_data, true );
		}

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$listing_id = $result;

		// Assign taxonomies.
		$this->assign_taxonomies( $listing_id );

		// Set featured image.
		$this->set_featured_image( $listing_id, $image_id );

		// Save custom fields.
		$this->save_custom_fields( $listing_id );

		/**
		 * Fires after a listing has been saved.
		 *
		 * @since 1.0.0
		 *
		 * @param int                  $listing_id The listing ID.
		 * @param array<string, mixed> $data       The submitted form data.
		 * @param bool                 $is_update  Whether this was an update.
		 */
		do_action( 'apd_listing_saved', $listing_id, $this->submitted_data, $is_update );

		return $listing_id;
	}

	/**
	 * Get the post status for the listing.
	 *
	 * @since 1.0.0
	 *
	 * @param int $listing_id Existing listing ID (0 for new).
	 * @return string Post status.
	 */
	private function get_post_status( int $listing_id ): string {
		// For updates, keep existing status by default but allow filtering.
		if ( $listing_id > 0 ) {
			$post = get_post( $listing_id );
			if ( $post ) {
				$current_status = $post->post_status;

				/**
				 * Filter the status for edited listings.
				 *
				 * By default, the current status is preserved when editing.
				 * Use this filter to force listings back to pending review after edits,
				 * or implement custom status transition logic.
				 *
				 * @since 1.0.0
				 *
				 * @param string $status         The status to use. Default is current status.
				 * @param string $current_status The listing's current status.
				 * @param int    $listing_id     The listing ID being edited.
				 * @param int    $user_id        The user ID making the edit.
				 */
				return apply_filters(
					'apd_edit_listing_status',
					$current_status,
					$current_status,
					$listing_id,
					get_current_user_id()
				);
			}
		}

		/**
		 * Filter the default status for new submissions.
		 *
		 * @since 1.0.0
		 *
		 * @param string $status  The default status.
		 * @param int    $user_id The submitting user ID.
		 */
		return apply_filters( 'apd_submission_default_status', $this->config['default_status'], get_current_user_id() );
	}

	/**
	 * Assign taxonomies to the listing.
	 *
	 * @since 1.0.0
	 *
	 * @param int $listing_id The listing ID.
	 * @return void
	 */
	private function assign_taxonomies( int $listing_id ): void {
		// Assign categories.
		$categories = $this->submitted_data['listing_categories'] ?? [];
		if ( ! empty( $categories ) ) {
			wp_set_object_terms( $listing_id, $categories, 'apd_category' );
		} else {
			wp_set_object_terms( $listing_id, [], 'apd_category' );
		}

		// Assign tags.
		$tags = $this->submitted_data['listing_tags'] ?? [];
		if ( ! empty( $tags ) ) {
			wp_set_object_terms( $listing_id, $tags, 'apd_tag' );
		} else {
			wp_set_object_terms( $listing_id, [], 'apd_tag' );
		}

		/**
		 * Fires after taxonomies have been assigned to a listing.
		 *
		 * @since 1.0.0
		 *
		 * @param int   $listing_id The listing ID.
		 * @param array $categories The assigned category IDs.
		 * @param array $tags       The assigned tag IDs.
		 */
		do_action( 'apd_listing_taxonomies_assigned', $listing_id, $categories, $tags );
	}

	/**
	 * Set the featured image for the listing.
	 *
	 * @since 1.0.0
	 *
	 * @param int $listing_id The listing ID.
	 * @param int $image_id   The uploaded image ID (0 if none uploaded).
	 * @return void
	 */
	private function set_featured_image( int $listing_id, int $image_id ): void {
		// Use newly uploaded image.
		if ( $image_id > 0 ) {
			set_post_thumbnail( $listing_id, $image_id );
			// Update attachment to be attached to the listing.
			wp_update_post(
				[
					'ID'          => $image_id,
					'post_parent' => $listing_id,
				]
			);
			return;
		}

		// Use existing image ID from form.
		$existing_id = $this->submitted_data['featured_image'] ?? 0;
		if ( $existing_id > 0 && $this->validate_featured_image_ownership( $existing_id ) ) {
			set_post_thumbnail( $listing_id, $existing_id );
			return;
		}

		// No image - remove if exists.
		delete_post_thumbnail( $listing_id );
	}

	/**
	 * Validate that the current user can use an attachment as a featured image.
	 *
	 * Prevents IDOR where a user could reference another user's private attachment
	 * by submitting an arbitrary attachment ID in the form.
	 *
	 * @since 1.0.0
	 *
	 * @param int $attachment_id The attachment ID to validate.
	 * @return bool True if the user can use this attachment.
	 */
	private function validate_featured_image_ownership( int $attachment_id ): bool {
		$attachment = get_post( $attachment_id );

		if ( ! $attachment || $attachment->post_type !== 'attachment' ) {
			return false;
		}

		if ( ! wp_attachment_is_image( $attachment_id ) ) {
			return false;
		}

		// Allow if user is admin or the attachment author.
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		return (int) $attachment->post_author === get_current_user_id();
	}

	/**
	 * Save custom field values.
	 *
	 * @since 1.0.0
	 *
	 * @param int $listing_id The listing ID.
	 * @return void
	 */
	private function save_custom_fields( int $listing_id ): void {
		$custom_fields = $this->submitted_data['custom_fields'] ?? [];

		if ( empty( $custom_fields ) ) {
			return;
		}

		// Process (sanitize and validate) the fields.
		$result = \apd_process_fields( $custom_fields );

		// Save each sanitized value.
		foreach ( $result['values'] as $field_name => $value ) {
			\apd_set_listing_field( $listing_id, $field_name, $value );
		}

		/**
		 * Fires after custom fields have been saved.
		 *
		 * @since 1.0.0
		 *
		 * @param int                  $listing_id The listing ID.
		 * @param array<string, mixed> $values     The saved field values.
		 */
		do_action( 'apd_listing_fields_saved', $listing_id, $result['values'] );
	}

	/**
	 * Send admin notification for new submission.
	 *
	 * @since 1.0.0
	 *
	 * @param int $listing_id The new listing ID.
	 * @return void
	 */
	private function send_admin_notification( int $listing_id ): void {
		$admin_email = get_option( 'admin_email' );
		$post        = get_post( $listing_id );

		if ( ! $post || ! $admin_email ) {
			return;
		}

		$subject = sprintf(
			/* translators: %s: listing title */
			__( '[%1$s] New Listing Submission: %2$s', 'all-purpose-directory' ),
			get_bloginfo( 'name' ),
			$post->post_title
		);

		$edit_link = admin_url( 'post.php?post=' . $listing_id . '&action=edit' );

		$message = sprintf(
			/* translators: 1: listing title, 2: author name, 3: edit URL */
			__(
				'A new listing has been submitted:

Title: %1$s
Author: %2$s
Status: %3$s

Review the listing: %4$s',
				'all-purpose-directory'
			),
			$post->post_title,
			get_the_author_meta( 'display_name', $post->post_author ),
			$post->post_status,
			$edit_link
		);

		/**
		 * Filter the admin notification email.
		 *
		 * @since 1.0.0
		 *
		 * @param array $email      The email data (to, subject, message).
		 * @param int   $listing_id The listing ID.
		 */
		$email = apply_filters(
			'apd_submission_admin_notification',
			[
				'to'      => $admin_email,
				'subject' => $subject,
				'message' => $message,
			],
			$listing_id
		);

		wp_mail( $email['to'], $email['subject'], $email['message'] );
	}

	/**
	 * Handle successful submission.
	 *
	 * @since 1.0.0
	 *
	 * @param int $listing_id The created/updated listing ID.
	 * @return void
	 */
	private function handle_success( int $listing_id ): void {
		$redirect_url = $this->submitted_data['redirect'] ?? '';
		$is_update    = $this->get_listing_id() > 0;

		// Build success URL.
		$query_args = [
			'apd_submission' => 'success',
			'listing_id'     => $listing_id,
		];

		// Add is_update flag for edit submissions.
		if ( $is_update ) {
			$query_args['is_update'] = '1';
		}

		// If no explicit redirect from shortcode attribute, use the setting.
		if ( empty( $redirect_url ) ) {
			$redirect_setting = \apd_get_option( 'redirect_after', 'listing' );

			switch ( $redirect_setting ) {
				case 'listing':
					$redirect_url = get_permalink( $listing_id );
					break;

				case 'dashboard':
					$dashboard_page = \apd_get_option( 'dashboard_page', 0 );
					if ( $dashboard_page ) {
						$redirect_url = get_permalink( $dashboard_page );
					}
					break;

				case 'custom':
					$redirect_url = \apd_get_option( 'redirect_custom_url', '' );
					break;
			}
		}

		if ( empty( $redirect_url ) ) {
			// Final fallback: current page with success param.
			$redirect_url = add_query_arg( $query_args, wp_get_referer() ?: home_url() );
		} else {
			$redirect_url = add_query_arg( $query_args, $redirect_url );
		}

		/**
		 * Filter the success redirect URL.
		 *
		 * @since 1.0.0
		 *
		 * @param string $redirect_url The redirect URL.
		 * @param int    $listing_id   The listing ID.
		 * @param bool   $is_update    Whether this was an update.
		 */
		$redirect_url = apply_filters( 'apd_submission_success_redirect', $redirect_url, $listing_id, $is_update );

		wp_safe_redirect( esc_url_raw( $redirect_url ) );
		exit;
	}

	/**
	 * Handle submission error.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Error $errors The error object.
	 * @return void
	 */
	private function handle_error( WP_Error $errors ): void {
		$user_id = get_current_user_id();

		// Store errors and submitted values in transients for the form to display.
		if ( $user_id > 0 ) {
			\apd_set_submission_errors( $errors, $user_id );
			\apd_set_submission_values( $this->submitted_data, $user_id );
		}

		// Redirect back to the form.
		$redirect_url = wp_get_referer() ?: home_url();

		/**
		 * Filter the error redirect URL.
		 *
		 * @since 1.0.0
		 *
		 * @param string   $redirect_url The redirect URL.
		 * @param WP_Error $errors       The validation errors.
		 */
		$redirect_url = apply_filters( 'apd_submission_error_redirect', $redirect_url, $errors );

		wp_safe_redirect( esc_url_raw( $redirect_url ) );
		exit;
	}

	// =========================================================================
	// Spam Protection Methods
	// =========================================================================

	/**
	 * Run all spam protection checks.
	 *
	 * Checks honeypot field, submission time, rate limiting,
	 * and allows extensions to add custom checks.
	 *
	 * @since 1.0.0
	 *
	 * @return bool|WP_Error True if passed, WP_Error if spam detected.
	 */
	private function check_spam_protection(): bool|WP_Error {
		/**
		 * Filter whether to bypass spam protection entirely.
		 *
		 * Use this to bypass spam checks for trusted users/roles.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $bypass    Whether to bypass. Default false.
		 * @param int  $user_id   The current user ID.
		 */
		$bypass = apply_filters( 'apd_bypass_spam_protection', false, get_current_user_id() );

		if ( $bypass ) {
			return true;
		}

		// Check honeypot field.
		if ( $this->is_honeypot_filled() ) {
			$this->log_spam_attempt( 'honeypot' );
			return $this->get_generic_spam_error();
		}

		// Check submission time (too fast = likely bot).
		if ( $this->is_submission_too_fast() ) {
			$this->log_spam_attempt( 'timing' );
			return $this->get_generic_spam_error();
		}

		// Check rate limiting.
		$rate_limit_check = $this->check_rate_limit();
		if ( is_wp_error( $rate_limit_check ) ) {
			$this->log_spam_attempt( 'rate_limit' );
			return $rate_limit_check;
		}

		/**
		 * Filter to run custom spam checks.
		 *
		 * Third-party integrations (like reCAPTCHA) can hook here
		 * to add additional spam checking. Return WP_Error to block.
		 *
		 * @since 1.0.0
		 *
		 * @param bool|WP_Error $result    Current result. True if passed.
		 * @param array         $post_data The $_POST data.
		 * @param int           $user_id   The current user ID.
		 */
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in process().
		$custom_check = apply_filters( 'apd_submission_spam_check', true, $_POST, get_current_user_id() );

		if ( is_wp_error( $custom_check ) ) {
			$this->log_spam_attempt( 'custom' );
			return $custom_check;
		}

		return true;
	}

	/**
	 * Check if the honeypot field was filled.
	 *
	 * The honeypot field is hidden from real users but visible to bots.
	 * If it contains a value, this is likely a bot submission.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if honeypot was filled (spam).
	 */
	private function is_honeypot_filled(): bool {
		/**
		 * Filter the honeypot field name.
		 *
		 * Must match the filter in SubmissionForm.
		 *
		 * @since 1.0.0
		 *
		 * @param string $field_name The honeypot field name.
		 */
		$honeypot_field = apply_filters( 'apd_honeypot_field_name', 'website_url' );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified earlier.
		if ( ! isset( $_POST[ $honeypot_field ] ) ) {
			// Field not present - might be an older form or spam protection disabled.
			return false;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$value = wp_unslash( $_POST[ $honeypot_field ] );

		// If the field has any value, it's likely spam.
		// Use hash_equals for constant-time comparison (prevents timing attacks).
		return ! hash_equals( '', (string) $value );
	}

	/**
	 * Check if the submission happened too quickly.
	 *
	 * Bots typically submit forms instantly. Real users need time
	 * to read and fill out the form.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if submission was too fast (spam).
	 */
	private function is_submission_too_fast(): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified earlier.
		if ( ! isset( $_POST['apd_form_token'] ) ) {
			// Token not present - might be an older form.
			return false;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$token = wp_unslash( $_POST['apd_form_token'] );

		// Decode and verify the signed timestamp.
		$decoded = base64_decode( (string) $token, true );
		if ( $decoded === false || strpos( $decoded, '|' ) === false ) {
			return true;
		}

		[ $timestamp_str, $signature ] = explode( '|', $decoded, 2 );
		$expected_signature            = hash_hmac( 'sha256', $timestamp_str, wp_salt( 'nonce' ) );

		if ( ! hash_equals( $expected_signature, $signature ) ) {
			return true;
		}

		$form_load_time = (int) $timestamp_str;
		$current_time   = time();

		// Check if timestamp is valid (not in the future, not too old).
		$day_in_seconds = defined( 'DAY_IN_SECONDS' ) ? DAY_IN_SECONDS : 86400;
		if ( $form_load_time > $current_time || $form_load_time < ( $current_time - $day_in_seconds ) ) {
			return true;
		}

		$elapsed = $current_time - $form_load_time;

		/**
		 * Filter the minimum time required for form submission.
		 *
		 * @since 1.0.0
		 *
		 * @param int $min_time Minimum seconds before submission is allowed. Default 3.
		 */
		$min_time = apply_filters( 'apd_submission_min_time', 3 );

		return $elapsed < $min_time;
	}

	/**
	 * Check rate limiting for submissions.
	 *
	 * Prevents users/IPs from submitting too many listings in a time period.
	 *
	 * @since 1.0.0
	 *
	 * @return bool|WP_Error True if within limit, WP_Error if rate limited.
	 */
	private function check_rate_limit(): bool|WP_Error {
		$identifier = $this->get_rate_limit_identifier();

		/**
		 * Filter the submission rate limit.
		 *
		 * @since 1.0.0
		 *
		 * @param int $limit Maximum submissions allowed in the time period. Default 5.
		 */
		$limit = apply_filters( 'apd_submission_rate_limit', 5 );

		/**
		 * Filter the submission rate limit time period.
		 *
		 * @since 1.0.0
		 *
		 * @param int $period Time period in seconds. Default 3600 (1 hour).
		 */
		$hour_in_seconds = defined( 'HOUR_IN_SECONDS' ) ? HOUR_IN_SECONDS : 3600;
		$period          = apply_filters( 'apd_submission_rate_period', $hour_in_seconds );

		$transient_key = 'apd_submission_count_' . $identifier;
		$count         = (int) get_transient( $transient_key );

		if ( $count >= $limit ) {
			return new WP_Error(
				'rate_limited',
				__( 'You have submitted too many listings. Please try again later.', 'all-purpose-directory' )
			);
		}

		// Increment the counter.
		set_transient( $transient_key, $count + 1, $period );

		return true;
	}

	/**
	 * Get the identifier for rate limiting.
	 *
	 * Uses user ID for logged-in users, IP address for guests.
	 *
	 * @since 1.0.0
	 *
	 * @return string Rate limit identifier.
	 */
	private function get_rate_limit_identifier(): string {
		$user_id = get_current_user_id();

		if ( $user_id > 0 ) {
			return 'user_' . $user_id;
		}

		// Use IP address for guests.
		$ip = $this->get_client_ip();

		// Hash the IP for privacy and to create a safe transient key.
		return 'ip_' . md5( $ip );
	}

	/**
	 * Get the client IP address.
	 *
	 * @since 1.0.0
	 *
	 * @return string Client IP address.
	 */
	private function get_client_ip(): string {
		$remote_addr = $this->extract_first_valid_ip( sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) ) );

		// Default to REMOTE_ADDR unless this request came through a trusted proxy.
		$client_ip = $remote_addr;
		if ( '' !== $remote_addr && $this->is_trusted_proxy_ip( $remote_addr ) ) {
			foreach ( [ 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP' ] as $header ) {
				if ( empty( $_SERVER[ $header ] ) ) {
					continue;
				}

				$forwarded_ip = $this->extract_first_valid_ip( sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) ) );
				if ( '' !== $forwarded_ip ) {
					$client_ip = $forwarded_ip;
					break;
				}
			}
		}

		return '' !== $client_ip ? $client_ip : '0.0.0.0';
	}

	/**
	 * Extract the first valid IP from a potentially comma-separated value.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value Header/raw IP value.
	 * @return string Valid IP or empty string when invalid.
	 */
	private function extract_first_valid_ip( string $value ): string {
		$candidates = array_map( 'trim', explode( ',', $value ) );

		foreach ( $candidates as $candidate ) {
			if ( filter_var( $candidate, FILTER_VALIDATE_IP ) ) {
				return $candidate;
			}
		}

		return '';
	}

	/**
	 * Check whether an IP belongs to a trusted proxy.
	 *
	 * @since 1.0.0
	 *
	 * @param string $ip IP address to validate.
	 * @return bool
	 */
	private function is_trusted_proxy_ip( string $ip ): bool {
		$trusted_proxies = apply_filters( 'apd_submission_trusted_proxies', [] );
		if ( ! is_array( $trusted_proxies ) ) {
			return false;
		}

		foreach ( $trusted_proxies as $proxy ) {
			if ( ! is_string( $proxy ) ) {
				continue;
			}

			$proxy = trim( $proxy );
			if ( '' === $proxy ) {
				continue;
			}

			if ( $this->ip_matches_proxy( $ip, $proxy ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Match an IP against exact/CIDR trusted proxy definitions.
	 *
	 * @since 1.0.0
	 *
	 * @param string $ip    Client IP.
	 * @param string $proxy Trusted proxy value (single IP or CIDR).
	 * @return bool
	 */
	private function ip_matches_proxy( string $ip, string $proxy ): bool {
		if ( strpos( $proxy, '/' ) === false ) {
			return $ip === $proxy;
		}

		[ $subnet, $mask_length ] = explode( '/', $proxy, 2 );
		$subnet                   = trim( $subnet );
		$mask_length              = (int) $mask_length;

		$ip_binary     = inet_pton( $ip );
		$subnet_binary = inet_pton( $subnet );

		if ( false === $ip_binary || false === $subnet_binary || strlen( $ip_binary ) !== strlen( $subnet_binary ) ) {
			return false;
		}

		$max_bits = strlen( $ip_binary ) * 8;
		if ( $mask_length < 0 || $mask_length > $max_bits ) {
			return false;
		}

		$full_bytes = intdiv( $mask_length, 8 );
		$extra_bits = $mask_length % 8;

		if ( $full_bytes > 0 && substr( $ip_binary, 0, $full_bytes ) !== substr( $subnet_binary, 0, $full_bytes ) ) {
			return false;
		}

		if ( 0 === $extra_bits ) {
			return true;
		}

		$mask = 0xFF << ( 8 - $extra_bits );

		return ( ord( $ip_binary[ $full_bytes ] ) & $mask ) === ( ord( $subnet_binary[ $full_bytes ] ) & $mask );
	}

	/**
	 * Get a generic spam error message.
	 *
	 * We don't reveal which check failed to avoid helping spammers.
	 *
	 * @since 1.0.0
	 *
	 * @return WP_Error Generic spam error.
	 */
	private function get_generic_spam_error(): WP_Error {
		return new WP_Error(
			'submission_failed',
			__( 'Submission failed. Please try again.', 'all-purpose-directory' )
		);
	}

	/**
	 * Log a spam attempt.
	 *
	 * Fires an action hook for admin logging/notification.
	 *
	 * @since 1.0.0
	 *
	 * @param string $type Type of spam detected (honeypot, timing, rate_limit, custom).
	 * @return void
	 */
	private function log_spam_attempt( string $type ): void {
		/**
		 * Fires when a spam submission attempt is detected.
		 *
		 * Use this hook to log spam attempts or send notifications.
		 *
		 * @since 1.0.0
		 *
		 * @param string $type       Type of spam detected.
		 * @param string $ip         Client IP address.
		 * @param int    $user_id    User ID (0 for guests).
		 * @param array  $post_data  The submitted POST data.
		 */
		do_action(
			'apd_spam_attempt_detected',
			$type,
			$this->get_client_ip(),
			get_current_user_id(),
			$_POST // phpcs:ignore WordPress.Security.NonceVerification.Missing
		);
	}

	/**
	 * Get the submitted form data.
	 *
	 * Useful for accessing data after processing in hooks.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed> The submitted form data.
	 */
	public function get_submitted_data(): array {
		return $this->submitted_data;
	}

	/**
	 * Get the current errors.
	 *
	 * @since 1.0.0
	 *
	 * @return WP_Error The errors object.
	 */
	public function get_errors(): WP_Error {
		return $this->errors;
	}

	/**
	 * Get configuration value.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key     Configuration key.
	 * @param mixed  $default Default value.
	 * @return mixed Configuration value.
	 */
	public function get_config( string $key, mixed $default = null ): mixed {
		return $this->config[ $key ] ?? $default;
	}
}
