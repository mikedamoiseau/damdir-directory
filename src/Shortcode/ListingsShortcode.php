<?php
/**
 * Listings Shortcode Class.
 *
 * Displays listings with various view options.
 *
 * @package APD\Shortcode
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Shortcode;

use APD\Frontend\Display\ViewRegistry;
use APD\Listing\ListingQueryBuilder;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ListingsShortcode
 *
 * @since 1.0.0
 */
final class ListingsShortcode extends AbstractShortcode {

	/**
	 * Shortcode tag.
	 *
	 * @var string
	 */
	protected string $tag = 'apd_listings';

	/**
	 * Shortcode description.
	 *
	 * @var string
	 */
	protected string $description = 'Display listings in grid or list view.';

	/**
	 * Default attributes.
	 *
	 * @var array<string, mixed>
	 */
	protected array $defaults = [
		'view'            => 'grid',
		'columns'         => 3,
		'count'           => 12,
		'category'        => '',
		'tag'             => '',
		'type'            => '',
		'orderby'         => 'date',
		'order'           => 'DESC',
		'ids'             => '',
		'exclude'         => '',
		'author'          => '',
		'show_image'      => 'true',
		'show_excerpt'    => 'true',
		'excerpt_length'  => '',
		'show_category'   => 'true',
		'show_pagination' => 'true',
		'class'           => '',
	];

	/**
	 * Constructor.
	 *
	 * Overrides hardcoded defaults with admin settings so that the shortcode
	 * respects Display/Listings settings out of the box.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->defaults['view']          = \apd_get_default_view();
		$this->defaults['columns']       = \apd_get_default_grid_columns();
		$this->defaults['count']         = \apd_get_listings_per_page();
		$this->defaults['show_image']    = \apd_get_option( 'show_thumbnail', true ) ? 'true' : 'false';
		$this->defaults['show_excerpt']  = \apd_get_option( 'show_excerpt', true ) ? 'true' : 'false';
		$this->defaults['show_category'] = \apd_get_option( 'show_category', true ) ? 'true' : 'false';
	}

	/**
	 * Attribute documentation.
	 *
	 * @var array<string, array>
	 */
	protected array $attribute_docs = [
		'view'            => [
			'type'        => 'slug',
			'description' => 'Display view: grid or list.',
			'default'     => 'grid',
		],
		'columns'         => [
			'type'        => 'integer',
			'description' => 'Number of columns for grid view (2-4).',
			'default'     => 3,
		],
		'count'           => [
			'type'        => 'integer',
			'description' => 'Number of listings to display.',
			'default'     => 12,
		],
		'category'        => [
			'type'        => 'string',
			'description' => 'Category slug(s) to filter by (comma-separated).',
			'default'     => '',
		],
		'tag'             => [
			'type'        => 'string',
			'description' => 'Tag slug(s) to filter by (comma-separated).',
			'default'     => '',
		],
		'type'            => [
			'type'        => 'string',
			'description' => 'Listing type slug(s) to filter by (comma-separated).',
			'default'     => '',
		],
		'orderby'         => [
			'type'        => 'slug',
			'description' => 'Order by: date, title, modified, rand, views.',
			'default'     => 'date',
		],
		'order'           => [
			'type'        => 'slug',
			'description' => 'Sort order: ASC or DESC.',
			'default'     => 'DESC',
		],
		'ids'             => [
			'type'        => 'ids',
			'description' => 'Specific listing IDs to display.',
			'default'     => '',
		],
		'exclude'         => [
			'type'        => 'ids',
			'description' => 'Listing IDs to exclude.',
			'default'     => '',
		],
		'author'          => [
			'type'        => 'string',
			'description' => 'Author ID or username.',
			'default'     => '',
		],
		'show_image'      => [
			'type'        => 'boolean',
			'description' => 'Show featured images.',
			'default'     => 'true',
		],
		'show_excerpt'    => [
			'type'        => 'boolean',
			'description' => 'Show listing excerpts.',
			'default'     => 'true',
		],
		'excerpt_length'  => [
			'type'        => 'integer',
			'description' => 'Excerpt length in words.',
			'default'     => '',
		],
		'show_category'   => [
			'type'        => 'boolean',
			'description' => 'Show category badges.',
			'default'     => 'true',
		],
		'show_pagination' => [
			'type'        => 'boolean',
			'description' => 'Show pagination links.',
			'default'     => 'true',
		],
		'class'           => [
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
		return '[apd_listings view="grid" columns="3" count="12" category="restaurants"]';
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
		// Build query arguments.
		$query_args = $this->build_query_args( $atts );

		/**
		 * Filter the listings query arguments.
		 *
		 * @since 1.0.0
		 *
		 * @param array $query_args The query arguments.
		 * @param array $atts       The shortcode attributes.
		 */
		$query_args = apply_filters( 'apd_listings_shortcode_query_args', $query_args, $atts );

		// Run query.
		$query = new \WP_Query( $query_args );

		// Get the view.
		$view_type   = $this->validate_view( $atts['view'] );
		$view_config = $this->build_view_config( $atts );

		$registry = ViewRegistry::get_instance();
		$view     = $registry->create_view( $view_type, $view_config );

		if ( ! $view ) {
			return $this->error(
				sprintf(
				/* translators: %s: View type */
					__( 'Invalid view type: %s', 'all-purpose-directory' ),
					$atts['view']
				)
			);
		}

		// Start output buffering.
		ob_start();

		// Container classes.
		$container_classes = [ 'apd-listings-shortcode' ];
		if ( ! empty( $atts['class'] ) ) {
			$container_classes[] = $atts['class'];
		}

		?>
		<div class="<?php echo esc_attr( implode( ' ', $container_classes ) ); ?>">
			<?php
			/**
			 * Fires before listings shortcode output.
			 *
			 * @since 1.0.0
			 *
			 * @param array    $atts  The shortcode attributes.
			 * @param WP_Query $query The query object.
			 */
			do_action( 'apd_before_listings_shortcode', $atts, $query );

			if ( $query->have_posts() ) {
				// Render using the view.
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML escaped in view templates via esc_html/esc_attr/esc_url.
				echo $view->renderListings( $query );

				// Pagination.
				if ( $atts['show_pagination'] && $query->max_num_pages > 1 ) {
					$this->render_pagination( $query, $atts );
				}
			} else {
				$this->render_no_results( $atts );
			}

			/**
			 * Fires after listings shortcode output.
			 *
			 * @since 1.0.0
			 *
			 * @param array    $atts  The shortcode attributes.
			 * @param WP_Query $query The query object.
			 */
			do_action( 'apd_after_listings_shortcode', $atts, $query );
			?>
		</div>
		<?php

		wp_reset_postdata();

		return ob_get_clean();
	}

	/**
	 * Build WP_Query arguments from shortcode attributes.
	 *
	 * @since 1.0.0
	 *
	 * @param array $atts Shortcode attributes.
	 * @return array Query arguments.
	 */
	private function build_query_args( array $atts ): array {
		$builder = new ListingQueryBuilder();

		return $builder->build(
			[
				'count'    => $atts['count'],
				'paged'    => $this->get_paged(),
				'ids'      => $atts['ids'],
				'exclude'  => $atts['exclude'],
				'category' => $atts['category'],
				'tag'      => $atts['tag'],
				'type'     => $atts['type'],
				'orderby'  => $atts['orderby'],
				'order'    => $atts['order'],
				'author'   => $atts['author'],
			]
		);
	}

	/**
	 * Build view configuration from shortcode attributes.
	 *
	 * @since 1.0.0
	 *
	 * @param array $atts Shortcode attributes.
	 * @return array View configuration.
	 */
	private function build_view_config( array $atts ): array {
		$config = [
			'show_image'     => $atts['show_image'],
			'show_excerpt'   => $atts['show_excerpt'],
			'show_category'  => $atts['show_category'],
			'posts_per_page' => absint( $atts['count'] ),
		];

		// Columns for grid view.
		if ( $atts['view'] === 'grid' ) {
			$columns = absint( $atts['columns'] );
			if ( $columns >= 2 && $columns <= 4 ) {
				$config['columns'] = $columns;
			}
		}

		// Excerpt length.
		if ( ! empty( $atts['excerpt_length'] ) ) {
			$config['excerpt_length'] = absint( $atts['excerpt_length'] );
		}

		return $config;
	}

	/**
	 * Validate view type.
	 *
	 * @since 1.0.0
	 *
	 * @param string $view View type.
	 * @return string Validated view type.
	 */
	private function validate_view( string $view ): string {
		$registry = ViewRegistry::get_instance();

		if ( $registry->has_view( $view ) ) {
			return $view;
		}

		return $registry->get_default_view();
	}

	/**
	 * Get current page number.
	 *
	 * @since 1.0.0
	 *
	 * @return int Page number.
	 */
	private function get_paged(): int {
		if ( get_query_var( 'paged' ) ) {
			return absint( get_query_var( 'paged' ) );
		}

		if ( get_query_var( 'page' ) ) {
			return absint( get_query_var( 'page' ) );
		}

		return 1;
	}

	/**
	 * Render pagination.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_Query $query The query object.
	 * @param array     $atts  Shortcode attributes.
	 * @return void
	 */
	private function render_pagination( \WP_Query $query, array $atts ): void {
		$args = [
			'total'     => $query->max_num_pages,
			'current'   => $this->get_paged(),
			'mid_size'  => 2,
			'prev_text' => '&laquo; ' . __( 'Previous', 'all-purpose-directory' ),
			'next_text' => __( 'Next', 'all-purpose-directory' ) . ' &raquo;',
		];

		/**
		 * Filter pagination arguments.
		 *
		 * @since 1.0.0
		 *
		 * @param array     $args  Pagination arguments.
		 * @param WP_Query  $query The query object.
		 * @param array     $atts  Shortcode attributes.
		 */
		$args = apply_filters( 'apd_listings_shortcode_pagination_args', $args, $query, $atts );

		$links = paginate_links( $args );

		if ( $links ) {
			printf(
				'<nav class="apd-pagination" aria-label="%s">%s</nav>',
				esc_attr__( 'Listings pagination', 'all-purpose-directory' ),
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				$links
			);
		}
	}

	/**
	 * Render no results message.
	 *
	 * @since 1.0.0
	 *
	 * @param array $atts Shortcode attributes.
	 * @return void
	 */
	private function render_no_results( array $atts ): void {
		/**
		 * Filter the no results message.
		 *
		 * @since 1.0.0
		 *
		 * @param string $message The no results message.
		 * @param array  $atts    Shortcode attributes.
		 */
		$message = apply_filters(
			'apd_listings_shortcode_no_results_message',
			__( 'No listings found.', 'all-purpose-directory' ),
			$atts
		);

		printf(
			'<div class="apd-no-results"><p>%s</p></div>',
			esc_html( $message )
		);
	}

	/**
	 * Sanitize shortcode attributes.
	 *
	 * @since 1.0.0
	 *
	 * @param array $atts Attributes to sanitize.
	 * @return array Sanitized attributes.
	 */
	protected function sanitize_attributes( array $atts ): array {
		$sanitized = parent::sanitize_attributes( $atts );

		// Ensure count is reasonable.
		$sanitized['count'] = max( 1, min( 100, $sanitized['count'] ) );

		// Validate columns.
		$sanitized['columns'] = max( 2, min( 4, $sanitized['columns'] ) );

		return $sanitized;
	}
}
