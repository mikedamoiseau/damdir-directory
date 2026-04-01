<?php
/**
 * Review Moderation Admin Class.
 *
 * Provides admin interface for managing listing reviews including
 * listing, filtering, bulk actions, and single review management.
 *
 * @package APD\Admin
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Admin;

use APD\Core\Url;
use APD\Review\ReviewManager;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ReviewModeration
 *
 * Admin interface for review moderation.
 *
 * @since 1.0.0
 */
final class ReviewModeration {

	/**
	 * Admin page slug.
	 */
	public const PAGE_SLUG = 'apd-reviews';

	/**
	 * Nonce action for review moderation.
	 */
	public const NONCE_ACTION = 'apd_review_moderation';

	/**
	 * Nonce field name.
	 */
	public const NONCE_NAME = 'apd_review_nonce';

	/**
	 * Number of reviews per page.
	 */
	public const PER_PAGE = 20;

	/**
	 * Parent menu slug (apd_listing post type).
	 */
	public const PARENT_MENU = 'edit.php?post_type=apd_listing';

	/**
	 * Capability required to manage reviews.
	 */
	public const CAPABILITY = 'moderate_comments';

	/**
	 * Singleton instance.
	 *
	 * @var ReviewModeration|null
	 */
	private static ?ReviewModeration $instance = null;

	/**
	 * Review Manager instance.
	 *
	 * @var ReviewManager
	 */
	private ReviewManager $review_manager;

	/**
	 * Get the singleton instance.
	 *
	 * @since 1.0.0
	 *
	 * @return ReviewModeration
	 */
	public static function get_instance(): ReviewModeration {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->review_manager = ReviewManager::get_instance();
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
	 * Initialize the review moderation.
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

		add_action( 'admin_menu', [ $this, 'register_admin_page' ] );
		add_action( 'admin_init', [ $this, 'handle_actions' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_styles' ] );

		// Add pending count to menu.
		add_filter( 'add_menu_classes', [ $this, 'add_pending_count_bubble' ] );
	}

	/**
	 * Register the admin menu page.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_admin_page(): void {
		$pending_count = $this->get_pending_count();
		$menu_title    = __( 'Reviews', 'damdir-directory' );

		if ( $pending_count > 0 ) {
			$menu_title .= sprintf(
				' <span class="awaiting-mod count-%d"><span class="pending-count" aria-hidden="true">%s</span><span class="screen-reader-text">%s</span></span>',
				$pending_count,
				number_format_i18n( $pending_count ),
				sprintf(
					/* translators: %s: Number of pending reviews */
					_n(
						'%s review awaiting moderation',
						'%s reviews awaiting moderation',
						$pending_count,
						'damdir-directory'
					),
					number_format_i18n( $pending_count )
				)
			);
		}

		add_submenu_page(
			self::PARENT_MENU,
			__( 'Reviews', 'damdir-directory' ),
			$menu_title,
			self::CAPABILITY,
			self::PAGE_SLUG,
			[ $this, 'render_admin_page' ]
		);
	}

	/**
	 * Enqueue admin styles.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook_suffix The current admin page.
	 * @return void
	 */
	public function enqueue_styles( string $hook_suffix ): void {
		// Only load on our page.
		if ( 'apd_listing_page_' . self::PAGE_SLUG !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'apd-admin-base',
			APD_PLUGIN_URL . 'assets/css/admin-base.css',
			[],
			APD_VERSION
		);

		wp_enqueue_style(
			'apd-admin',
			APD_PLUGIN_URL . 'assets/css/admin.css',
			[ 'apd-admin-base' ],
			APD_VERSION
		);
	}

	/**
	 * Add pending count bubble to the Listings menu.
	 *
	 * @since 1.0.0
	 *
	 * @param array $menu The admin menu array.
	 * @return array Modified menu.
	 */
	public function add_pending_count_bubble( array $menu ): array {
		$pending_count = $this->get_pending_count();

		if ( $pending_count <= 0 ) {
			return $menu;
		}

		foreach ( $menu as $key => $item ) {
			if ( isset( $item[2] ) && $item[2] === 'edit.php?post_type=apd_listing' ) {
				$menu[ $key ][0] .= sprintf(
					' <span class="awaiting-mod count-%d"><span class="pending-count">%s</span></span>',
					$pending_count,
					number_format_i18n( $pending_count )
				);
				break;
			}
		}

		return $menu;
	}

	/**
	 * Render the admin page.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function render_admin_page(): void {
		// Check permissions.
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'damdir-directory' ) );
		}

		// Get current filters.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display only.
		$current_status = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : 'all';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current_listing = isset( $_GET['listing_id'] ) ? absint( $_GET['listing_id'] ) : 0;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current_rating = isset( $_GET['rating'] ) ? absint( $_GET['rating'] ) : 0;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$paged = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;

		// Get reviews.
		$reviews_data = $this->get_reviews(
			[
				'status'     => $current_status,
				'listing_id' => $current_listing,
				'rating'     => $current_rating,
				'search'     => $search,
				'paged'      => $paged,
			]
		);

		$reviews     = $reviews_data['reviews'];
		$total       = $reviews_data['total'];
		$total_pages = $reviews_data['pages'];

		// Get counts for status tabs.
		$counts = $this->get_status_counts();

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML is escaped in render_page_content().
		echo $this->render_page_content( $reviews, $counts, $current_status, $current_listing, $current_rating, $search, $paged, $total, $total_pages );
	}

	/**
	 * Render the page content HTML.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $reviews         Array of review data.
	 * @param array  $counts          Status counts.
	 * @param string $current_status  Current status filter.
	 * @param int    $current_listing Current listing filter.
	 * @param int    $current_rating  Current rating filter.
	 * @param string $search          Search query.
	 * @param int    $paged           Current page.
	 * @param int    $total           Total reviews.
	 * @param int    $total_pages     Total pages.
	 * @return string HTML content.
	 */
	private function render_page_content(
		array $reviews,
		array $counts,
		string $current_status,
		int $current_listing,
		int $current_rating,
		string $search,
		int $paged,
		int $total,
		int $total_pages
	): string {
		$base_url = admin_url( 'edit.php?post_type=apd_listing&page=' . self::PAGE_SLUG );

		ob_start();
		?>
		<div class="wrap apd-reviews-wrap">
			<div class="apd-page-header">
				<div class="apd-page-header__icon">
					<span class="dashicons dashicons-star-half" aria-hidden="true"></span>
				</div>
				<div class="apd-page-header__content">
					<h1><?php esc_html_e( 'Reviews', 'damdir-directory' ); ?></h1>
				</div>
			</div>
			<hr class="wp-header-end">

			<?php $this->render_messages(); ?>

			<ul class="subsubsub">
				<?php echo wp_kses_post( $this->render_status_tabs( $counts, $current_status, $base_url ) ); ?>
			</ul>

			<form method="get" class="apd-reviews-filters">
				<input type="hidden" name="post_type" value="apd_listing">
				<input type="hidden" name="page" value="<?php echo esc_attr( self::PAGE_SLUG ); ?>">

				<p class="search-box">
					<label class="screen-reader-text" for="review-search-input">
						<?php esc_html_e( 'Search Reviews', 'damdir-directory' ); ?>
					</label>
					<input type="search" id="review-search-input" name="s" value="<?php echo esc_attr( $search ); ?>">
					<input type="submit" id="search-submit" class="button" value="<?php esc_attr_e( 'Search Reviews', 'damdir-directory' ); ?>">
				</p>
			</form>

			<form method="get" class="apd-reviews-filter-form">
				<input type="hidden" name="post_type" value="apd_listing">
				<input type="hidden" name="page" value="<?php echo esc_attr( self::PAGE_SLUG ); ?>">
				<div class="tablenav apd-filters-nav">
					<div class="alignleft actions">
						<?php echo wp_kses( $this->render_status_filter( $current_status ), $this->get_allowed_filter_html() ); ?>
						<?php echo wp_kses( $this->render_listing_filter( $current_listing ), $this->get_allowed_filter_html() ); ?>
						<?php echo wp_kses( $this->render_rating_filter( $current_rating ), $this->get_allowed_filter_html() ); ?>
						<input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'damdir-directory' ); ?>">
					</div>
					<br class="clear">
				</div>
			</form>

			<form method="post" id="apd-reviews-form">
				<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME ); ?>
				<input type="hidden" name="current_status" value="<?php echo esc_attr( $current_status ); ?>">

				<div class="tablenav top">
					<div class="alignleft actions bulkactions">
						<label for="bulk-action-selector-top" class="screen-reader-text">
							<?php esc_html_e( 'Select bulk action', 'damdir-directory' ); ?>
						</label>
						<select name="bulk_action" id="bulk-action-selector-top">
							<option value=""><?php esc_html_e( 'Bulk Actions', 'damdir-directory' ); ?></option>
							<?php if ( $current_status !== 'approved' ) : ?>
								<option value="approve"><?php esc_html_e( 'Approve', 'damdir-directory' ); ?></option>
							<?php endif; ?>
							<?php if ( $current_status !== 'spam' ) : ?>
								<option value="spam"><?php esc_html_e( 'Mark as Spam', 'damdir-directory' ); ?></option>
							<?php endif; ?>
							<?php if ( $current_status !== 'trash' ) : ?>
								<option value="trash"><?php esc_html_e( 'Move to Trash', 'damdir-directory' ); ?></option>
							<?php endif; ?>
							<?php if ( $current_status === 'trash' ) : ?>
								<option value="restore"><?php esc_html_e( 'Restore', 'damdir-directory' ); ?></option>
								<option value="delete"><?php esc_html_e( 'Delete Permanently', 'damdir-directory' ); ?></option>
							<?php endif; ?>
						</select>
						<input type="submit" id="doaction" class="button action" value="<?php esc_attr_e( 'Apply', 'damdir-directory' ); ?>">
					</div>

					<?php echo wp_kses_post( $this->render_pagination( $paged, $total_pages, $total, 'top' ) ); ?>
					<br class="clear">
				</div>

				<table class="wp-list-table widefat fixed striped table-view-list apd-reviews-table">
					<thead>
						<tr>
							<td id="cb" class="manage-column column-cb check-column">
								<input id="cb-select-all-1" type="checkbox">
								<label for="cb-select-all-1">
									<span class="screen-reader-text"><?php esc_html_e( 'Select All', 'damdir-directory' ); ?></span>
								</label>
							</td>
							<th scope="col" class="manage-column column-listing"><?php esc_html_e( 'Listing', 'damdir-directory' ); ?></th>
							<th scope="col" class="manage-column column-author"><?php esc_html_e( 'Author', 'damdir-directory' ); ?></th>
							<th scope="col" class="manage-column column-rating"><?php esc_html_e( 'Rating', 'damdir-directory' ); ?></th>
							<th scope="col" class="manage-column column-review"><?php esc_html_e( 'Review', 'damdir-directory' ); ?></th>
							<th scope="col" class="manage-column column-status"><?php esc_html_e( 'Status', 'damdir-directory' ); ?></th>
							<th scope="col" class="manage-column column-date"><?php esc_html_e( 'Date', 'damdir-directory' ); ?></th>
						</tr>
					</thead>
					<tbody id="the-list">
						<?php if ( empty( $reviews ) ) : ?>
							<tr class="no-items">
								<td class="colspanchange" colspan="7">
									<?php esc_html_e( 'No reviews found.', 'damdir-directory' ); ?>
								</td>
							</tr>
						<?php else : ?>
							<?php foreach ( $reviews as $review ) : ?>
								<?php
							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render_review_row() escapes all values internally.
								echo $this->render_review_row( $review, $current_status );
								?>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
					<tfoot>
						<tr>
							<td class="manage-column column-cb check-column">
								<input id="cb-select-all-2" type="checkbox">
								<label for="cb-select-all-2">
									<span class="screen-reader-text"><?php esc_html_e( 'Select All', 'damdir-directory' ); ?></span>
								</label>
							</td>
							<th scope="col" class="manage-column column-listing"><?php esc_html_e( 'Listing', 'damdir-directory' ); ?></th>
							<th scope="col" class="manage-column column-author"><?php esc_html_e( 'Author', 'damdir-directory' ); ?></th>
							<th scope="col" class="manage-column column-rating"><?php esc_html_e( 'Rating', 'damdir-directory' ); ?></th>
							<th scope="col" class="manage-column column-review"><?php esc_html_e( 'Review', 'damdir-directory' ); ?></th>
							<th scope="col" class="manage-column column-status"><?php esc_html_e( 'Status', 'damdir-directory' ); ?></th>
							<th scope="col" class="manage-column column-date"><?php esc_html_e( 'Date', 'damdir-directory' ); ?></th>
						</tr>
					</tfoot>
				</table>

				<div class="tablenav bottom">
					<div class="alignleft actions bulkactions">
						<label for="bulk-action-selector-bottom" class="screen-reader-text">
							<?php esc_html_e( 'Select bulk action', 'damdir-directory' ); ?>
						</label>
						<select name="bulk_action2" id="bulk-action-selector-bottom">
							<option value=""><?php esc_html_e( 'Bulk Actions', 'damdir-directory' ); ?></option>
							<?php if ( $current_status !== 'approved' ) : ?>
								<option value="approve"><?php esc_html_e( 'Approve', 'damdir-directory' ); ?></option>
							<?php endif; ?>
							<?php if ( $current_status !== 'spam' ) : ?>
								<option value="spam"><?php esc_html_e( 'Mark as Spam', 'damdir-directory' ); ?></option>
							<?php endif; ?>
							<?php if ( $current_status !== 'trash' ) : ?>
								<option value="trash"><?php esc_html_e( 'Move to Trash', 'damdir-directory' ); ?></option>
							<?php endif; ?>
							<?php if ( $current_status === 'trash' ) : ?>
								<option value="restore"><?php esc_html_e( 'Restore', 'damdir-directory' ); ?></option>
								<option value="delete"><?php esc_html_e( 'Delete Permanently', 'damdir-directory' ); ?></option>
							<?php endif; ?>
						</select>
						<input type="submit" id="doaction2" class="button action" value="<?php esc_attr_e( 'Apply', 'damdir-directory' ); ?>">
					</div>

					<?php echo wp_kses_post( $this->render_pagination( $paged, $total_pages, $total, 'bottom' ) ); ?>
					<br class="clear">
				</div>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render status tabs.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $counts         Status counts.
	 * @param string $current_status Current status filter.
	 * @param string $base_url       Base URL for links.
	 * @return string HTML for status tabs.
	 */
	private function render_status_tabs( array $counts, string $current_status, string $base_url ): string {
		$statuses = [
			'all'      => __( 'All', 'damdir-directory' ),
			'pending'  => __( 'Pending', 'damdir-directory' ),
			'approved' => __( 'Approved', 'damdir-directory' ),
			'spam'     => __( 'Spam', 'damdir-directory' ),
			'trash'    => __( 'Trash', 'damdir-directory' ),
		];

		$output = '';
		$items  = [];

		foreach ( $statuses as $status => $label ) {
			$count = $counts[ $status ] ?? 0;

			if ( $status !== 'all' && $count === 0 ) {
				continue;
			}

			$url   = $status === 'all' ? $base_url : add_query_arg( 'status', $status, $base_url );
			$class = $current_status === $status ? 'current' : '';

			$items[] = sprintf(
				'<li class="%s"><a href="%s" class="%s">%s <span class="count">(%s)</span></a></li>',
				esc_attr( $status ),
				esc_url( $url ),
				esc_attr( $class ),
				esc_html( $label ),
				esc_html( number_format_i18n( $count ) )
			);
		}

		return implode( ' | ', $items );
	}

	/**
	 * Render a single review row.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $review         Review data.
	 * @param string $current_status Current status filter.
	 * @return string HTML for the row.
	 */
	private function render_review_row( array $review, string $current_status ): string {
		$listing       = get_post( $review['listing_id'] );
		$listing_title = $listing ? $listing->post_title : __( '(Deleted)', 'damdir-directory' );
		$listing_url   = $listing ? get_edit_post_link( $listing->ID ) : '';

		$row_class = 'review-' . $review['id'];
		if ( $review['status'] === 'pending' ) {
			$row_class .= ' unapproved';
		}

		ob_start();
		?>
		<tr id="review-<?php echo esc_attr( $review['id'] ); ?>" class="<?php echo esc_attr( $row_class ); ?>">
			<th scope="row" class="check-column">
				<input type="checkbox" name="review_ids[]" value="<?php echo esc_attr( $review['id'] ); ?>">
			</th>
			<td class="column-listing" data-colname="<?php esc_attr_e( 'Listing', 'damdir-directory' ); ?>">
				<?php if ( $listing_url ) : ?>
					<a href="<?php echo esc_url( $listing_url ); ?>"><?php echo esc_html( $listing_title ); ?></a>
				<?php else : ?>
					<?php echo esc_html( $listing_title ); ?>
				<?php endif; ?>
			</td>
			<td class="column-author" data-colname="<?php esc_attr_e( 'Author', 'damdir-directory' ); ?>">
				<strong><?php echo esc_html( $review['author_name'] ); ?></strong>
				<?php if ( ! empty( $review['author_email'] ) ) : ?>
					<br><a href="mailto:<?php echo esc_attr( $review['author_email'] ); ?>"><?php echo esc_html( $review['author_email'] ); ?></a>
				<?php endif; ?>
			</td>
			<td class="column-rating" data-colname="<?php esc_attr_e( 'Rating', 'damdir-directory' ); ?>">
				<?php echo wp_kses_post( $this->render_stars( $review['rating'] ) ); ?>
			</td>
			<td class="column-review" data-colname="<?php esc_attr_e( 'Review', 'damdir-directory' ); ?>">
				<?php if ( ! empty( $review['title'] ) ) : ?>
					<strong><?php echo esc_html( $review['title'] ); ?></strong><br>
				<?php endif; ?>
				<?php echo wp_kses_post( wp_trim_words( $review['content'], 20 ) ); ?>
				<div class="row-actions">
					<?php echo wp_kses_post( $this->render_row_actions( $review, $current_status ) ); ?>
				</div>
			</td>
			<td class="column-status" data-colname="<?php esc_attr_e( 'Status', 'damdir-directory' ); ?>">
				<?php echo wp_kses_post( $this->render_status_badge( $review['status'] ) ); ?>
			</td>
			<td class="column-date" data-colname="<?php esc_attr_e( 'Date', 'damdir-directory' ); ?>">
				<?php echo esc_html( $review['date_formatted'] ); ?>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render star rating display.
	 *
	 * @since 1.0.0
	 *
	 * @param int $rating Rating value 1-5.
	 * @return string HTML for stars.
	 */
	private function render_stars( int $rating ): string {
		$output = '<span class="apd-stars" aria-label="' . sprintf(
			/* translators: %d: Rating number */
			esc_attr__( '%d out of 5 stars', 'damdir-directory' ),
			$rating
		) . '">';

		for ( $i = 1; $i <= 5; $i++ ) {
			$class   = $i <= $rating ? 'dashicons-star-filled' : 'dashicons-star-empty';
			$output .= '<span class="dashicons ' . $class . '" aria-hidden="true"></span>';
		}

		$output .= '</span>';

		return $output;
	}

	/**
	 * Render status badge.
	 *
	 * @since 1.0.0
	 *
	 * @param string $status Review status.
	 * @return string HTML for badge.
	 */
	private function render_status_badge( string $status ): string {
		$labels = [
			'approved' => __( 'Approved', 'damdir-directory' ),
			'pending'  => __( 'Pending', 'damdir-directory' ),
			'spam'     => __( 'Spam', 'damdir-directory' ),
			'trash'    => __( 'Trash', 'damdir-directory' ),
		];

		$label = $labels[ $status ] ?? ucfirst( $status );

		return sprintf(
			'<span class="apd-review-status apd-review-status--%s">%s</span>',
			esc_attr( $status ),
			esc_html( $label )
		);
	}

	/**
	 * Render row actions for a review.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $review         Review data.
	 * @param string $current_status Current status filter.
	 * @return string HTML for row actions.
	 */
	private function render_row_actions( array $review, string $current_status ): string {
		$actions  = [];
		$base_url = admin_url( 'edit.php?post_type=apd_listing&page=' . self::PAGE_SLUG );

		// Approve action (for pending/spam/trash).
		if ( $review['status'] !== 'approved' ) {
			$approve_url        = wp_nonce_url(
				add_query_arg(
					[
						'action'    => 'approve',
						'review_id' => $review['id'],
						'status'    => $current_status,
					],
					$base_url
				),
				'apd_review_action_' . $review['id']
			);
			$actions['approve'] = sprintf(
				'<a href="%s" class="apd-action-approve" aria-label="%s">%s</a>',
				esc_url( $approve_url ),
				/* translators: %s: Review ID */
				esc_attr( sprintf( __( 'Approve review %s', 'damdir-directory' ), $review['id'] ) ),
				esc_html__( 'Approve', 'damdir-directory' )
			);
		}

		// Unapprove action (for approved).
		if ( $review['status'] === 'approved' ) {
			$unapprove_url        = wp_nonce_url(
				add_query_arg(
					[
						'action'    => 'unapprove',
						'review_id' => $review['id'],
						'status'    => $current_status,
					],
					$base_url
				),
				'apd_review_action_' . $review['id']
			);
			$actions['unapprove'] = sprintf(
				'<a href="%s" class="apd-action-unapprove" aria-label="%s">%s</a>',
				esc_url( $unapprove_url ),
				/* translators: %s: Review ID */
				esc_attr( sprintf( __( 'Unapprove review %s', 'damdir-directory' ), $review['id'] ) ),
				esc_html__( 'Unapprove', 'damdir-directory' )
			);
		}

		// Spam action (for non-spam).
		if ( $review['status'] !== 'spam' && $review['status'] !== 'trash' ) {
			$spam_url        = wp_nonce_url(
				add_query_arg(
					[
						'action'    => 'spam',
						'review_id' => $review['id'],
						'status'    => $current_status,
					],
					$base_url
				),
				'apd_review_action_' . $review['id']
			);
			$actions['spam'] = sprintf(
				'<a href="%s" class="apd-action-spam" aria-label="%s">%s</a>',
				esc_url( $spam_url ),
				/* translators: %s: Review ID */
				esc_attr( sprintf( __( 'Mark review %s as spam', 'damdir-directory' ), $review['id'] ) ),
				esc_html__( 'Spam', 'damdir-directory' )
			);
		}

		// Trash action (for non-trash).
		if ( $review['status'] !== 'trash' ) {
			$trash_url        = wp_nonce_url(
				add_query_arg(
					[
						'action'    => 'trash',
						'review_id' => $review['id'],
						'status'    => $current_status,
					],
					$base_url
				),
				'apd_review_action_' . $review['id']
			);
			$actions['trash'] = sprintf(
				'<a href="%s" class="apd-action-trash" aria-label="%s">%s</a>',
				esc_url( $trash_url ),
				/* translators: %s: Review ID */
				esc_attr( sprintf( __( 'Move review %s to trash', 'damdir-directory' ), $review['id'] ) ),
				esc_html__( 'Trash', 'damdir-directory' )
			);
		}

		// Restore action (for trash).
		if ( $review['status'] === 'trash' ) {
			$restore_url        = wp_nonce_url(
				add_query_arg(
					[
						'action'    => 'restore',
						'review_id' => $review['id'],
						'status'    => $current_status,
					],
					$base_url
				),
				'apd_review_action_' . $review['id']
			);
			$actions['restore'] = sprintf(
				'<a href="%s" class="apd-action-restore" aria-label="%s">%s</a>',
				esc_url( $restore_url ),
				/* translators: %s: Review ID */
				esc_attr( sprintf( __( 'Restore review %s', 'damdir-directory' ), $review['id'] ) ),
				esc_html__( 'Restore', 'damdir-directory' )
			);

			// Permanent delete.
			$delete_url        = wp_nonce_url(
				add_query_arg(
					[
						'action'    => 'delete',
						'review_id' => $review['id'],
						'status'    => $current_status,
					],
					$base_url
				),
				'apd_review_action_' . $review['id']
			);
			$actions['delete'] = sprintf(
				'<a href="%s" class="apd-action-delete submitdelete" aria-label="%s">%s</a>',
				esc_url( $delete_url ),
				/* translators: %s: Review ID */
				esc_attr( sprintf( __( 'Delete review %s permanently', 'damdir-directory' ), $review['id'] ) ),
				esc_html__( 'Delete Permanently', 'damdir-directory' )
			);
		}

		// View listing action.
		$listing = get_post( $review['listing_id'] );
		if ( $listing ) {
			$view_url = get_permalink( $listing->ID );
			if ( $view_url ) {
				$actions['view'] = sprintf(
					'<a href="%s" target="_blank" rel="noopener noreferrer" aria-label="%s">%s</a>',
					esc_url( $view_url ),
					esc_attr__( 'View listing in new tab', 'damdir-directory' ),
					esc_html__( 'View Listing', 'damdir-directory' )
				);
			}
		}

		return implode( ' | ', $actions );
	}

	/**
	 * Render status filter dropdown.
	 *
	 * @since 1.0.0
	 *
	 * @param string $current_status Currently selected status.
	 * @return string HTML for the filter.
	 */
	private function render_status_filter( string $current_status ): string {
		$statuses = [
			'all'      => __( 'All Statuses', 'damdir-directory' ),
			'pending'  => __( 'Pending', 'damdir-directory' ),
			'approved' => __( 'Approved', 'damdir-directory' ),
			'spam'     => __( 'Spam', 'damdir-directory' ),
			'trash'    => __( 'Trash', 'damdir-directory' ),
		];

		$output  = '<label for="filter-by-status" class="screen-reader-text">' .
			esc_html__( 'Filter by status', 'damdir-directory' ) . '</label>';
		$output .= '<select name="status" id="filter-by-status">';

		foreach ( $statuses as $value => $label ) {
			$output .= sprintf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $value ),
				selected( $current_status, $value, false ),
				esc_html( $label )
			);
		}

		$output .= '</select>';

		return $output;
	}

	/**
	 * Render listing filter dropdown.
	 *
	 * @since 1.0.0
	 *
	 * @param int $current_listing Currently selected listing ID.
	 * @return string HTML for the filter.
	 */
	private function render_listing_filter( int $current_listing ): string {
		$listings = get_posts(
			[
				'post_type'      => 'apd_listing',
				'posts_per_page' => 100,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'post_status'    => 'any',
				'no_found_rows'  => true, // Performance: skip counting total rows.
			]
		);

		if ( empty( $listings ) ) {
			return '';
		}

		$output  = '<label for="filter-by-listing" class="screen-reader-text">' .
			esc_html__( 'Filter by listing', 'damdir-directory' ) . '</label>';
		$output .= '<select name="listing_id" id="filter-by-listing">';
		$output .= '<option value="">' . esc_html__( 'All Listings', 'damdir-directory' ) . '</option>';

		foreach ( $listings as $listing ) {
			$output .= sprintf(
				'<option value="%d" %s>%s</option>',
				$listing->ID,
				selected( $current_listing, $listing->ID, false ),
				esc_html( wp_trim_words( $listing->post_title, 8 ) )
			);
		}

		$output .= '</select>';

		return $output;
	}

	/**
	 * Render rating filter dropdown.
	 *
	 * @since 1.0.0
	 *
	 * @param int $current_rating Currently selected rating.
	 * @return string HTML for the filter.
	 */
	private function render_rating_filter( int $current_rating ): string {
		$output  = '<label for="filter-by-rating" class="screen-reader-text">' .
			esc_html__( 'Filter by rating', 'damdir-directory' ) . '</label>';
		$output .= '<select name="rating" id="filter-by-rating">';
		$output .= '<option value="">' . esc_html__( 'All Ratings', 'damdir-directory' ) . '</option>';

		for ( $i = 5; $i >= 1; $i-- ) {
			$label = sprintf(
				/* translators: %d: Star rating number */
				_n( '%d Star', '%d Stars', $i, 'damdir-directory' ),
				$i
			);
			$output .= sprintf(
				'<option value="%d" %s>%s</option>',
				$i,
				selected( $current_rating, $i, false ),
				esc_html( $label )
			);
		}

		$output .= '</select>';

		return $output;
	}

	/**
	 * Render pagination.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $current_page Current page number.
	 * @param int    $total_pages  Total number of pages.
	 * @param int    $total_items  Total number of items.
	 * @param string $position     Position (top or bottom).
	 * @return string HTML for pagination.
	 */
	private function render_pagination( int $current_page, int $total_pages, int $total_items, string $position ): string {
		if ( $total_pages <= 1 ) {
			return '<div class="tablenav-pages one-page"><span class="displaying-num">' .
				sprintf(
					/* translators: %s: Number of items */
					_n( '%s item', '%s items', $total_items, 'damdir-directory' ),
					number_format_i18n( $total_items )
				) . '</span></div>';
		}

		$output  = '<div class="tablenav-pages">';
		$output .= '<span class="displaying-num">' . sprintf(
			/* translators: %s: Number of items */
			_n( '%s item', '%s items', $total_items, 'damdir-directory' ),
			number_format_i18n( $total_items )
		) . '</span>';

		$output .= '<span class="pagination-links">';

		// First page.
		if ( $current_page > 1 ) {
			$output .= sprintf(
				'<a class="first-page button" href="%s"><span class="screen-reader-text">%s</span><span aria-hidden="true">%s</span></a>',
				esc_url( remove_query_arg( 'paged' ) ),
				esc_html__( 'First page', 'damdir-directory' ),
				'&laquo;'
			);
			$output .= sprintf(
				'<a class="prev-page button" href="%s"><span class="screen-reader-text">%s</span><span aria-hidden="true">%s</span></a>',
				esc_url( add_query_arg( 'paged', $current_page - 1 ) ),
				esc_html__( 'Previous page', 'damdir-directory' ),
				'&lsaquo;'
			);
		} else {
			$output .= '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span>';
			$output .= '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>';
		}

		$output .= '<span class="paging-input">';
		$output .= '<label for="current-page-selector-' . $position . '" class="screen-reader-text">' .
			esc_html__( 'Current Page', 'damdir-directory' ) . '</label>';
		$output .= '<span class="tablenav-paging-text">' .
			sprintf(
				/* translators: 1: Current page, 2: Total pages */
				__( '%1$s of %2$s', 'damdir-directory' ),
				'<span class="current-page">' . number_format_i18n( $current_page ) . '</span>',
				'<span class="total-pages">' . number_format_i18n( $total_pages ) . '</span>'
			) . '</span>';
		$output .= '</span>';

		// Last page.
		if ( $current_page < $total_pages ) {
			$output .= sprintf(
				'<a class="next-page button" href="%s"><span class="screen-reader-text">%s</span><span aria-hidden="true">%s</span></a>',
				esc_url( add_query_arg( 'paged', $current_page + 1 ) ),
				esc_html__( 'Next page', 'damdir-directory' ),
				'&rsaquo;'
			);
			$output .= sprintf(
				'<a class="last-page button" href="%s"><span class="screen-reader-text">%s</span><span aria-hidden="true">%s</span></a>',
				esc_url( add_query_arg( 'paged', $total_pages ) ),
				esc_html__( 'Last page', 'damdir-directory' ),
				'&raquo;'
			);
		} else {
			$output .= '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>';
			$output .= '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&raquo;</span>';
		}

		$output .= '</span></div>';

		return $output;
	}

	/**
	 * Render admin messages.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function render_messages(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display only.
		$message_type = isset( $_GET['message'] ) ? sanitize_key( $_GET['message'] ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$count = isset( $_GET['count'] ) ? absint( $_GET['count'] ) : 0;

		$messages = [
			'approved'   => sprintf(
				/* translators: %d: Number of reviews */
				_n( '%d review approved.', '%d reviews approved.', $count, 'damdir-directory' ),
				$count
			),
			'unapproved' => sprintf(
				/* translators: %d: Number of reviews */
				_n( '%d review unapproved.', '%d reviews unapproved.', $count, 'damdir-directory' ),
				$count
			),
			'spammed'    => sprintf(
				/* translators: %d: Number of reviews */
				_n( '%d review marked as spam.', '%d reviews marked as spam.', $count, 'damdir-directory' ),
				$count
			),
			'trashed'    => sprintf(
				/* translators: %d: Number of reviews */
				_n( '%d review moved to trash.', '%d reviews moved to trash.', $count, 'damdir-directory' ),
				$count
			),
			'restored'   => sprintf(
				/* translators: %d: Number of reviews */
				_n( '%d review restored.', '%d reviews restored.', $count, 'damdir-directory' ),
				$count
			),
			'deleted'    => sprintf(
				/* translators: %d: Number of reviews */
				_n( '%d review permanently deleted.', '%d reviews permanently deleted.', $count, 'damdir-directory' ),
				$count
			),
		];

		if ( $message_type && isset( $messages[ $message_type ] ) && $count > 0 ) {
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				esc_html( $messages[ $message_type ] )
			);
		}
	}

	/**
	 * Handle admin actions.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function handle_actions(): void {
		// Check we're on the right page.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Just checking page.
		if ( ! isset( $_GET['page'] ) || sanitize_text_field( wp_unslash( $_GET['page'] ) ) !== self::PAGE_SLUG ) {
			return;
		}

		// Handle single action.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['action'] ) && isset( $_GET['review_id'] ) ) {
			$this->handle_single_action();
			return;
		}

		// Handle bulk action.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified inside handle_bulk_action.
		if ( isset( $_POST[ self::NONCE_NAME ] ) ) {
			$this->handle_bulk_action();
		}
	}

	/**
	 * Handle single review action.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function handle_single_action(): void {
		$action    = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : '';
		$review_id = isset( $_GET['review_id'] ) ? absint( $_GET['review_id'] ) : 0;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$status = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : 'all';

		// Verify nonce.
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'apd_review_action_' . $review_id ) ) {
			wp_die( esc_html__( 'Security check failed.', 'damdir-directory' ) );
		}

		// Check permissions.
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'damdir-directory' ) );
		}

		$success = $this->process_action( $action, $review_id );
		$message = $this->get_action_message( $action );

		$redirect_url = add_query_arg(
			Url::encode_deep(
				[
					'post_type' => 'apd_listing',
					'page'      => self::PAGE_SLUG,
					'status'    => $status,
					'message'   => $success ? $message : '',
					'count'     => $success ? 1 : 0,
				]
			),
			admin_url( 'edit.php' )
		);

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Handle bulk action.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function handle_bulk_action(): void {
		// Verify nonce.
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ?? '' ) ), self::NONCE_ACTION ) ) {
			wp_die( esc_html__( 'Security check failed.', 'damdir-directory' ) );
		}

		// Check permissions.
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'damdir-directory' ) );
		}

		// Get action from either selector.
		$action = '';
		if ( ! empty( $_POST['bulk_action'] ) ) {
			$action = sanitize_key( $_POST['bulk_action'] );
		} elseif ( ! empty( $_POST['bulk_action2'] ) ) {
			$action = sanitize_key( $_POST['bulk_action2'] );
		}

		if ( empty( $action ) ) {
			return;
		}

		// Get selected reviews.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Array of IDs sanitized below.
		$review_ids = isset( $_POST['review_ids'] ) && is_array( $_POST['review_ids'] )
			? array_map( 'absint', $_POST['review_ids'] )
			: [];

		if ( empty( $review_ids ) ) {
			return;
		}

		$success_count = 0;

		foreach ( $review_ids as $review_id ) {
			if ( $this->process_action( $action, $review_id ) ) {
				++$success_count;
			}
		}

		$message = $this->get_action_message( $action );
		$status  = isset( $_POST['current_status'] ) ? sanitize_key( $_POST['current_status'] ) : 'all';

		$redirect_url = add_query_arg(
			Url::encode_deep(
				[
					'post_type' => 'apd_listing',
					'page'      => self::PAGE_SLUG,
					'status'    => $status,
					'message'   => $success_count > 0 ? $message : '',
					'count'     => $success_count,
				]
			),
			admin_url( 'edit.php' )
		);

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Process a review action.
	 *
	 * @since 1.0.0
	 *
	 * @param string $action    Action to perform.
	 * @param int    $review_id Review ID.
	 * @return bool True on success.
	 */
	private function process_action( string $action, int $review_id ): bool {
		switch ( $action ) {
			case 'approve':
				return $this->review_manager->approve( $review_id );

			case 'unapprove':
				return (bool) wp_set_comment_status( $review_id, 'hold' );

			case 'spam':
				return (bool) wp_set_comment_status( $review_id, 'spam' );

			case 'trash':
				return $this->review_manager->reject( $review_id );

			case 'restore':
				return (bool) wp_set_comment_status( $review_id, 'hold' );

			case 'delete':
				return $this->review_manager->delete( $review_id, true );

			default:
				return false;
		}
	}

	/**
	 * Get message key for an action.
	 *
	 * @since 1.0.0
	 *
	 * @param string $action Action name.
	 * @return string Message key.
	 */
	private function get_action_message( string $action ): string {
		$messages = [
			'approve'   => 'approved',
			'unapprove' => 'unapproved',
			'spam'      => 'spammed',
			'trash'     => 'trashed',
			'restore'   => 'restored',
			'delete'    => 'deleted',
		];

		return $messages[ $action ] ?? '';
	}

	/**
	 * Get reviews with filters.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Query arguments.
	 *                    - status: (string) Review status. Default 'all'.
	 *                    - listing_id: (int) Filter by listing ID.
	 *                    - rating: (int) Filter by rating.
	 *                    - search: (string) Search query.
	 *                    - paged: (int) Page number. Default 1.
	 * @return array{reviews: array[], total: int, pages: int} Reviews data.
	 */
	public function get_reviews( array $args = [] ): array {
		$defaults = [
			'status'     => 'all',
			'listing_id' => 0,
			'rating'     => 0,
			'search'     => '',
			'paged'      => 1,
		];

		$args = wp_parse_args( $args, $defaults );

		$query_args = [
			'type'    => ReviewManager::COMMENT_TYPE,
			'number'  => self::PER_PAGE,
			'offset'  => ( $args['paged'] - 1 ) * self::PER_PAGE,
			'orderby' => 'comment_date',
			'order'   => 'DESC',
		];

		// Handle status.
		if ( $args['status'] !== 'all' ) {
			$query_args['status'] = $this->translate_status( $args['status'] );
		} else {
			$query_args['status'] = 'all';
		}

		// Handle listing filter.
		if ( $args['listing_id'] > 0 ) {
			$query_args['post_id'] = $args['listing_id'];
		}

		// Handle rating filter.
		if ( $args['rating'] > 0 ) {
			$query_args['meta_query'] = [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Rating filter requires meta query.
				[
					'key'     => ReviewManager::META_RATING,
					'value'   => $args['rating'],
					'compare' => '=',
					'type'    => 'NUMERIC',
				],
			];
		}

		// Handle search.
		if ( ! empty( $args['search'] ) ) {
			$query_args['search'] = $args['search'];
		}

		// Get comments.
		$comments = get_comments( $query_args );

		// Get total count.
		$count_args           = $query_args;
		$count_args['count']  = true;
		$count_args['number'] = 0;
		$count_args['offset'] = 0;
		$total                = get_comments( $count_args );

		// Format reviews.
		$reviews = [];
		foreach ( $comments as $comment ) {
			$review = $this->review_manager->get( (int) $comment->comment_ID );
			if ( $review ) {
				$reviews[] = $review;
			}
		}

		// Calculate pages (minimum 1 page).
		$pages = self::PER_PAGE > 0 ? max( 1, (int) ceil( $total / self::PER_PAGE ) ) : 1;

		return [
			'reviews' => $reviews,
			'total'   => (int) $total,
			'pages'   => $pages,
		];
	}

	/**
	 * Get count of pending reviews.
	 *
	 * @since 1.0.0
	 *
	 * @return int Pending review count.
	 */
	public function get_pending_count(): int {
		return (int) get_comments(
			[
				'type'   => ReviewManager::COMMENT_TYPE,
				'status' => 'hold',
				'count'  => true,
			]
		);
	}

	/**
	 * Get counts for all statuses.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, int> Counts by status.
	 */
	private function get_status_counts(): array {
		$statuses = [
			'all'      => 'all',
			'pending'  => 'hold',
			'approved' => 'approve',
			'spam'     => 'spam',
			'trash'    => 'trash',
		];

		$counts = [];

		foreach ( $statuses as $key => $wp_status ) {
			$counts[ $key ] = (int) get_comments(
				[
					'type'   => ReviewManager::COMMENT_TYPE,
					'status' => $wp_status,
					'count'  => true,
				]
			);
		}

		return $counts;
	}

	/**
	 * Translate status string to WordPress comment status.
	 *
	 * @since 1.0.0
	 *
	 * @param string $status Status string.
	 * @return string WordPress comment status.
	 */
	private function translate_status( string $status ): string {
		return match ( $status ) {
			'approved' => 'approve',
			'pending'  => 'hold',
			'spam'     => 'spam',
			'trash'    => 'trash',
			'all'      => 'all',
			default    => 'all',
		};
	}

	/**
	 * Get allowed HTML for filter dropdowns.
	 *
	 * @since 1.0.0
	 *
	 * @return array Allowed HTML elements and attributes.
	 */
	private function get_allowed_filter_html(): array {
		return [
			'label'  => [
				'for'   => true,
				'class' => true,
			],
			'select' => [
				'name' => true,
				'id'   => true,
			],
			'option' => [
				'value'    => true,
				'selected' => true,
			],
		];
	}
}
