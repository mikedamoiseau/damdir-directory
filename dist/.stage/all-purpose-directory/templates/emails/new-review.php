<?php
/**
 * New Review Email Template.
 *
 * Sent to the listing owner when their listing receives a new review.
 * Override this template in your theme: all-purpose-directory/emails/new-review.php
 *
 * @package All_Purpose_Directory
 * @since   1.0.0
 *
 * @var int    $listing_id      Listing ID.
 * @var string $listing_title   Listing title.
 * @var string $listing_url     Listing URL.
 * @var string $author_name     Listing author name.
 * @var int    $review_id       Review comment ID.
 * @var string $review_author   Review author name.
 * @var string $review_email    Review author email.
 * @var string $review_content  Review content.
 * @var int    $review_rating   Review rating (1-5).
 * @var string $review_title    Review title.
 * @var string $review_date     Review date.
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generate star rating HTML.
 *
 * @param int $rating Rating 1-5.
 * @return string HTML string.
 */
function apd_email_star_rating( int $rating ): string {
	$output = '<span style="color: #ffc107; font-size: 18px;">';
	for ( $i = 1; $i <= 5; $i++ ) {
		$output .= $i <= $rating ? '&#9733;' : '&#9734;';
	}
	$output .= '</span>';
	return $output;
}
?>

<h2><?php esc_html_e( 'New Review on Your Listing', 'all-purpose-directory' ); ?></h2>

<p>
	<?php
	printf(
		/* translators: %s: author name */
		esc_html__( 'Hi %s,', 'all-purpose-directory' ),
		esc_html( $author_name )
	);
	?>
</p>

<p><?php esc_html_e( 'Your listing has received a new review! Here are the details:', 'all-purpose-directory' ); ?></p>

<table class="info-table">
	<tr>
		<td><?php esc_html_e( 'Listing', 'all-purpose-directory' ); ?></td>
		<td><a href="<?php echo esc_url( $listing_url ); ?>"><?php echo esc_html( $listing_title ); ?></a></td>
	</tr>
	<tr>
		<td><?php esc_html_e( 'Reviewer', 'all-purpose-directory' ); ?></td>
		<td><?php echo esc_html( $review_author ); ?></td>
	</tr>
	<tr>
		<td><?php esc_html_e( 'Rating', 'all-purpose-directory' ); ?></td>
		<td><?php echo apd_email_star_rating( (int) $review_rating ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> (<?php echo esc_html( $review_rating ); ?>/5)</td>
	</tr>
	<tr>
		<td><?php esc_html_e( 'Date', 'all-purpose-directory' ); ?></td>
		<td><?php echo esc_html( $review_date ); ?></td>
	</tr>
</table>

<?php if ( ! empty( $review_title ) ) : ?>
<p style="margin-top: 20px;"><strong><?php echo esc_html( $review_title ); ?></strong></p>
<?php endif; ?>

<div style="background-color: #f8f9fa; padding: 15px 20px; border-left: 4px solid #0073aa; margin: 15px 0; font-style: italic;">
	<p style="margin: 0;"><?php echo nl2br( esc_html( $review_content ) ); ?></p>
</div>

<div class="text-center mt-20">
	<a href="<?php echo esc_url( $listing_url . '#reviews' ); ?>" class="button"><?php esc_html_e( 'View Review', 'all-purpose-directory' ); ?></a>
</div>

<p class="text-muted mt-20"><?php esc_html_e( 'Reviews help build trust with potential customers. Thank your reviewer or respond to their feedback to show you value their opinion!', 'all-purpose-directory' ); ?></p>
