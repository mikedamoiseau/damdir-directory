<?php
/**
 * Dashboard Stats Template.
 *
 * Statistics overview section for the user dashboard.
 *
 * This template can be overridden by copying it to:
 * yourtheme/all-purpose-directory/dashboard/stats.php
 *
 * @package APD\Templates
 * @since   1.0.0
 *
 * @var array<string, int> $stats   User statistics.
 * @var int                $user_id User ID.
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Filter the stats to display in the dashboard.
 *
 * @since 1.0.0
 *
 * @param array<string, array> $stat_items Stat items configuration.
 * @param array<string, int>   $stats      Raw statistics.
 * @param int                  $user_id    User ID.
 */
$stat_items = apply_filters(
	'apd_dashboard_stat_items',
	[
		'total'     => [
			'label' => __( 'Total Listings', 'all-purpose-directory' ),
			'icon'  => 'dashicons-list-view',
			'value' => $stats['total'],
		],
		'published' => [
			'label' => __( 'Published', 'all-purpose-directory' ),
			'icon'  => 'dashicons-yes-alt',
			'value' => $stats['published'],
			'class' => 'apd-stat-card--success',
		],
		'pending'   => [
			'label' => __( 'Pending Review', 'all-purpose-directory' ),
			'icon'  => 'dashicons-clock',
			'value' => $stats['pending'],
			'class' => 'apd-stat-card--warning',
		],
		'draft'     => [
			'label' => __( 'Drafts', 'all-purpose-directory' ),
			'icon'  => 'dashicons-edit',
			'value' => $stats['draft'],
		],
		'views'     => [
			'label' => __( 'Total Views', 'all-purpose-directory' ),
			'icon'  => 'dashicons-visibility',
			'value' => $stats['views'],
			'class' => 'apd-stat-card--info',
		],
	],
	$stats,
	$user_id
);
?>

<div class="apd-dashboard-stats">
	<?php foreach ( $stat_items as $stat_key => $stat ) : ?>
		<?php
		$card_class = 'apd-stat-card';
		if ( ! empty( $stat['class'] ) ) {
			$card_class .= ' ' . $stat['class'];
		}
		?>
		<div class="<?php echo esc_attr( $card_class ); ?>">
			<?php if ( ! empty( $stat['icon'] ) ) : ?>
				<span class="apd-stat-card__icon dashicons <?php echo esc_attr( $stat['icon'] ); ?>" aria-hidden="true"></span>
			<?php endif; ?>
			<div class="apd-stat-card__content">
				<span class="apd-stat-card__value">
					<?php echo esc_html( number_format_i18n( $stat['value'] ) ); ?>
				</span>
				<span class="apd-stat-card__label">
					<?php echo esc_html( $stat['label'] ); ?>
				</span>
			</div>
		</div>
	<?php endforeach; ?>
</div>
