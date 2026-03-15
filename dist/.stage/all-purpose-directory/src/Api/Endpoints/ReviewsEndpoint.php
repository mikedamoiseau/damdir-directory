<?php
/**
 * Reviews REST API Endpoint.
 *
 * Provides REST API endpoints for managing listing reviews.
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
 * Class ReviewsEndpoint
 *
 * Handles REST API endpoints for reviews operations.
 *
 * @since 1.0.0
 */
class ReviewsEndpoint {

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

		// GET /reviews - Get reviews collection.
		// POST /reviews - Create a review.
		register_rest_route(
			$namespace,
			'/reviews',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_reviews' ],
					'permission_callback' => [ $this->controller, 'permission_public' ],
					'args'                => $this->get_collection_params(),
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'create_review' ],
					'permission_callback' => [ $this->controller, 'permission_authenticated_with_nonce' ],
					'args'                => $this->get_create_params(),
				],
				'schema' => [ $this, 'get_review_schema' ],
			]
		);

		// GET /reviews/{id} - Get a single review.
		// PUT /reviews/{id} - Update a review.
		// DELETE /reviews/{id} - Delete a review.
		register_rest_route(
			$namespace,
			'/reviews/(?P<id>[\d]+)',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_review' ],
					'permission_callback' => [ $this->controller, 'permission_public' ],
					'args'                => [
						'id' => [
							'description' => __( 'Review ID.', 'all-purpose-directory' ),
							'type'        => 'integer',
							'required'    => true,
							'minimum'     => 1,
						],
					],
				],
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'update_review' ],
					'permission_callback' => [ $this, 'permission_edit_review' ],
					'args'                => $this->get_update_params(),
				],
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'delete_review' ],
					'permission_callback' => [ $this, 'permission_delete_review' ],
					'args'                => [
						'id'    => [
							'description' => __( 'Review ID.', 'all-purpose-directory' ),
							'type'        => 'integer',
							'required'    => true,
							'minimum'     => 1,
						],
						'force' => [
							'description' => __( 'Whether to permanently delete.', 'all-purpose-directory' ),
							'type'        => 'boolean',
							'default'     => false,
						],
					],
				],
				'schema' => [ $this, 'get_review_schema' ],
			]
		);

		// GET /listings/{id}/reviews - Get reviews for a specific listing.
		register_rest_route(
			$namespace,
			'/listings/(?P<listing_id>[\d]+)/reviews',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_listing_reviews' ],
				'permission_callback' => [ $this->controller, 'permission_public' ],
				'args'                => $this->get_listing_reviews_params(),
				'schema'              => [ $this, 'get_review_schema' ],
			]
		);
	}

	/**
	 * Get collection parameters.
	 *
	 * @since 1.0.0
	 *
	 * @return array Parameters.
	 */
	public function get_collection_params(): array {
		return [
			'listing_id' => [
				'description' => __( 'Filter by listing ID.', 'all-purpose-directory' ),
				'type'        => 'integer',
				'minimum'     => 1,
			],
			'author'     => [
				'description' => __( 'Filter by author user ID.', 'all-purpose-directory' ),
				'type'        => 'integer',
				'minimum'     => 1,
			],
			'status'     => [
				'description' => __( 'Filter by review status.', 'all-purpose-directory' ),
				'type'        => 'string',
				'default'     => 'approved',
				'enum'        => [ 'approved', 'pending', 'all' ],
			],
			'page'       => [
				'description' => __( 'Current page.', 'all-purpose-directory' ),
				'type'        => 'integer',
				'default'     => 1,
				'minimum'     => 1,
			],
			'per_page'   => [
				'description' => __( 'Items per page.', 'all-purpose-directory' ),
				'type'        => 'integer',
				'default'     => 10,
				'minimum'     => 1,
				'maximum'     => 100,
			],
			'orderby'    => [
				'description' => __( 'Order by field.', 'all-purpose-directory' ),
				'type'        => 'string',
				'default'     => 'date',
				'enum'        => [ 'date', 'rating' ],
			],
			'order'      => [
				'description' => __( 'Sort order.', 'all-purpose-directory' ),
				'type'        => 'string',
				'default'     => 'DESC',
				'enum'        => [ 'ASC', 'DESC' ],
			],
		];
	}

	/**
	 * Get parameters for listing reviews.
	 *
	 * @since 1.0.0
	 *
	 * @return array Parameters.
	 */
	public function get_listing_reviews_params(): array {
		return [
			'listing_id' => [
				'description' => __( 'Listing ID.', 'all-purpose-directory' ),
				'type'        => 'integer',
				'required'    => true,
				'minimum'     => 1,
			],
			'status'     => [
				'description' => __( 'Filter by review status.', 'all-purpose-directory' ),
				'type'        => 'string',
				'default'     => 'approved',
				'enum'        => [ 'approved', 'pending', 'all' ],
			],
			'page'       => [
				'description' => __( 'Current page.', 'all-purpose-directory' ),
				'type'        => 'integer',
				'default'     => 1,
				'minimum'     => 1,
			],
			'per_page'   => [
				'description' => __( 'Items per page.', 'all-purpose-directory' ),
				'type'        => 'integer',
				'default'     => 10,
				'minimum'     => 1,
				'maximum'     => 100,
			],
		];
	}

	/**
	 * Get create parameters.
	 *
	 * @since 1.0.0
	 *
	 * @return array Parameters.
	 */
	public function get_create_params(): array {
		return [
			'listing_id' => [
				'description' => __( 'Listing ID to review.', 'all-purpose-directory' ),
				'type'        => 'integer',
				'required'    => true,
				'minimum'     => 1,
			],
			'rating'     => [
				'description' => __( 'Rating from 1 to 5.', 'all-purpose-directory' ),
				'type'        => 'integer',
				'required'    => true,
				'minimum'     => 1,
				'maximum'     => 5,
			],
			'title'      => [
				'description' => __( 'Review title.', 'all-purpose-directory' ),
				'type'        => 'string',
				'maxLength'   => 200,
			],
			'content'    => [
				'description' => __( 'Review content.', 'all-purpose-directory' ),
				'type'        => 'string',
				'required'    => true,
				'minLength'   => 10,
			],
		];
	}

	/**
	 * Get update parameters.
	 *
	 * @since 1.0.0
	 *
	 * @return array Parameters.
	 */
	public function get_update_params(): array {
		return [
			'id'      => [
				'description' => __( 'Review ID.', 'all-purpose-directory' ),
				'type'        => 'integer',
				'required'    => true,
				'minimum'     => 1,
			],
			'rating'  => [
				'description' => __( 'Rating from 1 to 5.', 'all-purpose-directory' ),
				'type'        => 'integer',
				'minimum'     => 1,
				'maximum'     => 5,
			],
			'title'   => [
				'description' => __( 'Review title.', 'all-purpose-directory' ),
				'type'        => 'string',
				'maxLength'   => 200,
			],
			'content' => [
				'description' => __( 'Review content.', 'all-purpose-directory' ),
				'type'        => 'string',
				'minLength'   => 10,
			],
		];
	}

	/**
	 * Get review schema.
	 *
	 * @since 1.0.0
	 *
	 * @return array Schema.
	 */
	public function get_review_schema(): array {
		return [
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'review',
			'type'       => 'object',
			'properties' => [
				'id'         => [
					'description' => __( 'Unique identifier.', 'all-purpose-directory' ),
					'type'        => 'integer',
					'readonly'    => true,
				],
				'listing_id' => [
					'description' => __( 'Listing ID.', 'all-purpose-directory' ),
					'type'        => 'integer',
				],
				'author_id'  => [
					'description' => __( 'Author user ID.', 'all-purpose-directory' ),
					'type'        => 'integer',
					'readonly'    => true,
				],
				'author'     => [
					'description' => __( 'Author details.', 'all-purpose-directory' ),
					'type'        => 'object',
					'readonly'    => true,
				],
				'rating'     => [
					'description' => __( 'Rating from 1 to 5.', 'all-purpose-directory' ),
					'type'        => 'integer',
					'minimum'     => 1,
					'maximum'     => 5,
				],
				'title'      => [
					'description' => __( 'Review title.', 'all-purpose-directory' ),
					'type'        => 'string',
				],
				'content'    => [
					'description' => __( 'Review content.', 'all-purpose-directory' ),
					'type'        => 'string',
				],
				'status'     => [
					'description' => __( 'Review status.', 'all-purpose-directory' ),
					'type'        => 'string',
					'readonly'    => true,
				],
				'date'       => [
					'description' => __( 'Created date.', 'all-purpose-directory' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'readonly'    => true,
				],
			],
		];
	}

	/**
	 * Check if user can edit a review.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error True if allowed, error otherwise.
	 */
	public function permission_edit_review( WP_REST_Request $request ): bool|WP_Error {
		$auth_check = $this->controller->permission_authenticated_with_nonce( $request );
		if ( is_wp_error( $auth_check ) ) {
			return $auth_check;
		}

		$review_id = (int) $request->get_param( 'id' );
		$review    = apd_get_review( $review_id );

		if ( ! $review ) {
			return new WP_Error(
				'rest_review_not_found',
				__( 'Review not found.', 'all-purpose-directory' ),
				[ 'status' => 404 ]
			);
		}

		$user_id = get_current_user_id();

		// Admin can edit any review.
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		// Author can edit their own review.
		if ( $user_id && (int) $review['author_id'] === $user_id ) {
			return true;
		}

		return new WP_Error(
			'rest_forbidden',
			__( 'You do not have permission to edit this review.', 'all-purpose-directory' ),
			[ 'status' => 403 ]
		);
	}

	/**
	 * Check if user can delete a review.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error True if allowed, error otherwise.
	 */
	public function permission_delete_review( WP_REST_Request $request ): bool|WP_Error {
		$auth_check = $this->controller->permission_authenticated_with_nonce( $request );
		if ( is_wp_error( $auth_check ) ) {
			return $auth_check;
		}

		$review_id = (int) $request->get_param( 'id' );
		$review    = apd_get_review( $review_id );

		if ( ! $review ) {
			return new WP_Error(
				'rest_review_not_found',
				__( 'Review not found.', 'all-purpose-directory' ),
				[ 'status' => 404 ]
			);
		}

		$user_id = get_current_user_id();

		// Admin can delete any review.
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		// Author can delete their own review.
		if ( $user_id && (int) $review['author_id'] === $user_id ) {
			return true;
		}

		return new WP_Error(
			'rest_forbidden',
			__( 'You do not have permission to delete this review.', 'all-purpose-directory' ),
			[ 'status' => 403 ]
		);
	}

	/**
	 * Get reviews collection.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function get_reviews( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$listing_id = $request->get_param( 'listing_id' );
		$author     = $request->get_param( 'author' );
		$status     = $this->enforce_public_collection_status( (string) ( $request->get_param( 'status' ) ?? 'approved' ) );
		$page       = (int) ( $request->get_param( 'page' ) ?? 1 );
		$per_page   = (int) ( $request->get_param( 'per_page' ) ?? 10 );
		$orderby    = $request->get_param( 'orderby' ) ?? 'date';
		$order      = $request->get_param( 'order' ) ?? 'DESC';

		$args = [
			'status'  => $status,
			'number'  => $per_page,
			'offset'  => $this->get_pagination_offset( $page, $per_page ),
			'orderby' => $orderby,
			'order'   => $order,
		];

		if ( $listing_id ) {
			$args['listing_id'] = (int) $listing_id;
		}

		if ( $author ) {
			$args['author'] = (int) $author;
		}

		$result = apd_get_listing_reviews( $listing_id ?? 0, $args );
		$total  = $result['total'] ?? apd_get_review_count( $listing_id ?? 0, $status );

		$items = [];
		foreach ( $result['reviews'] ?? [] as $review ) {
			$items[] = $this->prepare_review_for_response( $review );
		}

		return $this->controller->create_paginated_response( $items, $total, $page, $per_page );
	}

	/**
	 * Get reviews for a specific listing.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function get_listing_reviews( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$listing_id = (int) $request->get_param( 'listing_id' );
		$status     = $this->enforce_public_collection_status( (string) ( $request->get_param( 'status' ) ?? 'approved' ) );
		$page       = (int) ( $request->get_param( 'page' ) ?? 1 );
		$per_page   = (int) ( $request->get_param( 'per_page' ) ?? 10 );

		// Verify listing exists.
		$post = get_post( $listing_id );
		if ( ! $post || 'apd_listing' !== $post->post_type ) {
			return $this->controller->create_error(
				'rest_listing_not_found',
				__( 'Listing not found.', 'all-purpose-directory' ),
				404
			);
		}

		$args = [
			'status' => $status,
			'number' => $per_page,
			'offset' => $this->get_pagination_offset( $page, $per_page ),
		];

		$result = apd_get_listing_reviews( $listing_id, $args );
		$total  = $result['total'] ?? apd_get_review_count( $listing_id, $status );

		$items = [];
		foreach ( $result['reviews'] ?? [] as $review ) {
			$items[] = $this->prepare_review_for_response( $review );
		}

		// Include listing rating summary.
		$rating_data = [
			'rating'       => apd_get_listing_rating( $listing_id ),
			'review_count' => $total,
		];

		$response               = $this->controller->create_paginated_response( $items, $total, $page, $per_page );
		$data                   = $response->get_data();
		$data['listing_rating'] = $rating_data;
		$response->set_data( $data );

		return $response;
	}

	/**
	 * Get a single review.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function get_review( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$review_id = (int) $request->get_param( 'id' );
		$review    = apd_get_review( $review_id );

		if ( ! $review ) {
			return $this->controller->create_error(
				'rest_review_not_found',
				__( 'Review not found.', 'all-purpose-directory' ),
				404
			);
		}

		// Public reads must not expose non-approved reviews to unrelated users.
		if ( ! $this->can_view_review( $review ) ) {
			return $this->controller->create_error(
				'rest_review_not_found',
				__( 'Review not found.', 'all-purpose-directory' ),
				404
			);
		}

		return $this->controller->create_response(
			$this->prepare_review_for_response( $review )
		);
	}

	/**
	 * Create a review.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function create_review( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$listing_id = (int) $request->get_param( 'listing_id' );
		$rating     = (int) $request->get_param( 'rating' );
		$title      = $request->get_param( 'title' ) ?? '';
		$content    = $request->get_param( 'content' );

		// Verify listing exists.
		$post = get_post( $listing_id );
		if ( ! $post || 'apd_listing' !== $post->post_type ) {
			return $this->controller->create_error(
				'rest_listing_not_found',
				__( 'Listing not found.', 'all-purpose-directory' ),
				404
			);
		}

		// Check if user has already reviewed this listing.
		$user_id = get_current_user_id();
		if ( apd_has_user_reviewed( $listing_id, $user_id ) ) {
			return $this->controller->create_error(
				'rest_review_exists',
				__( 'You have already reviewed this listing.', 'all-purpose-directory' ),
				400
			);
		}

		// Create the review.
		$review_data = [
			'rating'  => $rating,
			'title'   => $title,
			'content' => $content,
		];

		$review_id = apd_create_review( $listing_id, $review_data );

		if ( is_wp_error( $review_id ) ) {
			return $this->controller->create_error(
				$review_id->get_error_code(),
				$review_id->get_error_message(),
				400
			);
		}

		$review = apd_get_review( $review_id );

		return $this->controller->create_response(
			$this->prepare_review_for_response( $review ),
			201
		);
	}

	/**
	 * Update a review.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function update_review( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$review_id = (int) $request->get_param( 'id' );

		$update_data = [];

		$rating = $request->get_param( 'rating' );
		if ( $rating !== null ) {
			$update_data['rating'] = (int) $rating;
		}

		$title = $request->get_param( 'title' );
		if ( $title !== null ) {
			$update_data['title'] = $title;
		}

		$content = $request->get_param( 'content' );
		if ( $content !== null ) {
			$update_data['content'] = $content;
		}

		if ( empty( $update_data ) ) {
			return $this->controller->create_error(
				'rest_no_update_data',
				__( 'No data provided to update.', 'all-purpose-directory' ),
				400
			);
		}

		$result = apd_update_review( $review_id, $update_data );

		if ( is_wp_error( $result ) ) {
			return $this->controller->create_error(
				$result->get_error_code(),
				$result->get_error_message(),
				400
			);
		}

		$review = apd_get_review( $review_id );

		return $this->controller->create_response(
			$this->prepare_review_for_response( $review )
		);
	}

	/**
	 * Delete a review.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function delete_review( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$review_id    = (int) $request->get_param( 'id' );
		$force_delete = (bool) $request->get_param( 'force' );

		$result = apd_delete_review( $review_id, $force_delete );

		if ( ! $result ) {
			return $this->controller->create_error(
				'rest_delete_failed',
				__( 'Failed to delete review.', 'all-purpose-directory' ),
				500
			);
		}

		return $this->controller->create_response(
			[
				'deleted'   => true,
				'review_id' => $review_id,
			]
		);
	}

	/**
	 * Enforce safe review status for public collection reads.
	 *
	 * @since 1.0.0
	 *
	 * @param string $status Requested status.
	 * @return string Safe status.
	 */
	private function enforce_public_collection_status( string $status ): string {
		if ( current_user_can( 'manage_options' ) ) {
			return $status;
		}

		return 'approved';
	}

	/**
	 * Determine if the current user can view a specific review.
	 *
	 * @since 1.0.0
	 *
	 * @param array $review Review data.
	 * @return bool True when view is allowed.
	 */
	private function can_view_review( array $review ): bool {
		if ( ( $review['status'] ?? 'pending' ) === 'approved' ) {
			return true;
		}

		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		$user_id = get_current_user_id();

		return $user_id > 0 && $user_id === (int) ( $review['author_id'] ?? 0 );
	}

	/**
	 * Convert page/per_page into a query offset.
	 *
	 * @since 1.0.0
	 *
	 * @param int $page     Current page.
	 * @param int $per_page Items per page.
	 * @return int Zero-based offset.
	 */
	private function get_pagination_offset( int $page, int $per_page ): int {
		$page     = max( 1, $page );
		$per_page = max( 1, $per_page );

		return ( $page - 1 ) * $per_page;
	}

	/**
	 * Prepare review for response.
	 *
	 * @since 1.0.0
	 *
	 * @param array $review Review data.
	 * @return array Prepared data.
	 */
	public function prepare_review_for_response( array $review ): array {
		$data = [
			'id'         => $review['id'] ?? 0,
			'listing_id' => $review['listing_id'] ?? 0,
			'author_id'  => $review['author_id'] ?? 0,
			'rating'     => $review['rating'] ?? 0,
			'title'      => $review['title'] ?? '',
			'content'    => $review['content'] ?? '',
			'status'     => $review['status'] ?? 'pending',
			'date'       => $review['date'] ?? '',
		];

		// Add author details.
		if ( ! empty( $review['author_id'] ) ) {
			$user = get_user_by( 'id', $review['author_id'] );
			if ( $user ) {
				$data['author'] = [
					'id'     => $user->ID,
					'name'   => $user->display_name,
					'avatar' => get_avatar_url( $user->ID, [ 'size' => 48 ] ),
				];
			}
		}

		/**
		 * Filters the review data for REST API response.
		 *
		 * @since 1.0.0
		 *
		 * @param array $data   Review data.
		 * @param array $review Original review data.
		 */
		return apply_filters( 'apd_rest_review_data', $data, $review );
	}
}
