<?php
/**
 * Rating Calculator Class.
 *
 * Calculates and caches average ratings for listings based on reviews.
 * Stores computed ratings in listing post meta for performance.
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
 * Class RatingCalculator
 *
 * @since 1.0.0
 */
class RatingCalculator {

	/**
	 * Meta key for average rating.
	 *
	 * @var string
	 */
	public const META_AVERAGE = '_apd_average_rating';

	/**
	 * Meta key for rating count.
	 *
	 * @var string
	 */
	public const META_COUNT = '_apd_rating_count';

	/**
	 * Meta key for rating distribution.
	 *
	 * @var string
	 */
	public const META_DISTRIBUTION = '_apd_rating_distribution';

	/**
	 * Default number of stars.
	 *
	 * @var int
	 */
	public const DEFAULT_STAR_COUNT = 5;

	/**
	 * Default decimal precision for average.
	 *
	 * @var int
	 */
	public const DEFAULT_PRECISION = 1;

	/**
	 * Singleton instance.
	 *
	 * @var RatingCalculator|null
	 */
	private static ?RatingCalculator $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @since 1.0.0
	 *
	 * @return RatingCalculator
	 */
	public static function get_instance(): RatingCalculator {
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
	 * Initialize the rating calculator.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init(): void {
		// Hook into review events to update ratings.
		add_action( 'apd_review_created', [ $this, 'on_review_change' ], 10, 2 );
		add_action( 'apd_review_updated', [ $this, 'on_review_updated' ], 10, 2 );
		add_action( 'apd_review_deleted', [ $this, 'on_review_deleted' ], 10, 2 );
		add_action( 'apd_review_approved', [ $this, 'on_review_status_change' ], 10, 1 );
		add_action( 'apd_review_rejected', [ $this, 'on_review_status_change' ], 10, 1 );

		/**
		 * Fires after the rating calculator is initialized.
		 *
		 * @since 1.0.0
		 *
		 * @param RatingCalculator $calculator The RatingCalculator instance.
		 */
		do_action( 'apd_rating_calculator_init', $this );
	}

	/**
	 * Calculate and store rating statistics for a listing.
	 *
	 * @since 1.0.0
	 *
	 * @param int $listing_id Listing post ID.
	 * @return array{average: float, count: int, distribution: array<int, int>} Rating statistics.
	 */
	public function calculate( int $listing_id ): array {
		// Get all approved reviews for the listing.
		$reviews = get_comments(
			[
				'post_id' => $listing_id,
				'type'    => ReviewManager::COMMENT_TYPE,
				'status'  => 'approve',
			]
		);

		$count        = 0;
		$total        = 0;
		$star_count   = $this->get_star_count();
		$distribution = array_fill( 1, $star_count, 0 );

		foreach ( $reviews as $review ) {
			$rating = (int) get_comment_meta( $review->comment_ID, ReviewManager::META_RATING, true );

			if ( $rating >= 1 && $rating <= $star_count ) {
				++$count;
				$total += $rating;
				++$distribution[ $rating ];
			}
		}

		// Calculate average.
		$average = $count > 0 ? $total / $count : 0.0;

		// Round to precision.
		$precision = $this->get_precision();
		$average   = round( $average, $precision );

		// Store in post meta.
		update_post_meta( $listing_id, self::META_AVERAGE, $average );
		update_post_meta( $listing_id, self::META_COUNT, $count );
		update_post_meta( $listing_id, self::META_DISTRIBUTION, $distribution );

		$stats = [
			'average'      => $average,
			'count'        => $count,
			'distribution' => $distribution,
		];

		/**
		 * Fires after rating statistics are calculated.
		 *
		 * @since 1.0.0
		 *
		 * @param int   $listing_id Listing post ID.
		 * @param array $stats      Rating statistics.
		 */
		do_action( 'apd_rating_calculated', $listing_id, $stats );

		return $stats;
	}

	/**
	 * Get the average rating for a listing.
	 *
	 * Returns cached value if available, otherwise calculates it.
	 *
	 * @since 1.0.0
	 *
	 * @param int $listing_id Listing post ID.
	 * @return float Average rating (0 if no ratings).
	 */
	public function get_average( int $listing_id ): float {
		$average = get_post_meta( $listing_id, self::META_AVERAGE, true );

		// If no cached value, calculate it.
		if ( $average === '' ) {
			$stats   = $this->calculate( $listing_id );
			$average = $stats['average'];
		}

		return (float) $average;
	}

	/**
	 * Get the rating count for a listing.
	 *
	 * Returns cached value if available, otherwise calculates it.
	 *
	 * @since 1.0.0
	 *
	 * @param int $listing_id Listing post ID.
	 * @return int Number of ratings.
	 */
	public function get_count( int $listing_id ): int {
		$count = get_post_meta( $listing_id, self::META_COUNT, true );

		// If no cached value, calculate it.
		if ( $count === '' ) {
			$stats = $this->calculate( $listing_id );
			$count = $stats['count'];
		}

		return (int) $count;
	}

	/**
	 * Get the rating distribution for a listing.
	 *
	 * Returns an array of counts per star rating.
	 *
	 * @since 1.0.0
	 *
	 * @param int $listing_id Listing post ID.
	 * @return array<int, int> Distribution array keyed by star rating.
	 */
	public function get_distribution( int $listing_id ): array {
		$distribution = get_post_meta( $listing_id, self::META_DISTRIBUTION, true );

		// If no cached value, calculate it.
		if ( ! is_array( $distribution ) ) {
			$stats        = $this->calculate( $listing_id );
			$distribution = $stats['distribution'];
		}

		return $distribution;
	}

	/**
	 * Force recalculation of rating statistics for a listing.
	 *
	 * @since 1.0.0
	 *
	 * @param int $listing_id Listing post ID.
	 * @return array{average: float, count: int, distribution: array<int, int>} Rating statistics.
	 */
	public function recalculate( int $listing_id ): array {
		return $this->calculate( $listing_id );
	}

	/**
	 * Recalculate ratings for all listings.
	 *
	 * Useful for maintenance or after bulk operations.
	 *
	 * @since 1.0.0
	 *
	 * @return int Number of listings processed.
	 */
	public function recalculate_all(): int {
		$listings = get_posts(
			[
				'post_type'      => 'apd_listing',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true, // Performance: skip counting total rows.
			]
		);

		$count = 0;

		foreach ( $listings as $listing_id ) {
			$this->calculate( $listing_id );
			++$count;
		}

		/**
		 * Fires after all listing ratings are recalculated.
		 *
		 * @since 1.0.0
		 *
		 * @param int $count Number of listings processed.
		 */
		do_action( 'apd_all_ratings_recalculated', $count );

		return $count;
	}

	/**
	 * Invalidate cached rating for a listing.
	 *
	 * Clears the cached values. Next call to get_average/get_count
	 * will trigger a recalculation.
	 *
	 * @since 1.0.0
	 *
	 * @param int $listing_id Listing post ID.
	 * @return void
	 */
	public function invalidate( int $listing_id ): void {
		delete_post_meta( $listing_id, self::META_AVERAGE );
		delete_post_meta( $listing_id, self::META_COUNT );
		delete_post_meta( $listing_id, self::META_DISTRIBUTION );

		/**
		 * Fires after rating cache is invalidated.
		 *
		 * @since 1.0.0
		 *
		 * @param int $listing_id Listing post ID.
		 */
		do_action( 'apd_rating_invalidated', $listing_id );
	}

	/**
	 * Get the number of stars to use in ratings.
	 *
	 * @since 1.0.0
	 *
	 * @return int Number of stars.
	 */
	public function get_star_count(): int {
		/**
		 * Filter the number of stars in the rating system.
		 *
		 * @since 1.0.0
		 *
		 * @param int $star_count Number of stars. Default 5.
		 */
		return apply_filters( 'apd_rating_star_count', self::DEFAULT_STAR_COUNT );
	}

	/**
	 * Get the decimal precision for average ratings.
	 *
	 * @since 1.0.0
	 *
	 * @return int Decimal places.
	 */
	public function get_precision(): int {
		/**
		 * Filter the decimal precision for average ratings.
		 *
		 * @since 1.0.0
		 *
		 * @param int $precision Decimal places. Default 1.
		 */
		return apply_filters( 'apd_rating_precision', self::DEFAULT_PRECISION );
	}

	/**
	 * Render star rating HTML.
	 *
	 * @since 1.0.0
	 *
	 * @param float $rating The rating value.
	 * @param array $args   Display options.
	 *                      - size: (string) Size variant (small, medium, large). Default 'medium'.
	 *                      - show_count: (bool) Whether to show rating count. Default false.
	 *                      - show_average: (bool) Whether to show average number. Default false.
	 *                      - count: (int) Number of ratings (for show_count).
	 *                      - inline: (bool) Display inline. Default true.
	 * @return string HTML output.
	 */
	public function render_stars( float $rating, array $args = [] ): string {
		$defaults = [
			'size'         => 'medium',
			'show_count'   => false,
			'show_average' => false,
			'count'        => 0,
			'inline'       => true,
		];

		$args = wp_parse_args( $args, $defaults );

		$star_count = $this->get_star_count();
		$precision  = $this->get_precision();

		// Ensure rating is within bounds.
		$rating = max( 0, min( $star_count, $rating ) );

		// Build CSS classes.
		$classes = [
			'apd-star-rating',
			'apd-star-rating--' . sanitize_html_class( $args['size'] ),
		];

		if ( $args['inline'] ) {
			$classes[] = 'apd-star-rating--inline';
		}

		// Build ARIA label.
		$aria_label = sprintf(
			/* translators: 1: rating value, 2: maximum rating (star count) */
			__( '%1$s out of %2$s stars', 'all-purpose-directory' ),
			number_format( $rating, $precision ),
			$star_count
		);

		if ( $args['show_count'] && $args['count'] > 0 ) {
			$aria_label .= sprintf(
				/* translators: %d: number of reviews */
				_n( ', based on %d review', ', based on %d reviews', $args['count'], 'all-purpose-directory' ),
				$args['count']
			);
		}

		// Start building HTML.
		$html = sprintf(
			'<div class="%s" role="img" aria-label="%s">',
			esc_attr( implode( ' ', $classes ) ),
			esc_attr( $aria_label )
		);

		// Add stars.
		for ( $i = 1; $i <= $star_count; $i++ ) {
			$star_class = 'apd-star';

			if ( $i <= floor( $rating ) ) {
				// Full star.
				$star_class .= ' apd-star--full';
			} elseif ( $i - 0.5 <= $rating ) {
				// Half star.
				$star_class .= ' apd-star--half';
			} else {
				// Empty star.
				$star_class .= ' apd-star--empty';
			}

			$html .= sprintf(
				'<span class="%s" aria-hidden="true"></span>',
				esc_attr( $star_class )
			);
		}

		// Add average number if requested.
		if ( $args['show_average'] && $rating > 0 ) {
			$html .= sprintf(
				'<span class="apd-star-rating__average">%s</span>',
				esc_html( number_format( $rating, $precision ) )
			);
		}

		// Add count if requested.
		if ( $args['show_count'] ) {
			$count_text = sprintf(
				/* translators: %d: number of reviews */
				_n( '(%d review)', '(%d reviews)', $args['count'], 'all-purpose-directory' ),
				$args['count']
			);
			$html .= sprintf(
				'<span class="apd-star-rating__count">%s</span>',
				esc_html( $count_text )
			);
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render star rating HTML for a listing.
	 *
	 * Convenience method that fetches rating data for a listing.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $listing_id Listing post ID.
	 * @param array $args       Display options (see render_stars).
	 * @return string HTML output.
	 */
	public function render_listing_stars( int $listing_id, array $args = [] ): string {
		$average = $this->get_average( $listing_id );
		$count   = $this->get_count( $listing_id );

		// If no ratings, optionally return empty.
		if ( $count === 0 ) {
			/**
			 * Filter whether to show empty star ratings.
			 *
			 * @since 1.0.0
			 *
			 * @param bool $show_empty Whether to show stars when there are no ratings.
			 * @param int  $listing_id Listing post ID.
			 */
			$show_empty = apply_filters( 'apd_show_empty_star_rating', false, $listing_id );

			if ( ! $show_empty ) {
				return '';
			}
		}

		// Set the count for display.
		$args['count'] = $count;

		return $this->render_stars( $average, $args );
	}

	/**
	 * Handle review creation event.
	 *
	 * @since 1.0.0
	 *
	 * @param int $comment_id Review (comment) ID.
	 * @param int $listing_id Listing post ID.
	 * @return void
	 */
	public function on_review_change( int $comment_id, int $listing_id ): void {
		$this->recalculate( $listing_id );
	}

	/**
	 * Handle review update event.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $review_id Review (comment) ID.
	 * @param array $data      Review data.
	 * @return void
	 */
	public function on_review_updated( int $review_id, array $data ): void {
		$comment = get_comment( $review_id );

		if ( $comment ) {
			$this->recalculate( (int) $comment->comment_post_ID );
		}
	}

	/**
	 * Handle review deletion event.
	 *
	 * @since 1.0.0
	 *
	 * @param int  $review_id    Review (comment) ID.
	 * @param bool $force_delete Whether permanently deleted.
	 * @return void
	 */
	public function on_review_deleted( int $review_id, bool $force_delete ): void {
		// We need to get the listing ID from the comment before it's fully deleted.
		// The hook fires after wp_delete_comment, so we need to use cached data.
		// For now, we'll rely on the listing ID being in the cache.
		// This is a limitation - permanent deletion may require tracking.

		// Try to get from object cache.
		$comment = get_comment( $review_id );

		if ( $comment ) {
			$this->recalculate( (int) $comment->comment_post_ID );
		}
	}

	/**
	 * Handle review status change (approve/reject).
	 *
	 * @since 1.0.0
	 *
	 * @param int $review_id Review (comment) ID.
	 * @return void
	 */
	public function on_review_status_change( int $review_id ): void {
		$comment = get_comment( $review_id );

		if ( $comment ) {
			$this->recalculate( (int) $comment->comment_post_ID );
		}
	}
}
