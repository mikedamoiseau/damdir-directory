<?php
/**
 * Date Range Filter Template.
 *
 * Template for rendering date range filter controls with start/end date inputs.
 *
 * This template can be overridden by copying it to:
 * yourtheme/all-purpose-directory/search/filter-date-range.php
 *
 * @package APD\Templates
 * @since   1.0.0
 *
 * @var FilterInterface $filter  The filter instance.
 * @var mixed           $value   Current filter value.
 * @var array           $request Request data.
 */

use APD\Contracts\FilterInterface;
use APD\Search\Filters\DateRangeFilter;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Ensure filter is DateRangeFilter for type safety.
if ( ! $filter instanceof DateRangeFilter ) {
	return;
}

$config    = $filter->getConfig();
$filter_id = 'apd-filter-' . esc_attr( $filter->getName() );
$is_active = $filter->isActive( $value );

$start_value = $value['start'] ?? '';
$end_value   = $value['end'] ?? '';

$wrapper_classes = [
	'apd-filter',
	'apd-filter--date-range',
	'apd-filter--' . esc_attr( $filter->getName() ),
];
if ( $is_active ) {
	$wrapper_classes[] = 'apd-filter--active';
}
?>
<div class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>" data-filter="<?php echo esc_attr( $filter->getName() ); ?>">
	<span class="apd-filter__label" id="<?php echo esc_attr( $filter_id ); ?>-label">
		<?php echo esc_html( $filter->getLabel() ); ?>
	</span>

	<div class="apd-filter__date-range-inputs" role="group" aria-labelledby="<?php echo esc_attr( $filter_id ); ?>-label">
		<div class="apd-filter__date-field">
			<label for="<?php echo esc_attr( $filter_id ); ?>-start" class="apd-filter__date-label">
				<?php echo esc_html( $config['start_label'] ?? __( 'From', 'all-purpose-directory' ) ); ?>
			</label>
			<input
				type="date"
				id="<?php echo esc_attr( $filter_id ); ?>-start"
				name="<?php echo esc_attr( $filter->getUrlParamStart() ); ?>"
				value="<?php echo esc_attr( $start_value ); ?>"
				class="apd-filter__input apd-filter__input--date"
				<?php if ( ! empty( $config['min'] ) ) : ?>
					min="<?php echo esc_attr( $config['min'] ); ?>"
				<?php endif; ?>
				<?php if ( ! empty( $config['max'] ) ) : ?>
					max="<?php echo esc_attr( $config['max'] ); ?>"
				<?php endif; ?>
			>
		</div>

		<div class="apd-filter__date-field">
			<label for="<?php echo esc_attr( $filter_id ); ?>-end" class="apd-filter__date-label">
				<?php echo esc_html( $config['end_label'] ?? __( 'To', 'all-purpose-directory' ) ); ?>
			</label>
			<input
				type="date"
				id="<?php echo esc_attr( $filter_id ); ?>-end"
				name="<?php echo esc_attr( $filter->getUrlParamEnd() ); ?>"
				value="<?php echo esc_attr( $end_value ); ?>"
				class="apd-filter__input apd-filter__input--date"
				<?php if ( ! empty( $config['min'] ) ) : ?>
					min="<?php echo esc_attr( $config['min'] ); ?>"
				<?php endif; ?>
				<?php if ( ! empty( $config['max'] ) ) : ?>
					max="<?php echo esc_attr( $config['max'] ); ?>"
				<?php endif; ?>
			>
		</div>
	</div>
</div>
