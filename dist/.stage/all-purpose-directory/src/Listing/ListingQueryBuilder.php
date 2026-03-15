<?php
/**
 * Listing Query Builder.
 *
 * Shared query builder for listing queries used by blocks and shortcodes.
 *
 * @package APD\Listing
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Listing;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ListingQueryBuilder
 *
 * Builds WP_Query arguments from a normalized parameter array.
 * Used by ListingsBlock and ListingsShortcode to ensure consistent
 * query building and sanitization.
 *
 * @since 1.0.0
 */
class ListingQueryBuilder {

	/**
	 * Valid orderby values.
	 *
	 * @var array<string>
	 */
	private const VALID_ORDERBY = [ 'date', 'title', 'modified', 'rand', 'views', 'menu_order', 'ID' ];

	/**
	 * Build WP_Query arguments from parameters.
	 *
	 * @since 1.0.0
	 *
	 * @param array $params {
	 *     Query parameters.
	 *
	 *     @type int          $count    Number of posts per page. Default 12.
	 *     @type int          $paged    Current page number. Default 1.
	 *     @type string|array $ids      Comma-separated or array of post IDs to include.
	 *     @type string|array $exclude  Comma-separated or array of post IDs to exclude.
	 *     @type string       $category Comma-separated category slugs.
	 *     @type string       $tag      Comma-separated tag slugs.
	 *     @type string       $type     Comma-separated listing type slugs.
	 *     @type string       $orderby  Order by field. Default 'date'.
	 *     @type string       $order    Sort order: ASC or DESC. Default 'DESC'.
	 *     @type int|string   $author   Author ID or username.
	 * }
	 * @return array WP_Query arguments.
	 */
	public function build( array $params ): array {
		$args = [
			'post_type'      => 'apd_listing',
			'post_status'    => 'publish',
			'posts_per_page' => absint( $params['count'] ?? 12 ),
			'paged'          => absint( $params['paged'] ?? 1 ),
		];

		$this->apply_post_in( $args, $params );
		$this->apply_post_not_in( $args, $params );
		$this->apply_author( $args, $params );
		$this->apply_taxonomy_filters( $args, $params );
		$this->apply_ordering( $args, $params );

		return $args;
	}

	/**
	 * Apply post__in filter.
	 *
	 * @param array $args   Query args (by reference).
	 * @param array $params Input parameters.
	 * @return void
	 */
	private function apply_post_in( array &$args, array $params ): void {
		if ( empty( $params['ids'] ) ) {
			return;
		}

		$ids = $this->parse_ids( $params['ids'] );
		if ( ! empty( $ids ) ) {
			$args['post__in'] = $ids;
			$args['orderby']  = 'post__in';
		}
	}

	/**
	 * Apply post__not_in filter.
	 *
	 * @param array $args   Query args (by reference).
	 * @param array $params Input parameters.
	 * @return void
	 */
	private function apply_post_not_in( array &$args, array $params ): void {
		if ( empty( $params['exclude'] ) ) {
			return;
		}

		$exclude = $this->parse_ids( $params['exclude'] );
		if ( ! empty( $exclude ) ) {
			$args['post__not_in'] = $exclude; // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- Bounded exclusion list from caller input.
		}
	}

	/**
	 * Apply author filter.
	 *
	 * @param array $args   Query args (by reference).
	 * @param array $params Input parameters.
	 * @return void
	 */
	private function apply_author( array &$args, array $params ): void {
		if ( empty( $params['author'] ) ) {
			return;
		}

		$author = $params['author'];

		if ( is_numeric( $author ) ) {
			$args['author'] = absint( $author );
		} else {
			$user = get_user_by( 'login', sanitize_user( (string) $author ) );
			if ( $user ) {
				$args['author'] = $user->ID;
			}
		}
	}

	/**
	 * Apply taxonomy filters (category, tag, type).
	 *
	 * @param array $args   Query args (by reference).
	 * @param array $params Input parameters.
	 * @return void
	 */
	private function apply_taxonomy_filters( array &$args, array $params ): void {
		$tax_query = [];

		// Category filter.
		if ( ! empty( $params['category'] ) ) {
			$categories  = array_map( 'sanitize_key', explode( ',', (string) $params['category'] ) );
			$tax_query[] = [
				'taxonomy' => 'apd_category',
				'field'    => 'slug',
				'terms'    => $categories,
			];
		}

		// Tag filter.
		if ( ! empty( $params['tag'] ) ) {
			$tags        = array_map( 'sanitize_key', explode( ',', (string) $params['tag'] ) );
			$tax_query[] = [
				'taxonomy' => 'apd_tag',
				'field'    => 'slug',
				'terms'    => $tags,
			];
		}

		// Listing type filter.
		if ( ! empty( $params['type'] ) ) {
			$types       = array_map( 'sanitize_key', explode( ',', (string) $params['type'] ) );
			$tax_query[] = [
				'taxonomy' => \APD\Taxonomy\ListingTypeTaxonomy::TAXONOMY,
				'field'    => 'slug',
				'terms'    => $types,
			];
		}

		if ( ! empty( $tax_query ) ) {
			if ( count( $tax_query ) > 1 ) {
				$tax_query['relation'] = 'AND';
			}
			$args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Taxonomy filtering is core directory functionality.
		}
	}

	/**
	 * Apply ordering parameters.
	 *
	 * @param array $args   Query args (by reference).
	 * @param array $params Input parameters.
	 * @return void
	 */
	private function apply_ordering( array &$args, array $params ): void {
		// Don't override ordering when specific IDs are requested.
		if ( ! empty( $args['post__in'] ) ) {
			return;
		}

		$orderby = sanitize_key( $params['orderby'] ?? 'date' );
		$order   = strtoupper( (string) ( $params['order'] ?? 'DESC' ) ) === 'ASC' ? 'ASC' : 'DESC';

		if ( ! in_array( $orderby, self::VALID_ORDERBY, true ) ) {
			$orderby = 'date';
		}

		if ( $orderby === 'views' ) {
			$args['meta_key'] = '_apd_views_count'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Required for meta-based sorting.
			$args['orderby']  = 'meta_value_num';
		} else {
			$args['orderby'] = $orderby;
		}

		$args['order'] = $order;
	}

	/**
	 * Parse IDs from string or array.
	 *
	 * @param string|array $input Comma-separated string or array of IDs.
	 * @return array<int> Array of positive integers.
	 */
	private function parse_ids( $input ): array {
		if ( is_array( $input ) ) {
			return array_values( array_filter( array_map( 'absint', $input ) ) );
		}

		if ( is_string( $input ) && $input !== '' ) {
			return array_values( array_filter( array_map( 'absint', explode( ',', $input ) ) ) );
		}

		return [];
	}
}
