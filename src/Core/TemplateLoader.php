<?php
/**
 * Template Loader for WordPress template hierarchy.
 *
 * Hooks into WordPress's template loading to provide custom templates
 * for listing archives and single listings.
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
 * Class TemplateLoader
 *
 * Integrates plugin templates with WordPress template hierarchy.
 *
 * @since 1.0.0
 */
final class TemplateLoader {

	/**
	 * Template instance.
	 *
	 * @var Template
	 */
	private Template $template;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param Template|null $template Optional. Template instance.
	 */
	public function __construct( ?Template $template = null ) {
		$this->template = $template ?? Template::get_instance();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init(): void {
		add_filter( 'template_include', [ $this, 'template_include' ], 10 );
		add_filter( 'body_class', [ $this, 'body_class' ], 10 );
		add_action( 'wp_head', [ $this, 'track_listing_view' ] );
		add_filter( 'the_content', [ $this, 'append_listing_fields' ], 20 );
		add_filter( 'comments_template', [ $this, 'listing_comments_template' ], 10 );
		add_filter( 'render_block', [ $this, 'replace_listing_comments_block' ], 10, 2 );
		add_filter( 'render_block', [ $this, 'fix_listing_post_terms_block' ], 10, 2 );
	}

	/**
	 * Filter the template to include.
	 *
	 * Checks if we're on a listing-related page and loads
	 * the appropriate plugin template if available.
	 *
	 * Note: Block themes (FSE) handle their own templates via the Site Editor,
	 * so we don't override templates for block themes.
	 *
	 * @since 1.0.0
	 *
	 * @param string $template The path of the template to include.
	 * @return string The filtered template path.
	 */
	public function template_include( string $template ): string {
		// Block themes (FSE) handle templates differently - don't override.
		// They use block templates and don't have header.php/footer.php.
		if ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) {
			return $template;
		}

		// Listing post type archive.
		if ( is_post_type_archive( 'apd_listing' ) ) {
			$located = $this->template->locate_template( 'archive-listing.php' );
			if ( $located ) {
				return $located;
			}
		}

		// Taxonomy archives (category and tag).
		if ( is_tax( 'apd_category' ) || is_tax( 'apd_tag' ) ) {
			// Try specific taxonomy template first.
			$taxonomy = get_queried_object()->taxonomy ?? '';
			$slug     = get_queried_object()->slug ?? '';

			$templates_to_try = [];

			if ( $taxonomy === 'apd_category' ) {
				$templates_to_try[] = "taxonomy-apd_category-{$slug}.php";
				$templates_to_try[] = 'taxonomy-apd_category.php';
			} elseif ( $taxonomy === 'apd_tag' ) {
				$templates_to_try[] = "taxonomy-apd_tag-{$slug}.php";
				$templates_to_try[] = 'taxonomy-apd_tag.php';
			}

			// Fall back to archive template.
			$templates_to_try[] = 'archive-listing.php';

			foreach ( $templates_to_try as $template_name ) {
				$located = $this->template->locate_template( $template_name );
				if ( $located ) {
					return $located;
				}
			}
		}

		// Single listing.
		if ( is_singular( 'apd_listing' ) ) {
			$located = $this->template->locate_template( 'single-listing.php' );
			if ( $located ) {
				return $located;
			}
		}

		return $template;
	}

	/**
	 * Add body classes for listing pages.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string> $classes Body classes.
	 * @return array<string> Modified body classes.
	 */
	public function body_class( array $classes ): array {
		if ( is_post_type_archive( 'apd_listing' ) ) {
			$classes[] = 'apd-archive';
			$classes[] = 'apd-archive-listing';
			$classes[] = 'apd-view-' . $this->get_current_view();
		}

		if ( is_tax( 'apd_category' ) ) {
			$classes[] = 'apd-archive';
			$classes[] = 'apd-archive-category';
			$classes[] = 'apd-view-' . $this->get_current_view();
		}

		if ( is_tax( 'apd_tag' ) ) {
			$classes[] = 'apd-archive';
			$classes[] = 'apd-archive-tag';
			$classes[] = 'apd-view-' . $this->get_current_view();
		}

		if ( is_singular( 'apd_listing' ) ) {
			$classes[] = 'apd-single';
			$classes[] = 'apd-single-listing';
		}

		return $classes;
	}

	/**
	 * Get the current view mode (grid or list).
	 *
	 * @since 1.0.0
	 *
	 * @return string View mode ('grid' or 'list').
	 */
	public function get_current_view(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$view = isset( $_GET['apd_view'] ) ? sanitize_key( $_GET['apd_view'] ) : '';

		if ( in_array( $view, [ 'grid', 'list' ], true ) ) {
			return $view;
		}

		// Check cookie for saved preference.
		if ( isset( $_COOKIE['apd_view'] ) ) {
			$cookie_view = sanitize_key( $_COOKIE['apd_view'] );
			if ( in_array( $cookie_view, [ 'grid', 'list' ], true ) ) {
				return $cookie_view;
			}
		}

		// Default from settings or fallback.
		$default = apd_get_option( 'default_view', 'grid' );

		return in_array( $default, [ 'grid', 'list' ], true ) ? $default : 'grid';
	}

	/**
	 * Get the current grid columns.
	 *
	 * @since 1.0.0
	 *
	 * @return int Number of columns (2, 3, or 4).
	 */
	public function get_grid_columns(): int {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$columns = isset( $_GET['apd_columns'] ) ? absint( $_GET['apd_columns'] ) : 0;

		if ( in_array( $columns, [ 2, 3, 4 ], true ) ) {
			return $columns;
		}

		// Default from settings or fallback.
		$default = absint( apd_get_option( 'grid_columns', 3 ) );

		return in_array( $default, [ 2, 3, 4 ], true ) ? $default : 3;
	}

	/**
	 * Get the view switcher URL.
	 *
	 * @since 1.0.0
	 *
	 * @param string $view The view to switch to.
	 * @return string URL with view parameter.
	 */
	public function get_view_url( string $view ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$params = $_GET;

		$params['apd_view'] = $view;

		// Get current URL base.
		if ( is_post_type_archive( 'apd_listing' ) ) {
			$base_url = get_post_type_archive_link( 'apd_listing' );
		} elseif ( is_tax() ) {
			$term     = get_queried_object();
			$base_url = get_term_link( $term );
		} else {
			$base_url = home_url( add_query_arg( [], false ) );
		}

		if ( is_wp_error( $base_url ) ) {
			$base_url = home_url();
		}

		return add_query_arg( $params, $base_url );
	}

	/**
	 * Get archive title.
	 *
	 * @since 1.0.0
	 *
	 * @return string The archive title.
	 */
	public function get_archive_title(): string {
		if ( is_post_type_archive( 'apd_listing' ) ) {
			$custom_title = \apd_get_option( 'archive_title', '' );
			if ( ! empty( $custom_title ) ) {
				$title = $custom_title;
			} else {
				$title = post_type_archive_title( '', false );

				if ( empty( $title ) ) {
					$post_type = get_post_type_object( 'apd_listing' );
					$title     = $post_type->labels->name ?? __( 'Listings', 'all-purpose-directory' );
				}
			}
		} elseif ( is_tax( 'apd_category' ) || is_tax( 'apd_tag' ) ) {
			$title = single_term_title( '', false );
		} else {
			$title = __( 'Listings', 'all-purpose-directory' );
		}

		/**
		 * Filter the archive title.
		 *
		 * @since 1.0.0
		 *
		 * @param string $title The archive title.
		 */
		return apply_filters( 'apd_archive_title', $title );
	}

	/**
	 * Get archive description.
	 *
	 * @since 1.0.0
	 *
	 * @return string The archive description.
	 */
	public function get_archive_description(): string {
		$description = '';

		if ( is_post_type_archive( 'apd_listing' ) ) {
			$post_type   = get_post_type_object( 'apd_listing' );
			$description = $post_type->description ?? '';
		} elseif ( is_tax( 'apd_category' ) || is_tax( 'apd_tag' ) ) {
			$description = term_description();
		}

		/**
		 * Filter the archive description.
		 *
		 * @since 1.0.0
		 *
		 * @param string $description The archive description.
		 */
		return apply_filters( 'apd_archive_description', $description );
	}

	/**
	 * Render the view switcher HTML.
	 *
	 * @since 1.0.0
	 *
	 * @return string The HTML for the view switcher.
	 */
	public function render_view_switcher(): string {
		$current_view = $this->get_current_view();
		$grid_url     = $this->get_view_url( 'grid' );
		$list_url     = $this->get_view_url( 'list' );

		$output = '<div class="apd-view-switcher" role="group" aria-label="' . esc_attr__( 'View mode', 'all-purpose-directory' ) . '">';

		// Grid button.
		$grid_class = 'apd-view-switcher__btn apd-view-switcher__btn--grid';
		if ( $current_view === 'grid' ) {
			$grid_class .= ' apd-view-switcher__btn--active';
		}
		$output .= sprintf(
			'<a href="%s" class="%s" %s aria-label="%s"><span class="dashicons dashicons-grid-view" aria-hidden="true"></span></a>',
			esc_url( $grid_url ),
			esc_attr( $grid_class ),
			$current_view === 'grid' ? 'aria-current="true"' : '',
			esc_attr__( 'Grid view', 'all-purpose-directory' )
		);

		// List button.
		$list_class = 'apd-view-switcher__btn apd-view-switcher__btn--list';
		if ( $current_view === 'list' ) {
			$list_class .= ' apd-view-switcher__btn--active';
		}
		$output .= sprintf(
			'<a href="%s" class="%s" %s aria-label="%s"><span class="dashicons dashicons-list-view" aria-hidden="true"></span></a>',
			esc_url( $list_url ),
			esc_attr( $list_class ),
			$current_view === 'list' ? 'aria-current="true"' : '',
			esc_attr__( 'List view', 'all-purpose-directory' )
		);

		$output .= '</div>';

		return $output;
	}

	/**
	 * Render results count.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_Query|null $query Optional. Query to get count from.
	 * @return string The HTML for the results count.
	 */
	public function render_results_count( ?\WP_Query $query = null ): string {
		if ( $query === null ) {
			global $wp_query;
			$query = $wp_query;
		}

		$total    = $query->found_posts;
		$paged    = max( 1, $query->get( 'paged' ) );
		$per_page = $query->get( 'posts_per_page' );

		if ( $per_page < 0 ) {
			$per_page = $total;
		}

		$start = ( ( $paged - 1 ) * $per_page ) + 1;
		$end   = min( $paged * $per_page, $total );

		if ( $total === 0 ) {
			$text = __( 'No listings found', 'all-purpose-directory' );
		} elseif ( $total === 1 ) {
			$text = __( 'Showing 1 listing', 'all-purpose-directory' );
		} elseif ( $end === $total ) {
			/* translators: %d: total number of listings */
			$text = sprintf( __( 'Showing all %d listings', 'all-purpose-directory' ), $total );
		} else {
			/* translators: 1: start, 2: end, 3: total */
			$text = sprintf( __( 'Showing %1$d-%2$d of %3$d listings', 'all-purpose-directory' ), $start, $end, $total );
		}

		return sprintf(
			'<p class="apd-results-count" aria-live="polite">%s</p>',
			esc_html( $text )
		);
	}

	/**
	 * Render pagination.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_Query|null $query Optional. Query to paginate.
	 * @return string The pagination HTML.
	 */
	public function render_pagination( ?\WP_Query $query = null ): string {
		if ( $query === null ) {
			global $wp_query;
			$query = $wp_query;
		}

		$total_pages = $query->max_num_pages;

		if ( $total_pages < 2 ) {
			return '';
		}

		$paged = max( 1, $query->get( 'paged' ) );

		$args = [
			'total'              => $total_pages,
			'current'            => $paged,
			'show_all'           => false,
			'end_size'           => 1,
			'mid_size'           => 2,
			'prev_next'          => true,
			'prev_text'          => sprintf(
				'<span class="screen-reader-text">%s</span><span aria-hidden="true">&laquo;</span>',
				__( 'Previous page', 'all-purpose-directory' )
			),
			'next_text'          => sprintf(
				'<span class="screen-reader-text">%s</span><span aria-hidden="true">&raquo;</span>',
				__( 'Next page', 'all-purpose-directory' )
			),
			'type'               => 'plain',
			'add_args'           => false,
			'add_fragment'       => '',
			'before_page_number' => '<span class="screen-reader-text">' . __( 'Page', 'all-purpose-directory' ) . ' </span>',
		];

		// In AJAX context, paginate_links() uses the current request URI as the base,
		// which is admin-ajax.php. Override to produce clean ?paged=N links instead.
		if ( wp_doing_ajax() ) {
			$args['base']   = '%_%';
			$args['format'] = '?paged=%#%';
		}

		/**
		 * Filter the pagination arguments.
		 *
		 * @since 1.0.0
		 *
		 * @param array     $args  Pagination arguments.
		 * @param \WP_Query $query The query being paginated.
		 */
		$args = apply_filters( 'apd_pagination_args', $args, $query );

		$links = paginate_links( $args );

		if ( empty( $links ) ) {
			return '';
		}

		$output  = '<nav class="apd-pagination" role="navigation" aria-label="' . esc_attr__( 'Listings pagination', 'all-purpose-directory' ) . '">';
		$output .= '<div class="apd-pagination__links">' . $links . '</div>';
		$output .= '</nav>';

		return $output;
	}

	/**
	 * Render archive content HTML.
	 *
	 * Returns the full listing archive content (header, search form, active filters,
	 * toolbar, listing grid, and pagination) as an HTML string. Used by both the
	 * classic theme template (archive-listing.php) and the block theme shortcode
	 * ([apd_archive_content]).
	 *
	 * @since 1.0.0
	 *
	 * @return string The archive content HTML.
	 */
	public function render_archive_content(): string {
		global $wp_query;

		$current_view = $this->get_current_view();
		$grid_columns = $this->get_grid_columns();

		ob_start();

		/**
		 * Fires before the listing archive content.
		 *
		 * @since 1.0.0
		 */
		do_action( 'apd_before_archive' );
		?>

		<div class="apd-archive-wrapper">

			<?php
			/**
			 * Fires at the start of the archive wrapper.
			 *
			 * @since 1.0.0
			 */
			do_action( 'apd_archive_wrapper_start' );
			?>

			<header class="apd-archive-header">
				<h1 class="apd-archive-title"><?php echo esc_html( $this->get_archive_title() ); ?></h1>

				<?php
				$description = $this->get_archive_description();
				if ( ! empty( $description ) ) :
					?>
					<div class="apd-archive-description">
						<?php echo wp_kses_post( $description ); ?>
					</div>
				<?php endif; ?>
			</header>

			<?php
			/**
			 * Fires before the search form.
			 *
			 * @since 1.0.0
			 */
			do_action( 'apd_before_archive_search_form' );
			?>

			<div class="apd-archive-search">
				<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo apd_render_search_form();
				?>
			</div>

			<?php
			/**
			 * Fires after the search form.
			 *
			 * @since 1.0.0
			 */
			do_action( 'apd_after_archive_search_form' );
			?>

			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo apd_render_active_filters();
			?>

			<div class="apd-archive-toolbar">
				<div class="apd-archive-toolbar__left">
					<?php
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo $this->render_results_count();
					?>
				</div>
				<div class="apd-archive-toolbar__right">
					<?php
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo $this->render_view_switcher();
					?>
				</div>
			</div>

			<?php
			/**
			 * Fires before the listings loop.
			 *
			 * @since 1.0.0
			 */
			do_action( 'apd_before_archive_loop' );
			?>

			<?php if ( have_posts() ) : ?>

				<div class="apd-listings apd-listings--<?php echo esc_attr( $current_view ); ?> apd-listings--columns-<?php echo esc_attr( (string) $grid_columns ); ?>"
					data-view="<?php echo esc_attr( $current_view ); ?>"
					data-columns="<?php echo esc_attr( (string) $grid_columns ); ?>"
					data-posts-per-page="<?php echo esc_attr( (string) get_option( 'posts_per_page', 10 ) ); ?>">

					<?php
					while ( have_posts() ) :
						the_post();

						$template_name = $current_view === 'list' ? 'listing-card-list' : 'listing-card';

						apd_get_template_part(
							$template_name,
							null,
							[
								'listing_id'   => get_the_ID(),
								'current_view' => $current_view,
							]
						);
					endwhile;
					?>

				</div>

				<?php
				/**
				 * Fires after the listings loop, before pagination.
				 *
				 * @since 1.0.0
				 */
				do_action( 'apd_after_archive_loop' );
				?>

				<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $this->render_pagination();
				?>

			<?php else : ?>

				<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo apd_render_no_results();
				?>

			<?php endif; ?>

			<?php
			/**
			 * Fires at the end of the archive wrapper.
			 *
			 * @since 1.0.0
			 */
			do_action( 'apd_archive_wrapper_end' );
			?>

		</div>

		<?php
		/**
		 * Fires after the listing archive content.
		 *
		 * @since 1.0.0
		 */
		do_action( 'apd_after_archive' );

		$html = ob_get_clean();

		/**
		 * Filter the complete archive content HTML.
		 *
		 * @since 1.0.0
		 *
		 * @param string $html The archive content HTML.
		 */
		return apply_filters( 'apd_archive_content', $html );
	}

	/**
	 * Append listing fields to post content for block themes.
	 *
	 * Block themes (FSE) don't use the plugin's single-listing.php template,
	 * so we inject the custom fields, categories, tags, and contact form
	 * directly into the_content output.
	 *
	 * @since 1.0.0
	 *
	 * @param string $content The post content.
	 * @return string Modified content with listing fields appended.
	 */
	public function append_listing_fields( string $content ): string {
		// Only apply on single listing pages in the main query.
		if ( ! is_singular( 'apd_listing' ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		// Only apply for block themes where our template isn't loaded.
		if ( ! function_exists( 'wp_is_block_theme' ) || ! wp_is_block_theme() ) {
			return $content;
		}

		$listing_id = get_the_ID();

		if ( ! $listing_id ) {
			return $content;
		}

		$extra = '';

		/**
		 * Fires before the listing content in block theme context.
		 *
		 * @since 1.0.0
		 *
		 * @param int $listing_id The listing post ID.
		 */
		do_action( 'apd_single_listing_after_content', $listing_id );

		// Listing meta (favorite button, rating summary, etc.).
		ob_start();
		do_action( 'apd_single_listing_meta', $listing_id );
		$meta_html = ob_get_clean();

		if ( ! empty( trim( $meta_html ) ) ) {
			$extra .= '<div class="apd-single-listing__meta">' . $meta_html . '</div>';
		}

		// Custom fields.
		$custom_fields_html = apd_render_display_fields( $listing_id );

		if ( ! empty( $custom_fields_html ) ) {
			$extra .= '<div class="apd-single-listing__fields">';
			$extra .= '<h2 class="apd-single-listing__section-title">'
				. esc_html__( 'Details', 'all-purpose-directory' ) . '</h2>';
			$extra .= $custom_fields_html;
			$extra .= '</div>';
		}

		/**
		 * Fires after custom fields in block theme context.
		 *
		 * @since 1.0.0
		 *
		 * @param int $listing_id The listing post ID.
		 */
		do_action( 'apd_single_listing_after_fields', $listing_id );

		// Tags.
		$tags = apd_get_listing_tags( $listing_id );

		if ( ! empty( $tags ) ) {
			$extra .= '<div class="apd-single-listing__tags">';
			$extra .= '<h3 class="apd-single-listing__tags-title">';
			$extra .= '<span class="dashicons dashicons-tag" aria-hidden="true"></span> ';
			$extra .= esc_html__( 'Tags', 'all-purpose-directory' );
			$extra .= '</h3>';
			$extra .= '<div class="apd-single-listing__tags-list">';

			foreach ( $tags as $tag ) {
				$extra .= sprintf(
					'<a href="%s" class="apd-single-listing__tag">%s</a> ',
					esc_url( get_term_link( $tag ) ),
					esc_html( $tag->name )
				);
			}

			$extra .= '</div></div>';
		}

		// Contact form hook.
		ob_start();
		do_action( 'apd_single_listing_contact_form', $listing_id );
		$contact_html = ob_get_clean();

		if ( ! empty( trim( $contact_html ) ) ) {
			$extra .= $contact_html;
		}

		if ( ! empty( $extra ) ) {
			$content .= '<div class="apd-single-listing-extras">' . $extra . '</div>';
		}

		return $content;
	}

	/**
	 * Override the comments template for listings.
	 *
	 * Replaces the default WordPress comments template with a custom one
	 * that renders reviews instead of standard comments. This ensures
	 * block themes display the review system rather than a plain comment form.
	 *
	 * @since 1.0.0
	 *
	 * @param string $template Path to the comments template.
	 * @return string Filtered template path.
	 */
	public function listing_comments_template( string $template ): string {
		if ( ! is_singular( 'apd_listing' ) ) {
			return $template;
		}

		$custom = $this->template->locate_template( 'comments-listing.php' );

		if ( $custom ) {
			return $custom;
		}

		return $template;
	}

	/**
	 * Replace the core/comments block output for listings.
	 *
	 * Block themes render comments via the core/comments block, which bypasses
	 * the `comments_template` filter. This filter intercepts the block output
	 * and replaces it with the plugin's review section when on a listing.
	 *
	 * @since 1.0.0
	 *
	 * @param string               $block_content The block content.
	 * @param array<string, mixed> $block         The full block, including name and attributes.
	 * @return string The filtered block content.
	 */
	public function replace_listing_comments_block( string $block_content, array $block ): string {
		if ( $block['blockName'] !== 'core/comments' ) {
			return $block_content;
		}

		if ( ! is_singular( 'apd_listing' ) ) {
			return $block_content;
		}

		// Reviews disabled — suppress the comments block entirely.
		if ( ! apd_reviews_enabled() ) {
			return '';
		}

		$listing_id = get_the_ID();

		if ( ! $listing_id ) {
			return '';
		}

		ob_start();

		/**
		 * Fires in the reviews section area for listings.
		 *
		 * Replaces the default WordPress Comments block in block themes.
		 *
		 * @since 1.0.0
		 *
		 * @param int $listing_id The listing post ID.
		 */
		do_action( 'apd_single_listing_reviews', $listing_id );

		return ob_get_clean();
	}

	/**
	 * Fix post-terms block output for listings in block themes.
	 *
	 * Block themes (e.g. Twenty Twenty-Five) render "Written by [author] in [categories]"
	 * via core/post-terms with term=category. Since listings use apd_category instead,
	 * the block outputs empty HTML but the "in" separator remains. This removes the
	 * empty post-terms block and cleans up the orphaned separator.
	 *
	 * @since 1.0.0
	 *
	 * @param string $block_content Rendered block content.
	 * @param array  $block         Block data including name and attributes.
	 * @return string Filtered block content.
	 */
	public function fix_listing_post_terms_block( string $block_content, array $block ): string {
		if ( ! is_singular( 'apd_listing' ) ) {
			return $block_content;
		}

		// Remove empty core/post-terms blocks for the "category" taxonomy
		// (listings use apd_category, so the standard category block is empty).
		if ( $block['blockName'] === 'core/post-terms' ) {
			$term = $block['attrs']['term'] ?? 'post_tag';
			if ( $term === 'category' && trim( wp_strip_all_tags( $block_content ) ) === '' ) {
				return '';
			}
		}

		// Clean up orphaned "in" separator text from the parent group block
		// that wraps "Written by [author] in [categories]".
		if ( $block['blockName'] === 'core/group' && ! empty( $block['innerBlocks'] ) ) {
			$has_empty_terms = false;
			foreach ( $block['innerBlocks'] as $inner_block ) {
				if (
					$inner_block['blockName'] === 'core/post-terms' &&
					( $inner_block['attrs']['term'] ?? 'post_tag' ) === 'category'
				) {
					$has_empty_terms = true;
					break;
				}
			}

			if ( $has_empty_terms ) {
				// Remove the "in" separator paragraph (e.g. <p>in</p>) that precedes the empty post-terms block.
				$block_content = preg_replace( '/\s*<p[^>]*>\s*in\s*<\/p>\s*/i', '', $block_content );
			}
		}

		return $block_content;
	}

	/**
	 * Track listing view count.
	 *
	 * Increments the view count when a single listing is viewed.
	 * Skips bots, logged-in admins (optional), and already counted sessions.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function track_listing_view(): void {
		if ( ! is_singular( 'apd_listing' ) ) {
			return;
		}

		$listing_id = get_queried_object_id();

		if ( ! $listing_id ) {
			return;
		}

		// Skip if user is admin (unless they want to count admin views).
		$skip_admin = apply_filters( 'apd_skip_admin_view_count', true );
		if ( $skip_admin && current_user_can( 'manage_options' ) ) {
			return;
		}

		// Skip bots based on user agent.
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] )
			? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) )
			: '';

		if ( $this->is_bot( $user_agent ) ) {
			return;
		}

		// Increment view count.
		\apd_increment_listing_views( $listing_id );
	}

	/**
	 * Check if the user agent belongs to a bot.
	 *
	 * @since 1.0.0
	 *
	 * @param string $user_agent The user agent string.
	 * @return bool True if likely a bot.
	 */
	private function is_bot( string $user_agent ): bool {
		if ( empty( $user_agent ) ) {
			return true;
		}

		$bot_patterns = [
			'bot',
			'crawl',
			'spider',
			'slurp',
			'search',
			'facebook',
			'twitter',
			'linkedin',
			'pinterest',
			'curl',
			'wget',
			'python',
			'java',
			'ruby',
		];

		$user_agent_lower = strtolower( $user_agent );

		foreach ( $bot_patterns as $pattern ) {
			if ( strpos( $user_agent_lower, $pattern ) !== false ) {
				return true;
			}
		}

		return false;
	}
}
