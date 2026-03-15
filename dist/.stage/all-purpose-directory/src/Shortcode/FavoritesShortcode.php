<?php
/**
 * Favorites Shortcode Class.
 *
 * Displays the user's favorite listings.
 *
 * @package APD\Shortcode
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Shortcode;

use APD\Frontend\Display\ViewRegistry;
use APD\Listing\PostType;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FavoritesShortcode
 *
 * @since 1.0.0
 */
final class FavoritesShortcode extends AbstractShortcode {

	/**
	 * Shortcode tag.
	 *
	 * @var string
	 */
	protected string $tag = 'apd_favorites';

	/**
	 * Shortcode description.
	 *
	 * @var string
	 */
	protected string $description = 'Display the user\'s favorite listings.';

	/**
	 * Default attributes.
	 *
	 * @var array<string, mixed>
	 */
	protected array $defaults = [
		'view'          => 'grid',
		'columns'       => 3,
		'count'         => 12,
		'show_empty'    => 'true',
		'empty_message' => '',
		'class'         => '',
	];

	/**
	 * Attribute documentation.
	 *
	 * @var array<string, array>
	 */
	protected array $attribute_docs = [
		'view'          => [
			'type'        => 'slug',
			'description' => 'Display view: grid or list.',
			'default'     => 'grid',
		],
		'columns'       => [
			'type'        => 'integer',
			'description' => 'Number of columns for grid view (2-4).',
			'default'     => 3,
		],
		'count'         => [
			'type'        => 'integer',
			'description' => 'Number of favorites to show per page.',
			'default'     => 12,
		],
		'show_empty'    => [
			'type'        => 'boolean',
			'description' => 'Show message when no favorites.',
			'default'     => 'true',
		],
		'empty_message' => [
			'type'        => 'string',
			'description' => 'Message when no favorites found.',
			'default'     => '',
		],
		'class'         => [
			'type'        => 'string',
			'description' => 'Additional CSS classes.',
			'default'     => '',
		],
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
		$this->defaults['view']    = \apd_get_default_view();
		$this->defaults['columns'] = \apd_get_default_grid_columns();
		$this->defaults['count']   = \apd_get_listings_per_page();
	}

	/**
	 * Get example usage.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_example(): string {
		return '[apd_favorites view="grid" columns="3"]';
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
		// Require login.
		if ( ! is_user_logged_in() ) {
			return $this->require_login( __( 'Please log in to view your favorites.', 'all-purpose-directory' ) );
		}

		/**
		 * Filter to enable custom favorites display override.
		 *
		 * Return true to replace the default rendering with a custom implementation
		 * via the apd_favorites_output filter.
		 *
		 * @since 1.0.0
		 *
		 * @param bool  $enabled Whether custom favorites output is enabled.
		 * @param array $atts    The shortcode attributes.
		 */
		$enabled = apply_filters( 'apd_favorites_enabled', false, $atts );

		if ( $enabled ) {
			/**
			 * Filter the custom favorites output.
			 *
			 * Only called when apd_favorites_enabled returns true.
			 *
			 * @since 1.0.0
			 *
			 * @param string $output The favorites output.
			 * @param array  $atts   The shortcode attributes.
			 */
			return apply_filters( 'apd_favorites_output', '', $atts );
		}

		// Get user's favorite listing IDs.
		$favorite_ids = \apd_get_user_favorites();

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

		// Build and run query.
		$query_args = $this->build_query_args( $atts, $favorite_ids );

		/**
		 * Filter the favorites shortcode query arguments.
		 *
		 * @since 1.0.0
		 *
		 * @param array $query_args The WP_Query arguments.
		 * @param array $atts       The shortcode attributes.
		 */
		$query_args = apply_filters( 'apd_favorites_shortcode_query_args', $query_args, $atts );

		$query = new \WP_Query( $query_args );

		// Start output buffering.
		ob_start();

		// Container classes.
		$container_classes = [ 'apd-favorites-shortcode' ];
		if ( ! empty( $atts['class'] ) ) {
			$container_classes[] = $atts['class'];
		}

		?>
		<div class="<?php echo esc_attr( implode( ' ', $container_classes ) ); ?>">
			<?php
			/**
			 * Fires before favorites shortcode output.
			 *
			 * @since 1.0.0
			 *
			 * @param array     $atts  The shortcode attributes.
			 * @param \WP_Query $query The query object.
			 */
			do_action( 'apd_before_favorites_shortcode', $atts, $query );

			if ( $query->have_posts() ) {
				// Render using the view.
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML escaped in view templates via esc_html/esc_attr/esc_url.
				echo $view->renderListings( $query );

				// Pagination.
				if ( $query->max_num_pages > 1 ) {
					$this->render_pagination( $query, $atts );
				}
			} else {
				$this->render_no_results( $atts );
			}

			/**
			 * Fires after favorites shortcode output.
			 *
			 * @since 1.0.0
			 *
			 * @param array     $atts  The shortcode attributes.
			 * @param \WP_Query $query The query object.
			 */
			do_action( 'apd_after_favorites_shortcode', $atts, $query );
			?>
		</div>
		<?php

		wp_reset_postdata();

		return ob_get_clean();
	}

	/**
	 * Build WP_Query arguments from shortcode attributes and favorite IDs.
	 *
	 * @since 1.0.0
	 *
	 * @param array $atts         Shortcode attributes.
	 * @param int[] $favorite_ids Array of favorite listing IDs.
	 * @return array Query arguments.
	 */
	private function build_query_args( array $atts, array $favorite_ids ): array {
		// Force empty result when no favorites.
		if ( empty( $favorite_ids ) ) {
			return [
				'post_type'      => PostType::POST_TYPE,
				'post__in'       => [ 0 ],
				'posts_per_page' => 1,
			];
		}

		return [
			'post_type'      => PostType::POST_TYPE,
			'post_status'    => 'publish',
			'post__in'       => $favorite_ids,
			'orderby'        => 'post__in',
			'posts_per_page' => absint( $atts['count'] ),
			'paged'          => $this->get_paged(),
		];
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
			'show_favorite'  => true,
			'show_image'     => \apd_get_option( 'show_thumbnail', true ),
			'show_excerpt'   => \apd_get_option( 'show_excerpt', true ),
			'show_category'  => \apd_get_option( 'show_category', true ),
			'posts_per_page' => absint( $atts['count'] ),
		];

		// Columns for grid view.
		if ( $atts['view'] === 'grid' ) {
			$columns = absint( $atts['columns'] );
			if ( $columns >= 2 && $columns <= 4 ) {
				$config['columns'] = $columns;
			}
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
	 * Get current page number from URL.
	 *
	 * Uses a custom 'fav_page' parameter to avoid conflicts with
	 * [apd_listings] pagination on the same page.
	 *
	 * @since 1.0.0
	 *
	 * @return int Page number.
	 */
	private function get_paged(): int {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Just reading page parameter for display.
		return isset( $_GET['fav_page'] ) ? max( 1, absint( $_GET['fav_page'] ) ) : 1;
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
			'base'      => add_query_arg( 'fav_page', '%#%' ),
			'format'    => '',
			'total'     => $query->max_num_pages,
			'current'   => $this->get_paged(),
			'mid_size'  => 2,
			'prev_text' => '&laquo; ' . __( 'Previous', 'all-purpose-directory' ),
			'next_text' => __( 'Next', 'all-purpose-directory' ) . ' &raquo;',
		];

		/**
		 * Filter favorites shortcode pagination arguments.
		 *
		 * @since 1.0.0
		 *
		 * @param array     $args  Pagination arguments.
		 * @param \WP_Query $query The query object.
		 * @param array     $atts  Shortcode attributes.
		 */
		$args = apply_filters( 'apd_favorites_shortcode_pagination_args', $args, $query, $atts );

		$links = paginate_links( $args );

		if ( $links ) {
			printf(
				'<nav class="apd-pagination" aria-label="%s">%s</nav>',
				esc_attr__( 'Favorites pagination', 'all-purpose-directory' ),
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
		if ( ! $atts['show_empty'] ) {
			return;
		}

		if ( ! empty( $atts['empty_message'] ) ) {
			$message = $atts['empty_message'];
		} else {
			/**
			 * Filter the favorites no results message.
			 *
			 * @since 1.0.0
			 *
			 * @param string $message The no results message.
			 * @param array  $atts    Shortcode attributes.
			 */
			$message = apply_filters(
				'apd_favorites_shortcode_no_results_message',
				__( 'You have no favorite listings yet.', 'all-purpose-directory' ),
				$atts
			);
		}

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
