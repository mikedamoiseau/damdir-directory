<?php
/**
 * Abstract Shortcode Class.
 *
 * Base class for all plugin shortcodes.
 *
 * @package APD\Shortcode
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Shortcode;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AbstractShortcode
 *
 * @since 1.0.0
 */
abstract class AbstractShortcode {

	/**
	 * Shortcode tag (without brackets).
	 *
	 * @var string
	 */
	protected string $tag = '';

	/**
	 * Shortcode description.
	 *
	 * @var string
	 */
	protected string $description = '';

	/**
	 * Default attributes.
	 *
	 * @var array<string, mixed>
	 */
	protected array $defaults = [];

	/**
	 * Attribute documentation.
	 *
	 * @var array<string, array>
	 */
	protected array $attribute_docs = [];

	/**
	 * Get the shortcode tag.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_tag(): string {
		return $this->tag;
	}

	/**
	 * Get the shortcode description.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_description(): string {
		return $this->description;
	}

	/**
	 * Get default attributes.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed>
	 */
	public function get_defaults(): array {
		return $this->defaults;
	}

	/**
	 * Get attribute documentation.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array>
	 */
	public function get_attribute_docs(): array {
		return $this->attribute_docs;
	}

	/**
	 * Get example usage.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_example(): string {
		return '[' . $this->tag . ']';
	}

	/**
	 * Render the shortcode.
	 *
	 * @since 1.0.0
	 *
	 * @param array|string $atts    Shortcode attributes.
	 * @param string|null  $content Shortcode content.
	 * @return string Shortcode output.
	 */
	public function render( $atts = [], ?string $content = null ): string {
		// Parse attributes.
		$atts = $this->parse_attributes( $atts );

		/**
		 * Filter the shortcode attributes before rendering.
		 *
		 * @since 1.0.0
		 *
		 * @param array  $atts    The parsed attributes.
		 * @param string $tag     The shortcode tag.
		 * @param string $content The shortcode content.
		 */
		$atts = apply_filters( "apd_shortcode_{$this->tag}_atts", $atts, $this->tag, $content );

		/**
		 * Fires before the shortcode is rendered.
		 *
		 * @since 1.0.0
		 *
		 * @param array  $atts    The shortcode attributes.
		 * @param string $content The shortcode content.
		 */
		do_action( "apd_before_shortcode_{$this->tag}", $atts, $content );

		// Generate output.
		$output = $this->output( $atts, $content );

		/**
		 * Filter the shortcode output.
		 *
		 * @since 1.0.0
		 *
		 * @param string $output  The shortcode output.
		 * @param array  $atts    The shortcode attributes.
		 * @param string $content The shortcode content.
		 */
		$output = apply_filters( "apd_shortcode_{$this->tag}_output", $output, $atts, $content );

		/**
		 * Fires after the shortcode is rendered.
		 *
		 * @since 1.0.0
		 *
		 * @param string $output  The shortcode output.
		 * @param array  $atts    The shortcode attributes.
		 * @param string $content The shortcode content.
		 */
		do_action( "apd_after_shortcode_{$this->tag}", $output, $atts, $content );

		return $output;
	}

	/**
	 * Generate the shortcode output.
	 *
	 * @since 1.0.0
	 *
	 * @param array       $atts    Parsed shortcode attributes.
	 * @param string|null $content Shortcode content.
	 * @return string Shortcode output.
	 */
	abstract protected function output( array $atts, ?string $content ): string;

	/**
	 * Parse and sanitize shortcode attributes.
	 *
	 * @since 1.0.0
	 *
	 * @param array|string $atts Raw shortcode attributes.
	 * @return array Parsed attributes.
	 */
	protected function parse_attributes( $atts ): array {
		// Handle empty or string attributes.
		if ( ! is_array( $atts ) ) {
			$atts = [];
		}

		// Merge with defaults.
		$atts = shortcode_atts( $this->defaults, $atts, $this->tag );

		// Sanitize attributes.
		return $this->sanitize_attributes( $atts );
	}

	/**
	 * Sanitize shortcode attributes.
	 *
	 * Override in child classes for custom sanitization.
	 *
	 * @since 1.0.0
	 *
	 * @param array $atts Attributes to sanitize.
	 * @return array Sanitized attributes.
	 */
	protected function sanitize_attributes( array $atts ): array {
		$sanitized = [];

		foreach ( $atts as $key => $value ) {
			$sanitized[ $key ] = $this->sanitize_attribute( $key, $value );
		}

		return $sanitized;
	}

	/**
	 * Sanitize a single attribute.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key   Attribute key.
	 * @param mixed  $value Attribute value.
	 * @return mixed Sanitized value.
	 */
	protected function sanitize_attribute( string $key, $value ) {
		// Get attribute definition.
		$doc  = $this->attribute_docs[ $key ] ?? [];
		$type = $doc['type'] ?? 'string';

		switch ( $type ) {
			case 'int':
			case 'integer':
				return absint( $value );

			case 'bool':
			case 'boolean':
				return filter_var( $value, FILTER_VALIDATE_BOOLEAN );

			case 'array':
				if ( is_string( $value ) ) {
					return array_map( 'trim', explode( ',', $value ) );
				}
				return is_array( $value ) ? $value : [];

			case 'id':
				return absint( $value );

			case 'ids':
				if ( is_string( $value ) ) {
					return array_filter( array_map( 'absint', explode( ',', $value ) ) );
				}
				return is_array( $value ) ? array_filter( array_map( 'absint', $value ) ) : [];

			case 'slug':
				return sanitize_key( $value );

			case 'text':
			case 'string':
			default:
				return sanitize_text_field( (string) $value );
		}
	}

	/**
	 * Get an error message wrapper.
	 *
	 * @since 1.0.0
	 *
	 * @param string $message Error message.
	 * @return string HTML wrapped error.
	 */
	protected function error( string $message ): string {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return '';
		}

		return sprintf(
			'<div class="apd-shortcode-error"><strong>%s:</strong> %s</div>',
			esc_html__( 'Shortcode Error', 'all-purpose-directory' ),
			esc_html( $message )
		);
	}

	/**
	 * Render a "coming soon" placeholder.
	 *
	 * @since 1.0.0
	 *
	 * @param string $feature Feature name.
	 * @return string HTML placeholder.
	 */
	protected function coming_soon( string $feature ): string {
		return sprintf(
			'<div class="apd-coming-soon">
				<p class="apd-coming-soon__message">%s</p>
			</div>',
			sprintf(
				/* translators: %s: Feature name */
				esc_html__( '%s is coming soon.', 'all-purpose-directory' ),
				esc_html( $feature )
			)
		);
	}

	/**
	 * Check if user is logged in, with optional message.
	 *
	 * @since 1.0.0
	 *
	 * @param string $message Optional message to show.
	 * @return string Empty string if logged in, login prompt otherwise.
	 */
	protected function require_login( string $message = '' ): string {
		if ( is_user_logged_in() ) {
			return '';
		}

		if ( empty( $message ) ) {
			$message = __( 'Please log in to access this content.', 'all-purpose-directory' );
		}

		$login_url = wp_login_url( get_permalink() );

		return sprintf(
			'<div class="apd-login-required">
				<p class="apd-login-required__message">%s</p>
				<p class="apd-login-required__link"><a href="%s" class="apd-button">%s</a></p>
			</div>',
			esc_html( $message ),
			esc_url( $login_url ),
			esc_html__( 'Log In', 'all-purpose-directory' )
		);
	}
}
