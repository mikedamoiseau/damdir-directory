<?php
/**
 * Time Field Type.
 *
 * HTML5 time picker input field for time values.
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
 * Class TimeField
 *
 * Handles rendering and processing of time picker inputs.
 *
 * Configuration options:
 * - 'min' => 'H:i' - Minimum allowed time
 * - 'max' => 'H:i' - Maximum allowed time
 * - 'time_format' => 'H:i' - Time format for display
 *
 * @since 1.0.0
 */
class TimeField extends AbstractFieldType {

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
		return 'time';
	}

	/**
	 * Render the time input field HTML.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $field Field configuration.
	 * @param mixed                $value Current value.
	 * @return string The rendered HTML.
	 */
	public function render( array $field, mixed $value ): string {
		$attributes          = $this->getCommonAttributes( $field );
		$attributes['type']  = 'time';
		$attributes['value'] = $this->sanitize( $value );

		// Add min time if defined.
		if ( ! empty( $field['min'] ) ) {
			$attributes['min'] = esc_attr( $field['min'] );
		}

		// Add max time if defined.
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
	 * Sanitize the time value.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The raw value.
	 * @return string The sanitized time string (H:i format) or empty string.
	 */
	public function sanitize( mixed $value ): mixed {
		if ( empty( $value ) || ! is_string( $value ) ) {
			return '';
		}

		$value = sanitize_text_field( $value );

		// Validate time format (H:i or H:i:s).
		if ( ! $this->isValidTimeFormat( $value ) ) {
			return '';
		}

		// Normalize to H:i format.
		return substr( $value, 0, 5 );
	}

	/**
	 * Validate the time value.
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

		// Validate time format.
		if ( ! $this->isValidTimeFormat( $value ) ) {
			$errors->add(
				'invalid_time',
				sprintf(
					/* translators: %s: field label */
					__( '%s must be a valid time in HH:MM format.', 'all-purpose-directory' ),
					$this->getLabel( $field )
				)
			);
			return $errors;
		}

		// Normalize value for comparison.
		$normalized_value = substr( $value, 0, 5 );

		// Validate min time.
		if ( ! empty( $field['min'] ) && $this->isValidTimeFormat( $field['min'] ) ) {
			$min_time = substr( $field['min'], 0, 5 );
			if ( strcmp( $normalized_value, $min_time ) < 0 ) {
				$errors->add(
					'time_too_early',
					sprintf(
						/* translators: 1: field label, 2: minimum time */
						__( '%1$s must be at or after %2$s.', 'all-purpose-directory' ),
						$this->getLabel( $field ),
						$min_time
					)
				);
			}
		}

		// Validate max time.
		if ( ! empty( $field['max'] ) && $this->isValidTimeFormat( $field['max'] ) ) {
			$max_time = substr( $field['max'], 0, 5 );
			if ( strcmp( $normalized_value, $max_time ) > 0 ) {
				$errors->add(
					'time_too_late',
					sprintf(
						/* translators: 1: field label, 2: maximum time */
						__( '%1$s must be at or before %2$s.', 'all-purpose-directory' ),
						$this->getLabel( $field ),
						$max_time
					)
				);
			}
		}

		return $errors->has_errors() ? $errors : true;
	}

	/**
	 * Format the time value for display.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed                $value The stored value.
	 * @param array<string, mixed> $field Field configuration.
	 * @return string The formatted time.
	 */
	public function formatValue( mixed $value, array $field ): string {
		if ( empty( $value ) || ! is_string( $value ) ) {
			return '';
		}

		if ( ! $this->isValidTimeFormat( $value ) ) {
			return esc_html( $value );
		}

		// Use configured format or default to WordPress time format.
		$format    = $field['time_format'] ?? get_option( 'time_format', 'H:i' );
		$timestamp = strtotime( '1970-01-01 ' . $value );

		if ( $timestamp === false ) {
			return esc_html( $value );
		}

		return esc_html( date_i18n( $format, $timestamp ) );
	}

	/**
	 * Check if a string is a valid time in H:i or H:i:s format.
	 *
	 * @since 1.0.0
	 *
	 * @param string $time The time string to validate.
	 * @return bool True if valid time format.
	 */
	protected function isValidTimeFormat( string $time ): bool {
		// Accept H:i or H:i:s format.
		if ( ! preg_match( '/^\d{2}:\d{2}(:\d{2})?$/', $time ) ) {
			return false;
		}

		$parts = explode( ':', $time );
		$hour  = (int) $parts[0];
		$min   = (int) $parts[1];
		$sec   = isset( $parts[2] ) ? (int) $parts[2] : 0;

		return $hour >= 0 && $hour <= 23 && $min >= 0 && $min <= 59 && $sec >= 0 && $sec <= 59;
	}
}
