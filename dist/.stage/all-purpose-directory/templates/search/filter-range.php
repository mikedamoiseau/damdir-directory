<?php
/**
 * Range Filter Template.
 *
 * Template for rendering numeric range filter controls with min/max inputs.
 *
 * This template can be overridden by copying it to:
 * yourtheme/all-purpose-directory/search/filter-range.php
 *
 * @package APD\Templates
 * @since   1.0.0
 *
 * @var FilterInterface $filter  The filter instance.
 * @var mixed           $value   Current filter value.
 * @var array           $request Request data.
 */

use APD\Contracts\FilterInterface;
use APD\Search\Filters\RangeFilter;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Ensure filter is RangeFilter for type safety.
if ( ! $filter instanceof RangeFilter ) {
	return;
}

$config    = $filter->getConfig();
$filter_id = 'apd-filter-' . esc_attr( $filter->getName() );
$is_active = $filter->isActive( $value );

$min_value = $value['min'] ?? '';
$max_value = $value['max'] ?? '';

$wrapper_classes = [
	'apd-filter',
	'apd-filter--range',
	'apd-filter--' . esc_attr( $filter->getName() ),
];
if ( $is_active ) {
	$wrapper_classes[] = 'apd-filter--active';
}
?>
<div class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>" data-filter="<?php echo esc_attr( $filter->getName() ); ?>">
	<label class="apd-filter__label" id="<?php echo esc_attr( $filter_id ); ?>-label">
		<?php echo esc_html( $filter->getLabel() ); ?>
	</label>

	<div class="apd-filter__range-inputs" role="group" aria-labelledby="<?php echo esc_attr( $filter_id ); ?>-label">
		<?php if ( ! empty( $config['prefix'] ) ) : ?>
			<span class="apd-filter__prefix" aria-hidden="true"><?php echo esc_html( $config['prefix'] ); ?></span>
		<?php endif; ?>

		<input
			type="number"
			id="<?php echo esc_attr( $filter_id ); ?>-min"
			name="<?php echo esc_attr( $filter->getUrlParamMin() ); ?>"
			value="<?php echo esc_attr( $min_value ); ?>"
			placeholder="<?php echo esc_attr( $config['min_placeholder'] ?? __( 'Min', 'all-purpose-directory' ) ); ?>"
			class="apd-filter__input apd-filter__input--min"
			aria-label="<?php esc_attr_e( 'Minimum value', 'all-purpose-directory' ); ?>"
			<?php if ( isset( $config['min'] ) && $config['min'] !== null ) : ?>
				min="<?php echo esc_attr( $config['min'] ); ?>"
			<?php endif; ?>
			<?php if ( isset( $config['max'] ) && $config['max'] !== null ) : ?>
				max="<?php echo esc_attr( $config['max'] ); ?>"
			<?php endif; ?>
			<?php if ( ! empty( $config['step'] ) ) : ?>
				step="<?php echo esc_attr( $config['step'] ); ?>"
			<?php endif; ?>
		>

		<span class="apd-filter__range-separator" aria-hidden="true">&ndash;</span>

		<input
			type="number"
			id="<?php echo esc_attr( $filter_id ); ?>-max"
			name="<?php echo esc_attr( $filter->getUrlParamMax() ); ?>"
			value="<?php echo esc_attr( $max_value ); ?>"
			placeholder="<?php echo esc_attr( $config['max_placeholder'] ?? __( 'Max', 'all-purpose-directory' ) ); ?>"
			class="apd-filter__input apd-filter__input--max"
			aria-label="<?php esc_attr_e( 'Maximum value', 'all-purpose-directory' ); ?>"
			<?php if ( isset( $config['min'] ) && $config['min'] !== null ) : ?>
				min="<?php echo esc_attr( $config['min'] ); ?>"
			<?php endif; ?>
			<?php if ( isset( $config['max'] ) && $config['max'] !== null ) : ?>
				max="<?php echo esc_attr( $config['max'] ); ?>"
			<?php endif; ?>
			<?php if ( ! empty( $config['step'] ) ) : ?>
				step="<?php echo esc_attr( $config['step'] ); ?>"
			<?php endif; ?>
		>

		<?php if ( ! empty( $config['suffix'] ) ) : ?>
			<span class="apd-filter__suffix" aria-hidden="true"><?php echo esc_html( $config['suffix'] ); ?></span>
		<?php endif; ?>
	</div>
</div>
