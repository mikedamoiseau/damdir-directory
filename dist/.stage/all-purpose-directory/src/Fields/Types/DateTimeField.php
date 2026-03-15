<?php
/**
 * DateTime Field Type.
 *
 * HTML5 datetime-local input field for combined date and time values.
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
 * Class DateTimeField
 *
 * Handles rendering and processing of datetime-local inputs.
 *
 * Configuration options:
 * - 'min' => 'Y-m-d\TH:i' - Minimum allowed datetime
 * - 'max' => 'Y-m-d\TH:i' - Maximum allowed datetime
 * - 'datetime_format' => 'Y-m-d H:i' - DateTime format for display (uses WordPress date_i18n)
 *
 * @since 1.0.0
 */
class DateTimeField extends AbstractFieldType {

	/**
	 * Features supported by this field type.
	 *
	 * @var array<string, bool>
	 */
	protected array $supports = [
		'searchable' => false,
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
		return 'datetime';
	}

	/**
	 * Render the datetime-local input field HTML.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $field Field configuration.
	 * @param mixed                $value Current value.
	 * @return string The rendered HTML.
	 */
	public function render( array $field, mixed $value ): string {
		$attributes          = $this->getCommonAttributes( $field );
		$attributes['type']  = 'datetime-local';
		$attributes['value'] = $this->sanitize( $value );

		// Add min datetime if defined.
		if ( ! empty( $field['min'] ) ) {
			$attributes['min'] = esc_attr( $field['min'] );
		}

		// Add max datetime if defined.
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
	 * Sanitize the datetime value.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The raw value.
	 * @return string The sanitized datetime string (Y-m-d\TH:i format) or empty string.
	 */
	public function sanitize( mixed $value ): mixed {
		if ( empty( $value ) || ! is_string( $value ) ) {
			return '';
		}

		$value = sanitize_text_field( $value );

		// Validate datetime format.
		if ( ! $this->isValidDateTimeFormat( $value ) ) {
			return '';
		}

		// Normalize to Y-m-d\TH:i format (without seconds).
		return substr( $value, 0, 16 );
	}

	/**
	 * Validate the datetime value.
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

		// Validate datetime format.
		if ( ! $this->isValidDateTimeFormat( $value ) ) {
			$errors->add(
				'invalid_datetime',
				sprintf(
					/* translators: %s: field label */
					__( '%s must be a valid date and time.', 'all-purpose-directory' ),
					$this->getLabel( $field )
				)
			);
			return $errors;
		}

		$value_timestamp = strtotime( str_replace( 'T', ' ', $value ) );

		// Validate min datetime.
		if ( ! empty( $field['min'] ) && $this->isValidDateTimeFormat( $field['min'] ) ) {
			$min_timestamp = strtotime( str_replace( 'T', ' ', $field['min'] ) );
			if ( $value_timestamp < $min_timestamp ) {
				$errors->add(
					'datetime_too_early',
					sprintf(
						/* translators: 1: field label, 2: minimum datetime */
						__( '%1$s must be on or after %2$s.', 'all-purpose-directory' ),
						$this->getLabel( $field ),
						str_replace( 'T', ' ', $field['min'] )
					)
				);
			}
		}

		// Validate max datetime.
		if ( ! empty( $field['max'] ) && $this->isValidDateTimeFormat( $field['max'] ) ) {
			$max_timestamp = strtotime( str_replace( 'T', ' ', $field['max'] ) );
			if ( $value_timestamp > $max_timestamp ) {
				$errors->add(
					'datetime_too_late',
					sprintf(
						/* translators: 1: field label, 2: maximum datetime */
						__( '%1$s must be on or before %2$s.', 'all-purpose-directory' ),
						$this->getLabel( $field ),
						str_replace( 'T', ' ', $field['max'] )
					)
				);
			}
		}

		return $errors->has_errors() ? $errors : true;
	}

	/**
	 * Format the datetime value for display.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed                $value The stored value.
	 * @param array<string, mixed> $field Field configuration.
	 * @return string The formatted datetime.
	 */
	public function formatValue( mixed $value, array $field ): string {
		if ( empty( $value ) || ! is_string( $value ) ) {
			return '';
		}

		if ( ! $this->isValidDateTimeFormat( $value ) ) {
			return esc_html( $value );
		}

		// Use configured format or combine WordPress date and time formats.
		$format = $field['datetime_format']
			?? get_option( 'date_format', 'Y-m-d' ) . ' ' . get_option( 'time_format', 'H:i' );

		// Convert T separator to space for strtotime.
		$timestamp = strtotime( str_replace( 'T', ' ', $value ) );

		if ( $timestamp === false ) {
			return esc_html( $value );
		}

		return esc_html( date_i18n( $format, $timestamp ) );
	}

	/**
	 * Prepare the value for storage.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The sanitized value.
	 * @return string The value for storage.
	 */
	public function prepareValueForStorage( mixed $value ): mixed {
		if ( empty( $value ) ) {
			return '';
		}

		// Store with T separator as standard datetime-local format.
		return $value;
	}

	/**
	 * Prepare the value from storage.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The stored value.
	 * @return string The usable value.
	 */
	public function prepareValueFromStorage( mixed $value ): mixed {
		if ( empty( $value ) || ! is_string( $value ) ) {
			return '';
		}

		// Ensure T separator for datetime-local input.
		return str_replace( ' ', 'T', $value );
	}

	/**
	 * Check if a string is a valid datetime in Y-m-d\TH:i or Y-m-d\TH:i:s format.
	 *
	 * @since 1.0.0
	 *
	 * @param string $datetime The datetime string to validate.
	 * @return bool True if valid datetime format.
	 */
	protected function isValidDateTimeFormat( string $datetime ): bool {
		// Accept Y-m-d\TH:i or Y-m-d\TH:i:s format.
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2})?$/', $datetime ) ) {
			return false;
		}

		// Split date and time parts.
		$parts     = explode( 'T', $datetime );
		$date_part = $parts[0];
		$time_part = $parts[1];

		// Validate date.
		$date_parts = explode( '-', $date_part );
		if ( ! checkdate( (int) $date_parts[1], (int) $date_parts[2], (int) $date_parts[0] ) ) {
			return false;
		}

		// Validate time.
		$time_parts = explode( ':', $time_part );
		$hour       = (int) $time_parts[0];
		$min        = (int) $time_parts[1];
		$sec        = isset( $time_parts[2] ) ? (int) $time_parts[2] : 0;

		return $hour >= 0 && $hour <= 23 && $min >= 0 && $min <= 59 && $sec >= 0 && $sec <= 59;
	}
}
