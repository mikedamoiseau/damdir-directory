<?php
/**
 * View Registry Class.
 *
 * Manages listing display views (grid, list, etc.).
 *
 * @package APD\Frontend\Display
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Frontend\Display;

use APD\Contracts\ViewInterface;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ViewRegistry
 *
 * @since 1.0.0
 */
final class ViewRegistry {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Registered views.
	 *
	 * @var array<string, ViewInterface>
	 */
	private array $views = [];

	/**
	 * Default view type.
	 *
	 * @var string
	 */
	private string $default_view = 'grid';

	/**
	 * Whether core views have been registered.
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
	 * Initialize core views.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init(): void {
		if ( $this->initialized ) {
			return;
		}

		// Register core views.
		$this->register_view( new GridView() );
		$this->register_view( new ListView() );

		$this->initialized = true;

		/**
		 * Fires after core views are registered.
		 *
		 * Use this hook to register custom views.
		 *
		 * @since 1.0.0
		 *
		 * @param ViewRegistry $registry The view registry instance.
		 */
		do_action( 'apd_views_init', $this );
	}

	/**
	 * Register a view.
	 *
	 * @since 1.0.0
	 *
	 * @param ViewInterface $view The view instance.
	 * @return bool True if registered successfully.
	 */
	public function register_view( ViewInterface $view ): bool {
		$type = $view->getType();

		if ( empty( $type ) ) {
			return false;
		}

		$this->views[ $type ] = $view;

		/**
		 * Fires after a view is registered.
		 *
		 * @since 1.0.0
		 *
		 * @param ViewInterface $view The registered view.
		 */
		do_action( 'apd_view_registered', $view );

		return true;
	}

	/**
	 * Unregister a view.
	 *
	 * @since 1.0.0
	 *
	 * @param string $type View type to unregister.
	 * @return bool True if unregistered successfully.
	 */
	public function unregister_view( string $type ): bool {
		if ( ! isset( $this->views[ $type ] ) ) {
			return false;
		}

		$view = $this->views[ $type ];
		unset( $this->views[ $type ] );

		/**
		 * Fires after a view is unregistered.
		 *
		 * @since 1.0.0
		 *
		 * @param string        $type The unregistered view type.
		 * @param ViewInterface $view The unregistered view.
		 */
		do_action( 'apd_view_unregistered', $type, $view );

		return true;
	}

	/**
	 * Get a registered view.
	 *
	 * @since 1.0.0
	 *
	 * @param string $type View type.
	 * @return ViewInterface|null View instance or null.
	 */
	public function get_view( string $type ): ?ViewInterface {
		$this->init();

		return $this->views[ $type ] ?? null;
	}

	/**
	 * Get all registered views.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, ViewInterface> Array of views keyed by type.
	 */
	public function get_views(): array {
		$this->init();

		return $this->views;
	}

	/**
	 * Check if a view is registered.
	 *
	 * @since 1.0.0
	 *
	 * @param string $type View type.
	 * @return bool True if registered.
	 */
	public function has_view( string $type ): bool {
		$this->init();

		return isset( $this->views[ $type ] );
	}

	/**
	 * Get the default view type.
	 *
	 * @since 1.0.0
	 *
	 * @return string Default view type.
	 */
	public function get_default_view(): string {
		$default = \apd_get_option( 'default_view', $this->default_view );

		// Ensure default is a valid registered view.
		if ( ! $this->has_view( $default ) ) {
			$default = $this->default_view;
		}

		return $default;
	}

	/**
	 * Set the default view type.
	 *
	 * @since 1.0.0
	 *
	 * @param string $type View type.
	 * @return void
	 */
	public function set_default_view( string $type ): void {
		$this->default_view = $type;
	}

	/**
	 * Get available view types with labels.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string> Type => label mapping.
	 */
	public function get_view_options(): array {
		$this->init();

		$options = [];
		foreach ( $this->views as $type => $view ) {
			$options[ $type ] = $view->getLabel();
		}

		return $options;
	}

	/**
	 * Create a view instance from a type.
	 *
	 * Returns a fresh instance configured with the given options.
	 *
	 * @since 1.0.0
	 *
	 * @param string $type   View type.
	 * @param array  $config Configuration options.
	 * @return ViewInterface|null View instance or null.
	 */
	public function create_view( string $type, array $config = [] ): ?ViewInterface {
		$this->init();

		if ( ! $this->has_view( $type ) ) {
			return null;
		}

		// Create a new instance of the view class.
		$view_class = get_class( $this->views[ $type ] );
		return new $view_class( $config );
	}
}
