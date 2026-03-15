<?php
/**
 * Rich Text Field Type.
 *
 * WYSIWYG editor field using WordPress wp_editor().
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
 * Class RichTextField
 *
 * Handles rendering and processing of WYSIWYG editor fields.
 *
 * @since 1.0.0
 */
class RichTextField extends AbstractFieldType {

	/**
	 * Features supported by this field type.
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
	 * Default number of textarea rows.
	 *
	 * @var int
	 */
	private const DEFAULT_ROWS = 10;

	/**
	 * Get the field type identifier.
	 *
	 * @since 1.0.0
	 *
	 * @return string The field type identifier.
	 */
	public function getType(): string {
		return 'richtext';
	}

	/**
	 * Render the rich text editor HTML.
	 *
	 * Uses wp_editor() with output buffering since it outputs directly.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $field Field configuration.
	 * @param mixed                $value Current value.
	 * @return string The rendered HTML.
	 */
	public function render( array $field, mixed $value ): string {
		$editor_id = $this->getFieldId( $field );
		$content   = is_string( $value ) ? $value : '';

		// Build editor settings from field config.
		$settings = $this->getEditorSettings( $field );

		// Use output buffering since wp_editor outputs directly.
		ob_start();
		wp_editor( $content, $editor_id, $settings );
		$html = ob_get_clean();

		// Add description after the editor.
		$html .= $this->renderDescription( $field );

		return $html;
	}

	/**
	 * Get wp_editor settings from field configuration.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $field Field configuration.
	 * @return array<string, mixed> Editor settings.
	 */
	private function getEditorSettings( array $field ): array {
		$settings = [
			'textarea_name' => $this->getFieldName( $field ),
			'textarea_rows' => $field['textarea_rows'] ?? self::DEFAULT_ROWS,
			'media_buttons' => $field['media_buttons'] ?? true,
			'teeny'         => $field['teeny'] ?? false,
			'quicktags'     => $field['quicktags'] ?? true,
		];

		// Handle wpautop setting.
		if ( isset( $field['wpautop'] ) ) {
			$settings['wpautop'] = (bool) $field['wpautop'];
		}

		// Handle tinymce settings.
		if ( isset( $field['tinymce'] ) ) {
			$settings['tinymce'] = $field['tinymce'];
		}

		return $settings;
	}

	/**
	 * Sanitize the field value.
	 *
	 * Uses wp_kses_post to allow safe HTML content.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The raw value.
	 * @return mixed The sanitized value.
	 */
	public function sanitize( mixed $value ): mixed {
		if ( ! is_string( $value ) ) {
			return '';
		}

		return wp_kses_post( $value );
	}

	/**
	 * Format the value for display.
	 *
	 * Returns the HTML content which was already sanitized on save.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed                $value The stored value.
	 * @param array<string, mixed> $field Field configuration.
	 * @return string The formatted value.
	 */
	public function formatValue( mixed $value, array $field ): string {
		if ( ! is_string( $value ) || $value === '' ) {
			return '';
		}

		// Return the HTML content - it was sanitized with wp_kses_post on save.
		return $value;
	}
}
