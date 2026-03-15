<?php
/**
 * Dashboard Controller Class.
 *
 * Main controller for the user dashboard, handling tab/section routing,
 * stats gathering, and authentication checks.
 *
 * @package APD\Frontend\Dashboard
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Frontend\Dashboard;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Dashboard
 *
 * @since 1.0.0
 */
class Dashboard {

	/**
	 * Default tab to show.
	 *
	 * @var string
	 */
	public const DEFAULT_TAB = 'my-listings';

	/**
	 * URL parameter for tab selection.
	 *
	 * @var string
	 */
	public const TAB_PARAM = 'tab';

	/**
	 * Dashboard configuration.
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
		'default_tab' => self::DEFAULT_TAB,
		'show_stats'  => true,
		'class'       => '',
	];

	/**
	 * Singleton instance.
	 *
	 * @var Dashboard|null
	 */
	private static ?Dashboard $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $config Optional. Configuration options.
	 * @return Dashboard
	 */
	public static function get_instance( array $config = [] ): Dashboard {
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
	 * @param array<string, mixed> $config Dashboard configuration.
	 */
	private function __construct( array $config = [] ) {
		$this->config = wp_parse_args( $config, self::DEFAULTS );
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
	 * Get the dashboard configuration.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed> Configuration array.
	 */
	public function get_config(): array {
		return $this->config;
	}

	/**
	 * Get a specific configuration value.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key     Configuration key.
	 * @param mixed  $default Default value if key doesn't exist.
	 * @return mixed Configuration value.
	 */
	public function get_config_value( string $key, mixed $default = null ): mixed {
		return $this->config[ $key ] ?? $default;
	}

	/**
	 * Check if user is logged in.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if user is logged in.
	 */
	public function is_user_logged_in(): bool {
		return is_user_logged_in();
	}

	/**
	 * Get the current tab from URL parameter.
	 *
	 * @since 1.0.0
	 *
	 * @return string Current tab slug.
	 */
	public function get_current_tab(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Just reading tab parameter.
		$tab = isset( $_GET[ self::TAB_PARAM ] ) ? sanitize_key( $_GET[ self::TAB_PARAM ] ) : '';

		// Validate against available tabs.
		$tabs = $this->get_tabs();

		if ( empty( $tab ) || ! isset( $tabs[ $tab ] ) ) {
			return $this->config['default_tab'];
		}

		return $tab;
	}

	/**
	 * Get available dashboard tabs.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array<string, mixed>> Tabs configuration keyed by slug.
	 */
	public function get_tabs(): array {
		$user_id         = get_current_user_id();
		$stats           = $this->get_user_stats( $user_id );
		$favorites_count = \apd_get_favorites_count( $user_id );

		$tabs = [
			'my-listings' => [
				'label'    => __( 'My Listings', 'all-purpose-directory' ),
				'icon'     => 'dashicons-list-view',
				'count'    => $stats['total'],
				'callback' => [ $this, 'render_my_listings_tab' ],
				'priority' => 10,
			],
			'add-new'     => [
				'label'    => __( 'Add New', 'all-purpose-directory' ),
				'icon'     => 'dashicons-plus-alt',
				'count'    => null,
				'callback' => [ $this, 'render_add_new_tab' ],
				'priority' => 20,
			],
			'favorites'   => [
				'label'    => __( 'Favorites', 'all-purpose-directory' ),
				'icon'     => 'dashicons-heart',
				'count'    => $favorites_count,
				'callback' => [ $this, 'render_favorites_tab' ],
				'priority' => 30,
			],
			'profile'     => [
				'label'    => __( 'Profile', 'all-purpose-directory' ),
				'icon'     => 'dashicons-admin-users',
				'count'    => null,
				'callback' => [ $this, 'render_profile_tab' ],
				'priority' => 40,
			],
		];

		/**
		 * Filter the available dashboard tabs.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, array<string, mixed>> $tabs    Tabs configuration.
		 * @param int                                 $user_id Current user ID.
		 */
		$tabs = apply_filters( 'apd_dashboard_tabs', $tabs, $user_id );

		// Sort by priority.
		uasort( $tabs, fn( $a, $b ) => ( $a['priority'] ?? 100 ) <=> ( $b['priority'] ?? 100 ) );

		return $tabs;
	}

	/**
	 * Get user's listing statistics.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id User ID.
	 * @return array<string, int> Statistics array.
	 */
	public function get_user_stats( int $user_id = 0 ): array {
		if ( $user_id <= 0 ) {
			$user_id = get_current_user_id();
		}

		if ( $user_id <= 0 ) {
			return $this->get_empty_stats();
		}

		$stats = [
			'total'            => 0,
			'published'        => 0,
			'pending'          => 0,
			'draft'            => 0,
			'expired'          => 0,
			'views'            => 0,
			'inquiries'        => 0,
			'unread_inquiries' => 0,
		];

		// Count listings by status in a single query.
		$status_counts = $this->count_user_listings_by_status( $user_id );

		$stats['published'] = $status_counts['publish'] ?? 0;
		$stats['pending']   = $status_counts['pending'] ?? 0;
		$stats['draft']     = $status_counts['draft'] ?? 0;
		$stats['expired']   = $status_counts['expired'] ?? 0;
		$stats['total']     = $stats['published'] + $stats['pending'] + $stats['draft'] + $stats['expired'];

		// Get total views.
		$stats['views'] = $this->get_user_total_views( $user_id );

		// Get inquiry counts.
		$inquiry_tracker           = \APD\Contact\InquiryTracker::get_instance();
		$stats['inquiries']        = $inquiry_tracker->count_user_inquiries( $user_id, 'all' );
		$stats['unread_inquiries'] = $inquiry_tracker->count_user_inquiries( $user_id, 'unread' );

		/**
		 * Filter the user's dashboard statistics.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, int> $stats   Statistics array.
		 * @param int                $user_id User ID.
		 */
		return apply_filters( 'apd_dashboard_stats', $stats, $user_id );
	}

	/**
	 * Get empty stats array.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, int> Empty statistics array.
	 */
	private function get_empty_stats(): array {
		return [
			'total'            => 0,
			'published'        => 0,
			'pending'          => 0,
			'draft'            => 0,
			'expired'          => 0,
			'views'            => 0,
			'inquiries'        => 0,
			'unread_inquiries' => 0,
		];
	}

	/**
	 * Count user's listings grouped by status in a single query.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id User ID.
	 * @return array<string, int> Counts keyed by post_status.
	 */
	private function count_user_listings_by_status( int $user_id ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_status, COUNT(*) AS count
				FROM {$wpdb->posts}
				WHERE post_author = %d
				AND post_type = 'apd_listing'
				AND post_status IN ('publish', 'pending', 'draft', 'expired')
				GROUP BY post_status",
				$user_id
			)
		);

		$counts = [];
		if ( $results ) {
			foreach ( $results as $row ) {
				$counts[ $row->post_status ] = (int) $row->count;
			}
		}

		return $counts;
	}

	/**
	 * Get total views across all user's listings.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id User ID.
	 * @return int Total view count.
	 */
	private function get_user_total_views( int $user_id ): int {
		global $wpdb;

		// Get sum of all views for user's listings.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$total = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM(CAST(pm.meta_value AS UNSIGNED))
				FROM {$wpdb->postmeta} pm
				INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
				WHERE p.post_author = %d
				AND p.post_type = 'apd_listing'
				AND pm.meta_key = '_apd_views_count'",
				$user_id
			)
		);

		return (int) $total;
	}

	/**
	 * Render the dashboard.
	 *
	 * @since 1.0.0
	 *
	 * @return string Rendered dashboard HTML.
	 */
	public function render(): string {
		// Check authentication.
		if ( ! $this->is_user_logged_in() ) {
			return $this->render_login_required();
		}

		$user_id     = get_current_user_id();
		$current_tab = $this->get_current_tab();
		$tabs        = $this->get_tabs();
		$stats       = $this->get_user_stats( $user_id );

		// Build template args.
		$args = [
			'dashboard'   => $this,
			'config'      => $this->config,
			'user_id'     => $user_id,
			'current_tab' => $current_tab,
			'tabs'        => $tabs,
			'stats'       => $stats,
			'show_stats'  => $this->config['show_stats'],
			'css_class'   => $this->get_css_class(),
		];

		/**
		 * Fires before the dashboard is rendered.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, mixed> $args Dashboard template arguments.
		 */
		do_action( 'apd_before_dashboard', $args );

		/**
		 * Filter the dashboard template arguments.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, mixed> $args Dashboard template arguments.
		 */
		$args = apply_filters( 'apd_dashboard_args', $args );

		// Render the template.
		ob_start();
		\apd_get_template( 'dashboard/dashboard.php', $args );
		$output = ob_get_clean();

		/**
		 * Fires after the dashboard is rendered.
		 *
		 * @since 1.0.0
		 *
		 * @param string               $output The dashboard HTML.
		 * @param array<string, mixed> $args   Dashboard template arguments.
		 */
		do_action( 'apd_after_dashboard', $output, $args );

		/**
		 * Filter the dashboard output.
		 *
		 * @since 1.0.0
		 *
		 * @param string               $output The dashboard HTML.
		 * @param array<string, mixed> $args   Dashboard template arguments.
		 */
		return apply_filters( 'apd_dashboard_html', $output, $args );
	}

	/**
	 * Render the login required message.
	 *
	 * @since 1.0.0
	 *
	 * @return string Rendered login required HTML.
	 */
	public function render_login_required(): string {
		$args = [
			'message'   => __( 'Please log in to access your dashboard.', 'all-purpose-directory' ),
			'login_url' => wp_login_url( $this->get_dashboard_url() ),
		];

		ob_start();
		\apd_get_template( 'dashboard/login-required.php', $args );
		return ob_get_clean();
	}

	/**
	 * Render the tab content.
	 *
	 * @since 1.0.0
	 *
	 * @param string $tab Tab slug.
	 * @return string Rendered tab content HTML.
	 */
	public function render_tab_content( string $tab ): string {
		$tabs = $this->get_tabs();

		/**
		 * Fires before the dashboard tab content.
		 *
		 * @since 1.0.0
		 *
		 * @param string $tab The current tab slug.
		 */
		do_action( 'apd_dashboard_before_content', $tab );

		// Allow custom content via action.
		ob_start();

		/**
		 * Fires to render custom tab content.
		 *
		 * @since 1.0.0
		 *
		 * @param string $tab The current tab slug.
		 */
		do_action( "apd_dashboard_{$tab}_content", $tab );

		$custom_content = ob_get_clean();

		// If action provided content, use it.
		if ( ! empty( $custom_content ) ) {
			$output = $custom_content;
		} elseif ( isset( $tabs[ $tab ]['callback'] ) && is_callable( $tabs[ $tab ]['callback'] ) ) {
			// Use the registered callback.
			ob_start();
			call_user_func( $tabs[ $tab ]['callback'], $tab );
			$output = ob_get_clean();
		} else {
			// Render placeholder with tab label if available.
			$label  = $tabs[ $tab ]['label'] ?? null;
			$output = $this->render_placeholder( $tab, $label );
		}

		/**
		 * Fires after the dashboard tab content.
		 *
		 * @since 1.0.0
		 *
		 * @param string $tab    The current tab slug.
		 * @param string $output The rendered content.
		 */
		do_action( 'apd_dashboard_after_content', $tab, $output );

		return $output;
	}

	/**
	 * Render placeholder content for tabs not yet implemented.
	 *
	 * @since 1.0.0
	 *
	 * @param string      $tab   Tab slug.
	 * @param string|null $label Optional. Tab label. Auto-generated from slug if not provided.
	 * @return string Placeholder HTML.
	 */
	public function render_placeholder( string $tab, ?string $label = null ): string {
		// Generate label from slug if not provided.
		if ( $label === null ) {
			$label = ucfirst( str_replace( '-', ' ', $tab ) );
		}

		return sprintf(
			'<div class="apd-dashboard-placeholder">
				<p class="apd-dashboard-placeholder__message">%s</p>
			</div>',
			sprintf(
				/* translators: %s: Tab name */
				esc_html__( '%s content is coming soon.', 'all-purpose-directory' ),
				esc_html( $label )
			)
		);
	}

	/**
	 * Render the My Listings tab.
	 *
	 * @since 1.0.0
	 *
	 * @param string $tab Tab slug.
	 * @return void
	 */
	public function render_my_listings_tab( string $tab ): void {
		$my_listings = MyListings::get_instance();
		$my_listings->set_user_id( get_current_user_id() );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped in the template.
		echo $my_listings->render();
	}

	/**
	 * Render the Add New tab.
	 *
	 * @since 1.0.0
	 *
	 * @param string $tab Tab slug.
	 * @return void
	 */
	public function render_add_new_tab( string $tab ): void {
		// Link to submission form or render inline.
		$submission_url = $this->get_submission_url();

		if ( ! empty( $submission_url ) ) {
			printf(
				'<div class="apd-dashboard-add-new">
					<p>%s</p>
					<a href="%s" class="apd-button apd-button--primary">%s</a>
				</div>',
				esc_html__( 'Create a new listing to share with the community.', 'all-purpose-directory' ),
				esc_url( $submission_url ),
				esc_html__( 'Add New Listing', 'all-purpose-directory' )
			);
		} else {
			echo $this->render_placeholder( $tab ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * Render the Favorites tab.
	 *
	 * @since 1.0.0
	 *
	 * @param string $tab Tab slug.
	 * @return void
	 */
	public function render_favorites_tab( string $tab ): void {
		$favorites_page = FavoritesPage::get_instance();
		$favorites_page->set_user_id( get_current_user_id() );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped in the template.
		echo $favorites_page->render();
	}

	/**
	 * Render the Profile tab.
	 *
	 * @since 1.0.0
	 *
	 * @param string $tab Tab slug.
	 * @return void
	 */
	public function render_profile_tab( string $tab ): void {
		$profile = Profile::get_instance();
		$profile->set_user_id( get_current_user_id() );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped in the template.
		echo $profile->render();
	}

	/**
	 * Get CSS classes for the dashboard wrapper.
	 *
	 * @since 1.0.0
	 *
	 * @return string CSS classes.
	 */
	private function get_css_class(): string {
		$classes = [ 'apd-dashboard' ];

		if ( ! empty( $this->config['class'] ) ) {
			$classes[] = $this->config['class'];
		}

		/**
		 * Filter the dashboard CSS classes.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string>        $classes CSS classes.
		 * @param array<string, mixed> $config  Dashboard configuration.
		 */
		$classes = apply_filters( 'apd_dashboard_classes', $classes, $this->config );

		return implode( ' ', array_filter( $classes ) );
	}

	/**
	 * Get the dashboard URL.
	 *
	 * @since 1.0.0
	 *
	 * @return string Dashboard URL.
	 */
	public function get_dashboard_url(): string {
		/**
		 * Filter the dashboard URL.
		 *
		 * If not filtered, returns the current page URL.
		 *
		 * @since 1.0.0
		 *
		 * @param string $url The dashboard URL.
		 */
		$url = apply_filters( 'apd_dashboard_url', '' );

		if ( empty( $url ) ) {
			$url = get_permalink();
		}

		return $url ?: home_url();
	}

	/**
	 * Get the URL to a specific dashboard tab.
	 *
	 * @since 1.0.0
	 *
	 * @param string $tab Tab slug.
	 * @return string Tab URL.
	 */
	public function get_tab_url( string $tab ): string {
		$base_url = $this->get_dashboard_url();

		if ( $tab === $this->config['default_tab'] ) {
			return $base_url;
		}

		return add_query_arg( self::TAB_PARAM, $tab, $base_url );
	}

	/**
	 * Get the submission form URL.
	 *
	 * @since 1.0.0
	 *
	 * @return string Submission URL or empty string.
	 */
	private function get_submission_url(): string {
		/**
		 * Filter the submission page URL.
		 *
		 * @since 1.0.0
		 *
		 * @param string $url The submission page URL.
		 */
		return apply_filters( 'apd_submission_page_url', '' );
	}
}
