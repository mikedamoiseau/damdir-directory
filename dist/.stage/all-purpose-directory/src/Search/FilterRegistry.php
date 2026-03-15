<?php
/**
 * Filter Registry.
 *
 * Central registry for managing search filters. Handles filter
 * registration, retrieval, and active filter tracking.
 *
 * @package APD\Search
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Search;

use APD\Contracts\FilterInterface;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FilterRegistry
 *
 * Singleton class for registering and managing search filters.
 *
 * @since 1.0.0
 */
final class FilterRegistry {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Registered filters.
	 *
	 * @var array<string, FilterInterface>
	 */
	private array $filters = [];

	/**
	 * Default filter configuration.
	 *
	 * @var array<string, mixed>
	 */
	private const DEFAULT_FILTER_CONFIG = [
		'name'           => '',
		'type'           => 'select',
		'label'          => '',
		'source'         => 'custom',
		'source_key'     => '',
		'options'        => [],
		'multiple'       => false,
		'empty_option'   => '',
		'query_callback' => null,
		'priority'       => 10,
		'active'         => true,
		'class'          => '',
		'attributes'     => [],
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
	 * Reset singleton instance (for testing).
	 *
	 * @return void
	 */
	public static function reset_instance(): void {
		self::$instance = null;
	}

	/**
	 * Register a filter.
	 *
	 * @since 1.0.0
	 *
	 * @param FilterInterface $filter The filter instance.
	 * @return bool True if registered successfully, false if already exists.
	 */
	public function register_filter( FilterInterface $filter ): bool {
		$name = $filter->getName();

		if ( empty( $name ) ) {
			_doing_it_wrong(
				__METHOD__,
				esc_html__( 'Filter name cannot be empty.', 'all-purpose-directory' ),
				'1.0.0'
			);
			return false;
		}

		if ( isset( $this->filters[ $name ] ) ) {
			_doing_it_wrong(
				__METHOD__,
				sprintf(
					/* translators: %s: filter name */
					esc_html__( 'Filter "%s" is already registered.', 'all-purpose-directory' ),
					esc_html( $name )
				),
				'1.0.0'
			);
			return false;
		}

		$this->filters[ $name ] = $filter;

		/**
		 * Fires after a filter is registered.
		 *
		 * @since 1.0.0
		 *
		 * @param string          $name   Filter name.
		 * @param FilterInterface $filter Filter instance.
		 */
		do_action( 'apd_filter_registered', $name, $filter );

		return true;
	}

	/**
	 * Unregister a filter.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name Filter name to unregister.
	 * @return bool True if unregistered, false if filter didn't exist.
	 */
	public function unregister_filter( string $name ): bool {
		$name = sanitize_key( $name );

		if ( ! isset( $this->filters[ $name ] ) ) {
			return false;
		}

		$filter = $this->filters[ $name ];
		unset( $this->filters[ $name ] );

		/**
		 * Fires after a filter is unregistered.
		 *
		 * @since 1.0.0
		 *
		 * @param string          $name   Filter name.
		 * @param FilterInterface $filter Filter instance that was removed.
		 */
		do_action( 'apd_filter_unregistered', $name, $filter );

		return true;
	}

	/**
	 * Get a registered filter by name.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name Filter name.
	 * @return FilterInterface|null Filter instance or null if not found.
	 */
	public function get_filter( string $name ): ?FilterInterface {
		$name = sanitize_key( $name );

		return $this->filters[ $name ] ?? null;
	}

	/**
	 * Get all registered filters.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $args Optional. Arguments to filter results.
	 *                                   - 'type': Filter by filter type.
	 *                                   - 'source': Filter by source (taxonomy, field, custom).
	 *                                   - 'active_only': Only return filters marked as active.
	 *                                   - 'orderby': Order by 'priority' or 'name'.
	 *                                   - 'order': 'ASC' or 'DESC'.
	 * @return array<string, FilterInterface> Array of filters keyed by name.
	 */
	public function get_filters( array $args = [] ): array {
		$defaults = [
			'type'        => null,
			'source'      => null,
			'active_only' => true,
			'orderby'     => 'priority',
			'order'       => 'ASC',
		];

		$args = wp_parse_args( $args, $defaults );

		$filters = $this->filters;

		// Filter by type.
		if ( $args['type'] !== null ) {
			$filters = array_filter(
				$filters,
				fn( FilterInterface $filter ) => $filter->getType() === $args['type']
			);
		}

		// Filter by source.
		if ( $args['source'] !== null ) {
			$filters = array_filter(
				$filters,
				fn( FilterInterface $filter ) => ( $filter->getConfig()['source'] ?? '' ) === $args['source']
			);
		}

		// Filter by active flag.
		if ( $args['active_only'] ) {
			$filters = array_filter(
				$filters,
				fn( FilterInterface $filter ) => ( $filter->getConfig()['active'] ?? true ) === true
			);
		}

		// Sort filters.
		$orderby = $args['orderby'];
		$order   = strtoupper( $args['order'] ) === 'DESC' ? -1 : 1;

		uasort(
			$filters,
			function ( FilterInterface $a, FilterInterface $b ) use ( $orderby, $order ) {
				if ( $orderby === 'name' ) {
					return strcmp( $a->getName(), $b->getName() ) * $order;
				}

				// Default: sort by priority.
				$a_priority = $a->getConfig()['priority'] ?? 10;
				$b_priority = $b->getConfig()['priority'] ?? 10;

				return ( $a_priority <=> $b_priority ) * $order;
			}
		);

		return $filters;
	}

	/**
	 * Get active filters from the current request.
	 *
	 * Returns filters that have a value set in the request.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed>|null $request Optional. Request data. Defaults to $_GET.
	 * @return array<string, array{filter: FilterInterface, value: mixed}> Active filters with values.
	 */
	public function get_active_filters( ?array $request = null ): array {
		if ( $request === null ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$request = $_GET;
		}

		$active = [];

		foreach ( $this->get_filters() as $name => $filter ) {
			$param = $filter->getUrlParam();

			if ( isset( $request[ $param ] ) ) {
				$value = $filter->sanitize( $request[ $param ] );

				if ( $filter->isActive( $value ) ) {
					$active[ $name ] = [
						'filter' => $filter,
						'value'  => $value,
					];
				}
			}
		}

		return $active;
	}

	/**
	 * Get filter value from request.
	 *
	 * @since 1.0.0
	 *
	 * @param string                    $name    Filter name.
	 * @param array<string, mixed>|null $request Optional. Request data. Defaults to $_GET.
	 * @return mixed The sanitized filter value or null if not set.
	 */
	public function get_filter_value( string $name, ?array $request = null ): mixed {
		$filter = $this->get_filter( $name );

		if ( $filter === null ) {
			return null;
		}

		if ( $request === null ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$request = $_GET;
		}

		$param = $filter->getUrlParam();

		if ( ! isset( $request[ $param ] ) ) {
			return null;
		}

		return $filter->sanitize( $request[ $param ] );
	}

	/**
	 * Check if a filter is registered.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name Filter name.
	 * @return bool True if registered.
	 */
	public function has_filter( string $name ): bool {
		return isset( $this->filters[ sanitize_key( $name ) ] );
	}

	/**
	 * Get the count of registered filters.
	 *
	 * @since 1.0.0
	 *
	 * @return int Number of registered filters.
	 */
	public function count(): int {
		return count( $this->filters );
	}

	/**
	 * Get the default filter configuration.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed> Default configuration.
	 */
	public function get_default_config(): array {
		return self::DEFAULT_FILTER_CONFIG;
	}

	/**
	 * Reset the registry.
	 *
	 * Clears all registered filters.
	 * Primarily used for testing.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function reset(): void {
		$this->filters = [];
	}
}
