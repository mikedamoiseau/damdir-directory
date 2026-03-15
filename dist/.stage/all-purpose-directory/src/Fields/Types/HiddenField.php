<?php
/**
 * Hidden Field Type.
 *
 * Hidden input field for storing values without user visibility.
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
 * Class HiddenField
 *
 * Handles rendering and processing of hidden input fields.
 * Hidden fields do not display labels or descriptions.
 *
 * @since 1.0.0
 */
class HiddenField extends AbstractFieldType {

	/**
	 * Features supported by this field type.
	 *
	 * @var array<string, bool>
	 */
	protected array $supports = [
		'searchable' => false,
		'filterable' => false,
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
		return 'hidden';
	}

	/**
	 * Render the hidden input field HTML.
	 *
	 * Hidden fields only render the input element without
	 * labels, descriptions, or wrapper elements.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $field Field configuration.
	 * @param mixed                $value Current value.
	 * @return string The rendered HTML.
	 */
	public function render( array $field, mixed $value ): string {
		$attributes = [
			'type'  => 'hidden',
			'id'    => $this->getFieldId( $field ),
			'name'  => $this->getFieldName( $field ),
			'value' => (string) $value,
		];

		// Merge any additional attributes.
		if ( ! empty( $field['attributes'] ) && is_array( $field['attributes'] ) ) {
			$attributes = array_merge( $attributes, $field['attributes'] );
		}

		return sprintf(
			'<input %s>',
			$this->buildAttributes( $attributes )
		);
	}

	/**
	 * Validate the hidden field value.
	 *
	 * Hidden fields always pass validation since they cannot
	 * be required from a user input perspective.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed                $value The value to validate.
	 * @param array<string, mixed> $field Field configuration.
	 * @return bool|WP_Error Always returns true.
	 */
	public function validate( mixed $value, array $field ): bool|WP_Error {
		// Hidden fields always pass validation.
		// They cannot be required since users cannot interact with them.
		return true;
	}
}
