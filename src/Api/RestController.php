<?php
/**
 * REST API Controller Class.
 *
 * Provides REST API functionality for the All Purpose Directory plugin.
 * Registers the API namespace and handles authentication/permissions.
 *
 * @package APD\Api
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Api;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RestController
 *
 * Main REST API controller that handles namespace registration,
 * authentication, and permission management.
 *
 * @since 1.0.0
 */
final class RestController {

	/**
	 * REST API namespace.
	 */
	public const NAMESPACE = 'apd/v1';

	/**
	 * API version.
	 */
	public const VERSION = 'v1';

	/**
	 * Nonce action for REST API requests.
	 */
	public const NONCE_ACTION = 'wp_rest';

	/**
	 * Singleton instance.
	 *
	 * @var RestController|null
	 */
	private static ?RestController $instance = null;

	/**
	 * Registered endpoint controllers.
	 *
	 * @var array<string, object>
	 */
	private array $endpoints = [];

	/**
	 * Whether the controller has been initialized.
	 *
	 * @var bool
	 */
	private bool $initialized = false;

	/**
	 * Get the singleton instance.
	 *
	 * @since 1.0.0
	 *
	 * @return RestController
	 */
	public static function get_instance(): RestController {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Reset the singleton instance (for testing).
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function reset_instance(): void {
		self::$instance = null;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		// Private constructor for singleton pattern.
	}

	/**
	 * Prevent unserialization.
	 *
	 * @throws \Exception Always throws exception.
	 */
	public function __wakeup(): void {
		throw new \Exception( 'Cannot unserialize singleton.' );
	}

	/**
	 * Initialize the REST API controller.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init(): void {
		if ( $this->initialized ) {
			return;
		}

		add_action( 'rest_api_init', [ $this, 'register_routes' ] );

		/**
		 * Fires after the REST API controller is initialized.
		 *
		 * @since 1.0.0
		 *
		 * @param RestController $controller The REST controller instance.
		 */
		do_action( 'apd_rest_api_init', $this );

		$this->initialized = true;
	}

	/**
	 * Check if the controller has been initialized.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_initialized(): bool {
		return $this->initialized;
	}

	/**
	 * Register REST API routes.
	 *
	 * This method is called on the 'rest_api_init' action.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_routes(): void {
		/**
		 * Fires before REST API routes are registered.
		 *
		 * Use this hook to register custom endpoint controllers.
		 *
		 * @since 1.0.0
		 *
		 * @param RestController $controller The REST controller instance.
		 */
		do_action( 'apd_register_rest_routes', $this );

		// Register endpoint controllers.
		foreach ( $this->endpoints as $endpoint ) {
			if ( method_exists( $endpoint, 'register_routes' ) ) {
				$endpoint->register_routes();
			}
		}

		/**
		 * Fires after REST API routes are registered.
		 *
		 * @since 1.0.0
		 *
		 * @param RestController $controller The REST controller instance.
		 */
		do_action( 'apd_rest_routes_registered', $this );
	}

	/**
	 * Get the API namespace.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_namespace(): string {
		return self::NAMESPACE;
	}

	/**
	 * Get the API version.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_version(): string {
		return self::VERSION;
	}

	/**
	 * Register an endpoint controller.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name     Endpoint identifier.
	 * @param object $endpoint Endpoint controller instance.
	 * @return void
	 */
	public function register_endpoint( string $name, object $endpoint ): void {
		$this->endpoints[ $name ] = $endpoint;

		/**
		 * Fires after an endpoint controller is registered.
		 *
		 * @since 1.0.0
		 *
		 * @param string $name     Endpoint identifier.
		 * @param object $endpoint Endpoint controller instance.
		 */
		do_action( 'apd_rest_endpoint_registered', $name, $endpoint );
	}

	/**
	 * Unregister an endpoint controller.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name Endpoint identifier.
	 * @return bool True if endpoint was removed, false if it didn't exist.
	 */
	public function unregister_endpoint( string $name ): bool {
		if ( ! isset( $this->endpoints[ $name ] ) ) {
			return false;
		}

		unset( $this->endpoints[ $name ] );

		/**
		 * Fires after an endpoint controller is unregistered.
		 *
		 * @since 1.0.0
		 *
		 * @param string $name Endpoint identifier.
		 */
		do_action( 'apd_rest_endpoint_unregistered', $name );

		return true;
	}

	/**
	 * Get a registered endpoint controller.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name Endpoint identifier.
	 * @return object|null Endpoint controller or null if not found.
	 */
	public function get_endpoint( string $name ): ?object {
		return $this->endpoints[ $name ] ?? null;
	}

	/**
	 * Get all registered endpoint controllers.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, object>
	 */
	public function get_endpoints(): array {
		return $this->endpoints;
	}

	/**
	 * Check if an endpoint is registered.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name Endpoint identifier.
	 * @return bool
	 */
	public function has_endpoint( string $name ): bool {
		return isset( $this->endpoints[ $name ] );
	}

	/**
	 * Build a full REST URL for an endpoint.
	 *
	 * @since 1.0.0
	 *
	 * @param string $route Endpoint route (without namespace).
	 * @return string Full REST URL.
	 */
	public function get_rest_url( string $route = '' ): string {
		$route = ltrim( $route, '/' );
		$path  = $route ? self::NAMESPACE . '/' . $route : self::NAMESPACE;

		return rest_url( $path );
	}

	/**
	 * Verify a REST API nonce.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request $request The REST request.
	 * @return bool True if nonce is valid, false otherwise.
	 */
	public function verify_nonce( \WP_REST_Request $request ): bool {
		$nonce = $request->get_header( 'X-WP-Nonce' );

		if ( empty( $nonce ) ) {
			return false;
		}

		return wp_verify_nonce( $nonce, self::NONCE_ACTION ) !== false;
	}

	/**
	 * Get the current user from a REST request.
	 *
	 * Uses cookie authentication when available.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request $request The REST request.
	 * @return \WP_User|null Current user or null if not authenticated.
	 */
	public function get_current_user( \WP_REST_Request $request ): ?\WP_User {
		// Try to get the current user (may be set via cookie or application password).
		$user_id = get_current_user_id();

		if ( $user_id > 0 ) {
			$user = get_userdata( $user_id );
			return $user instanceof \WP_User ? $user : null;
		}

		return null;
	}

	/**
	 * Check if the current request is authenticated.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request $request The REST request.
	 * @return bool True if authenticated, false otherwise.
	 */
	public function is_authenticated( \WP_REST_Request $request ): bool {
		return get_current_user_id() > 0;
	}

	/**
	 * Check if the current request uses cookie-based authentication.
	 *
	 * Non-cookie auth (e.g. Application Passwords, OAuth, Basic Auth) does not
	 * need nonce verification because credentials themselves prove intent.
	 *
	 * Important: Do not treat a mere Authorization header as sufficient to skip
	 * nonce checks if WordPress login cookies are also present. When WordPress
	 * cookies are present, treat the request as cookie-authenticated (CSRF risk).
	 *
	 * @since 1.1.0
	 *
	 * @param \WP_REST_Request $request The REST request.
	 * @return bool True if request should be treated as cookie-authenticated.
	 */
	public function is_cookie_auth( \WP_REST_Request $request ): bool {
		// If WordPress login cookies are present, treat as cookie-auth even if other auth signals exist.
		if ( $this->has_wp_login_cookies() ) {
			return true;
		}

		// No WP cookies: if we detect non-cookie auth credentials, skip nonce (not cookie auth).
		if ( $this->has_non_cookie_auth_credentials( $request ) ) {
			return false;
		}

		// Default to cookie-auth behavior (nonce required for mutating endpoints).
		return true;
	}

	/**
	 * Detect whether the request has non-cookie auth credentials.
	 *
	 * @since 1.1.0
	 *
	 * @param \WP_REST_Request $request The REST request.
	 * @return bool True if non-cookie credentials appear present.
	 */
	private function has_non_cookie_auth_credentials( \WP_REST_Request $request ): bool {
		$auth_header = (string) $request->get_header( 'Authorization' );
		if ( $auth_header !== '' ) {
			return true;
		}

		// Some server setups populate auth credentials in $_SERVER instead of passing the header through.
		$server_auth_keys = [
			'PHP_AUTH_USER',
			'PHP_AUTH_PW',
			'AUTH_TYPE',
			'REMOTE_USER',
			'REDIRECT_REMOTE_USER',
			'HTTP_AUTHORIZATION',
			'REDIRECT_HTTP_AUTHORIZATION',
		];

		foreach ( $server_auth_keys as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Detect whether WordPress login cookies are present.
	 *
	 * @since 1.1.0
	 *
	 * @return bool True if a WordPress auth cookie name is present.
	 */
	private function has_wp_login_cookies(): bool {
		if ( empty( $_COOKIE ) || ! is_array( $_COOKIE ) ) {
			return false;
		}

		$cookie_names = array_keys( $_COOKIE );
		foreach ( $cookie_names as $cookie_name ) {
			$cookie_name = (string) $cookie_name;

			// LOGGED_IN_COOKIE, SECURE_AUTH_COOKIE, AUTH_COOKIE (hash suffix is COOKIEHASH, usually 32 hex chars).
			if ( preg_match( '/^wordpress_logged_in_[a-f0-9]{32}$/i', $cookie_name ) ) {
				return true;
			}
			if ( preg_match( '/^wordpress_sec_[a-f0-9]{32}$/i', $cookie_name ) ) {
				return true;
			}
			if ( preg_match( '/^wordpress_[a-f0-9]{32}$/i', $cookie_name ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Permission callback: Public access (anyone can access).
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request $request The REST request.
	 * @return bool Always returns true.
	 */
	public function permission_public( \WP_REST_Request $request ): bool {
		return true;
	}

	/**
	 * Permission callback: Authenticated users only.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request $request The REST request.
	 * @return bool|\WP_Error True if authenticated, WP_Error otherwise.
	 */
	public function permission_authenticated( \WP_REST_Request $request ): bool|\WP_Error {
		if ( ! $this->is_authenticated( $request ) ) {
			return new \WP_Error(
				'rest_not_logged_in',
				__( 'You must be logged in to access this endpoint.', 'all-purpose-directory' ),
				[ 'status' => 401 ]
			);
		}

		return true;
	}

	/**
	 * Permission callback: Authenticated users with CSRF protection.
	 *
	 * Cookie-authenticated requests must include a valid X-WP-Nonce header
	 * (CSRF protection). Non-cookie requests (Authorization header, e.g.
	 * Application Passwords) skip the nonce check because credentials
	 * already prove intent.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request $request               The REST request.
	 * @param string|null      $not_logged_in_message Optional custom not-logged-in message.
	 * @return bool|\WP_Error True if authenticated and nonce is valid, WP_Error otherwise.
	 */
	public function permission_authenticated_with_nonce( \WP_REST_Request $request, ?string $not_logged_in_message = null ): bool|\WP_Error {
		if ( ! $this->is_authenticated( $request ) ) {
			return new \WP_Error(
				'rest_not_logged_in',
				$not_logged_in_message ?? __( 'You must be logged in to access this endpoint.', 'all-purpose-directory' ),
				[ 'status' => 401 ]
			);
		}

		// Only require nonce for cookie-auth requests (CSRF protection).
		// Non-cookie auth (Application Passwords, OAuth) uses the Authorization
		// header, so the credentials themselves prove intent.
		if ( $this->is_cookie_auth( $request ) && ! $this->verify_nonce( $request ) ) {
			return new \WP_Error(
				'rest_nonce_invalid',
				__( 'Invalid or missing REST API nonce.', 'all-purpose-directory' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Permission callback: Users who can create listings.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request $request The REST request.
	 * @return bool|\WP_Error True if user can create listings, WP_Error otherwise.
	 */
	public function permission_create_listing( \WP_REST_Request $request ): bool|\WP_Error {
		$auth_check = $this->permission_authenticated_with_nonce(
			$request,
			__( 'You must be logged in to create listings.', 'all-purpose-directory' )
		);
		if ( is_wp_error( $auth_check ) ) {
			return $auth_check;
		}

		if ( ! current_user_can( 'edit_apd_listings' ) ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to create listings.', 'all-purpose-directory' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Permission callback: Listing owner or admin.
	 *
	 * Checks if the current user is the owner of the listing or has
	 * admin privileges to edit others' listings.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request $request The REST request (must contain 'id' param).
	 * @return bool|\WP_Error True if user can edit, WP_Error otherwise.
	 */
	public function permission_edit_listing( \WP_REST_Request $request ): bool|\WP_Error {
		$auth_check = $this->permission_authenticated_with_nonce(
			$request,
			__( 'You must be logged in to edit listings.', 'all-purpose-directory' )
		);
		if ( is_wp_error( $auth_check ) ) {
			return $auth_check;
		}

		$listing_id = (int) $request->get_param( 'id' );

		if ( $listing_id <= 0 ) {
			return new \WP_Error(
				'rest_invalid_param',
				__( 'Invalid listing ID.', 'all-purpose-directory' ),
				[ 'status' => 400 ]
			);
		}

		$listing = get_post( $listing_id );

		if ( ! $listing || $listing->post_type !== 'apd_listing' ) {
			return new \WP_Error(
				'rest_listing_not_found',
				__( 'Listing not found.', 'all-purpose-directory' ),
				[ 'status' => 404 ]
			);
		}

		// Check if user can edit this specific listing.
		if ( ! current_user_can( 'edit_post', $listing_id ) ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to edit this listing.', 'all-purpose-directory' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Permission callback: Listing owner, admin, or can delete.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request $request The REST request (must contain 'id' param).
	 * @return bool|\WP_Error True if user can delete, WP_Error otherwise.
	 */
	public function permission_delete_listing( \WP_REST_Request $request ): bool|\WP_Error {
		$auth_check = $this->permission_authenticated_with_nonce(
			$request,
			__( 'You must be logged in to delete listings.', 'all-purpose-directory' )
		);
		if ( is_wp_error( $auth_check ) ) {
			return $auth_check;
		}

		$listing_id = (int) $request->get_param( 'id' );

		if ( $listing_id <= 0 ) {
			return new \WP_Error(
				'rest_invalid_param',
				__( 'Invalid listing ID.', 'all-purpose-directory' ),
				[ 'status' => 400 ]
			);
		}

		$listing = get_post( $listing_id );

		if ( ! $listing || $listing->post_type !== 'apd_listing' ) {
			return new \WP_Error(
				'rest_listing_not_found',
				__( 'Listing not found.', 'all-purpose-directory' ),
				[ 'status' => 404 ]
			);
		}

		// Check if user can delete this specific listing.
		if ( ! current_user_can( 'delete_post', $listing_id ) ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to delete this listing.', 'all-purpose-directory' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Permission callback: Admin users only.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request $request The REST request.
	 * @return bool|\WP_Error True if user is admin, WP_Error otherwise.
	 */
	public function permission_admin( \WP_REST_Request $request ): bool|\WP_Error {
		if ( ! $this->is_authenticated( $request ) ) {
			return new \WP_Error(
				'rest_not_logged_in',
				__( 'You must be logged in to access this endpoint.', 'all-purpose-directory' ),
				[ 'status' => 401 ]
			);
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to access this endpoint.', 'all-purpose-directory' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Permission callback: Can manage listings (edit others' listings).
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request $request The REST request.
	 * @return bool|\WP_Error True if user can manage listings, WP_Error otherwise.
	 */
	public function permission_manage_listings( \WP_REST_Request $request ): bool|\WP_Error {
		if ( ! $this->is_authenticated( $request ) ) {
			return new \WP_Error(
				'rest_not_logged_in',
				__( 'You must be logged in to manage listings.', 'all-purpose-directory' ),
				[ 'status' => 401 ]
			);
		}

		if ( ! current_user_can( 'edit_others_apd_listings' ) ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to manage listings.', 'all-purpose-directory' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Create a standardized REST response.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $data    Response data.
	 * @param int   $status  HTTP status code.
	 * @param array $headers Optional. Additional headers.
	 * @return \WP_REST_Response
	 */
	public function create_response( mixed $data, int $status = 200, array $headers = [] ): \WP_REST_Response {
		$response = new \WP_REST_Response( $data, $status );

		foreach ( $headers as $key => $value ) {
			$response->header( $key, $value );
		}

		return $response;
	}

	/**
	 * Create a standardized error response.
	 *
	 * @since 1.0.0
	 *
	 * @param string $code    Error code.
	 * @param string $message Error message.
	 * @param int    $status  HTTP status code.
	 * @param array  $data    Optional. Additional error data.
	 * @return \WP_Error
	 */
	public function create_error( string $code, string $message, int $status = 400, array $data = [] ): \WP_Error {
		$data['status'] = $status;

		return new \WP_Error( $code, $message, $data );
	}

	/**
	 * Create a paginated response.
	 *
	 * @since 1.0.0
	 *
	 * @param array $items       Items for the current page.
	 * @param int   $total       Total number of items.
	 * @param int   $page        Current page number.
	 * @param int   $per_page    Items per page.
	 * @param array $extra_data  Optional. Additional data to include in response.
	 * @return \WP_REST_Response
	 */
	public function create_paginated_response(
		array $items,
		int $total,
		int $page,
		int $per_page,
		array $extra_data = []
	): \WP_REST_Response {
		$max_pages = (int) ceil( $total / max( 1, $per_page ) );

		$data = array_merge(
			[
				'items'     => $items,
				'total'     => $total,
				'page'      => $page,
				'per_page'  => $per_page,
				'max_pages' => $max_pages,
			],
			$extra_data
		);

		$response = $this->create_response( $data );

		// Add pagination headers.
		$response->header( 'X-WP-Total', (string) $total );
		$response->header( 'X-WP-TotalPages', (string) $max_pages );

		return $response;
	}
}
