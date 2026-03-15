<?php
/**
 * Reviews Empty State Template.
 *
 * Template for rendering the empty state when a listing has no reviews.
 *
 * This template can be overridden by copying it to:
 * yourtheme/all-purpose-directory/review/reviews-empty.php
 *
 * @package APD\Templates
 * @since   1.0.0
 *
 * @var int $listing_id Listing post ID.
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="apd-reviews-empty">
	<div class="apd-reviews-empty__icon" aria-hidden="true">
		<span class="dashicons dashicons-star-empty"></span>
	</div>
	<p class="apd-reviews-empty__message">
		<?php esc_html_e( 'No reviews yet. Be the first to share your experience!', 'all-purpose-directory' ); ?>
	</p>
</div>
