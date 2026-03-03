<?php
/**
 * Search Query handler.
 *
 * Handles search and filtering for listing queries by hooking into
 * WP_Query lifecycle to apply filters from URL parameters.
 *
 * @package APD\Search
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Search;

use WP_Query;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SearchQuery
 *
 * Manages search queries and filter application for listings.
 *
 * @since 1.0.0
 */
final class SearchQuery {

	/**
	 * Filter registry instance.
	 *
	 * @var FilterRegistry
	 */
	private FilterRegistry $registry;

	/**
	 * Whether hooks have been initialized.
	 *
	 * @var bool
	 */
	private bool $initialized = false;

	/**
	 * Current query being modified.
	 *
	 * @var WP_Query|null
	 */
	private ?WP_Query $current_query = null;

	/**
	 * Searchable meta keys for keyword search.
	 *
	 * @var array<string>
	 */
	private array $searchable_meta_keys = [];

	/**
	 * Request/query parameters used to build search behavior.
	 *
	 * @var array<string, mixed>
	 */
	private array $request_params = [];

	/**
	 * Valid orderby options.
	 *
	 * @var array<string, string>
	 */
	private const ORDERBY_OPTIONS = [
		'date'   => 'post_date',
		'title'  => 'post_title',
		'views'  => '_apd_views_count',
		'random' => 'rand',
	];

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param FilterRegistry|null       $registry       Optional. Filter registry instance.
	 * @param array<string, mixed>|null $request_params Optional. Request/query params.
	 */
	public function __construct( ?FilterRegistry $registry = null, ?array $request_params = null ) {
		$this->registry       = $registry ?? FilterRegistry::get_instance();
		$this->request_params = is_array( $request_params ) ? $request_params : [];
	}

	/**
	 * Set request/query parameters for this instance.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $request_params Request/query params.
	 * @return self
	 */
	public function set_request_params( array $request_params ): self {
		$this->request_params = $request_params;

		return $this;
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init(): void {
		if ( $this->initialized ) {
			return;
		}

		add_action( 'pre_get_posts', [ $this, 'modify_main_query' ], 10 );
		add_filter( 'posts_join', [ $this, 'add_meta_join' ], 10, 2 );
		add_filter( 'posts_search', [ $this, 'add_meta_search' ], 10, 2 );
		add_filter( 'posts_distinct', [ $this, 'add_distinct' ], 10, 2 );

		$this->initialized = true;
	}

	/**
	 * Modify the main query for listing archives.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Query $query The query to modify.
	 * @return void
	 */
	public function modify_main_query( WP_Query $query ): void {
		// Only modify main query on frontend.
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		// Only modify listing queries.
		if ( ! $this->is_listing_query( $query ) ) {
			return;
		}

		$query->set( 'posts_per_page', \apd_get_listings_per_page() );

		$this->apply_filters( $query );
		$this->apply_orderby( $query );
		$this->apply_keyword_search( $query );
	}

	/**
	 * Apply registered filters to the query.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Query                 $query          The query to modify.
	 * @param array<string,mixed>|null $request_params Request params override (defaults to current request).
	 * @return void
	 */
	public function apply_filters( WP_Query $query, ?array $request_params = null ): void {
		$active_filters = $this->registry->get_active_filters( $this->resolve_request_params( $request_params ) );

		foreach ( $active_filters as $name => $data ) {
			$filter = $data['filter'];
			$value  = $data['value'];

			$filter->modifyQuery( $query, $value );
		}

		/**
		 * Filter the query args after filters are applied.
		 *
		 * @since 1.0.0
		 *
		 * @param WP_Query $query          The modified query.
		 * @param array    $active_filters Active filters with values.
		 */
		do_action( 'apd_search_query_modified', $query, $active_filters );
	}

	/**
	 * Apply orderby parameter.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Query                 $query          The query to modify.
	 * @param array<string,mixed>|null $request_params Request params override (defaults to current request).
	 * @return void
	 */
	public function apply_orderby( WP_Query $query, ?array $request_params = null ): void {
		$request = $this->resolve_request_params( $request_params );
		$orderby = isset( $request['apd_orderby'] ) ? sanitize_key( (string) $request['apd_orderby'] ) : '';
		$order   = isset( $request['apd_order'] ) ? strtoupper( sanitize_key( (string) $request['apd_order'] ) ) : 'DESC';

		if ( ! in_array( $order, [ 'ASC', 'DESC' ], true ) ) {
			$order = 'DESC';
		}

		if ( empty( $orderby ) || ! isset( self::ORDERBY_OPTIONS[ $orderby ] ) ) {
			return;
		}

		$orderby_value = self::ORDERBY_OPTIONS[ $orderby ];

		if ( $orderby === 'views' ) {
			// Order by meta value.
			$query->set( 'meta_key', $orderby_value );
			$query->set( 'orderby', 'meta_value_num' );
		} elseif ( $orderby === 'random' ) {
			$query->set( 'orderby', 'rand' );
		} else {
			$query->set( 'orderby', $orderby_value );
		}

		$query->set( 'order', $order );
	}

	/**
	 * Apply keyword search to title, content, and meta fields.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Query                 $query          The query to modify.
	 * @param array<string,mixed>|null $request_params Request params override (defaults to current request).
	 * @return void
	 */
	public function apply_keyword_search( WP_Query $query, ?array $request_params = null ): void {
		$request = $this->resolve_request_params( $request_params );
		$keyword = isset( $request['apd_keyword'] ) ? sanitize_text_field( wp_unslash( (string) $request['apd_keyword'] ) ) : '';

		if ( empty( $keyword ) ) {
			return;
		}

		// Set the search term for title/content search.
		$query->set( 's', $keyword );

		// Store the query for meta search hooks.
		$this->current_query        = $query;
		$this->searchable_meta_keys = $this->get_searchable_meta_keys();

		// Set a flag for the join/where hooks.
		$query->set( 'apd_meta_search', true );
		$query->set( 'apd_keyword', $keyword );
	}

	/**
	 * Add JOIN clause for meta search.
	 *
	 * No longer adds a LEFT JOIN since we now use an EXISTS subquery.
	 * Kept for backward compatibility with the posts_join filter hook.
	 *
	 * @since 1.0.0
	 *
	 * @param string   $join  The JOIN clause.
	 * @param WP_Query $query The query.
	 * @return string Unmodified JOIN clause.
	 */
	public function add_meta_join( string $join, WP_Query $query ): string {
		return $join;
	}

	/**
	 * Add meta fields to the search clause using an EXISTS subquery.
	 *
	 * Uses an EXISTS subquery instead of LEFT JOIN + DISTINCT to avoid
	 * row multiplication when multiple meta keys match. MySQL short-circuits
	 * on the first matching row, making this more efficient on large tables.
	 *
	 * Uses the `posts_search` filter which receives only the search-specific
	 * SQL clause (e.g. `AND ((conditions))`), making it safe to inject OR
	 * conditions without affecting other WHERE clauses like post_type or
	 * post_status.
	 *
	 * @since 1.0.0
	 *
	 * @param string   $search The search SQL clause.
	 * @param WP_Query $query  The query.
	 * @return string Modified search clause.
	 */
	public function add_meta_search( string $search, WP_Query $query ): string {
		if ( ! $query->get( 'apd_meta_search' ) ) {
			return $search;
		}

		if ( empty( $this->searchable_meta_keys ) || empty( $search ) ) {
			return $search;
		}

		global $wpdb;

		$keyword = $query->get( 'apd_keyword' );

		if ( empty( $keyword ) ) {
			return $search;
		}

		// Prepare meta key conditions.
		$meta_key_placeholders = implode( ',', array_fill( 0, count( $this->searchable_meta_keys ), '%s' ) );
		$like_keyword          = '%' . $wpdb->esc_like( $keyword ) . '%';

		// Build EXISTS subquery: avoids LEFT JOIN row multiplication.
		// $meta_key_placeholders is a string of %s placeholders generated above.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $meta_key_placeholders contains safe %s placeholders.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $wpdb table names are trusted core globals and $meta_key_placeholders contains only generated %s placeholders.
		$meta_condition = $wpdb->prepare(
			"EXISTS (SELECT 1 FROM {$wpdb->postmeta} AS apd_pm WHERE apd_pm.post_id = {$wpdb->posts}.ID AND apd_pm.meta_key IN ($meta_key_placeholders) AND apd_pm.meta_value LIKE %s)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Dynamic placeholders string is generated from safe `%s` tokens only.
			array_merge( $this->searchable_meta_keys, [ $like_keyword ] )
		);

		// WordPress search clause ends with )). Insert OR before the final
		// closing parens so meta search is OR'd with title/content/excerpt
		// within the search group, preserving other WHERE conditions.
		$last_parens_pos = strrpos( $search, '))' );
		if ( false !== $last_parens_pos ) {
			$search = substr( $search, 0, $last_parens_pos )
				. " OR $meta_condition)"
				. substr( $search, $last_parens_pos + 1 );
		}

		return $search;
	}

	/**
	 * Add DISTINCT to prevent duplicate results.
	 *
	 * No longer needed since EXISTS subquery doesn't produce duplicates.
	 * Kept for backward compatibility with the posts_distinct filter hook.
	 *
	 * @since 1.0.0
	 *
	 * @param string   $distinct The DISTINCT clause.
	 * @param WP_Query $query    The query.
	 * @return string Unmodified DISTINCT clause.
	 */
	public function add_distinct( string $distinct, WP_Query $query ): string {
		return $distinct;
	}

	/**
	 * Check if this is a listing query.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Query $query The query to check.
	 * @return bool True if this is a listing query.
	 */
	private function is_listing_query( WP_Query $query ): bool {
		$post_type = $query->get( 'post_type' );

		// Check if querying apd_listing post type.
		if ( $post_type === 'apd_listing' ) {
			return true;
		}

		// Check if on listing archive or taxonomy archive.
		if ( $query->is_post_type_archive( 'apd_listing' ) ) {
			return true;
		}

		if ( $query->is_tax( 'apd_category' ) || $query->is_tax( 'apd_tag' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get searchable meta keys from field registry.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string> Meta keys for searchable fields.
	 */
	private function get_searchable_meta_keys(): array {
		if ( ! function_exists( 'apd_field_registry' ) ) {
			return [];
		}

		$field_registry    = \apd_field_registry();
		$searchable_fields = $field_registry->get_searchable_fields();

		$meta_keys = [];
		foreach ( $searchable_fields as $field_name => $field ) {
			// Sanitize meta keys to prevent SQL injection.
			$meta_keys[] = sanitize_key( $field_registry->get_meta_key( $field_name ) );
		}

		/**
		 * Filter the searchable meta keys.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string> $meta_keys Searchable meta keys.
		 */
		$filtered_keys = apply_filters( 'apd_searchable_meta_keys', $meta_keys );

		// Sanitize any keys added via the filter to prevent SQL injection.
		return array_map( 'sanitize_key', $filtered_keys );
	}

	/**
	 * Get filtered listings.
	 *
	 * Convenience method to run a filtered query.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed>     $args           Additional query args.
	 * @param array<string,mixed>|null $request_params Request params override (defaults to current request).
	 * @return WP_Query The query result.
	 */
	public function get_filtered_listings( array $args = [], ?array $request_params = null ): WP_Query {
		$defaults = [
			'post_type'      => 'apd_listing',
			'post_status'    => 'publish',
			'posts_per_page' => \apd_get_listings_per_page(),
		];

		$query_args = wp_parse_args( $args, $defaults );

		/**
		 * Filter the query args before running filtered query.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, mixed>      $query_args     Query arguments.
		 * @param array<string, mixed>|null $request_params Submitted request/query params.
		 */
		$query_args       = apply_filters( 'apd_search_query_args', $query_args, $request_params );
		$final_query_args = $this->build_filtered_query_args( $query_args, $request_params );

		return new WP_Query( $final_query_args );
	}

	/**
	 * Build final filtered query args before running WP_Query.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed>      $query_args     Base query arguments.
	 * @param array<string, mixed>|null $request_params Optional request/query params.
	 * @return array<string, mixed> Final query arguments.
	 */
	private function build_filtered_query_args( array $query_args, ?array $request_params = null ): array {
		$query   = $this->create_query_arg_collector( $query_args );
		$request = $this->resolve_request_params( $request_params );

		$this->apply_keyword_search( $query, $request );
		$this->apply_filters( $query, $request );
		$this->apply_orderby( $query, $request );

		return $this->get_query_vars( $query, $query_args );
	}

	/**
	 * Create a query object used for collecting query var mutations.
	 *
	 * Returns a lightweight WP_Query subclass that only supports set()/get().
	 * If filter implementations start calling other WP_Query methods (e.g.
	 * is_main_query(), have_posts()), this collector will need to be replaced
	 * with a real WP_Query or a dedicated value object.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $query_args Initial query vars.
	 * @return WP_Query Query collector.
	 */
	private function create_query_arg_collector( array $query_args ): WP_Query {
		return new class( $query_args ) extends WP_Query {
			/**
			 * Constructor.
			 *
			 * @param array<string, mixed> $args Initial query vars.
			 */
			public function __construct( $args = [] ) {
				$this->query_vars = $args;
			}

			/**
			 * Set a query variable.
			 *
			 * @param string $query_var Query var key.
			 * @param mixed  $value     Query var value.
			 * @return void
			 */
			public function set( $query_var, $value ) {
				$this->query_vars[ $query_var ] = $value;
			}

			/**
			 * Get a query variable.
			 *
			 * @param string $query_var Query var key.
			 * @param mixed  $default   Optional default value.
			 * @return mixed Query var value.
			 */
			public function get( $query_var, $default = '' ) {
				return $this->query_vars[ $query_var ] ?? $default;
			}
		};
	}

	/**
	 * Get query vars from a query object with fallback.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Query             $query    Query object.
	 * @param array<string, mixed> $fallback Fallback args.
	 * @return array<string, mixed> Query vars.
	 */
	private function get_query_vars( WP_Query $query, array $fallback ): array {
		if ( isset( $query->query_vars ) && is_array( $query->query_vars ) ) {
			return $query->query_vars;
		}

		return $fallback;
	}

	/**
	 * Get available orderby options.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string> Orderby options with labels.
	 */
	public function get_orderby_options(): array {
		$options = [
			'date'   => __( 'Newest First', 'all-purpose-directory' ),
			'title'  => __( 'Title A-Z', 'all-purpose-directory' ),
			'views'  => __( 'Most Viewed', 'all-purpose-directory' ),
			'random' => __( 'Random', 'all-purpose-directory' ),
		];

		/**
		 * Filter the orderby options.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, string> $options Orderby options.
		 */
		return apply_filters( 'apd_orderby_options', $options );
	}

	/**
	 * Get current orderby value.
	 *
	 * @since 1.0.0
	 *
	 * @return string Current orderby value.
	 */
	public function get_current_orderby(): string {
		$orderby = isset( $this->request_params['apd_orderby'] ) ? sanitize_key( (string) $this->request_params['apd_orderby'] ) : 'date';

		if ( ! isset( self::ORDERBY_OPTIONS[ $orderby ] ) ) {
			return 'date';
		}

		return $orderby;
	}

	/**
	 * Get current order direction.
	 *
	 * @since 1.0.0
	 *
	 * @return string Current order direction (ASC or DESC).
	 */
	public function get_current_order(): string {
		$order = isset( $this->request_params['apd_order'] ) ? strtoupper( sanitize_key( (string) $this->request_params['apd_order'] ) ) : 'DESC';

		return in_array( $order, [ 'ASC', 'DESC' ], true ) ? $order : 'DESC';
	}

	/**
	 * Get current keyword search term.
	 *
	 * @since 1.0.0
	 *
	 * @return string Current search keyword.
	 */
	public function get_current_keyword(): string {
		return isset( $this->request_params['apd_keyword'] ) ? sanitize_text_field( wp_unslash( (string) $this->request_params['apd_keyword'] ) ) : '';
	}

	/**
	 * Resolve request/query params for a call.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed>|null $request_params Optional request/query params.
	 * @return array<string, mixed>
	 */
	private function resolve_request_params( ?array $request_params = null ): array {
		return is_array( $request_params ) ? $request_params : $this->request_params;
	}
}
