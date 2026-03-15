<?php
/**
 * Listings REST API Endpoint.
 *
 * Handles CRUD operations for listings via REST API.
 *
 * @package APD\Api\Endpoints
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Api\Endpoints;

use APD\Api\RestController;
use WP_Error;
use WP_Post;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ListingsEndpoint
 *
 * REST API endpoint controller for listings.
 *
 * @since 1.0.0
 */
class ListingsEndpoint {

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

		// GET /listings - List listings.
		// POST /listings - Create listing.
		register_rest_route(
			$namespace,
			'/listings',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_items' ],
					'permission_callback' => [ $this->controller, 'permission_public' ],
					'args'                => $this->get_collection_params(),
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'create_item' ],
					'permission_callback' => [ $this->controller, 'permission_create_listing' ],
					'args'                => $this->get_create_params(),
				],
				'schema' => [ $this, 'get_item_schema' ],
			]
		);

		// GET /listings/{id} - Get single listing.
		// PUT/PATCH /listings/{id} - Update listing.
		// DELETE /listings/{id} - Delete listing.
		register_rest_route(
			$namespace,
			'/listings/(?P<id>[\d]+)',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_item' ],
					'permission_callback' => [ $this->controller, 'permission_public' ],
					'args'                => [
						'id' => [
							'description' => __( 'Unique identifier for the listing.', 'all-purpose-directory' ),
							'type'        => 'integer',
							'required'    => true,
						],
					],
				],
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'update_item' ],
					'permission_callback' => [ $this->controller, 'permission_edit_listing' ],
					'args'                => $this->get_update_params(),
				],
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'delete_item' ],
					'permission_callback' => [ $this->controller, 'permission_delete_listing' ],
					'args'                => [
						'id'    => [
							'description' => __( 'Unique identifier for the listing.', 'all-purpose-directory' ),
							'type'        => 'integer',
							'required'    => true,
						],
						'force' => [
							'description' => __( 'Whether to bypass trash and force deletion.', 'all-purpose-directory' ),
							'type'        => 'boolean',
							'default'     => false,
						],
					],
				],
				'schema' => [ $this, 'get_item_schema' ],
			]
		);
	}

	/**
	 * Get collection of listings.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function get_items( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$args = [
			'post_type'      => 'apd_listing',
			'post_status'    => 'publish',
			'posts_per_page' => $request->get_param( 'per_page' ) ?? 10,
			'paged'          => $request->get_param( 'page' ) ?? 1,
			'orderby'        => $request->get_param( 'orderby' ) ?? 'date',
			'order'          => $request->get_param( 'order' ) ?? 'DESC',
		];

		// Search query.
		$search = $request->get_param( 'search' );
		if ( ! empty( $search ) ) {
			$args['s'] = $search;
		}

		// Category filter.
		$category = $request->get_param( 'category' );
		if ( ! empty( $category ) ) {
			$args['tax_query']   = $args['tax_query'] ?? []; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			$args['tax_query'][] = [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Taxonomy filtering is core REST API functionality.
				'taxonomy' => 'apd_category',
				'field'    => is_numeric( $category ) ? 'term_id' : 'slug',
				'terms'    => $category,
			];
		}

		// Tag filter.
		$tag = $request->get_param( 'tag' );
		if ( ! empty( $tag ) ) {
			$args['tax_query']   = $args['tax_query'] ?? []; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			$args['tax_query'][] = [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				'taxonomy' => 'apd_tag',
				'field'    => is_numeric( $tag ) ? 'term_id' : 'slug',
				'terms'    => $tag,
			];
		}

		// Listing type filter.
		$type = $request->get_param( 'type' );
		if ( ! empty( $type ) ) {
			$args['tax_query']   = $args['tax_query'] ?? []; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			$args['tax_query'][] = [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				'taxonomy' => \APD\Taxonomy\ListingTypeTaxonomy::TAXONOMY,
				'field'    => is_numeric( $type ) ? 'term_id' : 'slug',
				'terms'    => $type,
			];
		}

		// Author filter.
		$author = $request->get_param( 'author' );
		if ( ! empty( $author ) ) {
			$args['author'] = absint( $author );
		}

		// Status filter (only for authenticated users with permissions).
		$status = $request->get_param( 'status' );
		if ( ! empty( $status ) && current_user_can( 'edit_others_apd_listings' ) ) {
			$args['post_status'] = $status;
		}

		/**
		 * Filters the listings query args for REST API.
		 *
		 * @since 1.0.0
		 *
		 * @param array           $args    Query arguments.
		 * @param WP_REST_Request $request The REST request.
		 */
		$args = apply_filters( 'apd_rest_listings_query_args', $args, $request );

		$query = new \WP_Query( $args );

		// Prime the post meta cache to avoid N+1 queries when fetching field values.
		if ( ! empty( $query->posts ) ) {
			$post_ids = wp_list_pluck( $query->posts, 'ID' );
			\update_postmeta_cache( $post_ids );
		}

		$items = [];
		foreach ( $query->posts as $post ) {
			$items[] = $this->prepare_item_for_response( $post, $request );
		}

		return $this->controller->create_paginated_response(
			$items,
			$query->found_posts,
			(int) ( $request->get_param( 'page' ) ?? 1 ),
			(int) ( $request->get_param( 'per_page' ) ?? 10 )
		);
	}

	/**
	 * Get a single listing.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function get_item( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$listing_id = (int) $request->get_param( 'id' );
		$listing    = get_post( $listing_id );

		if ( ! $listing || $listing->post_type !== 'apd_listing' ) {
			return $this->controller->create_error(
				'rest_listing_not_found',
				__( 'Listing not found.', 'all-purpose-directory' ),
				404
			);
		}

		// Check if the listing is viewable.
		if ( $listing->post_status !== 'publish' && ! current_user_can( 'edit_post', $listing_id ) ) {
			return $this->controller->create_error(
				'rest_listing_not_found',
				__( 'Listing not found.', 'all-purpose-directory' ),
				404
			);
		}

		$data = $this->prepare_item_for_response( $listing, $request );

		return $this->controller->create_response( $data );
	}

	/**
	 * Create a listing.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function create_item( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$title   = $request->get_param( 'title' );
		$content = $request->get_param( 'content' ) ?? '';
		$excerpt = $request->get_param( 'excerpt' ) ?? '';
		$status  = $request->get_param( 'status' ) ?? 'pending';

		// Validate title.
		if ( empty( $title ) ) {
			return $this->controller->create_error(
				'rest_missing_param',
				__( 'Title is required.', 'all-purpose-directory' ),
				400
			);
		}

		// Validate status.
		$allowed_statuses = [ 'publish', 'pending', 'draft' ];
		if ( ! in_array( $status, $allowed_statuses, true ) ) {
			$status = 'pending';
		}

		// Only allow publish status if user can publish.
		if ( $status === 'publish' && ! current_user_can( 'publish_apd_listings' ) ) {
			$status = 'pending';
		}

		$post_data = [
			'post_type'    => 'apd_listing',
			'post_title'   => sanitize_text_field( $title ),
			'post_content' => wp_kses_post( $content ),
			'post_excerpt' => sanitize_textarea_field( $excerpt ),
			'post_status'  => $status,
			'post_author'  => get_current_user_id(),
		];

		/**
		 * Fires before a listing is created via REST API.
		 *
		 * @since 1.0.0
		 *
		 * @param array           $post_data Post data to be inserted.
		 * @param WP_REST_Request $request   The REST request.
		 */
		do_action( 'apd_rest_before_create_listing', $post_data, $request );

		$listing_id = wp_insert_post( $post_data, true );

		if ( is_wp_error( $listing_id ) ) {
			return $this->controller->create_error(
				'rest_listing_create_failed',
				$listing_id->get_error_message(),
				500
			);
		}

		// Handle categories.
		$categories = $request->get_param( 'categories' );
		if ( ! empty( $categories ) && is_array( $categories ) ) {
			wp_set_object_terms( $listing_id, array_map( 'absint', $categories ), 'apd_category' );
		}

		// Handle tags.
		$tags = $request->get_param( 'tags' );
		if ( ! empty( $tags ) && is_array( $tags ) ) {
			wp_set_object_terms( $listing_id, array_map( 'absint', $tags ), 'apd_tag' );
		}

		// Handle listing type.
		$type = $request->get_param( 'type' );
		if ( ! empty( $type ) ) {
			wp_set_object_terms( $listing_id, sanitize_key( $type ), \APD\Taxonomy\ListingTypeTaxonomy::TAXONOMY );
		}

		// Handle custom fields.
		$meta = $request->get_param( 'meta' );
		if ( ! empty( $meta ) && is_array( $meta ) ) {
			$this->update_listing_meta( $listing_id, $meta );
		}

		// Handle featured image.
		$featured_image = $request->get_param( 'featured_image' );
		if ( ! empty( $featured_image ) ) {
			$image_error = $this->validate_featured_image( absint( $featured_image ) );
			if ( is_wp_error( $image_error ) ) {
				return $image_error;
			}
			set_post_thumbnail( $listing_id, absint( $featured_image ) );
		}

		/**
		 * Fires after a listing is created via REST API.
		 *
		 * @since 1.0.0
		 *
		 * @param int             $listing_id The created listing ID.
		 * @param WP_REST_Request $request    The REST request.
		 */
		do_action( 'apd_rest_after_create_listing', $listing_id, $request );

		$listing = get_post( $listing_id );
		$data    = $this->prepare_item_for_response( $listing, $request );

		return $this->controller->create_response( $data, 201 );
	}

	/**
	 * Update a listing.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function update_item( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$listing_id = (int) $request->get_param( 'id' );
		$listing    = get_post( $listing_id );

		if ( ! $listing || $listing->post_type !== 'apd_listing' ) {
			return $this->controller->create_error(
				'rest_listing_not_found',
				__( 'Listing not found.', 'all-purpose-directory' ),
				404
			);
		}

		$post_data = [ 'ID' => $listing_id ];

		// Update title if provided.
		$title = $request->get_param( 'title' );
		if ( $title !== null ) {
			$post_data['post_title'] = sanitize_text_field( $title );
		}

		// Update content if provided.
		$content = $request->get_param( 'content' );
		if ( $content !== null ) {
			$post_data['post_content'] = wp_kses_post( $content );
		}

		// Update excerpt if provided.
		$excerpt = $request->get_param( 'excerpt' );
		if ( $excerpt !== null ) {
			$post_data['post_excerpt'] = sanitize_textarea_field( $excerpt );
		}

		// Update status if provided and user has permission.
		$status = $request->get_param( 'status' );
		if ( $status !== null ) {
			$allowed_statuses = [ 'publish', 'pending', 'draft' ];
			if ( in_array( $status, $allowed_statuses, true ) ) {
				// Only allow publish status if user can publish.
				if ( $status === 'publish' && ! current_user_can( 'publish_apd_listings' ) ) {
					$status = 'pending';
				}
				$post_data['post_status'] = $status;
			}
		}

		/**
		 * Fires before a listing is updated via REST API.
		 *
		 * @since 1.0.0
		 *
		 * @param array           $post_data Post data to be updated.
		 * @param WP_Post         $listing   The existing listing.
		 * @param WP_REST_Request $request   The REST request.
		 */
		do_action( 'apd_rest_before_update_listing', $post_data, $listing, $request );

		$result = wp_update_post( $post_data, true );

		if ( is_wp_error( $result ) ) {
			return $this->controller->create_error(
				'rest_listing_update_failed',
				$result->get_error_message(),
				500
			);
		}

		// Handle categories.
		$categories = $request->get_param( 'categories' );
		if ( $categories !== null && is_array( $categories ) ) {
			wp_set_object_terms( $listing_id, array_map( 'absint', $categories ), 'apd_category' );
		}

		// Handle tags.
		$tags = $request->get_param( 'tags' );
		if ( $tags !== null && is_array( $tags ) ) {
			wp_set_object_terms( $listing_id, array_map( 'absint', $tags ), 'apd_tag' );
		}

		// Handle listing type.
		$type = $request->get_param( 'type' );
		if ( $type !== null ) {
			wp_set_object_terms( $listing_id, sanitize_key( $type ), \APD\Taxonomy\ListingTypeTaxonomy::TAXONOMY );
		}

		// Handle custom fields.
		$meta = $request->get_param( 'meta' );
		if ( ! empty( $meta ) && is_array( $meta ) ) {
			$this->update_listing_meta( $listing_id, $meta );
		}

		// Handle featured image.
		$featured_image = $request->get_param( 'featured_image' );
		if ( $featured_image !== null ) {
			if ( empty( $featured_image ) ) {
				delete_post_thumbnail( $listing_id );
			} else {
				$image_error = $this->validate_featured_image( absint( $featured_image ) );
				if ( is_wp_error( $image_error ) ) {
					return $image_error;
				}
				set_post_thumbnail( $listing_id, absint( $featured_image ) );
			}
		}

		/**
		 * Fires after a listing is updated via REST API.
		 *
		 * @since 1.0.0
		 *
		 * @param int             $listing_id The updated listing ID.
		 * @param WP_REST_Request $request    The REST request.
		 */
		do_action( 'apd_rest_after_update_listing', $listing_id, $request );

		$listing = get_post( $listing_id );
		$data    = $this->prepare_item_for_response( $listing, $request );

		return $this->controller->create_response( $data );
	}

	/**
	 * Delete a listing.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function delete_item( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$listing_id = (int) $request->get_param( 'id' );
		$force      = (bool) $request->get_param( 'force' );
		$listing    = get_post( $listing_id );

		if ( ! $listing || $listing->post_type !== 'apd_listing' ) {
			return $this->controller->create_error(
				'rest_listing_not_found',
				__( 'Listing not found.', 'all-purpose-directory' ),
				404
			);
		}

		/**
		 * Fires before a listing is deleted via REST API.
		 *
		 * @since 1.0.0
		 *
		 * @param WP_Post         $listing The listing being deleted.
		 * @param bool            $force   Whether to force delete.
		 * @param WP_REST_Request $request The REST request.
		 */
		do_action( 'apd_rest_before_delete_listing', $listing, $force, $request );

		$previous_data = $this->prepare_item_for_response( $listing, $request );

		$result = $force ? wp_delete_post( $listing_id, true ) : wp_trash_post( $listing_id );

		if ( ! $result ) {
			return $this->controller->create_error(
				'rest_listing_delete_failed',
				__( 'Failed to delete listing.', 'all-purpose-directory' ),
				500
			);
		}

		/**
		 * Fires after a listing is deleted via REST API.
		 *
		 * @since 1.0.0
		 *
		 * @param int             $listing_id The deleted listing ID.
		 * @param bool            $force      Whether it was force deleted.
		 * @param WP_REST_Request $request    The REST request.
		 */
		do_action( 'apd_rest_after_delete_listing', $listing_id, $force, $request );

		return $this->controller->create_response(
			[
				'deleted'  => true,
				'previous' => $previous_data,
			]
		);
	}

	/**
	 * Prepare a listing for response.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post         $listing Listing post object.
	 * @param WP_REST_Request $request The REST request.
	 * @return array Prepared listing data.
	 */
	public function prepare_item_for_response( WP_Post $listing, WP_REST_Request $request ): array {
		$categories = apd_get_listing_categories( $listing->ID );
		$tags       = apd_get_listing_tags( $listing->ID );

		$data = [
			'id'             => $listing->ID,
			'title'          => $listing->post_title,
			'content'        => $listing->post_content,
			'excerpt'        => $listing->post_excerpt,
			'status'         => $listing->post_status,
			'author'         => (int) $listing->post_author,
			'date'           => mysql_to_rfc3339( $listing->post_date ),
			'date_gmt'       => mysql_to_rfc3339( $listing->post_date_gmt ),
			'modified'       => mysql_to_rfc3339( $listing->post_modified ),
			'modified_gmt'   => mysql_to_rfc3339( $listing->post_modified_gmt ),
			'slug'           => $listing->post_name,
			'link'           => get_permalink( $listing->ID ),
			'featured_image' => get_post_thumbnail_id( $listing->ID ) ?: null,
			'categories'     => array_map(
				fn( $term ) => [
					'id'   => $term->term_id,
					'name' => $term->name,
					'slug' => $term->slug,
				],
				$categories
			),
			'tags'           => array_map(
				fn( $term ) => [
					'id'   => $term->term_id,
					'name' => $term->name,
					'slug' => $term->slug,
				],
				$tags
			),
			'type'           => apd_get_listing_type( $listing->ID ),
			'meta'           => $this->get_listing_meta( $listing->ID ),
		];

		// Add view count if function exists.
		if ( function_exists( 'apd_get_listing_views' ) ) {
			$data['views'] = apd_get_listing_views( $listing->ID );
		}

		// Add rating if function exists.
		if ( function_exists( 'apd_get_listing_rating' ) ) {
			$data['rating']       = apd_get_listing_rating( $listing->ID );
			$data['review_count'] = apd_get_review_count( $listing->ID );
		}

		// Add favorite count if function exists.
		if ( function_exists( 'apd_get_listing_favorites_count' ) ) {
			$data['favorite_count'] = apd_get_listing_favorites_count( $listing->ID );
		}

		/**
		 * Filters the listing data for REST API response.
		 *
		 * @since 1.0.0
		 *
		 * @param array           $data    Listing data.
		 * @param WP_Post         $listing Listing post object.
		 * @param WP_REST_Request $request The REST request.
		 */
		return apply_filters( 'apd_rest_listing_data', $data, $listing, $request );
	}

	/**
	 * Validate a featured image attachment ID.
	 *
	 * Ensures the attachment exists, is an image, and that the current user
	 * is the author or an admin. Prevents IDOR where a user could assign
	 * another user's private media as their listing's featured image.
	 *
	 * @since 1.0.0
	 *
	 * @param int $attachment_id The attachment ID to validate.
	 * @return bool|WP_Error True if valid, WP_Error on failure.
	 */
	private function validate_featured_image( int $attachment_id ): bool|WP_Error {
		$attachment = get_post( $attachment_id );

		if ( ! $attachment || $attachment->post_type !== 'attachment' ) {
			return $this->controller->create_error(
				'rest_invalid_featured_image',
				__( 'Invalid featured image. The attachment does not exist.', 'all-purpose-directory' ),
				400
			);
		}

		if ( ! wp_attachment_is_image( $attachment_id ) ) {
			return $this->controller->create_error(
				'rest_invalid_featured_image',
				__( 'Invalid featured image. The attachment is not an image.', 'all-purpose-directory' ),
				400
			);
		}

		// Allow if user is admin or the attachment author.
		if ( ! current_user_can( 'manage_options' ) && (int) $attachment->post_author !== get_current_user_id() ) {
			return $this->controller->create_error(
				'rest_forbidden_featured_image',
				__( 'You do not have permission to use this image.', 'all-purpose-directory' ),
				403
			);
		}

		return true;
	}

	/**
	 * Get listing custom field meta.
	 *
	 * @since 1.0.0
	 *
	 * @param int $listing_id Listing ID.
	 * @return array Custom field values.
	 */
	private function get_listing_meta( int $listing_id ): array {
		$meta = [];

		// Get all registered fields.
		if ( function_exists( 'apd_get_fields' ) ) {
			$fields = apd_get_fields();

			foreach ( $fields as $field ) {
				$value = apd_get_listing_field( $listing_id, $field['name'] );
				if ( $value !== null && $value !== '' ) {
					$meta[ $field['name'] ] = $value;
				}
			}
		}

		return $meta;
	}

	/**
	 * Update listing custom field meta.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $listing_id Listing ID.
	 * @param array $meta       Meta values to update.
	 * @return void
	 */
	private function update_listing_meta( int $listing_id, array $meta ): void {
		if ( ! function_exists( 'apd_set_listing_field' ) ) {
			return;
		}

		foreach ( $meta as $key => $value ) {
			apd_set_listing_field( $listing_id, $key, $value );
		}
	}

	/**
	 * Get collection query parameters.
	 *
	 * @since 1.0.0
	 *
	 * @return array Query parameters.
	 */
	public function get_collection_params(): array {
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
			'search'   => [
				'description' => __( 'Search term.', 'all-purpose-directory' ),
				'type'        => 'string',
			],
			'category' => [
				'description' => __( 'Filter by category ID or slug.', 'all-purpose-directory' ),
				'type'        => [ 'integer', 'string' ],
			],
			'tag'      => [
				'description' => __( 'Filter by tag ID or slug.', 'all-purpose-directory' ),
				'type'        => [ 'integer', 'string' ],
			],
			'type'     => [
				'description' => __( 'Filter by listing type slug.', 'all-purpose-directory' ),
				'type'        => 'string',
			],
			'author'   => [
				'description' => __( 'Filter by author user ID.', 'all-purpose-directory' ),
				'type'        => 'integer',
			],
			'status'   => [
				'description' => __( 'Filter by status (requires manage listings capability).', 'all-purpose-directory' ),
				'type'        => 'string',
				'enum'        => [ 'publish', 'pending', 'draft', 'expired' ],
			],
			'orderby'  => [
				'description' => __( 'Sort collection by field.', 'all-purpose-directory' ),
				'type'        => 'string',
				'default'     => 'date',
				'enum'        => [ 'date', 'title', 'modified', 'rand', 'views' ],
			],
			'order'    => [
				'description' => __( 'Sort order.', 'all-purpose-directory' ),
				'type'        => 'string',
				'default'     => 'DESC',
				'enum'        => [ 'ASC', 'DESC' ],
			],
		];
	}

	/**
	 * Get parameters for creating a listing.
	 *
	 * @since 1.0.0
	 *
	 * @return array Create parameters.
	 */
	public function get_create_params(): array {
		return [
			'title'          => [
				'description' => __( 'Listing title.', 'all-purpose-directory' ),
				'type'        => 'string',
				'required'    => true,
			],
			'content'        => [
				'description' => __( 'Listing content/description.', 'all-purpose-directory' ),
				'type'        => 'string',
			],
			'excerpt'        => [
				'description' => __( 'Listing short description.', 'all-purpose-directory' ),
				'type'        => 'string',
			],
			'status'         => [
				'description' => __( 'Listing status.', 'all-purpose-directory' ),
				'type'        => 'string',
				'default'     => 'pending',
				'enum'        => [ 'publish', 'pending', 'draft' ],
			],
			'categories'     => [
				'description' => __( 'Category term IDs.', 'all-purpose-directory' ),
				'type'        => 'array',
				'items'       => [ 'type' => 'integer' ],
			],
			'tags'           => [
				'description' => __( 'Tag term IDs.', 'all-purpose-directory' ),
				'type'        => 'array',
				'items'       => [ 'type' => 'integer' ],
			],
			'type'           => [
				'description' => __( 'Listing type slug.', 'all-purpose-directory' ),
				'type'        => 'string',
			],
			'meta'           => [
				'description' => __( 'Custom field values.', 'all-purpose-directory' ),
				'type'        => 'object',
			],
			'featured_image' => [
				'description' => __( 'Featured image attachment ID.', 'all-purpose-directory' ),
				'type'        => 'integer',
			],
		];
	}

	/**
	 * Get parameters for updating a listing.
	 *
	 * @since 1.0.0
	 *
	 * @return array Update parameters.
	 */
	public function get_update_params(): array {
		return array_merge(
			[
				'id' => [
					'description' => __( 'Unique identifier for the listing.', 'all-purpose-directory' ),
					'type'        => 'integer',
					'required'    => true,
				],
			],
			$this->get_create_params()
		);
	}

	/**
	 * Get the item schema.
	 *
	 * @since 1.0.0
	 *
	 * @return array Schema definition.
	 */
	public function get_item_schema(): array {
		return [
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'listing',
			'type'       => 'object',
			'properties' => [
				'id'             => [
					'description' => __( 'Unique identifier for the listing.', 'all-purpose-directory' ),
					'type'        => 'integer',
					'readonly'    => true,
				],
				'title'          => [
					'description' => __( 'Listing title.', 'all-purpose-directory' ),
					'type'        => 'string',
				],
				'content'        => [
					'description' => __( 'Listing content.', 'all-purpose-directory' ),
					'type'        => 'string',
				],
				'excerpt'        => [
					'description' => __( 'Listing excerpt.', 'all-purpose-directory' ),
					'type'        => 'string',
				],
				'status'         => [
					'description' => __( 'Listing status.', 'all-purpose-directory' ),
					'type'        => 'string',
					'enum'        => [ 'publish', 'pending', 'draft', 'expired' ],
				],
				'author'         => [
					'description' => __( 'Author user ID.', 'all-purpose-directory' ),
					'type'        => 'integer',
				],
				'date'           => [
					'description' => __( 'Published date (RFC3339 format).', 'all-purpose-directory' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'readonly'    => true,
				],
				'link'           => [
					'description' => __( 'Listing URL.', 'all-purpose-directory' ),
					'type'        => 'string',
					'format'      => 'uri',
					'readonly'    => true,
				],
				'featured_image' => [
					'description' => __( 'Featured image attachment ID.', 'all-purpose-directory' ),
					'type'        => [ 'integer', 'null' ],
				],
				'categories'     => [
					'description' => __( 'Assigned categories.', 'all-purpose-directory' ),
					'type'        => 'array',
					'items'       => [
						'type'       => 'object',
						'properties' => [
							'id'   => [ 'type' => 'integer' ],
							'name' => [ 'type' => 'string' ],
							'slug' => [ 'type' => 'string' ],
						],
					],
					'readonly'    => true,
				],
				'tags'           => [
					'description' => __( 'Assigned tags.', 'all-purpose-directory' ),
					'type'        => 'array',
					'items'       => [
						'type'       => 'object',
						'properties' => [
							'id'   => [ 'type' => 'integer' ],
							'name' => [ 'type' => 'string' ],
							'slug' => [ 'type' => 'string' ],
						],
					],
					'readonly'    => true,
				],
				'meta'           => [
					'description' => __( 'Custom field values.', 'all-purpose-directory' ),
					'type'        => 'object',
				],
			],
		];
	}
}
