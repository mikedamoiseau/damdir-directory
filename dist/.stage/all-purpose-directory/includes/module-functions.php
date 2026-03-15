<?php
/**
 * Module Helper Functions.
 *
 * Global helper functions for the Module API.
 *
 * @package APD
 * @since   1.0.0
 */

declare(strict_types=1);

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the module registry instance.
 *
 * @since 1.0.0
 *
 * @return \APD\Module\ModuleRegistry The module registry singleton.
 */
function apd_module_registry(): \APD\Module\ModuleRegistry {
	return \APD\Module\ModuleRegistry::get_instance();
}

/**
 * Register a module using array configuration.
 *
 * Example usage:
 * ```php
 * add_action( 'apd_modules_init', function() {
 *     apd_register_module( 'url-directory', [
 *         'name'        => 'URL Directory',
 *         'description' => 'Turn your directory into a website/link directory.',
 *         'version'     => '1.0.0',
 *         'author'      => 'Your Name',
 *         'requires'    => [ 'core' => '1.0.0' ],
 *         'features'    => [ 'link_checker', 'favicon_fetcher' ],
 *         'icon'        => 'dashicons-admin-links',
 *     ] );
 * } );
 * ```
 *
 * @since 1.0.0
 *
 * @param string               $slug   Unique module identifier (e.g., 'url-directory').
 * @param array<string, mixed> $config Module configuration array.
 *                                      Required: 'name'.
 *                                      Optional: 'description', 'version', 'author', 'author_uri',
 *                                                'requires', 'features', 'icon', 'priority'.
 * @return bool True if registered successfully, false on failure.
 */
function apd_register_module( string $slug, array $config ): bool {
	return apd_module_registry()->register( $slug, $config );
}

/**
 * Register a class-based module.
 *
 * Example usage:
 * ```php
 * add_action( 'apd_modules_init', function() {
 *     apd_register_module_class( new MyModule() );
 * } );
 * ```
 *
 * @since 1.0.0
 *
 * @param \APD\Module\ModuleInterface $module The module instance implementing ModuleInterface.
 * @return bool True if registered successfully, false on failure.
 */
function apd_register_module_class( \APD\Module\ModuleInterface $module ): bool {
	return apd_module_registry()->register_module( $module );
}

/**
 * Unregister a module.
 *
 * @since 1.0.0
 *
 * @param string $slug Module slug to unregister.
 * @return bool True if unregistered, false if module didn't exist.
 */
function apd_unregister_module( string $slug ): bool {
	return apd_module_registry()->unregister( $slug );
}

/**
 * Get a registered module by slug.
 *
 * @since 1.0.0
 *
 * @param string $slug Module slug.
 * @return array<string, mixed>|null Module configuration or null if not found.
 */
function apd_get_module( string $slug ): ?array {
	return apd_module_registry()->get( $slug );
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
function apd_get_modules( array $args = [] ): array {
	return apd_module_registry()->get_all( $args );
}

/**
 * Check if a module is registered.
 *
 * @since 1.0.0
 *
 * @param string $slug Module slug.
 * @return bool True if module is registered.
 */
function apd_has_module( string $slug ): bool {
	return apd_module_registry()->has( $slug );
}

/**
 * Get the count of registered modules.
 *
 * @since 1.0.0
 *
 * @return int Number of registered modules.
 */
function apd_module_count(): int {
	return apd_module_registry()->count();
}

/**
 * Check if module requirements are met.
 *
 * @since 1.0.0
 *
 * @param array<string, string> $requires Array of requirements (e.g., ['core' => '1.0.0', 'other-module' => '2.0.0']).
 * @return array<string, string> Array of unmet requirements with error messages. Empty if all met.
 */
function apd_module_requirements_met( array $requires ): array {
	return apd_module_registry()->check_requirements( $requires );
}

/**
 * Get modules that support a specific feature.
 *
 * @since 1.0.0
 *
 * @param string $feature The feature to filter by (e.g., 'link_checker').
 * @return array<string, array<string, mixed>> Array of modules that support the feature.
 */
function apd_get_modules_by_feature( string $feature ): array {
	return apd_module_registry()->get_by_feature( $feature );
}

/**
 * Get the URL to the modules admin page.
 *
 * @since 1.0.0
 *
 * @return string The modules admin page URL.
 */
function apd_get_modules_page_url(): string {
	return \APD\Module\ModulesAdminPage::get_instance()->get_page_url();
}
