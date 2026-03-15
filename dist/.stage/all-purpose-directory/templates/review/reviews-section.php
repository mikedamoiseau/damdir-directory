<?php
/**
 * Reviews Section Template.
 *
 * Template for rendering the complete reviews section on listing pages.
 * Includes rating summary, review form, reviews list, and pagination.
 *
 * This template can be overridden by copying it to:
 * yourtheme/all-purpose-directory/review/reviews-section.php
 *
 * @package APD\Templates
 * @since   1.0.0
 *
 * @var int   $listing_id   Listing post ID.
 * @var int   $review_count Total number of approved reviews.
 * @var int   $current_page Current page number.
 * @var int   $total_pages  Total number of pages.
 * @var array $reviews      Array of review data for current page.
 * @var array $args         Display arguments (per_page, show_summary, show_form, show_pagination).
 * @var bool  $has_reviews  Whether listing has any reviews.
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Set default values for args to prevent undefined array key warnings.
$args = wp_parse_args(
	$args ?? [],
	[
		'per_page'        => 10,
		'show_summary'    => true,
		'show_form'       => true,
		'show_pagination' => true,
	]
);

$display = \APD\Review\ReviewDisplay::get_instance();
?>

<section class="apd-reviews-section" id="reviews" aria-labelledby="apd-reviews-heading">

	<?php
	/**
	 * Fires at the start of the reviews section.
	 *
	 * @since 1.0.0
	 *
	 * @param int $listing_id The listing post ID.
	 */
	do_action( 'apd_reviews_section_start', $listing_id );
	?>

	<header class="apd-reviews-section__header">
		<h2 id="apd-reviews-heading" class="apd-reviews-section__title">
			<?php
			if ( $review_count > 0 ) {
				printf(
					/* translators: %d: number of reviews */
					esc_html( _n( 'Reviews (%d)', 'Reviews (%d)', $review_count, 'all-purpose-directory' ) ),
					esc_html( number_format_i18n( $review_count ) )
				);
			} else {
				esc_html_e( 'Reviews', 'all-purpose-directory' );
			}
			?>
		</h2>
	</header>

	<?php
	/**
	 * Fires after the reviews section header.
	 *
	 * @since 1.0.0
	 *
	 * @param int $listing_id The listing post ID.
	 */
	do_action( 'apd_reviews_section_after_header', $listing_id );
	?>

	<?php if ( $has_reviews && $args['show_summary'] ) : ?>
		<div class="apd-reviews-section__summary">
			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped in render_summary method.
			echo $display->render_summary( $listing_id );
			?>
		</div>
	<?php endif; ?>

	<?php
	/**
	 * Fires before the review form in reviews section.
	 *
	 * @since 1.0.0
	 *
	 * @param int $listing_id The listing post ID.
	 */
	do_action( 'apd_reviews_section_before_form', $listing_id );
	?>

	<?php if ( $args['show_form'] ) : ?>
		<div class="apd-reviews-section__form">
			<?php
			// The form is rendered via ReviewForm hooked to apd_single_listing_reviews.
			// We trigger a specific action here for the form.
			/**
			 * Fires where the review form should be rendered.
			 *
			 * @since 1.0.0
			 *
			 * @param int $listing_id The listing post ID.
			 */
			do_action( 'apd_render_review_form', $listing_id );
			?>
		</div>
	<?php endif; ?>

	<?php
	/**
	 * Fires after the review form in reviews section.
	 *
	 * @since 1.0.0
	 *
	 * @param int $listing_id The listing post ID.
	 */
	do_action( 'apd_reviews_section_after_form', $listing_id );
	?>

	<div class="apd-reviews-section__content">

		<?php if ( $has_reviews ) : ?>

			<div class="apd-reviews-list" role="list" aria-label="<?php esc_attr_e( 'Reviews', 'all-purpose-directory' ); ?>">
				<?php foreach ( $reviews as $review ) : ?>
					<?php
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped in render_single_review method.
					echo $display->render_single_review( $review );
					?>
				<?php endforeach; ?>
			</div>

			<?php if ( $args['show_pagination'] && $total_pages > 1 ) : ?>
				<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped in render_pagination method.
				echo $display->render_pagination( $listing_id, $current_page, $total_pages );
				?>
			<?php endif; ?>

		<?php else : ?>

			<?php apd_get_template( 'review/reviews-empty.php', [ 'listing_id' => $listing_id ] ); ?>

		<?php endif; ?>

	</div>

	<?php
	/**
	 * Fires at the end of the reviews section.
	 *
	 * @since 1.0.0
	 *
	 * @param int $listing_id The listing post ID.
	 */
	do_action( 'apd_reviews_section_end', $listing_id );
	?>

</section>
