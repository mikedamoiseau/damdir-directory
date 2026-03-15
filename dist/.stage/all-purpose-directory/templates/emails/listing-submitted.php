<?php
/**
 * Listing Submitted Email Template.
 *
 * Sent to admin when a new listing is submitted.
 * Override this template in your theme: all-purpose-directory/emails/listing-submitted.php
 *
 * @package All_Purpose_Directory
 * @since   1.0.0
 *
 * @var int    $listing_id       Listing ID.
 * @var string $listing_title    Listing title.
 * @var string $listing_url      Listing URL.
 * @var string $listing_edit_url Listing edit URL.
 * @var string $listing_status   Listing status.
 * @var string $author_name      Author display name.
 * @var string $author_email     Author email.
 * @var int    $author_id        Author user ID.
 * @var string $admin_url        Admin listings URL.
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<h2><?php esc_html_e( 'New Listing Submitted', 'all-purpose-directory' ); ?></h2>

<p><?php esc_html_e( 'A new listing has been submitted and is awaiting your review.', 'all-purpose-directory' ); ?></p>

<table class="info-table">
	<tr>
		<td><?php esc_html_e( 'Title', 'all-purpose-directory' ); ?></td>
		<td><?php echo esc_html( $listing_title ); ?></td>
	</tr>
	<tr>
		<td><?php esc_html_e( 'Author', 'all-purpose-directory' ); ?></td>
		<td><?php echo esc_html( $author_name ); ?> (<?php echo esc_html( $author_email ); ?>)</td>
	</tr>
	<tr>
		<td><?php esc_html_e( 'Status', 'all-purpose-directory' ); ?></td>
		<td><?php echo esc_html( ucfirst( $listing_status ) ); ?></td>
	</tr>
	<tr>
		<td><?php esc_html_e( 'Submitted', 'all-purpose-directory' ); ?></td>
		<td><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ); ?></td>
	</tr>
</table>

<div class="text-center mt-20">
	<a href="<?php echo esc_url( $listing_edit_url ); ?>" class="button"><?php esc_html_e( 'Review Listing', 'all-purpose-directory' ); ?></a>
	<a href="<?php echo esc_url( $listing_url ); ?>" class="button button-secondary"><?php esc_html_e( 'View Listing', 'all-purpose-directory' ); ?></a>
</div>

<p class="text-center text-muted mt-20">
	<a href="<?php echo esc_url( $admin_url ); ?>"><?php esc_html_e( 'Manage All Listings', 'all-purpose-directory' ); ?></a>
</p>
