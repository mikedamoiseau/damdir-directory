<?php
/**
 * Dashboard Navigation Template.
 *
 * Tab navigation for the user dashboard.
 *
 * This template can be overridden by copying it to:
 * yourtheme/all-purpose-directory/dashboard/navigation.php
 *
 * @package APD\Templates
 * @since   1.0.0
 *
 * @var array<string, array>             $tabs        Available tabs.
 * @var string                           $current_tab Current tab slug.
 * @var APD\Frontend\Dashboard\Dashboard $dashboard   The dashboard instance.
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<ul class="apd-dashboard-nav">
	<?php foreach ( $tabs as $tab_slug => $tab ) : ?>
		<?php
		$is_active = ( $tab_slug === $current_tab );
		$tab_url   = $dashboard->get_tab_url( $tab_slug );
		$has_count = isset( $tab['count'] ) && $tab['count'] !== null;
		?>
		<li class="apd-dashboard-nav__item">
			<a href="<?php echo esc_url( $tab_url ); ?>"
				id="apd-tab-<?php echo esc_attr( $tab_slug ); ?>"
				class="apd-dashboard-nav__link <?php echo $is_active ? 'apd-dashboard-nav__link--active' : ''; ?>"
				<?php if ( $is_active ) : ?>
					aria-current="page"
				<?php endif; ?>>
				<?php if ( ! empty( $tab['icon'] ) ) : ?>
					<span class="apd-dashboard-nav__icon dashicons <?php echo esc_attr( $tab['icon'] ); ?>" aria-hidden="true"></span>
				<?php endif; ?>
				<span class="apd-dashboard-nav__label">
					<?php echo esc_html( $tab['label'] ); ?>
				</span>
				<?php if ( $has_count && $tab['count'] > 0 ) : ?>
					<?php /* translators: %d: Number of items in this tab */ ?>
					<span class="apd-dashboard-nav__count" aria-label="<?php echo esc_attr( sprintf( __( '%d items', 'all-purpose-directory' ), $tab['count'] ) ); ?>">
						<?php echo absint( $tab['count'] ); ?>
					</span>
				<?php endif; ?>
			</a>
		</li>
	<?php endforeach; ?>
</ul>
