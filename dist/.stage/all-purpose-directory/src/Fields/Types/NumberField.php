<?php
/**
 * Number Field Type.
 *
 * Handles integer number input fields with min/max/step validation.
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
 * Class NumberField
 *
 * Integer number input field type.
 *
 * Field configuration options:
 * - 'min'  => (int) Minimum value allowed.
 * - 'max'  => (int) Maximum value allowed.
 * - 'step' => (int) Step increment (default 1).
 *
 * @since 1.0.0
 */
class NumberField extends AbstractFieldType {

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
		return 'number';
	}

	/**
	 * Render the number field HTML.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $field Field configuration.
	 * @param mixed                $value Current value.
	 * @return string The rendered HTML.
	 */
	public function render( array $field, mixed $value ): string {
		$attributes = $this->getCommonAttributes( $field );

		// Add number-specific attributes.
		if ( isset( $field['min'] ) ) {
			$attributes['min'] = (int) $field['min'];
		}

		if ( isset( $field['max'] ) ) {
			$attributes['max'] = (int) $field['max'];
		}

		$attributes['step'] = (int) ( $field['step'] ?? 1 );

		// Ensure value is integer.
		$value = $value !== '' && $value !== null ? (int) $value : '';

		$html = sprintf(
			'<input type="number" %s value="%s">',
			$this->buildAttributes( $attributes ),
			esc_attr( (string) $value )
		);

		$html .= $this->renderDescription( $field );

		return $html;
	}

	/**
	 * Sanitize the field value.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The raw value.
	 * @return int The sanitized integer value.
	 */
	public function sanitize( mixed $value ): int {
		if ( $value === '' || $value === null ) {
			return 0;
		}

		// Use intval to support negative numbers.
		return (int) $value;
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

		$int_value = (int) $value;

		// Check minimum value.
		if ( isset( $field['min'] ) && $int_value < (int) $field['min'] ) {
			$errors->add(
				'min_value',
				sprintf(
					/* translators: 1: field label, 2: minimum value */
					__( '%1$s must be at least %2$d.', 'all-purpose-directory' ),
					$this->getLabel( $field ),
					(int) $field['min']
				)
			);
		}

		// Check maximum value.
		if ( isset( $field['max'] ) && $int_value > (int) $field['max'] ) {
			$errors->add(
				'max_value',
				sprintf(
					/* translators: 1: field label, 2: maximum value */
					__( '%1$s must be no more than %2$d.', 'all-purpose-directory' ),
					$this->getLabel( $field ),
					(int) $field['max']
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
	 * @return int The default value.
	 */
	public function getDefaultValue(): mixed {
		return 0;
	}

	/**
	 * Check if a value is empty.
	 *
	 * Numbers are considered empty only if null or empty string.
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
}
