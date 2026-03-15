<?php
/**
 * Category Filter.
 *
 * Filter by listing category taxonomy.
 *
 * @package APD\Search\Filters
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Search\Filters;

use WP_Query;
use WP_Term;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CategoryFilter
 *
 * Filter listings by apd_category taxonomy.
 *
 * @since 1.0.0
 */
class CategoryFilter extends AbstractFilter {

	/**
	 * Maximum recursion depth for hierarchical rendering.
	 */
	private const MAX_DEPTH = 10;

	/**
	 * Cached options.
	 *
	 * @var array<string, string>|null
	 */
	private ?array $cached_options = null;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $config Filter configuration.
	 */
	public function __construct( array $config = [] ) {
		$defaults = [
			'name'         => 'category',
			'label'        => __( 'Category', 'all-purpose-directory' ),
			'source'       => 'taxonomy',
			'source_key'   => 'apd_category',
			'multiple'     => false,
			'empty_option' => __( 'All Categories', 'all-purpose-directory' ),
			'hierarchical' => true,
			'hide_empty'   => true,
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
		return 'select';
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
		$value   = $this->sanitize( $value );
		$options = $this->getOptions();

		$output  = $this->renderWrapperStart( $value );
		$output .= $this->renderLabel();

		$attributes = $this->getCommonAttributes();
		if ( $this->config['multiple'] ) {
			$attributes['multiple'] = true;
			$attributes['name']    .= '[]';
		}

		$output .= sprintf(
			'<select %s>',
			$this->buildAttributes( $attributes )
		);

		// Empty option.
		if ( ! empty( $this->config['empty_option'] ) && ! $this->config['multiple'] ) {
			$output .= sprintf(
				'<option value="">%s</option>',
				esc_html( $this->config['empty_option'] )
			);
		}

		// Render hierarchical options.
		if ( $this->config['hierarchical'] ) {
			$output .= $this->renderHierarchicalOptions( $value );
		} else {
			foreach ( $options as $opt_value => $opt_label ) {
				$selected = $this->isOptionSelected( $opt_value, $value );
				$output  .= sprintf(
					'<option value="%s"%s>%s</option>',
					esc_attr( (string) $opt_value ),
					$selected ? ' selected' : '',
					esc_html( $opt_label )
				);
			}
		}

		$output .= '</select>';
		$output .= $this->renderWrapperEnd();

		return $output;
	}

	/**
	 * Render hierarchical options with indentation.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $selected_value Current selected value.
	 * @param int   $parent         Parent term ID.
	 * @param int   $depth          Current depth.
	 * @return string Option HTML.
	 */
	private function renderHierarchicalOptions( mixed $selected_value, int $parent = 0, int $depth = 0 ): string {
		// Prevent infinite recursion.
		if ( $depth > self::MAX_DEPTH ) {
			return '';
		}

		$taxonomy = $this->config['source_key'];
		$terms    = get_terms(
			[
				'taxonomy'   => $taxonomy,
				'hide_empty' => $this->config['hide_empty'],
				'parent'     => $parent,
			]
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return '';
		}

		$output = '';
		$indent = str_repeat( '&nbsp;&nbsp;', $depth );

		foreach ( $terms as $term ) {
			$selected = $this->isOptionSelected( (string) $term->term_id, $selected_value );
			$output  .= sprintf(
				'<option value="%s"%s>%s%s</option>',
				esc_attr( (string) $term->term_id ),
				$selected ? ' selected' : '',
				$indent,
				esc_html( $term->name )
			);

			// Render children.
			$output .= $this->renderHierarchicalOptions( $selected_value, $term->term_id, $depth + 1 );
		}

		return $output;
	}

	/**
	 * Check if an option is selected.
	 *
	 * @since 1.0.0
	 *
	 * @param string|int $opt_value     Option value.
	 * @param mixed      $current_value Current filter value.
	 * @return bool True if selected.
	 */
	private function isOptionSelected( string|int $opt_value, mixed $current_value ): bool {
		$opt_value = (string) $opt_value;
		if ( is_array( $current_value ) ) {
			return in_array( $opt_value, array_map( 'strval', $current_value ), true );
		}

		return (string) $current_value === $opt_value;
	}

	/**
	 * Sanitize the filter value.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The raw value.
	 * @return mixed The sanitized value.
	 */
	public function sanitize( mixed $value ): mixed {
		if ( is_array( $value ) ) {
			return array_map( 'absint', $value );
		}

		return absint( $value );
	}

	/**
	 * Modify the WP_Query.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Query $query The query to modify.
	 * @param mixed    $value The sanitized filter value.
	 * @return void
	 */
	public function modifyQuery( WP_Query $query, mixed $value ): void {
		if ( ! $this->isActive( $value ) ) {
			return;
		}

		$taxonomy  = $this->config['source_key'];
		$tax_query = $query->get( 'tax_query', [] );

		if ( ! is_array( $tax_query ) ) {
			$tax_query = [];
		}

		$tax_query[] = [
			'taxonomy' => $taxonomy,
			'field'    => 'term_id',
			'terms'    => is_array( $value ) ? $value : [ $value ],
		];

		$query->set( 'tax_query', $tax_query ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Category filter is core search functionality.
	}

	/**
	 * Get available options (categories).
	 *
	 * Caches results to avoid N+1 queries during rendering.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string> Options keyed by term ID.
	 */
	public function getOptions(): array {
		// Return cached options if available.
		if ( $this->cached_options !== null ) {
			return $this->cached_options;
		}

		$taxonomy = $this->config['source_key'];
		$terms    = get_terms(
			[
				'taxonomy'   => $taxonomy,
				'hide_empty' => $this->config['hide_empty'],
			]
		);

		if ( is_wp_error( $terms ) ) {
			$this->cached_options = [];
			return $this->cached_options;
		}

		$options = [];
		foreach ( $terms as $term ) {
			$options[ (string) $term->term_id ] = $term->name;
		}

		$this->cached_options = parent::getOptions() ?: $options;
		return $this->cached_options;
	}

	/**
	 * Get the display value for active filter.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The current filter value.
	 * @return string Human-readable display value.
	 */
	public function getDisplayValue( mixed $value ): string {
		if ( is_array( $value ) ) {
			$names = [];
			foreach ( $value as $term_id ) {
				$term = get_term( (int) $term_id, $this->config['source_key'] );
				if ( $term instanceof WP_Term ) {
					$names[] = $term->name;
				}
			}
			return implode( ', ', $names );
		}

		$term = get_term( (int) $value, $this->config['source_key'] );

		return $term instanceof WP_Term ? $term->name : '';
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
		if ( is_array( $value ) ) {
			return ! empty( array_filter( $value ) );
		}

		return ! empty( $value ) && $value > 0;
	}
}
