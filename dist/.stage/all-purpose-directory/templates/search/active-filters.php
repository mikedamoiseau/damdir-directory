<?php
/**
 * Active Filters Template.
 *
 * Template for rendering active filter chips/badges.
 *
 * This template can be overridden by copying it to:
 * yourtheme/all-purpose-directory/search/active-filters.php
 *
 * @package APD\Templates
 * @since   1.0.0
 *
 * @var array $active_filters Array of active filters with 'filter' and 'value' keys.
 * @var array $request        Request data.
 */

use APD\Search\Filters\RangeFilter;
use APD\Search\Filters\DateRangeFilter;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $active_filters ) ) {
	return;
}

$base_url = get_post_type_archive_link( 'apd_listing' );
?>
<div class="apd-active-filters" aria-live="polite">
	<span class="apd-active-filters__label" id="apd-active-filters-label">
		<?php esc_html_e( 'Active filters:', 'all-purpose-directory' ); ?>
	</span>

	<ul class="apd-active-filters__list" aria-labelledby="apd-active-filters-label">
		<?php foreach ( $active_filters as $item ) : ?>
			<?php
			$filter = $item['filter'];
			$value  = $item['value'];

			// Build remove URL.
			$params = $request;
			$param  = $filter->getUrlParam();
			unset( $params[ $param ] );

			// Handle range and date range filters.
			if ( $filter instanceof RangeFilter ) {
				unset( $params[ $filter->getUrlParamMin() ] );
				unset( $params[ $filter->getUrlParamMax() ] );
			}
			if ( $filter instanceof DateRangeFilter ) {
				unset( $params[ $filter->getUrlParamStart() ] );
				unset( $params[ $filter->getUrlParamEnd() ] );
			}

			$remove_url = empty( $params ) ? $base_url : add_query_arg( $params, $base_url );
			?>
			<li class="apd-active-filters__item">
				<span class="apd-active-filters__name">
					<?php echo esc_html( $filter->getLabel() ); ?>:
				</span>
				<span class="apd-active-filters__value">
					<?php echo esc_html( $filter->getDisplayValue( $value ) ); ?>
				</span>
				<a
					href="<?php echo esc_url( $remove_url ); ?>"
					class="apd-active-filters__remove"
					<?php /* translators: %s: Filter label (e.g., Category, Tag, Keyword) */ ?>
					aria-label="<?php echo esc_attr( sprintf( __( 'Remove %s filter', 'all-purpose-directory' ), $filter->getLabel() ) ); ?>"
				>
					<span aria-hidden="true">&times;</span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>

	<a href="<?php echo esc_url( $base_url ); ?>" class="apd-active-filters__clear">
		<?php esc_html_e( 'Clear all', 'all-purpose-directory' ); ?>
	</a>
</div>
