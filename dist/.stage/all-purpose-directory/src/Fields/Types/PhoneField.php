<?php
/**
 * Phone Field Type.
 *
 * Provides phone number input with validation and tel link formatting.
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
 * Class PhoneField
 *
 * Handles phone number input fields with validation.
 *
 * @since 1.0.0
 */
class PhoneField extends AbstractFieldType {

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
	 * Get the field type identifier.
	 *
	 * @since 1.0.0
	 *
	 * @return string The field type identifier.
	 */
	public function getType(): string {
		return 'phone';
	}

	/**
	 * Render the field HTML.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $field Field configuration.
	 * @param mixed                $value Current value.
	 * @return string The rendered HTML.
	 */
	public function render( array $field, mixed $value ): string {
		$attributes         = $this->getCommonAttributes( $field );
		$attributes['type'] = 'tel';

		if ( is_string( $value ) && $value !== '' ) {
			$attributes['value'] = $value;
		}

		$html  = '<input ' . $this->buildAttributes( $attributes ) . '>';
		$html .= $this->renderDescription( $field );

		return $html;
	}

	/**
	 * Sanitize the field value.
	 *
	 * Removes all characters except digits, plus sign, hyphens, spaces, and parentheses.
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

		// Remove all characters except digits, plus, hyphens, spaces, parentheses.
		$sanitized = preg_replace( '/[^0-9+\-\s().]/', '', $value );

		// Trim whitespace.
		return trim( $sanitized );
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
		// Run parent validation first.
		$parent_result = parent::validate( $value, $field );

		if ( is_wp_error( $parent_result ) ) {
			return $parent_result;
		}

		// Skip phone format validation if empty (required check is handled by parent).
		if ( $this->isEmpty( $value ) ) {
			return true;
		}

		// Validate phone format - must contain at least some digits.
		$digits_only = preg_replace( '/[^0-9]/', '', $value );

		// Phone number should have at least 7 digits (basic validation).
		if ( strlen( $digits_only ) < 7 ) {
			return new WP_Error(
				'invalid_phone',
				sprintf(
					/* translators: %s: field label */
					__( '%s must be a valid phone number.', 'all-purpose-directory' ),
					$this->getLabel( $field )
				)
			);
		}

		// Phone number should not exceed 15 digits (international standard).
		if ( strlen( $digits_only ) > 15 ) {
			return new WP_Error(
				'invalid_phone',
				sprintf(
					/* translators: %s: field label */
					__( '%s must be a valid phone number.', 'all-purpose-directory' ),
					$this->getLabel( $field )
				)
			);
		}

		return true;
	}

	/**
	 * Format the value for display.
	 *
	 * Creates a tel: link for the phone number.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed                $value The stored value.
	 * @param array<string, mixed> $field Field configuration.
	 * @return string The formatted value with tel link.
	 */
	public function formatValue( mixed $value, array $field ): string {
		if ( ! is_string( $value ) || $value === '' ) {
			return '';
		}

		$phone = trim( $value );

		if ( $phone === '' ) {
			return '';
		}

		// Create tel: link value (remove spaces and parentheses for href).
		$tel_href = preg_replace( '/[\s()]/', '', $phone );

		return sprintf(
			'<a href="tel:%s">%s</a>',
			esc_attr( $tel_href ),
			esc_html( $phone )
		);
	}
}
