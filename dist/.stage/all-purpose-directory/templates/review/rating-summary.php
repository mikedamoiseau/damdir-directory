<?php
/**
 * Rating Summary Template.
 *
 * Template for rendering the rating summary box with average rating
 * and rating distribution bars.
 *
 * This template can be overridden by copying it to:
 * yourtheme/all-purpose-directory/review/rating-summary.php
 *
 * @package APD\Templates
 * @since   1.0.0
 *
 * @var int   $listing_id   Listing post ID.
 * @var float $average      Average rating.
 * @var int   $count        Total number of reviews.
 * @var int   $star_count   Maximum number of stars.
 * @var array $distribution Rating distribution with count and percentage per star level.
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$calculator = \APD\Review\RatingCalculator::get_instance();
?>

<div class="apd-rating-summary" aria-label="<?php esc_attr_e( 'Rating summary', 'all-purpose-directory' ); ?>">

	<div class="apd-rating-summary__overview">

		<div class="apd-rating-summary__average-container">
			<span class="apd-rating-summary__average-number">
				<?php echo esc_html( number_format( $average, 1 ) ); ?>
			</span>

			<div class="apd-rating-summary__stars">
				<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped in render_stars method.
				echo $calculator->render_stars(
					$average,
					[
						'size'   => 'medium',
						'inline' => true,
					]
				);
				?>
			</div>

			<span class="apd-rating-summary__count">
				<?php
				printf(
					/* translators: %d: number of reviews */
					esc_html( _n( '%d review', '%d reviews', $count, 'all-purpose-directory' ) ),
					esc_html( number_format_i18n( $count ) )
				);
				?>
			</span>
		</div>

	</div>

	<div class="apd-rating-summary__distribution" role="list" aria-label="<?php esc_attr_e( 'Rating breakdown', 'all-purpose-directory' ); ?>">
		<?php for ( $stars = $star_count; $stars >= 1; $stars-- ) : ?>
			<?php
			$data       = $distribution[ $stars ] ?? [
				'count'      => 0,
				'percentage' => 0,
			];
			$star_label = $stars === 1
				/* translators: %d: number of stars (singular) */
				? sprintf( __( '%d star', 'all-purpose-directory' ), $stars )
				/* translators: %d: number of stars (plural) */
				: sprintf( __( '%d stars', 'all-purpose-directory' ), $stars );
			?>
			<div class="apd-rating-summary__bar-row" role="listitem">
				<span class="apd-rating-summary__bar-label" aria-hidden="true">
					<?php echo esc_html( $stars ); ?>
					<span class="apd-rating-summary__bar-star" aria-hidden="true"></span>
				</span>
				<div class="apd-rating-summary__bar-track"
					role="progressbar"
					aria-label="<?php echo esc_attr( $star_label ); ?>"
					aria-valuenow="<?php echo esc_attr( $data['percentage'] ); ?>"
					aria-valuemin="0"
					aria-valuemax="100">
					<div class="apd-rating-summary__bar-fill"
						style="width: <?php echo esc_attr( $data['percentage'] ); ?>%;">
					</div>
				</div>
				<span class="apd-rating-summary__bar-count">
					<?php echo esc_html( number_format_i18n( $data['count'] ) ); ?>
				</span>
			</div>
		<?php endfor; ?>
	</div>

</div>
