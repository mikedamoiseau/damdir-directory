<?php
/**
 * Dashboard Login Required Template.
 *
 * Displays when a user is not logged in and tries to access the dashboard.
 *
 * This template can be overridden by copying it to:
 * yourtheme/all-purpose-directory/dashboard/login-required.php
 *
 * @package APD\Templates
 * @since   1.0.0
 *
 * @var string $message   Login required message.
 * @var string $login_url URL to the login page.
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Filter the registration URL shown on the login required screen.
 *
 * @since 1.0.0
 *
 * @param string $register_url The registration URL.
 */
$register_url = apply_filters( 'apd_dashboard_register_url', wp_registration_url() );

/**
 * Filter whether to show the registration link on the login required screen.
 *
 * @since 1.0.0
 *
 * @param bool $show_register Whether to show registration link.
 */
$show_register = apply_filters( 'apd_dashboard_show_register', get_option( 'users_can_register' ) );
?>

<div class="apd-dashboard-login-required">
	<div class="apd-dashboard-login-required__icon">
		<span class="dashicons dashicons-lock" aria-hidden="true"></span>
	</div>

	<h2 class="apd-dashboard-login-required__title">
		<?php esc_html_e( 'Login Required', 'all-purpose-directory' ); ?>
	</h2>

	<p class="apd-dashboard-login-required__message">
		<?php echo esc_html( $message ); ?>
	</p>

	<div class="apd-dashboard-login-required__actions">
		<a href="<?php echo esc_url( $login_url ); ?>" class="apd-button apd-button--primary">
			<?php esc_html_e( 'Log In', 'all-purpose-directory' ); ?>
		</a>

		<?php if ( $show_register && ! empty( $register_url ) ) : ?>
			<span class="apd-dashboard-login-required__separator"><?php esc_html_e( 'or', 'all-purpose-directory' ); ?></span>
			<a href="<?php echo esc_url( $register_url ); ?>" class="apd-button apd-button--secondary">
				<?php esc_html_e( 'Create an Account', 'all-purpose-directory' ); ?>
			</a>
		<?php endif; ?>
	</div>
</div>
