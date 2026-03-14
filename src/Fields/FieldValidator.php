<?php
/**
 * Field Validator.
 *
 * Central class for validating listing field values. Coordinates validation
 * across multiple fields, delegates to field type validators, and aggregates
 * errors into a single WP_Error object.
 *
 * @package APD\Fields
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Fields;

use APD\Contracts\FieldTypeInterface;
use WP_Error;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FieldValidator
 *
 * Handles validation of listing field values.
 *
 * @since 1.0.0
 */
class FieldValidator {

	/**
	 * The field registry instance.
	 *
	 * @var FieldRegistry
	 */
	private FieldRegistry $registry;

	/**
	 * Validation context.
	 *
	 * @var string
	 */
	private string $context = 'form';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param FieldRegistry|null $registry Optional. Field registry instance.
	 */
	public function __construct( ?FieldRegistry $registry = null ) {
		$this->registry = $registry ?? FieldRegistry::get_instance();
	}

	/**
	 * Set the validation context.
	 *
	 * Context can affect which validation rules apply (e.g., 'admin', 'frontend', 'api').
	 *
	 * @since 1.0.0
	 *
	 * @param string $context Validation context.
	 * @return self
	 */
	public function set_context( string $context ): self {
		$this->context = $context;
		return $this;
	}

	/**
	 * Get the validation context.
	 *
	 * @since 1.0.0
	 *
	 * @return string Current context.
	 */
	public function get_context(): string {
		return $this->context;
	}

	/**
	 * Validate a single field value.
	 *
	 * @since 1.0.0
	 *
	 * @param string $field_name The field name.
	 * @param mixed  $value      The value to validate.
	 * @param bool   $sanitize   Optional. Whether to sanitize before validation. Default true.
	 * @return bool|WP_Error True if valid, WP_Error on failure.
	 */
	public function validate_field( string $field_name, mixed $value, bool $sanitize = true ): bool|WP_Error {
		$field = $this->registry->get_field( $field_name );

		if ( $field === null ) {
			return new WP_Error(
				'unknown_field',
				sprintf(
					/* translators: %s: field name */
					__( 'Unknown field: %s.', 'all-purpose-directory' ),
					$field_name
				)
			);
		}

		$field_type = $this->registry->get_field_type( $field['type'] );

		if ( $field_type === null ) {
			return new WP_Error(
				'unknown_field_type',
				sprintf(
					/* translators: %s: field type */
					__( 'Unknown field type: %s.', 'all-purpose-directory' ),
					$field['type']
				)
			);
		}

		// Required check must happen before sanitization to avoid empty numeric
		// values being converted to 0/0.0 and treated as non-empty.
		if ( ! empty( $field['required'] ) && $this->is_empty_value( $value ) ) {
			return new WP_Error(
				'required',
				sprintf(
					/* translators: %s: field label */
					__( '%s is required.', 'all-purpose-directory' ),
					$field['label'] ?? $field_name
				)
			);
		}

		// Sanitize the value first if requested.
		if ( $sanitize ) {
			$value = $this->sanitize_value_with_field( $field_type, $field, $value );
		}

		/**
		 * Filter the value before validation.
		 *
		 * @since 1.0.0
		 *
		 * @param mixed                $value      The value to validate.
		 * @param array<string, mixed> $field      Field configuration.
		 * @param string               $context    Validation context.
		 * @param FieldTypeInterface   $field_type Field type handler.
		 */
		$value = apply_filters( 'apd_before_validate_field', $value, $field, $this->context, $field_type );

		// Run field type validation.
		$result = $field_type->validate( $value, $field );

		/**
		 * Filter the validation result.
		 *
		 * Allows adding custom validation logic or modifying errors.
		 *
		 * @since 1.0.0
		 *
		 * @param bool|WP_Error        $result     Validation result.
		 * @param mixed                $value      The validated value.
		 * @param array<string, mixed> $field      Field configuration.
		 * @param string               $context    Validation context.
		 * @param FieldTypeInterface   $field_type Field type handler.
		 */
		return apply_filters( 'apd_validate_field', $result, $value, $field, $this->context, $field_type );
	}

	/**
	 * Validate multiple field values.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $values        Field values keyed by field name.
	 * @param array<string, mixed> $args          Optional. Arguments.
	 *                                            - 'fields': (array) Specific field names to validate.
	 *                                            - 'exclude': (array) Field names to exclude.
	 *                                            - 'sanitize': (bool) Whether to sanitize. Default true.
	 *                                            - 'skip_unregistered': (bool) Skip unknown fields. Default true.
	 * @return bool|WP_Error True if all valid, WP_Error with all errors on failure.
	 */
	public function validate_fields( array $values, array $args = [] ): bool|WP_Error {
		$defaults = [
			'fields'            => [],
			'exclude'           => [], // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- Validation config key; unrelated to WP_Query exclusion params.
			'sanitize'          => true,
			'skip_unregistered' => true,
		];

		$args   = wp_parse_args( $args, $defaults );
		$errors = new WP_Error();

		// Determine which fields to validate.
		if ( ! empty( $args['fields'] ) ) {
			$field_names = (array) $args['fields'];
		} else {
			// Validate all registered fields.
			$field_names = array_keys( $this->registry->get_fields() );
		}

		// Apply exclusions.
		if ( ! empty( $args['exclude'] ) ) {
			$field_names = array_diff( $field_names, (array) $args['exclude'] );
		}

		// Also validate any fields in $values that aren't in our field list yet.
		// This catches values submitted for unknown fields.
		if ( ! $args['skip_unregistered'] ) {
			$field_names = array_unique( array_merge( $field_names, array_keys( $values ) ) );
		}

		// Validate each field.
		foreach ( $field_names as $field_name ) {
			$value = $values[ $field_name ] ?? null;

			// Check if field exists.
			if ( ! $this->registry->has_field( $field_name ) ) {
				if ( ! $args['skip_unregistered'] ) {
					$errors->add(
						$field_name,
						sprintf(
							/* translators: %s: field name */
							__( 'Unknown field: %s.', 'all-purpose-directory' ),
							$field_name
						)
					);
				}
				continue;
			}

			$result = $this->validate_field( $field_name, $value, $args['sanitize'] );

			if ( is_wp_error( $result ) ) {
				// Add all errors from this field, keyed by field name.
				foreach ( $result->get_error_codes() as $code ) {
					foreach ( $result->get_error_messages( $code ) as $message ) {
						$errors->add( $field_name, $message );
					}
				}
			}
		}

		/**
		 * Fires after all fields have been validated.
		 *
		 * Allows adding cross-field validation or modifying the error object.
		 *
		 * @since 1.0.0
		 *
		 * @param WP_Error             $errors  The errors object (may be empty).
		 * @param array<string, mixed> $values  The validated values.
		 * @param array<string, mixed> $args    Validation arguments.
		 * @param string               $context Validation context.
		 */
		do_action( 'apd_after_validate_fields', $errors, $values, $args, $this->context );

		return $errors->has_errors() ? $errors : true;
	}

	/**
	 * Sanitize a single field value.
	 *
	 * @since 1.0.0
	 *
	 * @param string $field_name The field name.
	 * @param mixed  $value      The value to sanitize.
	 * @return mixed The sanitized value, or original value if field/type not found.
	 */
	public function sanitize_field( string $field_name, mixed $value ): mixed {
		$field = $this->registry->get_field( $field_name );

		if ( $field === null ) {
			return $value;
		}

		$field_type = $this->registry->get_field_type( $field['type'] );

		if ( $field_type === null ) {
			return $value;
		}

		// Preserve explicit empty values for required fields so they are not
		// coerced into numeric zeros by field sanitizers.
		if ( ! empty( $field['required'] ) && $this->is_empty_value( $value ) ) {
			return $this->get_empty_sentinel_value( $value );
		}

		return $this->sanitize_value_with_field( $field_type, $field, $value );
	}

	/**
	 * Sanitize multiple field values.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $values Field values keyed by field name.
	 * @param array<string, mixed> $args   Optional. Arguments.
	 *                                     - 'fields': (array) Specific field names to sanitize.
	 *                                     - 'exclude': (array) Field names to exclude.
	 *                                     - 'skip_unregistered': (bool) Skip unknown fields. Default true.
	 * @return array<string, mixed> Sanitized values.
	 */
	public function sanitize_fields( array $values, array $args = [] ): array {
		$defaults = [
			'fields'            => [],
			'exclude'           => [], // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- Sanitization config key; unrelated to WP_Query exclusion params.
			'skip_unregistered' => true,
		];

		$args      = wp_parse_args( $args, $defaults );
		$sanitized = [];

		// Determine which fields to process.
		$field_names = array_keys( $values );

		if ( ! empty( $args['fields'] ) ) {
			$field_names = array_intersect( $field_names, (array) $args['fields'] );
		}

		if ( ! empty( $args['exclude'] ) ) {
			$field_names = array_diff( $field_names, (array) $args['exclude'] );
		}

		foreach ( $field_names as $field_name ) {
			$value = $values[ $field_name ];

			// Check if field exists.
			if ( ! $this->registry->has_field( $field_name ) ) {
				if ( ! $args['skip_unregistered'] ) {
					$sanitized[ $field_name ] = $value;
				}
				continue;
			}

			$sanitized[ $field_name ] = $this->sanitize_field( $field_name, $value );
		}

		/**
		 * Filter the sanitized field values.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, mixed> $sanitized Sanitized values.
		 * @param array<string, mixed> $values    Original values.
		 * @param array<string, mixed> $args      Sanitization arguments.
		 * @param string               $context   Validation context.
		 */
		return apply_filters( 'apd_sanitized_fields', $sanitized, $values, $args, $this->context );
	}

	/**
	 * Validate and sanitize field values.
	 *
	 * Convenience method that sanitizes and then validates all fields.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $values Field values keyed by field name.
	 * @param array<string, mixed> $args   Optional. Arguments (same as validate_fields).
	 * @return array{valid: bool, values: array<string, mixed>, errors: WP_Error|null}
	 */
	public function process_fields( array $values, array $args = [] ): array {
		// First sanitize all values.
		$sanitized = $this->sanitize_fields( $values, $args );

		// Then validate (skip sanitization since already done).
		$args['sanitize'] = false;
		$result           = $this->validate_fields( $sanitized, $args );

		return [
			'valid'  => $result === true,
			'values' => $sanitized,
			'errors' => is_wp_error( $result ) ? $result : null,
		];
	}

	/**
	 * Get validation errors as an array.
	 *
	 * Converts WP_Error to array format suitable for JSON responses.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Error $errors The errors object.
	 * @return array<string, string[]> Errors keyed by field name.
	 */
	public function errors_to_array( WP_Error $errors ): array {
		$result = [];

		foreach ( $errors->get_error_codes() as $code ) {
			$messages = $errors->get_error_messages( $code );
			if ( ! isset( $result[ $code ] ) ) {
				$result[ $code ] = [];
			}
			$result[ $code ] = array_merge( $result[ $code ], $messages );
		}

		return $result;
	}

	/**
	 * Check if a specific field has errors.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Error $errors     The errors object.
	 * @param string   $field_name The field name to check.
	 * @return bool True if field has errors.
	 */
	public function field_has_error( WP_Error $errors, string $field_name ): bool {
		return in_array( $field_name, $errors->get_error_codes(), true );
	}

	/**
	 * Get errors for a specific field.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Error $errors     The errors object.
	 * @param string   $field_name The field name.
	 * @return string[] Array of error messages for the field.
	 */
	public function get_field_errors( WP_Error $errors, string $field_name ): array {
		return $errors->get_error_messages( $field_name );
	}

	/**
	 * Validate required fields have values.
	 *
	 * Quick check that all required fields are present in the values array.
	 * Does not run full validation rules.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $values Field values keyed by field name.
	 * @return bool|WP_Error True if all required present, WP_Error with missing fields.
	 */
	public function validate_required( array $values ): bool|WP_Error {
		$errors         = new WP_Error();
		$all_fields     = $this->registry->get_fields();
		$required_count = 0;

		foreach ( $all_fields as $field_name => $field ) {
			if ( empty( $field['required'] ) ) {
				continue;
			}

			++$required_count;

			$value    = $values[ $field_name ] ?? null;
			$is_empty = $this->is_empty_value( $value );

			if ( $is_empty ) {
				$errors->add(
					$field_name,
					sprintf(
						/* translators: %s: field label */
						__( '%s is required.', 'all-purpose-directory' ),
						$field['label'] ?? $field_name
					)
				);
			}
		}

		return $errors->has_errors() ? $errors : true;
	}

	/**
	 * Check if a value is considered empty.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Value to check.
	 * @return bool True if empty.
	 */
	private function is_empty_value( mixed $value ): bool {
		if ( is_array( $value ) ) {
			return empty( $value );
		}

		if ( is_string( $value ) ) {
			return trim( $value ) === '';
		}

		return $value === null;
	}

	/**
	 * Sanitize a value with field-aware sanitizer when available.
	 *
	 * @since 1.0.0
	 *
	 * @param FieldTypeInterface   $field_type Field type handler.
	 * @param array<string, mixed> $field      Field configuration.
	 * @param mixed                $value      Raw value.
	 * @return mixed Sanitized value.
	 */
	private function sanitize_value_with_field( FieldTypeInterface $field_type, array $field, mixed $value ): mixed {
		if ( is_callable( [ $field_type, 'sanitizeWithField' ] ) ) {
			return $field_type->sanitizeWithField( $value, $field );
		}

		return $field_type->sanitize( $value );
	}

	/**
	 * Normalize empty inputs to a stable empty sentinel for storage.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Raw empty value.
	 * @return mixed Normalized empty value.
	 */
	private function get_empty_sentinel_value( mixed $value ): mixed {
		if ( is_array( $value ) ) {
			return [];
		}

		if ( is_string( $value ) ) {
			return '';
		}

		return null;
	}
}
