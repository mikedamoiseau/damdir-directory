<?php
/**
 * Checkbox Filter Template.
 *
 * Template for rendering checkbox group filter controls.
 *
 * This template can be overridden by copying it to:
 * yourtheme/all-purpose-directory/search/filter-checkbox.php
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

// Normalize value to array.
$selected_values = is_array( $value ) ? array_map( 'strval', $value ) : ( $value ? [ (string) $value ] : [] );

$wrapper_classes = [
	'apd-filter',
	'apd-filter--checkbox',
	'apd-filter--' . esc_attr( $filter->getName() ),
];
if ( $is_active ) {
	$wrapper_classes[] = 'apd-filter--active';
}

if ( empty( $options ) ) {
	return;
}
?>
<div class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>" data-filter="<?php echo esc_attr( $filter->getName() ); ?>">
	<fieldset class="apd-filter__fieldset">
		<legend class="apd-filter__legend">
			<?php echo esc_html( $filter->getLabel() ); ?>
		</legend>

		<div class="apd-filter__options">
			<?php foreach ( $options as $opt_value => $opt_label ) : ?>
				<?php
				$option_id  = $filter_id . '-' . esc_attr( $opt_value );
				$is_checked = in_array( (string) $opt_value, $selected_values, true );
				?>
				<div class="apd-filter__option">
					<input
						type="checkbox"
						id="<?php echo esc_attr( $option_id ); ?>"
						name="<?php echo esc_attr( $filter->getUrlParam() ); ?>[]"
						value="<?php echo esc_attr( $opt_value ); ?>"
						class="apd-filter__checkbox"
						<?php checked( $is_checked ); ?>
					>
					<label for="<?php echo esc_attr( $option_id ); ?>" class="apd-filter__option-label">
						<?php echo esc_html( $opt_label ); ?>
					</label>
				</div>
			<?php endforeach; ?>
		</div>
	</fieldset>
</div>
