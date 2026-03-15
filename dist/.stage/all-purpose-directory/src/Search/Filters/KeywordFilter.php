<?php
/**
 * Keyword Filter.
 *
 * Text search filter for listing titles, content, and searchable meta fields.
 *
 * @package APD\Search\Filters
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Search\Filters;

use WP_Query;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class KeywordFilter
 *
 * Free text search filter.
 *
 * @since 1.0.0
 */
class KeywordFilter extends AbstractFilter {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $config Filter configuration.
	 */
	public function __construct( array $config = [] ) {
		$defaults = [
			'name'        => 'keyword',
			'label'       => __( 'Search', 'all-purpose-directory' ),
			'source'      => 'custom',
			'placeholder' => __( 'Search listings...', 'all-purpose-directory' ),
			'min_length'  => 2,
		];

		parent::__construct( wp_parse_args( $config, $defaults ) );
	}

	/**
	 * Get the filter type.
	 *
	 * @since 1.0.0
	 *
	 * @return string The filter type.
	 */
	public function getType(): string {
		return 'text';
	}

	/**
	 * Render the filter HTML.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Current filter value.
	 * @return string The rendered HTML.
	 */
	public function render( mixed $value ): string {
		$value = $this->sanitize( $value );

		$output  = $this->renderWrapperStart( $value );
		$output .= $this->renderLabel();

		$attributes = array_merge(
			$this->getCommonAttributes(),
			[
				'type'        => 'search',
				'value'       => $value,
				'placeholder' => $this->config['placeholder'] ?? '',
				'minlength'   => $this->config['min_length'] ?? 2,
				'class'       => 'apd-filter__input apd-filter__input--search',
			]
		);

		$output .= sprintf(
			'<input %s>',
			$this->buildAttributes( $attributes )
		);

		$output .= $this->renderWrapperEnd();

		return $output;
	}

	/**
	 * Maximum keyword length to prevent memory/DB issues.
	 */
	private const MAX_KEYWORD_LENGTH = 200;

	/**
	 * Sanitize the filter value.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The raw value.
	 * @return string The sanitized value.
	 */
	public function sanitize( mixed $value ): mixed {
		$sanitized = sanitize_text_field( (string) $value );

		// Limit to max length to prevent memory/DB issues.
		if ( \apd_strlen( $sanitized ) > self::MAX_KEYWORD_LENGTH ) {
			$sanitized = \apd_substr( $sanitized, 0, self::MAX_KEYWORD_LENGTH );
		}

		return $sanitized;
	}

	/**
	 * Modify the WP_Query.
	 *
	 * Note: Keyword search is handled by SearchQuery class via the 's' parameter
	 * and meta search hooks. This method does nothing by design.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Query $query The query to modify.
	 * @param mixed    $value The sanitized filter value.
	 * @return void
	 */
	public function modifyQuery( WP_Query $query, mixed $value ): void {
		// Keyword search is handled by SearchQuery::apply_keyword_search().
		// This filter exists for rendering purposes only.
	}

	/**
	 * Get available options.
	 *
	 * Text filters don't have options.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string> Empty array.
	 */
	public function getOptions(): array {
		return [];
	}

	/**
	 * Get the display value.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The current filter value.
	 * @return string The search term.
	 */
	public function getDisplayValue( mixed $value ): string {
		return sprintf(
			/* translators: %s: search keyword */
			__( '"%s"', 'all-purpose-directory' ),
			esc_html( (string) $value )
		);
	}

	/**
	 * Check if filter is active.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The current filter value.
	 * @return bool True if active.
	 */
	public function isActive( mixed $value ): bool {
		$min_length = $this->config['min_length'] ?? 2;

		return is_string( $value ) && \apd_strlen( trim( $value ) ) >= $min_length;
	}
}
