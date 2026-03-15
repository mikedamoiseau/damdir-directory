<?php
/**
 * Tag Filter.
 *
 * Filter by listing tag taxonomy.
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
 * Class TagFilter
 *
 * Filter listings by apd_tag taxonomy using checkboxes.
 *
 * @since 1.0.0
 */
class TagFilter extends AbstractFilter {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $config Filter configuration.
	 */
	public function __construct( array $config = [] ) {
		$defaults = [
			'name'       => 'tag',
			'label'      => __( 'Tags', 'all-purpose-directory' ),
			'source'     => 'taxonomy',
			'source_key' => 'apd_tag',
			'multiple'   => true,
			'hide_empty' => true,
			'max_items'  => 20,
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
		return 'checkbox';
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

		if ( empty( $options ) ) {
			return '';
		}

		$output = $this->renderWrapperStart( $value );

		$output .= sprintf(
			'<fieldset class="apd-filter__fieldset"><legend class="apd-filter__legend">%s</legend>',
			esc_html( $this->getLabel() )
		);

		$output .= '<div class="apd-filter__options">';

		$index = 0;
		foreach ( $options as $opt_value => $opt_label ) {
			if ( $index >= $this->config['max_items'] ) {
				break;
			}

			$checked    = $this->isOptionChecked( $opt_value, $value );
			$option_id  = $this->getFilterId() . '-' . $opt_value;
			$param_name = $this->getUrlParam() . '[]';

			$output .= '<div class="apd-filter__option">';
			$output .= sprintf(
				'<input type="checkbox" id="%s" name="%s" value="%s"%s>',
				esc_attr( $option_id ),
				esc_attr( $param_name ),
				esc_attr( (string) $opt_value ),
				$checked ? ' checked' : ''
			);
			$output .= sprintf(
				'<label for="%s">%s</label>',
				esc_attr( $option_id ),
				esc_html( $opt_label )
			);
			$output .= '</div>';

			++$index;
		}

		$output .= '</div>';
		$output .= '</fieldset>';
		$output .= $this->renderWrapperEnd();

		return $output;
	}

	/**
	 * Check if an option is checked.
	 *
	 * @since 1.0.0
	 *
	 * @param string|int $opt_value     Option value.
	 * @param mixed      $current_value Current filter value.
	 * @return bool True if checked.
	 */
	private function isOptionChecked( string|int $opt_value, mixed $current_value ): bool {
		$opt_value = (string) $opt_value;
		if ( ! is_array( $current_value ) ) {
			$current_value = $current_value ? [ $current_value ] : [];
		}

		return in_array( $opt_value, array_map( 'strval', $current_value ), true );
	}

	/**
	 * Sanitize the filter value.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The raw value.
	 * @return array<int> The sanitized value.
	 */
	public function sanitize( mixed $value ): mixed {
		if ( ! is_array( $value ) ) {
			return $value ? [ absint( $value ) ] : [];
		}

		return array_map( 'absint', $value );
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
			'terms'    => $value,
		];

		$query->set( 'tax_query', $tax_query ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Tag filter is core search functionality.
	}

	/**
	 * Get available options (tags).
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string> Options keyed by term ID.
	 */
	public function getOptions(): array {
		$taxonomy = $this->config['source_key'];
		$terms    = get_terms(
			[
				'taxonomy'   => $taxonomy,
				'hide_empty' => $this->config['hide_empty'],
				'number'     => $this->config['max_items'],
				'orderby'    => 'count',
				'order'      => 'DESC',
			]
		);

		if ( is_wp_error( $terms ) ) {
			return [];
		}

		$options = [];
		foreach ( $terms as $term ) {
			$options[ (string) $term->term_id ] = $term->name;
		}

		return parent::getOptions() ?: $options;
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
		if ( ! is_array( $value ) ) {
			$value = [ $value ];
		}

		$names = [];
		foreach ( $value as $term_id ) {
			$term = get_term( (int) $term_id, $this->config['source_key'] );
			if ( $term instanceof WP_Term ) {
				$names[] = $term->name;
			}
		}

		return implode( ', ', $names );
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
		return is_array( $value ) && ! empty( array_filter( $value ) );
	}
}
