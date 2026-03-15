<?php
/**
 * Currency Field Type.
 *
 * Handles currency/money input fields with symbol formatting.
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
 * Class CurrencyField
 *
 * Currency/money input field type.
 *
 * Field configuration options:
 * - 'currency_symbol'   => (string) Currency symbol (default '$').
 * - 'currency_position' => (string) 'before' or 'after' (default 'before').
 * - 'precision'         => (int) Number of decimal places (default 2).
 * - 'min'               => (float) Minimum value allowed.
 * - 'max'               => (float) Maximum value allowed.
 * - 'allow_negative'    => (bool) Allow negative values (default false).
 *
 * @since 1.0.0
 */
class CurrencyField extends AbstractFieldType {

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
		return 'currency';
	}

	/**
	 * Render the currency field HTML.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $field Field configuration.
	 * @param mixed                $value Current value.
	 * @return string The rendered HTML.
	 */
	public function render( array $field, mixed $value ): string {
		$attributes     = $this->getCommonAttributes( $field );
		$precision      = (int) ( $field['precision'] ?? 2 );
		$symbol         = $field['currency_symbol'] ?? ( function_exists( 'apd_get_currency_symbol' ) ? \apd_get_currency_symbol() : '$' );
		$position       = $field['currency_position'] ?? ( function_exists( 'apd_get_currency_position' ) ? \apd_get_currency_position() : 'before' );
		$allow_negative = ! empty( $field['allow_negative'] );

		// Add currency-specific attributes.
		if ( isset( $field['min'] ) ) {
			$attributes['min'] = (float) $field['min'];
		} elseif ( ! $allow_negative ) {
			$attributes['min'] = 0;
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

		// Build input with currency symbol.
		$input = sprintf(
			'<input type="number" %s value="%s">',
			$this->buildAttributes( $attributes ),
			esc_attr( $display_value )
		);

		// Wrap with currency symbol.
		$symbol_html = sprintf(
			'<span class="apd-currency-symbol" aria-hidden="true">%s</span>',
			esc_html( $symbol )
		);

		if ( 'after' === $position ) {
			$html = sprintf(
				'<div class="apd-currency-field apd-currency-after">%s%s</div>',
				$input,
				$symbol_html
			);
		} else {
			$html = sprintf(
				'<div class="apd-currency-field apd-currency-before">%s%s</div>',
				$symbol_html,
				$input
			);
		}

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

		$errors         = new WP_Error();
		$allow_negative = ! empty( $field['allow_negative'] );

		// Check if numeric.
		if ( ! is_numeric( $value ) ) {
			$errors->add(
				'not_numeric',
				sprintf(
					/* translators: %s: field label */
					__( '%s must be a valid amount.', 'all-purpose-directory' ),
					$this->getLabel( $field )
				)
			);
			return $errors;
		}

		$float_value = (float) $value;

		// Check for negative values.
		if ( ! $allow_negative && $float_value < 0 ) {
			$errors->add(
				'negative_value',
				sprintf(
					/* translators: %s: field label */
					__( '%s cannot be negative.', 'all-purpose-directory' ),
					$this->getLabel( $field )
				)
			);
		}

		// Check minimum value.
		if ( isset( $field['min'] ) && $float_value < (float) $field['min'] ) {
			$errors->add(
				'min_value',
				sprintf(
					/* translators: 1: field label, 2: minimum value with currency */
					__( '%1$s must be at least %2$s.', 'all-purpose-directory' ),
					$this->getLabel( $field ),
					$this->formatWithCurrency( (float) $field['min'], $field )
				)
			);
		}

		// Check maximum value.
		if ( isset( $field['max'] ) && $float_value > (float) $field['max'] ) {
			$errors->add(
				'max_value',
				sprintf(
					/* translators: 1: field label, 2: maximum value with currency */
					__( '%1$s must be no more than %2$s.', 'all-purpose-directory' ),
					$this->getLabel( $field ),
					$this->formatWithCurrency( (float) $field['max'], $field )
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
	 * @return string The formatted value with currency symbol.
	 */
	public function formatValue( mixed $value, array $field ): string {
		if ( $value === '' || $value === null ) {
			return '';
		}

		return esc_html( $this->formatWithCurrency( (float) $value, $field ) );
	}

	/**
	 * Check if a value is empty.
	 *
	 * Currency values are considered empty only if null or empty string.
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
	 * Format a value with currency symbol.
	 *
	 * @since 1.0.0
	 *
	 * @param float                $value The value to format.
	 * @param array<string, mixed> $field Field configuration.
	 * @return string The formatted value with currency symbol.
	 */
	protected function formatWithCurrency( float $value, array $field ): string {
		$precision = (int) ( $field['precision'] ?? 2 );
		$symbol    = $field['currency_symbol'] ?? ( function_exists( 'apd_get_currency_symbol' ) ? \apd_get_currency_symbol() : '$' );
		$position  = $field['currency_position'] ?? ( function_exists( 'apd_get_currency_position' ) ? \apd_get_currency_position() : 'before' );

		$formatted_number = number_format( $value, $precision, '.', ',' );

		if ( 'after' === $position ) {
			return $formatted_number . $symbol;
		}

		return $symbol . $formatted_number;
	}
}
