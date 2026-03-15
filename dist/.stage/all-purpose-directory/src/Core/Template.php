<?php
/**
 * Template Loader.
 *
 * Handles template loading with theme override support.
 *
 * @package APD\Core
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Core;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Template
 *
 * Provides a template system similar to WooCommerce's template loading.
 * Templates can be overridden in themes by placing them in an
 * `all-purpose-directory/` folder within the theme directory.
 *
 * @since 1.0.0
 */
final class Template {

	/**
	 * Template directory within the plugin.
	 *
	 * @var string
	 */
	private const PLUGIN_TEMPLATE_DIR = 'templates/';

	/**
	 * Template directory within themes.
	 *
	 * @var string
	 */
	private const THEME_TEMPLATE_DIR = 'all-purpose-directory/';

	/**
	 * Singleton instance.
	 *
	 * @var Template|null
	 */
	private static ?Template $instance = null;

	/**
	 * Template cache.
	 *
	 * @var array<string, string|false>
	 */
	private array $cache = [];

	/**
	 * Get singleton instance.
	 *
	 * @since 1.0.0
	 *
	 * @return Template
	 */
	public static function get_instance(): Template {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Private constructor.
	 */
	private function __construct() {}

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
	 * Locate a template file.
	 *
	 * Searches for templates in the following order:
	 * 1. Theme: `{theme}/all-purpose-directory/{template_name}`
	 * 2. Plugin: `{plugin}/templates/{template_name}`
	 *
	 * @since 1.0.0
	 *
	 * @param string $template_name Template file name (e.g., 'listing-card.php').
	 * @return string|false Full path to template file or false if not found.
	 */
	public function locate_template( string $template_name ): string|false {
		// Normalize template name.
		$template_name = ltrim( $template_name, '/' );

		// Check cache.
		$cache_key = 'locate:' . $template_name;
		if ( isset( $this->cache[ $cache_key ] ) ) {
			return $this->cache[ $cache_key ];
		}

		$template = false;

		// Check child theme first, then parent theme.
		$theme_paths = [
			get_stylesheet_directory() . '/' . self::THEME_TEMPLATE_DIR . $template_name,
			get_template_directory() . '/' . self::THEME_TEMPLATE_DIR . $template_name,
		];

		foreach ( $theme_paths as $theme_path ) {
			if ( file_exists( $theme_path ) ) {
				$template = $theme_path;
				break;
			}
		}

		// Fall back to plugin template.
		if ( ! $template ) {
			$plugin_path = APD_PLUGIN_DIR . self::PLUGIN_TEMPLATE_DIR . $template_name;
			if ( file_exists( $plugin_path ) ) {
				$template = $plugin_path;
			}
		}

		/**
		 * Filter the located template path.
		 *
		 * @since 1.0.0
		 *
		 * @param string|false $template      The template path or false if not found.
		 * @param string       $template_name The template name being located.
		 */
		$template = apply_filters( 'apd_locate_template', $template, $template_name );

		// Cache the result.
		$this->cache[ $cache_key ] = $template;

		return $template;
	}

	/**
	 * Load a template file with variables.
	 *
	 * Variables are extracted into the template's scope as individual variables.
	 * They are also available as an $args array.
	 *
	 * @since 1.0.0
	 *
	 * @param string               $template_name Template file name.
	 * @param array<string, mixed> $args          Variables to pass to the template.
	 * @param bool                 $require_once  Whether to use require_once (default: false).
	 * @return void
	 */
	public function get_template( string $template_name, array $args = [], bool $require_once = false ): void {
		$template = $this->locate_template( $template_name );

		if ( ! $template ) {
			return;
		}

		/**
		 * Fires before a template is loaded.
		 *
		 * @since 1.0.0
		 *
		 * @param string               $template_name Template name.
		 * @param string               $template      Full template path.
		 * @param array<string, mixed> $args          Template arguments.
		 */
		do_action( 'apd_before_get_template', $template_name, $template, $args );

		$this->load_template( $template, $args, $require_once );

		/**
		 * Fires after a template is loaded.
		 *
		 * @since 1.0.0
		 *
		 * @param string               $template_name Template name.
		 * @param string               $template      Full template path.
		 * @param array<string, mixed> $args          Template arguments.
		 */
		do_action( 'apd_after_get_template', $template_name, $template, $args );
	}

	/**
	 * Load and return a template as HTML.
	 *
	 * @since 1.0.0
	 *
	 * @param string               $template_name Template file name.
	 * @param array<string, mixed> $args          Variables to pass to the template.
	 * @return string The template HTML.
	 */
	public function get_template_html( string $template_name, array $args = [] ): string {
		ob_start();
		$this->get_template( $template_name, $args );
		return ob_get_clean() ?: '';
	}

	/**
	 * Load a template part.
	 *
	 * Works similarly to WordPress's get_template_part() but with theme override support.
	 * Will try to load templates in this order:
	 * 1. `{slug}-{name}.php`
	 * 2. `{slug}.php`
	 *
	 * @since 1.0.0
	 *
	 * @param string               $slug The slug name for the generic template.
	 * @param string|null          $name The name of the specialized template (optional).
	 * @param array<string, mixed> $args Variables to pass to the template.
	 * @return void
	 */
	public function get_template_part( string $slug, ?string $name = null, array $args = [] ): void {
		$templates = [];

		// Add specialized template first.
		if ( $name ) {
			$templates[] = "{$slug}-{$name}.php";
		}

		// Add generic template.
		$templates[] = "{$slug}.php";

		/**
		 * Filter the template part templates.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string>        $templates Array of templates to try.
		 * @param string               $slug      Template slug.
		 * @param string|null          $name      Template name.
		 * @param array<string, mixed> $args      Template arguments.
		 */
		$templates = apply_filters( 'apd_get_template_part', $templates, $slug, $name, $args );

		// Try each template in order.
		foreach ( $templates as $template_name ) {
			$template = $this->locate_template( $template_name );

			if ( $template ) {
				$this->load_template( $template, $args );
				return;
			}
		}
	}

	/**
	 * Load and return a template part as HTML.
	 *
	 * @since 1.0.0
	 *
	 * @param string               $slug The slug name for the generic template.
	 * @param string|null          $name The name of the specialized template (optional).
	 * @param array<string, mixed> $args Variables to pass to the template.
	 * @return string The template HTML.
	 */
	public function get_template_part_html( string $slug, ?string $name = null, array $args = [] ): string {
		ob_start();
		$this->get_template_part( $slug, $name, $args );
		return ob_get_clean() ?: '';
	}

	/**
	 * Include a template file with args.
	 *
	 * This is a protected helper to ensure variables are properly scoped.
	 *
	 * @since 1.0.0
	 *
	 * @param string               $_template     Full path to template file.
	 * @param array<string, mixed> $args          Variables to pass (also accessible as $args array).
	 * @param bool                 $_require_once Whether to use require_once.
	 * @return void
	 */
	private function load_template( string $_template, array $args, bool $_require_once = false ): void {
		// Extract args so they're available as individual variables.
		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		extract( $args, EXTR_SKIP );

		if ( $_require_once ) {
			require_once $_template;
		} else {
			require $_template;
		}
	}

	/**
	 * Get the template path for the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @return string Plugin template path.
	 */
	public function get_plugin_template_path(): string {
		return APD_PLUGIN_DIR . self::PLUGIN_TEMPLATE_DIR;
	}

	/**
	 * Get the template path for themes.
	 *
	 * @since 1.0.0
	 *
	 * @return string Theme template directory name.
	 */
	public function get_theme_template_dir(): string {
		return self::THEME_TEMPLATE_DIR;
	}

	/**
	 * Check if a template exists.
	 *
	 * @since 1.0.0
	 *
	 * @param string $template_name Template file name.
	 * @return bool True if template exists.
	 */
	public function template_exists( string $template_name ): bool {
		return $this->locate_template( $template_name ) !== false;
	}

	/**
	 * Check if a template is being overridden by the theme.
	 *
	 * @since 1.0.0
	 *
	 * @param string $template_name Template file name.
	 * @return bool True if overridden in theme.
	 */
	public function is_template_overridden( string $template_name ): bool {
		$template_name = ltrim( $template_name, '/' );

		// Check child theme and parent theme.
		$theme_paths = [
			get_stylesheet_directory() . '/' . self::THEME_TEMPLATE_DIR . $template_name,
			get_template_directory() . '/' . self::THEME_TEMPLATE_DIR . $template_name,
		];

		foreach ( $theme_paths as $theme_path ) {
			if ( file_exists( $theme_path ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Clear the template cache.
	 *
	 * Useful for testing or when templates change dynamically.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function clear_cache(): void {
		$this->cache = [];
	}
}
