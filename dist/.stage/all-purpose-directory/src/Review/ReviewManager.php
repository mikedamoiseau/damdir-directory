<?php
/**
 * Review Manager Class.
 *
 * Handles reviews for listings using WordPress comments with custom meta.
 * Reviews are stored as comments with comment_type 'apd_review'.
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
 * Class ReviewManager
 *
 * @since 1.0.0
 */
class ReviewManager {

	/**
	 * Comment type for reviews.
	 *
	 * @var string
	 */
	public const COMMENT_TYPE = 'apd_review';

	/**
	 * Meta key for rating.
	 *
	 * @var string
	 */
	public const META_RATING = '_apd_rating';

	/**
	 * Meta key for review title.
	 *
	 * @var string
	 */
	public const META_TITLE = '_apd_review_title';

	/**
	 * Minimum rating value.
	 *
	 * @var int
	 */
	public const MIN_RATING = 1;

	/**
	 * Maximum rating value.
	 *
	 * @var int
	 */
	public const MAX_RATING = 5;

	/**
	 * Default minimum content length.
	 *
	 * @var int
	 */
	public const DEFAULT_MIN_CONTENT_LENGTH = 10;

	/**
	 * Singleton instance.
	 *
	 * @var ReviewManager|null
	 */
	private static ?ReviewManager $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @since 1.0.0
	 *
	 * @return ReviewManager
	 */
	public static function get_instance(): ReviewManager {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {}

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
	 * Initialize the review system.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init(): void {
		// Filter to exclude reviews from regular comment queries (always active).
		add_filter( 'comments_clauses', [ $this, 'exclude_reviews_from_comments' ], 10, 2 );

		/**
		 * Fires after the review system is initialized.
		 *
		 * @since 1.0.0
		 *
		 * @param ReviewManager $manager The ReviewManager instance.
		 */
		do_action( 'apd_reviews_init', $this );
	}

	/**
	 * Enable comments on listing post type.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function enable_listing_comments(): void {
		add_post_type_support( 'apd_listing', 'comments' );
	}

	/**
	 * Exclude reviews from regular comment queries.
	 *
	 * @since 1.0.0
	 *
	 * @param array             $clauses    Comment query clauses.
	 * @param \WP_Comment_Query $query      Comment query object.
	 * @return array Modified clauses.
	 */
	public function exclude_reviews_from_comments( array $clauses, \WP_Comment_Query $query ): array {
		global $wpdb;

		// Check if we're explicitly querying for reviews.
		$comment_type = $query->query_vars['type'] ?? '';

		// If querying specifically for reviews, don't exclude them.
		if ( $comment_type === self::COMMENT_TYPE ) {
			return $clauses;
		}

		// If querying for all types, don't modify.
		if ( $comment_type === 'all' || ( is_array( $comment_type ) && in_array( self::COMMENT_TYPE, $comment_type, true ) ) ) {
			return $clauses;
		}

		// Exclude reviews from regular comment queries.
		$clauses['where'] .= $wpdb->prepare( ' AND comment_type != %s', self::COMMENT_TYPE );

		return $clauses;
	}

	/**
	 * Create a new review.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $listing_id Listing post ID.
	 * @param array $data       Review data.
	 *                          - rating: (int) Required. Rating 1-5.
	 *                          - content: (string) Required. Review text.
	 *                          - title: (string) Optional. Review title.
	 *                          - author_name: (string) Guest name (if not logged in).
	 *                          - author_email: (string) Guest email (if not logged in).
	 *                          - user_id: (int) Override user ID.
	 * @return int|\WP_Error Review ID on success, WP_Error on failure.
	 */
	public function create( int $listing_id, array $data ): int|\WP_Error {
		// Validate the listing.
		$listing = get_post( $listing_id );

		if ( ! $listing || $listing->post_type !== 'apd_listing' ) {
			return new \WP_Error(
				'invalid_listing',
				__( 'Invalid listing ID.', 'all-purpose-directory' )
			);
		}

		// Validate review data.
		$validation = $this->validate_review_data( $data, $listing_id );

		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		// Determine user.
		$user_id = $data['user_id'] ?? get_current_user_id();

		// Check if user has already reviewed this listing.
		if ( $user_id > 0 && $this->has_user_reviewed( $listing_id, $user_id ) ) {
			return new \WP_Error(
				'already_reviewed',
				__( 'You have already reviewed this listing.', 'all-purpose-directory' )
			);
		}

		// Prepare comment data.
		$comment_data = [
			'comment_post_ID'  => $listing_id,
			'comment_content'  => wp_kses_post( $data['content'] ),
			'comment_type'     => self::COMMENT_TYPE,
			'comment_approved' => $this->get_default_status(),
			'user_id'          => $user_id,
		];

		// Handle guest reviews.
		if ( $user_id <= 0 ) {
			$comment_data['comment_author']       = sanitize_text_field( $data['author_name'] ?? '' );
			$comment_data['comment_author_email'] = sanitize_email( $data['author_email'] ?? '' );
			$comment_data['comment_author_url']   = '';
		} else {
			$user = get_userdata( $user_id );
			if ( $user ) {
				$comment_data['comment_author']       = $user->display_name;
				$comment_data['comment_author_email'] = $user->user_email;
				$comment_data['comment_author_url']   = $user->user_url;
			}
		}

		/**
		 * Filter review data before saving.
		 *
		 * @since 1.0.0
		 *
		 * @param array $comment_data Comment data to insert.
		 * @param int   $listing_id   Listing post ID.
		 * @param array $data         Original review data.
		 */
		$comment_data = apply_filters( 'apd_review_data', $comment_data, $listing_id, $data );

		/**
		 * Fires before a review is created.
		 *
		 * @since 1.0.0
		 *
		 * @param array $comment_data Comment data to insert.
		 * @param int   $listing_id   Listing post ID.
		 */
		do_action( 'apd_before_review_create', $comment_data, $listing_id );

		// Insert comment.
		$comment_id = wp_insert_comment( $comment_data );

		if ( ! $comment_id ) {
			return new \WP_Error(
				'review_failed',
				__( 'Failed to create review.', 'all-purpose-directory' )
			);
		}

		// Save review meta.
		$rating = absint( $data['rating'] );
		update_comment_meta( $comment_id, self::META_RATING, $rating );

		if ( ! empty( $data['title'] ) ) {
			update_comment_meta( $comment_id, self::META_TITLE, sanitize_text_field( $data['title'] ) );
		}

		/**
		 * Fires after a review is created.
		 *
		 * @since 1.0.0
		 *
		 * @param int   $comment_id Review (comment) ID.
		 * @param int   $listing_id Listing post ID.
		 * @param array $data       Original review data.
		 */
		do_action( 'apd_review_created', $comment_id, $listing_id, $data );

		return $comment_id;
	}

	/**
	 * Update an existing review.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $review_id Review (comment) ID.
	 * @param array $data      Review data to update.
	 *                         - rating: (int) Rating 1-5.
	 *                         - content: (string) Review text.
	 *                         - title: (string) Review title.
	 * @return bool|\WP_Error True on success, WP_Error on failure.
	 */
	public function update( int $review_id, array $data ): bool|\WP_Error {
		$review = $this->get( $review_id );

		if ( ! $review ) {
			return new \WP_Error(
				'invalid_review',
				__( 'Invalid review ID.', 'all-purpose-directory' )
			);
		}

		// Validate rating if provided.
		if ( isset( $data['rating'] ) ) {
			$rating = absint( $data['rating'] );
			if ( $rating < self::MIN_RATING || $rating > self::MAX_RATING ) {
				return new \WP_Error(
					'invalid_rating',
					sprintf(
						/* translators: 1: minimum rating, 2: maximum rating */
						__( 'Rating must be between %1$d and %2$d.', 'all-purpose-directory' ),
						self::MIN_RATING,
						self::MAX_RATING
					)
				);
			}
		}

		// Validate content if provided.
		if ( isset( $data['content'] ) ) {
			$min_length = $this->get_min_content_length();
			$content    = trim( $data['content'] );

			if ( \apd_strlen( $content ) < $min_length ) {
				return new \WP_Error(
					'content_too_short',
					sprintf(
						/* translators: %d: minimum content length */
						__( 'Review content must be at least %d characters.', 'all-purpose-directory' ),
						$min_length
					)
				);
			}
		}

		$comment_data = [
			'comment_ID' => $review_id,
		];

		if ( isset( $data['content'] ) ) {
			$comment_data['comment_content'] = wp_kses_post( $data['content'] );
		}

		/**
		 * Fires before a review is updated.
		 *
		 * @since 1.0.0
		 *
		 * @param int   $review_id Review (comment) ID.
		 * @param array $data      Review data.
		 */
		do_action( 'apd_before_review_update', $review_id, $data );

		// Update comment.
		if ( count( $comment_data ) > 1 ) {
			$result = wp_update_comment( $comment_data );

			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		// Update meta.
		if ( isset( $data['rating'] ) ) {
			update_comment_meta( $review_id, self::META_RATING, absint( $data['rating'] ) );
		}

		if ( isset( $data['title'] ) ) {
			if ( empty( $data['title'] ) ) {
				delete_comment_meta( $review_id, self::META_TITLE );
			} else {
				update_comment_meta( $review_id, self::META_TITLE, sanitize_text_field( $data['title'] ) );
			}
		}

		/**
		 * Fires after a review is updated.
		 *
		 * @since 1.0.0
		 *
		 * @param int   $review_id Review (comment) ID.
		 * @param array $data      Review data.
		 */
		do_action( 'apd_review_updated', $review_id, $data );

		return true;
	}

	/**
	 * Delete a review.
	 *
	 * @since 1.0.0
	 *
	 * @param int  $review_id  Review (comment) ID.
	 * @param bool $force_delete Whether to permanently delete. Default false (move to trash).
	 * @return bool True on success, false on failure.
	 */
	public function delete( int $review_id, bool $force_delete = false ): bool {
		$review = $this->get( $review_id );

		if ( ! $review ) {
			return false;
		}

		/**
		 * Fires before a review is deleted.
		 *
		 * @since 1.0.0
		 *
		 * @param int  $review_id    Review (comment) ID.
		 * @param bool $force_delete Whether this is a permanent delete.
		 */
		do_action( 'apd_before_review_delete', $review_id, $force_delete );

		$result = wp_delete_comment( $review_id, $force_delete );

		if ( $result ) {
			/**
			 * Fires after a review is deleted.
			 *
			 * @since 1.0.0
			 *
			 * @param int  $review_id    Review (comment) ID.
			 * @param bool $force_delete Whether this was a permanent delete.
			 */
			do_action( 'apd_review_deleted', $review_id, $force_delete );
		}

		return $result;
	}

	/**
	 * Get a single review with meta.
	 *
	 * @since 1.0.0
	 *
	 * @param int $review_id Review (comment) ID.
	 * @return array|null Review data array or null if not found.
	 */
	public function get( int $review_id ): ?array {
		$comment = get_comment( $review_id );

		if ( ! $comment || $comment->comment_type !== self::COMMENT_TYPE ) {
			return null;
		}

		return $this->format_review( $comment );
	}

	/**
	 * Get reviews for a listing.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $listing_id Listing post ID.
	 * @param array $args       Query arguments.
	 *                          - status: (string) Review status (approved, pending, all). Default 'approved'.
	 *                          - orderby: (string) Order by field (date, rating). Default 'date'.
	 *                          - order: (string) Order direction (ASC, DESC). Default 'DESC'.
	 *                          - number: (int) Number of reviews to retrieve. Default 10.
	 *                          - offset: (int) Number of reviews to skip. Default 0.
	 *                          - author: (int) Filter by review author user ID.
	 *                          - user_id: (int) Alias for author filter.
	 *                          - rating: (int) Filter by specific rating.
	 * @return array{reviews: array[], total: int, pages: int} Reviews, total count, and page count.
	 */
	public function get_listing_reviews( int $listing_id, array $args = [] ): array {
		$defaults = [
			'status'  => 'approved',
			'orderby' => 'date',
			'order'   => 'DESC',
			'number'  => 10,
			'offset'  => 0,
		];

		$args = wp_parse_args( $args, $defaults );

		$query_args = [
			'post_id' => $listing_id,
			'type'    => self::COMMENT_TYPE,
			'number'  => $args['number'],
			'offset'  => $args['offset'],
			'order'   => strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC',
		];

		// Handle status.
		$query_args['status'] = $this->translate_status( $args['status'] );

		// Handle author filter.
		$author_id = 0;
		if ( isset( $args['author'] ) ) {
			$author_id = absint( $args['author'] );
		} elseif ( isset( $args['user_id'] ) ) {
			$author_id = absint( $args['user_id'] );
		}

		if ( $author_id > 0 ) {
			$query_args['user_id'] = $author_id;
		}

		// Handle orderby.
		if ( $args['orderby'] === 'rating' ) {
			$query_args['meta_key'] = self::META_RATING; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Required for rating-based sorting.
			$query_args['orderby']  = 'meta_value_num';
		} else {
			$query_args['orderby'] = 'comment_date';
		}

		// Handle rating filter.
		if ( isset( $args['rating'] ) ) {
			$query_args['meta_query'] = [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Rating filter requires meta query.
				[
					'key'     => self::META_RATING,
					'value'   => absint( $args['rating'] ),
					'compare' => '=',
					'type'    => 'NUMERIC',
				],
			];
		}

		$comments = get_comments( $query_args );

		// Get total count.
		$count_args           = $query_args;
		$count_args['count']  = true;
		$count_args['number'] = 0;
		$count_args['offset'] = 0;
		$total                = get_comments( $count_args );

		// Format reviews.
		$reviews = array_map( [ $this, 'format_review' ], $comments );

		// Calculate pages.
		$pages = $args['number'] > 0 ? (int) ceil( $total / $args['number'] ) : 1;

		return [
			'reviews' => $reviews,
			'total'   => (int) $total,
			'pages'   => $pages,
		];
	}

	/**
	 * Get a user's review for a specific listing.
	 *
	 * @since 1.0.0
	 *
	 * @param int $listing_id Listing post ID.
	 * @param int $user_id    User ID.
	 * @return array|null Review data or null if not found.
	 */
	public function get_user_review( int $listing_id, int $user_id ): ?array {
		if ( $user_id <= 0 ) {
			return null;
		}

		$comments = get_comments(
			[
				'post_id' => $listing_id,
				'user_id' => $user_id,
				'type'    => self::COMMENT_TYPE,
				'number'  => 1,
				'status'  => 'all',
			]
		);

		if ( empty( $comments ) ) {
			return null;
		}

		return $this->format_review( $comments[0] );
	}

	/**
	 * Check if a user has reviewed a listing.
	 *
	 * @since 1.0.0
	 *
	 * @param int $listing_id Listing post ID.
	 * @param int $user_id    User ID.
	 * @return bool True if user has reviewed the listing.
	 */
	public function has_user_reviewed( int $listing_id, int $user_id ): bool {
		if ( $user_id <= 0 ) {
			return false;
		}

		$count = get_comments(
			[
				'post_id' => $listing_id,
				'user_id' => $user_id,
				'type'    => self::COMMENT_TYPE,
				'count'   => true,
				'status'  => 'all',
			]
		);

		return $count > 0;
	}

	/**
	 * Get the review count for a listing.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $listing_id Listing post ID.
	 * @param string $status     Review status (approved, pending, all). Default 'approved'.
	 * @return int Review count.
	 */
	public function get_review_count( int $listing_id, string $status = 'approved' ): int {
		$count = get_comments(
			[
				'post_id' => $listing_id,
				'type'    => self::COMMENT_TYPE,
				'status'  => $this->translate_status( $status ),
				'count'   => true,
			]
		);

		return (int) $count;
	}

	/**
	 * Approve a pending review.
	 *
	 * @since 1.0.0
	 *
	 * @param int $review_id Review (comment) ID.
	 * @return bool True on success, false on failure.
	 */
	public function approve( int $review_id ): bool {
		$review = $this->get( $review_id );

		if ( ! $review ) {
			return false;
		}

		$result = wp_set_comment_status( $review_id, 'approve' );

		if ( $result ) {
			/**
			 * Fires after a review is approved.
			 *
			 * @since 1.0.0
			 *
			 * @param int $review_id Review (comment) ID.
			 */
			do_action( 'apd_review_approved', $review_id );
		}

		return (bool) $result;
	}

	/**
	 * Reject (trash) a review.
	 *
	 * @since 1.0.0
	 *
	 * @param int $review_id Review (comment) ID.
	 * @return bool True on success, false on failure.
	 */
	public function reject( int $review_id ): bool {
		$review = $this->get( $review_id );

		if ( ! $review ) {
			return false;
		}

		$result = wp_set_comment_status( $review_id, 'trash' );

		if ( $result ) {
			/**
			 * Fires after a review is rejected (trashed).
			 *
			 * @since 1.0.0
			 *
			 * @param int $review_id Review (comment) ID.
			 */
			do_action( 'apd_review_rejected', $review_id );
		}

		return (bool) $result;
	}

	/**
	 * Check if login is required to submit reviews.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if login is required.
	 */
	public function requires_login(): bool {
		$require_login = true;

		/**
		 * Filter whether login is required for reviews.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $require_login Whether login is required.
		 */
		return apply_filters( 'apd_reviews_require_login', $require_login );
	}

	/**
	 * Get the minimum content length for reviews.
	 *
	 * @since 1.0.0
	 *
	 * @return int Minimum content length.
	 */
	public function get_min_content_length(): int {
		/**
		 * Filter the minimum content length for reviews.
		 *
		 * @since 1.0.0
		 *
		 * @param int $min_length Minimum content length.
		 */
		return apply_filters( 'apd_review_min_content_length', self::DEFAULT_MIN_CONTENT_LENGTH );
	}

	/**
	 * Validate review data.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data       Review data.
	 * @param int   $listing_id Listing post ID.
	 * @return bool|\WP_Error True if valid, WP_Error on failure.
	 */
	private function validate_review_data( array $data, int $listing_id ): bool|\WP_Error {
		$errors       = new \WP_Error();
		$user_id      = $data['user_id'] ?? get_current_user_id();
		$is_logged_in = $user_id > 0;

		// Check login requirement.
		if ( ! $is_logged_in && $this->requires_login() ) {
			$errors->add(
				'login_required',
				__( 'You must be logged in to submit a review.', 'all-purpose-directory' )
			);
		}

		// Validate rating.
		if ( ! isset( $data['rating'] ) ) {
			$errors->add( 'rating_required', __( 'Rating is required.', 'all-purpose-directory' ) );
		} else {
			$rating = absint( $data['rating'] );
			if ( $rating < self::MIN_RATING || $rating > self::MAX_RATING ) {
				$errors->add(
					'invalid_rating',
					sprintf(
						/* translators: 1: minimum rating, 2: maximum rating */
						__( 'Rating must be between %1$d and %2$d.', 'all-purpose-directory' ),
						self::MIN_RATING,
						self::MAX_RATING
					)
				);
			}
		}

		// Validate content.
		if ( empty( $data['content'] ) ) {
			$errors->add( 'content_required', __( 'Review content is required.', 'all-purpose-directory' ) );
		} else {
			$min_length = $this->get_min_content_length();
			$content    = trim( $data['content'] );

			if ( \apd_strlen( $content ) < $min_length ) {
				$errors->add(
					'content_too_short',
					sprintf(
						/* translators: %d: minimum content length */
						__( 'Review content must be at least %d characters.', 'all-purpose-directory' ),
						$min_length
					)
				);
			}
		}

		// Validate guest fields if not logged in and guest reviews allowed.
		if ( ! $is_logged_in && ! $this->requires_login() ) {
			if ( empty( $data['author_name'] ) ) {
				$errors->add( 'author_name_required', __( 'Your name is required.', 'all-purpose-directory' ) );
			}
			if ( empty( $data['author_email'] ) || ! is_email( $data['author_email'] ) ) {
				$errors->add( 'author_email_required', __( 'A valid email address is required.', 'all-purpose-directory' ) );
			}
		}

		if ( $errors->has_errors() ) {
			return $errors;
		}

		return true;
	}

	/**
	 * Format a comment object as a review array.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_Comment $comment Comment object.
	 * @return array Review data array.
	 */
	private function format_review( \WP_Comment $comment ): array {
		$rating = (int) get_comment_meta( $comment->comment_ID, self::META_RATING, true );
		$title  = get_comment_meta( $comment->comment_ID, self::META_TITLE, true );

		// Translate status.
		$status = 'pending';
		if ( $comment->comment_approved === '1' ) {
			$status = 'approved';
		} elseif ( $comment->comment_approved === 'spam' ) {
			$status = 'spam';
		} elseif ( $comment->comment_approved === 'trash' ) {
			$status = 'trash';
		}

		return [
			'id'             => (int) $comment->comment_ID,
			'listing_id'     => (int) $comment->comment_post_ID,
			'author_id'      => (int) $comment->user_id,
			'author_name'    => $comment->comment_author,
			'author_email'   => $comment->comment_author_email,
			'rating'         => $rating,
			'title'          => $title ?: '',
			'content'        => $comment->comment_content,
			'status'         => $status,
			'date'           => $comment->comment_date,
			'date_formatted' => date_i18n( get_option( 'date_format' ), strtotime( $comment->comment_date ) ),
		];
	}

	/**
	 * Translate status string to comment status.
	 *
	 * @since 1.0.0
	 *
	 * @param string $status Status string.
	 * @return string|array Comment status.
	 */
	private function translate_status( string $status ): string|array {
		return match ( $status ) {
			'approved' => 'approve',
			'pending'  => 'hold',
			'spam'     => 'spam',
			'trash'    => 'trash',
			'all'      => 'all',
			default    => 'approve',
		};
	}

	/**
	 * Get the default status for new reviews.
	 *
	 * @since 1.0.0
	 *
	 * @return string|int Comment status ('1' for approved, '0' for pending).
	 */
	private function get_default_status(): string|int {
		/**
		 * Filter the default status for new reviews.
		 *
		 * @since 1.0.0
		 *
		 * @param string $status Default status. 'approved' or 'pending'.
		 */
		$status = apply_filters( 'apd_review_default_status', 'pending' );

		return $status === 'approved' ? 1 : 0;
	}
}
