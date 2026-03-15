<?php
/**
 * Edit Not Allowed Template.
 *
 * Template for displaying permission denied message when a user
 * tries to edit a listing they don't have access to.
 *
 * This template can be overridden by copying it to:
 * yourtheme/all-purpose-directory/submission/edit-not-allowed.php
 *
 * @package APD\Templates
 * @since   1.0.0
 *
 * @var string $title    Error title.
 * @var string $message  Error message.
 * @var string $home_url URL to the home page.
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="apd-edit-not-allowed" role="alert">
	<div class="apd-edit-not-allowed__icon" aria-hidden="true">
		<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
			<circle cx="12" cy="12" r="10"></circle>
			<line x1="12" y1="8" x2="12" y2="12"></line>
			<line x1="12" y1="16" x2="12.01" y2="16"></line>
		</svg>
	</div>

	<h2 class="apd-edit-not-allowed__title">
		<?php echo esc_html( $title ); ?>
	</h2>

	<div class="apd-edit-not-allowed__message">
		<p><?php echo esc_html( $message ); ?></p>
	</div>

	<div class="apd-edit-not-allowed__actions">
		<a href="<?php echo esc_url( $home_url ); ?>" class="apd-button apd-button--primary">
			<?php esc_html_e( 'Return to Home', 'all-purpose-directory' ); ?>
		</a>

		<?php if ( ! is_user_logged_in() ) : ?>
			<a href="<?php echo esc_url( wp_login_url( add_query_arg( [] ) ) ); ?>" class="apd-button apd-button--secondary">
				<?php esc_html_e( 'Log In', 'all-purpose-directory' ); ?>
			</a>
		<?php endif; ?>
	</div>

	<?php
	/**
	 * Fires after the edit not allowed content.
	 *
	 * @since 1.0.0
	 *
	 * @param string $title   Error title.
	 * @param string $message Error message.
	 */
	do_action( 'apd_after_edit_not_allowed', $title, $message );
	?>
</div>
