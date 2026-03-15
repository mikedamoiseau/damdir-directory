<?php
/**
 * Block Manager Class.
 *
 * Manages registration and initialization of all Gutenberg blocks.
 *
 * @package APD\Blocks
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Blocks;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BlockManager
 *
 * @since 1.0.0
 */
final class BlockManager {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Registered blocks.
	 *
	 * @var array<string, AbstractBlock>
	 */
	private array $blocks = [];

	/**
	 * Whether blocks have been registered.
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
	 * Initialize and register all blocks.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init(): void {
		if ( $this->initialized ) {
			return;
		}

		// Register custom block category.
		add_filter( 'block_categories_all', [ $this, 'register_block_category' ] );

		// Register blocks on init (priority 20 to ensure post types are registered).
		add_action( 'init', [ $this, 'register_blocks' ], 20 );

		// Enqueue block editor assets.
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ] );

		// Invalidate term cache when terms are modified.
		add_action( 'created_term', [ $this, 'invalidate_term_cache' ], 10, 3 );
		add_action( 'edited_term', [ $this, 'invalidate_term_cache_on_edit' ], 10, 3 );
		add_action( 'delete_term', [ $this, 'invalidate_term_cache' ], 10, 3 );

		$this->initialized = true;
	}

	/**
	 * Register custom block category for the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, array<string, mixed>> $categories Existing block categories.
	 * @return array<int, array<string, mixed>> Modified block categories.
	 */
	public function register_block_category( array $categories ): array {
		array_unshift(
			$categories,
			[
				'slug'  => 'all-purpose-directory',
				'title' => __( 'All Purpose Directory', 'all-purpose-directory' ),
				'icon'  => 'dashicons-location-alt',
			]
		);

		return $categories;
	}

	/**
	 * Register all plugin blocks.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_blocks(): void {
		// Register core blocks.
		$this->register( new ListingsBlock() );
		$this->register( new SearchFormBlock() );
		$this->register( new CategoriesBlock() );

		/**
		 * Fires after core blocks are registered.
		 *
		 * Use this hook to register custom blocks.
		 *
		 * @since 1.0.0
		 *
		 * @param BlockManager $manager The block manager instance.
		 */
		do_action( 'apd_blocks_init', $this );
	}

	/**
	 * Register a block.
	 *
	 * @since 1.0.0
	 *
	 * @param AbstractBlock $block The block instance.
	 * @return bool True if registered successfully.
	 */
	public function register( AbstractBlock $block ): bool {
		$name = $block->get_name();

		if ( empty( $name ) ) {
			return false;
		}

		$this->blocks[ $name ] = $block;

		// Register the block with WordPress.
		$block->register();

		/**
		 * Fires after a block is registered.
		 *
		 * @since 1.0.0
		 *
		 * @param AbstractBlock $block The registered block.
		 * @param string        $name  The block name.
		 */
		do_action( 'apd_block_registered', $block, $name );

		return true;
	}

	/**
	 * Unregister a block.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name Block name to unregister.
	 * @return bool True if unregistered successfully.
	 */
	public function unregister( string $name ): bool {
		if ( ! isset( $this->blocks[ $name ] ) ) {
			return false;
		}

		$block = $this->blocks[ $name ];
		unset( $this->blocks[ $name ] );

		// Unregister from WordPress.
		unregister_block_type( 'apd/' . $name );

		/**
		 * Fires after a block is unregistered.
		 *
		 * @since 1.0.0
		 *
		 * @param string        $name  The block name.
		 * @param AbstractBlock $block The unregistered block.
		 */
		do_action( 'apd_block_unregistered', $name, $block );

		return true;
	}

	/**
	 * Get a registered block.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name Block name.
	 * @return AbstractBlock|null Block instance or null.
	 */
	public function get( string $name ): ?AbstractBlock {
		return $this->blocks[ $name ] ?? null;
	}

	/**
	 * Get all registered blocks.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, AbstractBlock> Array of blocks keyed by name.
	 */
	public function get_all(): array {
		return $this->blocks;
	}

	/**
	 * Check if a block is registered.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name Block name.
	 * @return bool True if registered.
	 */
	public function has( string $name ): bool {
		return isset( $this->blocks[ $name ] );
	}

	/**
	 * Enqueue block editor assets.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function enqueue_editor_assets(): void {
		$asset_file = APD_PLUGIN_DIR . 'assets/js/blocks/index.asset.php';
		$version    = APD_VERSION;
		$deps       = [ 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-i18n', 'wp-server-side-render', 'wp-data' ];

		// Use asset file if available (from build process).
		if ( file_exists( $asset_file ) ) {
			$asset   = require $asset_file;
			$version = $asset['version'] ?? APD_VERSION;
			$deps    = $asset['dependencies'] ?? $deps;
		}

		// Enqueue the blocks script.
		wp_enqueue_script(
			'apd-blocks-editor',
			APD_PLUGIN_URL . 'assets/js/blocks/index.js',
			$deps,
			$version,
			true
		);

		// Localize script with block data.
		wp_localize_script(
			'apd-blocks-editor',
			'apdBlocks',
			$this->get_editor_script_data()
		);

		// Set script translations.
		wp_set_script_translations( 'apd-blocks-editor', 'all-purpose-directory' );

		// Enqueue editor styles.
		wp_enqueue_style(
			'apd-blocks-editor',
			APD_PLUGIN_URL . 'assets/css/blocks-editor.css',
			[ 'wp-edit-blocks' ],
			APD_VERSION
		);
	}

	/**
	 * Get data to localize for editor scripts.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed>
	 */
	private function get_editor_script_data(): array {
		// Get cached term options or build them.
		$category_options = $this->get_cached_term_options( 'apd_category' );
		$tag_options      = $this->get_cached_term_options( 'apd_tag' );

		$data = [
			'categories'     => $category_options,
			'tags'           => $tag_options,
			'archiveUrl'     => get_post_type_archive_link( 'apd_listing' ) ?: '',
			'viewOptions'    => [
				[
					'value' => 'grid',
					'label' => __( 'Grid', 'all-purpose-directory' ),
				],
				[
					'value' => 'list',
					'label' => __( 'List', 'all-purpose-directory' ),
				],
			],
			'layoutOptions'  => [
				[
					'value' => 'horizontal',
					'label' => __( 'Horizontal', 'all-purpose-directory' ),
				],
				[
					'value' => 'vertical',
					'label' => __( 'Vertical', 'all-purpose-directory' ),
				],
				[
					'value' => 'inline',
					'label' => __( 'Inline', 'all-purpose-directory' ),
				],
			],
			'orderbyOptions' => [
				[
					'value' => 'date',
					'label' => __( 'Date', 'all-purpose-directory' ),
				],
				[
					'value' => 'title',
					'label' => __( 'Title', 'all-purpose-directory' ),
				],
				[
					'value' => 'modified',
					'label' => __( 'Modified', 'all-purpose-directory' ),
				],
				[
					'value' => 'rand',
					'label' => __( 'Random', 'all-purpose-directory' ),
				],
				[
					'value' => 'views',
					'label' => __( 'Views', 'all-purpose-directory' ),
				],
			],
			'orderOptions'   => [
				[
					'value' => 'DESC',
					'label' => __( 'Descending', 'all-purpose-directory' ),
				],
				[
					'value' => 'ASC',
					'label' => __( 'Ascending', 'all-purpose-directory' ),
				],
			],
		];

		/**
		 * Filter block editor script data.
		 *
		 * @since 1.0.0
		 *
		 * @param array $data Localization data.
		 */
		return apply_filters( 'apd_blocks_editor_data', $data );
	}

	/**
	 * Get cached term options for a taxonomy.
	 *
	 * Uses object cache to avoid repeated database queries for sites with many terms.
	 *
	 * @since 1.0.0
	 *
	 * @param string $taxonomy The taxonomy name.
	 * @return array<int, array{value: string, label: string}> Term options.
	 */
	private function get_cached_term_options( string $taxonomy ): array {
		$cache_key   = 'apd_block_terms_' . $taxonomy;
		$cache_group = 'apd_blocks';

		$options = wp_cache_get( $cache_key, $cache_group );

		if ( false !== $options ) {
			return $options;
		}

		$terms = get_terms(
			[
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			]
		);

		$options = [];
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$options[] = [
					'value' => $term->slug,
					'label' => $term->name,
				];
			}
		}

		// Cache for 1 hour (will be invalidated on term changes).
		wp_cache_set( $cache_key, $options, $cache_group, HOUR_IN_SECONDS );

		return $options;
	}

	/**
	 * Invalidate the term options cache for a taxonomy.
	 *
	 * Called when terms are created, edited, or deleted.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $term_id  Term ID.
	 * @param int    $tt_id    Term taxonomy ID.
	 * @param string $taxonomy Taxonomy slug.
	 * @return void
	 */
	public function invalidate_term_cache( int $term_id, int $tt_id, string $taxonomy ): void {
		if ( in_array( $taxonomy, [ 'apd_category', 'apd_tag' ], true ) ) {
			wp_cache_delete( 'apd_block_terms_' . $taxonomy, 'apd_blocks' );
		}
	}

	/**
	 * Invalidate term cache on term edit.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $term_id  Term ID.
	 * @param int    $tt_id    Term taxonomy ID.
	 * @param string $taxonomy Taxonomy slug.
	 * @return void
	 */
	public function invalidate_term_cache_on_edit( int $term_id, int $tt_id, string $taxonomy ): void {
		$this->invalidate_term_cache( $term_id, $tt_id, $taxonomy );
	}
}
