<?php
/**
 * Checkbox Group Field Type.
 *
 * Multiple checkboxes for selecting multiple options from a list.
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
 * Class CheckboxGroupField
 *
 * Handles rendering and processing of checkbox group inputs.
 *
 * @since 1.0.0
 */
class CheckboxGroupField extends AbstractFieldType {

	/**
	 * Features supported by this field type.
	 *
	 * @var array<string, bool>
	 */
	protected array $supports = [
		'searchable' => false,
		'filterable' => true,
		'sortable'   => false,
		'repeater'   => true,
	];

	/**
	 * Get the field type identifier.
	 *
	 * @since 1.0.0
	 *
	 * @return string The field type identifier.
	 */
	public function getType(): string {
		return 'checkboxgroup';
	}

	/**
	 * Render the checkbox group field HTML.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $field Field configuration.
	 * @param mixed                $value Current value (array of checked values).
	 * @return string The rendered HTML.
	 */
	public function render( array $field, mixed $value ): string {
		$field_id   = $this->getFieldId( $field );
		$field_name = $this->getFieldName( $field ) . '[]';
		$options    = $field['options'] ?? [];

		// Ensure value is an array.
		$checked_values = is_array( $value ) ? array_map( 'strval', $value ) : [];

		$required_attr = '';
		if ( $this->isRequired( $field ) ) {
			$required_attr = ' aria-required="true"';
		}

		$describedby = '';
		if ( ! empty( $field['description'] ) ) {
			$describedby = sprintf( ' aria-describedby="%s"', esc_attr( $field_id . '-description' ) );
		}

		$html = sprintf(
			'<fieldset class="apd-checkbox-group" id="%s" role="group"%s%s>',
			esc_attr( $field_id ),
			$required_attr,
			$describedby
		);

		$html .= sprintf(
			'<legend class="screen-reader-text">%s</legend>',
			esc_html( $this->getLabel( $field ) )
		);

		$index = 0;
		foreach ( $options as $option_value => $option_label ) {
			$option_id  = $field_id . '-' . $index;
			$is_checked = in_array( (string) $option_value, $checked_values, true );
			$checked    = $is_checked ? ' checked="checked"' : '';

			$html .= '<label class="apd-checkbox-group-item">';
			$html .= sprintf(
				'<input type="checkbox" id="%s" name="%s" value="%s"%s>',
				esc_attr( $option_id ),
				esc_attr( $field_name ),
				esc_attr( (string) $option_value ),
				$checked
			);
			$html .= ' <span class="apd-checkbox-text">' . esc_html( $option_label ) . '</span>';
			$html .= '</label>';

			++$index;
		}

		$html .= '</fieldset>';
		$html .= $this->renderDescription( $field );

		return $html;
	}

	/**
	 * Sanitize the field value.
	 *
	 * Ensures value is an array of sanitized strings.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The raw value.
	 * @return array<string> The sanitized array of values.
	 */
	public function sanitize( mixed $value ): array {
		if ( ! is_array( $value ) ) {
			return [];
		}

		return array_map( 'sanitize_text_field', $value );
	}

	/**
	 * Validate the field value.
	 *
	 * Ensures all checked values exist in the options array.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed                $value The value to validate.
	 * @param array<string, mixed> $field Field configuration.
	 * @return bool|WP_Error True if valid, WP_Error on failure.
	 */
	public function validate( mixed $value, array $field ): bool|WP_Error {
		// First run parent validation (required check).
		$parent_result = parent::validate( $value, $field );
		if ( is_wp_error( $parent_result ) ) {
			return $parent_result;
		}

		// Skip option validation if empty.
		if ( $this->isEmpty( $value ) ) {
			return true;
		}

		// Ensure value is array.
		$values = is_array( $value ) ? $value : [];

		// Check all values exist in options.
		$options       = $field['options'] ?? [];
		$option_keys   = array_map( 'strval', array_keys( $options ) );
		$string_values = array_map( 'strval', $values );

		foreach ( $string_values as $checked_value ) {
			if ( ! in_array( $checked_value, $option_keys, true ) ) {
				return new WP_Error(
					'invalid_option',
					sprintf(
						/* translators: %s: field label */
						__( '%s contains an invalid selection.', 'all-purpose-directory' ),
						$this->getLabel( $field )
					)
				);
			}
		}

		return true;
	}

	/**
	 * Get the default value for this field type.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string> Empty array as default.
	 */
	public function getDefaultValue(): array {
		return [];
	}

	/**
	 * Format the value for display.
	 *
	 * Returns comma-separated labels for checked values.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed                $value The stored value.
	 * @param array<string, mixed> $field Field configuration.
	 * @return string The formatted value (comma-separated labels).
	 */
	public function formatValue( mixed $value, array $field ): string {
		if ( $this->isEmpty( $value ) ) {
			return '';
		}

		$values  = is_array( $value ) ? $value : [];
		$options = $field['options'] ?? [];
		$labels  = [];

		foreach ( $values as $checked_value ) {
			$label    = $options[ (string) $checked_value ] ?? (string) $checked_value;
			$labels[] = $label;
		}

		return esc_html( implode( ', ', $labels ) );
	}

	/**
	 * Prepare the value for storage.
	 *
	 * JSON encodes the array of checked values.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The sanitized value.
	 * @return string JSON encoded array.
	 */
	public function prepareValueForStorage( mixed $value ): string {
		if ( ! is_array( $value ) ) {
			return '[]';
		}

		return wp_json_encode( array_values( $value ) ) ?: '[]';
	}

	/**
	 * Prepare the value from storage.
	 *
	 * JSON decodes the stored value back to an array.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The stored value.
	 * @return array<string> The array of checked values.
	 */
	public function prepareValueFromStorage( mixed $value ): array {
		if ( is_array( $value ) ) {
			return $value;
		}

		if ( is_string( $value ) && ! empty( $value ) ) {
			$decoded = json_decode( $value, true );
			if ( is_array( $decoded ) ) {
				return $decoded;
			}
		}

		return [];
	}
}
