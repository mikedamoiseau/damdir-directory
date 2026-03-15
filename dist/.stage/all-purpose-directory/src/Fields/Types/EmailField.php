<?php
/**
 * Email Field Type.
 *
 * Provides email input with validation and mailto link formatting.
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
 * Class EmailField
 *
 * Handles email address input fields with validation.
 *
 * @since 1.0.0
 */
class EmailField extends AbstractFieldType {

	/**
	 * Features supported by this field type.
	 *
	 * @var array<string, bool>
	 */
	protected array $supports = [
		'searchable' => true,
		'filterable' => false,
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
		return 'email';
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
		$attributes['type'] = 'email';

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
	 * @since 1.0.0
	 *
	 * @param mixed $value The raw value.
	 * @return mixed The sanitized value.
	 */
	public function sanitize( mixed $value ): mixed {
		if ( ! is_string( $value ) ) {
			return '';
		}

		return sanitize_email( $value );
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

		// Skip email format validation if empty (required check is handled by parent).
		if ( $this->isEmpty( $value ) ) {
			return true;
		}

		// Validate email format.
		if ( ! is_email( $value ) ) {
			return new WP_Error(
				'invalid_email',
				sprintf(
					/* translators: %s: field label */
					__( '%s must be a valid email address.', 'all-purpose-directory' ),
					$this->getLabel( $field )
				)
			);
		}

		return true;
	}

	/**
	 * Format the value for display.
	 *
	 * Creates a mailto link for the email address.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed                $value The stored value.
	 * @param array<string, mixed> $field Field configuration.
	 * @return string The formatted value with mailto link.
	 */
	public function formatValue( mixed $value, array $field ): string {
		if ( ! is_string( $value ) || $value === '' ) {
			return '';
		}

		$email = sanitize_email( $value );

		if ( $email === '' ) {
			return '';
		}

		return sprintf(
			'<a href="mailto:%s">%s</a>',
			esc_attr( $email ),
			esc_html( $email )
		);
	}
}
