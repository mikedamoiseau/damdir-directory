<?php
/**
 * Review Display Class.
 *
 * Handles rendering of reviews on single listing pages including
 * reviews list, rating summary, pagination, and empty states.
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
 * Class ReviewDisplay
 *
 * @since 1.0.0
 */
class ReviewDisplay {

	/**
	 * Default number of reviews per page.
	 *
	 * @var int
	 */
	public const DEFAULT_PER_PAGE = 10;

	/**
	 * URL parameter for review pagination.
	 *
	 * @var string
	 */
	public const PAGE_PARAM = 'review_page';

	/**
	 * Singleton instance.
	 *
	 * @var ReviewDisplay|null
	 */
	private static ?ReviewDisplay $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @since 1.0.0
	 *
	 * @return ReviewDisplay
	 */
	public static function get_instance(): ReviewDisplay {
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
	 * Initialize the review display.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init(): void {
		if ( ! apd_reviews_enabled() ) {
			return;
		}

		// Hook into single listing reviews action to render reviews section.
		add_action( 'apd_single_listing_reviews', [ $this, 'render_reviews_section' ], 20, 1 );

		// Hook into listing meta to show rating summary.
		add_action( 'apd_single_listing_meta', [ $this, 'render_meta_rating' ], 10, 1 );

		/**
		 * Fires after the review display is initialized.
		 *
		 * @since 1.0.0
		 *
		 * @param ReviewDisplay $display The ReviewDisplay instance.
		 */
		do_action( 'apd_review_display_init', $this );
	}

	/**
	 * Render the complete reviews section.
	 *
	 * This is the main method that outputs the full reviews section including:
	 * - Section heading with count
	 * - Rating summary box
	 * - Review form
	 * - Reviews list
	 * - Pagination
	 *
	 * @since 1.0.0
	 *
	 * @param int $listing_id Listing post ID.
	 * @return void
	 */
	public function render_reviews_section( int $listing_id ): void {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML escaped in review templates via esc_html/esc_attr/wp_kses_post.
		echo $this->render( $listing_id );
	}

	/**
	 * Render the full reviews section.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $listing_id Listing post ID.
	 * @param array $args       Optional display arguments.
	 *                          - per_page: (int) Reviews per page. Default 10.
	 *                          - show_summary: (bool) Show rating summary. Default true.
	 *                          - show_form: (bool) Show review form. Default true.
	 *                          - show_pagination: (bool) Show pagination. Default true.
	 * @return string HTML output.
	 */
	public function render( int $listing_id, array $args = [] ): string {
		$defaults = [
			'per_page'        => $this->get_per_page(),
			'show_summary'    => true,
			'show_form'       => true,
			'show_pagination' => true,
		];

		$args = wp_parse_args( $args, $defaults );

		$calculator   = RatingCalculator::get_instance();
		$manager      = ReviewManager::get_instance();
		$review_count = $calculator->get_count( $listing_id );

		// Get current page.
		$current_page = $this->get_current_page();

		// Get reviews for current page.
		$offset  = ( $current_page - 1 ) * $args['per_page'];
		$reviews = $manager->get_listing_reviews(
			$listing_id,
			[
				'number' => $args['per_page'],
				'offset' => $offset,
			]
		);

		// Build template data.
		$template_data = [
			'listing_id'   => $listing_id,
			'review_count' => $review_count,
			'current_page' => $current_page,
			'total_pages'  => $reviews['pages'],
			'reviews'      => $reviews['reviews'],
			'args'         => $args,
			'has_reviews'  => $review_count > 0,
		];

		/**
		 * Filter the reviews section template data.
		 *
		 * @since 1.0.0
		 *
		 * @param array $template_data Template data.
		 * @param int   $listing_id    Listing post ID.
		 */
		$template_data = apply_filters( 'apd_reviews_section_data', $template_data, $listing_id );

		ob_start();
		\apd_get_template( 'review/reviews-section.php', $template_data );
		return ob_get_clean();
	}

	/**
	 * Render the rating summary box.
	 *
	 * @since 1.0.0
	 *
	 * @param int $listing_id Listing post ID.
	 * @return string HTML output.
	 */
	public function render_summary( int $listing_id ): string {
		$calculator   = RatingCalculator::get_instance();
		$average      = $calculator->get_average( $listing_id );
		$count        = $calculator->get_count( $listing_id );
		$distribution = $calculator->get_distribution( $listing_id );
		$star_count   = $calculator->get_star_count();

		// Calculate percentages for distribution bars.
		$distribution_data = [];
		for ( $i = $star_count; $i >= 1; $i-- ) {
			$star_count_val          = $distribution[ $i ] ?? 0;
			$distribution_data[ $i ] = [
				'count'      => $star_count_val,
				'percentage' => $count > 0 ? round( ( $star_count_val / $count ) * 100 ) : 0,
			];
		}

		$template_data = [
			'listing_id'   => $listing_id,
			'average'      => $average,
			'count'        => $count,
			'star_count'   => $star_count,
			'distribution' => $distribution_data,
		];

		/**
		 * Filter the rating summary template data.
		 *
		 * @since 1.0.0
		 *
		 * @param array $template_data Template data.
		 * @param int   $listing_id    Listing post ID.
		 */
		$template_data = apply_filters( 'apd_rating_summary_data', $template_data, $listing_id );

		ob_start();
		\apd_get_template( 'review/rating-summary.php', $template_data );
		return ob_get_clean();
	}

	/**
	 * Render the reviews list.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $listing_id Listing post ID.
	 * @param array $args       Query arguments.
	 *                          - number: (int) Number of reviews. Default 10.
	 *                          - offset: (int) Offset. Default 0.
	 *                          - orderby: (string) Order by (date, rating). Default 'date'.
	 *                          - order: (string) Order direction. Default 'DESC'.
	 * @return string HTML output.
	 */
	public function render_reviews_list( int $listing_id, array $args = [] ): string {
		$defaults = [
			'number'  => $this->get_per_page(),
			'offset'  => 0,
			'orderby' => 'date',
			'order'   => 'DESC',
		];

		$args    = wp_parse_args( $args, $defaults );
		$manager = ReviewManager::get_instance();
		$reviews = $manager->get_listing_reviews( $listing_id, $args );

		if ( empty( $reviews['reviews'] ) ) {
			ob_start();
			\apd_get_template(
				'review/reviews-empty.php',
				[
					'listing_id' => $listing_id,
				]
			);
			return ob_get_clean();
		}

		$template_data = [
			'listing_id' => $listing_id,
			'reviews'    => $reviews['reviews'],
			'total'      => $reviews['total'],
		];

		/**
		 * Filter the reviews list template data.
		 *
		 * @since 1.0.0
		 *
		 * @param array $template_data Template data.
		 * @param int   $listing_id    Listing post ID.
		 */
		$template_data = apply_filters( 'apd_reviews_list_data', $template_data, $listing_id );

		ob_start();

		echo '<div class="apd-reviews-list" aria-label="' . esc_attr__( 'Reviews list', 'all-purpose-directory' ) . '">';

		foreach ( $reviews['reviews'] as $review ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped in render method.
			echo $this->render_single_review( $review );
		}

		echo '</div>';

		return ob_get_clean();
	}

	/**
	 * Render a single review.
	 *
	 * @since 1.0.0
	 *
	 * @param array $review Review data array.
	 * @return string HTML output.
	 */
	public function render_single_review( array $review ): string {
		// Get author avatar.
		$avatar_url = '';
		if ( $review['author_id'] > 0 ) {
			$avatar_url = get_avatar_url( $review['author_id'], [ 'size' => 96 ] );
		} elseif ( ! empty( $review['author_email'] ) ) {
			$avatar_url = get_avatar_url( $review['author_email'], [ 'size' => 96 ] );
		}

		$template_data = [
			'review'     => $review,
			'avatar_url' => $avatar_url,
		];

		/**
		 * Filter the single review template data.
		 *
		 * @since 1.0.0
		 *
		 * @param array $template_data Template data.
		 * @param array $review        Review data.
		 */
		$template_data = apply_filters( 'apd_single_review_data', $template_data, $review );

		ob_start();
		\apd_get_template( 'review/review-item.php', $template_data );
		return ob_get_clean();
	}

	/**
	 * Render pagination for reviews.
	 *
	 * @since 1.0.0
	 *
	 * @param int $listing_id   Listing post ID.
	 * @param int $current_page Current page number.
	 * @param int $total_pages  Total number of pages.
	 * @return string HTML output.
	 */
	public function render_pagination( int $listing_id, int $current_page, int $total_pages ): string {
		if ( $total_pages <= 1 ) {
			return '';
		}

		$base_url = get_permalink( $listing_id );

		$template_data = [
			'listing_id'   => $listing_id,
			'current_page' => $current_page,
			'total_pages'  => $total_pages,
			'base_url'     => $base_url,
			'page_param'   => self::PAGE_PARAM,
		];

		/**
		 * Filter the reviews pagination template data.
		 *
		 * @since 1.0.0
		 *
		 * @param array $template_data Template data.
		 * @param int   $listing_id    Listing post ID.
		 */
		$template_data = apply_filters( 'apd_reviews_pagination_data', $template_data, $listing_id );

		ob_start();
		\apd_get_template( 'review/reviews-pagination.php', $template_data );
		return ob_get_clean();
	}

	/**
	 * Render rating display in listing meta.
	 *
	 * Shows a compact rating display in the listing header.
	 *
	 * @since 1.0.0
	 *
	 * @param int $listing_id Listing post ID.
	 * @return void
	 */
	public function render_meta_rating( int $listing_id ): void {
		$calculator = RatingCalculator::get_instance();
		$count      = $calculator->get_count( $listing_id );

		// Don't show if no reviews.
		if ( $count === 0 ) {
			return;
		}

		$average = $calculator->get_average( $listing_id );

		// Render compact star rating.
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Output escaped in render_stars().
		echo $calculator->render_stars(
			$average,
			[
				'size'         => 'small',
				'show_count'   => true,
				'show_average' => true,
				'count'        => $count,
			]
		);
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Get the current review page number.
	 *
	 * @since 1.0.0
	 *
	 * @return int Current page number (1-indexed).
	 */
	public function get_current_page(): int {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display only, no action taken.
		$page = isset( $_GET[ self::PAGE_PARAM ] ) ? absint( $_GET[ self::PAGE_PARAM ] ) : 1;

		return max( 1, $page );
	}

	/**
	 * Get the number of reviews per page.
	 *
	 * @since 1.0.0
	 *
	 * @return int Reviews per page.
	 */
	public function get_per_page(): int {
		/**
		 * Filter the number of reviews per page.
		 *
		 * @since 1.0.0
		 *
		 * @param int $per_page Reviews per page. Default 10.
		 */
		return apply_filters( 'apd_reviews_per_page', self::DEFAULT_PER_PAGE );
	}

	/**
	 * Build pagination URL.
	 *
	 * @since 1.0.0
	 *
	 * @param string $base_url Base URL.
	 * @param int    $page     Page number.
	 * @return string Full URL with page parameter.
	 */
	public function build_pagination_url( string $base_url, int $page ): string {
		if ( $page <= 1 ) {
			return remove_query_arg( self::PAGE_PARAM, $base_url );
		}

		return add_query_arg( self::PAGE_PARAM, $page, $base_url );
	}
}
