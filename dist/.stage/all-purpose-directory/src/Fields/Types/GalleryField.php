<?php
/**
 * Gallery Field Type.
 *
 * Multiple image upload field with drag-drop sorting.
 * Stores array of attachment IDs and displays image grid.
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
 * Class GalleryField
 *
 * Handles rendering and processing of gallery (multiple image) fields.
 * Uses WordPress media library for image selection with multi-select support.
 *
 * @since 1.0.0
 */
class GalleryField extends AbstractFieldType {

	/**
	 * Features supported by this field type.
	 *
	 * @var array<string, bool>
	 */
	protected array $supports = [
		'searchable' => false,
		'filterable' => false,
		'sortable'   => false,
		'repeater'   => true,
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
		return 'gallery';
	}

	/**
	 * Get the default value for this field type.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int> The default value (empty array).
	 */
	public function getDefaultValue(): mixed {
		return [];
	}

	/**
	 * Render the gallery field HTML.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $field Field configuration.
	 * @param mixed                $value Current value (array of attachment IDs).
	 * @return string The rendered HTML.
	 */
	public function render( array $field, mixed $value ): string {
		$attachment_ids = $this->normalizeIds( $value );
		$allowed_types  = $this->getAllowedTypes( $field );
		$preview_size   = $this->getPreviewSize( $field );
		$max_images     = $this->getMaxImages( $field );

		$field_id   = $this->getFieldId( $field );
		$field_name = $this->getFieldName( $field );

		// Build data attributes for the wrapper.
		$data_attributes = [
			'data-field-type'    => 'gallery',
			'data-field-name'    => $field['name'] ?? '',
			'data-allowed-types' => implode( ',', $allowed_types ),
			'data-preview-size'  => $preview_size,
		];

		if ( $max_images > 0 ) {
			$data_attributes['data-max-images'] = $max_images;
		}

		// Build wrapper class.
		$wrapper_class = 'apd-gallery-field';
		if ( ! empty( $field['class'] ) ) {
			$wrapper_class .= ' ' . $field['class'];
		}

		// Start output.
		$html = sprintf(
			'<div class="%s" %s>',
			esc_attr( $wrapper_class ),
			$this->buildAttributes( $data_attributes )
		);

		// Hidden input for comma-separated attachment IDs.
		$input_attributes = [
			'type'  => 'hidden',
			'id'    => $field_id,
			'name'  => $field_name,
			'value' => ! empty( $attachment_ids ) ? implode( ',', $attachment_ids ) : '',
		];

		if ( $this->isRequired( $field ) ) {
			$input_attributes['required']      = true;
			$input_attributes['aria-required'] = 'true';
		}

		if ( ! empty( $field['description'] ) ) {
			$input_attributes['aria-describedby'] = $field_id . '-description';
		}

		$html .= sprintf( '<input %s>', $this->buildAttributes( $input_attributes ) );

		// Gallery preview container.
		$html .= '<div class="apd-gallery-preview" data-sortable="true">';

		// Render existing images.
		foreach ( $attachment_ids as $attachment_id ) {
			$html .= $this->renderGalleryItem( $attachment_id, $preview_size );
		}

		$html .= '</div>';

		// Add images button.
		$button_disabled = $max_images > 0 && count( $attachment_ids ) >= $max_images;
		$html           .= sprintf(
			'<button type="button" class="apd-gallery-add button" data-title="%s" data-button="%s"%s>%s</button>',
			esc_attr__( 'Add Images', 'all-purpose-directory' ),
			esc_attr__( 'Add to gallery', 'all-purpose-directory' ),
			$button_disabled ? ' disabled' : '',
			esc_html__( 'Add Images', 'all-purpose-directory' )
		);

		// Image count display.
		if ( $max_images > 0 ) {
			$html .= sprintf(
				'<span class="apd-gallery-count">%s</span>',
				sprintf(
					/* translators: 1: current count, 2: max images */
					esc_html__( '%1$d of %2$d images', 'all-purpose-directory' ),
					count( $attachment_ids ),
					$max_images
				)
			);
		}

		$html .= '</div>';

		$html .= $this->renderDescription( $field );

		return $html;
	}

	/**
	 * Render a single gallery item.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $attachment_id The attachment ID.
	 * @param string $preview_size  The WordPress image size.
	 * @return string The rendered HTML.
	 */
	protected function renderGalleryItem( int $attachment_id, string $preview_size ): string {
		$image_src = wp_get_attachment_image_src( $attachment_id, $preview_size );

		if ( ! $image_src ) {
			return '';
		}

		$image_url = $image_src[0];
		$image_alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );

		$html  = sprintf(
			'<div class="apd-gallery-item" data-id="%d" draggable="true">',
			$attachment_id
		);
		$html .= sprintf(
			'<img src="%s" alt="%s" class="apd-gallery-thumbnail">',
			esc_url( $image_url ),
			esc_attr( $image_alt )
		);
		$html .= sprintf(
			'<button type="button" class="apd-gallery-remove" aria-label="%s">&times;</button>',
			esc_attr__( 'Remove image', 'all-purpose-directory' )
		);
		$html .= '</div>';

		return $html;
	}

	/**
	 * Sanitize the field value.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The raw value.
	 * @return array<int> The sanitized array of attachment IDs.
	 */
	public function sanitize( mixed $value ): mixed {
		return $this->normalizeIds( $value );
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
		$errors         = new WP_Error();
		$attachment_ids = $this->normalizeIds( $value );

		// Check required field.
		if ( $this->isRequired( $field ) && empty( $attachment_ids ) ) {
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

		// If no images, skip further validation.
		if ( empty( $attachment_ids ) ) {
			return true;
		}

		// Check max images limit.
		$max_images = $this->getMaxImages( $field );
		if ( $max_images > 0 && count( $attachment_ids ) > $max_images ) {
			$errors->add(
				'max_images_exceeded',
				sprintf(
					/* translators: 1: field label, 2: max images */
					__( '%1$s cannot contain more than %2$d images.', 'all-purpose-directory' ),
					$this->getLabel( $field ),
					$max_images
				)
			);
		}

		// Validate each attachment.
		$allowed_types = $this->getAllowedTypes( $field );
		foreach ( $attachment_ids as $attachment_id ) {
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
				continue;
			}

			// Check that attachment is an image.
			if ( ! wp_attachment_is_image( $attachment_id ) ) {
				$errors->add(
					'not_an_image',
					sprintf(
						/* translators: %s: field label */
						__( '%s must contain only image files.', 'all-purpose-directory' ),
						$this->getLabel( $field )
					)
				);
				continue;
			}

			// Check image type if allowed_types is specified.
			if ( ! empty( $allowed_types ) ) {
				$file_path = get_attached_file( $attachment_id );
				if ( $file_path ) {
					$extension = strtolower( pathinfo( $file_path, PATHINFO_EXTENSION ) );
					if ( ! in_array( $extension, $allowed_types, true ) ) {
						$errors->add(
							'invalid_image_type',
							sprintf(
								/* translators: 1: field label, 2: allowed image types */
								__( '%1$s images must be one of the following types: %2$s.', 'all-purpose-directory' ),
								$this->getLabel( $field ),
								implode( ', ', $allowed_types )
							)
						);
						break; // Only report this error once.
					}
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
	 * @return string The formatted value (gallery HTML or shortcode).
	 */
	public function formatValue( mixed $value, array $field ): string {
		$attachment_ids = $this->normalizeIds( $value );

		if ( empty( $attachment_ids ) ) {
			return '';
		}

		// Check if we should use gallery shortcode.
		if ( ! empty( $field['use_shortcode'] ) ) {
			return sprintf(
				'[gallery ids="%s"]',
				esc_attr( implode( ',', $attachment_ids ) )
			);
		}

		// Build image grid HTML.
		$size = $field['display_size'] ?? $this->getPreviewSize( $field );
		$html = '<div class="apd-gallery-display">';

		foreach ( $attachment_ids as $attachment_id ) {
			$image_html = wp_get_attachment_image(
				$attachment_id,
				$size,
				false,
				[ 'class' => 'apd-gallery-image' ]
			);

			if ( $image_html ) {
				// Wrap in link to full size.
				$full_url = wp_get_attachment_url( $attachment_id );
				if ( $full_url ) {
					$html .= sprintf(
						'<a href="%s" class="apd-gallery-link">%s</a>',
						esc_url( $full_url ),
						$image_html
					);
				} else {
					$html .= $image_html;
				}
			}
		}

		$html .= '</div>';

		return $html;
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
		$ids = $this->normalizeIds( $value );
		return empty( $ids );
	}

	/**
	 * Prepare the value for storage.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The sanitized value.
	 * @return string JSON encoded array of IDs.
	 */
	public function prepareValueForStorage( mixed $value ): mixed {
		$ids = $this->normalizeIds( $value );
		return wp_json_encode( $ids );
	}

	/**
	 * Prepare the value from storage.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The stored value.
	 * @return array<int> Array of attachment IDs.
	 */
	public function prepareValueFromStorage( mixed $value ): mixed {
		if ( empty( $value ) ) {
			return [];
		}

		// Handle JSON encoded string.
		if ( is_string( $value ) ) {
			// Try JSON decode first.
			$decoded = json_decode( $value, true );
			if ( is_array( $decoded ) ) {
				return array_map( 'absint', array_filter( $decoded ) );
			}

			// Fall back to comma-separated.
			return $this->normalizeIds( $value );
		}

		if ( is_array( $value ) ) {
			return array_map( 'absint', array_filter( $value ) );
		}

		return [];
	}

	/**
	 * Normalize value to array of integer IDs.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The value to normalize.
	 * @return array<int> Array of attachment IDs.
	 */
	protected function normalizeIds( mixed $value ): array {
		if ( empty( $value ) ) {
			return [];
		}

		if ( is_string( $value ) ) {
			// Try JSON decode first.
			$decoded = json_decode( $value, true );
			if ( is_array( $decoded ) ) {
				$value = $decoded;
			} else {
				// Fall back to comma-separated.
				$value = array_map( 'trim', explode( ',', $value ) );
			}
		}

		if ( ! is_array( $value ) ) {
			return [];
		}

		// Convert to integers and filter out empty values.
		$ids = array_map( 'absint', $value );
		return array_values( array_filter( $ids ) );
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

	/**
	 * Get max images limit from field configuration.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $field Field configuration.
	 * @return int Max images (0 = unlimited).
	 */
	protected function getMaxImages( array $field ): int {
		return isset( $field['max_images'] ) ? absint( $field['max_images'] ) : 0;
	}
}
