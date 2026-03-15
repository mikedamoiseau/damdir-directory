<?php
/**
 * Favorites REST API Endpoint.
 *
 * Provides REST API endpoints for managing user favorites.
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

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FavoritesEndpoint
 *
 * Handles REST API endpoints for favorites operations.
 *
 * @since 1.0.0
 */
class FavoritesEndpoint {

	/**
	 * REST controller instance.
	 *
	 * @var RestController
	 */
	private RestController $controller;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param RestController $controller REST controller instance.
	 */
	public function __construct( RestController $controller ) {
		$this->controller = $controller;
	}

	/**
	 * Register routes.
	 *
	 * @since 1.0.0
	 */
	public function register_routes(): void {
		$namespace = $this->controller->get_namespace();

		// GET /favorites - Get current user's favorite IDs.
		register_rest_route(
			$namespace,
			'/favorites',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_favorites' ],
					'permission_callback' => [ $this->controller, 'permission_authenticated' ],
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'add_favorite' ],
					'permission_callback' => [ $this->controller, 'permission_authenticated_with_nonce' ],
					'args'                => $this->get_add_params(),
				],
				'schema' => [ $this, 'get_favorites_schema' ],
			]
		);

		// GET /favorites/listings - Get current user's favorite listings with full data.
		register_rest_route(
			$namespace,
			'/favorites/listings',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_favorite_listings' ],
				'permission_callback' => [ $this->controller, 'permission_authenticated' ],
				'args'                => $this->get_listings_params(),
				'schema'              => [ $this, 'get_listing_schema' ],
			]
		);

		// DELETE /favorites/{id} - Remove a listing from favorites.
		register_rest_route(
			$namespace,
			'/favorites/(?P<id>[\d]+)',
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'remove_favorite' ],
				'permission_callback' => [ $this->controller, 'permission_authenticated_with_nonce' ],
				'args'                => [
					'id' => [
						'description' => __( 'Listing ID to remove from favorites.', 'all-purpose-directory' ),
						'type'        => 'integer',
						'required'    => true,
						'minimum'     => 1,
					],
				],
			]
		);

		// POST /favorites/toggle/{id} - Toggle a listing favorite.
		register_rest_route(
			$namespace,
			'/favorites/toggle/(?P<id>[\d]+)',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'toggle_favorite' ],
				'permission_callback' => [ $this->controller, 'permission_authenticated_with_nonce' ],
				'args'                => [
					'id' => [
						'description' => __( 'Listing ID to toggle favorite.', 'all-purpose-directory' ),
						'type'        => 'integer',
						'required'    => true,
						'minimum'     => 1,
					],
				],
			]
		);
	}

	/**
	 * Get parameters for add favorite request.
	 *
	 * @since 1.0.0
	 *
	 * @return array Parameters schema.
	 */
	public function get_add_params(): array {
		return [
			'listing_id' => [
				'description' => __( 'Listing ID to add to favorites.', 'all-purpose-directory' ),
				'type'        => 'integer',
				'required'    => true,
				'minimum'     => 1,
			],
		];
	}

	/**
	 * Get parameters for favorite listings request.
	 *
	 * @since 1.0.0
	 *
	 * @return array Parameters schema.
	 */
	public function get_listings_params(): array {
		return [
			'page'     => [
				'description' => __( 'Current page of the collection.', 'all-purpose-directory' ),
				'type'        => 'integer',
				'default'     => 1,
				'minimum'     => 1,
			],
			'per_page' => [
				'description' => __( 'Maximum number of items per page.', 'all-purpose-directory' ),
				'type'        => 'integer',
				'default'     => 10,
				'minimum'     => 1,
				'maximum'     => 100,
			],
		];
	}

	/**
	 * Get favorites schema.
	 *
	 * @since 1.0.0
	 *
	 * @return array Schema.
	 */
	public function get_favorites_schema(): array {
		return [
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'favorites',
			'type'       => 'object',
			'properties' => [
				'favorites' => [
					'description' => __( 'Array of favorite listing IDs.', 'all-purpose-directory' ),
					'type'        => 'array',
					'items'       => [
						'type' => 'integer',
					],
				],
				'count'     => [
					'description' => __( 'Total number of favorites.', 'all-purpose-directory' ),
					'type'        => 'integer',
				],
			],
		];
	}

	/**
	 * Get listing schema for favorite listings.
	 *
	 * @since 1.0.0
	 *
	 * @return array Schema.
	 */
	public function get_listing_schema(): array {
		return [
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'favorite_listing',
			'type'       => 'object',
			'properties' => [
				'id'      => [
					'description' => __( 'Unique identifier for the listing.', 'all-purpose-directory' ),
					'type'        => 'integer',
				],
				'title'   => [
					'description' => __( 'Listing title.', 'all-purpose-directory' ),
					'type'        => 'string',
				],
				'slug'    => [
					'description' => __( 'Listing slug.', 'all-purpose-directory' ),
					'type'        => 'string',
				],
				'excerpt' => [
					'description' => __( 'Listing excerpt.', 'all-purpose-directory' ),
					'type'        => 'string',
				],
				'link'    => [
					'description' => __( 'URL to the listing.', 'all-purpose-directory' ),
					'type'        => 'string',
					'format'      => 'uri',
				],
			],
		];
	}

	/**
	 * Get current user's favorites.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function get_favorites( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$favorites = apd_get_user_favorites();
		$count     = count( $favorites );

		$data = [
			'favorites' => array_values( $favorites ),
			'count'     => $count,
		];

		return $this->controller->create_response( $data );
	}

	/**
	 * Get current user's favorite listings with full data.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function get_favorite_listings( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$page     = (int) ( $request->get_param( 'page' ) ?? 1 );
		$per_page = (int) ( $request->get_param( 'per_page' ) ?? 10 );

		$favorites = apd_get_user_favorites();

		if ( empty( $favorites ) ) {
			return $this->controller->create_paginated_response( [], 0, $page, $per_page );
		}

		// Paginate the favorites.
		$total  = count( $favorites );
		$offset = ( $page - 1 ) * $per_page;
		$ids    = array_slice( $favorites, $offset, $per_page );

		if ( empty( $ids ) ) {
			return $this->controller->create_paginated_response( [], $total, $page, $per_page );
		}

		// Get the listings.
		$posts = get_posts(
			[
				'post_type'      => 'apd_listing',
				'post__in'       => $ids,
				'orderby'        => 'post__in',
				'posts_per_page' => $per_page,
				'post_status'    => 'publish',
				'no_found_rows'  => true, // Performance: we already know total from favorites array.
			]
		);

		$items = [];
		foreach ( $posts as $post ) {
			$items[] = $this->prepare_listing_for_response( $post );
		}

		return $this->controller->create_paginated_response( $items, $total, $page, $per_page );
	}

	/**
	 * Add a listing to favorites.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function add_favorite( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$listing_id = (int) $request->get_param( 'listing_id' );

		// Verify the listing exists.
		$post = get_post( $listing_id );

		if ( ! $post || 'apd_listing' !== $post->post_type ) {
			return $this->controller->create_error(
				'rest_listing_not_found',
				__( 'Listing not found.', 'all-purpose-directory' ),
				404
			);
		}

		// Check if already a favorite.
		if ( apd_is_favorite( $listing_id ) ) {
			return $this->controller->create_response(
				[
					'success'     => true,
					'message'     => __( 'Listing is already in favorites.', 'all-purpose-directory' ),
					'listing_id'  => $listing_id,
					'is_favorite' => true,
				]
			);
		}

		// Add to favorites.
		$result = apd_add_favorite( $listing_id );

		if ( ! $result ) {
			return $this->controller->create_error(
				'rest_favorite_failed',
				__( 'Failed to add listing to favorites.', 'all-purpose-directory' ),
				500
			);
		}

		return $this->controller->create_response(
			[
				'success'     => true,
				'message'     => __( 'Listing added to favorites.', 'all-purpose-directory' ),
				'listing_id'  => $listing_id,
				'is_favorite' => true,
			],
			201
		);
	}

	/**
	 * Remove a listing from favorites.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function remove_favorite( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$listing_id = (int) $request->get_param( 'id' );

		// Check if it's a favorite.
		if ( ! apd_is_favorite( $listing_id ) ) {
			return $this->controller->create_error(
				'rest_favorite_not_found',
				__( 'Listing is not in favorites.', 'all-purpose-directory' ),
				404
			);
		}

		// Remove from favorites.
		$result = apd_remove_favorite( $listing_id );

		if ( ! $result ) {
			return $this->controller->create_error(
				'rest_favorite_remove_failed',
				__( 'Failed to remove listing from favorites.', 'all-purpose-directory' ),
				500
			);
		}

		return $this->controller->create_response(
			[
				'success'     => true,
				'message'     => __( 'Listing removed from favorites.', 'all-purpose-directory' ),
				'listing_id'  => $listing_id,
				'is_favorite' => false,
			]
		);
	}

	/**
	 * Toggle a listing's favorite status.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function toggle_favorite( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$listing_id = (int) $request->get_param( 'id' );

		// Verify the listing exists.
		$post = get_post( $listing_id );

		if ( ! $post || 'apd_listing' !== $post->post_type ) {
			return $this->controller->create_error(
				'rest_listing_not_found',
				__( 'Listing not found.', 'all-purpose-directory' ),
				404
			);
		}

		// Toggle the favorite.
		$is_favorite = apd_toggle_favorite( $listing_id );

		if ( $is_favorite === null ) {
			return $this->controller->create_error(
				'rest_favorite_toggle_failed',
				__( 'Failed to toggle favorite status.', 'all-purpose-directory' ),
				500
			);
		}

		$message = $is_favorite
			? __( 'Listing added to favorites.', 'all-purpose-directory' )
			: __( 'Listing removed from favorites.', 'all-purpose-directory' );

		return $this->controller->create_response(
			[
				'success'     => true,
				'message'     => $message,
				'listing_id'  => $listing_id,
				'is_favorite' => $is_favorite,
			]
		);
	}

	/**
	 * Prepare a listing for response.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_Post $post Post object.
	 * @return array Prepared listing data.
	 */
	public function prepare_listing_for_response( \WP_Post $post ): array {
		$data = [
			'id'      => $post->ID,
			'title'   => $post->post_title,
			'slug'    => $post->post_name,
			'excerpt' => $post->post_excerpt ?: wp_trim_words( $post->post_content, 25, '...' ),
			'link'    => get_permalink( $post ),
			'status'  => $post->post_status,
		];

		// Get thumbnail if available.
		$thumbnail_id = get_post_thumbnail_id( $post->ID );
		if ( $thumbnail_id ) {
			$thumbnail_url = wp_get_attachment_image_url( (int) $thumbnail_id, 'medium' );
			if ( $thumbnail_url ) {
				$data['thumbnail'] = $thumbnail_url;
			}
		}

		/**
		 * Filters the favorite listing data for REST API response.
		 *
		 * @since 1.0.0
		 *
		 * @param array    $data Listing data.
		 * @param \WP_Post $post Post object.
		 */
		return apply_filters( 'apd_rest_favorite_listing_data', $data, $post );
	}
}
