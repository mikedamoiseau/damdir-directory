<?php
/**
 * Plugin uninstall handler.
 *
 * Fired when the plugin is deleted (not just deactivated).
 * This file is called by WordPress automatically.
 *
 * @package APD
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Exit if not called by WordPress uninstall.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Load Composer autoloader for access to plugin classes.
$autoloader = __DIR__ . '/vendor/autoload.php';
if ( file_exists( $autoloader ) ) {
	require_once $autoloader;
}

use APD\Core\Capabilities;

/**
 * Perform plugin cleanup.
 *
 * This function removes all plugin data from the database.
 * Only runs when the plugin is deleted, not when deactivated.
 */
function apd_uninstall(): void {
	global $wpdb;

	// Check if user wants to keep data.
	$settings = get_option( 'apd_options', [] );
	if ( empty( $settings['delete_data'] ) ) {
		return;
	}

	// Delete all listings and inquiries in batches to prevent memory issues.
	$post_types = [ 'apd_listing', 'apd_inquiry' ];
	$batch_size = 100;

	foreach ( $post_types as $post_type ) {
		do {
			$posts = get_posts(
				[
					'post_type'      => $post_type,
					'posts_per_page' => $batch_size,
					'post_status'    => 'any',
					'fields'         => 'ids',
				]
			);

			foreach ( $posts as $post_id ) {
				wp_delete_post( $post_id, true );
			}
		} while ( count( $posts ) === $batch_size );
	}

	// Delete all terms from custom taxonomies.
	$taxonomies = [ 'apd_category', 'apd_tag', 'apd_listing_type' ];
	foreach ( $taxonomies as $taxonomy ) {
		$terms = get_terms(
			[
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'fields'     => 'ids',
			]
		);

		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term_id ) {
				wp_delete_term( $term_id, $taxonomy );
			}
		}
	}

	// Delete plugin options.
	$options = [
		'apd_options',
		'apd_version',
		'apd_db_version',
		'apd_activation_time',
		'apd_flush_rewrite_rules',
		'apd_cache_key_registry',
	];

	foreach ( $options as $option ) {
		delete_option( $option );
	}

	// Delete all post meta with plugin prefix.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall cleanup intentionally performs one-time direct bulk deletes.
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s",
			'_apd_%'
		)
	);

	// Delete all user meta with plugin prefix.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall cleanup intentionally performs one-time direct bulk deletes.
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s",
			'_apd_%'
		)
	);

	// Delete transients.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall cleanup intentionally performs one-time direct bulk deletes.
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			'_transient_apd_%',
			'_transient_timeout_apd_%'
		)
	);

	// Remove custom capabilities from roles.
	$capabilities = class_exists( Capabilities::class )
		? Capabilities::get_all()
		: [
			'edit_apd_listing',
			'read_apd_listing',
			'delete_apd_listing',
			'edit_apd_listings',
			'edit_others_apd_listings',
			'publish_apd_listings',
			'read_private_apd_listings',
			'delete_apd_listings',
			'delete_private_apd_listings',
			'delete_published_apd_listings',
			'delete_others_apd_listings',
			'edit_private_apd_listings',
			'edit_published_apd_listings',
			'manage_apd_categories',
			'manage_apd_tags',
		];

	$roles = [ 'administrator', 'editor', 'author' ];
	foreach ( $roles as $role_name ) {
		$role = get_role( $role_name );
		if ( $role ) {
			foreach ( $capabilities as $cap ) {
				$role->remove_cap( $cap );
			}
		}
	}

	// Clear scheduled events.
	wp_clear_scheduled_hook( 'apd_check_expired_listings' );
	wp_clear_scheduled_hook( 'apd_cleanup_transients' );

	// Flush rewrite rules.
	flush_rewrite_rules();
}

apd_uninstall();
