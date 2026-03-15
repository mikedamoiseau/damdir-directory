<?php
/**
 * No Results Template.
 *
 * Template for displaying when no listings match the search criteria.
 *
 * This template can be overridden by copying it to:
 * yourtheme/all-purpose-directory/search/no-results.php
 *
 * @package APD\Templates
 * @since   1.0.0
 *
 * @var array $args Render arguments.
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$archive_url = get_post_type_archive_link( 'apd_listing' );
?>
<div class="apd-no-results">
	<div class="apd-no-results__icon" aria-hidden="true">
		<span class="dashicons dashicons-search"></span>
	</div>

	<h2 class="apd-no-results__title">
		<?php esc_html_e( 'No listings found', 'all-purpose-directory' ); ?>
	</h2>

	<p class="apd-no-results__message">
		<?php esc_html_e( 'No listings match your current search criteria. Try adjusting your filters or search terms.', 'all-purpose-directory' ); ?>
	</p>

	<div class="apd-no-results__actions">
		<a href="<?php echo esc_url( $archive_url ); ?>" class="apd-no-results__clear">
			<?php esc_html_e( 'View all listings', 'all-purpose-directory' ); ?>
		</a>
	</div>
</div>
