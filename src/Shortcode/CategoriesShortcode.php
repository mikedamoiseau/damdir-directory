<?php
/**
 * Categories Shortcode Class.
 *
 * Displays listing categories in various layouts.
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
 * Class CategoriesShortcode
 *
 * @since 1.0.0
 */
final class CategoriesShortcode extends AbstractShortcode {

	/**
	 * Shortcode tag.
	 *
	 * @var string
	 */
	protected string $tag = 'apd_categories';

	/**
	 * Shortcode description.
	 *
	 * @var string
	 */
	protected string $description = 'Display listing categories in a grid or list.';

	/**
	 * Default attributes.
	 *
	 * @var array<string, mixed>
	 */
	protected array $defaults = [
		'layout'           => 'grid',
		'columns'          => 4,
		'count'            => 0,
		'parent'           => '',
		'include'          => '',
		'exclude'          => '', // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- Attribute key name for shortcode API; not a query arg here.
		'hide_empty'       => 'true',
		'orderby'          => 'name',
		'order'            => 'ASC',
		'show_count'       => 'true',
		'show_icon'        => 'true',
		'show_image'       => 'false',
		'show_description' => 'false',
		'class'            => '',
	];

	/**
	 * Attribute documentation.
	 *
	 * @var array<string, array>
	 */
	protected array $attribute_docs = [
		'layout'           => [
			'type'        => 'slug',
			'description' => 'Display layout: grid or list.',
			'default'     => 'grid',
		],
		'columns'          => [
			'type'        => 'integer',
			'description' => 'Number of columns for grid layout (2-6).',
			'default'     => 4,
		],
		'count'            => [
			'type'        => 'integer',
			'description' => 'Number of categories to show (0 = all).',
			'default'     => 0,
		],
		'parent'           => [
			'type'        => 'integer',
			'description' => 'Parent category ID (0 for top-level only).',
			'default'     => '',
		],
		'include'          => [
			'type'        => 'ids',
			'description' => 'Category IDs to include (comma-separated).',
			'default'     => '',
		],
		'exclude'          => [ // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- Attribute schema key for shortcode docs.
			'type'        => 'ids',
			'description' => 'Category IDs to exclude (comma-separated).',
			'default'     => '',
		],
		'hide_empty'       => [
			'type'        => 'boolean',
			'description' => 'Hide categories with no listings.',
			'default'     => 'true',
		],
		'orderby'          => [
			'type'        => 'slug',
			'description' => 'Order by: name, count, id, slug.',
			'default'     => 'name',
		],
		'order'            => [
			'type'        => 'slug',
			'description' => 'Sort order: ASC or DESC.',
			'default'     => 'ASC',
		],
		'show_count'       => [
			'type'        => 'boolean',
			'description' => 'Show listing count per category.',
			'default'     => 'true',
		],
		'show_icon'        => [
			'type'        => 'boolean',
			'description' => 'Show category icons.',
			'default'     => 'true',
		],
		'show_image'       => [
			'type'        => 'boolean',
			'description' => 'Show category images (if set).',
			'default'     => 'false',
		],
		'show_description' => [
			'type'        => 'boolean',
			'description' => 'Show category descriptions.',
			'default'     => 'false',
		],
		'class'            => [
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
		return '[apd_categories layout="grid" columns="4" show_count="true"]';
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
		// Get categories.
		$categories = $this->get_categories( $atts );

		if ( empty( $categories ) ) {
			return $this->render_no_categories( $atts );
		}

		// Start output buffering.
		ob_start();

		// Container classes.
		$container_classes = $this->get_container_classes( $atts );

		?>
		<div class="<?php echo esc_attr( implode( ' ', $container_classes ) ); ?>">
			<?php
			/**
			 * Fires before categories shortcode output.
			 *
			 * @since 1.0.0
			 *
			 * @param array $atts       The shortcode attributes.
			 * @param array $categories The categories array.
			 */
			do_action( 'apd_before_categories_shortcode', $atts, $categories );

			if ( $atts['layout'] === 'list' ) {
				$this->render_list( $categories, $atts );
			} else {
				$this->render_grid( $categories, $atts );
			}

			/**
			 * Fires after categories shortcode output.
			 *
			 * @since 1.0.0
			 *
			 * @param array $atts       The shortcode attributes.
			 * @param array $categories The categories array.
			 */
			do_action( 'apd_after_categories_shortcode', $atts, $categories );
			?>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Get categories based on shortcode attributes.
	 *
	 * @since 1.0.0
	 *
	 * @param array $atts Shortcode attributes.
	 * @return array Array of WP_Term objects.
	 */
	private function get_categories( array $atts ): array {
		$args = [
			'taxonomy'   => 'apd_category',
			'hide_empty' => filter_var( $atts['hide_empty'], FILTER_VALIDATE_BOOLEAN ),
			'orderby'    => $this->validate_orderby( $atts['orderby'] ),
			'order'      => strtoupper( $atts['order'] ) === 'DESC' ? 'DESC' : 'ASC',
		];

		// Number limit.
		if ( $atts['count'] > 0 ) {
			$args['number'] = $atts['count'];
		}

		// Parent filter.
		if ( $atts['parent'] !== '' ) {
			$args['parent'] = absint( $atts['parent'] );
		}

		// Include specific categories.
		if ( ! empty( $atts['include'] ) ) {
			$args['include'] = $atts['include'];
		}

		// Exclude categories.
		if ( ! empty( $atts['exclude'] ) ) {
			$args['exclude'] = $atts['exclude']; // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- get_terms() term exclusion is intentional user-controlled filtering.
		}

		/**
		 * Filter the categories query arguments.
		 *
		 * @since 1.0.0
		 *
		 * @param array $args The query arguments.
		 * @param array $atts The shortcode attributes.
		 */
		$args = apply_filters( 'apd_categories_shortcode_query_args', $args, $atts );

		$categories = get_terms( $args );

		if ( is_wp_error( $categories ) ) {
			return [];
		}

		return $categories;
	}

	/**
	 * Render grid layout.
	 *
	 * @since 1.0.0
	 *
	 * @param array $categories Categories to render.
	 * @param array $atts       Shortcode attributes.
	 * @return void
	 */
	private function render_grid( array $categories, array $atts ): void {
		?>
		<div class="apd-categories__grid">
			<?php foreach ( $categories as $category ) : ?>
				<?php $this->render_category_card( $category, $atts ); ?>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Render list layout.
	 *
	 * @since 1.0.0
	 *
	 * @param array $categories Categories to render.
	 * @param array $atts       Shortcode attributes.
	 * @return void
	 */
	private function render_list( array $categories, array $atts ): void {
		?>
		<ul class="apd-categories__list">
			<?php foreach ( $categories as $category ) : ?>
				<li class="apd-categories__list-item">
					<?php $this->render_category_link( $category, $atts ); ?>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php
	}

	/**
	 * Render a single category card (grid layout).
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_Term $category The category term.
	 * @param array    $atts     Shortcode attributes.
	 * @return void
	 */
	private function render_category_card( \WP_Term $category, array $atts ): void {
		$link  = get_term_link( $category );
		$icon  = $atts['show_icon'] ? \apd_get_category_icon( $category ) : '';
		$color = \apd_get_category_color( $category );

		$card_style = '';
		if ( $color ) {
			$card_style = sprintf( '--apd-category-color: %s;', esc_attr( $color ) );
		}

		?>
		<div class="apd-category-card" style="<?php echo esc_attr( $card_style ); ?>">
			<a href="<?php echo esc_url( $link ); ?>" class="apd-category-card__link">
				<?php if ( $icon ) : ?>
					<span class="apd-category-card__icon dashicons <?php echo esc_attr( $icon ); ?>" aria-hidden="true"></span>
				<?php endif; ?>

				<span class="apd-category-card__name"><?php echo esc_html( $category->name ); ?></span>

				<?php if ( $atts['show_count'] ) : ?>
					<span class="apd-category-card__count">
						<?php
						printf(
							/* translators: %d: Number of listings */
							esc_html( _n( '%d listing', '%d listings', $category->count, 'all-purpose-directory' ) ),
							absint( $category->count )
						);
						?>
					</span>
				<?php endif; ?>

				<?php if ( $atts['show_description'] && $category->description ) : ?>
					<span class="apd-category-card__description">
						<?php echo esc_html( wp_trim_words( $category->description, 15 ) ); ?>
					</span>
				<?php endif; ?>
			</a>
		</div>
		<?php
	}

	/**
	 * Render a single category link (list layout).
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_Term $category The category term.
	 * @param array    $atts     Shortcode attributes.
	 * @return void
	 */
	private function render_category_link( \WP_Term $category, array $atts ): void {
		$link = get_term_link( $category );
		$icon = $atts['show_icon'] ? \apd_get_category_icon( $category ) : '';

		?>
		<a href="<?php echo esc_url( $link ); ?>" class="apd-category-link">
			<?php if ( $icon ) : ?>
				<span class="apd-category-link__icon dashicons <?php echo esc_attr( $icon ); ?>" aria-hidden="true"></span>
			<?php endif; ?>

			<span class="apd-category-link__name"><?php echo esc_html( $category->name ); ?></span>

			<?php if ( $atts['show_count'] ) : ?>
				<span class="apd-category-link__count">(<?php echo absint( $category->count ); ?>)</span>
			<?php endif; ?>
		</a>

		<?php if ( $atts['show_description'] && $category->description ) : ?>
			<p class="apd-category-link__description">
				<?php echo esc_html( wp_trim_words( $category->description, 20 ) ); ?>
			</p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Get container CSS classes.
	 *
	 * @since 1.0.0
	 *
	 * @param array $atts Shortcode attributes.
	 * @return array CSS classes.
	 */
	private function get_container_classes( array $atts ): array {
		$classes = [
			'apd-categories-shortcode',
			'apd-categories',
			'apd-categories--' . $atts['layout'],
		];

		if ( $atts['layout'] === 'grid' ) {
			$classes[] = 'apd-categories--columns-' . $atts['columns'];
		}

		if ( ! empty( $atts['class'] ) ) {
			$classes[] = $atts['class'];
		}

		/**
		 * Filter the categories container classes.
		 *
		 * @since 1.0.0
		 *
		 * @param array $classes The CSS classes.
		 * @param array $atts    The shortcode attributes.
		 */
		return apply_filters( 'apd_categories_shortcode_classes', $classes, $atts );
	}

	/**
	 * Render no categories message.
	 *
	 * @since 1.0.0
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	private function render_no_categories( array $atts ): string {
		/**
		 * Filter the no categories message.
		 *
		 * @since 1.0.0
		 *
		 * @param string $message The message.
		 * @param array  $atts    Shortcode attributes.
		 */
		$message = apply_filters(
			'apd_categories_shortcode_no_results_message',
			__( 'No categories found.', 'all-purpose-directory' ),
			$atts
		);

		return sprintf(
			'<div class="apd-categories-shortcode apd-no-results"><p>%s</p></div>',
			esc_html( $message )
		);
	}

	/**
	 * Validate orderby value.
	 *
	 * @since 1.0.0
	 *
	 * @param string $orderby Orderby value.
	 * @return string Validated orderby.
	 */
	private function validate_orderby( string $orderby ): string {
		$valid = [ 'name', 'count', 'id', 'slug', 'term_group', 'none' ];

		if ( in_array( $orderby, $valid, true ) ) {
			return $orderby;
		}

		return 'name';
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

		// Validate columns.
		$sanitized['columns'] = max( 2, min( 6, $sanitized['columns'] ) );

		// Validate layout.
		if ( ! in_array( $sanitized['layout'], [ 'grid', 'list' ], true ) ) {
			$sanitized['layout'] = 'grid';
		}

		return $sanitized;
	}
}
