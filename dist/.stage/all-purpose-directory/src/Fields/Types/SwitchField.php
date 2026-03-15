<?php
/**
 * Switch Field Type.
 *
 * Toggle switch (styled checkbox) for on/off values.
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
 * Class SwitchField
 *
 * Handles rendering and processing of toggle switch inputs.
 *
 * @since 1.0.0
 */
class SwitchField extends AbstractFieldType {

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
		return 'switch';
	}

	/**
	 * Render the switch field HTML.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $field Field configuration.
	 * @param mixed                $value Current value.
	 * @return string The rendered HTML.
	 */
	public function render( array $field, mixed $value ): string {
		$field_id   = $this->getFieldId( $field );
		$field_name = $this->getFieldName( $field );
		$checked    = $this->toBoolean( $value ) ? ' checked="checked"' : '';

		$on_label  = $field['on_label'] ?? __( 'On', 'all-purpose-directory' );
		$off_label = $field['off_label'] ?? __( 'Off', 'all-purpose-directory' );

		// Build switch attributes.
		$attributes = [
			'type'  => 'checkbox',
			'id'    => $field_id,
			'name'  => $field_name,
			'value' => '1',
			'class' => 'apd-switch-input',
			'role'  => 'switch',
		];

		if ( $this->isRequired( $field ) ) {
			$attributes['required']      = true;
			$attributes['aria-required'] = 'true';
		}

		$attributes['aria-checked'] = $this->toBoolean( $value ) ? 'true' : 'false';

		if ( ! empty( $field['description'] ) ) {
			$attributes['aria-describedby'] = $field_id . '-description';
		}

		// Add custom class if provided.
		if ( ! empty( $field['class'] ) ) {
			$attributes['class'] .= ' ' . $field['class'];
		}

		// Merge any additional attributes.
		if ( ! empty( $field['attributes'] ) && is_array( $field['attributes'] ) ) {
			$attributes = array_merge( $attributes, $field['attributes'] );
		}

		$html  = '<label class="apd-switch">';
		$html .= sprintf(
			'<input %s%s>',
			$this->buildAttributes( $attributes ),
			$checked
		);
		$html .= '<span class="apd-switch-slider" aria-hidden="true"></span>';
		$html .= sprintf(
			'<span class="apd-switch-label apd-switch-off" aria-hidden="true">%s</span>',
			esc_html( $off_label )
		);
		$html .= sprintf(
			'<span class="apd-switch-label apd-switch-on" aria-hidden="true">%s</span>',
			esc_html( $on_label )
		);
		$html .= '</label>';

		$html .= $this->renderDescription( $field );

		return $html;
	}

	/**
	 * Sanitize the field value.
	 *
	 * Casts to boolean.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The raw value.
	 * @return bool The sanitized boolean value.
	 */
	public function sanitize( mixed $value ): bool {
		return $this->toBoolean( $value );
	}

	/**
	 * Validate the field value.
	 *
	 * Switch validation always passes (boolean value is always valid).
	 * Required check handles whether on is mandatory.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed                $value The value to validate.
	 * @param array<string, mixed> $field Field configuration.
	 * @return bool|WP_Error True if valid, WP_Error on failure.
	 */
	public function validate( mixed $value, array $field ): bool|WP_Error {
		// Check required - for switch, required means it must be on.
		if ( $this->isRequired( $field ) && ! $this->toBoolean( $value ) ) {
			return new WP_Error(
				'required',
				sprintf(
					/* translators: %s: field label */
					__( '%s must be enabled.', 'all-purpose-directory' ),
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
	 * @return bool False as default (off).
	 */
	public function getDefaultValue(): bool {
		return false;
	}

	/**
	 * Format the value for display.
	 *
	 * Returns On/Off or custom labels from config.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed                $value The stored value.
	 * @param array<string, mixed> $field Field configuration.
	 * @return string The formatted value.
	 */
	public function formatValue( mixed $value, array $field ): string {
		$is_on = $this->toBoolean( $value );

		$on_label  = $field['on_label'] ?? __( 'On', 'all-purpose-directory' );
		$off_label = $field['off_label'] ?? __( 'Off', 'all-purpose-directory' );

		return esc_html( $is_on ? $on_label : $off_label );
	}

	/**
	 * Prepare the value for storage.
	 *
	 * Stores as '1' or '0' string.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The sanitized value.
	 * @return string '1' or '0'.
	 */
	public function prepareValueForStorage( mixed $value ): string {
		return $this->toBoolean( $value ) ? '1' : '0';
	}

	/**
	 * Prepare the value from storage.
	 *
	 * Casts stored value back to boolean.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The stored value.
	 * @return bool The boolean value.
	 */
	public function prepareValueFromStorage( mixed $value ): bool {
		return $this->toBoolean( $value );
	}

	/**
	 * Check if a value is empty.
	 *
	 * For switch, false is not considered empty (it's a valid state).
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The value to check.
	 * @return bool True if empty (null only).
	 */
	protected function isEmpty( mixed $value ): bool {
		return $value === null;
	}

	/**
	 * Convert a value to boolean.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The value to convert.
	 * @return bool The boolean value.
	 */
	private function toBoolean( mixed $value ): bool {
		if ( is_bool( $value ) ) {
			return $value;
		}

		if ( is_string( $value ) ) {
			return in_array( strtolower( $value ), [ '1', 'true', 'yes', 'on' ], true );
		}

		return (bool) $value;
	}
}
