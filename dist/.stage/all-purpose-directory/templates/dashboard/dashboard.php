<?php
/**
 * Dashboard Template.
 *
 * Main wrapper template for the user dashboard.
 *
 * This template can be overridden by copying it to:
 * yourtheme/all-purpose-directory/dashboard/dashboard.php
 *
 * @package APD\Templates
 * @since   1.0.0
 *
 * @var APD\Frontend\Dashboard\Dashboard $dashboard   The dashboard instance.
 * @var array<string, mixed>             $config      Dashboard configuration.
 * @var int                              $user_id     Current user ID.
 * @var string                           $current_tab Current tab slug.
 * @var array<string, array>             $tabs        Available tabs.
 * @var array<string, int>               $stats       User statistics.
 * @var bool                             $show_stats  Whether to show stats section.
 * @var string                           $css_class   CSS classes for wrapper.
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="<?php echo esc_attr( $css_class ); ?>" data-current-tab="<?php echo esc_attr( $current_tab ); ?>">

	<?php
	/**
	 * Fires at the start of the dashboard.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $args Dashboard arguments.
	 */
	do_action( 'apd_dashboard_start', $args );
	?>

	<?php if ( $show_stats ) : ?>
		<section class="apd-dashboard__stats" aria-labelledby="apd-dashboard-stats-title">
			<h2 id="apd-dashboard-stats-title" class="screen-reader-text">
				<?php esc_html_e( 'Dashboard Statistics', 'all-purpose-directory' ); ?>
			</h2>
			<?php
			apd_get_template(
				'dashboard/stats.php',
				[
					'stats'   => $stats,
					'user_id' => $user_id,
				]
			);
			?>
		</section>
	<?php endif; ?>

	<div class="apd-dashboard__main">
		<nav class="apd-dashboard__navigation" aria-label="<?php esc_attr_e( 'Dashboard navigation', 'all-purpose-directory' ); ?>">
			<?php
			apd_get_template(
				'dashboard/navigation.php',
				[
					'tabs'        => $tabs,
					'current_tab' => $current_tab,
					'dashboard'   => $dashboard,
				]
			);
			?>
		</nav>

		<div class="apd-dashboard__content">
			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Content escaped in render method.
			echo $dashboard->render_tab_content( $current_tab );
			?>
		</div>
	</div>

	<?php
	/**
	 * Fires at the end of the dashboard.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $args Dashboard arguments.
	 */
	do_action( 'apd_dashboard_end', $args );
	?>

</div>
