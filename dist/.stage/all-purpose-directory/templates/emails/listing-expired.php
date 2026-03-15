<?php
/**
 * Listing Expired Email Template.
 *
 * Sent to the listing author when their listing has expired.
 * Override this template in your theme: all-purpose-directory/emails/listing-expired.php
 *
 * @package All_Purpose_Directory
 * @since   1.0.0
 *
 * @var int    $listing_id    Listing ID.
 * @var string $listing_title Listing title.
 * @var string $author_name   Author display name.
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<h2><?php esc_html_e( 'Your Listing Has Expired', 'all-purpose-directory' ); ?></h2>

<p>
	<?php
	printf(
		/* translators: %s: author name */
		esc_html__( 'Hi %s,', 'all-purpose-directory' ),
		esc_html( $author_name )
	);
	?>
</p>

<p><?php esc_html_e( 'Your listing has expired and is no longer visible to the public.', 'all-purpose-directory' ); ?></p>

<table class="info-table">
	<tr>
		<td><?php esc_html_e( 'Listing Title', 'all-purpose-directory' ); ?></td>
		<td><strong><?php echo esc_html( $listing_title ); ?></strong></td>
	</tr>
	<tr>
		<td><?php esc_html_e( 'Status', 'all-purpose-directory' ); ?></td>
		<td><span style="color: #6c757d; font-weight: 500;"><?php esc_html_e( 'Expired', 'all-purpose-directory' ); ?></span></td>
	</tr>
	<tr>
		<td><?php esc_html_e( 'Expired On', 'all-purpose-directory' ); ?></td>
		<td><?php echo esc_html( wp_date( get_option( 'date_format' ) ) ); ?></td>
	</tr>
</table>

<p><?php esc_html_e( 'To make your listing visible again, you\'ll need to renew it or contact us for assistance.', 'all-purpose-directory' ); ?></p>

<p><?php esc_html_e( 'Thank you for being part of our community.', 'all-purpose-directory' ); ?></p>
