<?php
/**
 * Taxonomies REST API Endpoint.
 *
 * Handles retrieval of categories and tags via REST API.
 *
 * @package APD\Api\Endpoints
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Api\Endpoints;

use APD\Api\RestController;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Term;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TaxonomiesEndpoint
 *
 * REST API endpoint controller for categories and tags.
 *
 * @since 1.0.0
 */
class TaxonomiesEndpoint {

	/**
	 * REST controller instance.
	 *
	 * @var RestController
	 */
	private RestController $controller;

	/**
	 * Constructor.
	 *
	 * @param RestController $controller REST controller instance.
	 */
	public function __construct( RestController $controller ) {
		$this->controller = $controller;
	}

	/**
	 * Register routes for this endpoint.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_routes(): void {
		$namespace = $this->controller->get_namespace();

		// GET /categories - List categories.
		register_rest_route(
			$namespace,
			'/categories',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_categories' ],
					'permission_callback' => [ $this->controller, 'permission_public' ],
					'args'                => $this->get_taxonomy_params(),
				],
				'schema' => [ $this, 'get_category_schema' ],
			]
		);

		// GET /categories/{id} - Get single category.
		register_rest_route(
			$namespace,
			'/categories/(?P<id>[\d]+)',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_category' ],
					'permission_callback' => [ $this->controller, 'permission_public' ],
					'args'                => [
						'id' => [
							'description' => __( 'Unique identifier for the category.', 'all-purpose-directory' ),
							'type'        => 'integer',
							'required'    => true,
						],
					],
				],
				'schema' => [ $this, 'get_category_schema' ],
			]
		);

		// GET /tags - List tags.
		register_rest_route(
			$namespace,
			'/tags',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_tags' ],
					'permission_callback' => [ $this->controller, 'permission_public' ],
					'args'                => $this->get_taxonomy_params(),
				],
				'schema' => [ $this, 'get_tag_schema' ],
			]
		);

		// GET /tags/{id} - Get single tag.
		register_rest_route(
			$namespace,
			'/tags/(?P<id>[\d]+)',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_tag' ],
					'permission_callback' => [ $this->controller, 'permission_public' ],
					'args'                => [
						'id' => [
							'description' => __( 'Unique identifier for the tag.', 'all-purpose-directory' ),
							'type'        => 'integer',
							'required'    => true,
						],
					],
				],
				'schema' => [ $this, 'get_tag_schema' ],
			]
		);
	}

	/**
	 * Get collection of categories.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function get_categories( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		return $this->get_terms( 'apd_category', $request, true );
	}

	/**
	 * Get a single category.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function get_category( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		return $this->get_term( 'apd_category', $request, true );
	}

	/**
	 * Get collection of tags.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function get_tags( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		return $this->get_terms( 'apd_tag', $request, false );
	}

	/**
	 * Get a single tag.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function get_tag( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		return $this->get_term( 'apd_tag', $request, false );
	}

	/**
	 * Get terms for a taxonomy.
	 *
	 * @since 1.0.0
	 *
	 * @param string          $taxonomy      Taxonomy name.
	 * @param WP_REST_Request $request       Full data about the request.
	 * @param bool            $is_category   Whether this is a category (for meta).
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	private function get_terms( string $taxonomy, WP_REST_Request $request, bool $is_category ): WP_REST_Response|WP_Error {
		$args = [
			'taxonomy'   => $taxonomy,
			'hide_empty' => (bool) ( $request->get_param( 'hide_empty' ) ?? true ),
			'orderby'    => $request->get_param( 'orderby' ) ?? 'name',
			'order'      => $request->get_param( 'order' ) ?? 'ASC',
		];

		// Parent filter (for hierarchical taxonomies).
		$parent = $request->get_param( 'parent' );
		if ( $parent !== null ) {
			$args['parent'] = absint( $parent );
		}

		// Search.
		$search = $request->get_param( 'search' );
		if ( ! empty( $search ) ) {
			$args['search'] = $search;
		}

		// Pagination.
		$per_page = $request->get_param( 'per_page' ) ?? 100;
		$page     = $request->get_param( 'page' ) ?? 1;

		if ( $per_page > 0 ) {
			$args['number'] = $per_page;
			$args['offset'] = ( $page - 1 ) * $per_page;
		}

		// Include/exclude.
		$include = $request->get_param( 'include' );
		if ( ! empty( $include ) && is_array( $include ) ) {
			$args['include'] = array_map( 'absint', $include );
		}

		$exclude = $request->get_param( 'exclude' );
		if ( ! empty( $exclude ) && is_array( $exclude ) ) {
			$args['exclude'] = array_map( 'absint', $exclude ); // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- REST get_terms() supports explicit term exclusion filters.
		}

		/**
		 * Filters the taxonomy query args for REST API.
		 *
		 * @since 1.0.0
		 *
		 * @param array           $args     Query arguments.
		 * @param string          $taxonomy Taxonomy name.
		 * @param WP_REST_Request $request  The REST request.
		 */
		$args = apply_filters( 'apd_rest_taxonomy_query_args', $args, $taxonomy, $request );

		$terms = get_terms( $args );

		if ( is_wp_error( $terms ) ) {
			return $this->controller->create_error(
				'rest_taxonomy_query_failed',
				$terms->get_error_message(),
				500
			);
		}

		// Get total count for pagination.
		$count_args = $args;
		unset( $count_args['number'], $count_args['offset'] );
		$count_args['count'] = true;
		$total               = (int) get_terms( $count_args );

		$items = [];
		foreach ( $terms as $term ) {
			$items[] = $this->prepare_term_for_response( $term, $is_category );
		}

		return $this->controller->create_paginated_response(
			$items,
			$total,
			(int) $page,
			(int) $per_page
		);
	}

	/**
	 * Get a single term.
	 *
	 * @since 1.0.0
	 *
	 * @param string          $taxonomy    Taxonomy name.
	 * @param WP_REST_Request $request     Full data about the request.
	 * @param bool            $is_category Whether this is a category (for meta).
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	private function get_term( string $taxonomy, WP_REST_Request $request, bool $is_category ): WP_REST_Response|WP_Error {
		$term_id = (int) $request->get_param( 'id' );
		$term    = get_term( $term_id, $taxonomy );

		if ( is_wp_error( $term ) || ! $term ) {
			$type = $is_category ? 'category' : 'tag';
			return $this->controller->create_error(
				"rest_{$type}_not_found",
				sprintf(
					/* translators: %s: term type (category or tag) */
					__( '%s not found.', 'all-purpose-directory' ),
					ucfirst( $type )
				),
				404
			);
		}

		$data = $this->prepare_term_for_response( $term, $is_category );

		return $this->controller->create_response( $data );
	}

	/**
	 * Prepare a term for response.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Term $term        Term object.
	 * @param bool    $is_category Whether this is a category (for meta).
	 * @return array Prepared term data.
	 */
	public function prepare_term_for_response( WP_Term $term, bool $is_category = true ): array {
		$data = [
			'id'          => $term->term_id,
			'name'        => $term->name,
			'slug'        => $term->slug,
			'description' => $term->description,
			'parent'      => $term->parent,
			'count'       => $term->count,
			'link'        => get_term_link( $term ),
		];

		// Add category-specific meta.
		if ( $is_category ) {
			if ( function_exists( 'apd_get_category_icon' ) ) {
				$data['icon'] = apd_get_category_icon( $term );
			}
			if ( function_exists( 'apd_get_category_color' ) ) {
				$data['color'] = apd_get_category_color( $term );
			}
		}

		/**
		 * Filters the term data for REST API response.
		 *
		 * @since 1.0.0
		 *
		 * @param array   $data        Term data.
		 * @param WP_Term $term        Term object.
		 * @param bool    $is_category Whether this is a category.
		 */
		return apply_filters( 'apd_rest_term_data', $data, $term, $is_category );
	}

	/**
	 * Get taxonomy query parameters.
	 *
	 * @since 1.0.0
	 *
	 * @return array Query parameters.
	 */
	public function get_taxonomy_params(): array {
		return [
			'page'       => [
				'description' => __( 'Current page of the collection.', 'all-purpose-directory' ),
				'type'        => 'integer',
				'default'     => 1,
				'minimum'     => 1,
			],
			'per_page'   => [
				'description' => __( 'Maximum number of items per page.', 'all-purpose-directory' ),
				'type'        => 'integer',
				'default'     => 100,
				'minimum'     => 1,
				'maximum'     => 100,
			],
			'search'     => [
				'description' => __( 'Search term.', 'all-purpose-directory' ),
				'type'        => 'string',
			],
			'parent'     => [
				'description' => __( 'Filter by parent term ID (0 for top-level).', 'all-purpose-directory' ),
				'type'        => 'integer',
			],
			'hide_empty' => [
				'description' => __( 'Hide terms with no posts.', 'all-purpose-directory' ),
				'type'        => 'boolean',
				'default'     => true,
			],
			'include'    => [
				'description' => __( 'Limit to specific term IDs.', 'all-purpose-directory' ),
				'type'        => 'array',
				'items'       => [ 'type' => 'integer' ],
			],
			'exclude'    => [ // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- REST schema parameter name, not a query arg.
				'description' => __( 'Exclude specific term IDs.', 'all-purpose-directory' ),
				'type'        => 'array',
				'items'       => [ 'type' => 'integer' ],
			],
			'orderby'    => [
				'description' => __( 'Sort collection by field.', 'all-purpose-directory' ),
				'type'        => 'string',
				'default'     => 'name',
				'enum'        => [ 'name', 'slug', 'count', 'id', 'term_group' ],
			],
			'order'      => [
				'description' => __( 'Sort order.', 'all-purpose-directory' ),
				'type'        => 'string',
				'default'     => 'ASC',
				'enum'        => [ 'ASC', 'DESC' ],
			],
		];
	}

	/**
	 * Get the category schema.
	 *
	 * @since 1.0.0
	 *
	 * @return array Schema definition.
	 */
	public function get_category_schema(): array {
		return [
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'category',
			'type'       => 'object',
			'properties' => [
				'id'          => [
					'description' => __( 'Unique identifier for the category.', 'all-purpose-directory' ),
					'type'        => 'integer',
					'readonly'    => true,
				],
				'name'        => [
					'description' => __( 'Category name.', 'all-purpose-directory' ),
					'type'        => 'string',
				],
				'slug'        => [
					'description' => __( 'Category slug.', 'all-purpose-directory' ),
					'type'        => 'string',
				],
				'description' => [
					'description' => __( 'Category description.', 'all-purpose-directory' ),
					'type'        => 'string',
				],
				'parent'      => [
					'description' => __( 'Parent category ID.', 'all-purpose-directory' ),
					'type'        => 'integer',
				],
				'count'       => [
					'description' => __( 'Number of posts in this category.', 'all-purpose-directory' ),
					'type'        => 'integer',
					'readonly'    => true,
				],
				'link'        => [
					'description' => __( 'Category URL.', 'all-purpose-directory' ),
					'type'        => 'string',
					'format'      => 'uri',
					'readonly'    => true,
				],
				'icon'        => [
					'description' => __( 'Category icon class.', 'all-purpose-directory' ),
					'type'        => 'string',
				],
				'color'       => [
					'description' => __( 'Category color (hex).', 'all-purpose-directory' ),
					'type'        => 'string',
				],
			],
		];
	}

	/**
	 * Get the tag schema.
	 *
	 * @since 1.0.0
	 *
	 * @return array Schema definition.
	 */
	public function get_tag_schema(): array {
		return [
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'tag',
			'type'       => 'object',
			'properties' => [
				'id'          => [
					'description' => __( 'Unique identifier for the tag.', 'all-purpose-directory' ),
					'type'        => 'integer',
					'readonly'    => true,
				],
				'name'        => [
					'description' => __( 'Tag name.', 'all-purpose-directory' ),
					'type'        => 'string',
				],
				'slug'        => [
					'description' => __( 'Tag slug.', 'all-purpose-directory' ),
					'type'        => 'string',
				],
				'description' => [
					'description' => __( 'Tag description.', 'all-purpose-directory' ),
					'type'        => 'string',
				],
				'count'       => [
					'description' => __( 'Number of posts with this tag.', 'all-purpose-directory' ),
					'type'        => 'integer',
					'readonly'    => true,
				],
				'link'        => [
					'description' => __( 'Tag URL.', 'all-purpose-directory' ),
					'type'        => 'string',
					'format'      => 'uri',
					'readonly'    => true,
				],
			],
		];
	}
}
