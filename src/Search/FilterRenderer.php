<?php
/**
 * Filter Renderer.
 *
 * Handles rendering of search forms and filter controls.
 *
 * @package APD\Search
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Search;

use APD\Contracts\FilterInterface;
use APD\Core\Url;
use APD\Core\Template;
use APD\Search\Filters\RangeFilter;
use APD\Search\Filters\DateRangeFilter;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FilterRenderer
 *
 * Renders search forms and filter controls with template support.
 *
 * @since 1.0.0
 */
final class FilterRenderer {

	/**
	 * Filter registry instance.
	 *
	 * @var FilterRegistry
	 */
	private FilterRegistry $registry;

	/**
	 * Search query instance.
	 *
	 * @var SearchQuery
	 */
	private SearchQuery $search_query;

	/**
	 * Resolved form action URL for the current render cycle.
	 *
	 * @var string
	 */
	private string $current_action_url = '';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param FilterRegistry|null $registry     Optional. Filter registry instance.
	 * @param SearchQuery|null    $search_query Optional. Search query instance.
	 */
	public function __construct( ?FilterRegistry $registry = null, ?SearchQuery $search_query = null ) {
		$this->registry     = $registry ?? FilterRegistry::get_instance();
		$this->search_query = $search_query ?? new SearchQuery();
	}

	/**
	 * Render the complete search form.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $args Render arguments.
	 *                                   - 'filters': Array of filter names to include.
	 *                                   - 'exclude': Array of filter names to exclude.
	 *                                   - 'show_orderby': Whether to show orderby dropdown.
	 *                                   - 'show_submit': Whether to show submit button.
	 *                                   - 'action': Form action URL.
	 *                                   - 'method': Form method (get/post).
	 *                                   - 'ajax': Whether to use AJAX.
	 *                                   - 'class': Additional CSS classes.
	 * @return string The rendered form HTML.
	 */
	public function render_search_form( array $args = [] ): string {
		$defaults = [
			'filters'      => [],
			'exclude'      => [], // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- Render config key; unrelated to WP_Query exclusion params.
			'show_orderby' => true,
			'show_submit'  => true,
			'submit_text'  => '',
			'action'       => '',
			'method'       => 'get',
			'ajax'         => true,
			'class'        => '',
			'layout'       => 'horizontal',
		];

		$args = wp_parse_args( $args, $defaults );

		// Default form action to the current page URL (without query string) so the
		// form and "Clear Filters" link stay on the same page (e.g. /directory/).
		if ( empty( $args['action'] ) ) {
			$args['action'] = home_url( wp_parse_url( add_query_arg( [] ), PHP_URL_PATH ) ?: '/' );
		}

		// Store the resolved action URL for use by helper methods (active filters, no-results, etc.).
		$this->current_action_url = $args['action'];

		/**
		 * Fires before the search form is rendered.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, mixed> $args Render arguments.
		 */
		do_action( 'apd_before_search_form', $args );

		// Get filters.
		$filters = $this->registry->get_filters();

		// Filter by name if specified.
		if ( ! empty( $args['filters'] ) ) {
			$filters = array_filter(
				$filters,
				fn( FilterInterface $filter ) => in_array( $filter->getName(), $args['filters'], true )
			);
		}

		// Exclude specified filters.
		if ( ! empty( $args['exclude'] ) ) {
			$filters = array_filter(
				$filters,
				fn( FilterInterface $filter ) => ! in_array( $filter->getName(), $args['exclude'], true )
			);
		}

		// Build CSS classes.
		$classes = [ 'apd-search-form' ];
		if ( $args['ajax'] ) {
			$classes[] = 'apd-search-form--ajax';
		}
		$valid_layouts = [ 'horizontal', 'vertical', 'inline' ];
		$layout        = in_array( $args['layout'], $valid_layouts, true ) ? $args['layout'] : 'horizontal';
		$classes[]     = 'apd-search-form--' . $layout;
		if ( ! empty( $args['class'] ) ) {
			$classes[] = $args['class'];
		}

		/**
		 * Filter the search form CSS classes.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string>        $classes CSS classes.
		 * @param array<string, mixed> $args    Render arguments.
		 */
		$classes = apply_filters( 'apd_search_form_classes', $classes, $args );

		// Get current request data for filter values.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$request = $_GET;

		// Load template or build output.
		$template = $this->locate_template( 'search/search-form.php' );

		if ( $template ) {
			ob_start();
			include $template;
			$output = ob_get_clean();
		} else {
			$output = $this->build_search_form( $args, $filters, $classes, $request );
		}

		/**
		 * Fires after the search form is rendered.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, mixed> $args Render arguments.
		 */
		do_action( 'apd_after_search_form', $args );

		return $output;
	}

	/**
	 * Build the search form HTML.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed>           $args    Render arguments.
	 * @param array<string, FilterInterface> $filters Filters to render.
	 * @param array<string>                  $classes CSS classes.
	 * @param array<string, mixed>           $request Request data for values.
	 * @return string The form HTML.
	 */
	private function build_search_form( array $args, array $filters, array $classes, array $request ): string {
		$action = $args['action'] ?: $this->get_base_url();

		$output = sprintf(
			'<form class="%s" action="%s" method="%s" data-ajax="%s">',
			esc_attr( implode( ' ', $classes ) ),
			esc_url( $action ),
			esc_attr( $args['method'] ),
			$args['ajax'] ? 'true' : 'false'
		);

		/**
		 * Fires before the filters are rendered.
		 *
		 * @since 1.0.0
		 */
		do_action( 'apd_before_filters' );

		$output .= '<div class="apd-search-form__filters">';

		foreach ( $filters as $filter ) {
			$output .= $this->render_filter( $filter->getName(), $request );
		}

		$output .= '</div>';

		/**
		 * Fires after the filters are rendered.
		 *
		 * @since 1.0.0
		 */
		do_action( 'apd_after_filters' );

		// Orderby dropdown.
		if ( $args['show_orderby'] ) {
			$output .= $this->render_orderby( $request );
		}

		// Submit button.
		if ( $args['show_submit'] ) {
			$submit_label = ! empty( $args['submit_text'] )
				? sanitize_text_field( $args['submit_text'] )
				: __( 'Search', 'damdir-directory' );

			$output .= '<div class="apd-search-form__actions">';
			$output .= sprintf(
				'<button type="submit" class="apd-search-form__submit">%s</button>',
				esc_html( $submit_label )
			);
			$output .= sprintf(
				'<a href="%s" class="apd-search-form__clear">%s</a>',
				esc_url( $action ),
				esc_html__( 'Clear Filters', 'damdir-directory' )
			);
			$output .= '</div>';
		}

		$output .= '</form>';

		return $output;
	}

	/**
	 * Render a single filter.
	 *
	 * @since 1.0.0
	 *
	 * @param string                    $name    Filter name.
	 * @param array<string, mixed>|null $request Request data for value.
	 * @return string The rendered filter HTML.
	 */
	public function render_filter( string $name, ?array $request = null ): string {
		$filter = $this->registry->get_filter( $name );

		if ( $filter === null ) {
			return '';
		}

		if ( $request === null ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$request = $_GET;
		}

		// Get filter value from request.
		$value = $this->get_filter_value( $filter, $request );

		// Try template first.
		$template_name = 'search/filter-' . $filter->getType() . '.php';
		$template      = $this->locate_template( $template_name );

		if ( $template ) {
			ob_start();
			include $template;
			$output = ob_get_clean();
		} else {
			$output = $filter->render( $value );
		}

		/**
		 * Filter the rendered filter HTML.
		 *
		 * @since 1.0.0
		 *
		 * @param string          $output  Rendered HTML.
		 * @param FilterInterface $filter  Filter instance.
		 * @param mixed           $value   Current value.
		 * @param array           $request Request data.
		 */
		return apply_filters( 'apd_render_filter', $output, $filter, $value, $request );
	}

	/**
	 * Get filter value from request.
	 *
	 * @since 1.0.0
	 *
	 * @param FilterInterface      $filter  The filter.
	 * @param array<string, mixed> $request Request data.
	 * @return mixed The filter value.
	 */
	private function get_filter_value( FilterInterface $filter, array $request ): mixed {
		// Handle range and date range filters with multiple params.
		if ( $filter instanceof RangeFilter || $filter instanceof DateRangeFilter ) {
			return $filter->getValueFromRequest( $request );
		}

		$param = $filter->getUrlParam();

		if ( ! isset( $request[ $param ] ) ) {
			return null;
		}

		return $filter->sanitize( $request[ $param ] );
	}

	/**
	 * Render the orderby dropdown.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $request Request data.
	 * @return string The rendered HTML.
	 */
	public function render_orderby( array $request ): string {
		$options = $this->search_query->get_orderby_options();
		$current = $request['apd_orderby'] ?? 'date';

		$output  = '<div class="apd-search-form__orderby">';
		$output .= sprintf(
			'<label for="apd-orderby" class="apd-search-form__label">%s</label>',
			esc_html__( 'Sort by', 'damdir-directory' )
		);

		$output .= '<select id="apd-orderby" name="apd_orderby" class="apd-search-form__select">';

		foreach ( $options as $value => $label ) {
			$output .= sprintf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $value ),
				selected( $current, $value, false ),
				esc_html( $label )
			);
		}

		$output .= '</select>';

		$output .= '</div>';

		return $output;
	}

	/**
	 * Render active filter chips.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed>|null $request Request data.
	 * @return string The rendered HTML.
	 */
	public function render_active_filters( ?array $request = null ): string {
		if ( $request === null ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$request = $_GET;
		}

		$active_filters = [];

		// Collect active filters (keyword is included via the registry loop).
		foreach ( $this->registry->get_filters() as $filter ) {
			$value = $this->get_filter_value( $filter, $request );

			if ( $filter->isActive( $value ) ) {
				$active_filters[] = [
					'filter' => $filter,
					'value'  => $value,
				];
			}
		}

		if ( empty( $active_filters ) ) {
			return '';
		}

		// Try template.
		$template = $this->locate_template( 'search/active-filters.php' );

		if ( $template ) {
			ob_start();
			include $template;
			return ob_get_clean();
		}

		return $this->build_active_filters( $active_filters, $request );
	}

	/**
	 * Build active filters HTML.
	 *
	 * @since 1.0.0
	 *
	 * @param array<array{filter: FilterInterface, value: mixed}> $active_filters Active filters.
	 * @param array<string, mixed>                                $request        Request data.
	 * @return string The HTML.
	 */
	private function build_active_filters( array $active_filters, array $request ): string {
		$output  = '<div class="apd-active-filters" aria-live="polite">';
		$output .= sprintf(
			'<span class="apd-active-filters__label">%s</span>',
			esc_html__( 'Active filters:', 'damdir-directory' )
		);

		$output .= '<ul class="apd-active-filters__list">';

		foreach ( $active_filters as $item ) {
			$filter = $item['filter'];
			$value  = $item['value'];

			// Build remove URL.
			$remove_url = $this->build_remove_filter_url( $filter, $request );

			$output .= '<li class="apd-active-filters__item">';
			$output .= sprintf(
				'<span class="apd-active-filters__name">%s:</span>',
				esc_html( $filter->getLabel() )
			);
			$output .= sprintf(
				'<span class="apd-active-filters__value">%s</span>',
				esc_html( $filter->getDisplayValue( $value ) )
			);
			$output .= sprintf(
				'<a href="%s" class="apd-active-filters__remove" aria-label="%s"><span aria-hidden="true">&times;</span></a>',
				esc_url( $remove_url ),
				/* translators: %s: filter label */
				esc_attr( sprintf( __( 'Remove %s filter', 'damdir-directory' ), $filter->getLabel() ) )
			);
			$output .= '</li>';
		}

		$output .= '</ul>';

		// Clear all link.
		$clear_url = $this->get_base_url();
		$output   .= sprintf(
			'<a href="%s" class="apd-active-filters__clear">%s</a>',
			esc_url( $clear_url ),
			esc_html__( 'Clear all', 'damdir-directory' )
		);

		$output .= '</div>';

		return $output;
	}

	/**
	 * Build URL with a filter removed.
	 *
	 * @since 1.0.0
	 *
	 * @param FilterInterface      $filter  The filter to remove.
	 * @param array<string, mixed> $request Current request data.
	 * @return string The URL without the filter.
	 */
	private function build_remove_filter_url( FilterInterface $filter, array $request ): string {
		$params = $request;

		// Remove the filter's parameter(s).
		$param = $filter->getUrlParam();
		unset( $params[ $param ] );

		// Handle range and date range filters.
		if ( $filter instanceof RangeFilter ) {
			unset( $params[ $filter->getUrlParamMin() ] );
			unset( $params[ $filter->getUrlParamMax() ] );
		}
		if ( $filter instanceof DateRangeFilter ) {
			unset( $params[ $filter->getUrlParamStart() ] );
			unset( $params[ $filter->getUrlParamEnd() ] );
		}

		$base_url = $this->get_base_url();

		if ( empty( $params ) ) {
			return $base_url;
		}

		return add_query_arg( Url::encode_deep( $params ), $base_url );
	}

	/**
	 * Render no results message.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $args Render arguments.
	 * @return string The rendered HTML.
	 */
	public function render_no_results( array $args = [] ): string {
		$template = $this->locate_template( 'search/no-results.php' );

		if ( $template ) {
			ob_start();
			include $template;
			return ob_get_clean();
		}

		$output  = '<div class="apd-no-results">';
		$output .= sprintf(
			'<p class="apd-no-results__message">%s</p>',
			esc_html__( 'No listings found matching your criteria.', 'damdir-directory' )
		);
		$output .= sprintf(
			'<a href="%s" class="apd-no-results__clear">%s</a>',
			esc_url( $this->get_base_url() ),
			esc_html__( 'Clear all filters', 'damdir-directory' )
		);
		$output .= '</div>';

		return $output;
	}

	/**
	 * Get the base action URL for the current render cycle.
	 *
	 * Returns the resolved action URL set during render_search_form(),
	 * falling back to the post type archive link if called outside a render cycle.
	 *
	 * @since 1.0.0
	 *
	 * @return string The base URL.
	 */
	private function get_base_url(): string {
		if ( ! empty( $this->current_action_url ) ) {
			return $this->current_action_url;
		}

		return get_post_type_archive_link( 'apd_listing' );
	}

	/**
	 * Locate a template file.
	 *
	 * Delegates to the Template class for consistent template loading.
	 *
	 * @since 1.0.0
	 *
	 * @param string $template_name Template file name.
	 * @return string|false Template path or false if not found.
	 */
	public function locate_template( string $template_name ): string|false {
		return Template::get_instance()->locate_template( $template_name );
	}

	/**
	 * Load a template with variables.
	 *
	 * Delegates to the Template class for consistent template loading.
	 *
	 * @since 1.0.0
	 *
	 * @param string               $template_name Template file name.
	 * @param array<string, mixed> $args          Variables to pass to template.
	 * @return void
	 */
	public function get_template( string $template_name, array $args = [] ): void {
		Template::get_instance()->get_template( $template_name, $args );
	}
}
