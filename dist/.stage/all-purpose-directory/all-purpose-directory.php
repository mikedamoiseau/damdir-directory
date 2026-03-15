<?php
/**
 * Plugin Name:       All Purpose Directory
 * Plugin URI:        https://damoiseau.xyz/all-purpose-directory/
 * Description:       A flexible WordPress plugin for building directory and listing websites.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Michaël Damoiseau
 * Author URI:        https://damoiseau.xyz
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       all-purpose-directory
 * Domain Path:       /languages
 *
 * @package APD
 */

declare(strict_types=1);

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin version.
define( 'APD_VERSION', '1.0.0' );

// Plugin file path.
define( 'APD_PLUGIN_FILE', __FILE__ );

// Plugin directory path.
define( 'APD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// Plugin directory URL.
define( 'APD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Plugin basename.
define( 'APD_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Minimum WordPress version.
define( 'APD_MIN_WP_VERSION', '6.0' );

// Minimum PHP version.
define( 'APD_MIN_PHP_VERSION', '8.0' );

/**
 * Load Composer autoloader.
 */
if ( file_exists( APD_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once APD_PLUGIN_DIR . 'vendor/autoload.php';
}

/**
 * Load helper functions.
 */
require_once APD_PLUGIN_DIR . 'includes/functions.php';
require_once APD_PLUGIN_DIR . 'includes/module-functions.php';
require_once APD_PLUGIN_DIR . 'includes/demo-data-functions.php';

/**
 * Register WP-CLI commands.
 */
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'apd demo', \APD\CLI\DemoDataCommand::class );
}

/**
 * Initialize the plugin.
 *
 * @return void
 */
function apd_init(): void {
	// Check PHP version.
	if ( version_compare( PHP_VERSION, APD_MIN_PHP_VERSION, '<' ) ) {
		add_action( 'admin_notices', 'apd_php_version_notice' );
		return;
	}

	// Check WordPress version.
	if ( version_compare( get_bloginfo( 'version' ), APD_MIN_WP_VERSION, '<' ) ) {
		add_action( 'admin_notices', 'apd_wp_version_notice' );
		return;
	}

	// Boot the plugin.
	\APD\Core\Plugin::get_instance();
}
add_action( 'plugins_loaded', 'apd_init' );

/**
 * Display PHP version notice.
 *
 * @return void
 */
function apd_php_version_notice(): void {
	$message = sprintf(
		/* translators: 1: Required PHP version, 2: Current PHP version */
		__( 'All Purpose Directory requires PHP %1$s or higher. You are running PHP %2$s.', 'all-purpose-directory' ),
		APD_MIN_PHP_VERSION,
		PHP_VERSION
	);

	printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( $message ) );
}

/**
 * Display WordPress version notice.
 *
 * @return void
 */
function apd_wp_version_notice(): void {
	$message = sprintf(
		/* translators: 1: Required WordPress version, 2: Current WordPress version */
		__( 'All Purpose Directory requires WordPress %1$s or higher. You are running WordPress %2$s.', 'all-purpose-directory' ),
		APD_MIN_WP_VERSION,
		get_bloginfo( 'version' )
	);

	printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( $message ) );
}

/**
 * Plugin activation hook.
 *
 * @return void
 */
function apd_activate(): void {
	if ( file_exists( APD_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
		require_once APD_PLUGIN_DIR . 'vendor/autoload.php';
	}

	\APD\Core\Activator::activate();
}
register_activation_hook( __FILE__, 'apd_activate' );

/**
 * Plugin deactivation hook.
 *
 * @return void
 */
function apd_deactivate(): void {
	\APD\Core\Deactivator::deactivate();
}
register_deactivation_hook( __FILE__, 'apd_deactivate' );
