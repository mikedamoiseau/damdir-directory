<?php
/**
 * My Listings Empty State Template.
 *
 * Displayed when the user has no listings.
 *
 * This template can be overridden by copying it to:
 * yourtheme/all-purpose-directory/dashboard/my-listings-empty.php
 *
 * @package APD\Templates
 * @since   1.0.0
 *
 * @var string $status Current status filter (all, publish, pending, draft, expired).
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get status-specific messaging.
$messages = [
	'all'     => [
		'title'   => __( 'No Listings Yet', 'all-purpose-directory' ),
		'message' => __( 'You haven\'t created any listings yet. Get started by adding your first listing!', 'all-purpose-directory' ),
		'icon'    => 'dashicons-plus-alt',
	],
	'publish' => [
		'title'   => __( 'No Published Listings', 'all-purpose-directory' ),
		'message' => __( 'You don\'t have any published listings. Submit a listing for review to get it published.', 'all-purpose-directory' ),
		'icon'    => 'dashicons-yes-alt',
	],
	'pending' => [
		'title'   => __( 'No Pending Listings', 'all-purpose-directory' ),
		'message' => __( 'You don\'t have any listings pending review.', 'all-purpose-directory' ),
		'icon'    => 'dashicons-clock',
	],
	'draft'   => [
		'title'   => __( 'No Draft Listings', 'all-purpose-directory' ),
		'message' => __( 'You don\'t have any draft listings saved.', 'all-purpose-directory' ),
		'icon'    => 'dashicons-edit',
	],
	'expired' => [
		'title'   => __( 'No Expired Listings', 'all-purpose-directory' ),
		'message' => __( 'You don\'t have any expired listings.', 'all-purpose-directory' ),
		'icon'    => 'dashicons-dismiss',
	],
];

$current = $messages[ $status ] ?? $messages['all'];

// Get submission URL.
$submission_url = apply_filters( 'apd_submission_page_url', '' );
$show_cta       = ( $status === 'all' || $status === 'publish' || $status === 'draft' ) && ! empty( $submission_url );
?>

<div class="apd-my-listings-empty">
	<div class="apd-my-listings-empty__icon">
		<span class="dashicons <?php echo esc_attr( $current['icon'] ); ?>" aria-hidden="true"></span>
	</div>

	<h3 class="apd-my-listings-empty__title">
		<?php echo esc_html( $current['title'] ); ?>
	</h3>

	<p class="apd-my-listings-empty__message">
		<?php echo esc_html( $current['message'] ); ?>
	</p>

	<?php if ( $show_cta ) : ?>
		<div class="apd-my-listings-empty__actions">
			<a href="<?php echo esc_url( $submission_url ); ?>" class="apd-button apd-button--primary">
				<span class="dashicons dashicons-plus-alt" aria-hidden="true"></span>
				<?php esc_html_e( 'Create New Listing', 'all-purpose-directory' ); ?>
			</a>
		</div>
	<?php endif; ?>

	<?php if ( $status !== 'all' ) : ?>
		<div class="apd-my-listings-empty__link">
			<a href="<?php echo esc_url( remove_query_arg( [ 'status', 'paged' ] ) ); ?>">
				<?php esc_html_e( 'View all listings', 'all-purpose-directory' ); ?>
			</a>
		</div>
	<?php endif; ?>
</div>
