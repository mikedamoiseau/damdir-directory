<?php
/**
 * Textarea Field Type.
 *
 * Multi-line text input field for longer text content.
 *
 * @package APD\Fields\Types
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Fields\Types;

use APD\Fields\AbstractFieldType;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TextareaField
 *
 * Handles rendering and processing of multi-line text inputs.
 *
 * @since 1.0.0
 */
class TextareaField extends AbstractFieldType {

	/**
	 * Features supported by this field type.
	 *
	 * @var array<string, bool>
	 */
	protected array $supports = [
		'searchable' => true,
		'filterable' => false,
		'sortable'   => false,
		'repeater'   => false,
	];

	/**
	 * Default number of rows for the textarea.
	 *
	 * @var int
	 */
	private const DEFAULT_ROWS = 5;

	/**
	 * Get the field type identifier.
	 *
	 * @since 1.0.0
	 *
	 * @return string The field type identifier.
	 */
	public function getType(): string {
		return 'textarea';
	}

	/**
	 * Render the textarea field HTML.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $field Field configuration.
	 * @param mixed                $value Current value.
	 * @return string The rendered HTML.
	 */
	public function render( array $field, mixed $value ): string {
		$attributes         = $this->getCommonAttributes( $field );
		$attributes['rows'] = $field['rows'] ?? self::DEFAULT_ROWS;

		// Add maxlength if defined in validation rules.
		if ( isset( $field['validation']['max_length'] ) ) {
			$attributes['maxlength'] = (int) $field['validation']['max_length'];
		}

		// Add minlength if defined in validation rules.
		if ( isset( $field['validation']['min_length'] ) ) {
			$attributes['minlength'] = (int) $field['validation']['min_length'];
		}

		$html = sprintf(
			'<textarea %s>%s</textarea>',
			$this->buildAttributes( $attributes ),
			esc_html( (string) $value )
		);

		$html .= $this->renderDescription( $field );

		return $html;
	}

	/**
	 * Sanitize the textarea value.
	 *
	 * Uses sanitize_textarea_field to preserve line breaks.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The raw value.
	 * @return mixed The sanitized value.
	 */
	public function sanitize( mixed $value ): mixed {
		if ( is_array( $value ) ) {
			return array_map( 'sanitize_textarea_field', $value );
		}

		if ( is_string( $value ) ) {
			return sanitize_textarea_field( $value );
		}

		return $value;
	}

	/**
	 * Format the value for display.
	 *
	 * Converts newlines to HTML line breaks for display.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed                $value The stored value.
	 * @param array<string, mixed> $field Field configuration.
	 * @return string The formatted value.
	 */
	public function formatValue( mixed $value, array $field ): string {
		if ( is_array( $value ) ) {
			return esc_html( implode( ', ', array_map( 'strval', $value ) ) );
		}

		// Convert newlines to <br> tags for display.
		return nl2br( esc_html( (string) $value ) );
	}
}
