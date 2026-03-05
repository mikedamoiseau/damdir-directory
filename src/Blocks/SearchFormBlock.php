<?php
/**
 * Search Form Block Class.
 *
 * Displays the listing search form.
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
 * Class SearchFormBlock
 *
 * @since 1.0.0
 */
final class SearchFormBlock extends AbstractBlock {

	/**
	 * Block name (without namespace).
	 *
	 * @var string
	 */
	protected string $name = 'search-form';

	/**
	 * Block title.
	 *
	 * @var string
	 */
	protected string $title = 'Listing Search Form';

	/**
	 * Block description.
	 *
	 * @var string
	 */
	protected string $description = 'Display a search form for filtering listings.';

	/**
	 * Block icon.
	 *
	 * @var string
	 */
	protected string $icon = 'search';

	/**
	 * Block keywords.
	 *
	 * @var array<string>
	 */
	protected array $keywords = [ 'search', 'filter', 'form', 'listings' ];

	/**
	 * Block attributes.
	 *
	 * @var array<string, array>
	 */
	protected array $attributes = [
		'filters'      => [
			'type'    => 'string',
			'default' => '',
		],
		'showKeyword'  => [
			'type'    => 'boolean',
			'default' => true,
		],
		'showCategory' => [
			'type'    => 'boolean',
			'default' => true,
		],
		'showTag'      => [
			'type'    => 'boolean',
			'default' => false,
		],
		'showSubmit'   => [
			'type'    => 'boolean',
			'default' => true,
		],
		'submitText'   => [
			'type'    => 'string',
			'default' => '',
		],
		'action'       => [
			'type'    => 'string',
			'default' => '',
		],
		'layout'       => [
			'type'    => 'string',
			'default' => 'horizontal',
		],
		'showActive'   => [
			'type'    => 'boolean',
			'default' => false,
		],
	];

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->title       = __( 'Listing Search Form', 'all-purpose-directory' );
		$this->description = __( 'Display a search form for filtering listings.', 'all-purpose-directory' );
		$this->keywords    = [
			__( 'search', 'all-purpose-directory' ),
			__( 'filter', 'all-purpose-directory' ),
			__( 'form', 'all-purpose-directory' ),
			__( 'listings', 'all-purpose-directory' ),
		];
	}

	/**
	 * Generate the block output.
	 *
	 * @since 1.0.0
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Block content.
	 * @param \WP_Block $block      Block instance.
	 * @return string Block output HTML.
	 */
	protected function output( array $attributes, string $content, \WP_Block $block ): string {
		// Build renderer arguments.
		$render_args = $this->build_render_args( $attributes );

		/**
		 * Filter the search form block render arguments.
		 *
		 * @since 1.0.0
		 *
		 * @param array $render_args The render arguments.
		 * @param array $attributes  The block attributes.
		 */
		$render_args = apply_filters( 'apd_search_form_block_args', $render_args, $attributes );

		// Start output.
		ob_start();

		$wrapper_attributes = $this->get_wrapper_attributes(
			$attributes,
			[ 'apd-search-form-block' ]
		);

		?>
		<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<?php
			// Render the search form.
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo \apd_render_search_form( $render_args );

			// Show active filters if requested.
			// Uses the shared singleton so $current_action_url set by render_search_form() above is available.
			if ( $attributes['showActive'] ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.Security.NonceVerification.Recommended
				echo \apd_render_active_filters( $_GET );
			}
			?>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Build render arguments from block attributes.
	 *
	 * @since 1.0.0
	 *
	 * @param array $attributes Block attributes.
	 * @return array Render arguments.
	 */
	private function build_render_args( array $attributes ): array {
		$args = [
			'layout' => $this->validate_layout( $attributes['layout'] ),
		];

		// Form action — when not specified, FilterRenderer auto-detects the current page URL
		// so the form and "Clear Filters" link stay on the same page (e.g. /directory/).
		if ( ! empty( $attributes['action'] ) ) {
			$args['action'] = esc_url( $attributes['action'] );
		}

		// Submit button.
		$args['show_submit'] = (bool) $attributes['showSubmit'];
		if ( ! empty( $attributes['submitText'] ) ) {
			$args['submit_text'] = sanitize_text_field( $attributes['submitText'] );
		}

		// Determine which filters to show.
		$args['filters'] = $this->determine_filters( $attributes );

		return $args;
	}

	/**
	 * Determine which filters to display.
	 *
	 * @since 1.0.0
	 *
	 * @param array $attributes Block attributes.
	 * @return array Filter names to display.
	 */
	private function determine_filters( array $attributes ): array {
		// If specific filters are provided, use those.
		if ( ! empty( $attributes['filters'] ) ) {
			return array_map( 'trim', explode( ',', $attributes['filters'] ) );
		}

		// Otherwise, build from show* attributes.
		$filters = [];

		if ( $attributes['showKeyword'] ) {
			$filters[] = 'keyword';
		}

		if ( $attributes['showCategory'] ) {
			$filters[] = 'category';
		}

		if ( $attributes['showTag'] ) {
			$filters[] = 'tag';
		}

		return $filters;
	}

	/**
	 * Validate layout value.
	 *
	 * @since 1.0.0
	 *
	 * @param string $layout Layout value.
	 * @return string Validated layout.
	 */
	private function validate_layout( string $layout ): string {
		$valid = [ 'horizontal', 'vertical', 'inline' ];

		if ( in_array( $layout, $valid, true ) ) {
			return $layout;
		}

		return 'horizontal';
	}
}
