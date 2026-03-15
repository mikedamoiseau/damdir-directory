<?php
/**
 * Image Field Type.
 *
 * Image upload field that uses WordPress media library with preview.
 * Stores attachment ID and displays image thumbnail.
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
 * Class ImageField
 *
 * Handles rendering and processing of image upload fields.
 * Uses WordPress media library for image selection with preview.
 *
 * @since 1.0.0
 */
class ImageField extends AbstractFieldType {

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
	 * Default allowed image types.
	 *
	 * @var array<string>
	 */
	protected array $default_allowed_types = [ 'jpg', 'jpeg', 'png', 'gif', 'webp' ];

	/**
	 * Default preview size.
	 *
	 * @var string
	 */
	protected string $default_preview_size = 'thumbnail';

	/**
	 * Get the field type identifier.
	 *
	 * @since 1.0.0
	 *
	 * @return string The field type identifier.
	 */
	public function getType(): string {
		return 'image';
	}

	/**
	 * Get the default value for this field type.
	 *
	 * @since 1.0.0
	 *
	 * @return int The default value (0 for no attachment).
	 */
	public function getDefaultValue(): mixed {
		return 0;
	}

	/**
	 * Render the image upload field HTML.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $field Field configuration.
	 * @param mixed                $value Current value (attachment ID).
	 * @return string The rendered HTML.
	 */
	public function render( array $field, mixed $value ): string {
		$attachment_id = absint( $value );
		$allowed_types = $this->getAllowedTypes( $field );
		$preview_size  = $this->getPreviewSize( $field );

		// Get image info if attachment exists.
		$image_url = '';
		$image_alt = '';
		if ( $attachment_id > 0 ) {
			$image_src = wp_get_attachment_image_src( $attachment_id, $preview_size );
			if ( $image_src ) {
				$image_url = $image_src[0];
				$image_alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
			}
		}

		$field_id   = $this->getFieldId( $field );
		$field_name = $this->getFieldName( $field );

		// Build hidden input attributes.
		$input_attributes = [
			'type'               => 'hidden',
			'id'                 => $field_id,
			'name'               => $field_name,
			'value'              => $attachment_id > 0 ? $attachment_id : '',
			'data-field-type'    => 'image',
			'data-allowed-types' => esc_attr( implode( ',', $allowed_types ) ),
			'data-preview-size'  => esc_attr( $preview_size ),
		];

		if ( $this->isRequired( $field ) ) {
			$input_attributes['required']      = true;
			$input_attributes['aria-required'] = 'true';
		}

		if ( ! empty( $field['description'] ) ) {
			$input_attributes['aria-describedby'] = $field_id . '-description';
		}

		// Build wrapper class.
		$wrapper_class = 'apd-image-field';
		if ( ! empty( $field['class'] ) ) {
			$wrapper_class .= ' ' . $field['class'];
		}

		// Start output.
		$html = sprintf(
			'<div class="%s" data-field-name="%s">',
			esc_attr( $wrapper_class ),
			esc_attr( $field['name'] ?? '' )
		);

		// Hidden input for attachment ID.
		$html .= sprintf( '<input %s>', $this->buildAttributes( $input_attributes ) );

		// Image preview container.
		$display_style = $attachment_id > 0 && $image_url ? '' : 'display: none;';
		$html         .= sprintf(
			'<div class="apd-image-preview" style="%s">',
			esc_attr( $display_style )
		);
		$html         .= sprintf(
			'<img src="%s" alt="%s" class="apd-image-thumbnail">',
			esc_url( $image_url ),
			esc_attr( $image_alt )
		);
		$html         .= sprintf(
			'<button type="button" class="apd-image-remove button" aria-label="%s">%s</button>',
			esc_attr__( 'Remove image', 'all-purpose-directory' ),
			esc_html__( 'Remove', 'all-purpose-directory' )
		);
		$html         .= '</div>';

		// Upload button.
		$button_style = $attachment_id > 0 && $image_url ? 'display: none;' : '';
		$html        .= sprintf(
			'<button type="button" class="apd-image-upload button" style="%s" data-title="%s" data-button="%s">%s</button>',
			esc_attr( $button_style ),
			esc_attr__( 'Select Image', 'all-purpose-directory' ),
			esc_attr__( 'Use this image', 'all-purpose-directory' ),
			esc_html__( 'Select Image', 'all-purpose-directory' )
		);

		$html .= '</div>';

		$html .= $this->renderDescription( $field );

		return $html;
	}

	/**
	 * Sanitize the field value.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The raw value.
	 * @return int The sanitized attachment ID.
	 */
	public function sanitize( mixed $value ): mixed {
		return absint( $value );
	}

	/**
	 * Validate the field value.
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
			return $errors;
		}

		// If no value, skip further validation.
		if ( $this->isEmpty( $value ) ) {
			return true;
		}

		$attachment_id = absint( $value );

		// Check attachment exists.
		$attachment_url = wp_get_attachment_url( $attachment_id );
		if ( ! $attachment_url ) {
			$errors->add(
				'invalid_attachment',
				sprintf(
					/* translators: %s: field label */
					__( '%s contains an invalid image.', 'all-purpose-directory' ),
					$this->getLabel( $field )
				)
			);
			return $errors;
		}

		// Check that attachment is an image.
		if ( ! wp_attachment_is_image( $attachment_id ) ) {
			$errors->add(
				'not_an_image',
				sprintf(
					/* translators: %s: field label */
					__( '%s must be an image file.', 'all-purpose-directory' ),
					$this->getLabel( $field )
				)
			);
			return $errors;
		}

		// Check image type if allowed_types is specified.
		$allowed_types = $this->getAllowedTypes( $field );
		if ( ! empty( $allowed_types ) ) {
			$file_path = get_attached_file( $attachment_id );
			if ( $file_path ) {
				$extension = strtolower( pathinfo( $file_path, PATHINFO_EXTENSION ) );
				if ( ! in_array( $extension, $allowed_types, true ) ) {
					$errors->add(
						'invalid_image_type',
						sprintf(
							/* translators: 1: field label, 2: allowed image types */
							__( '%1$s must be one of the following types: %2$s.', 'all-purpose-directory' ),
							$this->getLabel( $field ),
							implode( ', ', $allowed_types )
						)
					);
				}
			}
		}

		// Apply custom validation rules.
		if ( isset( $field['validation'] ) ) {
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
	 * Format the value for display.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed                $value The stored value.
	 * @param array<string, mixed> $field Field configuration.
	 * @return string The formatted value (image HTML or URL).
	 */
	public function formatValue( mixed $value, array $field ): string {
		$attachment_id = absint( $value );

		if ( $attachment_id <= 0 ) {
			return '';
		}

		// Get the display size from field config or use default.
		$size = $field['display_size'] ?? $this->getPreviewSize( $field );

		$image_html = wp_get_attachment_image(
			$attachment_id,
			$size,
			false,
			[ 'class' => 'apd-image-display' ]
		);

		if ( $image_html ) {
			return $image_html;
		}

		// Fallback to URL if image HTML fails.
		$image_url = wp_get_attachment_url( $attachment_id );
		return $image_url ? esc_url( $image_url ) : '';
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
		return empty( $value ) || absint( $value ) <= 0;
	}

	/**
	 * Prepare the value from storage.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The stored value.
	 * @return int The attachment ID.
	 */
	public function prepareValueFromStorage( mixed $value ): mixed {
		return absint( $value );
	}

	/**
	 * Get allowed image types from field configuration.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $field Field configuration.
	 * @return array<string> Allowed image extensions.
	 */
	protected function getAllowedTypes( array $field ): array {
		if ( isset( $field['allowed_types'] ) && is_array( $field['allowed_types'] ) ) {
			return array_map( 'strtolower', $field['allowed_types'] );
		}
		return $this->default_allowed_types;
	}

	/**
	 * Get preview size from field configuration.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $field Field configuration.
	 * @return string WordPress image size.
	 */
	protected function getPreviewSize( array $field ): string {
		return $field['preview_size'] ?? $this->default_preview_size;
	}
}
