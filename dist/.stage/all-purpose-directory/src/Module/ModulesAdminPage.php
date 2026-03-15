<?php
/**
 * Modules Admin Page.
 *
 * Admin interface for viewing registered modules.
 *
 * @package APD\Module
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Module;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ModulesAdminPage
 *
 * Admin page for viewing registered modules.
 *
 * @since 1.0.0
 */
final class ModulesAdminPage {

	/**
	 * Admin page slug.
	 */
	public const PAGE_SLUG = 'apd-modules';

	/**
	 * Parent menu slug.
	 */
	public const PARENT_MENU = 'edit.php?post_type=apd_listing';

	/**
	 * Capability required to view modules.
	 */
	public const CAPABILITY = 'manage_options';

	/**
	 * Singleton instance.
	 *
	 * @var ModulesAdminPage|null
	 */
	private static ?ModulesAdminPage $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @since 1.0.0
	 *
	 * @return ModulesAdminPage
	 */
	public static function get_instance(): ModulesAdminPage {
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
	 * Initialize the modules admin page.
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

		/**
		 * Fires after modules admin page is initialized.
		 *
		 * @since 1.0.0
		 *
		 * @param ModulesAdminPage $page The modules admin page instance.
		 */
		do_action( 'apd_modules_admin_init', $this );
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
			__( 'Modules', 'all-purpose-directory' ),
			__( 'Modules', 'all-purpose-directory' ),
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
			'apd-admin-modules',
			APD_PLUGIN_URL . 'assets/css/admin-modules.css',
			[ 'apd-admin-base' ],
			APD_VERSION
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

		$registry = ModuleRegistry::get_instance();
		$modules  = $registry->get_all(
			[
				'orderby' => 'name',
				'order'   => 'ASC',
			]
		);
		$count    = $registry->count();

		?>
		<div class="wrap apd-modules-wrap">
			<div class="apd-page-header">
				<div class="apd-page-header__icon">
					<span class="dashicons dashicons-admin-plugins" aria-hidden="true"></span>
				</div>
				<div class="apd-page-header__content">
					<h1><?php esc_html_e( 'Installed Modules', 'all-purpose-directory' ); ?></h1>
					<p><?php esc_html_e( 'Modules extend the functionality of All Purpose Directory with specialized features for different use cases.', 'all-purpose-directory' ); ?></p>
				</div>
			</div>

			<?php if ( $count === 0 ) : ?>
				<div class="apd-modules-empty">
					<span class="dashicons dashicons-admin-plugins"></span>
					<h2><?php esc_html_e( 'No Modules Installed', 'all-purpose-directory' ); ?></h2>
					<p>
						<?php esc_html_e( 'Modules are separate plugins that add specialized features to your directory.', 'all-purpose-directory' ); ?>
					</p>
					<p>
						<?php esc_html_e( 'Examples include URL directories, job boards, real estate listings, and more.', 'all-purpose-directory' ); ?>
					</p>
				</div>
			<?php else : ?>
				<p class="apd-modules-count">
					<?php
					printf(
						/* translators: %s: Number of modules */
						esc_html( _n( '%s module installed', '%s modules installed', $count, 'all-purpose-directory' ) ),
						'<strong>' . esc_html( number_format_i18n( $count ) ) . '</strong>'
					);
					?>
				</p>

				<table class="wp-list-table widefat fixed striped apd-modules-table">
					<thead>
						<tr>
							<th scope="col" class="column-icon"><?php esc_html_e( 'Icon', 'all-purpose-directory' ); ?></th>
							<th scope="col" class="column-name"><?php esc_html_e( 'Module', 'all-purpose-directory' ); ?></th>
							<th scope="col" class="column-description"><?php esc_html_e( 'Description', 'all-purpose-directory' ); ?></th>
							<th scope="col" class="column-version"><?php esc_html_e( 'Version', 'all-purpose-directory' ); ?></th>
							<th scope="col" class="column-author"><?php esc_html_e( 'Author', 'all-purpose-directory' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $modules as $slug => $module ) : ?>
							<?php $this->render_module_row( $slug, $module ); ?>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render a single module row.
	 *
	 * @since 1.0.0
	 *
	 * @param string               $slug   Module slug.
	 * @param array<string, mixed> $module Module configuration.
	 * @return void
	 */
	private function render_module_row( string $slug, array $module ): void {
		$icon = ! empty( $module['icon'] ) ? $module['icon'] : 'dashicons-admin-plugins';

		// Check requirements.
		$registry     = ModuleRegistry::get_instance();
		$unmet        = $registry->check_requirements( $module['requires'] ?? [] );
		$has_warnings = ! empty( $unmet );

		?>
		<tr class="apd-module-row<?php echo $has_warnings ? ' apd-module-has-warnings' : ''; ?>">
			<td class="column-icon">
				<span class="apd-module-icon-wrap">
					<span class="dashicons <?php echo esc_attr( $icon ); ?>" aria-hidden="true"></span>
				</span>
			</td>
			<td class="column-name">
				<strong><?php echo esc_html( $module['name'] ); ?></strong>
				<span class="apd-module-slug"><?php echo esc_html( $slug ); ?></span>
				<?php if ( ! empty( $module['features'] ) ) : ?>
					<div class="apd-module-features">
						<?php foreach ( $module['features'] as $feature ) : ?>
							<span class="apd-feature-badge"><?php echo esc_html( $feature ); ?></span>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</td>
			<td class="column-description">
				<?php echo esc_html( $module['description'] ); ?>
				<?php if ( $has_warnings ) : ?>
					<div class="apd-module-warnings">
						<?php foreach ( $unmet as $warning ) : ?>
							<p class="apd-warning-message">
								<span class="dashicons dashicons-warning" aria-hidden="true"></span>
								<?php echo esc_html( $warning ); ?>
							</p>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</td>
			<td class="column-version">
				<?php echo esc_html( $module['version'] ); ?>
			</td>
			<td class="column-author">
				<?php if ( ! empty( $module['author_uri'] ) && ! empty( $module['author'] ) ) : ?>
					<a href="<?php echo esc_url( $module['author_uri'] ); ?>" target="_blank" rel="noopener noreferrer">
						<?php echo esc_html( $module['author'] ); ?>
					</a>
				<?php elseif ( ! empty( $module['author'] ) ) : ?>
					<?php echo esc_html( $module['author'] ); ?>
				<?php else : ?>
					<span class="apd-no-author">&mdash;</span>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Get the URL to the modules admin page.
	 *
	 * @since 1.0.0
	 *
	 * @return string The modules admin page URL.
	 */
	public function get_page_url(): string {
		return admin_url( self::PARENT_MENU . '&page=' . self::PAGE_SLUG );
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
