<?php
/**
 * Module Interface.
 *
 * Interface for class-based modules that extend the core plugin.
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
 * Interface ModuleInterface
 *
 * Defines the contract for class-based modules.
 * Modules can also be registered using array configuration.
 *
 * @since 1.0.0
 */
interface ModuleInterface {

	/**
	 * Get the unique module slug.
	 *
	 * @since 1.0.0
	 *
	 * @return string The module slug (e.g., 'url-directory').
	 */
	public function get_slug(): string;

	/**
	 * Get the module name.
	 *
	 * @since 1.0.0
	 *
	 * @return string The human-readable module name.
	 */
	public function get_name(): string;

	/**
	 * Get the module description.
	 *
	 * @since 1.0.0
	 *
	 * @return string A brief description of what the module does.
	 */
	public function get_description(): string;

	/**
	 * Get the module version.
	 *
	 * @since 1.0.0
	 *
	 * @return string The module version (e.g., '1.0.0').
	 */
	public function get_version(): string;

	/**
	 * Get the module configuration.
	 *
	 * Returns the complete configuration array for this module,
	 * including optional fields like author, requires, features, etc.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed> The module configuration.
	 */
	public function get_config(): array;

	/**
	 * Initialize the module.
	 *
	 * Called after the module is registered to set up hooks,
	 * register post types, etc.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init(): void;
}
