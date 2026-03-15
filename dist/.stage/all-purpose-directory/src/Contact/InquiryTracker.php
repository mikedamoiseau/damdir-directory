<?php
/**
 * Inquiry Tracker class.
 *
 * Logs contact form inquiries for tracking and history.
 *
 * @package APD\Contact
 * @since 1.0.0
 */

declare(strict_types=1);

namespace APD\Contact;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * InquiryTracker class.
 */
class InquiryTracker {

	/**
	 * Single instance.
	 *
	 * @var InquiryTracker|null
	 */
	private static ?InquiryTracker $instance = null;

	/**
	 * Post type for inquiries.
	 */
	public const POST_TYPE = 'apd_inquiry';

	/**
	 * Meta keys.
	 */
	public const META_LISTING_ID   = '_apd_inquiry_listing_id';
	public const META_SENDER_NAME  = '_apd_inquiry_sender_name';
	public const META_SENDER_EMAIL = '_apd_inquiry_sender_email';
	public const META_SENDER_PHONE = '_apd_inquiry_sender_phone';
	public const META_SUBJECT      = '_apd_inquiry_subject';
	public const META_READ         = '_apd_inquiry_read';

	/**
	 * Listing meta key for inquiry count.
	 */
	public const LISTING_INQUIRY_COUNT = '_apd_inquiry_count';

	/**
	 * Get single instance.
	 *
	 * @return InquiryTracker
	 */
	public static function get_instance(): InquiryTracker {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {}

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
	 * Initialize hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		// Register post type.
		add_action( 'init', [ $this, 'register_post_type' ], 5 );

		// Hook into contact sent action to log inquiry.
		add_action( 'apd_contact_sent', [ $this, 'log_inquiry' ], 10, 3 );

		/**
		 * Fires after inquiry tracker initializes.
		 *
		 * @since 1.0.0
		 */
		do_action( 'apd_inquiry_tracker_init' );
	}

	/**
	 * Register the inquiry post type.
	 *
	 * @return void
	 */
	public function register_post_type(): void {
		$labels = [
			'name'               => _x( 'Inquiries', 'post type general name', 'all-purpose-directory' ),
			'singular_name'      => _x( 'Inquiry', 'post type singular name', 'all-purpose-directory' ),
			'menu_name'          => _x( 'Inquiries', 'admin menu', 'all-purpose-directory' ),
			'all_items'          => __( 'All Inquiries', 'all-purpose-directory' ),
			'view_item'          => __( 'View Inquiry', 'all-purpose-directory' ),
			'search_items'       => __( 'Search Inquiries', 'all-purpose-directory' ),
			'not_found'          => __( 'No inquiries found.', 'all-purpose-directory' ),
			'not_found_in_trash' => __( 'No inquiries found in Trash.', 'all-purpose-directory' ),
		];

		$args = [
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => false,
			'show_in_menu'       => false,
			'query_var'          => false,
			'rewrite'            => false,
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'supports'           => [ 'title', 'editor', 'author' ],
			'show_in_rest'       => false,
		];

		/**
		 * Filter inquiry post type arguments.
		 *
		 * @since 1.0.0
		 * @param array $args Post type arguments.
		 */
		$args = apply_filters( 'apd_inquiry_post_type_args', $args );

		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Log an inquiry from contact form submission.
	 *
	 * @param array    $data    Form data.
	 * @param \WP_Post $listing Listing post.
	 * @param \WP_User $owner   Listing owner.
	 * @return int|false Inquiry ID on success, false on failure.
	 */
	public function log_inquiry( array $data, \WP_Post $listing, \WP_User $owner ): int|false {
		/**
		 * Filter whether to track this inquiry.
		 *
		 * @since 1.0.0
		 * @param bool     $track   Whether to track the inquiry.
		 * @param array    $data    Form data.
		 * @param \WP_Post $listing Listing post.
		 */
		$track = apply_filters( 'apd_track_inquiry', true, $data, $listing );

		if ( ! $track ) {
			return false;
		}

		$inquiry_id = $this->save_inquiry(
			[
				'listing_id'   => $listing->ID,
				'sender_name'  => $data['contact_name'] ?? '',
				'sender_email' => $data['contact_email'] ?? '',
				'sender_phone' => $data['contact_phone'] ?? '',
				'subject'      => $data['contact_subject'] ?? '',
				'message'      => $data['contact_message'] ?? '',
			]
		);

		if ( $inquiry_id ) {
			// Update listing inquiry count.
			$this->increment_listing_count( $listing->ID );

			/**
			 * Fires after an inquiry is logged.
			 *
			 * @since 1.0.0
			 * @param int      $inquiry_id Inquiry post ID.
			 * @param array    $data       Form data.
			 * @param \WP_Post $listing    Listing post.
			 */
			do_action( 'apd_inquiry_logged', $inquiry_id, $data, $listing );
		}

		return $inquiry_id;
	}

	/**
	 * Save an inquiry to the database.
	 *
	 * @param array $data Inquiry data.
	 * @return int|false Inquiry ID on success, false on failure.
	 */
	public function save_inquiry( array $data ): int|false {
		$listing_id = (int) ( $data['listing_id'] ?? 0 );
		if ( $listing_id <= 0 ) {
			return false;
		}

		// Get listing for title.
		$listing = get_post( $listing_id );
		if ( ! $listing ) {
			return false;
		}

		$sender_name  = sanitize_text_field( $data['sender_name'] ?? '' );
		$sender_email = sanitize_email( $data['sender_email'] ?? '' );

		// Create inquiry post.
		$inquiry_data = [
			'post_type'    => self::POST_TYPE,
			'post_status'  => 'publish',
			'post_title'   => sprintf(
				/* translators: 1: sender name, 2: listing title */
				__( 'Inquiry from %1$s about %2$s', 'all-purpose-directory' ),
				$sender_name,
				$listing->post_title
			),
			'post_content' => sanitize_textarea_field( $data['message'] ?? '' ),
			'post_author'  => $listing->post_author,
		];

		/**
		 * Filter inquiry post data before saving.
		 *
		 * @since 1.0.0
		 * @param array $inquiry_data Post data.
		 * @param array $data         Original inquiry data.
		 */
		$inquiry_data = apply_filters( 'apd_inquiry_post_data', $inquiry_data, $data );

		$inquiry_id = wp_insert_post( $inquiry_data, true );

		if ( is_wp_error( $inquiry_id ) ) {
			return false;
		}

		// Save meta data.
		update_post_meta( $inquiry_id, self::META_LISTING_ID, $listing_id );
		update_post_meta( $inquiry_id, self::META_SENDER_NAME, $sender_name );
		update_post_meta( $inquiry_id, self::META_SENDER_EMAIL, $sender_email );
		update_post_meta( $inquiry_id, self::META_SENDER_PHONE, sanitize_text_field( $data['sender_phone'] ?? '' ) );
		update_post_meta( $inquiry_id, self::META_SUBJECT, sanitize_text_field( $data['subject'] ?? '' ) );
		update_post_meta( $inquiry_id, self::META_READ, 0 );

		return $inquiry_id;
	}

	/**
	 * Get a single inquiry.
	 *
	 * @param int $inquiry_id Inquiry ID.
	 * @return array|null Inquiry data or null if not found.
	 */
	public function get_inquiry( int $inquiry_id ): ?array {
		$post = get_post( $inquiry_id );

		if ( ! $post || self::POST_TYPE !== $post->post_type ) {
			return null;
		}

		return $this->format_inquiry( $post );
	}

	/**
	 * Get inquiries for a listing.
	 *
	 * @param int   $listing_id Listing ID.
	 * @param array $args       Query arguments.
	 * @return array Array of inquiry data.
	 */
	public function get_listing_inquiries( int $listing_id, array $args = [] ): array {
		$defaults = [
			'number'  => 20,
			'offset'  => 0,
			'orderby' => 'date',
			'order'   => 'DESC',
			'status'  => 'all', // all, read, unread.
		];

		$args = wp_parse_args( $args, $defaults );

		$query_args = [
			'post_type'      => self::POST_TYPE,
			'posts_per_page' => $args['number'],
			'offset'         => $args['offset'],
			'orderby'        => $args['orderby'],
			'order'          => $args['order'],
			'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Inquiry lookup by listing ID requires meta query.
				[
					'key'   => self::META_LISTING_ID,
					'value' => $listing_id,
					'type'  => 'NUMERIC',
				],
			],
		];

		// Filter by read status.
		if ( 'read' === $args['status'] ) {
			$query_args['meta_query'][] = [
				'key'   => self::META_READ,
				'value' => 1,
				'type'  => 'NUMERIC',
			];
		} elseif ( 'unread' === $args['status'] ) {
			$query_args['meta_query'][] = [
				'key'     => self::META_READ,
				'value'   => 0,
				'type'    => 'NUMERIC',
				'compare' => '=',
			];
		}

		/**
		 * Filter listing inquiries query args.
		 *
		 * @since 1.0.0
		 * @param array $query_args WP_Query arguments.
		 * @param int   $listing_id Listing ID.
		 * @param array $args       Original arguments.
		 */
		$query_args = apply_filters( 'apd_listing_inquiries_query_args', $query_args, $listing_id, $args );

		$query = new \WP_Query( $query_args );

		$inquiries = [];
		foreach ( $query->posts as $post ) {
			$inquiries[] = $this->format_inquiry( $post );
		}

		return $inquiries;
	}

	/**
	 * Get inquiries for a user (all their listings).
	 *
	 * @param int   $user_id User ID.
	 * @param array $args    Query arguments.
	 * @return array Array of inquiry data.
	 */
	public function get_user_inquiries( int $user_id, array $args = [] ): array {
		$defaults = [
			'number'     => 20,
			'offset'     => 0,
			'orderby'    => 'date',
			'order'      => 'DESC',
			'status'     => 'all', // all, read, unread.
			'listing_id' => 0, // Filter by specific listing.
		];

		$args = wp_parse_args( $args, $defaults );

		$query_args = [
			'post_type'      => self::POST_TYPE,
			'posts_per_page' => $args['number'],
			'offset'         => $args['offset'],
			'orderby'        => $args['orderby'],
			'order'          => $args['order'],
			'author'         => $user_id,
			'meta_query'     => [], // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Inquiry filtering by read status requires meta query.
		];

		// Filter by specific listing.
		if ( $args['listing_id'] > 0 ) {
			$query_args['meta_query'][] = [
				'key'   => self::META_LISTING_ID,
				'value' => $args['listing_id'],
				'type'  => 'NUMERIC',
			];
		}

		// Filter by read status.
		if ( 'read' === $args['status'] ) {
			$query_args['meta_query'][] = [
				'key'   => self::META_READ,
				'value' => 1,
				'type'  => 'NUMERIC',
			];
		} elseif ( 'unread' === $args['status'] ) {
			$query_args['meta_query'][] = [
				'key'     => self::META_READ,
				'value'   => 0,
				'type'    => 'NUMERIC',
				'compare' => '=',
			];
		}

		/**
		 * Filter user inquiries query args.
		 *
		 * @since 1.0.0
		 * @param array $query_args WP_Query arguments.
		 * @param int   $user_id    User ID.
		 * @param array $args       Original arguments.
		 */
		$query_args = apply_filters( 'apd_user_inquiries_query_args', $query_args, $user_id, $args );

		$query = new \WP_Query( $query_args );

		$inquiries = [];
		foreach ( $query->posts as $post ) {
			$inquiries[] = $this->format_inquiry( $post );
		}

		return $inquiries;
	}

	/**
	 * Count inquiries for a user.
	 *
	 * @param int    $user_id User ID.
	 * @param string $status  Status filter (all, read, unread).
	 * @return int Inquiry count.
	 */
	public function count_user_inquiries( int $user_id, string $status = 'all' ): int {
		$query_args = [
			'post_type'      => self::POST_TYPE,
			'posts_per_page' => 1,
			'author'         => $user_id,
			'fields'         => 'ids',
			'no_found_rows'  => false,
			'meta_query'     => [], // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Inquiry count by read status requires meta query.
		];

		if ( 'read' === $status ) {
			$query_args['meta_query'][] = [
				'key'   => self::META_READ,
				'value' => 1,
				'type'  => 'NUMERIC',
			];
		} elseif ( 'unread' === $status ) {
			$query_args['meta_query'][] = [
				'key'     => self::META_READ,
				'value'   => 0,
				'type'    => 'NUMERIC',
				'compare' => '=',
			];
		}

		$query = new \WP_Query( $query_args );

		return $query->found_posts;
	}

	/**
	 * Format inquiry post to data array.
	 *
	 * @param \WP_Post $post Inquiry post.
	 * @return array Formatted inquiry data.
	 */
	public function format_inquiry( \WP_Post $post ): array {
		$listing_id = (int) get_post_meta( $post->ID, self::META_LISTING_ID, true );
		$listing    = get_post( $listing_id );

		return [
			'id'             => $post->ID,
			'listing_id'     => $listing_id,
			'listing_title'  => $listing ? $listing->post_title : '',
			'listing_url'    => $listing ? get_permalink( $listing_id ) : '',
			'sender_name'    => get_post_meta( $post->ID, self::META_SENDER_NAME, true ),
			'sender_email'   => get_post_meta( $post->ID, self::META_SENDER_EMAIL, true ),
			'sender_phone'   => get_post_meta( $post->ID, self::META_SENDER_PHONE, true ),
			'subject'        => get_post_meta( $post->ID, self::META_SUBJECT, true ),
			'message'        => $post->post_content,
			'is_read'        => (bool) get_post_meta( $post->ID, self::META_READ, true ),
			'date'           => $post->post_date,
			'date_formatted' => date_i18n( get_option( 'date_format' ), strtotime( $post->post_date ) ),
		];
	}

	/**
	 * Mark an inquiry as read.
	 *
	 * @param int $inquiry_id Inquiry ID.
	 * @return bool True on success.
	 */
	public function mark_as_read( int $inquiry_id ): bool {
		$post = get_post( $inquiry_id );
		if ( ! $post || self::POST_TYPE !== $post->post_type ) {
			return false;
		}

		update_post_meta( $inquiry_id, self::META_READ, 1 );

		/**
		 * Fires when an inquiry is marked as read.
		 *
		 * @since 1.0.0
		 * @param int $inquiry_id Inquiry ID.
		 */
		do_action( 'apd_inquiry_marked_read', $inquiry_id );

		return true;
	}

	/**
	 * Mark an inquiry as unread.
	 *
	 * @param int $inquiry_id Inquiry ID.
	 * @return bool True on success.
	 */
	public function mark_as_unread( int $inquiry_id ): bool {
		$post = get_post( $inquiry_id );
		if ( ! $post || self::POST_TYPE !== $post->post_type ) {
			return false;
		}

		update_post_meta( $inquiry_id, self::META_READ, 0 );

		/**
		 * Fires when an inquiry is marked as unread.
		 *
		 * @since 1.0.0
		 * @param int $inquiry_id Inquiry ID.
		 */
		do_action( 'apd_inquiry_marked_unread', $inquiry_id );

		return true;
	}

	/**
	 * Delete an inquiry.
	 *
	 * @param int  $inquiry_id   Inquiry ID.
	 * @param bool $force_delete Whether to bypass trash.
	 * @return bool True on success.
	 */
	public function delete_inquiry( int $inquiry_id, bool $force_delete = false ): bool {
		$post = get_post( $inquiry_id );
		if ( ! $post || self::POST_TYPE !== $post->post_type ) {
			return false;
		}

		$listing_id = (int) get_post_meta( $inquiry_id, self::META_LISTING_ID, true );

		/**
		 * Fires before an inquiry is deleted.
		 *
		 * @since 1.0.0
		 * @param int  $inquiry_id   Inquiry ID.
		 * @param bool $force_delete Whether deletion bypasses trash.
		 */
		do_action( 'apd_before_inquiry_delete', $inquiry_id, $force_delete );

		$result = wp_delete_post( $inquiry_id, $force_delete );

		if ( $result && $listing_id > 0 ) {
			$this->decrement_listing_count( $listing_id );
		}

		return (bool) $result;
	}

	/**
	 * Get inquiry count for a listing.
	 *
	 * @param int    $listing_id Listing ID.
	 * @param string $status     Status filter (all, read, unread).
	 * @return int Inquiry count.
	 */
	public function get_listing_inquiry_count( int $listing_id, string $status = 'all' ): int {
		if ( 'all' === $status ) {
			$count = get_post_meta( $listing_id, self::LISTING_INQUIRY_COUNT, true );
			return (int) $count;
		}

		$query_args = [
			'post_type'      => self::POST_TYPE,
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Inquiry count by listing ID requires meta query.
				[
					'key'   => self::META_LISTING_ID,
					'value' => $listing_id,
					'type'  => 'NUMERIC',
				],
			],
		];

		if ( 'read' === $status ) {
			$query_args['meta_query'][] = [
				'key'   => self::META_READ,
				'value' => 1,
				'type'  => 'NUMERIC',
			];
		} elseif ( 'unread' === $status ) {
			$query_args['meta_query'][] = [
				'key'     => self::META_READ,
				'value'   => 0,
				'type'    => 'NUMERIC',
				'compare' => '=',
			];
		}

		$query = new \WP_Query( $query_args );

		return (int) $query->found_posts;
	}

	/**
	 * Increment listing inquiry count.
	 *
	 * @param int $listing_id Listing ID.
	 * @return int New count.
	 */
	public function increment_listing_count( int $listing_id ): int {
		$count = $this->get_listing_inquiry_count( $listing_id );
		++$count;
		update_post_meta( $listing_id, self::LISTING_INQUIRY_COUNT, $count );
		return $count;
	}

	/**
	 * Decrement listing inquiry count.
	 *
	 * @param int $listing_id Listing ID.
	 * @return int New count.
	 */
	public function decrement_listing_count( int $listing_id ): int {
		$count = $this->get_listing_inquiry_count( $listing_id );
		$count = max( 0, $count - 1 );
		update_post_meta( $listing_id, self::LISTING_INQUIRY_COUNT, $count );
		return $count;
	}

	/**
	 * Recalculate listing inquiry count from database.
	 *
	 * @param int $listing_id Listing ID.
	 * @return int Recalculated count.
	 */
	public function recalculate_listing_count( int $listing_id ): int {
		$query = new \WP_Query(
			[
				'post_type'      => self::POST_TYPE,
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => false,
				'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Recalculate listing inquiry count requires meta query.
					[
						'key'   => self::META_LISTING_ID,
						'value' => $listing_id,
						'type'  => 'NUMERIC',
					],
				],
			]
		);

		$count = $query->found_posts;
		update_post_meta( $listing_id, self::LISTING_INQUIRY_COUNT, $count );

		return $count;
	}

	/**
	 * Check if a user can view an inquiry.
	 *
	 * @param int $inquiry_id Inquiry ID.
	 * @param int $user_id    User ID.
	 * @return bool True if user can view.
	 */
	public function can_user_view( int $inquiry_id, int $user_id ): bool {
		$post = get_post( $inquiry_id );
		if ( ! $post || self::POST_TYPE !== $post->post_type ) {
			return false;
		}

		// Admin can view all.
		if ( user_can( $user_id, 'manage_options' ) ) {
			return true;
		}

		// Inquiry author (listing owner) can view.
		if ( (int) $post->post_author === $user_id ) {
			return true;
		}

		/**
		 * Filter whether a user can view an inquiry.
		 *
		 * @since 1.0.0
		 * @param bool $can_view   Whether user can view.
		 * @param int  $inquiry_id Inquiry ID.
		 * @param int  $user_id    User ID.
		 */
		return apply_filters( 'apd_user_can_view_inquiry', false, $inquiry_id, $user_id );
	}
}
