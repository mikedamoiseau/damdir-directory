<?php
/**
 * Performance optimization class
 *
 * Provides transient caching and optimization utilities for expensive operations.
 *
 * @package APD\Core
 */

declare(strict_types=1);

namespace APD\Core;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Performance class for caching and optimization
 */
class Performance {

	/**
	 * Singleton instance
	 *
	 * @var Performance|null
	 */
	private static ?Performance $instance = null;

	/**
	 * Transient prefix
	 *
	 * @var string
	 */
	private const TRANSIENT_PREFIX = 'apd_cache_';

	/**
	 * Default cache expiration in seconds (1 hour)
	 *
	 * @var int
	 */
	private const DEFAULT_EXPIRATION = HOUR_IN_SECONDS;

	/**
	 * Cache group for object caching
	 *
	 * @var string
	 */
	private const CACHE_GROUP = 'apd';

	/**
	 * Get singleton instance
	 *
	 * @return Performance
	 */
	public static function get_instance(): Performance {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor for singleton pattern
	 */
	private function __construct() {
		$this->register_hooks();
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
	 * Reset singleton instance (for testing).
	 *
	 * @return void
	 */
	public static function reset_instance(): void {
		self::$instance = null;
	}

	/**
	 * Registry of known cache keys for pattern-based deletion
	 *
	 * @var array<string, true>
	 */
	private array $cache_keys = [];

	/**
	 * New cache keys pending flush to the database
	 *
	 * @var array<string, true>
	 */
	private array $pending_keys = [];

	/**
	 * Whether the shutdown hook has been registered
	 *
	 * @var bool
	 */
	private bool $shutdown_registered = false;

	/**
	 * Register WordPress hooks for cache invalidation
	 *
	 * @return void
	 */
	private function register_hooks(): void {
		// Invalidate category cache on term changes
		add_action( 'created_apd_category', [ $this, 'invalidate_category_cache' ] );
		add_action( 'edited_apd_category', [ $this, 'invalidate_category_cache' ] );
		add_action( 'delete_apd_category', [ $this, 'invalidate_category_cache' ] );

		// Invalidate caches on listing changes
		add_action( 'save_post_apd_listing', [ $this, 'invalidate_listing_caches' ], 10, 2 );
		add_action( 'delete_post', [ $this, 'on_post_delete' ] );
		add_action( 'trashed_post', [ $this, 'on_post_delete' ] );

		// Invalidate related listings cache when categories change
		add_action( 'set_object_terms', [ $this, 'on_terms_changed' ], 10, 4 );

		// Invalidate dashboard stats when reviews change
		add_action( 'apd_review_created', [ $this, 'on_review_created' ], 10, 2 );
		add_action( 'apd_review_deleted', [ $this, 'on_review_deleted' ] );

		// Invalidate dashboard stats when favorites change
		add_action( 'apd_favorite_added', [ $this, 'on_favorite_changed' ] );
		add_action( 'apd_favorite_removed', [ $this, 'on_favorite_changed' ] );
	}

	/**
	 * Get cached value or execute callback and cache result
	 *
	 * @param string   $key        Cache key (will be prefixed).
	 * @param callable $callback   Callback to generate value if not cached.
	 * @param int      $expiration Cache expiration in seconds.
	 * @return mixed Cached or generated value.
	 */
	public function remember( string $key, callable $callback, int $expiration = self::DEFAULT_EXPIRATION ): mixed {
		$cache_key = $this->get_cache_key( $key );

		// Try object cache first (if available)
		$value = wp_cache_get( $cache_key, self::CACHE_GROUP );
		if ( false !== $value ) {
			return $value;
		}

		// Try transient
		$value = get_transient( $cache_key );
		if ( false !== $value ) {
			// Store in object cache for faster subsequent access
			wp_cache_set( $cache_key, $value, self::CACHE_GROUP, $expiration );
			return $value;
		}

		// Generate value
		$value = $callback();

		// Cache the value
		$this->set( $key, $value, $expiration );

		return $value;
	}

	/**
	 * Get cached value
	 *
	 * @param string $key Cache key.
	 * @return mixed|false Cached value or false if not found.
	 */
	public function get( string $key ): mixed {
		$cache_key = $this->get_cache_key( $key );

		// Try object cache first
		$value = wp_cache_get( $cache_key, self::CACHE_GROUP );
		if ( false !== $value ) {
			return $value;
		}

		// Try transient
		return get_transient( $cache_key );
	}

	/**
	 * Set cached value
	 *
	 * @param string $key        Cache key.
	 * @param mixed  $value      Value to cache.
	 * @param int    $expiration Cache expiration in seconds.
	 * @return bool True on success.
	 */
	public function set( string $key, mixed $value, int $expiration = self::DEFAULT_EXPIRATION ): bool {
		$cache_key = $this->get_cache_key( $key );

		// Track key for pattern-based deletion
		$this->register_cache_key( $cache_key );

		// Set in object cache
		wp_cache_set( $cache_key, $value, self::CACHE_GROUP, $expiration );

		// Set in transient for persistence
		return set_transient( $cache_key, $value, $expiration );
	}

	/**
	 * Delete cached value
	 *
	 * @param string $key Cache key.
	 * @return bool True on success.
	 */
	public function delete( string $key ): bool {
		$cache_key = $this->get_cache_key( $key );

		// Remove from registry and pending keys.
		unset( $this->cache_keys[ $cache_key ] );
		unset( $this->pending_keys[ $cache_key ] );

		// Delete from object cache
		wp_cache_delete( $cache_key, self::CACHE_GROUP );

		// Delete transient
		return delete_transient( $cache_key );
	}

	/**
	 * Delete all cached values matching a pattern
	 *
	 * Uses a registry of known cache keys instead of a LIKE query on wp_options.
	 *
	 * @param string $pattern Cache key pattern prefix to match.
	 * @return int Number of deleted entries.
	 */
	public function delete_pattern( string $pattern ): int {
		$full_prefix   = self::TRANSIENT_PREFIX . $pattern;
		$registry      = $this->get_cache_key_registry();
		$deleted       = 0;
		$keys_to_clean = [];

		foreach ( $registry as $cache_key => $_ ) {
			if ( '' === $pattern || str_starts_with( $cache_key, $full_prefix ) ) {
				wp_cache_delete( $cache_key, self::CACHE_GROUP );
				if ( delete_transient( $cache_key ) ) {
					++$deleted;
				}
				$keys_to_clean[] = $cache_key;
			}
		}

		// Remove deleted keys from registry and pending keys.
		if ( ! empty( $keys_to_clean ) ) {
			foreach ( $keys_to_clean as $key ) {
				unset( $registry[ $key ] );
				unset( $this->pending_keys[ $key ] );
				unset( $this->cache_keys[ $key ] );
			}
			update_option( 'apd_cache_key_registry', $registry, false );
		}

		return $deleted;
	}

	/**
	 * Register a cache key in the registry
	 *
	 * Buffers new keys in memory and flushes them to the database
	 * once on shutdown to avoid multiple update_option() calls per request.
	 *
	 * @param string $cache_key Full cache key.
	 * @return void
	 */
	private function register_cache_key( string $cache_key ): void {
		if ( isset( $this->cache_keys[ $cache_key ] ) ) {
			return;
		}

		// Ensure the persisted registry is loaded into cache_keys.
		$this->get_cache_key_registry();

		// If still not known (wasn't in DB), queue for batched flush.
		if ( ! isset( $this->cache_keys[ $cache_key ] ) ) {
			$this->pending_keys[ $cache_key ] = true;
			$this->ensure_shutdown_hook();
		}

		$this->cache_keys[ $cache_key ] = true;
	}

	/**
	 * Flush pending cache keys to the database
	 *
	 * Called on shutdown to batch all new key registrations
	 * into a single update_option() call.
	 *
	 * @return void
	 */
	public function flush_registry(): void {
		if ( empty( $this->pending_keys ) ) {
			return;
		}

		$registry = get_option( 'apd_cache_key_registry', [] );
		if ( ! is_array( $registry ) ) {
			$registry = [];
		}

		$registry = array_merge( $registry, $this->pending_keys );
		update_option( 'apd_cache_key_registry', $registry, false );

		$this->pending_keys = [];
	}

	/**
	 * Register the shutdown hook for flushing pending keys
	 *
	 * @return void
	 */
	private function ensure_shutdown_hook(): void {
		if ( ! $this->shutdown_registered ) {
			add_action( 'shutdown', [ $this, 'flush_registry' ] );
			$this->shutdown_registered = true;
		}
	}

	/**
	 * Get the cache key registry from the database
	 *
	 * @return array<string, true>
	 */
	private function get_cache_key_registry(): array {
		if ( ! empty( $this->cache_keys ) ) {
			return $this->cache_keys;
		}

		$registry = get_option( 'apd_cache_key_registry', [] );
		if ( ! is_array( $registry ) ) {
			$registry = [];
		}

		$this->cache_keys = $registry;
		return $registry;
	}

	/**
	 * Get prefixed cache key
	 *
	 * @param string $key Raw key.
	 * @return string Prefixed key.
	 */
	public function get_cache_key( string $key ): string {
		return self::TRANSIENT_PREFIX . $key;
	}

	/**
	 * Get categories with counts (cached)
	 *
	 * @param array $args Query arguments.
	 * @return array Array of WP_Term objects.
	 */
	public function get_categories_with_counts( array $args = [] ): array {
		$cache_key = 'categories_' . md5( wp_json_encode( $args ) );

		return $this->remember(
			$cache_key,
			function () use ( $args ) {
				$defaults = [
					'taxonomy'   => 'apd_category',
					'hide_empty' => true,
					'orderby'    => 'name',
					'order'      => 'ASC',
				];

				$args  = wp_parse_args( $args, $defaults );
				$terms = get_terms( $args );

				return is_wp_error( $terms ) ? [] : $terms;
			},
			$this->get_expiration( 'categories' )
		);
	}

	/**
	 * Get related listings (cached)
	 *
	 * @param int   $listing_id Listing ID.
	 * @param int   $limit      Number of related listings.
	 * @param array $args       Additional query arguments.
	 * @return array Array of WP_Post objects.
	 */
	public function get_related_listings( int $listing_id, int $limit = 4, array $args = [] ): array {
		$cache_key = "related_{$listing_id}_{$limit}_" . md5( wp_json_encode( $args ) );

		return $this->remember(
			$cache_key,
			function () use ( $listing_id, $limit, $args ) {
				// Get listing categories
				$categories = wp_get_post_terms( $listing_id, 'apd_category', [ 'fields' => 'ids' ] );

				if ( empty( $categories ) || is_wp_error( $categories ) ) {
					return [];
				}

				$query_args = wp_parse_args(
					$args,
					[
						'post_type'      => 'apd_listing',
						'post_status'    => 'publish',
						'posts_per_page' => $limit,
						'post__not_in'   => [ $listing_id ], // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in
						'tax_query'      => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
							[
								'taxonomy' => 'apd_category',
								'field'    => 'term_id',
								'terms'    => $categories,
							],
						],
						'orderby'        => 'date',
						'order'          => 'DESC',
						'no_found_rows'  => true, // Performance optimization
					]
				);

				$query = new \WP_Query( $query_args );

				return $query->posts;
			},
			$this->get_expiration( 'related_listings' )
		);
	}

	/**
	 * Get dashboard stats (cached)
	 *
	 * @param int $user_id User ID.
	 * @return array Dashboard stats array.
	 */
	public function get_dashboard_stats( int $user_id ): array {
		$cache_key = "dashboard_stats_{$user_id}";

		return $this->remember(
			$cache_key,
			function () use ( $user_id ) {
				global $wpdb;

				// Get listing counts by status using a single grouped query
				$counts = [
					'published' => 0,
					'pending'   => 0,
					'draft'     => 0,
					'expired'   => 0,
					'total'     => 0,
				];

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$status_counts = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT post_status, COUNT(*) AS count
						FROM {$wpdb->posts}
						WHERE post_type = 'apd_listing'
						AND post_author = %d
						AND post_status IN ('publish', 'pending', 'draft', 'expired')
						GROUP BY post_status",
						$user_id
					)
				);

				foreach ( $status_counts as $row ) {
					$status = $row->post_status;
					$count  = (int) $row->count;

					if ( 'publish' === $status ) {
						$counts['published'] = $count;
					} elseif ( isset( $counts[ $status ] ) ) {
						$counts[ $status ] = $count;
					}
					$counts['total'] += $count;
				}

				// Get total views
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$total_views = (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COALESCE(SUM(CAST(pm.meta_value AS UNSIGNED)), 0)
                        FROM {$wpdb->postmeta} pm
                        INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                        WHERE p.post_author = %d
                        AND p.post_type = 'apd_listing'
                        AND pm.meta_key = '_apd_views_count'",
						$user_id
					)
				);

				// Get favorites count
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$total_favorites = (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COALESCE(SUM(CAST(pm.meta_value AS UNSIGNED)), 0)
                        FROM {$wpdb->postmeta} pm
                        INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                        WHERE p.post_author = %d
                        AND p.post_type = 'apd_listing'
                        AND pm.meta_key = '_apd_favorite_count'",
						$user_id
					)
				);

				return [
					'listings'  => $counts,
					'views'     => $total_views,
					'favorites' => $total_favorites,
				];
			},
			$this->get_expiration( 'dashboard_stats' )
		);
	}

	/**
	 * Get popular listings (cached)
	 *
	 * @param int   $limit Number of listings.
	 * @param array $args  Additional query arguments.
	 * @return array Array of WP_Post objects.
	 */
	public function get_popular_listings( int $limit = 10, array $args = [] ): array {
		$cache_key = "popular_{$limit}_" . md5( wp_json_encode( $args ) );

		return $this->remember(
			$cache_key,
			function () use ( $limit, $args ) {
				$query_args = wp_parse_args(
					$args,
					[
						'post_type'      => 'apd_listing',
						'post_status'    => 'publish',
						'posts_per_page' => $limit,
						'meta_key'       => '_apd_views_count', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
						'orderby'        => 'meta_value_num',
						'order'          => 'DESC',
						'no_found_rows'  => true,
					]
				);

				$query = new \WP_Query( $query_args );

				return $query->posts;
			},
			$this->get_expiration( 'popular_listings' )
		);
	}

	/**
	 * Get expiration time for a cache type
	 *
	 * @param string $type Cache type.
	 * @return int Expiration in seconds.
	 */
	private function get_expiration( string $type ): int {
		$expirations = [
			'categories'       => HOUR_IN_SECONDS,
			'related_listings' => 15 * MINUTE_IN_SECONDS,
			'dashboard_stats'  => 5 * MINUTE_IN_SECONDS,
			'popular_listings' => 30 * MINUTE_IN_SECONDS,
		];

		/**
		 * Filter cache expiration time
		 *
		 * @param int    $expiration Expiration in seconds.
		 * @param string $type       Cache type.
		 */
		return apply_filters(
			'apd_cache_expiration',
			$expirations[ $type ] ?? self::DEFAULT_EXPIRATION,
			$type
		);
	}

	/**
	 * Invalidate category cache
	 *
	 * @return void
	 */
	public function invalidate_category_cache(): void {
		$this->delete_pattern( 'categories_' );

		/**
		 * Fires when category cache is invalidated
		 */
		do_action( 'apd_category_cache_invalidated' );
	}

	/**
	 * Invalidate listing-related caches
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 * @return void
	 */
	public function invalidate_listing_caches( int $post_id, \WP_Post $post ): void {
		// Clear related listings cache for this listing
		$this->delete_pattern( "related_{$post_id}_" );

		// Clear dashboard stats for the author
		$this->delete( "dashboard_stats_{$post->post_author}" );

		// Clear popular listings cache
		$this->delete_pattern( 'popular_' );

		// Clear category cache (counts may have changed)
		$this->invalidate_category_cache();

		/**
		 * Fires when listing caches are invalidated
		 *
		 * @param int      $post_id Post ID.
		 * @param \WP_Post $post    Post object.
		 */
		do_action( 'apd_listing_cache_invalidated', $post_id, $post );
	}

	/**
	 * Handle post deletion
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function on_post_delete( int $post_id ): void {
		$post = get_post( $post_id );
		if ( $post && 'apd_listing' === $post->post_type ) {
			$this->invalidate_listing_caches( $post_id, $post );
		}
	}

	/**
	 * Handle term changes on posts
	 *
	 * @param int    $object_id  Object ID.
	 * @param array  $terms      Terms.
	 * @param array  $tt_ids     Term taxonomy IDs.
	 * @param string $taxonomy   Taxonomy name.
	 * @return void
	 */
	public function on_terms_changed( int $object_id, array $terms, array $tt_ids, string $taxonomy ): void {
		if ( 'apd_category' !== $taxonomy && 'apd_tag' !== $taxonomy ) {
			return;
		}

		$post = get_post( $object_id );
		if ( $post && 'apd_listing' === $post->post_type ) {
			// Clear related listings cache
			$this->delete_pattern( "related_{$object_id}_" );

			// Clear category cache if categories changed
			if ( 'apd_category' === $taxonomy ) {
				$this->invalidate_category_cache();
			}
		}
	}

	/**
	 * Handle review creation - invalidate dashboard stats for listing author
	 *
	 * @param int $comment_id Comment ID.
	 * @param int $listing_id Listing ID.
	 * @return void
	 */
	public function on_review_created( int $comment_id, int $listing_id ): void {
		$this->invalidate_dashboard_stats_for_listing( $listing_id );
	}

	/**
	 * Handle review deletion - invalidate dashboard stats for listing author
	 *
	 * @param int $review_id Review (comment) ID.
	 * @return void
	 */
	public function on_review_deleted( int $review_id ): void {
		$comment = get_comment( $review_id );
		if ( $comment ) {
			$this->invalidate_dashboard_stats_for_listing( (int) $comment->comment_post_ID );
		}
	}

	/**
	 * Handle favorite change - invalidate dashboard stats for listing author
	 *
	 * @param int $listing_id Listing ID.
	 * @return void
	 */
	public function on_favorite_changed( int $listing_id ): void {
		$this->invalidate_dashboard_stats_for_listing( $listing_id );
	}

	/**
	 * Invalidate dashboard stats cache for a listing's author
	 *
	 * @param int $listing_id Listing ID.
	 * @return void
	 */
	private function invalidate_dashboard_stats_for_listing( int $listing_id ): void {
		$post = get_post( $listing_id );
		if ( $post && 'apd_listing' === $post->post_type ) {
			$this->delete( "dashboard_stats_{$post->post_author}" );
		}
	}

	/**
	 * Clear all plugin caches
	 *
	 * @return int Number of deleted cache entries.
	 */
	public function clear_all(): int {
		$deleted = $this->delete_pattern( '' );

		/**
		 * Fires when all caches are cleared
		 *
		 * @param int $deleted Number of deleted entries.
		 */
		do_action( 'apd_cache_cleared', $deleted );

		return $deleted;
	}
}
