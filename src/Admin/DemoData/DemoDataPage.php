<?php
/**
 * Demo Data Admin Page Class.
 *
 * Provides admin interface for generating and deleting demo data.
 * Features a tabbed interface with shared Users section and per-module tabs.
 *
 * @package APD\Admin\DemoData
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Admin\DemoData;

use APD\Contracts\DemoDataModuleProviderInterface;
use APD\Contracts\TabProviderInterface;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DemoDataPage
 *
 * Admin page for demo data management.
 *
 * @since 1.0.0
 */
final class DemoDataPage {

	/**
	 * Admin page slug.
	 */
	public const PAGE_SLUG = 'apd-demo-data';

	/**
	 * Parent menu slug.
	 */
	public const PARENT_MENU = 'edit.php?post_type=apd_listing';

	/**
	 * Capability required to manage demo data.
	 */
	public const CAPABILITY = 'manage_options';

	/**
	 * Nonce action for generate.
	 */
	public const NONCE_GENERATE = 'apd_generate_demo';

	/**
	 * Nonce action for delete.
	 */
	public const NONCE_DELETE = 'apd_delete_demo';

	/**
	 * Singleton instance.
	 *
	 * @var DemoDataPage|null
	 */
	private static ?DemoDataPage $instance = null;

	/**
	 * Default quantities for generation.
	 *
	 * @var array<string, int>
	 */
	private array $defaults = [
		'users'    => 5,
		'tags'     => 10,
		'listings' => 25,
	];

	/**
	 * Registered tab providers.
	 *
	 * @var TabProviderInterface[]
	 */
	private array $tabs = [];

	/**
	 * Get the singleton instance.
	 *
	 * @since 1.0.0
	 *
	 * @return DemoDataPage
	 */
	public static function get_instance(): DemoDataPage {
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
	 * Initialize the demo data page.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init(): void {
		// Only run in admin context.
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'admin_menu', [ $this, 'register_admin_page' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );

		// AJAX handlers.
		add_action( 'wp_ajax_apd_generate_demo', [ $this, 'ajax_generate' ] );
		add_action( 'wp_ajax_apd_delete_demo', [ $this, 'ajax_delete' ] );
		add_action( 'wp_ajax_apd_generate_users', [ $this, 'ajax_generate_users' ] );
		add_action( 'wp_ajax_apd_delete_users', [ $this, 'ajax_delete_users' ] );

		/**
		 * Fires after demo data page is initialized.
		 *
		 * @since 1.0.0
		 *
		 * @param DemoDataPage $page The demo data page instance.
		 */
		do_action( 'apd_demo_data_init', $this );

		// Initialize demo data provider registry so modules can register providers.
		DemoDataProviderRegistry::get_instance()->init();

		// Register tabs after providers are initialized.
		$this->register_tabs();
	}

	/**
	 * Register tab providers.
	 *
	 * Creates the General tab and wraps DemoDataModuleProviderInterface providers.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	private function register_tabs(): void {
		// Always register the General tab.
		$this->tabs[] = new GeneralTabProvider();

		// Create ModuleTabProvider for each DemoDataModuleProviderInterface.
		$provider_registry = DemoDataProviderRegistry::get_instance();

		foreach ( $provider_registry->get_all() as $provider ) {
			if ( $provider instanceof DemoDataModuleProviderInterface ) {
				$this->tabs[] = new ModuleTabProvider( $provider );
			}
		}

		// Sort tabs by priority.
		usort(
			$this->tabs,
			function ( TabProviderInterface $a, TabProviderInterface $b ) {
				return $a->get_priority() <=> $b->get_priority();
			}
		);
	}

	/**
	 * Get registered tab providers.
	 *
	 * @since 1.2.0
	 *
	 * @return TabProviderInterface[]
	 */
	public function get_tabs(): array {
		return $this->tabs;
	}

	/**
	 * Get a tab provider by slug.
	 *
	 * @since 1.2.0
	 *
	 * @param string $slug Tab slug.
	 * @return TabProviderInterface|null
	 */
	public function get_tab( string $slug ): ?TabProviderInterface {
		foreach ( $this->tabs as $tab ) {
			if ( $tab->get_slug() === $slug ) {
				return $tab;
			}
		}
		return null;
	}

	/**
	 * Register the admin menu page.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_admin_page(): void {
		add_submenu_page(
			self::PARENT_MENU,
			__( 'Demo Data', 'all-purpose-directory' ),
			__( 'Demo Data', 'all-purpose-directory' ),
			self::CAPABILITY,
			self::PAGE_SLUG,
			[ $this, 'render_page' ]
		);
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		// Only load on our page.
		if ( 'apd_listing_page_' . self::PAGE_SLUG !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'apd-admin-base',
			APD_PLUGIN_URL . 'assets/css/admin-base.css',
			[],
			APD_VERSION
		);

		wp_enqueue_style(
			'apd-admin-demo-data',
			APD_PLUGIN_URL . 'assets/css/admin-demo-data.css',
			[ 'apd-admin-base' ],
			APD_VERSION
		);

		wp_enqueue_script(
			'apd-admin-demo-data',
			APD_PLUGIN_URL . 'assets/js/admin-demo-data.js',
			[ 'jquery' ],
			APD_VERSION,
			true
		);

		// Build tab slugs for JS.
		$tab_slugs = array_map(
			function ( TabProviderInterface $tab ) {
				return $tab->get_slug();
			},
			$this->tabs
		);

		wp_localize_script(
			'apd-admin-demo-data',
			'apdDemoData',
			[
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'generateNonce' => wp_create_nonce( self::NONCE_GENERATE ),
				'deleteNonce'   => wp_create_nonce( self::NONCE_DELETE ),
				'tabs'          => $tab_slugs,
				'strings'       => [
					'generating'       => __( 'Generating demo data...', 'all-purpose-directory' ),
					'deleting'         => __( 'Deleting demo data...', 'all-purpose-directory' ),
					'confirmDelete'    => __( 'Are you sure you want to delete this demo data? This cannot be undone.', 'all-purpose-directory' ),
					'confirmDeleteAll' => __( 'Are you sure you want to delete ALL demo data including users? This cannot be undone.', 'all-purpose-directory' ),
					'success'          => __( 'Operation completed successfully!', 'all-purpose-directory' ),
					'error'            => __( 'An error occurred. Please try again.', 'all-purpose-directory' ),
					'generatingUsers'  => __( 'Creating users...', 'all-purpose-directory' ),
					'deletingUsers'    => __( 'Deleting users...', 'all-purpose-directory' ),
				],
			]
		);
	}

	/**
	 * Render the admin page.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function render_page(): void {
		// Check user capabilities.
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'all-purpose-directory' ) );
		}

		$tracker  = DemoDataTracker::get_instance();
		$defaults = $this->get_filtered_defaults();

		?>
		<div class="wrap apd-demo-data-wrap">
			<?php $this->render_page_header(); ?>
			<?php $this->render_users_section( $tracker, $defaults ); ?>
			<?php $this->render_tab_navigation(); ?>

			<div class="apd-tab-content-wrap">
				<?php foreach ( $this->tabs as $tab ) : ?>
					<div id="apd-tab-<?php echo esc_attr( $tab->get_slug() ); ?>"
						class="apd-tab-content"
						role="tabpanel"
						aria-labelledby="apd-tab-link-<?php echo esc_attr( $tab->get_slug() ); ?>"
						style="display: none;"
						data-module="<?php echo esc_attr( $tab->get_slug() ); ?>">

						<?php $this->render_tab_content( $tab, $tracker, $defaults ); ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the page header.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	private function render_page_header(): void {
		?>
		<div class="apd-page-header">
			<div class="apd-page-header__icon">
				<span class="dashicons dashicons-database" aria-hidden="true"></span>
			</div>
			<div class="apd-page-header__content">
				<h1><?php esc_html_e( 'Demo Data Generator', 'all-purpose-directory' ); ?></h1>
				<p><?php esc_html_e( 'Generate sample data to test your directory. All demo data can be deleted later without affecting your real content.', 'all-purpose-directory' ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the shared Users section above tabs.
	 *
	 * @since 1.2.0
	 *
	 * @param DemoDataTracker    $tracker  Tracker instance.
	 * @param array<string, int> $defaults Default quantities.
	 * @return void
	 */
	private function render_users_section( DemoDataTracker $tracker, array $defaults ): void {
		$user_counts     = $tracker->count_demo_data( DemoDataTracker::USERS_MODULE );
		$user_count      = $user_counts['users'] ?? 0;
		$has_module_data = $tracker->has_module_demo_data();

		?>
		<div class="apd-demo-section apd-demo-users">
			<h2><?php esc_html_e( 'Demo Users', 'all-purpose-directory' ); ?></h2>
			<p class="apd-section-description">
				<?php esc_html_e( 'Demo users are shared across all tabs. They are used as listing authors, reviewers, and for favorites.', 'all-purpose-directory' ); ?>
			</p>

			<div class="apd-users-status">
				<table class="apd-demo-stats">
					<tbody>
						<tr>
							<td>
								<span class="dashicons dashicons-admin-users" aria-hidden="true"></span>
								<?php esc_html_e( 'Users', 'all-purpose-directory' ); ?>
							</td>
							<td class="apd-stat-count" data-type="users">
								<?php echo esc_html( number_format_i18n( $user_count ) ); ?>
							</td>
						</tr>
					</tbody>
				</table>
			</div>

			<div class="apd-users-actions">
				<form id="apd-generate-users-form" class="apd-demo-form apd-inline-form">
					<fieldset>
						<legend class="screen-reader-text"><?php esc_html_e( 'Generate demo users', 'all-purpose-directory' ); ?></legend>
						<label for="apd-users-count" class="screen-reader-text"><?php esc_html_e( 'Number of users', 'all-purpose-directory' ); ?></label>
						<input type="number" id="apd-users-count" name="users_count" value="<?php echo esc_attr( (string) ( $defaults['users'] ?? 5 ) ); ?>" min="1" max="20" class="small-text">
						<span class="description"><?php esc_html_e( 'users (max 20)', 'all-purpose-directory' ); ?></span>
						<button type="submit" class="button button-secondary" id="apd-generate-users-btn">
							<span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
							<?php esc_html_e( 'Generate Users', 'all-purpose-directory' ); ?>
						</button>
					</fieldset>
				</form>

				<?php if ( $user_count > 0 ) : ?>
					<button type="button"
						class="button button-link-delete"
						id="apd-delete-users-btn"
						<?php disabled( $has_module_data ); ?>>
						<span class="dashicons dashicons-trash" aria-hidden="true"></span>
						<?php esc_html_e( 'Delete Users', 'all-purpose-directory' ); ?>
					</button>
					<?php if ( $has_module_data ) : ?>
						<span class="description apd-delete-users-hint">
							<?php esc_html_e( 'Delete all tab data first before deleting users.', 'all-purpose-directory' ); ?>
						</span>
					<?php endif; ?>
				<?php endif; ?>
			</div>

			<!-- Users progress/results -->
			<div id="apd-users-progress" class="apd-progress" style="display: none;">
				<div class="apd-progress-bar">
					<div class="apd-progress-bar-fill"></div>
				</div>
				<p class="apd-progress-text"></p>
			</div>
			<div id="apd-users-results" class="apd-results" style="display: none;"></div>
		</div>
		<?php
	}

	/**
	 * Render the tab navigation bar.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	private function render_tab_navigation(): void {
		if ( count( $this->tabs ) < 2 ) {
			return;
		}

		?>
		<nav class="apd-demo-tabs nav-tab-wrapper" role="tablist">
			<?php foreach ( $this->tabs as $index => $tab ) : ?>
				<a href="#<?php echo esc_attr( $tab->get_slug() ); ?>"
					id="apd-tab-link-<?php echo esc_attr( $tab->get_slug() ); ?>"
					class="nav-tab <?php echo $index === 0 ? 'nav-tab-active' : ''; ?>"
					role="tab"
					aria-selected="<?php echo $index === 0 ? 'true' : 'false'; ?>"
					aria-controls="apd-tab-<?php echo esc_attr( $tab->get_slug() ); ?>"
					data-tab="<?php echo esc_attr( $tab->get_slug() ); ?>">
					<span class="dashicons <?php echo esc_attr( $tab->get_icon() ); ?>" aria-hidden="true"></span>
					<?php echo esc_html( $tab->get_name() ); ?>
				</a>
			<?php endforeach; ?>
		</nav>
		<?php
	}

	/**
	 * Render a single tab's content.
	 *
	 * @since 1.2.0
	 *
	 * @param TabProviderInterface $tab      Tab provider.
	 * @param DemoDataTracker      $tracker  Tracker instance.
	 * @param array<string, int>   $defaults Default quantities.
	 * @return void
	 */
	private function render_tab_content( TabProviderInterface $tab, DemoDataTracker $tracker, array $defaults ): void {
		$total = $tab->get_total( $tracker );
		$slug  = $tab->get_slug();

		?>
		<!-- Status Section -->
		<div class="apd-demo-section apd-demo-status">
			<h2>
				<?php
				printf(
					/* translators: %s: Tab name */
					esc_html__( 'Current %s Demo Data', 'all-purpose-directory' ),
					esc_html( $tab->get_name() )
				);
				?>
			</h2>

			<div class="apd-stats-grid">
				<?php $tab->render_status_section( $tracker ); ?>
				<div class="apd-stat-item apd-stat-footer">
					<span class="apd-stat-label"><?php esc_html_e( 'Total Items', 'all-purpose-directory' ); ?></span>
					<span class="apd-stat-total" data-module="<?php echo esc_attr( $slug ); ?>">
						<?php echo esc_html( number_format_i18n( $total ) ); ?>
					</span>
				</div>
			</div>
		</div>

		<!-- Generate Form -->
		<div class="apd-demo-section apd-demo-generate">
			<h2>
				<?php
				printf(
					/* translators: %s: Tab name */
					esc_html__( 'Generate %s Demo Data', 'all-purpose-directory' ),
					esc_html( $tab->get_name() )
				);
				?>
			</h2>

			<form class="apd-demo-form apd-generate-tab-form" data-module="<?php echo esc_attr( $slug ); ?>">
				<fieldset>
					<legend class="screen-reader-text">
						<?php
						printf(
							/* translators: %s: Tab name */
							esc_html__( 'Select %s data to generate', 'all-purpose-directory' ),
							esc_html( $tab->get_name() )
						);
						?>
					</legend>
					<?php $tab->render_generate_form( $defaults ); ?>
				</fieldset>

				<div class="apd-form-actions">
					<button type="submit" class="button button-primary button-large">
						<span class="dashicons dashicons-database-add" aria-hidden="true"></span>
						<?php
						printf(
							/* translators: %s: Tab name */
							esc_html__( 'Generate %s Data', 'all-purpose-directory' ),
							esc_html( $tab->get_name() )
						);
						?>
					</button>
				</div>
			</form>

			<!-- Per-tab progress indicator -->
			<div class="apd-progress apd-tab-progress" style="display: none;">
				<div class="apd-progress-bar">
					<div class="apd-progress-bar-fill"></div>
				</div>
				<p class="apd-progress-text"></p>
			</div>

			<!-- Per-tab results -->
			<div class="apd-results apd-tab-results" style="display: none;"></div>
		</div>

		<!-- Delete Section -->
		<div class="apd-demo-section apd-demo-delete">
			<h2>
				<?php
				printf(
					/* translators: %s: Tab name */
					esc_html__( 'Delete %s Demo Data', 'all-purpose-directory' ),
					esc_html( $tab->get_name() )
				);
				?>
			</h2>

			<?php $tab->render_delete_section( $tracker ); ?>
		</div>
		<?php
	}

	/**
	 * AJAX handler for generating demo data (per-tab).
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function ajax_generate(): void {
		// Verify nonce.
		if ( ! check_ajax_referer( self::NONCE_GENERATE, 'nonce', false ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid security token.', 'all-purpose-directory' ) ], 403 );
		}

		// Check capability.
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'all-purpose-directory' ) ], 403 );
		}

		// Get the target module/tab.
		$module = isset( $_POST['module'] ) ? sanitize_key( $_POST['module'] ) : '';
		$tab    = $this->get_tab( $module );

		if ( ! $tab ) {
			wp_send_json_error( [ 'message' => __( 'Invalid tab.', 'all-purpose-directory' ) ], 400 );
			return;
		}

		/**
		 * Fires before demo data generation begins.
		 *
		 * @since 1.0.0
		 */
		do_action( 'apd_before_generate_demo_data' );

		// Sanitize POST data before passing to handler.
		$post_data = [];
		foreach ( $_POST as $key => $value ) {
			$key = sanitize_key( $key );
			if ( is_numeric( $value ) ) {
				$post_data[ $key ] = absint( $value );
			} else {
				$post_data[ $key ] = sanitize_text_field( wp_unslash( $value ) );
			}
		}

		$result = $tab->handle_generate( $post_data );

		/**
		 * Fires after demo data generation completes.
		 *
		 * @since 1.0.0
		 *
		 * @param array $results Number of items created by type.
		 */
		do_action( 'apd_after_generate_demo_data', $result['created'] );

		// Include user count for the Users section update.
		$tracker                   = DemoDataTracker::get_instance();
		$user_counts               = $tracker->count_demo_data( DemoDataTracker::USERS_MODULE );
		$result['counts']['users'] = $user_counts['users'] ?? 0;
		$result['has_module_data'] = $tracker->has_module_demo_data();

		wp_send_json_success(
			[
				'message'         => __( 'Demo data generated successfully!', 'all-purpose-directory' ),
				'created'         => $result['created'],
				'counts'          => $result['counts'],
				'module'          => $module,
				'has_module_data' => $result['has_module_data'],
			]
		);
	}

	/**
	 * AJAX handler for deleting demo data (per-tab).
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function ajax_delete(): void {
		// Verify nonce.
		if ( ! check_ajax_referer( self::NONCE_DELETE, 'nonce', false ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid security token.', 'all-purpose-directory' ) ], 403 );
		}

		// Check capability.
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'all-purpose-directory' ) ], 403 );
		}

		$module = isset( $_POST['module'] ) ? sanitize_key( $_POST['module'] ) : '';
		$tab    = $this->get_tab( $module );

		if ( ! $tab ) {
			wp_send_json_error( [ 'message' => __( 'Invalid tab.', 'all-purpose-directory' ) ], 400 );
			return;
		}

		$tracker = DemoDataTracker::get_instance();

		/**
		 * Fires before demo data deletion begins.
		 *
		 * @since 1.0.0
		 */
		do_action( 'apd_before_delete_demo_data' );

		$deleted = $tab->handle_delete( $tracker );

		/**
		 * Fires after demo data deletion completes.
		 *
		 * @since 1.0.0
		 *
		 * @param array $counts Number of items deleted by type.
		 */
		do_action( 'apd_after_delete_demo_data', $deleted );

		// Get updated counts for this tab.
		$counts = $tab->get_counts( $tracker );

		// Include user info.
		$user_counts = $tracker->count_demo_data( DemoDataTracker::USERS_MODULE );

		wp_send_json_success(
			[
				'message'         => __( 'Demo data has been deleted.', 'all-purpose-directory' ),
				'deleted'         => $deleted,
				'counts'          => $counts,
				'module'          => $module,
				'users'           => $user_counts['users'] ?? 0,
				'has_module_data' => $tracker->has_module_demo_data(),
			]
		);
	}

	/**
	 * AJAX handler for generating demo users.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	public function ajax_generate_users(): void {
		if ( ! check_ajax_referer( self::NONCE_GENERATE, 'nonce', false ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid security token.', 'all-purpose-directory' ) ], 403 );
		}

		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'all-purpose-directory' ) ], 403 );
		}

		$count     = min( absint( $_POST['users_count'] ?? 5 ), 20 );
		$generator = DemoDataGenerator::get_instance();
		$user_ids  = $generator->generate_users( $count );

		wp_send_json_success(
			[
				'message' => sprintf(
					/* translators: %d: Number of users created */
					__( '%d demo users created.', 'all-purpose-directory' ),
					count( $user_ids )
				),
				'created' => count( $user_ids ),
				'count'   => count( DemoDataTracker::get_instance()->get_demo_user_ids() ),
			]
		);
	}

	/**
	 * AJAX handler for deleting demo users.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	public function ajax_delete_users(): void {
		if ( ! check_ajax_referer( self::NONCE_DELETE, 'nonce', false ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid security token.', 'all-purpose-directory' ) ], 403 );
		}

		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'all-purpose-directory' ) ], 403 );
		}

		$tracker = DemoDataTracker::get_instance();

		// Don't allow deleting users while module data exists.
		if ( $tracker->has_module_demo_data() ) {
			wp_send_json_error(
				[
					'message' => __( 'Cannot delete demo users while demo data exists in other tabs. Delete all tab data first.', 'all-purpose-directory' ),
				],
				400
			);
		}

		$deleted = $tracker->delete_demo_users();

		wp_send_json_success(
			[
				'message' => sprintf(
					/* translators: %d: Number of users deleted */
					__( '%d demo users deleted.', 'all-purpose-directory' ),
					$deleted
				),
				'deleted' => $deleted,
				'count'   => 0,
			]
		);
	}

	/**
	 * Get the default generation quantities with filter applied.
	 *
	 * @since 1.2.0
	 *
	 * @return array<string, int>
	 */
	private function get_filtered_defaults(): array {
		/**
		 * Filter the default demo data counts.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, int> $defaults Default quantities.
		 */
		return apply_filters( 'apd_demo_default_counts', $this->defaults );
	}

	/**
	 * Get the default generation quantities.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, int>
	 */
	public function get_defaults(): array {
		return $this->defaults;
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
