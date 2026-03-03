<?php
/**
 * Asset management for All Purpose Directory.
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
 * Class Assets
 *
 * Handles registration and enqueueing of plugin CSS and JavaScript assets.
 */
class Assets {

	/**
	 * Plugin version for cache busting.
	 *
	 * @var string
	 */
	private string $version;

	/**
	 * Plugin URL for asset paths.
	 *
	 * @var string
	 */
	private string $plugin_url;

	/**
	 * Constructor.
	 *
	 * @param string $version    Plugin version.
	 * @param string $plugin_url Plugin URL.
	 */
	public function __construct( string $version, string $plugin_url ) {
		$this->version    = $version;
		$this->plugin_url = $plugin_url;
	}

	/**
	 * Initialize asset hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_assets' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
	}

	/**
	 * Enqueue frontend assets.
	 *
	 * @return void
	 */
	public function enqueue_frontend_assets(): void {
		// Only enqueue on pages that need it.
		if ( ! $this->should_load_frontend_assets() ) {
			return;
		}

		wp_enqueue_style(
			'apd-frontend',
			$this->plugin_url . 'assets/css/frontend.css',
			[],
			$this->version
		);

		// Output custom CSS from admin settings.
		$custom_css = \apd_get_option( 'custom_css', '' );
		if ( ! empty( trim( $custom_css ) ) ) {
			wp_add_inline_style( 'apd-frontend', $custom_css );
		}

		wp_enqueue_script(
			'apd-frontend',
			$this->plugin_url . 'assets/js/frontend.js',
			[],
			$this->version,
			true
		);

		wp_localize_script( 'apd-frontend', 'apdFrontend', $this->get_frontend_script_data() );
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @return void
	 */
	public function enqueue_admin_assets(): void {
		$screen = get_current_screen();

		// Only load on plugin-related screens.
		if ( ! $screen || ! $this->is_plugin_admin_screen( $screen ) ) {
			return;
		}

		wp_enqueue_style(
			'apd-admin',
			$this->plugin_url . 'assets/css/admin.css',
			[],
			$this->version
		);

		wp_enqueue_script(
			'apd-admin',
			$this->plugin_url . 'assets/js/admin.js',
			[ 'jquery' ],
			$this->version,
			true
		);

		wp_localize_script( 'apd-admin', 'apdAdmin', $this->get_admin_script_data() );
	}

	/**
	 * Check if frontend assets should be loaded.
	 *
	 * @return bool
	 */
	private function should_load_frontend_assets(): bool {
		// Load on listing archives and singles.
		if ( is_post_type_archive( 'apd_listing' ) || is_singular( 'apd_listing' ) ) {
			return true;
		}

		// Load on listing taxonomy archives.
		if ( is_tax( 'apd_category' ) || is_tax( 'apd_tag' ) ) {
			return true;
		}

		// Load on pages containing APD shortcodes or blocks.
		if ( $this->current_post_has_apd_content() ) {
			return true;
		}

		/**
		 * Filter whether to load frontend assets.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $should_load Whether to load assets.
		 */
		return apply_filters( 'apd_should_load_frontend_assets', false );
	}

	/**
	 * Check if the current post/page contains APD shortcodes or blocks.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	private function current_post_has_apd_content(): bool {
		global $post;

		if ( ! $post instanceof \WP_Post ) {
			return false;
		}

		$content = $post->post_content;

		// Check for APD shortcodes.
		$shortcodes = [
			'apd_listings',
			'apd_search_form',
			'apd_categories',
			'apd_submission_form',
			'apd_dashboard',
			'apd_favorites',
			'apd_login_form',
			'apd_register_form',
		];

		foreach ( $shortcodes as $shortcode ) {
			if ( has_shortcode( $content, $shortcode ) ) {
				return true;
			}
		}

		// Check for APD blocks.
		$blocks = [
			'apd/listings',
			'apd/search-form',
			'apd/categories',
		];

		foreach ( $blocks as $block ) {
			if ( has_block( $block, $post ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if current screen is a plugin admin screen.
	 *
	 * @param \WP_Screen $screen Current screen object.
	 * @return bool
	 */
	private function is_plugin_admin_screen( \WP_Screen $screen ): bool {
		$plugin_screens = [
			'apd_listing',
			'edit-apd_listing',
			'edit-apd_category',
			'edit-apd_tag',
			'toplevel_page_apd-settings',
		];

		if ( in_array( $screen->id, $plugin_screens, true ) ) {
			return true;
		}

		// Check for any APD-prefixed screen.
		if ( str_starts_with( $screen->id, 'apd_' ) ) {
			return true;
		}

		/**
		 * Filter whether to load admin assets on the current screen.
		 *
		 * @since 1.0.0
		 *
		 * @param bool       $is_plugin_screen Whether this is a plugin screen.
		 * @param \WP_Screen $screen           Current screen object.
		 */
		return apply_filters( 'apd_is_plugin_admin_screen', false, $screen );
	}

	/**
	 * Get data to localize for frontend scripts.
	 *
	 * @return array<string, mixed>
	 */
	private function get_frontend_script_data(): array {
		$data = [
			'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
			'nonce'       => wp_create_nonce( 'apd_frontend' ),
			'filterNonce' => wp_create_nonce( 'apd_filter_listings' ),
			'archiveUrl'  => get_post_type_archive_link( 'apd_listing' ) ?: '',
			'i18n'        => [
				'loading'             => __( 'Loading...', 'all-purpose-directory' ),
				'error'               => __( 'An error occurred. Please try again.', 'all-purpose-directory' ),
				'noResults'           => __( 'No listings found.', 'all-purpose-directory' ),
				'addToFavorites'      => __( 'Add to favorites', 'all-purpose-directory' ),
				'removeFromFavorites' => __( 'Remove from favorites', 'all-purpose-directory' ),
				'favoriteAdded'       => __( 'Added to favorites', 'all-purpose-directory' ),
				'favoriteRemoved'     => __( 'Removed from favorites', 'all-purpose-directory' ),
				'favoriteError'       => __( 'Could not update favorites. Please try again.', 'all-purpose-directory' ),
				'filtering'           => __( 'Filtering listings...', 'all-purpose-directory' ),
				/* translators: %d: Number of listings found */
				'resultsFound'        => __( '%d listings found', 'all-purpose-directory' ),
				'oneResultFound'      => __( '1 listing found', 'all-purpose-directory' ),
			],
		];

		/**
		 * Filter frontend script localization data.
		 *
		 * @since 1.0.0
		 *
		 * @param array $data Localization data.
		 */
		return apply_filters( 'apd_frontend_script_data', $data );
	}

	/**
	 * Get data to localize for admin scripts.
	 *
	 * @return array<string, mixed>
	 */
	private function get_admin_script_data(): array {
		$data = [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'apd_admin' ),
			'i18n'    => [
				'confirmDelete' => __( 'Are you sure you want to delete this item?', 'all-purpose-directory' ),
				'saving'        => __( 'Saving...', 'all-purpose-directory' ),
				'saved'         => __( 'Saved!', 'all-purpose-directory' ),
				'error'         => __( 'An error occurred. Please try again.', 'all-purpose-directory' ),
			],
		];

		/**
		 * Filter admin script localization data.
		 *
		 * @since 1.0.0
		 *
		 * @param array $data Localization data.
		 */
		return apply_filters( 'apd_admin_script_data', $data );
	}

	/**
	 * Register a frontend style.
	 *
	 * @param string           $handle Handle name.
	 * @param string           $src    Source URL.
	 * @param array<string>    $deps   Dependencies.
	 * @param string|bool|null $ver    Version.
	 * @return void
	 */
	public function register_style( string $handle, string $src, array $deps = [], string|bool|null $ver = null ): void {
		wp_register_style( $handle, $src, $deps, $ver ?? $this->version );
	}

	/**
	 * Register a frontend script.
	 *
	 * @param string           $handle    Handle name.
	 * @param string           $src       Source URL.
	 * @param array<string>    $deps      Dependencies.
	 * @param string|bool|null $ver       Version.
	 * @param bool             $in_footer Whether to load in footer.
	 * @return void
	 */
	public function register_script( string $handle, string $src, array $deps = [], string|bool|null $ver = null, bool $in_footer = true ): void {
		wp_register_script( $handle, $src, $deps, $ver ?? $this->version, $in_footer );
	}
}
