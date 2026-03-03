<?php
/**
 * Review Handler Class.
 *
 * Processes review form submissions both via standard POST and AJAX.
 * Validates input, creates/updates reviews, and returns appropriate responses.
 *
 * @package APD\Review
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Review;

use WP_Error;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ReviewHandler
 *
 * @since 1.0.0
 */
class ReviewHandler {

	/**
	 * AJAX action name for submitting reviews.
	 */
	public const AJAX_ACTION = 'apd_submit_review';

	/**
	 * Singleton instance.
	 *
	 * @var ReviewHandler|null
	 */
	private static ?ReviewHandler $instance = null;

	/**
	 * Collected validation errors.
	 *
	 * @var WP_Error
	 */
	private WP_Error $errors;

	/**
	 * Get the singleton instance.
	 *
	 * @since 1.0.0
	 *
	 * @return ReviewHandler
	 */
	public static function get_instance(): ReviewHandler {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->errors = new WP_Error();
	}

	/**
	 * Prevent cloning.
	 */
	private function __clone() {}

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
	 * Initialize the review handler.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init(): void {
		if ( ! apd_reviews_enabled() ) {
			return;
		}

		// Register AJAX handlers.
		add_action( 'wp_ajax_' . self::AJAX_ACTION, [ $this, 'handle_ajax_submit' ] );
		add_action( 'wp_ajax_nopriv_' . self::AJAX_ACTION, [ $this, 'handle_ajax_submit_nopriv' ] );

		// Handle standard form submission.
		add_action( 'init', [ $this, 'handle_submit' ] );

		/**
		 * Fires after the review handler is initialized.
		 *
		 * @since 1.0.0
		 *
		 * @param ReviewHandler $handler The ReviewHandler instance.
		 */
		do_action( 'apd_review_handler_init', $this );
	}

	/**
	 * Handle standard form submission (non-AJAX).
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function handle_submit(): void {
		// Check if this is our form submission.
		if ( ! $this->is_submission_request() ) {
			return;
		}

		// Verify nonce.
		if ( ! $this->verify_nonce() ) {
			wp_die(
				esc_html__( 'Security check failed. Please try again.', 'all-purpose-directory' ),
				esc_html__( 'Error', 'all-purpose-directory' ),
				[ 'response' => 403 ]
			);
		}

		// Collect and sanitize data.
		$data = $this->collect_form_data();

		// Validate data.
		$validation = $this->validate( $data );

		if ( is_wp_error( $validation ) ) {
			// Store errors in transient for form display.
			$user_id = get_current_user_id();
			if ( $user_id > 0 ) {
				set_transient( 'apd_review_errors_' . $user_id, $validation->get_error_messages(), 60 );
				set_transient( 'apd_review_data_' . $user_id, $data, 60 );
			}

			// Redirect back to form.
			wp_safe_redirect( wp_get_referer() ?: home_url() );
			exit;
		}

		// Process the review.
		$result = $this->process_review( $data );

		if ( is_wp_error( $result ) ) {
			// Store error.
			$user_id = get_current_user_id();
			if ( $user_id > 0 ) {
				set_transient( 'apd_review_errors_' . $user_id, [ $result->get_error_message() ], 60 );
			}

			wp_safe_redirect( wp_get_referer() ?: home_url() );
			exit;
		}

		// Success - store flash in transient and redirect clean.
		$user_id = get_current_user_id();
		if ( $user_id > 0 ) {
			set_transient( 'apd_review_success_' . $user_id, $result, 60 );
		}

		wp_safe_redirect( wp_get_referer() ?: home_url() );
		exit;
	}

	/**
	 * Handle AJAX submission for logged-in users.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function handle_ajax_submit(): void {
		// Verify nonce.
		if ( ! check_ajax_referer( ReviewForm::NONCE_ACTION, 'nonce', false ) ) {
			wp_send_json_error(
				[
					'message' => __( 'Security check failed. Please refresh the page and try again.', 'all-purpose-directory' ),
				],
				403
			);
		}

		// Process the submission.
		$this->process_ajax_submission();
	}

	/**
	 * Handle AJAX submission for non-logged-in users.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function handle_ajax_submit_nopriv(): void {
		$manager = ReviewManager::get_instance();

		// Check if guest reviews are allowed.
		if ( $manager->requires_login() ) {
			wp_send_json_error(
				[
					'message'        => __( 'You must be logged in to submit a review.', 'all-purpose-directory' ),
					'login_required' => true,
				],
				401
			);
		}

		// Verify nonce.
		if ( ! check_ajax_referer( ReviewForm::NONCE_ACTION, 'nonce', false ) ) {
			wp_send_json_error(
				[
					'message' => __( 'Security check failed. Please refresh the page and try again.', 'all-purpose-directory' ),
				],
				403
			);
		}

		// Process the submission.
		$this->process_ajax_submission();
	}

	/**
	 * Process an AJAX review submission.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function process_ajax_submission(): void {
		// Collect and sanitize data.
		$data = $this->collect_form_data();

		// Validate data.
		$validation = $this->validate( $data );

		if ( is_wp_error( $validation ) ) {
			wp_send_json_error(
				[
					'message' => $validation->get_error_message(),
					'errors'  => $validation->get_error_messages(),
				],
				400
			);
		}

		// Process the review.
		$result = $this->process_review( $data );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error(
				[
					'message' => $result->get_error_message(),
				],
				400
			);
		}

		// Get the created/updated review.
		$review = ReviewManager::get_instance()->get( $result );

		// Determine success message.
		$is_update  = ! empty( $data['review_id'] );
		$is_pending = $review && $review['status'] === 'pending';

		$message = $is_update
			? __( 'Your review has been updated.', 'all-purpose-directory' )
			: __( 'Thank you for your review!', 'all-purpose-directory' );

		if ( $is_pending && ! $is_update ) {
			$message = __( 'Thank you! Your review has been submitted and is pending approval.', 'all-purpose-directory' );
		}

		/**
		 * Filter the success message for review submission.
		 *
		 * @since 1.0.0
		 *
		 * @param string $message   Success message.
		 * @param int    $review_id Review ID.
		 * @param array  $data      Submitted data.
		 * @param bool   $is_update Whether this was an update.
		 */
		$message = apply_filters( 'apd_review_success_message', $message, $result, $data, $is_update );

		wp_send_json_success(
			[
				'message'    => $message,
				'review_id'  => $result,
				'is_update'  => $is_update,
				'is_pending' => $is_pending,
				'review'     => $review,
			]
		);
	}

	/**
	 * Process a review (create or update).
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $data Review data.
	 * @return int|WP_Error Review ID on success, WP_Error on failure.
	 */
	private function process_review( array $data ): int|WP_Error {
		$manager = ReviewManager::get_instance();

		/**
		 * Fires before a review is processed.
		 *
		 * @since 1.0.0
		 *
		 * @param array $data Submitted review data.
		 */
		do_action( 'apd_before_review_process', $data );

		// Check if this is an update.
		if ( ! empty( $data['review_id'] ) ) {
			$review_id = absint( $data['review_id'] );

			// Verify user can edit this review.
			if ( ! $this->can_edit_review( $review_id ) ) {
				return new WP_Error(
					'permission_denied',
					__( 'You do not have permission to edit this review.', 'all-purpose-directory' )
				);
			}

			$result = $manager->update(
				$review_id,
				[
					'rating'  => $data['rating'],
					'title'   => $data['title'] ?? '',
					'content' => $data['content'],
				]
			);

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			/**
			 * Fires after a review is updated via the form.
			 *
			 * @since 1.0.0
			 *
			 * @param int   $review_id Review ID.
			 * @param array $data      Submitted data.
			 */
			do_action( 'apd_review_form_updated', $review_id, $data );

			return $review_id;
		}

		// Create new review.
		$review_data = [
			'rating'  => $data['rating'],
			'content' => $data['content'],
		];

		if ( ! empty( $data['title'] ) ) {
			$review_data['title'] = $data['title'];
		}

		// Handle guest reviews.
		if ( ! is_user_logged_in() ) {
			$review_data['author_name']  = $data['author_name'] ?? '';
			$review_data['author_email'] = $data['author_email'] ?? '';
		}

		$result = $manager->create( $data['listing_id'], $review_data );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		/**
		 * Fires after a review is created via the form.
		 *
		 * @since 1.0.0
		 *
		 * @param int   $review_id  Review ID.
		 * @param int   $listing_id Listing ID.
		 * @param array $data       Submitted data.
		 */
		do_action( 'apd_review_form_created', $result, $data['listing_id'], $data );

		return $result;
	}

	/**
	 * Validate review data.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $data Review data.
	 * @return bool|WP_Error True if valid, WP_Error on failure.
	 */
	public function validate( array $data ): bool|WP_Error {
		$this->errors = new WP_Error();
		$manager      = ReviewManager::get_instance();

		// Validate listing ID.
		if ( empty( $data['listing_id'] ) ) {
			$this->errors->add( 'listing_id', __( 'Invalid listing.', 'all-purpose-directory' ) );
		} else {
			$listing = get_post( $data['listing_id'] );
			if ( ! $listing || ! is_object( $listing ) || ( $listing->post_type ?? '' ) !== 'apd_listing' ) {
				$this->errors->add( 'listing_id', __( 'Invalid listing.', 'all-purpose-directory' ) );
			}
		}

		// Validate rating.
		if ( ! isset( $data['rating'] ) || $data['rating'] < ReviewManager::MIN_RATING || $data['rating'] > ReviewManager::MAX_RATING ) {
			$this->errors->add(
				'rating',
				sprintf(
					/* translators: 1: minimum rating, 2: maximum rating */
					__( 'Rating must be between %1$d and %2$d.', 'all-purpose-directory' ),
					ReviewManager::MIN_RATING,
					ReviewManager::MAX_RATING
				)
			);
		}

		// Validate content.
		$min_length = $manager->get_min_content_length();
		$content    = trim( $data['content'] ?? '' );

		if ( empty( $content ) ) {
			$this->errors->add( 'content', __( 'Review content is required.', 'all-purpose-directory' ) );
		} elseif ( \apd_strlen( $content ) < $min_length ) {
			$this->errors->add(
				'content',
				sprintf(
					/* translators: %d: minimum content length */
					__( 'Review must be at least %d characters.', 'all-purpose-directory' ),
					$min_length
				)
			);
		}

		// Validate guest fields if not logged in and guest reviews allowed.
		if ( ! is_user_logged_in() && ! $manager->requires_login() ) {
			if ( empty( $data['author_name'] ) ) {
				$this->errors->add( 'author_name', __( 'Your name is required.', 'all-purpose-directory' ) );
			}

			if ( empty( $data['author_email'] ) || ! is_email( $data['author_email'] ) ) {
				$this->errors->add( 'author_email', __( 'A valid email address is required.', 'all-purpose-directory' ) );
			}
		}

		/**
		 * Fires after review validation.
		 *
		 * @since 1.0.0
		 *
		 * @param WP_Error $errors The errors object.
		 * @param array    $data   The submitted data.
		 */
		do_action( 'apd_validate_review', $this->errors, $data );

		if ( $this->errors->has_errors() ) {
			return $this->errors;
		}

		return true;
	}

	/**
	 * Check if the current request is a review submission.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if this is a review submission request.
	 */
	private function is_submission_request(): bool {
		// Must be a POST request.
		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
			return false;
		}

		// Must have our action.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in handle_submit().
		if ( ! isset( $_POST['apd_action'] ) || $_POST['apd_action'] !== 'submit_review' ) {
			return false;
		}

		return true;
	}

	/**
	 * Verify the form nonce.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if nonce is valid.
	 */
	private function verify_nonce(): bool {
		if ( ! isset( $_POST[ ReviewForm::NONCE_NAME ] ) ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verification.
		return (bool) wp_verify_nonce( wp_unslash( $_POST[ ReviewForm::NONCE_NAME ] ), ReviewForm::NONCE_ACTION );
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

		// Listing ID.
		if ( isset( $_POST['listing_id'] ) ) {
			$data['listing_id'] = absint( $_POST['listing_id'] );
		}

		// Review ID (for updates).
		if ( isset( $_POST['review_id'] ) ) {
			$data['review_id'] = absint( $_POST['review_id'] );
		}

		// Rating.
		if ( isset( $_POST['rating'] ) ) {
			$data['rating'] = absint( $_POST['rating'] );
		}

		// Title (optional).
		if ( isset( $_POST['review_title'] ) ) {
			$data['title'] = sanitize_text_field( wp_unslash( $_POST['review_title'] ) );
		}

		// Content.
		if ( isset( $_POST['review_content'] ) ) {
			$data['content'] = wp_kses_post( wp_unslash( $_POST['review_content'] ) );
		}

		// Guest fields.
		if ( isset( $_POST['author_name'] ) ) {
			$data['author_name'] = sanitize_text_field( wp_unslash( $_POST['author_name'] ) );
		}

		if ( isset( $_POST['author_email'] ) ) {
			$data['author_email'] = sanitize_email( wp_unslash( $_POST['author_email'] ) );
		}

		// phpcs:enable WordPress.Security.NonceVerification.Missing

		/**
		 * Filter the collected review form data.
		 *
		 * @since 1.0.0
		 *
		 * @param array $data The collected form data.
		 */
		return apply_filters( 'apd_review_form_data_collected', $data );
	}

	/**
	 * Check if current user can edit a review.
	 *
	 * @since 1.0.0
	 *
	 * @param int $review_id Review ID.
	 * @return bool True if user can edit.
	 */
	private function can_edit_review( int $review_id ): bool {
		$review = ReviewManager::get_instance()->get( $review_id );

		if ( ! $review ) {
			return false;
		}

		$user_id = get_current_user_id();

		// Check if user is the author.
		if ( $review['author_id'] === $user_id && $user_id > 0 ) {
			return true;
		}

		// Check if user has moderator capabilities.
		if ( current_user_can( 'moderate_comments' ) ) {
			return true;
		}

		/**
		 * Filter whether user can edit a review.
		 *
		 * @since 1.0.0
		 *
		 * @param bool  $can_edit  Whether user can edit.
		 * @param int   $review_id Review ID.
		 * @param int   $user_id   Current user ID.
		 * @param array $review    Review data.
		 */
		return apply_filters( 'apd_user_can_edit_review', false, $review_id, $user_id, $review );
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
}
