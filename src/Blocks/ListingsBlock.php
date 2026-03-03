<?php
/**
 * Listings Block Class.
 *
 * Displays listings in grid or list view.
 *
 * @package APD\Blocks
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Blocks;

use APD\Frontend\Display\ViewRegistry;
use APD\Listing\ListingQueryBuilder;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ListingsBlock
 *
 * @since 1.0.0
 */
final class ListingsBlock extends AbstractBlock {

	/**
	 * Block name (without namespace).
	 *
	 * @var string
	 */
	protected string $name = 'listings';

	/**
	 * Block title.
	 *
	 * @var string
	 */
	protected string $title = 'Listings';

	/**
	 * Block description.
	 *
	 * @var string
	 */
	protected string $description = 'Display listings in grid or list view.';

	/**
	 * Block icon.
	 *
	 * @var string
	 */
	protected string $icon = 'grid-view';

	/**
	 * Block keywords.
	 *
	 * @var array<string>
	 */
	protected array $keywords = [ 'listings', 'directory', 'grid', 'list' ];

	/**
	 * Block attributes.
	 *
	 * @var array<string, array>
	 */
	protected array $attributes = [
		'view'           => [
			'type'    => 'string',
			'default' => 'grid',
		],
		'columns'        => [
			'type'    => 'number',
			'default' => 3,
		],
		'count'          => [
			'type'    => 'number',
			'default' => 12,
		],
		'category'       => [
			'type'    => 'string',
			'default' => '',
		],
		'tag'            => [
			'type'    => 'string',
			'default' => '',
		],
		'type'           => [
			'type'    => 'string',
			'default' => '',
		],
		'orderby'        => [
			'type'    => 'string',
			'default' => 'date',
		],
		'order'          => [
			'type'    => 'string',
			'default' => 'DESC',
		],
		'ids'            => [
			'type'    => 'string',
			'default' => '',
		],
		'exclude'        => [
			'type'    => 'string',
			'default' => '',
		],
		'showImage'      => [
			'type'    => 'boolean',
			'default' => true,
		],
		'showExcerpt'    => [
			'type'    => 'boolean',
			'default' => true,
		],
		'excerptLength'  => [
			'type'    => 'number',
			'default' => 15,
		],
		'showCategory'   => [
			'type'    => 'boolean',
			'default' => true,
		],
		'showPagination' => [
			'type'    => 'boolean',
			'default' => true,
		],
	];

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->title       = __( 'Listings', 'all-purpose-directory' );
		$this->description = __( 'Display listings in grid or list view.', 'all-purpose-directory' );
		$this->keywords    = [
			__( 'listings', 'all-purpose-directory' ),
			__( 'directory', 'all-purpose-directory' ),
			__( 'grid', 'all-purpose-directory' ),
			__( 'list', 'all-purpose-directory' ),
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
		// Build query arguments.
		$query_args = $this->build_query_args( $attributes );

		/**
		 * Filter the listings block query arguments.
		 *
		 * @since 1.0.0
		 *
		 * @param array $query_args The query arguments.
		 * @param array $attributes The block attributes.
		 */
		$query_args = apply_filters( 'apd_listings_block_query_args', $query_args, $attributes );

		// Run query.
		$query = new \WP_Query( $query_args );

		// Get the view.
		$view_type   = $this->validate_view( $attributes['view'] );
		$view_config = $this->build_view_config( $attributes );

		$registry = ViewRegistry::get_instance();
		$view     = $registry->create_view( $view_type, $view_config );

		if ( ! $view ) {
			return $this->render_error(
				sprintf(
					/* translators: %s: View type */
					__( 'Invalid view type: %s', 'all-purpose-directory' ),
					$attributes['view']
				)
			);
		}

		// Start output.
		ob_start();

		$wrapper_attributes = $this->get_wrapper_attributes(
			$attributes,
			[ 'apd-listings-block' ]
		);

		?>
		<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<?php
			if ( $query->have_posts() ) {
				// Render using the view.
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $view->renderListings( $query );

				// Pagination.
				if ( $attributes['showPagination'] && $query->max_num_pages > 1 ) {
					$this->render_pagination( $query, $attributes );
				}
			} else {
				$this->render_no_results( $attributes );
			}
			?>
		</div>
		<?php

		wp_reset_postdata();

		return ob_get_clean();
	}

	/**
	 * Build WP_Query arguments from block attributes.
	 *
	 * @since 1.0.0
	 *
	 * @param array $attributes Block attributes.
	 * @return array Query arguments.
	 */
	private function build_query_args( array $attributes ): array {
		$builder = new ListingQueryBuilder();

		return $builder->build(
			[
				'count'    => $attributes['count'],
				'paged'    => $this->get_paged(),
				'ids'      => $attributes['ids'],
				'exclude'  => $attributes['exclude'],
				'category' => $attributes['category'],
				'tag'      => $attributes['tag'],
				'type'     => $attributes['type'],
				'orderby'  => $attributes['orderby'],
				'order'    => $attributes['order'],
			]
		);
	}

	/**
	 * Build view configuration from block attributes.
	 *
	 * @since 1.0.0
	 *
	 * @param array $attributes Block attributes.
	 * @return array View configuration.
	 */
	private function build_view_config( array $attributes ): array {
		$config = [
			'show_image'     => (bool) $attributes['showImage'],
			'show_excerpt'   => (bool) $attributes['showExcerpt'],
			'show_category'  => (bool) $attributes['showCategory'],
			'posts_per_page' => absint( $attributes['count'] ),
		];

		// Columns for grid view.
		if ( $attributes['view'] === 'grid' ) {
			$columns = absint( $attributes['columns'] );
			if ( $columns >= 2 && $columns <= 4 ) {
				$config['columns'] = $columns;
			}
		}

		// Excerpt length.
		if ( ! empty( $attributes['excerptLength'] ) ) {
			$config['excerpt_length'] = absint( $attributes['excerptLength'] );
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
	 * @param \WP_Query $query      The query object.
	 * @param array     $attributes Block attributes.
	 * @return void
	 */
	private function render_pagination( \WP_Query $query, array $attributes ): void {
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
		 * @param array    $args       Pagination arguments.
		 * @param WP_Query $query      The query object.
		 * @param array    $attributes Block attributes.
		 */
		$args = apply_filters( 'apd_listings_block_pagination_args', $args, $query, $attributes );

		$links = paginate_links( $args );

		if ( $links ) {
			printf(
				'<nav class="apd-pagination" aria-label="%s">%s</nav>',
				esc_attr__( 'Listings pagination', 'all-purpose-directory' ),
				$links // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			);
		}
	}

	/**
	 * Render no results message.
	 *
	 * @since 1.0.0
	 *
	 * @param array $attributes Block attributes.
	 * @return void
	 */
	private function render_no_results( array $attributes ): void {
		/**
		 * Filter the no results message.
		 *
		 * @since 1.0.0
		 *
		 * @param string $message    The no results message.
		 * @param array  $attributes Block attributes.
		 */
		$message = apply_filters(
			'apd_listings_block_no_results_message',
			__( 'No listings found.', 'all-purpose-directory' ),
			$attributes
		);

		printf(
			'<div class="apd-no-results"><p>%s</p></div>',
			esc_html( $message )
		);
	}
}
