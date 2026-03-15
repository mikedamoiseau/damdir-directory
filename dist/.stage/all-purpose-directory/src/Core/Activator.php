<?php
/**
 * Plugin activation handler.
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
 * Class Activator
 *
 * Handles plugin activation tasks.
 */
class Activator {

	/**
	 * Activate the plugin.
	 *
	 * @return void
	 */
	public static function activate(): void {
		self::check_requirements();
		self::create_tables();
		self::create_options();
		self::create_roles();
		self::create_default_pages();
		self::schedule_events();

		// Flush rewrite rules after post type registration.
		add_action( 'init', 'flush_rewrite_rules', 99 );

		// Store plugin version.
		update_option( 'apd_version', APD_VERSION );

		/**
		 * Fires after the plugin is activated.
		 *
		 * @since 1.0.0
		 */
		do_action( 'apd_activated' );
	}

	/**
	 * Check plugin requirements.
	 *
	 * @return void
	 */
	private static function check_requirements(): void {
		if ( version_compare( PHP_VERSION, APD_MIN_PHP_VERSION, '<' ) ) {
			deactivate_plugins( APD_PLUGIN_BASENAME );
			wp_die(
				sprintf(
					/* translators: %s: Required PHP version */
					esc_html__( 'All Purpose Directory requires PHP %s or higher.', 'all-purpose-directory' ),
					esc_html( APD_MIN_PHP_VERSION )
				),
				'Plugin Activation Error',
				[ 'back_link' => true ]
			);
		}

		if ( version_compare( get_bloginfo( 'version' ), APD_MIN_WP_VERSION, '<' ) ) {
			deactivate_plugins( APD_PLUGIN_BASENAME );
			wp_die(
				sprintf(
					/* translators: %s: Required WordPress version */
					esc_html__( 'All Purpose Directory requires WordPress %s or higher.', 'all-purpose-directory' ),
					esc_html( APD_MIN_WP_VERSION )
				),
				'Plugin Activation Error',
				[ 'back_link' => true ]
			);
		}
	}

	/**
	 * Create database tables.
	 *
	 * @return void
	 */
	private static function create_tables(): void {
		// Custom tables will be created here when needed.
		// For now, the plugin uses WordPress post meta for most data.
	}

	/**
	 * Create default options.
	 *
	 * @return void
	 */
	private static function create_options(): void {
		// Use the same option name as the Settings class (apd_options).
		$option_name = \APD\Admin\Settings::OPTION_NAME;

		// Only add if option doesn't exist. Defaults are managed by the Settings class.
		if ( get_option( $option_name ) === false ) {
			add_option( $option_name, [] );
		}
	}

	/**
	 * Create custom roles and capabilities.
	 *
	 * @return void
	 */
	private static function create_roles(): void {
		// Add listing capabilities to administrator.
		$admin = get_role( 'administrator' );
		if ( $admin ) {
			foreach ( Capabilities::get_all() as $cap ) {
				$admin->add_cap( $cap );
			}
		}

		// Add basic listing capabilities to editor.
		$editor = get_role( 'editor' );
		if ( $editor ) {
			foreach ( Capabilities::get_editor_capabilities() as $cap ) {
				$editor->add_cap( $cap );
			}
		}

		// Add own listing capabilities to author.
		$author = get_role( 'author' );
		if ( $author ) {
			foreach ( Capabilities::get_author_capabilities() as $cap ) {
				$author->add_cap( $cap );
			}
		}
	}

	/**
	 * Create default pages with shortcodes.
	 *
	 * Creates pages only if the corresponding setting is empty or the page
	 * no longer exists. Stores page IDs in plugin settings for reference.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private static function create_default_pages(): void {
		$pages = [
			'directory_page' => [
				'title'   => __( 'Directory', 'all-purpose-directory' ),
				'content' => "<!-- wp:shortcode -->\n[apd_search_form]\n<!-- /wp:shortcode -->\n\n<!-- wp:shortcode -->\n[apd_listings]\n<!-- /wp:shortcode -->",
			],
			'submit_page'    => [
				'title'   => __( 'Submit a Listing', 'all-purpose-directory' ),
				'content' => "<!-- wp:shortcode -->\n[apd_submission_form]\n<!-- /wp:shortcode -->",
			],
			'dashboard_page' => [
				'title'   => __( 'My Dashboard', 'all-purpose-directory' ),
				'content' => "<!-- wp:shortcode -->\n[apd_dashboard]\n<!-- /wp:shortcode -->",
			],
		];

		/**
		 * Filter the default pages to create on activation.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, array{title: string, content: string}> $pages Page configurations keyed by setting name.
		 */
		$pages = apply_filters( 'apd_default_pages', $pages );

		if ( ! is_array( $pages ) || empty( $pages ) ) {
			return;
		}

		$option_name = \APD\Admin\Settings::OPTION_NAME;
		$options     = get_option( $option_name, [] );
		if ( ! is_array( $options ) ) {
			$options = [];
		}

		foreach ( $pages as $setting_key => $page_config ) {
			// Skip if page ID already set and page still exists.
			if ( ! empty( $options[ $setting_key ] ) ) {
				$existing = get_post( (int) $options[ $setting_key ] );
				if ( $existing && $existing->post_status !== 'trash' ) {
					continue;
				}
			}

			$page_id = wp_insert_post(
				[
					'post_title'   => $page_config['title'],
					'post_content' => $page_config['content'],
					'post_status'  => 'publish',
					'post_type'    => 'page',
				]
			);

			if ( $page_id && ! is_wp_error( $page_id ) ) {
				$options[ $setting_key ] = $page_id;
			}
		}

		update_option( $option_name, $options );
	}

	/**
	 * Schedule cron events.
	 *
	 * @return void
	 */
	private static function schedule_events(): void {
		// Schedule daily expiration check.
		if ( ! wp_next_scheduled( 'apd_check_expired_listings' ) ) {
			wp_schedule_event( time(), 'daily', 'apd_check_expired_listings' );
		}

		// Schedule hourly cleanup.
		if ( ! wp_next_scheduled( 'apd_cleanup_transients' ) ) {
			wp_schedule_event( time(), 'hourly', 'apd_cleanup_transients' );
		}
	}
}
