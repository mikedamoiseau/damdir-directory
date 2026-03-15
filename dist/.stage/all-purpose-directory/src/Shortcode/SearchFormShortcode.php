<?php
/**
 * Search Form Shortcode Class.
 *
 * Displays the listing search form.
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
 * Class SearchFormShortcode
 *
 * @since 1.0.0
 */
final class SearchFormShortcode extends AbstractShortcode {

	/**
	 * Shortcode tag.
	 *
	 * @var string
	 */
	protected string $tag = 'apd_search_form';

	/**
	 * Shortcode description.
	 *
	 * @var string
	 */
	protected string $description = 'Display the listing search form.';

	/**
	 * Default attributes.
	 *
	 * @var array<string, mixed>
	 */
	protected array $defaults = [
		'filters'       => '',
		'show_keyword'  => 'true',
		'show_category' => 'true',
		'show_tag'      => 'false',
		'show_submit'   => 'true',
		'submit_text'   => '',
		'action'        => '',
		'layout'        => 'horizontal',
		'show_active'   => 'false',
		'class'         => '',
	];

	/**
	 * Attribute documentation.
	 *
	 * @var array<string, array>
	 */
	protected array $attribute_docs = [
		'filters'       => [
			'type'        => 'string',
			'description' => 'Specific filter names to show (comma-separated).',
			'default'     => '',
		],
		'show_keyword'  => [
			'type'        => 'boolean',
			'description' => 'Show keyword search field.',
			'default'     => 'true',
		],
		'show_category' => [
			'type'        => 'boolean',
			'description' => 'Show category filter.',
			'default'     => 'true',
		],
		'show_tag'      => [
			'type'        => 'boolean',
			'description' => 'Show tag filter.',
			'default'     => 'false',
		],
		'show_submit'   => [
			'type'        => 'boolean',
			'description' => 'Show submit button.',
			'default'     => 'true',
		],
		'submit_text'   => [
			'type'        => 'string',
			'description' => 'Submit button text.',
			'default'     => 'Search',
		],
		'action'        => [
			'type'        => 'string',
			'description' => 'Form action URL (defaults to listings archive).',
			'default'     => '',
		],
		'layout'        => [
			'type'        => 'slug',
			'description' => 'Form layout: horizontal, vertical, or inline.',
			'default'     => 'horizontal',
		],
		'show_active'   => [
			'type'        => 'boolean',
			'description' => 'Show active filters below form.',
			'default'     => 'false',
		],
		'class'         => [
			'type'        => 'string',
			'description' => 'Additional CSS classes.',
			'default'     => '',
		],
	];

	/**
	 * Get example usage.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_example(): string {
		return '[apd_search_form show_keyword="true" show_category="true" layout="horizontal"]';
	}

	/**
	 * Generate the shortcode output.
	 *
	 * @since 1.0.0
	 *
	 * @param array       $atts    Parsed shortcode attributes.
	 * @param string|null $content Shortcode content.
	 * @return string Shortcode output.
	 */
	protected function output( array $atts, ?string $content ): string {
		// Build renderer arguments.
		$render_args = $this->build_render_args( $atts );

		/**
		 * Filter the search form render arguments.
		 *
		 * @since 1.0.0
		 *
		 * @param array $render_args The render arguments.
		 * @param array $atts        The shortcode attributes.
		 */
		$render_args = apply_filters( 'apd_search_form_shortcode_args', $render_args, $atts );

		// Start output buffering.
		ob_start();

		// Container classes.
		$container_classes = [ 'apd-search-form-shortcode' ];
		if ( ! empty( $atts['class'] ) ) {
			$container_classes[] = $atts['class'];
		}

		?>
		<div class="<?php echo esc_attr( implode( ' ', $container_classes ) ); ?>">
			<?php
			/**
			 * Fires before search form shortcode output.
			 *
			 * @since 1.0.0
			 *
			 * @param array $atts The shortcode attributes.
			 */
			do_action( 'apd_before_search_form_shortcode', $atts );

			// Render the search form.
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo \apd_render_search_form( $render_args );

			// Show active filters if requested.
			// Uses the shared singleton so $current_action_url set by render_search_form() above is available.
			if ( $atts['show_active'] ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.Security.NonceVerification.Recommended
				echo \apd_render_active_filters( $_GET );
			}

			/**
			 * Fires after search form shortcode output.
			 *
			 * @since 1.0.0
			 *
			 * @param array $atts The shortcode attributes.
			 */
			do_action( 'apd_after_search_form_shortcode', $atts );
			?>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Build render arguments from shortcode attributes.
	 *
	 * @since 1.0.0
	 *
	 * @param array $atts Shortcode attributes.
	 * @return array Render arguments.
	 */
	private function build_render_args( array $atts ): array {
		$args = [
			'layout' => $this->validate_layout( $atts['layout'] ),
		];

		// Form action — when not specified, FilterRenderer auto-detects the current page URL
		// so the form and "Clear Filters" link stay on the same page (e.g. /directory/).
		if ( ! empty( $atts['action'] ) ) {
			$args['action'] = esc_url( $atts['action'] );
		}

		// Submit button.
		$args['show_submit'] = $atts['show_submit'];
		if ( ! empty( $atts['submit_text'] ) ) {
			$args['submit_text'] = $atts['submit_text'];
		}

		// Determine which filters to show.
		$args['filters'] = $this->determine_filters( $atts );

		return $args;
	}

	/**
	 * Determine which filters to display.
	 *
	 * @since 1.0.0
	 *
	 * @param array $atts Shortcode attributes.
	 * @return array Filter names to display.
	 */
	private function determine_filters( array $atts ): array {
		// If specific filters are provided, use those.
		if ( ! empty( $atts['filters'] ) ) {
			return array_map( 'trim', explode( ',', $atts['filters'] ) );
		}

		// Otherwise, build from show_* attributes.
		$filters = [];

		if ( $atts['show_keyword'] ) {
			$filters[] = 'keyword';
		}

		if ( $atts['show_category'] ) {
			$filters[] = 'category';
		}

		if ( $atts['show_tag'] ) {
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
