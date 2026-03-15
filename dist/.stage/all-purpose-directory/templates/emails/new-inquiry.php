<?php
/**
 * New Inquiry Email Template.
 *
 * Sent to the listing owner when they receive a contact inquiry.
 * Override this template in your theme: all-purpose-directory/emails/new-inquiry.php
 *
 * @package All_Purpose_Directory
 * @since   1.0.0
 *
 * @var int    $listing_id      Listing ID.
 * @var string $listing_title   Listing title.
 * @var string $listing_url     Listing URL.
 * @var string $author_name     Listing author name.
 * @var string $inquiry_name    Inquirer's name.
 * @var string $inquiry_email   Inquirer's email.
 * @var string $inquiry_phone   Inquirer's phone (optional).
 * @var string $inquiry_message Inquiry message.
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<h2><?php esc_html_e( 'New Inquiry About Your Listing', 'all-purpose-directory' ); ?></h2>

<p>
	<?php
	printf(
		/* translators: %s: author name */
		esc_html__( 'Hi %s,', 'all-purpose-directory' ),
		esc_html( $author_name )
	);
	?>
</p>

<p><?php esc_html_e( 'You have received a new inquiry about your listing. Here are the details:', 'all-purpose-directory' ); ?></p>

<table class="info-table">
	<tr>
		<td><?php esc_html_e( 'Listing', 'all-purpose-directory' ); ?></td>
		<td><a href="<?php echo esc_url( $listing_url ); ?>"><?php echo esc_html( $listing_title ); ?></a></td>
	</tr>
	<tr>
		<td><?php esc_html_e( 'From', 'all-purpose-directory' ); ?></td>
		<td><?php echo esc_html( $inquiry_name ); ?></td>
	</tr>
	<tr>
		<td><?php esc_html_e( 'Email', 'all-purpose-directory' ); ?></td>
		<td><a href="mailto:<?php echo esc_attr( $inquiry_email ); ?>"><?php echo esc_html( $inquiry_email ); ?></a></td>
	</tr>
	<?php if ( ! empty( $inquiry_phone ) ) : ?>
	<tr>
		<td><?php esc_html_e( 'Phone', 'all-purpose-directory' ); ?></td>
		<td><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $inquiry_phone ) ); ?>"><?php echo esc_html( $inquiry_phone ); ?></a></td>
	</tr>
	<?php endif; ?>
	<tr>
		<td><?php esc_html_e( 'Date', 'all-purpose-directory' ); ?></td>
		<td><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ); ?></td>
	</tr>
</table>

<h3 style="margin-top: 25px; margin-bottom: 10px;"><?php esc_html_e( 'Message:', 'all-purpose-directory' ); ?></h3>

<div style="background-color: #f8f9fa; padding: 20px; border-radius: 6px; border: 1px solid #e9ecef;">
	<p style="margin: 0; white-space: pre-wrap;"><?php echo nl2br( esc_html( $inquiry_message ) ); ?></p>
</div>

<div class="text-center mt-20">
	<a href="mailto:<?php echo esc_attr( $inquiry_email ); ?>?subject=<?php echo esc_attr( rawurlencode( 'Re: ' . $listing_title ) ); ?>" class="button"><?php esc_html_e( 'Reply to Inquiry', 'all-purpose-directory' ); ?></a>
</div>

<p class="text-muted mt-20"><?php esc_html_e( 'Responding quickly to inquiries can help convert potential customers. We recommend replying within 24 hours.', 'all-purpose-directory' ); ?></p>
