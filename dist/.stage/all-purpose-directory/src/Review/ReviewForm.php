<?php
/**
 * Review Form Class.
 *
 * Renders the review submission form for listings.
 * Handles both new reviews and editing existing reviews.
 *
 * @package APD\Review
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Review;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ReviewForm
 *
 * @since 1.0.0
 */
class ReviewForm {

	/**
	 * Nonce action for review form.
	 */
	public const NONCE_ACTION = 'apd_submit_review';

	/**
	 * Nonce field name.
	 */
	public const NONCE_NAME = 'apd_review_nonce';

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
		'show_title'         => true,
		'title_required'     => false,
		'min_content_length' => 10,
		'ajax_enabled'       => true,
		'show_guidelines'    => true,
		'guidelines_text'    => '',
	];

	/**
	 * Singleton instance.
	 *
	 * @var ReviewForm|null
	 */
	private static ?ReviewForm $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @since 1.0.0
	 *
	 * @return ReviewForm
	 */
	public static function get_instance(): ReviewForm {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed> $config Configuration options.
	 */
	private function __construct( array $config = [] ) {
		$this->config = wp_parse_args( $config, self::DEFAULTS );
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
	 * Initialize the review form.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init(): void {
		if ( ! apd_reviews_enabled() ) {
			return;
		}

		// Hook into the render review form action (triggered by reviews-section.php template).
		add_action( 'apd_render_review_form', [ $this, 'render_form_for_listing' ], 10, 1 );

		// Add review data to frontend script localization.
		add_filter( 'apd_frontend_script_data', [ $this, 'add_script_data' ] );

		/**
		 * Fires after the review form is initialized.
		 *
		 * @since 1.0.0
		 *
		 * @param ReviewForm $form The ReviewForm instance.
		 */
		do_action( 'apd_review_form_init', $this );
	}

	/**
	 * Render the review form for a listing (callback for action hook).
	 *
	 * @since 1.0.0
	 *
	 * @param int $listing_id Listing post ID.
	 * @return void
	 */
	public function render_form_for_listing( int $listing_id ): void {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped in render method.
		echo $this->render( $listing_id );
	}

	/**
	 * Render the complete review section including form and existing reviews.
	 *
	 * @since 1.0.0
	 *
	 * @param int $listing_id Listing post ID.
	 * @return void
	 */
	public function render_review_section( int $listing_id ): void {
		/**
		 * Fires before the reviews section.
		 *
		 * @since 1.0.0
		 *
		 * @param int $listing_id The listing post ID.
		 */
		do_action( 'apd_before_reviews_section', $listing_id );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped in render method.
		echo $this->render( $listing_id );

		/**
		 * Fires after the reviews section.
		 *
		 * @since 1.0.0
		 *
		 * @param int $listing_id The listing post ID.
		 */
		do_action( 'apd_after_reviews_section', $listing_id );
	}

	/**
	 * Render the review form.
	 *
	 * @since 1.0.0
	 *
	 * @param int $listing_id Listing post ID.
	 * @return string HTML output.
	 */
	public function render( int $listing_id ): string {
		// Check if reviews are enabled for this listing.
		if ( ! $this->can_show_form( $listing_id ) ) {
			return '';
		}

		$user_id        = get_current_user_id();
		$is_logged_in   = $user_id > 0;
		$user_review    = $this->get_user_review( $listing_id );
		$is_edit_mode   = $user_review !== null;
		$manager        = ReviewManager::get_instance();
		$requires_login = $manager->requires_login();

		// Build template data.
		$template_data = [
			'listing_id'         => $listing_id,
			'user_id'            => $user_id,
			'is_logged_in'       => $is_logged_in,
			'requires_login'     => $requires_login,
			'user_review'        => $user_review,
			'is_edit_mode'       => $is_edit_mode,
			'config'             => $this->config,
			'nonce_action'       => self::NONCE_ACTION,
			'nonce_name'         => self::NONCE_NAME,
			'star_count'         => RatingCalculator::get_instance()->get_star_count(),
			'min_content_length' => $manager->get_min_content_length(),
			'form_classes'       => $this->get_form_classes( $is_edit_mode ),
			'guidelines_text'    => $this->get_guidelines_text(),
		];

		// If logged in, get user info.
		if ( $is_logged_in ) {
			$user                        = get_userdata( $user_id );
			$template_data['user_name']  = $user ? $user->display_name : '';
			$template_data['user_email'] = $user ? $user->user_email : '';
		}

		// Get existing rating/content for edit mode.
		if ( $is_edit_mode && $user_review ) {
			$template_data['existing_rating']  = $user_review['rating'];
			$template_data['existing_title']   = $user_review['title'];
			$template_data['existing_content'] = $user_review['content'];
			$template_data['review_id']        = $user_review['id'];
		}

		/**
		 * Filter the review form template data.
		 *
		 * @since 1.0.0
		 *
		 * @param array $template_data Template data.
		 * @param int   $listing_id    Listing post ID.
		 */
		$template_data = apply_filters( 'apd_review_form_data', $template_data, $listing_id );

		// Start output buffering.
		ob_start();

		// Load template.
		\apd_get_template( 'review/review-form.php', $template_data );

		return ob_get_clean();
	}

	/**
	 * Render the star input HTML.
	 *
	 * @since 1.0.0
	 *
	 * @param int $selected_rating Currently selected rating (0 if none).
	 * @param int $star_count      Number of stars.
	 * @return string HTML output.
	 */
	public function render_star_input( int $selected_rating = 0, int $star_count = 5 ): string {
		ob_start();

		\apd_get_template(
			'review/star-input.php',
			[
				'selected_rating' => $selected_rating,
				'star_count'      => $star_count,
			]
		);

		return ob_get_clean();
	}

	/**
	 * Get the current user's review for a listing.
	 *
	 * @since 1.0.0
	 *
	 * @param int $listing_id Listing post ID.
	 * @return array|null Review data or null if not found.
	 */
	public function get_user_review( int $listing_id ): ?array {
		$user_id = get_current_user_id();

		if ( $user_id <= 0 ) {
			return null;
		}

		return ReviewManager::get_instance()->get_user_review( $listing_id, $user_id );
	}

	/**
	 * Check if the form is in edit mode.
	 *
	 * @since 1.0.0
	 *
	 * @param int $listing_id Listing post ID.
	 * @return bool True if editing existing review.
	 */
	public function is_edit_mode( int $listing_id ): bool {
		return $this->get_user_review( $listing_id ) !== null;
	}

	/**
	 * Check if the form can be shown for this listing.
	 *
	 * @since 1.0.0
	 *
	 * @param int $listing_id Listing post ID.
	 * @return bool True if form should be shown.
	 */
	private function can_show_form( int $listing_id ): bool {
		// Check if post is a valid listing.
		$post = get_post( $listing_id );

		if ( ! $post || $post->post_type !== 'apd_listing' ) {
			return false;
		}

		// Check if listing is published.
		if ( $post->post_status !== 'publish' ) {
			return false;
		}

		// Check if user is the listing author (they shouldn't review their own listing).
		$user_id = get_current_user_id();
		if ( $user_id > 0 && (int) $post->post_author === $user_id ) {
			/**
			 * Filter whether listing authors can review their own listings.
			 *
			 * @since 1.0.0
			 *
			 * @param bool $can_review  Whether author can review. Default false.
			 * @param int  $listing_id  Listing post ID.
			 * @param int  $user_id     User ID.
			 */
			$author_can_review = apply_filters( 'apd_author_can_review_own_listing', false, $listing_id, $user_id );

			if ( ! $author_can_review ) {
				return false;
			}
		}

		/**
		 * Filter whether the review form can be shown.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $can_show   Whether form can be shown.
		 * @param int  $listing_id Listing post ID.
		 */
		return apply_filters( 'apd_can_show_review_form', true, $listing_id );
	}

	/**
	 * Get CSS classes for the form.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $is_edit_mode Whether in edit mode.
	 * @return string CSS classes.
	 */
	private function get_form_classes( bool $is_edit_mode ): string {
		$classes = [ 'apd-review-form' ];

		if ( $is_edit_mode ) {
			$classes[] = 'apd-review-form--edit-mode';
		}

		if ( $this->config['ajax_enabled'] ) {
			$classes[] = 'apd-review-form--ajax';
		}

		/**
		 * Filter the review form CSS classes.
		 *
		 * @since 1.0.0
		 *
		 * @param array $classes      CSS class names.
		 * @param bool  $is_edit_mode Whether in edit mode.
		 */
		$classes = apply_filters( 'apd_review_form_classes', $classes, $is_edit_mode );

		return implode( ' ', $classes );
	}

	/**
	 * Get the guidelines text.
	 *
	 * @since 1.0.0
	 *
	 * @return string Guidelines text.
	 */
	private function get_guidelines_text(): string {
		if ( ! empty( $this->config['guidelines_text'] ) ) {
			return $this->config['guidelines_text'];
		}

		$default_text = __( 'Please share your honest experience. Reviews help other users make informed decisions.', 'all-purpose-directory' );

		/**
		 * Filter the review guidelines text.
		 *
		 * @since 1.0.0
		 *
		 * @param string $text Guidelines text.
		 */
		return apply_filters( 'apd_review_guidelines_text', $default_text );
	}

	/**
	 * Add review-specific data to frontend scripts.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $data Script data.
	 * @return array<string, mixed> Modified script data.
	 */
	public function add_script_data( array $data ): array {
		$data['reviewNonce'] = wp_create_nonce( self::NONCE_ACTION );
		$data['i18n']        = array_merge(
			$data['i18n'] ?? [],
			[
				'reviewSubmitting' => __( 'Submitting review...', 'all-purpose-directory' ),
				'reviewSubmitted'  => __( 'Thank you for your review!', 'all-purpose-directory' ),
				'reviewUpdated'    => __( 'Your review has been updated.', 'all-purpose-directory' ),
				'reviewError'      => __( 'Failed to submit review. Please try again.', 'all-purpose-directory' ),
				'ratingRequired'   => __( 'Please select a rating.', 'all-purpose-directory' ),
				/* translators: %d: Minimum number of characters required for review */
				'reviewTooShort'   => __( 'Your review is too short. Please write at least %d characters.', 'all-purpose-directory' ),
				'reviewPending'    => __( 'Your review has been submitted and is pending approval.', 'all-purpose-directory' ),
				'selectRating'     => __( 'Select a rating', 'all-purpose-directory' ),
				/* translators: %d: Star rating number (singular) */
				'starLabel'        => __( '%d star', 'all-purpose-directory' ),
				/* translators: %d: Star rating number (plural) */
				'starsLabel'       => __( '%d stars', 'all-purpose-directory' ),
			]
		);

		return $data;
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
