<?php
/**
 * Field Type Interface.
 *
 * Defines the contract for all custom field types in the directory plugin.
 * Field types handle rendering, sanitization, and validation of listing data.
 *
 * @package APD\Contracts
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Contracts;

use WP_Error;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface FieldTypeInterface
 *
 * All field types must implement this interface to be used with the
 * FieldRegistry and FieldRenderer systems.
 *
 * Field configuration array structure:
 * - name: (string) Unique field identifier
 * - type: (string) Field type (text, select, etc.)
 * - label: (string) Display label
 * - description: (string) Help text
 * - required: (bool) Whether field is required
 * - default: (mixed) Default value
 * - placeholder: (string) Placeholder text
 * - options: (array) Options for select/radio/checkbox types
 * - validation: (array) Validation rules
 * - searchable: (bool) Include in search queries
 * - filterable: (bool) Show in filter UI
 * - admin_only: (bool) Hide from frontend
 * - priority: (int) Display order (lower = earlier)
 * - class: (string) Additional CSS classes
 * - attributes: (array) Additional HTML attributes
 *
 * @since 1.0.0
 */
interface FieldTypeInterface {

	/**
	 * Get the field type identifier.
	 *
	 * Returns the unique string identifier for this field type.
	 * Used for registration and field type matching.
	 *
	 * @since 1.0.0
	 *
	 * @return string The field type identifier (e.g., 'text', 'email', 'select').
	 */
	public function getType(): string;

	/**
	 * Render the field HTML.
	 *
	 * Generates the HTML markup for displaying the field in forms.
	 * Must return escaped, ready-to-output HTML.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $field Field configuration array.
	 * @param mixed                $value Current field value.
	 * @return string The rendered HTML markup.
	 */
	public function render( array $field, mixed $value ): string;

	/**
	 * Sanitize the field value.
	 *
	 * Cleans and sanitizes the input value before storage.
	 * Called before validation and before saving to the database.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The raw input value to sanitize.
	 * @return mixed The sanitized value.
	 */
	public function sanitize( mixed $value ): mixed;

	/**
	 * Validate the field value.
	 *
	 * Validates the sanitized value against field rules.
	 * Returns true if valid, or WP_Error with error message(s) if invalid.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed                $value The sanitized value to validate.
	 * @param array<string, mixed> $field Field configuration array.
	 * @return bool|WP_Error True if valid, WP_Error on failure.
	 */
	public function validate( mixed $value, array $field ): bool|WP_Error;

	/**
	 * Get the default value for this field type.
	 *
	 * Returns the appropriate default value when no value is set.
	 * Can be overridden by field configuration's 'default' key.
	 *
	 * @since 1.0.0
	 *
	 * @return mixed The default value.
	 */
	public function getDefaultValue(): mixed;

	/**
	 * Check if this field type supports a given feature.
	 *
	 * Features include: 'searchable', 'filterable', 'sortable', 'repeater'.
	 *
	 * @since 1.0.0
	 *
	 * @param string $feature The feature to check.
	 * @return bool True if the feature is supported.
	 */
	public function supports( string $feature ): bool;

	/**
	 * Format the value for display.
	 *
	 * Transforms the stored value for frontend output.
	 * Unlike render(), this returns just the formatted value, not form HTML.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed                $value The stored value.
	 * @param array<string, mixed> $field Field configuration array.
	 * @return string The formatted display value.
	 */
	public function formatValue( mixed $value, array $field ): string;

	/**
	 * Prepare the value for storage.
	 *
	 * Transforms the sanitized value for database storage.
	 * Used for serializing complex values or type casting.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The sanitized value.
	 * @return mixed The value ready for database storage.
	 */
	public function prepareValueForStorage( mixed $value ): mixed;

	/**
	 * Prepare the value for use after retrieval.
	 *
	 * Transforms the stored value back to its usable form.
	 * Reverses any transformations done in prepareValueForStorage().
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The value from the database.
	 * @return mixed The value ready for use.
	 */
	public function prepareValueFromStorage( mixed $value ): mixed;
}
