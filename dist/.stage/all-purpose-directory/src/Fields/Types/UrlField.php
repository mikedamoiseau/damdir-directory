<?php
/**
 * URL Field Type.
 *
 * Provides URL input with validation and clickable link formatting.
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
 * Class UrlField
 *
 * Handles URL input fields with validation.
 *
 * @since 1.0.0
 */
class UrlField extends AbstractFieldType {

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
	 * Get the field type identifier.
	 *
	 * @since 1.0.0
	 *
	 * @return string The field type identifier.
	 */
	public function getType(): string {
		return 'url';
	}

	/**
	 * Render the field HTML.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $field Field configuration.
	 * @param mixed                $value Current value.
	 * @return string The rendered HTML.
	 */
	public function render( array $field, mixed $value ): string {
		$attributes         = $this->getCommonAttributes( $field );
		$attributes['type'] = 'url';

		if ( is_string( $value ) && $value !== '' ) {
			$attributes['value'] = $value;
		}

		$html  = '<input ' . $this->buildAttributes( $attributes ) . '>';
		$html .= $this->renderDescription( $field );

		return $html;
	}

	/**
	 * Sanitize the field value.
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

		return esc_url_raw( $value );
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
		// Run parent validation first.
		$parent_result = parent::validate( $value, $field );

		if ( is_wp_error( $parent_result ) ) {
			return $parent_result;
		}

		// Skip URL format validation if empty (required check is handled by parent).
		if ( $this->isEmpty( $value ) ) {
			return true;
		}

		// Validate URL format.
		if ( filter_var( $value, FILTER_VALIDATE_URL ) === false ) {
			return new WP_Error(
				'invalid_url',
				sprintf(
					/* translators: %s: field label */
					__( '%s must be a valid URL.', 'all-purpose-directory' ),
					$this->getLabel( $field )
				)
			);
		}

		return true;
	}

	/**
	 * Format the value for display.
	 *
	 * Creates a clickable link with appropriate rel attributes for external URLs.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed                $value The stored value.
	 * @param array<string, mixed> $field Field configuration.
	 * @return string The formatted value with clickable link.
	 */
	public function formatValue( mixed $value, array $field ): string {
		if ( ! is_string( $value ) || $value === '' ) {
			return '';
		}

		$url = esc_url( $value );

		if ( $url === '' ) {
			return '';
		}

		// Determine display text (strip protocol for cleaner display).
		$display_url = preg_replace( '#^https?://#', '', $url );
		$display_url = rtrim( $display_url, '/' );

		// Check if URL is external (different host than current site).
		$is_external = $this->isExternalUrl( $url );

		$rel_attr = $is_external ? ' rel="noopener noreferrer"' : '';

		return sprintf(
			'<a href="%s" target="_blank"%s>%s</a>',
			esc_attr( $url ),
			$rel_attr,
			esc_html( $display_url )
		);
	}

	/**
	 * Check if a URL is external.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url The URL to check.
	 * @return bool True if external.
	 */
	protected function isExternalUrl( string $url ): bool {
		// Parse the URL to get the host.
		$url_host = wp_parse_url( $url, PHP_URL_HOST );

		if ( ! $url_host ) {
			return false;
		}

		// Get the site host.
		$site_url  = home_url();
		$site_host = wp_parse_url( $site_url, PHP_URL_HOST );

		return $url_host !== $site_host;
	}
}
