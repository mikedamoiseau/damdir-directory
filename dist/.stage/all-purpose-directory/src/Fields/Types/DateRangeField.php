<?php
/**
 * Date Range Field Type.
 *
 * Start/end date pair field for date ranges.
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
 * Class DateRangeField
 *
 * Handles rendering and processing of date range inputs (start/end date pair).
 *
 * Configuration options:
 * - 'min' => 'Y-m-d' - Minimum allowed date for both fields
 * - 'max' => 'Y-m-d' - Maximum allowed date for both fields
 * - 'date_format' => 'Y-m-d' - Date format for display (uses WordPress date_i18n)
 * - 'separator' => ' - ' - Separator between dates in formatted output
 *
 * Stored value format: JSON array with 'start' and 'end' keys.
 *
 * @since 1.0.0
 */
class DateRangeField extends AbstractFieldType {

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
		return 'daterange';
	}

	/**
	 * Render the date range input fields HTML.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $field Field configuration.
	 * @param mixed                $value Current value (array with 'start' and 'end' keys).
	 * @return string The rendered HTML.
	 */
	public function render( array $field, mixed $value ): string {
		$value       = $this->normalizeValue( $value );
		$field_id    = $this->getFieldId( $field );
		$field_name  = $this->getFieldName( $field );
		$is_required = $this->isRequired( $field );

		// Common attributes for both inputs.
		$common_attrs = [];
		if ( ! empty( $field['min'] ) ) {
			$common_attrs['min'] = esc_attr( $field['min'] );
		}
		if ( ! empty( $field['max'] ) ) {
			$common_attrs['max'] = esc_attr( $field['max'] );
		}
		if ( ! empty( $field['class'] ) ) {
			$common_attrs['class'] = $field['class'];
		}

		// Start date attributes.
		$start_attrs = array_merge(
			$common_attrs,
			[
				'type'  => 'date',
				'id'    => $field_id . '-start',
				'name'  => $field_name . '[start]',
				'value' => $value['start'],
			]
		);

		if ( $is_required ) {
			$start_attrs['required']      = true;
			$start_attrs['aria-required'] = 'true';
		}

		// End date attributes.
		$end_attrs = array_merge(
			$common_attrs,
			[
				'type'  => 'date',
				'id'    => $field_id . '-end',
				'name'  => $field_name . '[end]',
				'value' => $value['end'],
			]
		);

		if ( $is_required ) {
			$end_attrs['required']      = true;
			$end_attrs['aria-required'] = 'true';
		}

		// Add aria-describedby if description exists.
		if ( ! empty( $field['description'] ) ) {
			$description_id                  = $field_id . '-description';
			$start_attrs['aria-describedby'] = $description_id;
			$end_attrs['aria-describedby']   = $description_id;
		}

		// Build HTML.
		$html = sprintf(
			'<div class="apd-daterange-wrapper" id="%s">',
			esc_attr( $field_id )
		);

		$html .= '<div class="apd-daterange-start">';
		$html .= sprintf(
			'<label for="%s">%s</label>',
			esc_attr( $field_id . '-start' ),
			esc_html__( 'Start Date', 'all-purpose-directory' )
		);
		$html .= sprintf( '<input %s>', $this->buildAttributes( $start_attrs ) );
		$html .= '</div>';

		$html .= '<div class="apd-daterange-end">';
		$html .= sprintf(
			'<label for="%s">%s</label>',
			esc_attr( $field_id . '-end' ),
			esc_html__( 'End Date', 'all-purpose-directory' )
		);
		$html .= sprintf( '<input %s>', $this->buildAttributes( $end_attrs ) );
		$html .= '</div>';

		$html .= '</div>';

		$html .= $this->renderDescription( $field );

		return $html;
	}

	/**
	 * Sanitize the date range value.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The raw value.
	 * @return array{start: string, end: string} The sanitized date range array.
	 */
	public function sanitize( mixed $value ): mixed {
		$value = $this->normalizeValue( $value );

		// Sanitize start date.
		if ( ! empty( $value['start'] ) ) {
			$value['start'] = sanitize_text_field( $value['start'] );
			if ( ! $this->isValidDateFormat( $value['start'] ) ) {
				$value['start'] = '';
			}
		}

		// Sanitize end date.
		if ( ! empty( $value['end'] ) ) {
			$value['end'] = sanitize_text_field( $value['end'] );
			if ( ! $this->isValidDateFormat( $value['end'] ) ) {
				$value['end'] = '';
			}
		}

		return $value;
	}

	/**
	 * Validate the date range value.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed                $value The value to validate.
	 * @param array<string, mixed> $field Field configuration.
	 * @return bool|WP_Error True if valid, WP_Error on failure.
	 */
	public function validate( mixed $value, array $field ): bool|WP_Error {
		$value  = $this->normalizeValue( $value );
		$errors = new WP_Error();

		// Check required field.
		if ( $this->isRequired( $field ) ) {
			if ( empty( $value['start'] ) || empty( $value['end'] ) ) {
				$errors->add(
					'required',
					sprintf(
						/* translators: %s: field label */
						__( '%s requires both start and end dates.', 'all-purpose-directory' ),
						$this->getLabel( $field )
					)
				);
				return $errors;
			}
		}

		// Skip further validation if both are empty.
		if ( empty( $value['start'] ) && empty( $value['end'] ) ) {
			return true;
		}

		// Validate individual date formats.
		if ( ! empty( $value['start'] ) && ! $this->isValidDateFormat( $value['start'] ) ) {
			$errors->add(
				'invalid_start_date',
				sprintf(
					/* translators: %s: field label */
					__( '%s start date must be in YYYY-MM-DD format.', 'all-purpose-directory' ),
					$this->getLabel( $field )
				)
			);
		}

		if ( ! empty( $value['end'] ) && ! $this->isValidDateFormat( $value['end'] ) ) {
			$errors->add(
				'invalid_end_date',
				sprintf(
					/* translators: %s: field label */
					__( '%s end date must be in YYYY-MM-DD format.', 'all-purpose-directory' ),
					$this->getLabel( $field )
				)
			);
		}

		// If there are format errors, return now.
		if ( $errors->has_errors() ) {
			return $errors;
		}

		// Validate end date is not before start date.
		if ( ! empty( $value['start'] ) && ! empty( $value['end'] ) ) {
			if ( strtotime( $value['end'] ) < strtotime( $value['start'] ) ) {
				$errors->add(
					'end_before_start',
					sprintf(
						/* translators: %s: field label */
						__( '%s end date cannot be before the start date.', 'all-purpose-directory' ),
						$this->getLabel( $field )
					)
				);
			}
		}

		// Validate min date.
		if ( ! empty( $field['min'] ) && $this->isValidDateFormat( $field['min'] ) ) {
			$min_timestamp = strtotime( $field['min'] );

			if ( ! empty( $value['start'] ) && strtotime( $value['start'] ) < $min_timestamp ) {
				$errors->add(
					'start_too_early',
					sprintf(
						/* translators: 1: field label, 2: minimum date */
						__( '%1$s start date must be on or after %2$s.', 'all-purpose-directory' ),
						$this->getLabel( $field ),
						$field['min']
					)
				);
			}

			if ( ! empty( $value['end'] ) && strtotime( $value['end'] ) < $min_timestamp ) {
				$errors->add(
					'end_too_early',
					sprintf(
						/* translators: 1: field label, 2: minimum date */
						__( '%1$s end date must be on or after %2$s.', 'all-purpose-directory' ),
						$this->getLabel( $field ),
						$field['min']
					)
				);
			}
		}

		// Validate max date.
		if ( ! empty( $field['max'] ) && $this->isValidDateFormat( $field['max'] ) ) {
			$max_timestamp = strtotime( $field['max'] );

			if ( ! empty( $value['start'] ) && strtotime( $value['start'] ) > $max_timestamp ) {
				$errors->add(
					'start_too_late',
					sprintf(
						/* translators: 1: field label, 2: maximum date */
						__( '%1$s start date must be on or before %2$s.', 'all-purpose-directory' ),
						$this->getLabel( $field ),
						$field['max']
					)
				);
			}

			if ( ! empty( $value['end'] ) && strtotime( $value['end'] ) > $max_timestamp ) {
				$errors->add(
					'end_too_late',
					sprintf(
						/* translators: 1: field label, 2: maximum date */
						__( '%1$s end date must be on or before %2$s.', 'all-purpose-directory' ),
						$this->getLabel( $field ),
						$field['max']
					)
				);
			}
		}

		return $errors->has_errors() ? $errors : true;
	}

	/**
	 * Get the default value for this field type.
	 *
	 * @since 1.0.0
	 *
	 * @return array{start: string, end: string} The default value.
	 */
	public function getDefaultValue(): mixed {
		return [
			'start' => '',
			'end'   => '',
		];
	}

	/**
	 * Format the date range value for display.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed                $value The stored value.
	 * @param array<string, mixed> $field Field configuration.
	 * @return string The formatted date range.
	 */
	public function formatValue( mixed $value, array $field ): string {
		$value = $this->normalizeValue( $value );

		if ( empty( $value['start'] ) && empty( $value['end'] ) ) {
			return '';
		}

		$format    = $field['date_format'] ?? get_option( 'date_format', 'Y-m-d' );
		$separator = $field['separator'] ?? ' - ';

		$formatted_start = '';
		$formatted_end   = '';

		if ( ! empty( $value['start'] ) && $this->isValidDateFormat( $value['start'] ) ) {
			$start_timestamp = strtotime( $value['start'] );
			if ( $start_timestamp !== false ) {
				$formatted_start = date_i18n( $format, $start_timestamp );
			}
		}

		if ( ! empty( $value['end'] ) && $this->isValidDateFormat( $value['end'] ) ) {
			$end_timestamp = strtotime( $value['end'] );
			if ( $end_timestamp !== false ) {
				$formatted_end = date_i18n( $format, $end_timestamp );
			}
		}

		if ( ! empty( $formatted_start ) && ! empty( $formatted_end ) ) {
			return esc_html( $formatted_start . $separator . $formatted_end );
		}

		if ( ! empty( $formatted_start ) ) {
			return esc_html( $formatted_start );
		}

		if ( ! empty( $formatted_end ) ) {
			return esc_html( $formatted_end );
		}

		return '';
	}

	/**
	 * Prepare the value for storage (JSON encode).
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The sanitized value.
	 * @return string The JSON-encoded value for storage.
	 */
	public function prepareValueForStorage( mixed $value ): mixed {
		$value = $this->normalizeValue( $value );

		// Don't store empty values.
		if ( empty( $value['start'] ) && empty( $value['end'] ) ) {
			return '';
		}

		$json = wp_json_encode( $value );
		return $json !== false ? $json : '';
	}

	/**
	 * Prepare the value from storage (JSON decode).
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The stored value.
	 * @return array{start: string, end: string} The usable value.
	 */
	public function prepareValueFromStorage( mixed $value ): mixed {
		if ( empty( $value ) ) {
			return $this->getDefaultValue();
		}

		// If it's already an array, normalize it.
		if ( is_array( $value ) ) {
			return $this->normalizeValue( $value );
		}

		// Try to decode JSON.
		if ( is_string( $value ) ) {
			$decoded = json_decode( $value, true );
			if ( is_array( $decoded ) ) {
				return $this->normalizeValue( $decoded );
			}
		}

		return $this->getDefaultValue();
	}

	/**
	 * Check if the value is empty.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The value to check.
	 * @return bool True if empty.
	 */
	protected function isEmpty( mixed $value ): bool {
		$value = $this->normalizeValue( $value );
		return empty( $value['start'] ) && empty( $value['end'] );
	}

	/**
	 * Normalize the value to ensure it has 'start' and 'end' keys.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The value to normalize.
	 * @return array{start: string, end: string} The normalized value.
	 */
	protected function normalizeValue( mixed $value ): array {
		$default = $this->getDefaultValue();

		if ( ! is_array( $value ) ) {
			return $default;
		}

		return [
			'start' => isset( $value['start'] ) && is_string( $value['start'] ) ? $value['start'] : '',
			'end'   => isset( $value['end'] ) && is_string( $value['end'] ) ? $value['end'] : '',
		];
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
