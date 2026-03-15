<?php
/**
 * Abstract Field Type base class.
 *
 * Provides default implementations for common field type functionality.
 * Concrete field types should extend this class rather than implementing
 * FieldTypeInterface directly.
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
 * Class AbstractFieldType
 *
 * Base class for all field types providing common functionality.
 *
 * @since 1.0.0
 */
abstract class AbstractFieldType implements FieldTypeInterface {

	/**
	 * Features supported by this field type.
	 *
	 * Override in child classes to declare support for features:
	 * - 'searchable': Can be included in search queries
	 * - 'filterable': Can be used as a filter option
	 * - 'sortable': Can be used for sorting results
	 * - 'repeater': Supports multiple values
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
	 * Must be implemented by child classes.
	 *
	 * @since 1.0.0
	 *
	 * @return string The field type identifier.
	 */
	abstract public function getType(): string;

	/**
	 * Render the field HTML.
	 *
	 * Must be implemented by child classes.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $field Field configuration.
	 * @param mixed                $value Current value.
	 * @return string The rendered HTML.
	 */
	abstract public function render( array $field, mixed $value ): string;

	/**
	 * Sanitize the field value.
	 *
	 * Default implementation uses sanitize_text_field().
	 * Override in child classes for type-specific sanitization.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The raw value.
	 * @return mixed The sanitized value.
	 */
	public function sanitize( mixed $value ): mixed {
		if ( is_array( $value ) ) {
			return array_map( 'sanitize_text_field', $value );
		}

		if ( is_string( $value ) ) {
			return sanitize_text_field( $value );
		}

		return $value;
	}

	/**
	 * Validate the field value.
	 *
	 * Default implementation checks required fields.
	 * Override or extend in child classes for type-specific validation.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed                $value The value to validate.
	 * @param array<string, mixed> $field Field configuration.
	 * @return bool|WP_Error True if valid, WP_Error on failure.
	 */
	public function validate( mixed $value, array $field ): bool|WP_Error {
		$errors = new WP_Error();

		// Check required field.
		if ( $this->isRequired( $field ) && $this->isEmpty( $value ) ) {
			$errors->add(
				'required',
				sprintf(
					/* translators: %s: field label */
					__( '%s is required.', 'all-purpose-directory' ),
					$this->getLabel( $field )
				)
			);
		}

		// Apply custom validation rules if defined.
		if ( ! $this->isEmpty( $value ) && isset( $field['validation'] ) ) {
			$validation_errors = $this->applyValidationRules( $value, $field );
			if ( is_wp_error( $validation_errors ) ) {
				foreach ( $validation_errors->get_error_codes() as $code ) {
					$errors->add( $code, $validation_errors->get_error_message( $code ) );
				}
			}
		}

		return $errors->has_errors() ? $errors : true;
	}

	/**
	 * Get the default value for this field type.
	 *
	 * Default implementation returns empty string.
	 *
	 * @since 1.0.0
	 *
	 * @return mixed The default value.
	 */
	public function getDefaultValue(): mixed {
		return '';
	}

	/**
	 * Check if this field type supports a feature.
	 *
	 * @since 1.0.0
	 *
	 * @param string $feature The feature to check.
	 * @return bool True if supported.
	 */
	public function supports( string $feature ): bool {
		return $this->supports[ $feature ] ?? false;
	}

	/**
	 * Format the value for display.
	 *
	 * Default implementation escapes and returns the value as string.
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

		return esc_html( (string) $value );
	}

	/**
	 * Prepare the value for storage.
	 *
	 * Default implementation returns value unchanged.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The sanitized value.
	 * @return mixed The value for storage.
	 */
	public function prepareValueForStorage( mixed $value ): mixed {
		return $value;
	}

	/**
	 * Prepare the value from storage.
	 *
	 * Default implementation returns value unchanged.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The stored value.
	 * @return mixed The usable value.
	 */
	public function prepareValueFromStorage( mixed $value ): mixed {
		return $value;
	}

	/**
	 * Check if a field is required.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $field Field configuration.
	 * @return bool True if required.
	 */
	protected function isRequired( array $field ): bool {
		return ! empty( $field['required'] );
	}

	/**
	 * Check if a value is empty.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The value to check.
	 * @return bool True if empty.
	 */
	protected function isEmpty( mixed $value ): bool {
		if ( is_array( $value ) ) {
			return empty( $value );
		}

		if ( is_string( $value ) ) {
			return trim( $value ) === '';
		}

		return $value === null;
	}

	/**
	 * Get the field label.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $field Field configuration.
	 * @return string The field label.
	 */
	protected function getLabel( array $field ): string {
		return $field['label'] ?? $field['name'] ?? __( 'This field', 'all-purpose-directory' );
	}

	/**
	 * Get the field name attribute.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $field Field configuration.
	 * @return string The field name.
	 */
	protected function getFieldName( array $field ): string {
		return 'apd_field_' . ( $field['name'] ?? '' );
	}

	/**
	 * Get the field ID attribute.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $field Field configuration.
	 * @return string The field ID.
	 */
	protected function getFieldId( array $field ): string {
		return 'apd-field-' . ( $field['name'] ?? '' );
	}

	/**
	 * Build HTML attributes string.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $attributes Key-value pairs of attributes.
	 * @return string The attributes string.
	 */
	protected function buildAttributes( array $attributes ): string {
		$parts = [];

		foreach ( $attributes as $key => $value ) {
			if ( $value === true ) {
				$parts[] = esc_attr( $key );
			} elseif ( $value !== false && $value !== null ) {
				$parts[] = sprintf( '%s="%s"', esc_attr( $key ), esc_attr( (string) $value ) );
			}
		}

		return implode( ' ', $parts );
	}

	/**
	 * Get common field attributes.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $field Field configuration.
	 * @return array<string, mixed> Common attributes.
	 */
	protected function getCommonAttributes( array $field ): array {
		$attributes = [
			'id'   => $this->getFieldId( $field ),
			'name' => $this->getFieldName( $field ),
		];

		if ( $this->isRequired( $field ) ) {
			$attributes['required']      = true;
			$attributes['aria-required'] = 'true';
		}

		if ( ! empty( $field['placeholder'] ) ) {
			$attributes['placeholder'] = $field['placeholder'];
		}

		if ( ! empty( $field['description'] ) ) {
			$attributes['aria-describedby'] = $this->getFieldId( $field ) . '-description';
		}

		if ( ! empty( $field['class'] ) ) {
			$attributes['class'] = $field['class'];
		}

		// Merge any additional attributes.
		if ( ! empty( $field['attributes'] ) && is_array( $field['attributes'] ) ) {
			$attributes = array_merge( $attributes, $field['attributes'] );
		}

		return $attributes;
	}

	/**
	 * Render the field description.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $field Field configuration.
	 * @return string The description HTML.
	 */
	protected function renderDescription( array $field ): string {
		if ( empty( $field['description'] ) ) {
			return '';
		}

		return sprintf(
			'<p class="apd-field-description" id="%s">%s</p>',
			esc_attr( $this->getFieldId( $field ) . '-description' ),
			esc_html( $field['description'] )
		);
	}

	/**
	 * Apply custom validation rules.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed                $value The value to validate.
	 * @param array<string, mixed> $field Field configuration.
	 * @return bool|WP_Error True if valid, WP_Error on failure.
	 */
	protected function applyValidationRules( mixed $value, array $field ): bool|WP_Error {
		$errors     = new WP_Error();
		$validation = $field['validation'] ?? [];

		// Min length validation.
		if ( isset( $validation['min_length'] ) && is_string( $value ) ) {
			if ( \apd_strlen( $value ) < (int) $validation['min_length'] ) {
				$errors->add(
					'min_length',
					sprintf(
						/* translators: 1: field label, 2: minimum length */
						__( '%1$s must be at least %2$d characters.', 'all-purpose-directory' ),
						$this->getLabel( $field ),
						$validation['min_length']
					)
				);
			}
		}

		// Max length validation.
		if ( isset( $validation['max_length'] ) && is_string( $value ) ) {
			if ( \apd_strlen( $value ) > (int) $validation['max_length'] ) {
				$errors->add(
					'max_length',
					sprintf(
						/* translators: 1: field label, 2: maximum length */
						__( '%1$s must not exceed %2$d characters.', 'all-purpose-directory' ),
						$this->getLabel( $field ),
						$validation['max_length']
					)
				);
			}
		}

		// Pattern validation (regex).
		if ( isset( $validation['pattern'] ) && is_string( $value ) ) {
			if ( ! preg_match( $validation['pattern'], $value ) ) {
				$message = $validation['pattern_message']
					?? sprintf(
						/* translators: %s: field label */
						__( '%s format is invalid.', 'all-purpose-directory' ),
						$this->getLabel( $field )
					);
				$errors->add( 'pattern', $message );
			}
		}

		// Custom callback validation.
		if ( isset( $validation['callback'] ) && is_callable( $validation['callback'] ) ) {
			$callback_result = call_user_func( $validation['callback'], $value, $field );
			if ( is_wp_error( $callback_result ) ) {
				foreach ( $callback_result->get_error_codes() as $code ) {
					$errors->add( $code, $callback_result->get_error_message( $code ) );
				}
			} elseif ( $callback_result === false ) {
				$errors->add(
					'callback',
					sprintf(
						/* translators: %s: field label */
						__( '%s is invalid.', 'all-purpose-directory' ),
						$this->getLabel( $field )
					)
				);
			}
		}

		return $errors->has_errors() ? $errors : true;
	}
}
