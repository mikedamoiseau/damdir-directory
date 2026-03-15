<?php
/**
 * Abstract Block Class.
 *
 * Base class for all Gutenberg blocks.
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
 * Class AbstractBlock
 *
 * @since 1.0.0
 */
abstract class AbstractBlock {

	/**
	 * Block name (without namespace).
	 *
	 * @var string
	 */
	protected string $name = '';

	/**
	 * Block title.
	 *
	 * @var string
	 */
	protected string $title = '';

	/**
	 * Block description.
	 *
	 * @var string
	 */
	protected string $description = '';

	/**
	 * Block category.
	 *
	 * @var string
	 */
	protected string $category = 'all-purpose-directory';

	/**
	 * Block icon.
	 *
	 * @var string
	 */
	protected string $icon = 'admin-generic';

	/**
	 * Block keywords.
	 *
	 * @var array<string>
	 */
	protected array $keywords = [];

	/**
	 * Block attributes.
	 *
	 * @var array<string, array>
	 */
	protected array $attributes = [];

	/**
	 * Whether to use server-side rendering.
	 *
	 * @var bool
	 */
	protected bool $uses_ssr = true;

	/**
	 * Get the block name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Get the full block name with namespace.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_full_name(): string {
		return 'apd/' . $this->name;
	}

	/**
	 * Get the block title.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_title(): string {
		return $this->title;
	}

	/**
	 * Get the block description.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_description(): string {
		return $this->description;
	}

	/**
	 * Get the block attributes.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array>
	 */
	public function get_attributes(): array {
		return $this->attributes;
	}

	/**
	 * Register the block with WordPress.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register(): void {
		$args = [
			'title'       => $this->title,
			'description' => $this->description,
			'category'    => $this->category,
			'icon'        => $this->icon,
			'keywords'    => $this->keywords,
			'attributes'  => $this->attributes,
			'supports'    => $this->get_supports(),
		];

		// Add render callback for server-side rendering.
		if ( $this->uses_ssr ) {
			$args['render_callback'] = [ $this, 'render' ];
		}

		/**
		 * Filter block registration arguments.
		 *
		 * @since 1.0.0
		 *
		 * @param array         $args  Registration arguments.
		 * @param AbstractBlock $block The block instance.
		 */
		$args = apply_filters( 'apd_block_args', $args, $this );
		$args = apply_filters( "apd_block_{$this->name}_args", $args, $this );

		register_block_type( $this->get_full_name(), $args );
	}

	/**
	 * Get block supports configuration.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed>
	 */
	protected function get_supports(): array {
		return [
			'html'   => false,
			'align'  => [ 'wide', 'full' ],
			'anchor' => true,
		];
	}

	/**
	 * Render the block.
	 *
	 * @since 1.0.0
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Block content.
	 * @param \WP_Block $block      Block instance.
	 * @return string Rendered block HTML.
	 */
	public function render( array $attributes, string $content, \WP_Block $block ): string {
		// Merge with defaults.
		$attributes = $this->parse_attributes( $attributes );

		/**
		 * Fires before block output.
		 *
		 * @since 1.0.0
		 *
		 * @param array $attributes Block attributes.
		 */
		do_action( "apd_before_block_{$this->name}", $attributes );

		// Get the output.
		$output = $this->output( $attributes, $content, $block );

		/**
		 * Filter block output.
		 *
		 * @since 1.0.0
		 *
		 * @param string $output     Block HTML output.
		 * @param array  $attributes Block attributes.
		 */
		$output = apply_filters( "apd_block_{$this->name}_output", $output, $attributes );

		/**
		 * Fires after block output.
		 *
		 * @since 1.0.0
		 *
		 * @param array $attributes Block attributes.
		 */
		do_action( "apd_after_block_{$this->name}", $attributes );

		return $output;
	}

	/**
	 * Parse and merge attributes with defaults.
	 *
	 * @since 1.0.0
	 *
	 * @param array $attributes Provided attributes.
	 * @return array Parsed attributes.
	 */
	protected function parse_attributes( array $attributes ): array {
		$defaults = [];

		foreach ( $this->attributes as $key => $config ) {
			$defaults[ $key ] = $config['default'] ?? null;
		}

		return wp_parse_args( $attributes, $defaults );
	}

	/**
	 * Generate the block output.
	 *
	 * Must be implemented by child classes.
	 *
	 * @since 1.0.0
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Block content.
	 * @param \WP_Block $block      Block instance.
	 * @return string Block output HTML.
	 */
	abstract protected function output( array $attributes, string $content, \WP_Block $block ): string;

	/**
	 * Get wrapper attributes for the block.
	 *
	 * @since 1.0.0
	 *
	 * @param array $attributes Block attributes.
	 * @param array $extra_classes Additional CSS classes.
	 * @return string Wrapper attributes string.
	 */
	protected function get_wrapper_attributes( array $attributes, array $extra_classes = [] ): string {
		$classes = array_merge(
			[ 'apd-block', 'apd-block-' . $this->name ],
			$extra_classes
		);

		// Add className if provided.
		if ( ! empty( $attributes['className'] ) ) {
			$classes[] = $attributes['className'];
		}

		// Add alignment class.
		if ( ! empty( $attributes['align'] ) ) {
			$classes[] = 'align' . $attributes['align'];
		}

		$wrapper_attributes = [
			'class' => implode( ' ', array_filter( $classes ) ),
		];

		// Add anchor if provided.
		if ( ! empty( $attributes['anchor'] ) ) {
			$wrapper_attributes['id'] = $attributes['anchor'];
		}

		return get_block_wrapper_attributes( $wrapper_attributes );
	}

	/**
	 * Render an error message for the editor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $message Error message.
	 * @return string Error HTML.
	 */
	protected function render_error( string $message ): string {
		return sprintf(
			'<div class="apd-block-error components-placeholder"><p>%s</p></div>',
			esc_html( $message )
		);
	}
}
