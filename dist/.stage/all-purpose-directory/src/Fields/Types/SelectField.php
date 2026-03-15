<?php
/**
 * Select Field Type.
 *
 * Single dropdown select input for choosing one option from a list.
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
 * Class SelectField
 *
 * Handles rendering and processing of single-select dropdown inputs.
 *
 * @since 1.0.0
 */
class SelectField extends AbstractFieldType {

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
		return 'select';
	}

	/**
	 * Render the select field HTML.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $field Field configuration.
	 * @param mixed                $value Current value.
	 * @return string The rendered HTML.
	 */
	public function render( array $field, mixed $value ): string {
		$attributes = $this->getCommonAttributes( $field );
		$options    = $field['options'] ?? [];

		$html = sprintf(
			'<select %s>',
			$this->buildAttributes( $attributes )
		);

		// Add empty option if configured.
		if ( isset( $field['empty_option'] ) ) {
			$html .= sprintf(
				'<option value="">%s</option>',
				esc_html( $field['empty_option'] )
			);
		}

		// Render options.
		foreach ( $options as $option_value => $option_label ) {
			$selected = selected( (string) $value, (string) $option_value, false );
			$html    .= sprintf(
				'<option value="%s"%s>%s</option>',
				esc_attr( (string) $option_value ),
				$selected,
				esc_html( $option_label )
			);
		}

		$html .= '</select>';
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
