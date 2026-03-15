<?php
/**
 * Module Registry.
 *
 * Central registry for managing external modules that extend the core plugin.
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
 * Class ModuleRegistry
 *
 * Singleton class for registering and managing modules.
 *
 * @since 1.0.0
 */
final class ModuleRegistry {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Registered modules.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private array $modules = [];

	/**
	 * Whether the registry has been initialized.
	 *
	 * @var bool
	 */
	private bool $initialized = false;

	/**
	 * Default module configuration.
	 *
	 * @var array<string, mixed>
	 */
	private const DEFAULT_CONFIG = [
		'name'          => '',
		'description'   => '',
		'version'       => '1.0.0',
		'author'        => '',
		'author_uri'    => '',
		'requires'      => [],
		'features'      => [],
		'hidden_fields' => [],
		'icon'          => 'dashicons-admin-plugins',
		'priority'      => 10,
	];

	/**
	 * Get the singleton instance.
	 *
	 * @since 1.0.0
	 *
	 * @return self
	 */
	public static function get_instance(): self {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Private constructor to prevent direct instantiation.
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
	 * Initialize the module registry.
	 *
	 * Fires the init action to allow modules to register.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init(): void {
		if ( $this->initialized ) {
			return;
		}

		$this->initialized = true;

		/**
		 * Fires when modules can be registered.
		 *
		 * External modules should hook into this action to register themselves.
		 *
		 * @since 1.0.0
		 *
		 * @param ModuleRegistry $registry The module registry instance.
		 */
		do_action( 'apd_modules_init', $this );

		/**
		 * Fires after all modules have been loaded.
		 *
		 * @since 1.0.0
		 *
		 * @param ModuleRegistry $registry The module registry instance.
		 */
		do_action( 'apd_modules_loaded', $this );
	}

	/**
	 * Register a module using array configuration.
	 *
	 * @since 1.0.0
	 *
	 * @param string               $slug   Unique module identifier.
	 * @param array<string, mixed> $config Module configuration.
	 * @return bool True if registered successfully, false on failure.
	 */
	public function register( string $slug, array $config = [] ): bool {
		// Validate slug.
		if ( empty( $slug ) ) {
			_doing_it_wrong(
				__METHOD__,
				esc_html__( 'Module slug cannot be empty.', 'all-purpose-directory' ),
				'1.0.0'
			);
			return false;
		}

		// Sanitize slug.
		$slug = sanitize_key( $slug );

		// Check for duplicate registration.
		if ( isset( $this->modules[ $slug ] ) ) {
			_doing_it_wrong(
				__METHOD__,
				sprintf(
					/* translators: %s: module slug */
					esc_html__( 'Module "%s" is already registered.', 'all-purpose-directory' ),
					esc_html( $slug )
				),
				'1.0.0'
			);
			return false;
		}

		// Validate required fields.
		if ( empty( $config['name'] ) ) {
			_doing_it_wrong(
				__METHOD__,
				sprintf(
					/* translators: %s: module slug */
					esc_html__( 'Module "%s" is missing required "name" field.', 'all-purpose-directory' ),
					esc_html( $slug )
				),
				'1.0.0'
			);
			return false;
		}

		// Merge with defaults.
		$config = wp_parse_args( $config, self::DEFAULT_CONFIG );

		// Ensure slug is set in config.
		$config['slug'] = $slug;

		// Ensure priority is an integer.
		$config['priority'] = absint( $config['priority'] );

		// Ensure requires, features, and hidden_fields are arrays.
		if ( ! is_array( $config['requires'] ) ) {
			$config['requires'] = [];
		}
		if ( ! is_array( $config['features'] ) ) {
			$config['features'] = [];
		}
		if ( ! is_array( $config['hidden_fields'] ) ) {
			$config['hidden_fields'] = [];
		}

		/**
		 * Filter the module configuration before registration.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, mixed> $config Module configuration.
		 * @param string               $slug   Module slug.
		 */
		$config = apply_filters( 'apd_register_module_config', $config, $slug );

		$this->modules[ $slug ] = $config;

		/**
		 * Fires after a module is registered.
		 *
		 * @since 1.0.0
		 *
		 * @param string               $slug   Module slug.
		 * @param array<string, mixed> $config Module configuration.
		 */
		do_action( 'apd_module_registered', $slug, $config );

		return true;
	}

	/**
	 * Register a module using a class instance.
	 *
	 * @since 1.0.0
	 *
	 * @param ModuleInterface $module The module instance.
	 * @return bool True if registered successfully, false on failure.
	 */
	public function register_module( ModuleInterface $module ): bool {
		$slug   = $module->get_slug();
		$config = $module->get_config();

		// Ensure required fields are populated from interface methods.
		$config['name']        = $module->get_name();
		$config['description'] = $module->get_description();
		$config['version']     = $module->get_version();
		$config['instance']    = $module;

		$result = $this->register( $slug, $config );

		// Initialize the module if registration was successful.
		if ( $result ) {
			$module->init();
		}

		return $result;
	}

	/**
	 * Unregister a module.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Module slug to unregister.
	 * @return bool True if unregistered, false if module didn't exist.
	 */
	public function unregister( string $slug ): bool {
		$slug = sanitize_key( $slug );

		if ( ! isset( $this->modules[ $slug ] ) ) {
			return false;
		}

		$config = $this->modules[ $slug ];
		unset( $this->modules[ $slug ] );

		/**
		 * Fires after a module is unregistered.
		 *
		 * @since 1.0.0
		 *
		 * @param string               $slug   Module slug.
		 * @param array<string, mixed> $config Module configuration that was removed.
		 */
		do_action( 'apd_module_unregistered', $slug, $config );

		return true;
	}

	/**
	 * Get a registered module by slug.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Module slug.
	 * @return array<string, mixed>|null Module configuration or null if not found.
	 */
	public function get( string $slug ): ?array {
		$slug = sanitize_key( $slug );

		if ( ! isset( $this->modules[ $slug ] ) ) {
			return null;
		}

		/**
		 * Filter the module configuration when retrieved.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, mixed> $config Module configuration.
		 * @param string               $slug   Module slug.
		 */
		return apply_filters( 'apd_get_module', $this->modules[ $slug ], $slug );
	}

	/**
	 * Get all registered modules.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $args Optional. Arguments to filter/sort modules.
	 *                                   - 'orderby': Order by 'priority', 'name', or 'slug'.
	 *                                   - 'order': 'ASC' or 'DESC'.
	 *                                   - 'feature': Filter by feature support.
	 * @return array<string, array<string, mixed>> Array of module configurations keyed by slug.
	 */
	public function get_all( array $args = [] ): array {
		$defaults = [
			'orderby' => 'priority',
			'order'   => 'ASC',
			'feature' => null,
		];

		$args = wp_parse_args( $args, $defaults );

		$modules = $this->modules;

		// Filter by feature if specified.
		if ( $args['feature'] !== null ) {
			$feature = sanitize_key( $args['feature'] );
			$modules = array_filter(
				$modules,
				fn( $module ) => in_array( $feature, $module['features'], true )
			);
		}

		// Sort modules.
		$orderby = $args['orderby'];
		$order   = strtoupper( $args['order'] ) === 'DESC' ? -1 : 1;

		uasort(
			$modules,
			function ( $a, $b ) use ( $orderby, $order ) {
				if ( $orderby === 'name' ) {
					return strcmp( $a['name'], $b['name'] ) * $order;
				}

				if ( $orderby === 'slug' ) {
					return strcmp( $a['slug'], $b['slug'] ) * $order;
				}

				// Default: sort by priority.
				return ( $a['priority'] <=> $b['priority'] ) * $order;
			}
		);

		/**
		 * Filter the retrieved modules.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, array<string, mixed>> $modules Filtered modules.
		 * @param array<string, mixed>                $args    Query arguments.
		 */
		return apply_filters( 'apd_get_modules', $modules, $args );
	}

	/**
	 * Check if a module is registered.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Module slug.
	 * @return bool True if registered.
	 */
	public function has( string $slug ): bool {
		return isset( $this->modules[ sanitize_key( $slug ) ] );
	}

	/**
	 * Get the count of registered modules.
	 *
	 * @since 1.0.0
	 *
	 * @return int Number of registered modules.
	 */
	public function count(): int {
		return count( $this->modules );
	}

	/**
	 * Check module requirements.
	 *
	 * Validates that all required dependencies are met.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, string> $requires Array of requirements (e.g., ['core' => '1.0.0']).
	 * @return array<string, string> Array of unmet requirements with error messages, empty if all met.
	 */
	public function check_requirements( array $requires ): array {
		$unmet = [];

		foreach ( $requires as $dependency => $required_version ) {
			// Check core plugin version.
			if ( $dependency === 'core' ) {
				if ( version_compare( APD_VERSION, $required_version, '<' ) ) {
					$unmet[ $dependency ] = sprintf(
						/* translators: 1: Required version, 2: Current version */
						__( 'Requires All Purpose Directory %1$s or higher (current: %2$s)', 'all-purpose-directory' ),
						$required_version,
						APD_VERSION
					);
				}
				continue;
			}

			// Check if dependent module is registered.
			if ( ! $this->has( $dependency ) ) {
				$unmet[ $dependency ] = sprintf(
					/* translators: %s: Module slug */
					__( 'Requires module "%s" which is not installed', 'all-purpose-directory' ),
					$dependency
				);
				continue;
			}

			// Check module version.
			$module = $this->get( $dependency );
			if ( $module && version_compare( $module['version'], $required_version, '<' ) ) {
				$unmet[ $dependency ] = sprintf(
					/* translators: 1: Module name, 2: Required version, 3: Current version */
					__( 'Requires %1$s %2$s or higher (current: %3$s)', 'all-purpose-directory' ),
					$module['name'],
					$required_version,
					$module['version']
				);
			}
		}

		return $unmet;
	}

	/**
	 * Get modules that support a specific feature.
	 *
	 * @since 1.0.0
	 *
	 * @param string $feature The feature to filter by.
	 * @return array<string, array<string, mixed>> Array of modules that support the feature.
	 */
	public function get_by_feature( string $feature ): array {
		return $this->get_all( [ 'feature' => $feature ] );
	}

	/**
	 * Check if the registry has been initialized.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if initialized.
	 */
	public function is_initialized(): bool {
		return $this->initialized;
	}

	/**
	 * Reset the registry.
	 *
	 * Clears all registered modules. Primarily used for testing.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function reset(): void {
		$this->modules     = [];
		$this->initialized = false;
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
