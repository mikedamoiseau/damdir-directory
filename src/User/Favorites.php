<?php
/**
 * Favorites Manager Class.
 *
 * Handles user favorites for listings, storing data in user meta for logged-in
 * users and optionally in cookies for guests.
 *
 * @package APD\User
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\User;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Favorites
 *
 * @since 1.0.0
 */
class Favorites {
	/**
	 * Maximum retries for optimistic compare-and-swap usermeta updates.
	 *
	 * @var int
	 */
	private const USER_META_CAS_MAX_RETRIES = 5;


	/**
	 * User meta key for storing favorites.
	 *
	 * @var string
	 */
	public const META_KEY = '_apd_favorites';

	/**
	 * Listing meta key for storing favorite count.
	 *
	 * @var string
	 */
	public const LISTING_META_KEY = '_apd_favorite_count';

	/**
	 * Cookie name for guest favorites.
	 *
	 * @var string
	 */
	public const COOKIE_NAME = 'apd_guest_favorites';

	/**
	 * Cookie expiration in days.
	 *
	 * @var int
	 */
	public const COOKIE_EXPIRY_DAYS = 30;

	/**
	 * Singleton instance.
	 *
	 * @var Favorites|null
	 */
	private static ?Favorites $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @since 1.0.0
	 *
	 * @return Favorites
	 */
	public static function get_instance(): Favorites {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {}

	/**
	 * Prevent cloning.
	 */
	private function __clone() {}

	/**
	 * Prevent unserialization.
	 *
	 * @throws \Exception Always throws exception.
	 */
	public function __wakeup(): void {
		throw new \Exception( 'Cannot unserialize singleton.' );
	}

	/**
	 * Reset singleton instance (for testing).
	 *
	 * @return void
	 */
	public static function reset_instance(): void {
		self::$instance = null;
	}

	/**
	 * Initialize the favorites system.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init(): void {
		// Merge guest favorites on user login.
		add_action( 'wp_login', [ $this, 'merge_guest_favorites_on_login' ], 10, 2 );

		/**
		 * Fires after the favorites system is initialized.
		 *
		 * @since 1.0.0
		 *
		 * @param Favorites $favorites The Favorites instance.
		 */
		do_action( 'apd_favorites_init', $this );
	}

	/**
	 * Add a listing to favorites.
	 *
	 * @since 1.0.0
	 *
	 * @param int      $listing_id Listing post ID.
	 * @param int|null $user_id    User ID. Defaults to current user.
	 * @return bool True if added successfully, false otherwise.
	 */
	public function add( int $listing_id, ?int $user_id = null ): bool {
		// Validate listing.
		if ( ! $this->is_valid_listing( $listing_id ) ) {
			return false;
		}

		$user_id = $this->resolve_user_id( $user_id );

		// Check if login is required and user is not logged in.
		if ( $user_id === 0 && $this->requires_login() ) {
			return false;
		}

		// Handle guest favorites.
		if ( $user_id === 0 ) {
			return $this->add_guest_favorite( $listing_id );
		}

		$result = $this->mutate_user_favorites(
			$user_id,
			static function ( array $favorites ) use ( $listing_id ): array {
				if ( in_array( $listing_id, $favorites, true ) ) {
					return $favorites;
				}

				$favorites[] = $listing_id;
				return $favorites;
			}
		);

		if ( ! $result['success'] ) {
			return false;
		}

		if ( $result['changed'] ) {
			// Increment listing favorite count.
			$this->increment_listing_count( $listing_id );

			/**
			 * Fires when a listing is added to favorites.
			 *
			 * @since 1.0.0
			 *
			 * @param int $listing_id The listing post ID.
			 * @param int $user_id    The user ID.
			 */
			do_action( 'apd_favorite_added', $listing_id, $user_id );
		}

		return true;
	}

	/**
	 * Remove a listing from favorites.
	 *
	 * @since 1.0.0
	 *
	 * @param int      $listing_id Listing post ID.
	 * @param int|null $user_id    User ID. Defaults to current user.
	 * @return bool True if removed successfully, false otherwise.
	 */
	public function remove( int $listing_id, ?int $user_id = null ): bool {
		$user_id = $this->resolve_user_id( $user_id );

		// Check if login is required and user is not logged in.
		if ( $user_id === 0 && $this->requires_login() ) {
			return false;
		}

		// Handle guest favorites.
		if ( $user_id === 0 ) {
			return $this->remove_guest_favorite( $listing_id );
		}

		$result = $this->mutate_user_favorites(
			$user_id,
			static function ( array $favorites ) use ( $listing_id ): array {
				if ( ! in_array( $listing_id, $favorites, true ) ) {
					return $favorites;
				}

				return array_values( array_diff( $favorites, [ $listing_id ] ) );
			}
		);

		if ( ! $result['success'] ) {
			return false;
		}

		if ( $result['changed'] ) {
			// Decrement listing favorite count.
			$this->decrement_listing_count( $listing_id );

			/**
			 * Fires when a listing is removed from favorites.
			 *
			 * @since 1.0.0
			 *
			 * @param int $listing_id The listing post ID.
			 * @param int $user_id    The user ID.
			 */
			do_action( 'apd_favorite_removed', $listing_id, $user_id );
		}

		return true;
	}

	/**
	 * Toggle a listing's favorite status.
	 *
	 * @since 1.0.0
	 *
	 * @param int      $listing_id Listing post ID.
	 * @param int|null $user_id    User ID. Defaults to current user.
	 * @return bool|null The new favorite state (true = favorited, false = removed), or null on error.
	 */
	public function toggle( int $listing_id, ?int $user_id = null ): ?bool {
		// Validate listing.
		if ( ! $this->is_valid_listing( $listing_id ) ) {
			return null;
		}

		$user_id = $this->resolve_user_id( $user_id );

		// Check if login is required and user is not logged in.
		if ( $user_id === 0 && $this->requires_login() ) {
			return null;
		}

		if ( $this->is_favorite( $listing_id, $user_id ) ) {
			return $this->remove( $listing_id, $user_id ) ? false : null;
		}

		return $this->add( $listing_id, $user_id ) ? true : null;
	}

	/**
	 * Check if a listing is favorited by a user.
	 *
	 * @since 1.0.0
	 *
	 * @param int      $listing_id Listing post ID.
	 * @param int|null $user_id    User ID. Defaults to current user.
	 * @return bool True if favorited, false otherwise.
	 */
	public function is_favorite( int $listing_id, ?int $user_id = null ): bool {
		$user_id = $this->resolve_user_id( $user_id );

		// Check if login is required and user is not logged in.
		if ( $user_id === 0 && $this->requires_login() ) {
			return false;
		}

		$favorites = $this->get_favorites( $user_id );

		return in_array( $listing_id, $favorites, true );
	}

	/**
	 * Get all favorite listing IDs for a user.
	 *
	 * @since 1.0.0
	 *
	 * @param int|null $user_id User ID. Defaults to current user.
	 * @return int[] Array of listing IDs.
	 */
	public function get_favorites( ?int $user_id = null ): array {
		$user_id = $this->resolve_user_id( $user_id );

		// Check if login is required and user is not logged in.
		if ( $user_id === 0 && $this->requires_login() ) {
			return [];
		}

		// Guest favorites.
		if ( $user_id === 0 ) {
			return $this->get_guest_favorites();
		}

		// Get from user meta.
		$favorites = get_user_meta( $user_id, self::META_KEY, true );

		if ( ! is_array( $favorites ) ) {
			return [];
		}

		// Ensure all values are integers.
		return array_values( array_map( 'absint', $favorites ) );
	}

	/**
	 * Get the total count of favorites for a user.
	 *
	 * @since 1.0.0
	 *
	 * @param int|null $user_id User ID. Defaults to current user.
	 * @return int Favorites count.
	 */
	public function get_count( ?int $user_id = null ): int {
		return count( $this->get_favorites( $user_id ) );
	}

	/**
	 * Get the number of users who have favorited a listing.
	 *
	 * @since 1.0.0
	 *
	 * @param int $listing_id Listing post ID.
	 * @return int Favorite count.
	 */
	public function get_listing_favorite_count( int $listing_id ): int {
		return absint( get_post_meta( $listing_id, self::LISTING_META_KEY, true ) );
	}

	/**
	 * Clear all favorites for a user.
	 *
	 * @since 1.0.0
	 *
	 * @param int|null $user_id User ID. Defaults to current user.
	 * @return bool True if cleared successfully.
	 */
	public function clear( ?int $user_id = null ): bool {
		$user_id = $this->resolve_user_id( $user_id );

		// Check if login is required and user is not logged in.
		if ( $user_id === 0 && $this->requires_login() ) {
			return false;
		}

		// Handle guest favorites.
		if ( $user_id === 0 ) {
			return $this->clear_guest_favorites();
		}

		// Get current favorites to decrement counts.
		$favorites = $this->get_favorites( $user_id );

		// Decrement listing counts.
		foreach ( $favorites as $listing_id ) {
			$this->decrement_listing_count( $listing_id );
		}

		// Clear user meta.
		$result = delete_user_meta( $user_id, self::META_KEY );

		if ( $result ) {
			/**
			 * Fires when all favorites are cleared for a user.
			 *
			 * @since 1.0.0
			 *
			 * @param int $user_id The user ID.
			 */
			do_action( 'apd_favorites_cleared', $user_id );
		}

		return $result;
	}

	/**
	 * Check if login is required for favorites.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if login is required.
	 */
	public function requires_login(): bool {
		$require_login = ! $this->guest_favorites_enabled();

		/**
		 * Filter whether login is required for favorites.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $require_login Whether login is required.
		 */
		return apply_filters( 'apd_favorites_require_login', $require_login );
	}

	/**
	 * Check if guest favorites are enabled.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if guest favorites are enabled.
	 */
	public function guest_favorites_enabled(): bool {
		/**
		 * Filter whether guest favorites are enabled.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $enabled Whether guest favorites are enabled.
		 */
		return apply_filters( 'apd_guest_favorites_enabled', false );
	}

	/**
	 * Recalculate the favorite count for a listing.
	 *
	 * This is useful for fixing counts that may have gotten out of sync.
	 *
	 * @since 1.0.0
	 *
	 * @param int $listing_id Listing post ID.
	 * @return int The recalculated count.
	 */
	public function recalculate_listing_count( int $listing_id ): int {
		global $wpdb;

		// Count users who have this listing in their favorites.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->usermeta}
				WHERE meta_key = %s
				AND (
					meta_value LIKE %s
					OR meta_value LIKE %s
					OR meta_value LIKE %s
				)",
				self::META_KEY,
				'%i:' . $listing_id . ';%',
				'%"' . $listing_id . '"%',
				'%:' . $listing_id . ';%'
			)
		);

		$count = absint( $count );

		// Update the listing meta.
		update_post_meta( $listing_id, self::LISTING_META_KEY, $count );

		return $count;
	}

	/**
	 * Merge guest favorites into user account on login.
	 *
	 * @since 1.0.0
	 *
	 * @param string   $user_login The username.
	 * @param \WP_User $user       The user object.
	 * @return void
	 */
	public function merge_guest_favorites_on_login( string $user_login, \WP_User $user ): void {
		if ( ! $this->guest_favorites_enabled() ) {
			return;
		}

		$guest_favorites = $this->get_guest_favorites();

		if ( empty( $guest_favorites ) ) {
			return;
		}

		// Get existing user favorites.
		$user_favorites = $this->get_favorites( $user->ID );

		// Merge, avoiding duplicates.
		$merged = array_values( array_unique( array_merge( $user_favorites, $guest_favorites ) ) );

		// Update counts for newly added favorites.
		$new_favorites = array_diff( $guest_favorites, $user_favorites );
		foreach ( $new_favorites as $listing_id ) {
			if ( $this->is_valid_listing( $listing_id ) ) {
				$this->increment_listing_count( $listing_id );
			}
		}

		// Filter out invalid listings.
		$merged = array_filter( $merged, [ $this, 'is_valid_listing' ] );

		// Save merged favorites.
		$this->save_user_favorites( $user->ID, $merged );

		// Clear guest favorites.
		$this->clear_guest_favorites();
	}

	/**
	 * Resolve the user ID.
	 *
	 * @since 1.0.0
	 *
	 * @param int|null $user_id User ID or null for current user.
	 * @return int Resolved user ID (0 for guests).
	 */
	private function resolve_user_id( ?int $user_id ): int {
		if ( $user_id === null ) {
			return get_current_user_id();
		}

		return max( 0, $user_id );
	}

	/**
	 * Validate that a listing exists and is viewable.
	 *
	 * @since 1.0.0
	 *
	 * @param int $listing_id Listing post ID.
	 * @return bool True if valid listing.
	 */
	private function is_valid_listing( int $listing_id ): bool {
		if ( $listing_id <= 0 ) {
			return false;
		}

		$post = get_post( $listing_id );

		if ( ! $post ) {
			return false;
		}

		// Must be a listing post type.
		if ( $post->post_type !== 'apd_listing' ) {
			return false;
		}

		// Must be published or viewable by current user.
		if ( $post->post_status === 'publish' ) {
			return true;
		}

		// Check if current user is the author.
		$current_user_id = get_current_user_id();
		if ( $current_user_id > 0 && (int) $post->post_author === $current_user_id ) {
			return true;
		}

		// Check if user can edit posts (admin/editor).
		if ( current_user_can( 'edit_others_posts' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Save user favorites to user meta.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $user_id   User ID.
	 * @param int[] $favorites Array of listing IDs.
	 * @return bool True on success.
	 */
	private function save_user_favorites( int $user_id, array $favorites ): bool {
		// Ensure all values are integers.
		$favorites = $this->normalize_favorites( $favorites );

		return (bool) update_user_meta( $user_id, self::META_KEY, $favorites );
	}

	/**
	 * Mutate user favorites using optimistic compare-and-swap retries.
	 *
	 * @since 1.0.0
	 *
	 * @param int      $user_id User ID.
	 * @param callable $mutator Receives current favorites array and returns next favorites array.
	 * @return array{success: bool, changed: bool}
	 */
	private function mutate_user_favorites( int $user_id, callable $mutator ): array {
		for ( $attempt = 0; $attempt < self::USER_META_CAS_MAX_RETRIES; $attempt++ ) {
			$current_raw       = get_user_meta( $user_id, self::META_KEY, true );
			$current_favorites = $this->normalize_favorites( $current_raw );
			$next_favorites    = $this->normalize_favorites( $mutator( $current_favorites ) );

			// Nothing to update.
			if ( $next_favorites === $current_favorites ) {
				return [
					'success' => true,
					'changed' => false,
				];
			}

			// Meta does not exist yet: try atomic create.
			if ( ! metadata_exists( 'user', $user_id, self::META_KEY ) ) {
				$created = add_user_meta( $user_id, self::META_KEY, $next_favorites, true );
				if ( false !== $created ) {
					return [
						'success' => true,
						'changed' => true,
					];
				}

				// Another request may have created it first, retry with fresh value.
				continue;
			}

			$updated = update_user_meta( $user_id, self::META_KEY, $next_favorites, $current_raw );
			if ( false !== $updated ) {
				return [
					'success' => true,
					'changed' => true,
				];
			}
		}

		return [
			'success' => false,
			'changed' => false,
		];
	}

	/**
	 * Normalize favorites payload into a unique list of positive listing IDs.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $favorites Raw favorites value.
	 * @return int[] Normalized favorite listing IDs.
	 */
	private function normalize_favorites( mixed $favorites ): array {
		if ( ! is_array( $favorites ) ) {
			return [];
		}

		$normalized = [];
		foreach ( $favorites as $favorite ) {
			$listing_id = absint( $favorite );
			if ( $listing_id > 0 ) {
				$normalized[] = $listing_id;
			}
		}

		return array_values( array_unique( $normalized ) );
	}

	/**
	 * Increment the listing favorite count atomically.
	 *
	 * Uses a direct SQL UPDATE to avoid race conditions from concurrent requests.
	 *
	 * @since 1.0.0
	 *
	 * @param int $listing_id Listing post ID.
	 * @return void
	 */
	private function increment_listing_count( int $listing_id ): void {
		global $wpdb;

		// Ensure the meta key exists first.
		if ( ! metadata_exists( 'post', $listing_id, self::LISTING_META_KEY ) ) {
			update_post_meta( $listing_id, self::LISTING_META_KEY, 1 );
			return;
		}

		// Atomic increment.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->postmeta}
				SET meta_value = CAST(meta_value AS UNSIGNED) + 1
				WHERE post_id = %d AND meta_key = %s",
				$listing_id,
				self::LISTING_META_KEY
			)
		);

		// Clear the object cache for this meta so subsequent reads are fresh.
		wp_cache_delete( $listing_id, 'post_meta' );
	}

	/**
	 * Decrement the listing favorite count atomically.
	 *
	 * Uses a direct SQL UPDATE to avoid race conditions from concurrent requests.
	 * Ensures the count never goes below zero.
	 *
	 * @since 1.0.0
	 *
	 * @param int $listing_id Listing post ID.
	 * @return void
	 */
	private function decrement_listing_count( int $listing_id ): void {
		global $wpdb;

		// Ensure the meta key exists.
		if ( ! metadata_exists( 'post', $listing_id, self::LISTING_META_KEY ) ) {
			update_post_meta( $listing_id, self::LISTING_META_KEY, 0 );
			return;
		}

		// Atomic decrement with floor of 0.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->postmeta}
				SET meta_value = GREATEST(CAST(meta_value AS UNSIGNED) - 1, 0)
				WHERE post_id = %d AND meta_key = %s",
				$listing_id,
				self::LISTING_META_KEY
			)
		);

		// Clear the object cache for this meta so subsequent reads are fresh.
		wp_cache_delete( $listing_id, 'post_meta' );
	}

	/**
	 * Get guest favorites from cookie.
	 *
	 * @since 1.0.0
	 *
	 * @return int[] Array of listing IDs.
	 */
	private function get_guest_favorites(): array {
		if ( ! isset( $_COOKIE[ self::COOKIE_NAME ] ) ) {
			return [];
		}

		$cookie_value = sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ) );
		$favorites    = json_decode( $cookie_value, true );

		if ( ! is_array( $favorites ) ) {
			return [];
		}

		// Ensure all values are integers.
		return array_values( array_map( 'absint', $favorites ) );
	}

	/**
	 * Add a guest favorite.
	 *
	 * @since 1.0.0
	 *
	 * @param int $listing_id Listing post ID.
	 * @return bool True on success.
	 */
	private function add_guest_favorite( int $listing_id ): bool {
		$favorites = $this->get_guest_favorites();

		// Already favorited.
		if ( in_array( $listing_id, $favorites, true ) ) {
			return true;
		}

		$favorites[] = $listing_id;

		$result = $this->save_guest_favorites( $favorites );

		if ( $result ) {
			$this->increment_listing_count( $listing_id );

			/**
			 * Fires when a listing is added to favorites.
			 *
			 * @since 1.0.0
			 *
			 * @param int $listing_id The listing post ID.
			 * @param int $user_id    The user ID (0 for guests).
			 */
			do_action( 'apd_favorite_added', $listing_id, 0 );
		}

		return $result;
	}

	/**
	 * Remove a guest favorite.
	 *
	 * @since 1.0.0
	 *
	 * @param int $listing_id Listing post ID.
	 * @return bool True on success.
	 */
	private function remove_guest_favorite( int $listing_id ): bool {
		$favorites = $this->get_guest_favorites();

		// Not favorited.
		if ( ! in_array( $listing_id, $favorites, true ) ) {
			return true;
		}

		$favorites = array_values( array_diff( $favorites, [ $listing_id ] ) );

		$result = $this->save_guest_favorites( $favorites );

		if ( $result ) {
			$this->decrement_listing_count( $listing_id );

			/**
			 * Fires when a listing is removed from favorites.
			 *
			 * @since 1.0.0
			 *
			 * @param int $listing_id The listing post ID.
			 * @param int $user_id    The user ID (0 for guests).
			 */
			do_action( 'apd_favorite_removed', $listing_id, 0 );
		}

		return $result;
	}

	/**
	 * Save guest favorites to cookie.
	 *
	 * @since 1.0.0
	 *
	 * @param int[] $favorites Array of listing IDs.
	 * @return bool True on success.
	 */
	private function save_guest_favorites( array $favorites ): bool {
		// Ensure all values are integers.
		$favorites = array_values( array_map( 'absint', $favorites ) );

		$cookie_value = wp_json_encode( $favorites );
		$expiry       = time() + ( self::COOKIE_EXPIRY_DAYS * DAY_IN_SECONDS );

		if ( ! headers_sent() ) {
			setcookie(
				self::COOKIE_NAME,
				$cookie_value,
				[
					'expires'  => $expiry,
					'path'     => COOKIEPATH,
					'domain'   => COOKIE_DOMAIN,
					'secure'   => is_ssl(),
					'httponly' => true,
					'samesite' => 'Lax',
				]
			);
		}

		// Also update $_COOKIE for immediate access.
		$_COOKIE[ self::COOKIE_NAME ] = $cookie_value;

		return true;
	}

	/**
	 * Clear guest favorites.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True on success.
	 */
	private function clear_guest_favorites(): bool {
		if ( ! headers_sent() ) {
			setcookie(
				self::COOKIE_NAME,
				'',
				[
					'expires'  => time() - 3600,
					'path'     => COOKIEPATH,
					'domain'   => COOKIE_DOMAIN,
					'secure'   => is_ssl(),
					'httponly' => true,
					'samesite' => 'Lax',
				]
			);
		}

		unset( $_COOKIE[ self::COOKIE_NAME ] );

		/**
		 * Fires when all favorites are cleared for a user.
		 *
		 * @since 1.0.0
		 *
		 * @param int $user_id The user ID (0 for guests).
		 */
		do_action( 'apd_favorites_cleared', 0 );

		return true;
	}
}
