<?php
/**
 * Submission Success Template.
 *
 * Template for displaying the success message after a listing submission.
 *
 * This template can be overridden by copying it to:
 * yourtheme/all-purpose-directory/submission/submission-success.php
 *
 * @package APD\Templates
 * @since   1.0.0
 *
 * @var int    $listing_id   The submitted listing ID.
 * @var string $listing_url  URL to view the listing (empty if not published).
 * @var string $status       The listing status (publish, pending, etc.).
 * @var string $title        The listing title.
 * @var string $submit_url   URL to submit another listing.
 * @var bool   $is_update    Whether this was an update to an existing listing.
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$is_published = $status === 'publish';
?>

<div class="apd-submission-success" role="alert" aria-live="polite">
	<div class="apd-submission-success__icon" aria-hidden="true">
		<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
			<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
			<polyline points="22 4 12 14.01 9 11.01"></polyline>
		</svg>
	</div>

	<h2 class="apd-submission-success__title">
		<?php
		if ( $is_update ) {
			esc_html_e( 'Listing Updated Successfully!', 'all-purpose-directory' );
		} else {
			esc_html_e( 'Thank You for Your Submission!', 'all-purpose-directory' );
		}
		?>
	</h2>

	<div class="apd-submission-success__message">
		<?php if ( $is_published ) : ?>
			<p>
				<?php
				if ( $is_update ) {
					esc_html_e( 'Your listing has been updated and is now live.', 'all-purpose-directory' );
				} else {
					esc_html_e( 'Your listing has been published and is now live.', 'all-purpose-directory' );
				}
				?>
			</p>
		<?php else : ?>
			<p>
				<?php
				if ( $is_update ) {
					esc_html_e( 'Your listing has been updated and is awaiting review.', 'all-purpose-directory' );
				} else {
					esc_html_e( 'Your listing has been submitted and is awaiting review by our team.', 'all-purpose-directory' );
				}
				?>
			</p>
			<p>
				<?php esc_html_e( 'We will review your submission shortly. You will be notified once it has been approved.', 'all-purpose-directory' ); ?>
			</p>
		<?php endif; ?>
	</div>

	<?php if ( ! empty( $title ) ) : ?>
		<div class="apd-submission-success__details">
			<p class="apd-submission-success__listing-title">
				<strong><?php esc_html_e( 'Listing:', 'all-purpose-directory' ); ?></strong>
				<?php echo esc_html( $title ); ?>
			</p>
		</div>
	<?php endif; ?>

	<div class="apd-submission-success__actions">
		<?php if ( $is_published && ! empty( $listing_url ) ) : ?>
			<a href="<?php echo esc_url( $listing_url ); ?>" class="apd-button apd-button--primary">
				<?php esc_html_e( 'View Your Listing', 'all-purpose-directory' ); ?>
			</a>
		<?php endif; ?>

		<?php if ( ! empty( $submit_url ) ) : ?>
			<a href="<?php echo esc_url( $submit_url ); ?>" class="apd-button apd-button--secondary">
				<?php esc_html_e( 'Submit Another Listing', 'all-purpose-directory' ); ?>
			</a>
		<?php endif; ?>

		<a href="<?php echo esc_url( home_url() ); ?>" class="apd-button apd-button--text">
			<?php esc_html_e( 'Return to Home', 'all-purpose-directory' ); ?>
		</a>
	</div>

	<?php
	/**
	 * Fires after the submission success content.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $listing_id The listing ID.
	 * @param string $status     The listing status.
	 * @param bool   $is_update  Whether this was an update.
	 */
	do_action( 'apd_after_submission_success', $listing_id, $status, $is_update );
	?>
</div>
