<?php
/**
 * Plugin deactivation handler.
 *
 * @package APD\Core
 */

declare(strict_types=1);

namespace APD\Core;

/**
 * Class Deactivator
 *
 * Handles plugin deactivation tasks.
 */
class Deactivator {

	/**
	 * Deactivate the plugin.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		self::clear_scheduled_events();
		self::flush_rewrite_rules();

		/**
		 * Fires after the plugin is deactivated.
		 *
		 * @since 1.0.0
		 */
		do_action( 'apd_deactivated' );
	}

	/**
	 * Clear scheduled cron events.
	 *
	 * @return void
	 */
	private static function clear_scheduled_events(): void {
		wp_clear_scheduled_hook( 'apd_check_expired_listings' );
		wp_clear_scheduled_hook( 'apd_cleanup_transients' );
	}

	/**
	 * Flush rewrite rules.
	 *
	 * @return void
	 */
	private static function flush_rewrite_rules(): void {
		flush_rewrite_rules();
	}
}
