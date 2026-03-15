<?php
/**
 * Listing Rejected Email Template.
 *
 * Sent to the listing author when their listing is not approved.
 * Override this template in your theme: all-purpose-directory/emails/listing-rejected.php
 *
 * @package All_Purpose_Directory
 * @since   1.0.0
 *
 * @var int    $listing_id       Listing ID.
 * @var string $listing_title    Listing title.
 * @var string $author_name      Author display name.
 * @var string $rejection_reason Optional rejection reason.
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$site_name = get_bloginfo( 'name' );
?>

<h2><?php esc_html_e( 'Listing Not Approved', 'all-purpose-directory' ); ?></h2>

<p>
	<?php
	printf(
		/* translators: %s: author name */
		esc_html__( 'Hi %s,', 'all-purpose-directory' ),
		esc_html( $author_name )
	);
	?>
</p>

<p><?php esc_html_e( 'We\'re sorry, but your listing submission was not approved after our review.', 'all-purpose-directory' ); ?></p>

<table class="info-table">
	<tr>
		<td><?php esc_html_e( 'Listing Title', 'all-purpose-directory' ); ?></td>
		<td><strong><?php echo esc_html( $listing_title ); ?></strong></td>
	</tr>
	<tr>
		<td><?php esc_html_e( 'Status', 'all-purpose-directory' ); ?></td>
		<td><span style="color: #dc3545; font-weight: 500;"><?php esc_html_e( 'Not Approved', 'all-purpose-directory' ); ?></span></td>
	</tr>
</table>

<?php if ( ! empty( $rejection_reason ) ) : ?>
<div style="background-color: #f8f9fa; padding: 15px 20px; border-left: 4px solid #dc3545; margin: 20px 0;">
	<p style="margin: 0 0 5px; font-weight: 600;"><?php esc_html_e( 'Reason:', 'all-purpose-directory' ); ?></p>
	<p style="margin: 0;"><?php echo esc_html( $rejection_reason ); ?></p>
</div>
<?php endif; ?>

<p>
	<?php
	printf(
		/* translators: %s: site name */
		esc_html__( 'If you believe this was a mistake or have any questions, please don\'t hesitate to contact us. You may also submit a new listing that meets our %s guidelines.', 'all-purpose-directory' ),
		esc_html( $site_name )
	);
	?>
</p>

<p><?php esc_html_e( 'Thank you for your understanding.', 'all-purpose-directory' ); ?></p>
