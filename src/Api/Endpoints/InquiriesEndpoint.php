<?php
/**
 * Inquiries REST API Endpoint.
 *
 * Provides REST API endpoints for managing contact inquiries.
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
 * Class InquiriesEndpoint
 *
 * Handles REST API endpoints for inquiries operations.
 *
 * @since 1.0.0
 */
class InquiriesEndpoint {

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

		// GET /inquiries - Get current user's inquiries.
		register_rest_route(
			$namespace,
			'/inquiries',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_inquiries' ],
				'permission_callback' => [ $this->controller, 'permission_authenticated' ],
				'args'                => $this->get_collection_params(),
				'schema'              => [ $this, 'get_inquiry_schema' ],
			]
		);

		// GET /inquiries/{id} - Get a single inquiry.
		// DELETE /inquiries/{id} - Delete an inquiry.
		register_rest_route(
			$namespace,
			'/inquiries/(?P<id>[\d]+)',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_inquiry' ],
					'permission_callback' => [ $this, 'permission_view_inquiry' ],
					'args'                => [
						'id' => [
							'description' => __( 'Inquiry ID.', 'all-purpose-directory' ),
							'type'        => 'integer',
							'required'    => true,
							'minimum'     => 1,
						],
					],
				],
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'delete_inquiry' ],
					'permission_callback' => [ $this, 'permission_manage_inquiry' ],
					'args'                => [
						'id'    => [
							'description' => __( 'Inquiry ID.', 'all-purpose-directory' ),
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
				'schema' => [ $this, 'get_inquiry_schema' ],
			]
		);

		// POST /inquiries/{id}/read - Mark inquiry as read.
		register_rest_route(
			$namespace,
			'/inquiries/(?P<id>[\d]+)/read',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'mark_read' ],
				'permission_callback' => [ $this, 'permission_manage_inquiry' ],
				'args'                => [
					'id' => [
						'description' => __( 'Inquiry ID.', 'all-purpose-directory' ),
						'type'        => 'integer',
						'required'    => true,
						'minimum'     => 1,
					],
				],
			]
		);

		// POST /inquiries/{id}/unread - Mark inquiry as unread.
		register_rest_route(
			$namespace,
			'/inquiries/(?P<id>[\d]+)/unread',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'mark_unread' ],
				'permission_callback' => [ $this, 'permission_manage_inquiry' ],
				'args'                => [
					'id' => [
						'description' => __( 'Inquiry ID.', 'all-purpose-directory' ),
						'type'        => 'integer',
						'required'    => true,
						'minimum'     => 1,
					],
				],
			]
		);

		// GET /listings/{id}/inquiries - Get inquiries for a specific listing.
		register_rest_route(
			$namespace,
			'/listings/(?P<listing_id>[\d]+)/inquiries',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_listing_inquiries' ],
				'permission_callback' => [ $this, 'permission_view_listing_inquiries' ],
				'args'                => $this->get_listing_inquiries_params(),
				'schema'              => [ $this, 'get_inquiry_schema' ],
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
			'status'   => [
				'description' => __( 'Filter by read status.', 'all-purpose-directory' ),
				'type'        => 'string',
				'default'     => 'all',
				'enum'        => [ 'all', 'read', 'unread' ],
			],
			'page'     => [
				'description' => __( 'Current page.', 'all-purpose-directory' ),
				'type'        => 'integer',
				'default'     => 1,
				'minimum'     => 1,
			],
			'per_page' => [
				'description' => __( 'Items per page.', 'all-purpose-directory' ),
				'type'        => 'integer',
				'default'     => 10,
				'minimum'     => 1,
				'maximum'     => 100,
			],
			'orderby'  => [
				'description' => __( 'Order by field.', 'all-purpose-directory' ),
				'type'        => 'string',
				'default'     => 'date',
				'enum'        => [ 'date', 'listing' ],
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
	 * Get listing inquiries parameters.
	 *
	 * @since 1.0.0
	 *
	 * @return array Parameters.
	 */
	public function get_listing_inquiries_params(): array {
		return [
			'listing_id' => [
				'description' => __( 'Listing ID.', 'all-purpose-directory' ),
				'type'        => 'integer',
				'required'    => true,
				'minimum'     => 1,
			],
			'status'     => [
				'description' => __( 'Filter by read status.', 'all-purpose-directory' ),
				'type'        => 'string',
				'default'     => 'all',
				'enum'        => [ 'all', 'read', 'unread' ],
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
	 * Get inquiry schema.
	 *
	 * @since 1.0.0
	 *
	 * @return array Schema.
	 */
	public function get_inquiry_schema(): array {
		return [
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'inquiry',
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
				'listing'    => [
					'description' => __( 'Listing details.', 'all-purpose-directory' ),
					'type'        => 'object',
					'readonly'    => true,
				],
				'sender'     => [
					'description' => __( 'Sender details.', 'all-purpose-directory' ),
					'type'        => 'object',
				],
				'subject'    => [
					'description' => __( 'Inquiry subject.', 'all-purpose-directory' ),
					'type'        => 'string',
				],
				'message'    => [
					'description' => __( 'Inquiry message.', 'all-purpose-directory' ),
					'type'        => 'string',
				],
				'is_read'    => [
					'description' => __( 'Whether inquiry has been read.', 'all-purpose-directory' ),
					'type'        => 'boolean',
				],
				'date'       => [
					'description' => __( 'Sent date.', 'all-purpose-directory' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'readonly'    => true,
				],
			],
		];
	}

	/**
	 * Check if user can view an inquiry.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error True if allowed, error otherwise.
	 */
	public function permission_view_inquiry( WP_REST_Request $request ): bool|WP_Error {
		$inquiry_id = (int) $request->get_param( 'id' );
		$user_id    = get_current_user_id();

		if ( ! $user_id ) {
			return new WP_Error(
				'rest_not_logged_in',
				__( 'You must be logged in to view inquiries.', 'all-purpose-directory' ),
				[ 'status' => 401 ]
			);
		}

		// Admin can view any inquiry.
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		// Check if user can view this inquiry.
		if ( apd_can_view_inquiry( $inquiry_id, $user_id ) ) {
			return true;
		}

		return new WP_Error(
			'rest_forbidden',
			__( 'You do not have permission to view this inquiry.', 'all-purpose-directory' ),
			[ 'status' => 403 ]
		);
	}

	/**
	 * Check if user can perform mutating actions on an inquiry.
	 *
	 * Requires inquiry access and, for cookie-authenticated requests,
	 * a valid REST nonce (CSRF protection). Non-cookie auth (Application
	 * Passwords, OAuth) skips the nonce check.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error True if allowed, error otherwise.
	 */
	public function permission_manage_inquiry( WP_REST_Request $request ): bool|WP_Error {
		$view_check = $this->permission_view_inquiry( $request );
		if ( is_wp_error( $view_check ) ) {
			return $view_check;
		}

		// Only require nonce for cookie-auth requests (CSRF protection).
		if ( $this->controller->is_cookie_auth( $request ) && ! $this->controller->verify_nonce( $request ) ) {
			return new WP_Error(
				'rest_nonce_invalid',
				__( 'Invalid or missing REST API nonce.', 'all-purpose-directory' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Check if user can view listing inquiries.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error True if allowed, error otherwise.
	 */
	public function permission_view_listing_inquiries( WP_REST_Request $request ): bool|WP_Error {
		$listing_id = (int) $request->get_param( 'listing_id' );
		$user_id    = get_current_user_id();

		if ( ! $user_id ) {
			return new WP_Error(
				'rest_not_logged_in',
				__( 'You must be logged in to view inquiries.', 'all-purpose-directory' ),
				[ 'status' => 401 ]
			);
		}

		// Admin can view any listing's inquiries.
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		// Check if user owns the listing.
		$post = get_post( $listing_id );
		if ( $post && (int) $post->post_author === $user_id ) {
			return true;
		}

		return new WP_Error(
			'rest_forbidden',
			__( 'You do not have permission to view these inquiries.', 'all-purpose-directory' ),
			[ 'status' => 403 ]
		);
	}

	/**
	 * Get current user's inquiries.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function get_inquiries( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$user_id  = get_current_user_id();
		$status   = $request->get_param( 'status' ) ?? 'all';
		$page     = max( 1, (int) ( $request->get_param( 'page' ) ?? 1 ) );
		$per_page = max( 1, (int) ( $request->get_param( 'per_page' ) ?? 10 ) );
		$orderby  = $request->get_param( 'orderby' ) ?? 'date';
		$order    = $request->get_param( 'order' ) ?? 'DESC';

		$args = [
			'number'  => $per_page,
			'offset'  => ( $page - 1 ) * $per_page,
			'status'  => $status,
			'orderby' => $orderby,
			'order'   => $order,
		];

		$inquiries = apd_get_user_inquiries( $user_id, $args );
		$total     = apd_get_user_inquiry_count( $user_id, $status );

		$items = [];
		foreach ( $inquiries as $inquiry ) {
			$items[] = $this->prepare_inquiry_for_response( $inquiry );
		}

		// Add unread count to response.
		$response             = $this->controller->create_paginated_response( $items, $total, $page, $per_page );
		$data                 = $response->get_data();
		$data['unread_count'] = apd_get_user_inquiry_count( $user_id, 'unread' );
		$response->set_data( $data );

		return $response;
	}

	/**
	 * Get inquiries for a specific listing.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function get_listing_inquiries( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$listing_id = (int) $request->get_param( 'listing_id' );
		$status     = $request->get_param( 'status' ) ?? 'all';
		$page       = max( 1, (int) ( $request->get_param( 'page' ) ?? 1 ) );
		$per_page   = max( 1, (int) ( $request->get_param( 'per_page' ) ?? 10 ) );

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
			'number' => $per_page,
			'offset' => ( $page - 1 ) * $per_page,
			'status' => $status,
		];

		$inquiries = apd_get_listing_inquiries( $listing_id, $args );
		$total     = apd_get_listing_inquiry_count( $listing_id, $status );

		$items = [];
		foreach ( $inquiries as $inquiry ) {
			$items[] = $this->prepare_inquiry_for_response( $inquiry );
		}

		return $this->controller->create_paginated_response( $items, $total, $page, $per_page );
	}

	/**
	 * Get a single inquiry.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function get_inquiry( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$inquiry_id = (int) $request->get_param( 'id' );
		$inquiry    = apd_get_inquiry( $inquiry_id );

		if ( ! $inquiry ) {
			return $this->controller->create_error(
				'rest_inquiry_not_found',
				__( 'Inquiry not found.', 'all-purpose-directory' ),
				404
			);
		}

		return $this->controller->create_response(
			$this->prepare_inquiry_for_response( $inquiry )
		);
	}

	/**
	 * Mark inquiry as read.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function mark_read( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$inquiry_id = (int) $request->get_param( 'id' );

		$result = apd_mark_inquiry_read( $inquiry_id );

		if ( ! $result ) {
			return $this->controller->create_error(
				'rest_mark_read_failed',
				__( 'Failed to mark inquiry as read.', 'all-purpose-directory' ),
				500
			);
		}

		$inquiry = apd_get_inquiry( $inquiry_id );

		return $this->controller->create_response(
			$this->prepare_inquiry_for_response( $inquiry )
		);
	}

	/**
	 * Mark inquiry as unread.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function mark_unread( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$inquiry_id = (int) $request->get_param( 'id' );

		$result = apd_mark_inquiry_unread( $inquiry_id );

		if ( ! $result ) {
			return $this->controller->create_error(
				'rest_mark_unread_failed',
				__( 'Failed to mark inquiry as unread.', 'all-purpose-directory' ),
				500
			);
		}

		$inquiry = apd_get_inquiry( $inquiry_id );

		return $this->controller->create_response(
			$this->prepare_inquiry_for_response( $inquiry )
		);
	}

	/**
	 * Delete an inquiry.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function delete_inquiry( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$inquiry_id   = (int) $request->get_param( 'id' );
		$force_delete = (bool) $request->get_param( 'force' );

		$result = apd_delete_inquiry( $inquiry_id, $force_delete );

		if ( ! $result ) {
			return $this->controller->create_error(
				'rest_delete_failed',
				__( 'Failed to delete inquiry.', 'all-purpose-directory' ),
				500
			);
		}

		return $this->controller->create_response(
			[
				'deleted'    => true,
				'inquiry_id' => $inquiry_id,
			]
		);
	}

	/**
	 * Prepare inquiry for response.
	 *
	 * @since 1.0.0
	 *
	 * @param array $inquiry Inquiry data.
	 * @return array Prepared data.
	 */
	public function prepare_inquiry_for_response( array $inquiry ): array {
		$data = [
			'id'         => $inquiry['id'] ?? 0,
			'listing_id' => $inquiry['listing_id'] ?? 0,
			'sender'     => [
				'name'  => $inquiry['sender_name'] ?? '',
				'email' => $inquiry['sender_email'] ?? '',
				'phone' => $inquiry['sender_phone'] ?? '',
			],
			'subject'    => $inquiry['subject'] ?? '',
			'message'    => $inquiry['message'] ?? '',
			'is_read'    => ! empty( $inquiry['is_read'] ),
			'date'       => $inquiry['date'] ?? '',
		];

		// Add listing details if available.
		if ( ! empty( $inquiry['listing_id'] ) ) {
			$post = get_post( $inquiry['listing_id'] );
			if ( $post ) {
				$data['listing'] = [
					'id'    => $post->ID,
					'title' => $post->post_title,
					'link'  => get_permalink( $post ),
				];
			}
		}

		/**
		 * Filters the inquiry data for REST API response.
		 *
		 * @since 1.0.0
		 *
		 * @param array $data    Inquiry data.
		 * @param array $inquiry Original inquiry data.
		 */
		return apply_filters( 'apd_rest_inquiry_data', $data, $inquiry );
	}
}
