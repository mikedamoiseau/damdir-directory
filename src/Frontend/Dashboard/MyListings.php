<?php
/**
 * My Listings Dashboard Tab Controller.
 *
 * Handles the My Listings tab in the user dashboard, including
 * listing display, filtering, and action handling.
 *
 * @package APD\Frontend\Dashboard
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Frontend\Dashboard;

use APD\Core\Url;
use APD\Listing\PostType;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MyListings
 *
 * @since 1.0.0
 */
class MyListings {

	/**
	 * Number of listings per page.
	 *
	 * @var int
	 */
	public const PER_PAGE = 10;

	/**
	 * Nonce action for listing actions.
	 *
	 * @var string
	 */
	public const NONCE_ACTION = 'apd_my_listings_action';

	/**
	 * Valid status filters.
	 *
	 * @var array<string>
	 */
	private const VALID_STATUSES = [ 'all', 'publish', 'pending', 'draft', 'expired' ];

	/**
	 * Valid sort options.
	 *
	 * @var array<string>
	 */
	private const VALID_ORDERBY = [ 'date', 'title', 'views', 'inquiries' ];

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
		'per_page'       => self::PER_PAGE,
		'show_views'     => true,
		'show_date'      => true,
		'show_thumbnail' => true,
		'show_inquiries' => true,
	];

	/**
	 * Singleton instance.
	 *
	 * @var MyListings|null
	 */
	private static ?MyListings $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $config Optional. Configuration options.
	 * @return MyListings
	 */
	public static function get_instance( array $config = [] ): MyListings {
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
		// Handle form actions on init.
		add_action( 'init', [ $this, 'handle_actions' ] );
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
	 * Render the My Listings tab content.
	 *
	 * @since 1.0.0
	 *
	 * @return string Rendered HTML.
	 */
	public function render(): string {
		if ( $this->user_id <= 0 ) {
			return '';
		}

		$status  = $this->get_status_filter();
		$orderby = $this->get_orderby_filter();
		$order   = $this->get_order_filter();
		$paged   = $this->get_current_page();

		$listings = $this->get_listings(
			[
				'status'  => $status,
				'orderby' => $orderby,
				'order'   => $order,
				'paged'   => $paged,
			]
		);

		$args = [
			'my_listings' => $this,
			'listings'    => $listings,
			'status'      => $status,
			'orderby'     => $orderby,
			'order'       => $order,
			'paged'       => $paged,
			'total'       => $listings->found_posts,
			'max_pages'   => $listings->max_num_pages,
			'user_id'     => $this->user_id,
			'config'      => $this->config,
			'nonce'       => wp_create_nonce( self::NONCE_ACTION ),
			'statuses'    => $this->get_status_options(),
		];

		/**
		 * Filter the My Listings template arguments.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, mixed> $args Template arguments.
		 */
		$args = apply_filters( 'apd_my_listings_args', $args );

		ob_start();
		\apd_get_template( 'dashboard/my-listings.php', $args );
		return ob_get_clean();
	}

	/**
	 * Get user's listings with optional filters.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $args Query arguments.
	 *                                   - status: (string) Post status filter.
	 *                                   - orderby: (string) Order by field.
	 *                                   - order: (string) Order direction.
	 *                                   - paged: (int) Page number.
	 *                                   - per_page: (int) Items per page.
	 * @return \WP_Query Query result.
	 */
	public function get_listings( array $args = [] ): \WP_Query {
		$defaults = [
			'status'   => 'all',
			'orderby'  => 'date',
			'order'    => 'DESC',
			'paged'    => 1,
			'per_page' => $this->config['per_page'],
		];

		$args = wp_parse_args( $args, $defaults );

		// Build post status array.
		if ( $args['status'] === 'all' ) {
			$post_status = [ 'publish', 'pending', 'draft', 'expired' ];
		} else {
			$post_status = $args['status'];
		}

		$query_args = [
			'post_type'      => PostType::POST_TYPE,
			'post_status'    => $post_status,
			'author'         => $this->user_id,
			'posts_per_page' => $args['per_page'],
			'paged'          => $args['paged'],
		];

		// Handle orderby.
		// For meta-based sorting (views, inquiries), use meta_query with EXISTS/NOT EXISTS
		// so listings without the meta key are included (LEFT JOIN) instead of excluded (INNER JOIN).
		if ( $args['orderby'] === 'views' ) {
			$query_args['meta_query'] = [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Views sorting uses EXISTS/NOT EXISTS for LEFT JOIN behavior.
				'relation' => 'OR',
				[
					'key'     => '_apd_views_count',
					'compare' => 'EXISTS',
				],
				[
					'key'     => '_apd_views_count',
					'compare' => 'NOT EXISTS',
				],
			];
			$query_args['orderby']    = 'meta_value_num';
		} elseif ( $args['orderby'] === 'inquiries' ) {
			$meta_key                 = \APD\Contact\InquiryTracker::LISTING_INQUIRY_COUNT;
			$query_args['meta_query'] = [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Inquiry sorting uses EXISTS/NOT EXISTS for LEFT JOIN behavior.
				'relation' => 'OR',
				[
					'key'     => $meta_key,
					'compare' => 'EXISTS',
				],
				[
					'key'     => $meta_key,
					'compare' => 'NOT EXISTS',
				],
			];
			$query_args['orderby']    = 'meta_value_num';
		} else {
			$query_args['orderby'] = $args['orderby'];
		}

		$query_args['order'] = strtoupper( $args['order'] );

		/**
		 * Filter the My Listings query arguments.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, mixed> $query_args WP_Query arguments.
		 * @param int                  $user_id    User ID.
		 */
		$query_args = apply_filters( 'apd_my_listings_query_args', $query_args, $this->user_id );

		return new \WP_Query( $query_args );
	}

	/**
	 * Get the current status filter from URL.
	 *
	 * @since 1.0.0
	 *
	 * @return string Status filter value.
	 */
	public function get_status_filter(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Just reading filter parameter.
		$status = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : 'all';

		if ( ! in_array( $status, self::VALID_STATUSES, true ) ) {
			return 'all';
		}

		return $status;
	}

	/**
	 * Get the current orderby filter from URL.
	 *
	 * @since 1.0.0
	 *
	 * @return string Orderby value.
	 */
	public function get_orderby_filter(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Just reading filter parameter.
		$orderby = isset( $_GET['orderby'] ) ? sanitize_key( $_GET['orderby'] ) : 'date';

		if ( ! in_array( $orderby, self::VALID_ORDERBY, true ) ) {
			return 'date';
		}

		return $orderby;
	}

	/**
	 * Get the current order direction from URL.
	 *
	 * @since 1.0.0
	 *
	 * @return string Order direction (ASC or DESC).
	 */
	public function get_order_filter(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Just reading filter parameter.
		$order = isset( $_GET['order'] ) ? strtoupper( sanitize_key( $_GET['order'] ) ) : 'DESC';

		return in_array( $order, [ 'ASC', 'DESC' ], true ) ? $order : 'DESC';
	}

	/**
	 * Get the current page number from URL.
	 *
	 * @since 1.0.0
	 *
	 * @return int Current page number.
	 */
	public function get_current_page(): int {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Just reading page parameter.
		return isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
	}

	/**
	 * Get available status filter options.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array<string, mixed>> Status options with labels and counts.
	 */
	public function get_status_options(): array {
		$stats = \apd_get_user_listing_stats( $this->user_id );

		return [
			'all'     => [
				'label' => __( 'All', 'damdir-directory' ),
				'count' => $stats['total'],
			],
			'publish' => [
				'label' => __( 'Published', 'damdir-directory' ),
				'count' => $stats['published'],
			],
			'pending' => [
				'label' => __( 'Pending', 'damdir-directory' ),
				'count' => $stats['pending'],
			],
			'draft'   => [
				'label' => __( 'Draft', 'damdir-directory' ),
				'count' => $stats['draft'],
			],
			'expired' => [
				'label' => __( 'Expired', 'damdir-directory' ),
				'count' => $stats['expired'],
			],
		];
	}

	/**
	 * Get available orderby options.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string> Orderby options with labels.
	 */
	public function get_orderby_options(): array {
		return [
			'date'      => __( 'Date', 'damdir-directory' ),
			'title'     => __( 'Title', 'damdir-directory' ),
			'views'     => __( 'Views', 'damdir-directory' ),
			'inquiries' => __( 'Inquiries', 'damdir-directory' ),
		];
	}

	/**
	 * Get the inquiry count for a listing.
	 *
	 * @since 1.0.0
	 *
	 * @param int $listing_id Listing post ID.
	 * @return int Inquiry count.
	 */
	public function get_listing_inquiry_count( int $listing_id ): int {
		$tracker = \APD\Contact\InquiryTracker::get_instance();
		return $tracker->get_listing_inquiry_count( $listing_id );
	}

	/**
	 * Handle listing actions (delete, status change).
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function handle_actions(): void {
		// Check for action parameter.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified below.
		if ( ! isset( $_REQUEST['apd_action'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$action = sanitize_key( $_REQUEST['apd_action'] );

		// Verify nonce.
		if ( ! isset( $_REQUEST['_apd_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_apd_nonce'] ) ), self::NONCE_ACTION ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$listing_id = isset( $_REQUEST['listing_id'] ) ? absint( $_REQUEST['listing_id'] ) : 0;

		if ( $listing_id <= 0 ) {
			return;
		}

		$result = false;

		switch ( $action ) {
			case 'delete':
				$result = $this->delete_listing( $listing_id );
				break;

			case 'trash':
				$result = $this->trash_listing( $listing_id );
				break;

			case 'expire':
				$result = $this->update_listing_status( $listing_id, 'expired' );
				break;

			case 'publish':
				$result = $this->update_listing_status( $listing_id, 'publish' );
				break;

			case 'draft':
				$result = $this->update_listing_status( $listing_id, 'draft' );
				break;
		}

		// Set message transient.
		if ( $result ) {
			set_transient(
				'apd_my_listings_message_' . get_current_user_id(),
				[
					'type'    => 'success',
					'message' => $this->get_action_success_message( $action ),
				],
				30
			);
		} else {
			set_transient(
				'apd_my_listings_message_' . get_current_user_id(),
				[
					'type'    => 'error',
					'message' => $this->get_action_error_message( $action ),
				],
				30
			);
		}

		// Redirect to remove action from URL.
		$redirect_url = remove_query_arg( [ 'apd_action', 'listing_id', '_apd_nonce' ] );
		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Delete a listing permanently.
	 *
	 * @since 1.0.0
	 *
	 * @param int      $listing_id Listing post ID.
	 * @param int|null $user_id    Optional. User ID to verify ownership. Defaults to current user.
	 * @return bool True on success, false on failure.
	 */
	public function delete_listing( int $listing_id, ?int $user_id = null ): bool {
		if ( ! $this->can_delete_listing( $listing_id, $user_id ) ) {
			return false;
		}

		/**
		 * Fires before a listing is deleted from the dashboard.
		 *
		 * @since 1.0.0
		 *
		 * @param int $listing_id The listing ID being deleted.
		 * @param int $user_id    The user deleting the listing.
		 */
		do_action( 'apd_before_delete_listing', $listing_id, $user_id ?? $this->user_id );

		$result = wp_delete_post( $listing_id, true );

		if ( $result ) {
			/**
			 * Fires after a listing is deleted from the dashboard.
			 *
			 * @since 1.0.0
			 *
			 * @param int $listing_id The listing ID that was deleted.
			 * @param int $user_id    The user who deleted the listing.
			 */
			do_action( 'apd_after_delete_listing', $listing_id, $user_id ?? $this->user_id );
		}

		return $result !== false && $result !== null;
	}

	/**
	 * Move a listing to trash.
	 *
	 * @since 1.0.0
	 *
	 * @param int      $listing_id Listing post ID.
	 * @param int|null $user_id    Optional. User ID to verify ownership. Defaults to current user.
	 * @return bool True on success, false on failure.
	 */
	public function trash_listing( int $listing_id, ?int $user_id = null ): bool {
		if ( ! $this->can_delete_listing( $listing_id, $user_id ) ) {
			return false;
		}

		/**
		 * Fires before a listing is trashed from the dashboard.
		 *
		 * @since 1.0.0
		 *
		 * @param int $listing_id The listing ID being trashed.
		 * @param int $user_id    The user trashing the listing.
		 */
		do_action( 'apd_before_trash_listing', $listing_id, $user_id ?? $this->user_id );

		$result = wp_trash_post( $listing_id );

		if ( $result ) {
			/**
			 * Fires after a listing is trashed from the dashboard.
			 *
			 * @since 1.0.0
			 *
			 * @param int $listing_id The listing ID that was trashed.
			 * @param int $user_id    The user who trashed the listing.
			 */
			do_action( 'apd_after_trash_listing', $listing_id, $user_id ?? $this->user_id );
		}

		return $result !== false && $result !== null;
	}

	/**
	 * Update a listing's status.
	 *
	 * @since 1.0.0
	 *
	 * @param int      $listing_id Listing post ID.
	 * @param string   $status     New status (publish, draft, pending, expired).
	 * @param int|null $user_id    Optional. User ID to verify ownership. Defaults to current user.
	 * @return bool True on success, false on failure.
	 */
	public function update_listing_status( int $listing_id, string $status, ?int $user_id = null ): bool {
		if ( ! $this->can_edit_listing( $listing_id, $user_id ) ) {
			return false;
		}

		$valid_statuses = [ 'publish', 'draft', 'pending', 'expired' ];
		if ( ! in_array( $status, $valid_statuses, true ) ) {
			return false;
		}

		// Users cannot self-publish if pending is default.
		if ( $status === 'publish' ) {
			$default_status = \apd_get_default_listing_status();
			if ( $default_status === 'pending' && ! current_user_can( 'publish_apd_listings' ) ) {
				$status = 'pending';
			}
		}

		$old_status = get_post_status( $listing_id );

		/**
		 * Fires before a listing status is changed from the dashboard.
		 *
		 * @since 1.0.0
		 *
		 * @param int    $listing_id The listing ID.
		 * @param string $status     The new status.
		 * @param string $old_status The old status.
		 * @param int    $user_id    The user changing the status.
		 */
		do_action( 'apd_before_change_listing_status', $listing_id, $status, $old_status, $user_id ?? $this->user_id );

		$result = wp_update_post(
			[
				'ID'          => $listing_id,
				'post_status' => $status,
			],
			true
		);

		if ( ! is_wp_error( $result ) ) {
			/**
			 * Fires after a listing status is changed from the dashboard.
			 *
			 * @since 1.0.0
			 *
			 * @param int    $listing_id The listing ID.
			 * @param string $status     The new status.
			 * @param string $old_status The old status.
			 * @param int    $user_id    The user who changed the status.
			 */
			do_action( 'apd_after_change_listing_status', $listing_id, $status, $old_status, $user_id ?? $this->user_id );

			return true;
		}

		return false;
	}

	/**
	 * Check if a user can delete a listing.
	 *
	 * @since 1.0.0
	 *
	 * @param int      $listing_id Listing post ID.
	 * @param int|null $user_id    Optional. User ID to check. Defaults to current user.
	 * @return bool True if user can delete, false otherwise.
	 */
	public function can_delete_listing( int $listing_id, ?int $user_id = null ): bool {
		if ( $user_id === null ) {
			$user_id = $this->user_id;
		}

		if ( $user_id <= 0 || $listing_id <= 0 ) {
			return false;
		}

		$post = get_post( $listing_id );

		if ( ! $post || $post->post_type !== PostType::POST_TYPE ) {
			return false;
		}

		// Check if user is the author.
		if ( (int) $post->post_author === $user_id ) {
			return true;
		}

		// Check if user can delete others' listings.
		if ( user_can( $user_id, 'delete_others_apd_listings' ) ) {
			return true;
		}

		/**
		 * Filter whether the user can delete this listing.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $can_delete Whether user can delete. Default false.
		 * @param int  $listing_id The listing ID.
		 * @param int  $user_id    The user ID.
		 */
		return apply_filters( 'apd_user_can_delete_listing', false, $listing_id, $user_id );
	}

	/**
	 * Check if a user can edit a listing.
	 *
	 * @since 1.0.0
	 *
	 * @param int      $listing_id Listing post ID.
	 * @param int|null $user_id    Optional. User ID to check. Defaults to current user.
	 * @return bool True if user can edit, false otherwise.
	 */
	public function can_edit_listing( int $listing_id, ?int $user_id = null ): bool {
		if ( $user_id === null ) {
			$user_id = $this->user_id;
		}

		return \apd_user_can_edit_listing( $listing_id, $user_id );
	}

	/**
	 * Get URL for a specific listing action.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $listing_id Listing post ID.
	 * @param string $action     Action name (delete, trash, expire, publish, draft).
	 * @return string Action URL with nonce.
	 */
	public function get_action_url( int $listing_id, string $action ): string {
		return add_query_arg(
			Url::encode_deep(
				[
					'apd_action' => $action,
					'listing_id' => $listing_id,
					'_apd_nonce' => wp_create_nonce( self::NONCE_ACTION ),
				]
			)
		);
	}

	/**
	 * Get the edit URL for a listing.
	 *
	 * @since 1.0.0
	 *
	 * @param int $listing_id Listing post ID.
	 * @return string Edit URL.
	 */
	public function get_edit_url( int $listing_id ): string {
		return \apd_get_edit_listing_url( $listing_id );
	}

	/**
	 * Get success message for an action.
	 *
	 * @since 1.0.0
	 *
	 * @param string $action Action name.
	 * @return string Success message.
	 */
	private function get_action_success_message( string $action ): string {
		$messages = [
			'delete'  => __( 'Listing permanently deleted.', 'damdir-directory' ),
			'trash'   => __( 'Listing moved to trash.', 'damdir-directory' ),
			'expire'  => __( 'Listing marked as expired.', 'damdir-directory' ),
			'publish' => __( 'Listing published.', 'damdir-directory' ),
			'draft'   => __( 'Listing saved as draft.', 'damdir-directory' ),
		];

		return $messages[ $action ] ?? __( 'Action completed.', 'damdir-directory' );
	}

	/**
	 * Get error message for an action.
	 *
	 * @since 1.0.0
	 *
	 * @param string $action Action name.
	 * @return string Error message.
	 */
	private function get_action_error_message( string $action ): string {
		$messages = [
			'delete'  => __( 'Failed to delete listing. You may not have permission.', 'damdir-directory' ),
			'trash'   => __( 'Failed to trash listing. You may not have permission.', 'damdir-directory' ),
			'expire'  => __( 'Failed to expire listing. You may not have permission.', 'damdir-directory' ),
			'publish' => __( 'Failed to publish listing. You may not have permission.', 'damdir-directory' ),
			'draft'   => __( 'Failed to save listing as draft. You may not have permission.', 'damdir-directory' ),
		];

		return $messages[ $action ] ?? __( 'Action failed. Please try again.', 'damdir-directory' );
	}

	/**
	 * Get any pending message for display.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string>|null Message array with 'type' and 'message', or null.
	 */
	public function get_message(): ?array {
		$transient_key = 'apd_my_listings_message_' . $this->user_id;
		$message       = get_transient( $transient_key );

		if ( $message ) {
			delete_transient( $transient_key );
			return $message;
		}

		return null;
	}

	/**
	 * Get the status badge HTML for a listing.
	 *
	 * @since 1.0.0
	 *
	 * @param string $status Post status.
	 * @return string Badge HTML.
	 */
	public function get_status_badge( string $status ): string {
		$badges = [
			'publish' => [
				'label' => __( 'Published', 'damdir-directory' ),
				'class' => 'apd-status-badge--success',
			],
			'pending' => [
				'label' => __( 'Pending', 'damdir-directory' ),
				'class' => 'apd-status-badge--warning',
			],
			'draft'   => [
				'label' => __( 'Draft', 'damdir-directory' ),
				'class' => 'apd-status-badge--default',
			],
			'expired' => [
				'label' => __( 'Expired', 'damdir-directory' ),
				'class' => 'apd-status-badge--error',
			],
		];

		$badge = $badges[ $status ] ?? [
			'label' => ucfirst( $status ),
			'class' => 'apd-status-badge--default',
		];

		return sprintf(
			'<span class="apd-status-badge %s">%s</span>',
			esc_attr( $badge['class'] ),
			esc_html( $badge['label'] )
		);
	}

	/**
	 * Get available actions for a listing based on its status.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_Post $post Listing post object.
	 * @return array<string, array<string, string>> Available actions.
	 */
	public function get_listing_actions( \WP_Post $post ): array {
		$actions = [];

		// Edit action (always available).
		$edit_url = $this->get_edit_url( $post->ID );
		if ( ! empty( $edit_url ) ) {
			$actions['edit'] = [
				'label' => __( 'Edit', 'damdir-directory' ),
				'url'   => $edit_url,
				'class' => 'apd-listing-action--edit',
			];
		}

		// View action (if published).
		if ( $post->post_status === 'publish' ) {
			$actions['view'] = [
				'label' => __( 'View', 'damdir-directory' ),
				'url'   => get_permalink( $post->ID ),
				'class' => 'apd-listing-action--view',
			];
		}

		// Status change actions based on current status.
		switch ( $post->post_status ) {
			case 'publish':
				$actions['expire'] = [
					'label'   => __( 'Mark Expired', 'damdir-directory' ),
					'url'     => $this->get_action_url( $post->ID, 'expire' ),
					'class'   => 'apd-listing-action--expire',
					'confirm' => __( 'Are you sure you want to mark this listing as expired?', 'damdir-directory' ),
				];
				break;

			case 'draft':
			case 'expired':
				if ( current_user_can( 'publish_apd_listings' ) ) {
					$actions['publish'] = [
						'label' => __( 'Publish', 'damdir-directory' ),
						'url'   => $this->get_action_url( $post->ID, 'publish' ),
						'class' => 'apd-listing-action--publish',
					];
				} else {
					$actions['pending'] = [
						'label' => __( 'Submit for Review', 'damdir-directory' ),
						'url'   => $this->get_action_url( $post->ID, 'publish' ),
						'class' => 'apd-listing-action--submit',
					];
				}
				break;

			case 'pending':
				$actions['draft'] = [
					'label' => __( 'Save as Draft', 'damdir-directory' ),
					'url'   => $this->get_action_url( $post->ID, 'draft' ),
					'class' => 'apd-listing-action--draft',
				];
				break;
		}

		// Delete action (always available).
		$actions['delete'] = [
			'label'   => __( 'Delete', 'damdir-directory' ),
			'url'     => $this->get_action_url( $post->ID, 'trash' ),
			'class'   => 'apd-listing-action--delete',
			'confirm' => __( 'Are you sure you want to delete this listing?', 'damdir-directory' ),
		];

		/**
		 * Filter the available actions for a listing.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, array<string, string>> $actions Available actions.
		 * @param \WP_Post                             $post    Listing post object.
		 */
		return apply_filters( 'apd_my_listings_actions', $actions, $post );
	}
}
