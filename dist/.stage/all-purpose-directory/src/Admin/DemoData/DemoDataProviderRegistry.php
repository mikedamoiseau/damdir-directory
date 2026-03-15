<?php
/**
 * Demo Data Provider Registry.
 *
 * Central registry for managing module demo data providers.
 *
 * @package APD\Admin\DemoData
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Admin\DemoData;

use APD\Contracts\DemoDataProviderInterface;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DemoDataProviderRegistry
 *
 * Singleton registry for demo data providers. Module plugins register
 * their providers here to participate in demo data generation and deletion.
 *
 * @since 1.0.0
 */
final class DemoDataProviderRegistry {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Registered providers.
	 *
	 * @var array<string, DemoDataProviderInterface>
	 */
	private array $providers = [];

	/**
	 * Whether the registry has been initialized.
	 *
	 * @var bool
	 */
	private bool $initialized = false;

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
	 * Initialize the provider registry.
	 *
	 * Fires the init action to allow module plugins to register their providers.
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
		 * Fires when demo data providers can be registered.
		 *
		 * Module plugins should hook into this action to register their
		 * DemoDataProviderInterface implementations.
		 *
		 * @since 1.0.0
		 *
		 * @param DemoDataProviderRegistry $registry The provider registry instance.
		 */
		do_action( 'apd_demo_providers_init', $this );
	}

	/**
	 * Register a demo data provider.
	 *
	 * @since 1.0.0
	 *
	 * @param DemoDataProviderInterface $provider The provider instance.
	 * @return bool True if registered successfully, false on failure.
	 */
	public function register( DemoDataProviderInterface $provider ): bool {
		$slug = $provider->get_slug();

		// Validate slug.
		if ( empty( $slug ) ) {
			_doing_it_wrong(
				__METHOD__,
				esc_html__( 'Demo data provider slug cannot be empty.', 'all-purpose-directory' ),
				'1.0.0'
			);
			return false;
		}

		// Sanitize slug.
		$slug = sanitize_key( $slug );

		// Check for duplicate registration.
		if ( isset( $this->providers[ $slug ] ) ) {
			_doing_it_wrong(
				__METHOD__,
				sprintf(
					/* translators: %s: provider slug */
					esc_html__( 'Demo data provider "%s" is already registered.', 'all-purpose-directory' ),
					esc_html( $slug )
				),
				'1.0.0'
			);
			return false;
		}

		$this->providers[ $slug ] = $provider;

		/**
		 * Fires after a demo data provider is registered.
		 *
		 * @since 1.0.0
		 *
		 * @param string                     $slug     Provider slug.
		 * @param DemoDataProviderInterface $provider The provider instance.
		 */
		do_action( 'apd_demo_provider_registered', $slug, $provider );

		return true;
	}

	/**
	 * Unregister a demo data provider.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Provider slug to unregister.
	 * @return bool True if unregistered, false if provider didn't exist.
	 */
	public function unregister( string $slug ): bool {
		$slug = sanitize_key( $slug );

		if ( ! isset( $this->providers[ $slug ] ) ) {
			return false;
		}

		$provider = $this->providers[ $slug ];
		unset( $this->providers[ $slug ] );

		/**
		 * Fires after a demo data provider is unregistered.
		 *
		 * @since 1.0.0
		 *
		 * @param string                     $slug     Provider slug.
		 * @param DemoDataProviderInterface $provider The provider instance that was removed.
		 */
		do_action( 'apd_demo_provider_unregistered', $slug, $provider );

		return true;
	}

	/**
	 * Get a registered provider by slug.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Provider slug.
	 * @return DemoDataProviderInterface|null The provider or null if not found.
	 */
	public function get( string $slug ): ?DemoDataProviderInterface {
		$slug = sanitize_key( $slug );

		return $this->providers[ $slug ] ?? null;
	}

	/**
	 * Get all registered providers.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, DemoDataProviderInterface> Providers keyed by slug.
	 */
	public function get_all(): array {
		return $this->providers;
	}

	/**
	 * Check if a provider is registered.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Provider slug.
	 * @return bool True if registered.
	 */
	public function has( string $slug ): bool {
		return isset( $this->providers[ sanitize_key( $slug ) ] );
	}

	/**
	 * Get the count of registered providers.
	 *
	 * @since 1.0.0
	 *
	 * @return int Number of registered providers.
	 */
	public function count(): int {
		return count( $this->providers );
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
	 * Clears all registered providers. Primarily used for testing.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function reset(): void {
		$this->providers   = [];
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
