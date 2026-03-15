<?php
/**
 * Abstract Filter base class.
 *
 * Provides default implementations for common filter functionality.
 * Concrete filter types should extend this class rather than implementing
 * FilterInterface directly.
 *
 * @package APD\Search\Filters
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Search\Filters;

use APD\Contracts\FilterInterface;
use WP_Query;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AbstractFilter
 *
 * Base class for all filter types providing common functionality.
 *
 * @since 1.0.0
 */
abstract class AbstractFilter implements FilterInterface {

	/**
	 * Filter configuration.
	 *
	 * @var array<string, mixed>
	 */
	protected array $config = [];

	/**
	 * Default filter configuration.
	 *
	 * @var array<string, mixed>
	 */
	protected const DEFAULT_CONFIG = [
		'name'           => '',
		'type'           => 'select',
		'label'          => '',
		'source'         => 'custom',
		'source_key'     => '',
		'options'        => [],
		'multiple'       => false,
		'empty_option'   => '',
		'query_callback' => null,
		'priority'       => 10,
		'active'         => true,
		'class'          => '',
		'attributes'     => [],
	];

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $config Filter configuration.
	 */
	public function __construct( array $config = [] ) {
		$this->config = wp_parse_args( $config, static::DEFAULT_CONFIG );

		// Generate label from name if not provided.
		if ( empty( $this->config['label'] ) && ! empty( $this->config['name'] ) ) {
			$this->config['label'] = ucwords( str_replace( [ '_', '-' ], ' ', $this->config['name'] ) );
		}
	}

	/**
	 * Get the filter name identifier.
	 *
	 * @since 1.0.0
	 *
	 * @return string The filter name.
	 */
	public function getName(): string {
		return $this->config['name'];
	}

	/**
	 * Get the filter type.
	 *
	 * Must be implemented by child classes.
	 *
	 * @since 1.0.0
	 *
	 * @return string The filter type.
	 */
	abstract public function getType(): string;

	/**
	 * Render the filter HTML.
	 *
	 * Must be implemented by child classes.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Current filter value.
	 * @return string The rendered HTML.
	 */
	abstract public function render( mixed $value ): string;

	/**
	 * Sanitize the filter value.
	 *
	 * Default implementation uses sanitize_text_field().
	 * Override in child classes for type-specific sanitization.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The raw value.
	 * @return mixed The sanitized value.
	 */
	public function sanitize( mixed $value ): mixed {
		if ( is_array( $value ) ) {
			return array_map( 'sanitize_text_field', $value );
		}

		if ( is_string( $value ) ) {
			return sanitize_text_field( $value );
		}

		return $value;
	}

	/**
	 * Modify the WP_Query.
	 *
	 * Default implementation does nothing.
	 * Override in child classes for filter-specific query modification.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Query $query The query to modify.
	 * @param mixed    $value The sanitized filter value.
	 * @return void
	 */
	public function modifyQuery( WP_Query $query, mixed $value ): void {
		// Check for custom callback.
		if ( ! empty( $this->config['query_callback'] ) && is_callable( $this->config['query_callback'] ) ) {
			call_user_func( $this->config['query_callback'], $query, $value, $this );
		}
	}

	/**
	 * Get available options.
	 *
	 * Default implementation returns config options.
	 * Override for dynamic options.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string> Options keyed by value.
	 */
	public function getOptions(): array {
		$options = $this->config['options'] ?? [];

		/**
		 * Filter the options for a filter.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, string> $options Filter options.
		 * @param FilterInterface       $filter  The filter instance.
		 */
		return apply_filters( 'apd_filter_options', $options, $this );
	}

	/**
	 * Check if this filter is active.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The current filter value.
	 * @return bool True if active.
	 */
	public function isActive( mixed $value ): bool {
		if ( is_array( $value ) ) {
			return ! empty( $value );
		}

		if ( is_string( $value ) ) {
			return trim( $value ) !== '';
		}

		return $value !== null && $value !== false;
	}

	/**
	 * Get the filter configuration.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed> The filter configuration.
	 */
	public function getConfig(): array {
		return $this->config;
	}

	/**
	 * Get the URL parameter name.
	 *
	 * @since 1.0.0
	 *
	 * @return string The URL parameter name.
	 */
	public function getUrlParam(): string {
		return 'apd_' . $this->getName();
	}

	/**
	 * Get the display label.
	 *
	 * @since 1.0.0
	 *
	 * @return string The display label.
	 */
	public function getLabel(): string {
		return $this->config['label'] ?? '';
	}

	/**
	 * Get the display value for active filter chips.
	 *
	 * Default implementation returns the value or label from options.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The current filter value.
	 * @return string Human-readable display value.
	 */
	public function getDisplayValue( mixed $value ): string {
		if ( is_array( $value ) ) {
			$options = $this->getOptions();
			$labels  = [];

			foreach ( $value as $v ) {
				$labels[] = $options[ $v ] ?? $v;
			}

			return implode( ', ', $labels );
		}

		$options = $this->getOptions();

		return $options[ $value ] ?? (string) $value;
	}

	/**
	 * Get the filter ID attribute.
	 *
	 * @since 1.0.0
	 *
	 * @return string The filter ID.
	 */
	protected function getFilterId(): string {
		return 'apd-filter-' . $this->getName();
	}

	/**
	 * Build HTML attributes string.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $attributes Key-value pairs of attributes.
	 * @return string The attributes string.
	 */
	protected function buildAttributes( array $attributes ): string {
		$parts = [];

		foreach ( $attributes as $key => $attr_value ) {
			if ( $attr_value === true ) {
				$parts[] = esc_attr( $key );
			} elseif ( $attr_value !== false && $attr_value !== null ) {
				$parts[] = sprintf( '%s="%s"', esc_attr( $key ), esc_attr( (string) $attr_value ) );
			}
		}

		return implode( ' ', $parts );
	}

	/**
	 * Get common filter attributes.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed> Common attributes.
	 */
	protected function getCommonAttributes(): array {
		$attributes = [
			'id'   => $this->getFilterId(),
			'name' => $this->getUrlParam(),
		];

		if ( ! empty( $this->config['class'] ) ) {
			$attributes['class'] = $this->config['class'];
		}

		// Merge any additional attributes.
		if ( ! empty( $this->config['attributes'] ) && is_array( $this->config['attributes'] ) ) {
			$attributes = array_merge( $attributes, $this->config['attributes'] );
		}

		return $attributes;
	}

	/**
	 * Render the filter wrapper start.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Current value for active state.
	 * @return string The wrapper HTML.
	 */
	protected function renderWrapperStart( mixed $value ): string {
		$classes = [
			'apd-filter',
			'apd-filter--' . $this->getType(),
			'apd-filter--' . $this->getName(),
		];

		if ( $this->isActive( $value ) ) {
			$classes[] = 'apd-filter--active';
		}

		/**
		 * Filter the wrapper CSS classes.
		 *
		 * @since 1.0.0
		 *
		 * @param array           $classes CSS classes.
		 * @param FilterInterface $filter  The filter instance.
		 * @param mixed           $value   Current value.
		 */
		$classes = apply_filters( 'apd_filter_wrapper_class', $classes, $this, $value );

		return sprintf(
			'<div class="%s" data-filter="%s">',
			esc_attr( implode( ' ', $classes ) ),
			esc_attr( $this->getName() )
		);
	}

	/**
	 * Render the filter wrapper end.
	 *
	 * @since 1.0.0
	 *
	 * @return string The wrapper HTML.
	 */
	protected function renderWrapperEnd(): string {
		return '</div>';
	}

	/**
	 * Render the filter label.
	 *
	 * @since 1.0.0
	 *
	 * @return string The label HTML.
	 */
	protected function renderLabel(): string {
		if ( empty( $this->config['label'] ) ) {
			return '';
		}

		return sprintf(
			'<label for="%s" class="apd-filter__label">%s</label>',
			esc_attr( $this->getFilterId() ),
			esc_html( $this->config['label'] )
		);
	}
}
