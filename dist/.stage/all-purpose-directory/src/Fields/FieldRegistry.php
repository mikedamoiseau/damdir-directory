<?php
/**
 * Field Registry.
 *
 * Central registry for managing custom listing fields. Handles field
 * registration, retrieval, and field type management.
 *
 * @package APD\Fields
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Fields;

use APD\Contracts\FieldTypeInterface;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FieldRegistry
 *
 * Singleton class for registering and managing custom fields.
 *
 * @since 1.0.0
 */
final class FieldRegistry {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Registered fields.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private array $fields = [];

	/**
	 * Registered field types.
	 *
	 * @var array<string, FieldTypeInterface>
	 */
	private array $field_types = [];

	/**
	 * Default field configuration.
	 *
	 * @var array<string, mixed>
	 */
	private const DEFAULT_FIELD_CONFIG = [
		'name'         => '',
		'type'         => 'text',
		'label'        => '',
		'description'  => '',
		'required'     => false,
		'default'      => '',
		'placeholder'  => '',
		'options'      => [],
		'validation'   => [],
		'searchable'   => false,
		'filterable'   => false,
		'admin_only'   => false,
		'priority'     => 10,
		'class'        => '',
		'attributes'   => [],
		'listing_type' => null,
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
	 * Register a field type handler.
	 *
	 * Field types define how fields are rendered, sanitized, and validated.
	 *
	 * @since 1.0.0
	 *
	 * @param FieldTypeInterface $field_type The field type handler instance.
	 * @return bool True if registered successfully, false if type already exists.
	 */
	public function register_field_type( FieldTypeInterface $field_type ): bool {
		$type = $field_type->getType();

		if ( isset( $this->field_types[ $type ] ) ) {
			return false;
		}

		$this->field_types[ $type ] = $field_type;

		/**
		 * Fires after a field type is registered.
		 *
		 * @since 1.0.0
		 *
		 * @param string             $type       The field type identifier.
		 * @param FieldTypeInterface $field_type The field type handler.
		 */
		do_action( 'apd_field_type_registered', $type, $field_type );

		return true;
	}

	/**
	 * Get a registered field type handler.
	 *
	 * @since 1.0.0
	 *
	 * @param string $type The field type identifier.
	 * @return FieldTypeInterface|null The field type handler or null if not found.
	 */
	public function get_field_type( string $type ): ?FieldTypeInterface {
		return $this->field_types[ $type ] ?? null;
	}

	/**
	 * Get all registered field types.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, FieldTypeInterface> Array of field types keyed by type identifier.
	 */
	public function get_field_types(): array {
		return $this->field_types;
	}

	/**
	 * Check if a field type is registered.
	 *
	 * @since 1.0.0
	 *
	 * @param string $type The field type identifier.
	 * @return bool True if registered.
	 */
	public function has_field_type( string $type ): bool {
		return isset( $this->field_types[ $type ] );
	}

	/**
	 * Register a field.
	 *
	 * @since 1.0.0
	 *
	 * @param string               $name   Unique field identifier.
	 * @param array<string, mixed> $config Field configuration.
	 * @return bool True if registered successfully, false on failure.
	 */
	public function register_field( string $name, array $config = [] ): bool {
		// Validate field name.
		if ( empty( $name ) ) {
			_doing_it_wrong(
				__METHOD__,
				esc_html__( 'Field name cannot be empty.', 'all-purpose-directory' ),
				'1.0.0'
			);
			return false;
		}

		// Sanitize field name.
		$name = sanitize_key( $name );

		// Check for duplicate registration.
		if ( isset( $this->fields[ $name ] ) ) {
			_doing_it_wrong(
				__METHOD__,
				sprintf(
					/* translators: %s: field name */
					esc_html__( 'Field "%s" is already registered.', 'all-purpose-directory' ),
					esc_html( $name )
				),
				'1.0.0'
			);
			return false;
		}

		// Merge with defaults.
		$config = wp_parse_args( $config, self::DEFAULT_FIELD_CONFIG );

		// Ensure name is set in config.
		$config['name'] = $name;

		// Generate label from name if not provided.
		if ( empty( $config['label'] ) ) {
			$config['label'] = ucwords( str_replace( [ '_', '-' ], ' ', $name ) );
		}

		// Ensure priority is an integer.
		$config['priority'] = absint( $config['priority'] );

		/**
		 * Filter the field configuration before registration.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, mixed> $config Field configuration.
		 * @param string               $name   Field name.
		 */
		$config = apply_filters( 'apd_register_field_config', $config, $name );

		$this->fields[ $name ] = $config;

		/**
		 * Fires after a field is registered.
		 *
		 * @since 1.0.0
		 *
		 * @param string               $name   Field name.
		 * @param array<string, mixed> $config Field configuration.
		 */
		do_action( 'apd_field_registered', $name, $config );

		return true;
	}

	/**
	 * Unregister a field.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name Field name to unregister.
	 * @return bool True if unregistered, false if field didn't exist.
	 */
	public function unregister_field( string $name ): bool {
		$name = sanitize_key( $name );

		if ( ! isset( $this->fields[ $name ] ) ) {
			return false;
		}

		$config = $this->fields[ $name ];
		unset( $this->fields[ $name ] );

		/**
		 * Fires after a field is unregistered.
		 *
		 * @since 1.0.0
		 *
		 * @param string               $name   Field name.
		 * @param array<string, mixed> $config Field configuration that was removed.
		 */
		do_action( 'apd_field_unregistered', $name, $config );

		return true;
	}

	/**
	 * Get a registered field by name.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name Field name.
	 * @return array<string, mixed>|null Field configuration or null if not found.
	 */
	public function get_field( string $name ): ?array {
		$name = sanitize_key( $name );

		if ( ! isset( $this->fields[ $name ] ) ) {
			return null;
		}

		/**
		 * Filter the field configuration when retrieved.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, mixed> $field Field configuration.
		 * @param string               $name  Field name.
		 */
		return apply_filters( 'apd_get_field', $this->fields[ $name ], $name );
	}

	/**
	 * Get all registered fields.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $args Optional. Arguments to filter fields.
	 *                                   - 'type': Filter by field type.
	 *                                   - 'searchable': Filter by searchable flag.
	 *                                   - 'filterable': Filter by filterable flag.
	 *                                   - 'admin_only': Filter by admin_only flag.
	 *                                   - 'orderby': Order by 'priority' or 'name'.
	 *                                   - 'order': 'ASC' or 'DESC'.
	 * @return array<string, array<string, mixed>> Array of field configurations keyed by name.
	 */
	public function get_fields( array $args = [] ): array {
		$defaults = [
			'type'         => null,
			'searchable'   => null,
			'filterable'   => null,
			'admin_only'   => null,
			'listing_type' => null,
			'orderby'      => 'priority',
			'order'        => 'ASC',
		];

		$args = wp_parse_args( $args, $defaults );

		$fields = $this->fields;

		// Filter by type.
		if ( $args['type'] !== null ) {
			$fields = array_filter(
				$fields,
				fn( $field ) => $field['type'] === $args['type']
			);
		}

		// Filter by searchable.
		if ( $args['searchable'] !== null ) {
			$fields = array_filter(
				$fields,
				fn( $field ) => $field['searchable'] === $args['searchable']
			);
		}

		// Filter by filterable.
		if ( $args['filterable'] !== null ) {
			$fields = array_filter(
				$fields,
				fn( $field ) => $field['filterable'] === $args['filterable']
			);
		}

		// Filter by admin_only.
		if ( $args['admin_only'] !== null ) {
			$fields = array_filter(
				$fields,
				fn( $field ) => $field['admin_only'] === $args['admin_only']
			);
		}

		// Filter by listing_type.
		if ( $args['listing_type'] !== null ) {
			$fields = array_filter(
				$fields,
				fn( $field ) => $field['listing_type'] === null
					|| $field['listing_type'] === $args['listing_type']
					|| ( is_array( $field['listing_type'] ) && in_array( $args['listing_type'], $field['listing_type'], true ) )
			);
		}

		// Sort fields.
		$orderby = $args['orderby'];
		$order   = strtoupper( $args['order'] ) === 'DESC' ? -1 : 1;

		uasort(
			$fields,
			function ( $a, $b ) use ( $orderby, $order ) {
				if ( $orderby === 'name' ) {
					return strcmp( $a['name'], $b['name'] ) * $order;
				}

				// Default: sort by priority.
				return ( $a['priority'] <=> $b['priority'] ) * $order;
			}
		);

		/**
		 * Filter the retrieved fields.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, array<string, mixed>> $fields Filtered fields.
		 * @param array<string, mixed>                $args   Query arguments.
		 */
		return apply_filters( 'apd_get_fields', $fields, $args );
	}

	/**
	 * Check if a field is registered.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name Field name.
	 * @return bool True if registered.
	 */
	public function has_field( string $name ): bool {
		return isset( $this->fields[ sanitize_key( $name ) ] );
	}

	/**
	 * Get the count of registered fields.
	 *
	 * @since 1.0.0
	 *
	 * @return int Number of registered fields.
	 */
	public function count(): int {
		return count( $this->fields );
	}

	/**
	 * Get searchable fields.
	 *
	 * Convenience method to get all fields marked as searchable.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array<string, mixed>> Array of searchable field configurations.
	 */
	public function get_searchable_fields(): array {
		return $this->get_fields( [ 'searchable' => true ] );
	}

	/**
	 * Get filterable fields.
	 *
	 * Convenience method to get all fields marked as filterable.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array<string, mixed>> Array of filterable field configurations.
	 */
	public function get_filterable_fields(): array {
		return $this->get_fields( [ 'filterable' => true ] );
	}

	/**
	 * Get frontend fields (non-admin-only).
	 *
	 * Convenience method to get fields that should be shown on the frontend.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array<string, mixed>> Array of frontend field configurations.
	 */
	public function get_frontend_fields(): array {
		return $this->get_fields( [ 'admin_only' => false ] );
	}

	/**
	 * Get admin-only fields.
	 *
	 * Convenience method to get fields that are only shown in admin.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array<string, mixed>> Array of admin-only field configurations.
	 */
	public function get_admin_fields(): array {
		return $this->get_fields( [ 'admin_only' => true ] );
	}

	/**
	 * Get the meta key for a field.
	 *
	 * @since 1.0.0
	 *
	 * @param string $field_name Field name.
	 * @return string The meta key (prefixed with _apd_).
	 */
	public function get_meta_key( string $field_name ): string {
		return '_apd_' . sanitize_key( $field_name );
	}

	/**
	 * Register default listing fields.
	 *
	 * Registers the standard set of fields that ship with the plugin.
	 * These match the meta keys used by demo data and provide a working
	 * out-of-the-box experience. Modules and themes can modify or remove
	 * these via the apd_register_default_fields filter.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_default_fields(): void {
		$fields = [
			'phone'       => [
				'type'     => 'phone',
				'label'    => __( 'Phone', 'all-purpose-directory' ),
				'priority' => 10,
			],
			'email'       => [
				'type'     => 'email',
				'label'    => __( 'Email', 'all-purpose-directory' ),
				'priority' => 20,
			],
			'website'     => [
				'type'     => 'url',
				'label'    => __( 'Website', 'all-purpose-directory' ),
				'priority' => 30,
			],
			'address'     => [
				'type'     => 'text',
				'label'    => __( 'Address', 'all-purpose-directory' ),
				'priority' => 40,
			],
			'city'        => [
				'type'     => 'text',
				'label'    => __( 'City', 'all-purpose-directory' ),
				'priority' => 50,
			],
			'state'       => [
				'type'     => 'text',
				'label'    => __( 'State', 'all-purpose-directory' ),
				'priority' => 60,
			],
			'zip'         => [
				'type'     => 'text',
				'label'    => __( 'Zip Code', 'all-purpose-directory' ),
				'priority' => 70,
			],
			'hours'       => [
				'type'     => 'textarea',
				'label'    => __( 'Business Hours', 'all-purpose-directory' ),
				'priority' => 80,
			],
			'price_range' => [
				'type'     => 'select',
				'label'    => __( 'Price Range', 'all-purpose-directory' ),
				'priority' => 90,
				'options'  => [
					''     => __( 'Not specified', 'all-purpose-directory' ),
					'$'    => '$',
					'$$'   => '$$',
					'$$$'  => '$$$',
					'$$$$' => '$$$$',
				],
			],
		];

		/**
		 * Filter the default listing fields.
		 *
		 * Return an empty array to disable all default fields.
		 * Modify individual entries to customize labels, types, or priorities.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, array<string, mixed>> $fields Default field configurations.
		 */
		$fields = apply_filters( 'apd_register_default_fields', $fields );

		if ( ! is_array( $fields ) ) {
			return;
		}

		foreach ( $fields as $name => $config ) {
			if ( ! is_string( $name ) || empty( $name ) || ! is_array( $config ) ) {
				continue;
			}

			// Skip if already registered (direct registration takes priority).
			if ( $this->has_field( $name ) ) {
				continue;
			}

			$this->register_field( $name, $config );
		}
	}

	/**
	 * Load fields registered via the apd_listing_fields filter.
	 *
	 * Fires the filter and registers any fields that were added.
	 * Should be called once during initialization after all plugins
	 * have had a chance to add their filter callbacks.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function load_external_fields(): void {
		/**
		 * Filter the registered listing fields.
		 *
		 * Use this filter to add, remove, or modify listing fields.
		 *
		 * @since 1.0.0
		 *
		 * @param array $fields Array of field definitions keyed by name.
		 */
		$external_fields = apply_filters( 'apd_listing_fields', [] );

		if ( ! is_array( $external_fields ) ) {
			return;
		}

		foreach ( $external_fields as $name => $config ) {
			if ( ! is_string( $name ) || empty( $name ) || ! is_array( $config ) ) {
				continue;
			}

			// Skip if already registered (direct registration takes priority).
			if ( $this->has_field( $name ) ) {
				continue;
			}

			$this->register_field( $name, $config );
		}
	}

	/**
	 * Reset the registry.
	 *
	 * Clears all registered fields and field types.
	 * Primarily used for testing.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function reset(): void {
		$this->fields      = [];
		$this->field_types = [];
	}

	/**
	 * Reset only fields (keep field types).
	 *
	 * Primarily used for testing.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function reset_fields(): void {
		$this->fields = [];
	}
}
