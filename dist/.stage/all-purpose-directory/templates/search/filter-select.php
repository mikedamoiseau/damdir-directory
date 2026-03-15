<?php
/**
 * Select Filter Template.
 *
 * Template for rendering dropdown/select filter controls.
 *
 * This template can be overridden by copying it to:
 * yourtheme/all-purpose-directory/search/filter-select.php
 *
 * @package APD\Templates
 * @since   1.0.0
 *
 * @var FilterInterface $filter  The filter instance.
 * @var mixed           $value   Current filter value.
 * @var array           $request Request data.
 */

use APD\Contracts\FilterInterface;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$config    = $filter->getConfig();
$options   = $filter->getOptions();
$filter_id = 'apd-filter-' . esc_attr( $filter->getName() );
$is_active = $filter->isActive( $value );

$wrapper_classes = [
	'apd-filter',
	'apd-filter--select',
	'apd-filter--' . esc_attr( $filter->getName() ),
];
if ( $is_active ) {
	$wrapper_classes[] = 'apd-filter--active';
}
?>
<div class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>" data-filter="<?php echo esc_attr( $filter->getName() ); ?>">
	<label for="<?php echo esc_attr( $filter_id ); ?>" class="apd-filter__label">
		<?php echo esc_html( $filter->getLabel() ); ?>
	</label>

	<select
		id="<?php echo esc_attr( $filter_id ); ?>"
		name="<?php echo esc_attr( $filter->getUrlParam() ); ?>"
		class="apd-filter__select"
		<?php if ( $config['multiple'] ?? false ) : ?>
			multiple
		<?php endif; ?>
	>
		<?php if ( ! empty( $config['empty_option'] ) && empty( $config['multiple'] ) ) : ?>
			<option value=""><?php echo esc_html( $config['empty_option'] ); ?></option>
		<?php endif; ?>

		<?php foreach ( $options as $opt_value => $opt_label ) : ?>
			<?php
			$is_selected = is_array( $value )
				? in_array( (string) $opt_value, array_map( 'strval', $value ), true )
				: (string) $value === (string) $opt_value;
			?>
			<option value="<?php echo esc_attr( $opt_value ); ?>" <?php selected( $is_selected ); ?>>
				<?php echo esc_html( $opt_label ); ?>
			</option>
		<?php endforeach; ?>
	</select>
</div>
