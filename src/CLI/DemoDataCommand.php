<?php
/**
 * WP-CLI Demo Data Command.
 *
 * Provides CLI commands for generating and managing demo data.
 *
 * @package APD\CLI
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\CLI;

use APD\Admin\DemoData\DemoDataGenerator;
use APD\Admin\DemoData\DemoDataPage;
use APD\Admin\DemoData\DemoDataProviderRegistry;
use APD\Admin\DemoData\DemoDataTracker;
use APD\Contracts\DemoDataModuleProviderInterface;
use WP_CLI;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manage demo data for the All Purpose Directory plugin.
 *
 * ## EXAMPLES
 *
 *     # Generate all demo data with defaults
 *     $ wp apd demo generate
 *
 *     # Generate only listings and reviews
 *     $ wp apd demo generate --types=categories,tags,listings,reviews
 *
 *     # Generate 50 listings
 *     $ wp apd demo generate --listings=50
 *
 *     # Generate data for a specific module
 *     $ wp apd demo generate --module=url-directory
 *
 *     # Show current demo data counts
 *     $ wp apd demo status
 *
 *     # Show counts for a specific module
 *     $ wp apd demo status --module=general
 *
 *     # Delete all demo data
 *     $ wp apd demo delete
 *
 *     # Delete only a specific module's data
 *     $ wp apd demo delete --module=general
 *
 * @since 1.0.0
 */
final class DemoDataCommand {

	/**
	 * Ensure the DemoDataProviderRegistry is initialized.
	 *
	 * In WP-CLI context, the registry's init() is not triggered by
	 * DemoDataPage (which requires is_admin()). This method ensures
	 * the `apd_demo_providers_init` action fires so module plugins
	 * can register their providers.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function ensure_providers_initialized(): void {
		DemoDataProviderRegistry::get_instance()->init();
	}

	/**
	 * Get valid module slugs for validation.
	 *
	 * @since 1.2.0
	 *
	 * @return string[] Valid module slugs.
	 */
	private function get_valid_modules(): array {
		$modules = [ DemoDataTracker::GENERAL_MODULE ];

		$providers = DemoDataProviderRegistry::get_instance()->get_all();
		foreach ( $providers as $provider ) {
			if ( $provider instanceof DemoDataModuleProviderInterface ) {
				$modules[] = $provider->get_slug();
			}
		}

		return $modules;
	}

	/**
	 * Validate a module slug.
	 *
	 * @since 1.2.0
	 *
	 * @param string $module Module slug to validate.
	 * @return bool True if valid.
	 */
	private function validate_module( string $module ): bool {
		$valid = $this->get_valid_modules();

		if ( ! in_array( $module, $valid, true ) ) {
			WP_CLI::error(
				sprintf(
					"Invalid module '%s'. Valid modules: %s",
					$module,
					implode( ', ', $valid )
				)
			);
			return false;
		}

		return true;
	}

	/**
	 * Generate demo data.
	 *
	 * ## OPTIONS
	 *
	 * [--types=<types>]
	 * : Comma-separated list of data types to generate.
	 * Options: users,categories,tags,listings,reviews,inquiries,favorites,all
	 * ---
	 * default: all
	 * ---
	 *
	 * [--users=<count>]
	 * : Number of users to create (max 20).
	 * ---
	 * default: 5
	 * ---
	 *
	 * [--tags=<count>]
	 * : Number of tags to create (max 10).
	 * ---
	 * default: 10
	 * ---
	 *
	 * [--listings=<count>]
	 * : Number of listings to create (max 100).
	 * ---
	 * default: 25
	 * ---
	 *
	 * [--module=<slug>]
	 * : Generate data for a specific module only. Use 'general' for core data.
	 * ---
	 * default: all
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Generate all demo data with defaults
	 *     $ wp apd demo generate
	 *
	 *     # Generate 50 listings with reviews
	 *     $ wp apd demo generate --types=categories,tags,listings,reviews --listings=50
	 *
	 *     # Generate only users and categories
	 *     $ wp apd demo generate --types=users,categories --users=10
	 *
	 *     # Generate data for url-directory module only
	 *     $ wp apd demo generate --module=url-directory --listings=30
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public function generate( array $args, array $assoc_args ): void {
		$this->ensure_providers_initialized();

		$types_input = $assoc_args['types'] ?? 'all';
		$types       = array_map( 'trim', explode( ',', $types_input ) );
		$all_types   = in_array( 'all', $types, true );

		$users_count    = min( absint( $assoc_args['users'] ?? 5 ), 20 );
		$tags_count     = min( absint( $assoc_args['tags'] ?? 10 ), 10 );
		$listings_count = min( absint( $assoc_args['listings'] ?? 25 ), 100 );

		$module_flag = $assoc_args['module'] ?? 'all';
		$all_modules = ( $module_flag === 'all' );

		// Validate module if specified.
		if ( ! $all_modules ) {
			$this->validate_module( $module_flag );
		}

		/**
		 * Fires before demo data generation begins.
		 *
		 * @since 1.0.0
		 */
		do_action( 'apd_before_generate_demo_data' );

		$generator = DemoDataGenerator::get_instance();
		$results   = [];

		// Track created IDs for dependent operations.
		$user_ids    = [];
		$listing_ids = [];

		// Generate users (shared, always generated unless module-specific).
		if ( ( $all_types || in_array( 'users', $types, true ) ) && $all_modules ) {
			WP_CLI::log( "Creating {$users_count} users..." );
			$user_ids         = $generator->generate_users( $users_count );
			$results['users'] = count( $user_ids );
			WP_CLI::log( "  Created {$results['users']} users." );
		} else {
			// Get existing demo users for dependent operations.
			$user_ids = DemoDataTracker::get_instance()->get_demo_user_ids();
		}

		// Determine which modules to generate for.
		$modules_to_generate = [];

		if ( $all_modules ) {
			$modules_to_generate[] = DemoDataTracker::GENERAL_MODULE;
			$providers             = DemoDataProviderRegistry::get_instance()->get_all();
			foreach ( $providers as $provider ) {
				if ( $provider instanceof DemoDataModuleProviderInterface ) {
					$modules_to_generate[] = $provider->get_slug();
				}
			}
		} else {
			$modules_to_generate[] = $module_flag;
		}

		foreach ( $modules_to_generate as $module ) {
			$is_general = ( $module === DemoDataTracker::GENERAL_MODULE );
			$label      = $is_general ? 'General' : $module;

			WP_CLI::log( '' );
			WP_CLI::log( "--- {$label} ---" );

			$generator->reset_state();

			// Generate categories.
			if ( $all_types || in_array( 'categories', $types, true ) ) {
				WP_CLI::log( 'Creating categories...' );
				$category_data = [];

				if ( ! $is_general ) {
					$provider = DemoDataProviderRegistry::get_instance()->get( $module );
					if ( $provider instanceof DemoDataModuleProviderInterface ) {
						$category_data = $provider->get_category_data();
					}
				}

				$category_ids                       = $generator->generate_categories( $module, $category_data );
				$results[ $module . '_categories' ] = count( $category_ids );
				WP_CLI::log( '  Created ' . count( $category_ids ) . ' categories.' );
			}

			// Generate tags.
			if ( $all_types || in_array( 'tags', $types, true ) ) {
				WP_CLI::log( "Creating {$tags_count} tags..." );
				$tag_ids                      = $generator->generate_tags( $tags_count, $module );
				$results[ $module . '_tags' ] = count( $tag_ids );
				WP_CLI::log( '  Created ' . count( $tag_ids ) . ' tags.' );
			}

			// Generate listings.
			if ( $all_types || in_array( 'listings', $types, true ) ) {
				WP_CLI::log( "Creating {$listings_count} listings..." );
				$listing_ids                      = $generator->generate_listings( $listings_count, $module );
				$results[ $module . '_listings' ] = count( $listing_ids );
				WP_CLI::log( '  Created ' . count( $listing_ids ) . ' listings.' );
			}

			// Generate reviews.
			if ( ( $all_types || in_array( 'reviews', $types, true ) ) && ! empty( $listing_ids ) ) {
				WP_CLI::log( 'Creating reviews...' );
				$review_ids                      = $generator->generate_reviews( $listing_ids, $user_ids, $module );
				$results[ $module . '_reviews' ] = count( $review_ids );
				WP_CLI::log( '  Created ' . count( $review_ids ) . ' reviews.' );
			}

			// Generate inquiries.
			if ( ( $all_types || in_array( 'inquiries', $types, true ) ) && ! empty( $listing_ids ) ) {
				WP_CLI::log( 'Creating inquiries...' );
				$inquiry_ids                       = $generator->generate_inquiries( $listing_ids, $module );
				$results[ $module . '_inquiries' ] = count( $inquiry_ids );
				WP_CLI::log( '  Created ' . count( $inquiry_ids ) . ' inquiries.' );
			}

			// Generate favorites.
			if ( ( $all_types || in_array( 'favorites', $types, true ) ) && ! empty( $listing_ids ) && ! empty( $user_ids ) ) {
				WP_CLI::log( 'Creating favorites...' );
				$fav_count                         = $generator->generate_favorites( $listing_ids, $user_ids );
				$results[ $module . '_favorites' ] = $fav_count;
				WP_CLI::log( "  Created {$fav_count} favorites." );
			}

			// Module-specific data for non-general modules.
			if ( ! $is_general ) {
				$provider = DemoDataProviderRegistry::get_instance()->get( $module );
				if ( $provider ) {
					$tracker = DemoDataTracker::get_instance();
					$context = [
						'user_ids'     => $user_ids,
						'listing_ids'  => $listing_ids,
						'category_ids' => isset( $category_ids ) ? $category_ids : [],
						'tag_ids'      => isset( $tag_ids ) ? $tag_ids : [],
						'options'      => [],
					];

					WP_CLI::log( "Creating {$label} module-specific data..." );
					$provider_results = $provider->generate( $context, $tracker );

					foreach ( $provider_results as $type => $count ) {
						$results[ $module . '_' . $type ] = $count;
						WP_CLI::log( "  Created {$count} {$type}." );
					}
				}
			}
		}

		/**
		 * Fires after demo data generation completes.
		 *
		 * @since 1.0.0
		 *
		 * @param array $results Number of items created by type.
		 */
		do_action( 'apd_after_generate_demo_data', $results );

		// Summary.
		WP_CLI::success( 'Demo data generated.' );
		$this->print_status();
	}

	/**
	 * Delete demo data.
	 *
	 * ## OPTIONS
	 *
	 * [--module=<slug>]
	 * : Delete data for a specific module only. Use 'general' for core data, 'users' for demo users.
	 * ---
	 * default: all
	 * ---
	 *
	 * [--yes]
	 * : Skip the confirmation prompt.
	 *
	 * ## EXAMPLES
	 *
	 *     # Delete all demo data
	 *     $ wp apd demo delete --yes
	 *
	 *     # Delete only General demo data
	 *     $ wp apd demo delete --module=general --yes
	 *
	 *     # Delete only url-directory module data
	 *     $ wp apd demo delete --module=url-directory --yes
	 *
	 *     # Delete only demo users (must delete module data first)
	 *     $ wp apd demo delete --module=users --yes
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public function delete( array $args, array $assoc_args ): void {
		$this->ensure_providers_initialized();

		$module_flag = $assoc_args['module'] ?? 'all';
		$tracker     = DemoDataTracker::get_instance();

		if ( $module_flag === 'all' ) {
			// Delete everything.
			$counts = $tracker->count_demo_data();
			$total  = array_sum( $counts );

			if ( $total === 0 ) {
				WP_CLI::success( 'No demo data found. Nothing to delete.' );
				return;
			}

			WP_CLI::log( "Found {$total} demo data items." );
			WP_CLI::confirm( 'Are you sure you want to delete ALL demo data?', $assoc_args );

			WP_CLI::log( 'Deleting demo data...' );
			$deleted = $tracker->delete_all();
		} elseif ( $module_flag === DemoDataTracker::USERS_MODULE ) {
			// Delete only users.
			if ( $tracker->has_module_demo_data() ) {
				WP_CLI::error( 'Cannot delete demo users while module demo data exists. Delete all module data first.' );
				return;
			}

			$user_count = count( $tracker->get_demo_user_ids() );
			if ( $user_count === 0 ) {
				WP_CLI::success( 'No demo users found.' );
				return;
			}

			WP_CLI::log( "Found {$user_count} demo users." );
			WP_CLI::confirm( 'Delete all demo users?', $assoc_args );

			WP_CLI::log( 'Deleting demo users...' );
			$deleted = [ 'users' => $tracker->delete_demo_users() ];
		} else {
			// Validate module.
			$valid_modules = $this->get_valid_modules();
			if ( ! in_array( $module_flag, $valid_modules, true ) ) {
				WP_CLI::error(
					sprintf(
						"Invalid module '%s'. Valid modules: %s, users",
						$module_flag,
						implode( ', ', $valid_modules )
					)
				);
				return;
			}

			// Delete specific module.
			$counts = $tracker->count_demo_data( $module_flag );
			$total  = array_sum( $counts );

			if ( $total === 0 ) {
				WP_CLI::success( "No demo data found for module '{$module_flag}'." );
				return;
			}

			WP_CLI::log( "Found {$total} demo data items for module '{$module_flag}'." );
			WP_CLI::confirm( "Delete all demo data for module '{$module_flag}'?", $assoc_args );

			WP_CLI::log( "Deleting {$module_flag} demo data..." );
			$deleted = $tracker->delete_by_module( $module_flag );
		}

		// Report results.
		foreach ( $deleted as $type => $count ) {
			if ( $count > 0 ) {
				WP_CLI::log( "  Deleted {$count} {$type}." );
			}
		}

		WP_CLI::success( 'Demo data deleted.' );
	}

	/**
	 * Show current demo data status.
	 *
	 * ## OPTIONS
	 *
	 * [--module=<slug>]
	 * : Show counts for a specific module only.
	 * ---
	 * default: all
	 * ---
	 *
	 * [--format=<format>]
	 * : Output format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - csv
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Show status as table
	 *     $ wp apd demo status
	 *
	 *     # Show status as JSON
	 *     $ wp apd demo status --format=json
	 *
	 *     # Show status for a specific module
	 *     $ wp apd demo status --module=general
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public function status( array $args, array $assoc_args ): void {
		$this->ensure_providers_initialized();

		$format      = $assoc_args['format'] ?? 'table';
		$module_flag = $assoc_args['module'] ?? 'all';
		$tracker     = DemoDataTracker::get_instance();

		if ( $module_flag !== 'all' ) {
			// Show counts for a specific module.
			$valid = array_merge( $this->get_valid_modules(), [ DemoDataTracker::USERS_MODULE ] );
			if ( ! in_array( $module_flag, $valid, true ) ) {
				WP_CLI::error(
					sprintf(
						"Invalid module '%s'. Valid modules: %s",
						$module_flag,
						implode( ', ', $valid )
					)
				);
				return;
			}

			$counts = $tracker->count_demo_data( $module_flag );
			$this->format_status_output( $counts, $module_flag, $format );
			return;
		}

		// Show all counts grouped by module.
		$items = [];

		// Users (shared).
		$user_counts = $tracker->count_demo_data( DemoDataTracker::USERS_MODULE );
		$items[]     = [
			'module' => 'Users (shared)',
			'type'   => 'Users',
			'count'  => $user_counts['users'] ?? 0,
		];

		// General module.
		$general_counts = $tracker->count_demo_data( DemoDataTracker::GENERAL_MODULE );
		foreach ( $general_counts as $type => $count ) {
			if ( $type === 'users' ) {
				continue;
			}
			$items[] = [
				'module' => 'General',
				'type'   => ucfirst( str_replace( '_', ' ', $type ) ),
				'count'  => $count,
			];
		}

		// Module providers.
		$providers = DemoDataProviderRegistry::get_instance()->get_all();
		foreach ( $providers as $provider ) {
			if ( ! ( $provider instanceof DemoDataModuleProviderInterface ) ) {
				continue;
			}

			$slug          = $provider->get_slug();
			$module_counts = $tracker->count_demo_data( $slug );

			foreach ( $module_counts as $type => $count ) {
				if ( $type === 'users' ) {
					continue;
				}
				$items[] = [
					'module' => $provider->get_name(),
					'type'   => ucfirst( str_replace( '_', ' ', $type ) ),
					'count'  => $count,
				];
			}

			// Module-specific counts.
			$provider_counts = $provider->count( $tracker );
			foreach ( $provider_counts as $type => $count ) {
				$items[] = [
					'module' => $provider->get_name(),
					'type'   => ucfirst( str_replace( '_', ' ', $type ) ),
					'count'  => $count,
				];
			}
		}

		// Total.
		$total = 0;
		foreach ( $items as $item ) {
			$total += $item['count'];
		}

		$items[] = [
			'module' => '---',
			'type'   => 'Total',
			'count'  => $total,
		];

		WP_CLI\Utils\format_items( $format, $items, [ 'module', 'type', 'count' ] );
	}

	/**
	 * Format and output status for a single module.
	 *
	 * @param array<string, int> $counts  Counts by type.
	 * @param string             $module  Module slug.
	 * @param string             $format  Output format.
	 * @return void
	 */
	private function format_status_output( array $counts, string $module, string $format ): void {
		$items = [];
		$total = 0;

		foreach ( $counts as $type => $count ) {
			$items[] = [
				'type'  => ucfirst( str_replace( '_', ' ', $type ) ),
				'count' => $count,
			];
			$total  += $count;
		}

		$items[] = [
			'type'  => '---',
			'count' => '---',
		];
		$items[] = [
			'type'  => 'Total',
			'count' => $total,
		];

		WP_CLI\Utils\format_items( $format, $items, [ 'type', 'count' ] );
	}

	/**
	 * Print the current status table.
	 *
	 * @return void
	 */
	private function print_status(): void {
		$tracker = DemoDataTracker::get_instance();

		WP_CLI::log( '' );
		WP_CLI::log( 'Current demo data:' );

		// Users.
		$user_counts = $tracker->count_demo_data( DemoDataTracker::USERS_MODULE );
		WP_CLI::log( '  Users (shared): ' . ( $user_counts['users'] ?? 0 ) );

		// General.
		$general_counts = $tracker->count_demo_data( DemoDataTracker::GENERAL_MODULE );
		$general_total  = 0;
		foreach ( $general_counts as $type => $count ) {
			if ( $type === 'users' ) {
				continue;
			}
			$general_total += $count;
		}
		WP_CLI::log( "  General: {$general_total}" );

		// Modules.
		$providers = DemoDataProviderRegistry::get_instance()->get_all();
		foreach ( $providers as $provider ) {
			if ( ! ( $provider instanceof DemoDataModuleProviderInterface ) ) {
				continue;
			}

			$slug          = $provider->get_slug();
			$module_counts = $tracker->count_demo_data( $slug );
			$module_total  = 0;
			foreach ( $module_counts as $type => $count ) {
				if ( $type === 'users' ) {
					continue;
				}
				$module_total += $count;
			}

			$provider_counts = $provider->count( $tracker );
			$module_total   += array_sum( $provider_counts );

			WP_CLI::log( "  {$provider->get_name()}: {$module_total}" );
		}

		// Grand total.
		$all_counts  = $tracker->count_demo_data();
		$grand_total = array_sum( $all_counts );

		// Add module provider counts.
		foreach ( $providers as $provider ) {
			$provider_counts = $provider->count( $tracker );
			$grand_total    += array_sum( $provider_counts );
		}

		WP_CLI::log( "  Total: {$grand_total}" );
	}
}
