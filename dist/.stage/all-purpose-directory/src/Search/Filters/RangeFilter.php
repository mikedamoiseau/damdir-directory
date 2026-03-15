<?php
/**
 * Range Filter.
 *
 * Numeric range filter with min/max inputs for filtering by numeric meta fields.
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
 * Class RangeFilter
 *
 * Numeric range filter for filterable number fields.
 *
 * @since 1.0.0
 */
class RangeFilter extends AbstractFilter {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $config Filter configuration.
	 */
	public function __construct( array $config = [] ) {
		$defaults = [
			'name'            => '',
			'label'           => '',
			'source'          => 'field',
			'source_key'      => '',
			'min'             => null,
			'max'             => null,
			'step'            => 1,
			'min_placeholder' => __( 'Min', 'all-purpose-directory' ),
			'max_placeholder' => __( 'Max', 'all-purpose-directory' ),
			'prefix'          => '',
			'suffix'          => '',
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
		return 'range';
	}

	/**
	 * Get the URL parameter name for min value.
	 *
	 * @since 1.0.0
	 *
	 * @return string The URL parameter name.
	 */
	public function getUrlParamMin(): string {
		return $this->getUrlParam() . '_min';
	}

	/**
	 * Get the URL parameter name for max value.
	 *
	 * @since 1.0.0
	 *
	 * @return string The URL parameter name.
	 */
	public function getUrlParamMax(): string {
		return $this->getUrlParam() . '_max';
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
		$min   = $value['min'] ?? '';
		$max   = $value['max'] ?? '';

		$output  = $this->renderWrapperStart( $value );
		$output .= $this->renderLabel();

		$output .= '<div class="apd-filter__range-inputs">';

		// Prefix display.
		if ( ! empty( $this->config['prefix'] ) ) {
			$output .= sprintf(
				'<span class="apd-filter__prefix">%s</span>',
				esc_html( $this->config['prefix'] )
			);
		}

		// Min input.
		$min_attributes = [
			'type'        => 'number',
			'id'          => $this->getFilterId() . '-min',
			'name'        => $this->getUrlParamMin(),
			'value'       => $min,
			'placeholder' => $this->config['min_placeholder'],
			'class'       => 'apd-filter__input apd-filter__input--min',
			'step'        => $this->config['step'],
		];

		if ( $this->config['min'] !== null ) {
			$min_attributes['min'] = $this->config['min'];
		}
		if ( $this->config['max'] !== null ) {
			$min_attributes['max'] = $this->config['max'];
		}

		$output .= sprintf(
			'<input %s>',
			$this->buildAttributes( $min_attributes )
		);

		$output .= '<span class="apd-filter__range-separator" aria-hidden="true">&ndash;</span>';

		// Max input.
		$max_attributes = [
			'type'        => 'number',
			'id'          => $this->getFilterId() . '-max',
			'name'        => $this->getUrlParamMax(),
			'value'       => $max,
			'placeholder' => $this->config['max_placeholder'],
			'class'       => 'apd-filter__input apd-filter__input--max',
			'step'        => $this->config['step'],
		];

		if ( $this->config['min'] !== null ) {
			$max_attributes['min'] = $this->config['min'];
		}
		if ( $this->config['max'] !== null ) {
			$max_attributes['max'] = $this->config['max'];
		}

		$output .= sprintf(
			'<input %s>',
			$this->buildAttributes( $max_attributes )
		);

		// Suffix display.
		if ( ! empty( $this->config['suffix'] ) ) {
			$output .= sprintf(
				'<span class="apd-filter__suffix">%s</span>',
				esc_html( $this->config['suffix'] )
			);
		}

		$output .= '</div>';
		$output .= $this->renderWrapperEnd();

		return $output;
	}

	/**
	 * Sanitize the filter value.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The raw value.
	 * @return array{min: string, max: string} The sanitized value.
	 */
	public function sanitize( mixed $value ): mixed {
		if ( is_array( $value ) ) {
			return [
				'min' => isset( $value['min'] ) ? $this->sanitizeNumber( $value['min'] ) : '',
				'max' => isset( $value['max'] ) ? $this->sanitizeNumber( $value['max'] ) : '',
			];
		}

		return [
			'min' => '',
			'max' => '',
		];
	}

	/**
	 * Sanitize a single number value.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The raw value.
	 * @return string The sanitized number as string, or empty string.
	 */
	private function sanitizeNumber( mixed $value ): string {
		if ( $value === '' || $value === null ) {
			return '';
		}

		// Validate that value is numeric.
		if ( ! is_numeric( $value ) ) {
			return '';
		}

		$number = is_float( $this->config['step'] ) || $this->config['step'] < 1
			? (float) $value
			: (int) $value;

		// Validate against min/max if configured.
		if ( $this->config['min'] !== null && $number < $this->config['min'] ) {
			$number = $this->config['min'];
		}
		if ( $this->config['max'] !== null && $number > $this->config['max'] ) {
			$number = $this->config['max'];
		}

		return (string) $number;
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

		$meta_key   = $this->getMetaKey();
		$meta_query = $query->get( 'meta_query', [] );

		if ( ! is_array( $meta_query ) ) {
			$meta_query = [];
		}

		$min = $value['min'] ?? '';
		$max = $value['max'] ?? '';

		if ( $min !== '' && $max !== '' ) {
			// Both min and max set - use BETWEEN.
			$meta_query[] = [
				'key'     => $meta_key,
				'value'   => [ (float) $min, (float) $max ],
				'type'    => 'NUMERIC',
				'compare' => 'BETWEEN',
			];
		} elseif ( $min !== '' ) {
			// Only min set.
			$meta_query[] = [
				'key'     => $meta_key,
				'value'   => (float) $min,
				'type'    => 'NUMERIC',
				'compare' => '>=',
			];
		} elseif ( $max !== '' ) {
			// Only max set.
			$meta_query[] = [
				'key'     => $meta_key,
				'value'   => (float) $max,
				'type'    => 'NUMERIC',
				'compare' => '<=',
			];
		}

		$query->set( 'meta_query', $meta_query ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Numeric range filter requires meta query.
	}

	/**
	 * Get the meta key for the source field.
	 *
	 * @since 1.0.0
	 *
	 * @return string The meta key.
	 */
	private function getMetaKey(): string {
		$source_key = $this->config['source_key'] ?? $this->config['name'];

		return '_apd_' . sanitize_key( $source_key );
	}

	/**
	 * Get available options.
	 *
	 * Range filters don't have discrete options.
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
	 * @return string Human-readable range display.
	 */
	public function getDisplayValue( mixed $value ): string {
		$min    = $value['min'] ?? '';
		$max    = $value['max'] ?? '';
		$prefix = $this->config['prefix'] ?? '';
		$suffix = $this->config['suffix'] ?? '';

		if ( $min !== '' && $max !== '' ) {
			return sprintf( '%s%s%s - %s%s%s', $prefix, $min, $suffix, $prefix, $max, $suffix );
		} elseif ( $min !== '' ) {
			return sprintf(
				/* translators: %s: minimum value */
				__( '%s or more', 'all-purpose-directory' ),
				$prefix . $min . $suffix
			);
		} elseif ( $max !== '' ) {
			return sprintf(
				/* translators: %s: maximum value */
				__( 'Up to %s', 'all-purpose-directory' ),
				$prefix . $max . $suffix
			);
		}

		return '';
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
		if ( ! is_array( $value ) ) {
			return false;
		}

		return ( $value['min'] ?? '' ) !== '' || ( $value['max'] ?? '' ) !== '';
	}

	/**
	 * Get filter value from request.
	 *
	 * Override to handle _min/_max parameters.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $request Request data.
	 * @return array{min: string, max: string} The filter value.
	 */
	public function getValueFromRequest( array $request ): array {
		$min_param = $this->getUrlParamMin();
		$max_param = $this->getUrlParamMax();

		return $this->sanitize(
			[
				'min' => $request[ $min_param ] ?? '',
				'max' => $request[ $max_param ] ?? '',
			]
		);
	}
}
