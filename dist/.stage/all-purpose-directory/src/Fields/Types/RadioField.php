<?php
/**
 * Radio Field Type.
 *
 * Radio button group for selecting one option from a list.
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
 * Class RadioField
 *
 * Handles rendering and processing of radio button group inputs.
 *
 * @since 1.0.0
 */
class RadioField extends AbstractFieldType {

	/**
	 * Features supported by this field type.
	 *
	 * @var array<string, bool>
	 */
	protected array $supports = [
		'searchable' => false,
		'filterable' => true,
		'sortable'   => false,
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
		return 'radio';
	}

	/**
	 * Render the radio button group field HTML.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $field Field configuration.
	 * @param mixed                $value Current value.
	 * @return string The rendered HTML.
	 */
	public function render( array $field, mixed $value ): string {
		$field_id   = $this->getFieldId( $field );
		$field_name = $this->getFieldName( $field );
		$options    = $field['options'] ?? [];

		$required_attr = '';
		if ( $this->isRequired( $field ) ) {
			$required_attr = ' aria-required="true"';
		}

		$describedby = '';
		if ( ! empty( $field['description'] ) ) {
			$describedby = sprintf( ' aria-describedby="%s"', esc_attr( $field_id . '-description' ) );
		}

		$html = sprintf(
			'<fieldset class="apd-radio-group" id="%s" role="radiogroup"%s%s>',
			esc_attr( $field_id ),
			$required_attr,
			$describedby
		);

		$html .= sprintf(
			'<legend class="apd-radio-legend">%s</legend>',
			esc_html( $this->getLabel( $field ) )
		);

		$index = 0;
		foreach ( $options as $option_value => $option_label ) {
			$option_id  = $field_id . '-' . $index;
			$is_checked = (string) $value === (string) $option_value;
			$checked    = $is_checked ? ' checked="checked"' : '';
			$required   = $this->isRequired( $field ) && $index === 0 ? ' required' : '';

			$html .= '<label class="apd-radio-item">';
			$html .= sprintf(
				'<input type="radio" id="%s" name="%s" value="%s"%s%s>',
				esc_attr( $option_id ),
				esc_attr( $field_name ),
				esc_attr( (string) $option_value ),
				$checked,
				$required
			);
			$html .= ' <span class="apd-radio-text">' . esc_html( $option_label ) . '</span>';
			$html .= '</label>';

			++$index;
		}

		$html .= '</fieldset>';
		$html .= $this->renderDescription( $field );

		return $html;
	}

	/**
	 * Validate the field value.
	 *
	 * Ensures the selected value exists in the options array.
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

		// Skip option validation if empty (handled by required check).
		if ( $this->isEmpty( $value ) ) {
			return true;
		}

		// Check value exists in options.
		$options = $field['options'] ?? [];
		if ( ! array_key_exists( (string) $value, $options ) ) {
			return new WP_Error(
				'invalid_option',
				sprintf(
					/* translators: %s: field label */
					__( '%s contains an invalid selection.', 'all-purpose-directory' ),
					$this->getLabel( $field )
				)
			);
		}

		return true;
	}

	/**
	 * Format the value for display.
	 *
	 * Returns the label for the selected value.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed                $value The stored value.
	 * @param array<string, mixed> $field Field configuration.
	 * @return string The formatted value (option label).
	 */
	public function formatValue( mixed $value, array $field ): string {
		if ( $this->isEmpty( $value ) ) {
			return '';
		}

		$options = $field['options'] ?? [];
		$label   = $options[ (string) $value ] ?? (string) $value;

		return esc_html( $label );
	}
}
