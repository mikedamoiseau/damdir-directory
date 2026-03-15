<?php
/**
 * Decimal Field Type.
 *
 * Handles decimal/float number input fields with precision control.
 *
 * @package APD\Fields\Types
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Fields\Types;

use APD\Fields\AbstractFieldType;
use WP_Error;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DecimalField
 *
 * Decimal/float number input field type.
 *
 * Field configuration options:
 * - 'min'       => (float) Minimum value allowed.
 * - 'max'       => (float) Maximum value allowed.
 * - 'precision' => (int) Number of decimal places (default 2).
 *
 * @since 1.0.0
 */
class DecimalField extends AbstractFieldType {

	/**
	 * Features supported by this field type.
	 *
	 * @var array<string, bool>
	 */
	protected array $supports = [
		'searchable' => false,
		'filterable' => true,
		'sortable'   => true,
		'repeater'   => false,
	];

	/**
	 * Get the field type identifier.
	 *
	 * @since 1.0.0
	 *
	 * @return string The field type identifier.
	 */
	public function getType(): string {
		return 'decimal';
	}

	/**
	 * Render the decimal field HTML.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $field Field configuration.
	 * @param mixed                $value Current value.
	 * @return string The rendered HTML.
	 */
	public function render( array $field, mixed $value ): string {
		$attributes = $this->getCommonAttributes( $field );
		$precision  = (int) ( $field['precision'] ?? 2 );

		// Add decimal-specific attributes.
		if ( isset( $field['min'] ) ) {
			$attributes['min'] = (float) $field['min'];
		}

		if ( isset( $field['max'] ) ) {
			$attributes['max'] = (float) $field['max'];
		}

		// Calculate step based on precision.
		$attributes['step'] = $this->calculateStep( $precision );

		// Format value with precision.
		$display_value = $value !== '' && $value !== null
			? number_format( (float) $value, $precision, '.', '' )
			: '';

		$html = sprintf(
			'<input type="number" %s value="%s">',
			$this->buildAttributes( $attributes ),
			esc_attr( $display_value )
		);

		$html .= $this->renderDescription( $field );

		return $html;
	}

	/**
	 * Sanitize the field value.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value     The raw value.
	 * @param int   $precision Optional. Decimal precision (default 2).
	 * @return float The sanitized float value.
	 */
	public function sanitize( mixed $value, int $precision = 2 ): float {
		if ( $value === '' || $value === null ) {
			return 0.0;
		}

		$float_value = (float) $value;

		// Round to specified precision.
		return round( $float_value, $precision );
	}

	/**
	 * Sanitize with field configuration.
	 *
	 * This method is called when the field configuration is available.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed                $value The raw value.
	 * @param array<string, mixed> $field Field configuration.
	 * @return float The sanitized float value.
	 */
	public function sanitizeWithField( mixed $value, array $field ): float {
		$precision = (int) ( $field['precision'] ?? 2 );
		return $this->sanitize( $value, $precision );
	}

	/**
	 * Validate the field value.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed                $value The value to validate.
	 * @param array<string, mixed> $field Field configuration.
	 * @return bool|WP_Error True if valid, WP_Error on failure.
	 */
	public function validate( mixed $value, array $field ): bool|WP_Error {
		// Run parent validation first (required check).
		$parent_result = parent::validate( $value, $field );
		if ( is_wp_error( $parent_result ) ) {
			return $parent_result;
		}

		// Skip further validation if empty and not required.
		if ( $this->isEmpty( $value ) && ! $this->isRequired( $field ) ) {
			return true;
		}

		$errors = new WP_Error();

		// Check if numeric.
		if ( ! is_numeric( $value ) ) {
			$errors->add(
				'not_numeric',
				sprintf(
					/* translators: %s: field label */
					__( '%s must be a number.', 'all-purpose-directory' ),
					$this->getLabel( $field )
				)
			);
			return $errors;
		}

		$float_value = (float) $value;

		// Check minimum value.
		if ( isset( $field['min'] ) && $float_value < (float) $field['min'] ) {
			$errors->add(
				'min_value',
				sprintf(
					/* translators: 1: field label, 2: minimum value */
					__( '%1$s must be at least %2$s.', 'all-purpose-directory' ),
					$this->getLabel( $field ),
					$this->formatNumber( (float) $field['min'], $field )
				)
			);
		}

		// Check maximum value.
		if ( isset( $field['max'] ) && $float_value > (float) $field['max'] ) {
			$errors->add(
				'max_value',
				sprintf(
					/* translators: 1: field label, 2: maximum value */
					__( '%1$s must be no more than %2$s.', 'all-purpose-directory' ),
					$this->getLabel( $field ),
					$this->formatNumber( (float) $field['max'], $field )
				)
			);
		}

		return $errors->has_errors() ? $errors : true;
	}

	/**
	 * Get the default value for this field type.
	 *
	 * @since 1.0.0
	 *
	 * @return float The default value.
	 */
	public function getDefaultValue(): mixed {
		return 0.0;
	}

	/**
	 * Format the value for display.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed                $value The stored value.
	 * @param array<string, mixed> $field Field configuration.
	 * @return string The formatted value.
	 */
	public function formatValue( mixed $value, array $field ): string {
		if ( $value === '' || $value === null ) {
			return '';
		}

		return esc_html( $this->formatNumber( (float) $value, $field ) );
	}

	/**
	 * Check if a value is empty.
	 *
	 * Decimals are considered empty only if null or empty string.
	 * Zero is a valid value.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The value to check.
	 * @return bool True if empty.
	 */
	protected function isEmpty( mixed $value ): bool {
		return $value === null || $value === '';
	}

	/**
	 * Calculate the step attribute based on precision.
	 *
	 * @since 1.0.0
	 *
	 * @param int $precision Number of decimal places.
	 * @return string The step value.
	 */
	protected function calculateStep( int $precision ): string {
		if ( $precision <= 0 ) {
			return '1';
		}

		return '0.' . str_repeat( '0', $precision - 1 ) . '1';
	}

	/**
	 * Format a number with field precision.
	 *
	 * @since 1.0.0
	 *
	 * @param float                $value The number to format.
	 * @param array<string, mixed> $field Field configuration.
	 * @return string The formatted number.
	 */
	protected function formatNumber( float $value, array $field ): string {
		$precision = (int) ( $field['precision'] ?? 2 );
		return number_format( $value, $precision, '.', '' );
	}
}
