<?php
/**
 * Date Field Type.
 *
 * HTML5 date picker input field for date values.
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
 * Class DateField
 *
 * Handles rendering and processing of date picker inputs.
 *
 * Configuration options:
 * - 'min' => 'Y-m-d' - Minimum allowed date
 * - 'max' => 'Y-m-d' - Maximum allowed date
 * - 'date_format' => 'Y-m-d' - Date format for display (uses WordPress date_i18n)
 *
 * @since 1.0.0
 */
class DateField extends AbstractFieldType {

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
		return 'date';
	}

	/**
	 * Render the date input field HTML.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $field Field configuration.
	 * @param mixed                $value Current value.
	 * @return string The rendered HTML.
	 */
	public function render( array $field, mixed $value ): string {
		$attributes          = $this->getCommonAttributes( $field );
		$attributes['type']  = 'date';
		$attributes['value'] = $this->sanitize( $value );

		// Add min date if defined.
		if ( ! empty( $field['min'] ) ) {
			$attributes['min'] = esc_attr( $field['min'] );
		}

		// Add max date if defined.
		if ( ! empty( $field['max'] ) ) {
			$attributes['max'] = esc_attr( $field['max'] );
		}

		$html = sprintf(
			'<input %s>',
			$this->buildAttributes( $attributes )
		);

		$html .= $this->renderDescription( $field );

		return $html;
	}

	/**
	 * Sanitize the date value.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The raw value.
	 * @return string The sanitized date string (Y-m-d format) or empty string.
	 */
	public function sanitize( mixed $value ): mixed {
		if ( empty( $value ) || ! is_string( $value ) ) {
			return '';
		}

		$value = sanitize_text_field( $value );

		// Validate date format (Y-m-d).
		if ( ! $this->isValidDateFormat( $value ) ) {
			return '';
		}

		return $value;
	}

	/**
	 * Validate the date value.
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
		if ( $this->isEmpty( $value ) ) {
			return true;
		}

		$errors = new WP_Error();

		// Validate date format.
		if ( ! $this->isValidDateFormat( $value ) ) {
			$errors->add(
				'invalid_date',
				sprintf(
					/* translators: %s: field label */
					__( '%s must be a valid date in YYYY-MM-DD format.', 'all-purpose-directory' ),
					$this->getLabel( $field )
				)
			);
			return $errors;
		}

		// Validate min date.
		if ( ! empty( $field['min'] ) && $this->isValidDateFormat( $field['min'] ) ) {
			if ( strtotime( $value ) < strtotime( $field['min'] ) ) {
				$errors->add(
					'date_too_early',
					sprintf(
						/* translators: 1: field label, 2: minimum date */
						__( '%1$s must be on or after %2$s.', 'all-purpose-directory' ),
						$this->getLabel( $field ),
						$field['min']
					)
				);
			}
		}

		// Validate max date.
		if ( ! empty( $field['max'] ) && $this->isValidDateFormat( $field['max'] ) ) {
			if ( strtotime( $value ) > strtotime( $field['max'] ) ) {
				$errors->add(
					'date_too_late',
					sprintf(
						/* translators: 1: field label, 2: maximum date */
						__( '%1$s must be on or before %2$s.', 'all-purpose-directory' ),
						$this->getLabel( $field ),
						$field['max']
					)
				);
			}
		}

		return $errors->has_errors() ? $errors : true;
	}

	/**
	 * Format the date value for display.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed                $value The stored value.
	 * @param array<string, mixed> $field Field configuration.
	 * @return string The formatted date.
	 */
	public function formatValue( mixed $value, array $field ): string {
		if ( empty( $value ) || ! is_string( $value ) ) {
			return '';
		}

		if ( ! $this->isValidDateFormat( $value ) ) {
			return esc_html( $value );
		}

		// Use configured format or default to WordPress date format.
		$format    = $field['date_format'] ?? get_option( 'date_format', 'Y-m-d' );
		$timestamp = strtotime( $value );

		if ( $timestamp === false ) {
			return esc_html( $value );
		}

		return esc_html( date_i18n( $format, $timestamp ) );
	}

	/**
	 * Check if a string is a valid date in Y-m-d format.
	 *
	 * @since 1.0.0
	 *
	 * @param string $date The date string to validate.
	 * @return bool True if valid Y-m-d format.
	 */
	protected function isValidDateFormat( string $date ): bool {
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return false;
		}

		$parts = explode( '-', $date );
		return checkdate( (int) $parts[1], (int) $parts[2], (int) $parts[0] );
	}
}
