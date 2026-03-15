<?php
/**
 * Date Range Filter.
 *
 * Date range filter with start/end date inputs for filtering by date meta fields.
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
 * Class DateRangeFilter
 *
 * Date range filter for filterable date fields.
 *
 * @since 1.0.0
 */
class DateRangeFilter extends AbstractFilter {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $config Filter configuration.
	 */
	public function __construct( array $config = [] ) {
		$defaults = [
			'name'              => '',
			'label'             => '',
			'source'            => 'field',
			'source_key'        => '',
			'min'               => '',
			'max'               => '',
			'start_label'       => __( 'From', 'all-purpose-directory' ),
			'end_label'         => __( 'To', 'all-purpose-directory' ),
			'start_placeholder' => __( 'Start date', 'all-purpose-directory' ),
			'end_placeholder'   => __( 'End date', 'all-purpose-directory' ),
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
		return 'date_range';
	}

	/**
	 * Get the URL parameter name for start date.
	 *
	 * @since 1.0.0
	 *
	 * @return string The URL parameter name.
	 */
	public function getUrlParamStart(): string {
		return $this->getUrlParam() . '_start';
	}

	/**
	 * Get the URL parameter name for end date.
	 *
	 * @since 1.0.0
	 *
	 * @return string The URL parameter name.
	 */
	public function getUrlParamEnd(): string {
		return $this->getUrlParam() . '_end';
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
		$start = $value['start'] ?? '';
		$end   = $value['end'] ?? '';

		$output  = $this->renderWrapperStart( $value );
		$output .= $this->renderLabel();

		$output .= '<div class="apd-filter__date-range-inputs">';

		// Start date.
		$output .= '<div class="apd-filter__date-field">';
		$output .= sprintf(
			'<label for="%s" class="apd-filter__date-label">%s</label>',
			esc_attr( $this->getFilterId() . '-start' ),
			esc_html( $this->config['start_label'] )
		);

		$start_attributes = [
			'type'        => 'date',
			'id'          => $this->getFilterId() . '-start',
			'name'        => $this->getUrlParamStart(),
			'value'       => $start,
			'placeholder' => $this->config['start_placeholder'],
			'class'       => 'apd-filter__input apd-filter__input--date',
		];

		if ( ! empty( $this->config['min'] ) ) {
			$start_attributes['min'] = $this->config['min'];
		}
		if ( ! empty( $this->config['max'] ) ) {
			$start_attributes['max'] = $this->config['max'];
		}

		$output .= sprintf(
			'<input %s>',
			$this->buildAttributes( $start_attributes )
		);
		$output .= '</div>';

		// End date.
		$output .= '<div class="apd-filter__date-field">';
		$output .= sprintf(
			'<label for="%s" class="apd-filter__date-label">%s</label>',
			esc_attr( $this->getFilterId() . '-end' ),
			esc_html( $this->config['end_label'] )
		);

		$end_attributes = [
			'type'        => 'date',
			'id'          => $this->getFilterId() . '-end',
			'name'        => $this->getUrlParamEnd(),
			'value'       => $end,
			'placeholder' => $this->config['end_placeholder'],
			'class'       => 'apd-filter__input apd-filter__input--date',
		];

		if ( ! empty( $this->config['min'] ) ) {
			$end_attributes['min'] = $this->config['min'];
		}
		if ( ! empty( $this->config['max'] ) ) {
			$end_attributes['max'] = $this->config['max'];
		}

		$output .= sprintf(
			'<input %s>',
			$this->buildAttributes( $end_attributes )
		);
		$output .= '</div>';

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
	 * @return array{start: string, end: string} The sanitized value.
	 */
	public function sanitize( mixed $value ): mixed {
		if ( is_array( $value ) ) {
			return [
				'start' => isset( $value['start'] ) ? $this->sanitizeDate( $value['start'] ) : '',
				'end'   => isset( $value['end'] ) ? $this->sanitizeDate( $value['end'] ) : '',
			];
		}

		return [
			'start' => '',
			'end'   => '',
		];
	}

	/**
	 * Sanitize a single date value.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The raw value.
	 * @return string The sanitized date (Y-m-d format) or empty string.
	 */
	private function sanitizeDate( mixed $value ): string {
		if ( empty( $value ) ) {
			return '';
		}

		$value = sanitize_text_field( (string) $value );

		// Validate date format (Y-m-d).
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
			$parts = explode( '-', $value );
			if ( checkdate( (int) $parts[1], (int) $parts[2], (int) $parts[0] ) ) {
				return $value;
			}
		}

		return '';
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

		$start = $value['start'] ?? '';
		$end   = $value['end'] ?? '';

		if ( $start !== '' && $end !== '' ) {
			// Both start and end set - use BETWEEN.
			$meta_query[] = [
				'key'     => $meta_key,
				'value'   => [ $start, $end ],
				'type'    => 'DATE',
				'compare' => 'BETWEEN',
			];
		} elseif ( $start !== '' ) {
			// Only start set.
			$meta_query[] = [
				'key'     => $meta_key,
				'value'   => $start,
				'type'    => 'DATE',
				'compare' => '>=',
			];
		} elseif ( $end !== '' ) {
			// Only end set.
			$meta_query[] = [
				'key'     => $meta_key,
				'value'   => $end,
				'type'    => 'DATE',
				'compare' => '<=',
			];
		}

		$query->set( 'meta_query', $meta_query ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Date range filter requires meta query.
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
	 * Date range filters don't have discrete options.
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
	 * @return string Human-readable date range display.
	 */
	public function getDisplayValue( mixed $value ): string {
		$start = $value['start'] ?? '';
		$end   = $value['end'] ?? '';

		$date_format = get_option( 'date_format', 'Y-m-d' );

		if ( $start !== '' && $end !== '' ) {
			return sprintf(
				'%s - %s',
				date_i18n( $date_format, strtotime( $start ) ),
				date_i18n( $date_format, strtotime( $end ) )
			);
		} elseif ( $start !== '' ) {
			return sprintf(
				/* translators: %s: start date */
				__( 'From %s', 'all-purpose-directory' ),
				date_i18n( $date_format, strtotime( $start ) )
			);
		} elseif ( $end !== '' ) {
			return sprintf(
				/* translators: %s: end date */
				__( 'Until %s', 'all-purpose-directory' ),
				date_i18n( $date_format, strtotime( $end ) )
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

		return ( $value['start'] ?? '' ) !== '' || ( $value['end'] ?? '' ) !== '';
	}

	/**
	 * Get filter value from request.
	 *
	 * Override to handle _start/_end parameters.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $request Request data.
	 * @return array{start: string, end: string} The filter value.
	 */
	public function getValueFromRequest( array $request ): array {
		$start_param = $this->getUrlParamStart();
		$end_param   = $this->getUrlParamEnd();

		return $this->sanitize(
			[
				'start' => $request[ $start_param ] ?? '',
				'end'   => $request[ $end_param ] ?? '',
			]
		);
	}
}
