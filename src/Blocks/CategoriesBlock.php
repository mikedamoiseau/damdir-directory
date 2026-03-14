<?php
/**
 * Categories Block Class.
 *
 * Displays listing categories in various layouts.
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
 * Class CategoriesBlock
 *
 * @since 1.0.0
 */
final class CategoriesBlock extends AbstractBlock {

	/**
	 * Block name (without namespace).
	 *
	 * @var string
	 */
	protected string $name = 'categories';

	/**
	 * Block title.
	 *
	 * @var string
	 */
	protected string $title = 'Listing Categories';

	/**
	 * Block description.
	 *
	 * @var string
	 */
	protected string $description = 'Display listing categories in a grid or list.';

	/**
	 * Block icon.
	 *
	 * @var string
	 */
	protected string $icon = 'category';

	/**
	 * Block keywords.
	 *
	 * @var array<string>
	 */
	protected array $keywords = [ 'categories', 'taxonomy', 'directory', 'grid' ];

	/**
	 * Block attributes.
	 *
	 * @var array<string, array>
	 */
	protected array $attributes = [
		'layout'          => [
			'type'    => 'string',
			'default' => 'grid',
		],
		'columns'         => [
			'type'    => 'number',
			'default' => 4,
		],
		'count'           => [
			'type'    => 'number',
			'default' => 0,
		],
		'parent'          => [
			'type'    => 'string',
			'default' => '',
		],
		'include'         => [
			'type'    => 'string',
			'default' => '',
		],
		'exclude'         => [ // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- Block attribute schema key.
			'type'    => 'string',
			'default' => '',
		],
		'hideEmpty'       => [
			'type'    => 'boolean',
			'default' => true,
		],
		'orderby'         => [
			'type'    => 'string',
			'default' => 'name',
		],
		'order'           => [
			'type'    => 'string',
			'default' => 'ASC',
		],
		'showCount'       => [
			'type'    => 'boolean',
			'default' => true,
		],
		'showIcon'        => [
			'type'    => 'boolean',
			'default' => true,
		],
		'showDescription' => [
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
		$this->title       = __( 'Listing Categories', 'all-purpose-directory' );
		$this->description = __( 'Display listing categories in a grid or list.', 'all-purpose-directory' );
		$this->keywords    = [
			__( 'categories', 'all-purpose-directory' ),
			__( 'taxonomy', 'all-purpose-directory' ),
			__( 'directory', 'all-purpose-directory' ),
			__( 'grid', 'all-purpose-directory' ),
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
		// Get categories.
		$categories = $this->get_categories( $attributes );

		if ( empty( $categories ) ) {
			return $this->render_no_categories( $attributes );
		}

		// Start output.
		ob_start();

		$extra_classes = [
			'apd-categories-block',
			'apd-categories',
			'apd-categories--' . $attributes['layout'],
		];

		if ( $attributes['layout'] === 'grid' ) {
			$extra_classes[] = 'apd-categories--columns-' . absint( $attributes['columns'] );
		}

		$wrapper_attributes = $this->get_wrapper_attributes( $attributes, $extra_classes );

		?>
		<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<?php
			if ( $attributes['layout'] === 'list' ) {
				$this->render_list( $categories, $attributes );
			} else {
				$this->render_grid( $categories, $attributes );
			}
			?>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Get categories based on block attributes.
	 *
	 * @since 1.0.0
	 *
	 * @param array $attributes Block attributes.
	 * @return array Array of WP_Term objects.
	 */
	private function get_categories( array $attributes ): array {
		$args = [
			'taxonomy'   => 'apd_category',
			'hide_empty' => (bool) $attributes['hideEmpty'],
			'orderby'    => $this->validate_orderby( $attributes['orderby'] ),
			'order'      => strtoupper( $attributes['order'] ) === 'DESC' ? 'DESC' : 'ASC',
		];

		// Number limit.
		if ( absint( $attributes['count'] ) > 0 ) {
			$args['number'] = absint( $attributes['count'] );
		}

		// Parent filter.
		if ( $attributes['parent'] !== '' ) {
			$args['parent'] = absint( $attributes['parent'] );
		}

		// Include specific categories.
		if ( ! empty( $attributes['include'] ) ) {
			$args['include'] = array_map( 'absint', array_filter( explode( ',', $attributes['include'] ) ) );
		}

		// Exclude categories.
		if ( ! empty( $attributes['exclude'] ) ) {
			$args['exclude'] = array_map( 'absint', array_filter( explode( ',', $attributes['exclude'] ) ) ); // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- get_terms() term exclusion is intentional user filtering.
		}

		/**
		 * Filter the categories block query arguments.
		 *
		 * @since 1.0.0
		 *
		 * @param array $args       The query arguments.
		 * @param array $attributes The block attributes.
		 */
		$args = apply_filters( 'apd_categories_block_query_args', $args, $attributes );

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
	 * @param array $attributes Block attributes.
	 * @return void
	 */
	private function render_grid( array $categories, array $attributes ): void {
		?>
		<div class="apd-categories__grid">
			<?php foreach ( $categories as $category ) : ?>
				<?php $this->render_category_card( $category, $attributes ); ?>
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
	 * @param array $attributes Block attributes.
	 * @return void
	 */
	private function render_list( array $categories, array $attributes ): void {
		?>
		<ul class="apd-categories__list">
			<?php foreach ( $categories as $category ) : ?>
				<li class="apd-categories__list-item">
					<?php $this->render_category_link( $category, $attributes ); ?>
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
	 * @param \WP_Term $category   The category term.
	 * @param array    $attributes Block attributes.
	 * @return void
	 */
	private function render_category_card( \WP_Term $category, array $attributes ): void {
		$link  = get_term_link( $category );
		$icon  = $attributes['showIcon'] ? \apd_get_category_icon( $category ) : '';
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

				<?php if ( $attributes['showCount'] ) : ?>
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

				<?php if ( $attributes['showDescription'] && $category->description ) : ?>
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
	 * @param \WP_Term $category   The category term.
	 * @param array    $attributes Block attributes.
	 * @return void
	 */
	private function render_category_link( \WP_Term $category, array $attributes ): void {
		$link = get_term_link( $category );
		$icon = $attributes['showIcon'] ? \apd_get_category_icon( $category ) : '';

		?>
		<a href="<?php echo esc_url( $link ); ?>" class="apd-category-link">
			<?php if ( $icon ) : ?>
				<span class="apd-category-link__icon dashicons <?php echo esc_attr( $icon ); ?>" aria-hidden="true"></span>
			<?php endif; ?>

			<span class="apd-category-link__name"><?php echo esc_html( $category->name ); ?></span>

			<?php if ( $attributes['showCount'] ) : ?>
				<span class="apd-category-link__count">(<?php echo absint( $category->count ); ?>)</span>
			<?php endif; ?>
		</a>

		<?php if ( $attributes['showDescription'] && $category->description ) : ?>
			<p class="apd-category-link__description">
				<?php echo esc_html( wp_trim_words( $category->description, 20 ) ); ?>
			</p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render no categories message.
	 *
	 * @since 1.0.0
	 *
	 * @param array $attributes Block attributes.
	 * @return string HTML output.
	 */
	private function render_no_categories( array $attributes ): string {
		/**
		 * Filter the no categories message.
		 *
		 * @since 1.0.0
		 *
		 * @param string $message    The message.
		 * @param array  $attributes Block attributes.
		 */
		$message = apply_filters(
			'apd_categories_block_no_results_message',
			__( 'No categories found.', 'all-purpose-directory' ),
			$attributes
		);

		$wrapper_attributes = $this->get_wrapper_attributes(
			$attributes,
			[ 'apd-categories-block', 'apd-no-results' ]
		);

		return sprintf(
			'<div %s><p>%s</p></div>',
			$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
}
