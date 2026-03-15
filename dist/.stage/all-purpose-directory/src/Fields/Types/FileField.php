<?php
/**
 * File Field Type.
 *
 * File upload field that uses WordPress media library.
 * Stores attachment ID and displays filename with download link.
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
 * Class FileField
 *
 * Handles rendering and processing of file upload fields.
 * Uses WordPress media library for file selection.
 *
 * @since 1.0.0
 */
class FileField extends AbstractFieldType {

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
	 * Default allowed file types.
	 *
	 * @var array<string>
	 */
	protected array $default_allowed_types = [ 'pdf', 'doc', 'docx' ];

	/**
	 * Get the field type identifier.
	 *
	 * @since 1.0.0
	 *
	 * @return string The field type identifier.
	 */
	public function getType(): string {
		return 'file';
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
	 * Render the file upload field HTML.
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
		$max_size      = $this->getMaxSize( $field );

		// Get file info if attachment exists.
		$filename = '';
		$file_url = '';
		if ( $attachment_id > 0 ) {
			$file_url = wp_get_attachment_url( $attachment_id );
			if ( $file_url ) {
				$filename = basename( get_attached_file( $attachment_id ) ?: '' );
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
			'data-field-type'    => 'file',
			'data-allowed-types' => esc_attr( implode( ',', $allowed_types ) ),
		];

		if ( $max_size > 0 ) {
			$input_attributes['data-max-size'] = $max_size;
		}

		if ( $this->isRequired( $field ) ) {
			$input_attributes['required']      = true;
			$input_attributes['aria-required'] = 'true';
		}

		if ( ! empty( $field['description'] ) ) {
			$input_attributes['aria-describedby'] = $field_id . '-description';
		}

		// Build wrapper class.
		$wrapper_class = 'apd-file-field';
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

		// File preview container.
		$display_style = $attachment_id > 0 && $filename ? '' : 'display: none;';
		$html         .= sprintf(
			'<div class="apd-file-preview" style="%s">',
			esc_attr( $display_style )
		);
		$html         .= sprintf(
			'<span class="apd-file-name">%s</span>',
			esc_html( $filename )
		);
		$html         .= sprintf(
			'<button type="button" class="apd-file-remove button" aria-label="%s">%s</button>',
			esc_attr__( 'Remove file', 'all-purpose-directory' ),
			esc_html__( 'Remove', 'all-purpose-directory' )
		);
		$html         .= '</div>';

		// Upload button.
		$button_style = $attachment_id > 0 && $filename ? 'display: none;' : '';
		$html        .= sprintf(
			'<button type="button" class="apd-file-upload button" style="%s" data-title="%s" data-button="%s">%s</button>',
			esc_attr( $button_style ),
			esc_attr__( 'Select File', 'all-purpose-directory' ),
			esc_attr__( 'Use this file', 'all-purpose-directory' ),
			esc_html__( 'Select File', 'all-purpose-directory' )
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
					__( '%s contains an invalid file.', 'all-purpose-directory' ),
					$this->getLabel( $field )
				)
			);
			return $errors;
		}

		// Check file type if allowed_types is specified.
		$allowed_types = $this->getAllowedTypes( $field );
		if ( ! empty( $allowed_types ) ) {
			$file_path = get_attached_file( $attachment_id );
			if ( $file_path ) {
				$extension = strtolower( pathinfo( $file_path, PATHINFO_EXTENSION ) );
				if ( ! in_array( $extension, $allowed_types, true ) ) {
					$errors->add(
						'invalid_file_type',
						sprintf(
							/* translators: 1: field label, 2: allowed file types */
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
	 * @return string The formatted value (filename with download link).
	 */
	public function formatValue( mixed $value, array $field ): string {
		$attachment_id = absint( $value );

		if ( $attachment_id <= 0 ) {
			return '';
		}

		$file_url = wp_get_attachment_url( $attachment_id );
		if ( ! $file_url ) {
			return '';
		}

		$file_path = get_attached_file( $attachment_id );
		$filename  = $file_path ? basename( $file_path ) : __( 'Download', 'all-purpose-directory' );

		return sprintf(
			'<a href="%s" class="apd-file-link" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_url( $file_url ),
			esc_html( $filename )
		);
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
	 * Get allowed file types from field configuration.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $field Field configuration.
	 * @return array<string> Allowed file extensions.
	 */
	protected function getAllowedTypes( array $field ): array {
		if ( isset( $field['allowed_types'] ) && is_array( $field['allowed_types'] ) ) {
			return array_map( 'strtolower', $field['allowed_types'] );
		}
		return $this->default_allowed_types;
	}

	/**
	 * Get max file size from field configuration.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $field Field configuration.
	 * @return int Max file size in bytes (0 = no limit).
	 */
	protected function getMaxSize( array $field ): int {
		return isset( $field['max_size'] ) ? absint( $field['max_size'] ) : 0;
	}
}
