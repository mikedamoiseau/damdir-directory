<?php
/**
 * Shortcode Manager Class.
 *
 * Manages registration and initialization of all plugin shortcodes.
 *
 * @package APD\Shortcode
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Shortcode;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ShortcodeManager
 *
 * @since 1.0.0
 */
final class ShortcodeManager {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Registered shortcodes.
	 *
	 * @var array<string, AbstractShortcode>
	 */
	private array $shortcodes = [];

	/**
	 * Whether shortcodes have been registered.
	 *
	 * @var bool
	 */
	private bool $initialized = false;

	/**
	 * Private constructor for singleton pattern.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		// Private constructor.
	}

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
	 * Initialize and register all shortcodes.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init(): void {
		if ( $this->initialized ) {
			return;
		}

		// Register core shortcodes.
		$this->register( new ListingsShortcode() );
		$this->register( new SearchFormShortcode() );
		$this->register( new CategoriesShortcode() );
		$this->register( new LoginFormShortcode() );
		$this->register( new RegisterFormShortcode() );
		$this->register( new SubmissionFormShortcode() );
		$this->register( new DashboardShortcode() );
		$this->register( new FavoritesShortcode() );

		// Internal shortcode — used only by BlockTemplateController's block templates.
		// Not exposed via get_all() or get_documentation() because it is only meaningful
		// inside the registered block templates for listing/taxonomy archives.
		add_shortcode( 'apd_archive_content', [ $this, 'render_archive_content_shortcode' ] );

		$this->initialized = true;

		/**
		 * Fires after core shortcodes are registered.
		 *
		 * Use this hook to register custom shortcodes.
		 *
		 * @since 1.0.0
		 *
		 * @param ShortcodeManager $manager The shortcode manager instance.
		 */
		do_action( 'apd_shortcodes_init', $this );
	}

	/**
	 * Register a shortcode.
	 *
	 * @since 1.0.0
	 *
	 * @param AbstractShortcode $shortcode The shortcode instance.
	 * @return bool True if registered successfully.
	 */
	public function register( AbstractShortcode $shortcode ): bool {
		$tag = $shortcode->get_tag();

		if ( empty( $tag ) ) {
			return false;
		}

		$this->shortcodes[ $tag ] = $shortcode;

		// Register with WordPress.
		add_shortcode( $tag, [ $shortcode, 'render' ] );

		/**
		 * Fires after a shortcode is registered.
		 *
		 * @since 1.0.0
		 *
		 * @param AbstractShortcode $shortcode The registered shortcode.
		 * @param string            $tag       The shortcode tag.
		 */
		do_action( 'apd_shortcode_registered', $shortcode, $tag );

		return true;
	}

	/**
	 * Unregister a shortcode.
	 *
	 * @since 1.0.0
	 *
	 * @param string $tag Shortcode tag to unregister.
	 * @return bool True if unregistered successfully.
	 */
	public function unregister( string $tag ): bool {
		if ( ! isset( $this->shortcodes[ $tag ] ) ) {
			return false;
		}

		$shortcode = $this->shortcodes[ $tag ];
		unset( $this->shortcodes[ $tag ] );

		// Remove from WordPress.
		remove_shortcode( $tag );

		/**
		 * Fires after a shortcode is unregistered.
		 *
		 * @since 1.0.0
		 *
		 * @param string            $tag       The shortcode tag.
		 * @param AbstractShortcode $shortcode The unregistered shortcode.
		 */
		do_action( 'apd_shortcode_unregistered', $tag, $shortcode );

		return true;
	}

	/**
	 * Get a registered shortcode.
	 *
	 * @since 1.0.0
	 *
	 * @param string $tag Shortcode tag.
	 * @return AbstractShortcode|null Shortcode instance or null.
	 */
	public function get( string $tag ): ?AbstractShortcode {
		return $this->shortcodes[ $tag ] ?? null;
	}

	/**
	 * Get all registered shortcodes.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, AbstractShortcode> Array of shortcodes keyed by tag.
	 */
	public function get_all(): array {
		return $this->shortcodes;
	}

	/**
	 * Check if a shortcode is registered.
	 *
	 * @since 1.0.0
	 *
	 * @param string $tag Shortcode tag.
	 * @return bool True if registered.
	 */
	public function has( string $tag ): bool {
		return isset( $this->shortcodes[ $tag ] );
	}

	/**
	 * Render the archive content shortcode.
	 *
	 * Internal shortcode used by block templates to render listing archive content.
	 * Only produces output on listing archive and taxonomy archive pages.
	 *
	 * @since 1.0.0
	 *
	 * @return string Archive content HTML, or empty string on non-archive pages.
	 */
	public function render_archive_content_shortcode(): string {
		if (
			! is_post_type_archive( 'apd_listing' )
			&& ! is_tax( 'apd_category' )
			&& ! is_tax( 'apd_tag' )
		) {
			return '';
		}

		$template_loader = new \APD\Core\TemplateLoader();

		return $template_loader->render_archive_content();
	}

	/**
	 * Get shortcode documentation for display.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array> Array of shortcode documentation.
	 */
	public function get_documentation(): array {
		$docs = [];

		foreach ( $this->shortcodes as $tag => $shortcode ) {
			$docs[ $tag ] = [
				'tag'         => $tag,
				'description' => $shortcode->get_description(),
				'attributes'  => $shortcode->get_attribute_docs(),
				'example'     => $shortcode->get_example(),
			];
		}

		return $docs;
	}
}
