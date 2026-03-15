<?php
/**
 * Favorites Page Dashboard Tab Controller.
 *
 * Handles the Favorites tab in the user dashboard, displaying
 * the user's favorited listings with pagination and view toggle.
 *
 * @package APD\Frontend\Dashboard
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Frontend\Dashboard;

use APD\Listing\PostType;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FavoritesPage
 *
 * @since 1.0.0
 */
class FavoritesPage {

	/**
	 * Number of favorites per page.
	 *
	 * @var int
	 */
	public const PER_PAGE = 12;

	/**
	 * User meta key for view mode preference.
	 *
	 * @var string
	 */
	public const VIEW_MODE_META_KEY = '_apd_favorites_view_mode';

	/**
	 * Valid view modes.
	 *
	 * @var array<string>
	 */
	private const VALID_VIEW_MODES = [ 'grid', 'list' ];

	/**
	 * Default view mode.
	 *
	 * @var string
	 */
	private const DEFAULT_VIEW_MODE = 'grid';

	/**
	 * Current user ID.
	 *
	 * @var int
	 */
	private int $user_id = 0;

	/**
	 * Configuration options.
	 *
	 * @var array<string, mixed>
	 */
	private array $config = [];

	/**
	 * Default configuration.
	 *
	 * @var array<string, mixed>
	 */
	private const DEFAULTS = [
		'per_page'         => self::PER_PAGE,
		'show_view_toggle' => true,
		'columns'          => 4,
	];

	/**
	 * Singleton instance.
	 *
	 * @var FavoritesPage|null
	 */
	private static ?FavoritesPage $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $config Optional. Configuration options.
	 * @return FavoritesPage
	 */
	public static function get_instance( array $config = [] ): FavoritesPage {
		if ( self::$instance === null || ! empty( $config ) ) {
			self::$instance = new self( $config );
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $config Configuration options.
	 */
	private function __construct( array $config = [] ) {
		$this->config  = wp_parse_args( $config, self::DEFAULTS );
		$this->user_id = get_current_user_id();
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
	 * Get the current user ID.
	 *
	 * @since 1.0.0
	 *
	 * @return int User ID.
	 */
	public function get_user_id(): int {
		return $this->user_id;
	}

	/**
	 * Set the user ID.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id User ID.
	 * @return void
	 */
	public function set_user_id( int $user_id ): void {
		$this->user_id = $user_id;
	}

	/**
	 * Get the configuration.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed> Configuration options.
	 */
	public function get_config(): array {
		return $this->config;
	}

	/**
	 * Render the Favorites tab content.
	 *
	 * @since 1.0.0
	 *
	 * @return string Rendered HTML.
	 */
	public function render(): string {
		if ( $this->user_id <= 0 ) {
			return '';
		}

		$paged     = $this->get_current_page();
		$view_mode = $this->get_view_mode();
		$favorites = $this->get_favorites(
			[
				'paged'    => $paged,
				'per_page' => $this->config['per_page'],
			]
		);

		$args = [
			'favorites_page' => $this,
			'favorites'      => $favorites,
			'view_mode'      => $view_mode,
			'paged'          => $paged,
			'total'          => $favorites->found_posts,
			'max_pages'      => $favorites->max_num_pages,
			'user_id'        => $this->user_id,
			'config'         => $this->config,
		];

		/**
		 * Filter the Favorites Page template arguments.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, mixed> $args Template arguments.
		 */
		$args = apply_filters( 'apd_favorites_page_args', $args );

		ob_start();
		\apd_get_template( 'dashboard/favorites.php', $args );
		return ob_get_clean();
	}

	/**
	 * Get user's favorited listings with pagination.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $args Query arguments.
	 *                                   - paged: (int) Page number.
	 *                                   - per_page: (int) Items per page.
	 * @return \WP_Query Query result.
	 */
	public function get_favorites( array $args = [] ): \WP_Query {
		$defaults = [
			'paged'    => 1,
			'per_page' => $this->config['per_page'],
		];

		$args = wp_parse_args( $args, $defaults );

		// Get favorite listing IDs.
		$favorite_ids = \apd_get_user_favorites( $this->user_id );

		// Return empty query if no favorites.
		if ( empty( $favorite_ids ) ) {
			return new \WP_Query(
				[
					'post_type'      => PostType::POST_TYPE,
					'post__in'       => [ 0 ], // Force empty result.
					'posts_per_page' => 1,
				]
			);
		}

		$query_args = [
			'post_type'      => PostType::POST_TYPE,
			'post_status'    => 'publish',
			'post__in'       => $favorite_ids,
			'orderby'        => 'post__in', // Maintain favorite order.
			'posts_per_page' => $args['per_page'],
			'paged'          => $args['paged'],
		];

		/**
		 * Filter the Favorites Page query arguments.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, mixed> $query_args WP_Query arguments.
		 * @param int                  $user_id    User ID.
		 */
		$query_args = apply_filters( 'apd_favorites_page_query_args', $query_args, $this->user_id );

		return new \WP_Query( $query_args );
	}

	/**
	 * Get the current page number from URL.
	 *
	 * @since 1.0.0
	 *
	 * @return int Current page number.
	 */
	public function get_current_page(): int {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Just reading page parameter.
		return isset( $_GET['fav_page'] ) ? max( 1, absint( $_GET['fav_page'] ) ) : 1;
	}

	/**
	 * Get the current view mode (grid or list).
	 *
	 * Priority: URL parameter > User meta > Default.
	 *
	 * @since 1.0.0
	 *
	 * @return string View mode ('grid' or 'list').
	 */
	public function get_view_mode(): string {
		// Check URL parameter first.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Just reading view parameter for display.
		if ( isset( $_GET['view'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$view = sanitize_key( $_GET['view'] );
			if ( in_array( $view, self::VALID_VIEW_MODES, true ) ) {
				// Save preference to user meta.
				$this->save_view_mode( $view );
				return $view;
			}
		}

		// Check user meta.
		if ( $this->user_id > 0 ) {
			$saved_view = get_user_meta( $this->user_id, self::VIEW_MODE_META_KEY, true );
			if ( in_array( $saved_view, self::VALID_VIEW_MODES, true ) ) {
				return $saved_view;
			}
		}

		return self::DEFAULT_VIEW_MODE;
	}

	/**
	 * Save view mode preference to user meta.
	 *
	 * @since 1.0.0
	 *
	 * @param string $view_mode View mode to save.
	 * @return bool True on success.
	 */
	public function save_view_mode( string $view_mode ): bool {
		if ( $this->user_id <= 0 ) {
			return false;
		}

		if ( ! in_array( $view_mode, self::VALID_VIEW_MODES, true ) ) {
			return false;
		}

		return (bool) update_user_meta( $this->user_id, self::VIEW_MODE_META_KEY, $view_mode );
	}

	/**
	 * Get the total count of favorites for the current user.
	 *
	 * @since 1.0.0
	 *
	 * @return int Favorites count.
	 */
	public function get_favorites_count(): int {
		return \apd_get_favorites_count( $this->user_id );
	}

	/**
	 * Check if user has any favorites.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if user has favorites.
	 */
	public function has_favorites(): bool {
		return $this->get_favorites_count() > 0;
	}

	/**
	 * Get URL for a specific view mode.
	 *
	 * @since 1.0.0
	 *
	 * @param string $view_mode View mode (grid or list).
	 * @return string View mode URL.
	 */
	public function get_view_mode_url( string $view_mode ): string {
		return add_query_arg(
			[
				'view'     => $view_mode,
				'fav_page' => 1, // Reset to page 1 when changing view.
			]
		);
	}

	/**
	 * Get the archive URL for browsing listings.
	 *
	 * @since 1.0.0
	 *
	 * @return string Archive URL.
	 */
	public function get_listings_archive_url(): string {
		$archive_url = get_post_type_archive_link( PostType::POST_TYPE );

		/**
		 * Filter the listings archive URL shown in empty favorites.
		 *
		 * @since 1.0.0
		 *
		 * @param string $archive_url The archive URL.
		 */
		return apply_filters( 'apd_favorites_empty_browse_url', $archive_url ?: home_url() );
	}

	/**
	 * Get grid view configuration.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed> Grid view configuration.
	 */
	public function get_grid_config(): array {
		return [
			'columns'       => $this->config['columns'],
			'show_favorite' => true,
			'show_image'    => true,
			'show_excerpt'  => true,
			'show_category' => true,
		];
	}
}
