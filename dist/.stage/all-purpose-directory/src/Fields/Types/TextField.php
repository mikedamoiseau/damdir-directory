<?php
/**
 * Text Field Type.
 *
 * Single line text input field for short text values.
 *
 * @package APD\Fields\Types
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Fields\Types;

use APD\Fields\AbstractFieldType;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TextField
 *
 * Handles rendering and processing of single-line text inputs.
 *
 * @since 1.0.0
 */
class TextField extends AbstractFieldType {

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
		return 'text';
	}

	/**
	 * Render the text input field HTML.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $field Field configuration.
	 * @param mixed                $value Current value.
	 * @return string The rendered HTML.
	 */
	public function render( array $field, mixed $value ): string {
		$attributes          = $this->getCommonAttributes( $field );
		$attributes['type']  = 'text';
		$attributes['value'] = (string) $value;

		// Add maxlength if defined in validation rules.
		if ( isset( $field['validation']['max_length'] ) ) {
			$attributes['maxlength'] = (int) $field['validation']['max_length'];
		}

		// Add minlength if defined in validation rules.
		if ( isset( $field['validation']['min_length'] ) ) {
			$attributes['minlength'] = (int) $field['validation']['min_length'];
		}

		// Add pattern if defined in validation rules (for HTML5 validation).
		if ( isset( $field['validation']['pattern'] ) ) {
			// Remove PHP regex delimiters for HTML5 pattern attribute.
			$pattern = $field['validation']['pattern'];
			if ( preg_match( '/^(.)(.*)\1[a-z]*$/i', $pattern, $matches ) ) {
				$attributes['pattern'] = $matches[2];
			}
		}

		$html = sprintf(
			'<input %s>',
			$this->buildAttributes( $attributes )
		);

		$html .= $this->renderDescription( $field );

		return $html;
	}
}
