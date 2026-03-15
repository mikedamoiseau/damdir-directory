<?php
/**
 * Review Item Template.
 *
 * Template for rendering a single review in the reviews list.
 *
 * This template can be overridden by copying it to:
 * yourtheme/all-purpose-directory/review/review-item.php
 *
 * @package APD\Templates
 * @since   1.0.0
 *
 * @var array  $review     Review data array with keys:
 *                         - id: (int) Review ID.
 *                         - listing_id: (int) Listing post ID.
 *                         - author_id: (int) Author user ID (0 for guests).
 *                         - author_name: (string) Author display name.
 *                         - author_email: (string) Author email.
 *                         - rating: (int) Star rating (1-5).
 *                         - title: (string) Review title.
 *                         - content: (string) Review content.
 *                         - status: (string) Review status.
 *                         - date: (string) Review date (raw).
 *                         - date_formatted: (string) Formatted date.
 * @var string $avatar_url URL to author avatar image.
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$calculator = \APD\Review\RatingCalculator::get_instance();

// Get the star count for ARIA label.
$star_label = $review['rating'] === 1
	/* translators: %d: number of stars (singular) */
	? sprintf( __( '%d star', 'all-purpose-directory' ), $review['rating'] )
	/* translators: %d: number of stars (plural) */
	: sprintf( __( '%d stars', 'all-purpose-directory' ), $review['rating'] );
?>

<article class="apd-review-item" id="review-<?php echo esc_attr( $review['id'] ); ?>" role="listitem">

	<header class="apd-review-item__header">

		<div class="apd-review-item__author">
			<?php if ( $avatar_url ) : ?>
				<div class="apd-review-item__avatar">
					<img src="<?php echo esc_url( $avatar_url ); ?>"
						alt=""
						width="48"
						height="48"
						loading="lazy"
						decoding="async"
						class="apd-review-item__avatar-image">
				</div>
			<?php endif; ?>

			<div class="apd-review-item__author-info">
				<span class="apd-review-item__author-name">
					<?php echo esc_html( $review['author_name'] ); ?>
				</span>
				<time class="apd-review-item__date"
					datetime="<?php echo esc_attr( $review['date'] ); ?>">
					<?php echo esc_html( $review['date_formatted'] ); ?>
				</time>
			</div>
		</div>

		<div class="apd-review-item__rating" aria-label="<?php echo esc_attr( $star_label ); ?>">
			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped in render_stars method.
			echo $calculator->render_stars(
				(float) $review['rating'],
				[
					'size'   => 'small',
					'inline' => true,
				]
			);
			?>
		</div>

	</header>

	<div class="apd-review-item__body">

		<?php if ( ! empty( $review['title'] ) ) : ?>
			<h3 class="apd-review-item__title">
				<?php echo esc_html( $review['title'] ); ?>
			</h3>
		<?php endif; ?>

		<div class="apd-review-item__content">
			<?php
			// Allow basic HTML formatting in review content.
			echo wp_kses_post( wpautop( $review['content'] ) );
			?>
		</div>

	</div>

	<?php
	/**
	 * Fires at the end of a single review item.
	 *
	 * Use this to add helpful buttons, report links, owner responses, etc.
	 *
	 * @since 1.0.0
	 *
	 * @param array $review Review data.
	 */
	do_action( 'apd_review_item_footer', $review );
	?>

</article>
