<?php
/**
 * Demo Data Tracker Class.
 *
 * Tracks and manages demo data for cleanup purposes.
 * Items are tracked with module slugs for per-module generation and deletion.
 *
 * @package APD\Admin\DemoData
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Admin\DemoData;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DemoDataTracker
 *
 * Tracks demo data items and provides cleanup functionality.
 * Each item is marked with _apd_demo_data = module_slug (e.g., 'general', 'url-directory').
 * Users are always marked with 'users' since they are shared across modules.
 *
 * @since 1.0.0
 */
final class DemoDataTracker {

	/**
	 * Meta key used to mark items as demo data.
	 */
	public const META_KEY = '_apd_demo_data';

	/**
	 * Meta value for shared demo users.
	 */
	public const USERS_MODULE = 'users';

	/**
	 * Meta value for general (core) demo data.
	 */
	public const GENERAL_MODULE = 'general';

	/**
	 * Singleton instance.
	 *
	 * @var DemoDataTracker|null
	 */
	private static ?DemoDataTracker $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @since 1.0.0
	 *
	 * @return DemoDataTracker
	 */
	public static function get_instance(): DemoDataTracker {
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
	 * Mark a post as demo data.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $post_id Post ID.
	 * @param string $module  Module slug (default: 'general').
	 * @return bool Whether the meta was added successfully.
	 */
	public function mark_post_as_demo( int $post_id, string $module = self::GENERAL_MODULE ): bool {
		return (bool) update_post_meta( $post_id, self::META_KEY, $module );
	}

	/**
	 * Mark a term as demo data.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $term_id Term ID.
	 * @param string $module  Module slug (default: 'general').
	 * @return bool Whether the meta was added successfully.
	 */
	public function mark_term_as_demo( int $term_id, string $module = self::GENERAL_MODULE ): bool {
		return (bool) update_term_meta( $term_id, self::META_KEY, $module );
	}

	/**
	 * Mark a user as demo data.
	 *
	 * Users are always marked as 'users' since they are shared across modules.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id User ID.
	 * @return bool Whether the meta was added successfully.
	 */
	public function mark_user_as_demo( int $user_id ): bool {
		return (bool) update_user_meta( $user_id, self::META_KEY, self::USERS_MODULE );
	}

	/**
	 * Mark a comment (review) as demo data.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $comment_id Comment ID.
	 * @param string $module     Module slug (default: 'general').
	 * @return bool Whether the meta was added successfully.
	 */
	public function mark_comment_as_demo( int $comment_id, string $module = self::GENERAL_MODULE ): bool {
		return (bool) update_comment_meta( $comment_id, self::META_KEY, $module );
	}

	/**
	 * Check if a post is demo data.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public function is_demo_post( int $post_id ): bool {
		$value = get_post_meta( $post_id, self::META_KEY, true );
		return ! empty( $value );
	}

	/**
	 * Check if a term is demo data.
	 *
	 * @since 1.0.0
	 *
	 * @param int $term_id Term ID.
	 * @return bool
	 */
	public function is_demo_term( int $term_id ): bool {
		$value = get_term_meta( $term_id, self::META_KEY, true );
		return ! empty( $value );
	}

	/**
	 * Check if a user is demo data.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	public function is_demo_user( int $user_id ): bool {
		$value = get_user_meta( $user_id, self::META_KEY, true );
		return ! empty( $value );
	}

	/**
	 * Check if a comment is demo data.
	 *
	 * @since 1.0.0
	 *
	 * @param int $comment_id Comment ID.
	 * @return bool
	 */
	public function is_demo_comment( int $comment_id ): bool {
		$value = get_comment_meta( $comment_id, self::META_KEY, true );
		return ! empty( $value );
	}

	/**
	 * Count demo data items by type, optionally filtered by module.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $module Module slug to filter by, or null for all modules.
	 * @return array{users: int, categories: int, tags: int, listings: int, reviews: int, inquiries: int}
	 */
	public function count_demo_data( ?string $module = null ): array {
		global $wpdb;

		// These aggregate counts rely on optimized direct SQL joins for admin-only demo data tooling.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		// Count demo users (always filtered by 'users' module).
		if ( $module === null || $module === self::USERS_MODULE ) {
			$users = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value = %s",
					self::META_KEY,
					self::USERS_MODULE
				)
			);
		} else {
			$users = 0;
		}

		// Determine module filter for term/post/comment queries.
		$filter_by_module = ( $module !== null && $module !== self::USERS_MODULE );

		// Count demo categories.
		if ( $filter_by_module ) {
			$categories = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->termmeta} tm
					INNER JOIN {$wpdb->term_taxonomy} tt ON tm.term_id = tt.term_id
					WHERE tm.meta_key = %s AND tm.meta_value = %s AND tt.taxonomy = %s",
					self::META_KEY,
					$module,
					'apd_category'
				)
			);
		} else {
			$categories = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->termmeta} tm
					INNER JOIN {$wpdb->term_taxonomy} tt ON tm.term_id = tt.term_id
					WHERE tm.meta_key = %s AND tm.meta_value != %s AND tt.taxonomy = %s",
					self::META_KEY,
					self::USERS_MODULE,
					'apd_category'
				)
			);
		}

		// Count demo tags.
		if ( $filter_by_module ) {
			$tags = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->termmeta} tm
					INNER JOIN {$wpdb->term_taxonomy} tt ON tm.term_id = tt.term_id
					WHERE tm.meta_key = %s AND tm.meta_value = %s AND tt.taxonomy = %s",
					self::META_KEY,
					$module,
					'apd_tag'
				)
			);
		} else {
			$tags = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->termmeta} tm
					INNER JOIN {$wpdb->term_taxonomy} tt ON tm.term_id = tt.term_id
					WHERE tm.meta_key = %s AND tm.meta_value != %s AND tt.taxonomy = %s",
					self::META_KEY,
					self::USERS_MODULE,
					'apd_tag'
				)
			);
		}

		// Count demo listings.
		if ( $filter_by_module ) {
			$listings = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->postmeta} pm
					INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
					WHERE pm.meta_key = %s AND pm.meta_value = %s AND p.post_type = %s",
					self::META_KEY,
					$module,
					'apd_listing'
				)
			);
		} else {
			$listings = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->postmeta} pm
					INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
					WHERE pm.meta_key = %s AND pm.meta_value != %s AND p.post_type = %s",
					self::META_KEY,
					self::USERS_MODULE,
					'apd_listing'
				)
			);
		}

		// Count demo reviews.
		if ( $filter_by_module ) {
			$reviews = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->commentmeta} cm
					INNER JOIN {$wpdb->comments} c ON cm.comment_id = c.comment_ID
					WHERE cm.meta_key = %s AND cm.meta_value = %s AND c.comment_type = %s",
					self::META_KEY,
					$module,
					'apd_review'
				)
			);
		} else {
			$reviews = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->commentmeta} cm
					INNER JOIN {$wpdb->comments} c ON cm.comment_id = c.comment_ID
					WHERE cm.meta_key = %s AND cm.meta_value != %s AND c.comment_type = %s",
					self::META_KEY,
					self::USERS_MODULE,
					'apd_review'
				)
			);
		}

		// Count demo inquiries.
		if ( $filter_by_module ) {
			$inquiries = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->postmeta} pm
					INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
					WHERE pm.meta_key = %s AND pm.meta_value = %s AND p.post_type = %s",
					self::META_KEY,
					$module,
					'apd_inquiry'
				)
			);
		} else {
			$inquiries = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->postmeta} pm
					INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
					WHERE pm.meta_key = %s AND pm.meta_value != %s AND p.post_type = %s",
					self::META_KEY,
					self::USERS_MODULE,
					'apd_inquiry'
				)
			);
		}

		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return [
			'users'      => $users,
			'categories' => $categories,
			'tags'       => $tags,
			'listings'   => $listings,
			'reviews'    => $reviews,
			'inquiries'  => $inquiries,
		];
	}

	/**
	 * Get IDs of demo posts by post type, optionally filtered by module.
	 *
	 * @since 1.0.0
	 *
	 * @param string      $post_type Post type.
	 * @param string|null $module    Module slug to filter by, or null for all.
	 * @return int[]
	 */
	public function get_demo_post_ids( string $post_type, ?string $module = null ): array {
		global $wpdb;

		if ( $module !== null ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT p.ID FROM {$wpdb->posts} p
					INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
					WHERE pm.meta_key = %s AND pm.meta_value = %s AND p.post_type = %s",
					self::META_KEY,
					$module,
					$post_type
				)
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT p.ID FROM {$wpdb->posts} p
					INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
					WHERE pm.meta_key = %s AND p.post_type = %s",
					self::META_KEY,
					$post_type
				)
			);
		}

		return array_map( 'intval', $ids );
	}

	/**
	 * Get IDs of demo terms by taxonomy, optionally filtered by module.
	 *
	 * @since 1.0.0
	 *
	 * @param string      $taxonomy Taxonomy name.
	 * @param string|null $module   Module slug to filter by, or null for all.
	 * @return int[]
	 */
	public function get_demo_term_ids( string $taxonomy, ?string $module = null ): array {
		global $wpdb;

		if ( $module !== null ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT t.term_id FROM {$wpdb->terms} t
					INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
					INNER JOIN {$wpdb->termmeta} tm ON t.term_id = tm.term_id
					WHERE tm.meta_key = %s AND tm.meta_value = %s AND tt.taxonomy = %s",
					self::META_KEY,
					$module,
					$taxonomy
				)
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT t.term_id FROM {$wpdb->terms} t
					INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
					INNER JOIN {$wpdb->termmeta} tm ON t.term_id = tm.term_id
					WHERE tm.meta_key = %s AND tt.taxonomy = %s",
					self::META_KEY,
					$taxonomy
				)
			);
		}

		return array_map( 'intval', $ids );
	}

	/**
	 * Get IDs of demo users.
	 *
	 * @since 1.0.0
	 *
	 * @return int[]
	 */
	public function get_demo_user_ids(): array {
		global $wpdb;

		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value = %s",
				self::META_KEY,
				self::USERS_MODULE
			)
		);

		return array_map( 'intval', $ids );
	}

	/**
	 * Get IDs of demo comments, optionally filtered by type and module.
	 *
	 * @since 1.0.0
	 *
	 * @param string      $comment_type Comment type (e.g., 'apd_review').
	 * @param string|null $module       Module slug to filter by, or null for all.
	 * @return int[]
	 */
	public function get_demo_comment_ids( string $comment_type = '', ?string $module = null ): array {
		global $wpdb;

		if ( ! empty( $comment_type ) && $module !== null ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT c.comment_ID FROM {$wpdb->comments} c
					INNER JOIN {$wpdb->commentmeta} cm ON c.comment_ID = cm.comment_id
					WHERE cm.meta_key = %s AND cm.meta_value = %s AND c.comment_type = %s",
					self::META_KEY,
					$module,
					$comment_type
				)
			);
		} elseif ( ! empty( $comment_type ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT c.comment_ID FROM {$wpdb->comments} c
					INNER JOIN {$wpdb->commentmeta} cm ON c.comment_ID = cm.comment_id
					WHERE cm.meta_key = %s AND c.comment_type = %s",
					self::META_KEY,
					$comment_type
				)
			);
		} elseif ( $module !== null ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT cm.comment_id FROM {$wpdb->commentmeta} cm
					WHERE cm.meta_key = %s AND cm.meta_value = %s",
					self::META_KEY,
					$module
				)
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT cm.comment_id FROM {$wpdb->commentmeta} cm
					WHERE cm.meta_key = %s",
					self::META_KEY
				)
			);
		}

		return array_map( 'intval', $ids );
	}

	/**
	 * Delete demo users.
	 *
	 * @since 1.0.0
	 *
	 * @return int Number of users deleted.
	 */
	public function delete_demo_users(): int {
		require_once ABSPATH . 'wp-admin/includes/user.php';

		$user_ids = $this->get_demo_user_ids();
		$deleted  = 0;

		foreach ( $user_ids as $user_id ) {
			if ( wp_delete_user( $user_id, 1 ) ) {
				++$deleted;
			}
		}

		return $deleted;
	}

	/**
	 * Delete demo categories, optionally filtered by module.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $module Module slug to filter by, or null for all.
	 * @return int Number of categories deleted.
	 */
	public function delete_demo_categories( ?string $module = null ): int {
		$term_ids = $this->get_demo_term_ids( 'apd_category', $module );
		$deleted  = 0;

		foreach ( $term_ids as $term_id ) {
			$result = wp_delete_term( $term_id, 'apd_category' );
			if ( $result && ! is_wp_error( $result ) ) {
				++$deleted;
			}
		}

		return $deleted;
	}

	/**
	 * Delete demo tags, optionally filtered by module.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $module Module slug to filter by, or null for all.
	 * @return int Number of tags deleted.
	 */
	public function delete_demo_tags( ?string $module = null ): int {
		$term_ids = $this->get_demo_term_ids( 'apd_tag', $module );
		$deleted  = 0;

		foreach ( $term_ids as $term_id ) {
			$result = wp_delete_term( $term_id, 'apd_tag' );
			if ( $result && ! is_wp_error( $result ) ) {
				++$deleted;
			}
		}

		return $deleted;
	}

	/**
	 * Delete demo listings, optionally filtered by module.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $module Module slug to filter by, or null for all.
	 * @return int Number of listings deleted.
	 */
	public function delete_demo_listings( ?string $module = null ): int {
		$post_ids = $this->get_demo_post_ids( 'apd_listing', $module );
		$deleted  = 0;

		foreach ( $post_ids as $post_id ) {
			$result = wp_delete_post( $post_id, true );
			if ( $result ) {
				++$deleted;
			}
		}

		return $deleted;
	}

	/**
	 * Delete demo reviews, optionally filtered by module.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $module Module slug to filter by, or null for all.
	 * @return int Number of reviews deleted.
	 */
	public function delete_demo_reviews( ?string $module = null ): int {
		$comment_ids = $this->get_demo_comment_ids( 'apd_review', $module );
		$deleted     = 0;

		foreach ( $comment_ids as $comment_id ) {
			$result = wp_delete_comment( $comment_id, true );
			if ( $result ) {
				++$deleted;
			}
		}

		return $deleted;
	}

	/**
	 * Delete demo inquiries, optionally filtered by module.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $module Module slug to filter by, or null for all.
	 * @return int Number of inquiries deleted.
	 */
	public function delete_demo_inquiries( ?string $module = null ): int {
		$post_ids = $this->get_demo_post_ids( 'apd_inquiry', $module );
		$deleted  = 0;

		foreach ( $post_ids as $post_id ) {
			$result = wp_delete_post( $post_id, true );
			if ( $result ) {
				++$deleted;
			}
		}

		return $deleted;
	}

	/**
	 * Clear user favorites that reference demo listings for a specific module.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $module Module slug to filter listings by, or null for all.
	 * @return int Number of favorites cleared.
	 */
	public function clear_demo_favorites( ?string $module = null ): int {
		global $wpdb;

		$demo_listing_ids = $this->get_demo_post_ids( 'apd_listing', $module );
		if ( empty( $demo_listing_ids ) ) {
			return 0;
		}

		$cleared = 0;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$users_with_favorites = $wpdb->get_col(
			"SELECT DISTINCT user_id FROM {$wpdb->usermeta} WHERE meta_key = '_apd_favorites'"
		);

		foreach ( $users_with_favorites as $user_id ) {
			$favorites = get_user_meta( (int) $user_id, '_apd_favorites', true );
			if ( ! is_array( $favorites ) || empty( $favorites ) ) {
				continue;
			}

			$original_count = count( $favorites );
			$favorites      = array_diff( $favorites, $demo_listing_ids );
			$new_count      = count( $favorites );

			if ( $new_count !== $original_count ) {
				update_user_meta( (int) $user_id, '_apd_favorites', array_values( $favorites ) );
				$cleared += ( $original_count - $new_count );
			}
		}

		return $cleared;
	}

	/**
	 * Delete all demo data for a specific module.
	 *
	 * Deletes in dependency order: reviews -> inquiries -> favorites -> listings -> tags -> categories.
	 * Does NOT delete users (they are shared across modules).
	 *
	 * @since 1.2.0
	 *
	 * @param string $module Module slug to delete data for.
	 * @return array<string, int> Deleted counts keyed by type.
	 */
	public function delete_by_module( string $module ): array {
		$counts = [];

		// Delete module provider data first (may reference core data).
		$provider_registry = DemoDataProviderRegistry::get_instance();
		$provider          = $provider_registry->get( $module );

		if ( $provider ) {
			$provider_counts = $provider->delete( $this );
			foreach ( $provider_counts as $type => $type_count ) {
				$counts[ 'module_' . $module . '_' . $type ] = $type_count;
			}
		}

		// Delete core data in dependency order.
		$counts['reviews']    = $this->delete_demo_reviews( $module );
		$counts['inquiries']  = $this->delete_demo_inquiries( $module );
		$counts['favorites']  = $this->clear_demo_favorites( $module );
		$counts['listings']   = $this->delete_demo_listings( $module );
		$counts['tags']       = $this->delete_demo_tags( $module );
		$counts['categories'] = $this->delete_demo_categories( $module );

		return $counts;
	}

	/**
	 * Delete all demo data across all modules and users.
	 *
	 * Deletes in dependency order: module data -> reviews -> inquiries -> favorites ->
	 * listings -> tags -> categories -> users.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, int> Deleted counts keyed by type.
	 */
	public function delete_all(): array {
		/**
		 * Fires before demo data deletion begins.
		 *
		 * @since 1.0.0
		 */
		do_action( 'apd_before_delete_demo_data' );

		$counts = [];

		// Delete module provider demo data first (may reference core data).
		$provider_registry = DemoDataProviderRegistry::get_instance();
		foreach ( $provider_registry->get_all() as $slug => $provider ) {
			$provider_counts = $provider->delete( $this );
			foreach ( $provider_counts as $type => $type_count ) {
				$counts[ 'module_' . $slug . '_' . $type ] = $type_count;
			}
		}

		// Delete core data in dependency order (all modules).
		$counts['reviews']    = $this->delete_demo_reviews();
		$counts['inquiries']  = $this->delete_demo_inquiries();
		$counts['favorites']  = $this->clear_demo_favorites();
		$counts['listings']   = $this->delete_demo_listings();
		$counts['tags']       = $this->delete_demo_tags();
		$counts['categories'] = $this->delete_demo_categories();
		$counts['users']      = $this->delete_demo_users();

		/**
		 * Fires after demo data deletion completes.
		 *
		 * @since 1.0.0
		 *
		 * @param array $counts Number of items deleted by type.
		 */
		do_action( 'apd_after_delete_demo_data', $counts );

		return $counts;
	}

	/**
	 * Check if any module (excluding users) has demo data.
	 *
	 * Used to determine if the "Delete Users" button should be enabled.
	 *
	 * @since 1.2.0
	 *
	 * @return bool True if any non-user demo data exists.
	 */
	public function has_module_demo_data(): bool {
		global $wpdb;

		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value != %s LIMIT 1",
				self::META_KEY,
				self::USERS_MODULE
			)
		);

		if ( $count > 0 ) {
			return true;
		}

		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->termmeta} WHERE meta_key = %s AND meta_value != %s LIMIT 1",
				self::META_KEY,
				self::USERS_MODULE
			)
		);

		if ( $count > 0 ) {
			return true;
		}

		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->commentmeta} WHERE meta_key = %s AND meta_value != %s LIMIT 1",
				self::META_KEY,
				self::USERS_MODULE
			)
		);

		return $count > 0;
	}

	/**
	 * Count demo data from module providers.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array<string, int>> Counts keyed by provider slug, then type.
	 */
	public function count_module_demo_data(): array {
		$provider_registry = DemoDataProviderRegistry::get_instance();
		$module_counts     = [];

		foreach ( $provider_registry->get_all() as $slug => $provider ) {
			$module_counts[ $slug ] = $provider->count( $this );
		}

		return $module_counts;
	}

	/**
	 * Reset singleton instance for testing.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function reset_instance(): void {
		self::$instance = null;
	}
}
