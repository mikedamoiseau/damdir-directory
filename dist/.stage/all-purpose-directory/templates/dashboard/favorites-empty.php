<?php
/**
 * Favorites Empty State Template.
 *
 * Displayed when the user has no favorited listings.
 *
 * This template can be overridden by copying it to:
 * yourtheme/all-purpose-directory/dashboard/favorites-empty.php
 *
 * @package APD\Templates
 * @since   1.0.0
 *
 * @var APD\Frontend\Dashboard\FavoritesPage $favorites_page The FavoritesPage instance.
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get the browse listings URL.
$browse_url = $favorites_page->get_listings_archive_url();
?>

<div class="apd-favorites-empty">
	<div class="apd-favorites-empty__icon">
		<span class="dashicons dashicons-heart" aria-hidden="true"></span>
	</div>

	<h3 class="apd-favorites-empty__title">
		<?php esc_html_e( 'No favorites yet', 'all-purpose-directory' ); ?>
	</h3>

	<p class="apd-favorites-empty__message">
		<?php esc_html_e( 'You haven\'t saved any listings to your favorites. Browse listings and click the heart icon to save them here.', 'all-purpose-directory' ); ?>
	</p>

	<?php if ( ! empty( $browse_url ) ) : ?>
		<div class="apd-favorites-empty__actions">
			<a href="<?php echo esc_url( $browse_url ); ?>" class="apd-button apd-button--primary">
				<span class="dashicons dashicons-search" aria-hidden="true"></span>
				<?php esc_html_e( 'Browse Listings', 'all-purpose-directory' ); ?>
			</a>
		</div>
	<?php endif; ?>
</div>
