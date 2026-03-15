<?php
/**
 * Color Field Type.
 *
 * HTML5 color picker input field for hex color values.
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
 * Class ColorField
 *
 * Handles rendering and processing of color picker fields.
 *
 * @since 1.0.0
 */
class ColorField extends AbstractFieldType {

	/**
	 * Features supported by this field type.
	 *
	 * @var array<string, bool>
	 */
	protected array $supports = [
		'searchable' => false,
		'filterable' => false,
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
		return 'color';
	}

	/**
	 * Render the color input field HTML.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $field Field configuration.
	 * @param mixed                $value Current value.
	 * @return string The rendered HTML.
	 */
	public function render( array $field, mixed $value ): string {
		$attributes         = $this->getCommonAttributes( $field );
		$attributes['type'] = 'color';

		// Use field default, then type default if no value.
		if ( is_string( $value ) && $value !== '' ) {
			$attributes['value'] = $this->normalizeHexColor( $value );
		} else {
			$attributes['value'] = $field['default'] ?? $this->getDefaultValue();
		}

		$html  = '<input ' . $this->buildAttributes( $attributes ) . '>';
		$html .= $this->renderDescription( $field );

		return $html;
	}

	/**
	 * Sanitize the field value.
	 *
	 * Uses sanitize_hex_color to ensure valid hex color format.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The raw value.
	 * @return mixed The sanitized value.
	 */
	public function sanitize( mixed $value ): mixed {
		if ( ! is_string( $value ) ) {
			return '';
		}

		$sanitized = sanitize_hex_color( $value );

		// Return empty string if sanitization fails (invalid color).
		return $sanitized ?? '';
	}

	/**
	 * Validate the field value.
	 *
	 * Checks for valid hex color format (#RRGGBB or #RGB).
	 *
	 * @since 1.0.0
	 *
	 * @param mixed                $value The value to validate.
	 * @param array<string, mixed> $field Field configuration.
	 * @return bool|WP_Error True if valid, WP_Error on failure.
	 */
	public function validate( mixed $value, array $field ): bool|WP_Error {
		// Run parent validation first.
		$parent_result = parent::validate( $value, $field );

		if ( is_wp_error( $parent_result ) ) {
			return $parent_result;
		}

		// Skip format validation if empty (required check is handled by parent).
		if ( $this->isEmpty( $value ) ) {
			return true;
		}

		// Validate hex color format.
		if ( ! $this->isValidHexColor( $value ) ) {
			return new WP_Error(
				'invalid_color',
				sprintf(
					/* translators: %s: field label */
					__( '%s must be a valid hex color (e.g., #FF5733 or #F53).', 'all-purpose-directory' ),
					$this->getLabel( $field )
				)
			);
		}

		return true;
	}

	/**
	 * Get the default value for this field type.
	 *
	 * @since 1.0.0
	 *
	 * @return string The default color value.
	 */
	public function getDefaultValue(): mixed {
		return '#000000';
	}

	/**
	 * Format the value for display.
	 *
	 * Returns the hex color value.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed                $value The stored value.
	 * @param array<string, mixed> $field Field configuration.
	 * @return string The formatted value.
	 */
	public function formatValue( mixed $value, array $field ): string {
		if ( ! is_string( $value ) || $value === '' ) {
			return '';
		}

		return esc_html( $value );
	}

	/**
	 * Check if a value is a valid hex color.
	 *
	 * Accepts both 3-character (#RGB) and 6-character (#RRGGBB) formats.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The value to check.
	 * @return bool True if valid hex color.
	 */
	private function isValidHexColor( mixed $value ): bool {
		if ( ! is_string( $value ) ) {
			return false;
		}

		// Match #RGB or #RRGGBB format.
		return (bool) preg_match( '/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/', $value );
	}

	/**
	 * Normalize hex color to 6-character format.
	 *
	 * Expands 3-character colors (#RGB) to 6-character format (#RRGGBB).
	 *
	 * @since 1.0.0
	 *
	 * @param string $color The hex color.
	 * @return string The normalized hex color.
	 */
	private function normalizeHexColor( string $color ): string {
		// If 3-character format, expand to 6-character.
		if ( preg_match( '/^#([0-9A-Fa-f])([0-9A-Fa-f])([0-9A-Fa-f])$/', $color, $matches ) ) {
			return '#' . $matches[1] . $matches[1] . $matches[2] . $matches[2] . $matches[3] . $matches[3];
		}

		return $color;
	}
}
