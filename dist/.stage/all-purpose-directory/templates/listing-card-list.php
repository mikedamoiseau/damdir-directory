<?php
/**
 * Listing Card Template (List View).
 *
 * This template displays a single listing in list/horizontal format.
 *
 * This template can be overridden by copying it to:
 * yourtheme/all-purpose-directory/listing-card-list.php
 *
 * @package APD\Templates
 * @since   1.0.0
 *
 * @var int    $listing_id   The listing post ID.
 * @var string $current_view The current view mode (grid/list).
 * @var array  $args         All template arguments.
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Ensure we have a listing ID.
$listing_id = $listing_id ?? get_the_ID();

if ( ! $listing_id ) {
	return;
}

// Get listing data.
$title      = get_the_title( $listing_id );
$permalink  = get_permalink( $listing_id );
$excerpt    = get_the_excerpt( $listing_id );
$categories = apd_get_listing_categories( $listing_id );
$tags       = apd_get_listing_tags( $listing_id );

// Check for featured image.
$has_thumbnail = has_post_thumbnail( $listing_id );
$thumbnail_url = $has_thumbnail ? get_the_post_thumbnail_url( $listing_id, 'medium' ) : '';

// Get post date.
$post_date = function_exists( 'apd_get_listing_date' )
	? apd_get_listing_date( $listing_id )
	: get_the_date( get_option( 'date_format' ), $listing_id );

/**
 * Filter the listing card data.
 *
 * @since 1.0.0
 *
 * @param array $data       Listing card data.
 * @param int   $listing_id The listing post ID.
 */
$card_data = apply_filters(
	'apd_listing_card_data',
	[
		'listing_id'    => $listing_id,
		'title'         => $title,
		'permalink'     => $permalink,
		'excerpt'       => $excerpt,
		'categories'    => $categories,
		'tags'          => $tags,
		'has_thumbnail' => $has_thumbnail,
		'thumbnail_url' => $thumbnail_url,
		'post_date'     => $post_date,
	],
	$listing_id
);

// Build card classes.
$card_classes = [
	'apd-listing-card',
	'apd-listing-card--list',
];

if ( ! $card_data['has_thumbnail'] ) {
	$card_classes[] = 'apd-listing-card--no-image';
}

/**
 * Filter the listing card CSS classes.
 *
 * @since 1.0.0
 *
 * @param array $classes    CSS classes.
 * @param int   $listing_id The listing post ID.
 */
$card_classes = apply_filters( 'apd_listing_card_classes', $card_classes, $listing_id );
?>

<article <?php post_class( $card_classes, $listing_id ); ?>>

	<?php
	/**
	 * Fires at the start of the listing card.
	 *
	 * @since 1.0.0
	 *
	 * @param int $listing_id The listing post ID.
	 */
	do_action( 'apd_listing_card_start', $listing_id );
	?>

	<?php if ( ( $show_image ?? true ) && $card_data['has_thumbnail'] ) : ?>
		<div class="apd-listing-card__image">
			<a href="<?php echo esc_url( $card_data['permalink'] ); ?>" aria-hidden="true" tabindex="-1">
				<?php
				echo get_the_post_thumbnail(
					$listing_id,
					'medium',
					[
						'loading'  => 'lazy',
						'decoding' => 'async',
					]
				);
				?>
			</a>

			<?php
			/**
			 * Fires inside the image area.
			 *
			 * @since 1.0.0
			 *
			 * @param int $listing_id The listing post ID.
			 */
			do_action( 'apd_listing_card_image', $listing_id );
			?>
		</div>
	<?php endif; ?>

	<div class="apd-listing-card__content">
		<div class="apd-listing-card__body">

			<?php if ( ( $show_category ?? true ) && ! empty( $card_data['categories'] ) ) : ?>
				<div class="apd-listing-card__categories">
					<?php foreach ( $card_data['categories'] as $category ) : ?>
						<a href="<?php echo esc_url( get_term_link( $category ) ); ?>" class="apd-listing-card__category">
							<?php
							$icon = apd_get_category_icon( $category );
							if ( $icon ) :
								?>
								<span class="dashicons <?php echo esc_attr( $icon ); ?>" aria-hidden="true"></span>
							<?php endif; ?>
							<?php echo esc_html( $category->name ); ?>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<h2 class="apd-listing-card__title">
				<a href="<?php echo esc_url( $card_data['permalink'] ); ?>">
					<?php echo esc_html( $card_data['title'] ); ?>
				</a>
			</h2>

			<?php
			// Display star rating if function exists and setting is enabled.
			if ( ( $show_rating ?? true ) && function_exists( 'apd_get_listing_rating_count' ) ) :
				$rating_count = apd_get_listing_rating_count( $listing_id );

				if ( $rating_count > 0 ) :
					?>
					<div class="apd-listing-card__rating">
						<?php
						apd_render_listing_star_rating(
							$listing_id,
							[
								'size'         => 'small',
								'show_count'   => true,
								'show_average' => true,
							]
						);
						?>
					</div>
					<?php
				endif;
			endif;
			?>

			<?php if ( ( $show_excerpt ?? true ) && ! empty( $card_data['excerpt'] ) ) : ?>
				<div class="apd-listing-card__excerpt">
					<?php echo esc_html( wp_trim_words( $card_data['excerpt'], 30, '&hellip;' ) ); ?>
				</div>
			<?php endif; ?>

			<?php
			/**
			 * Fires inside the card body, after the excerpt.
			 *
			 * Use this to add custom fields, ratings, etc.
			 *
			 * @since 1.0.0
			 *
			 * @param int $listing_id The listing post ID.
			 */
			do_action( 'apd_listing_card_body', $listing_id );
			?>

			<?php if ( ! empty( $card_data['tags'] ) ) : ?>
				<div class="apd-listing-card__tags">
					<?php foreach ( array_slice( $card_data['tags'], 0, 5 ) as $tag ) : ?>
						<a href="<?php echo esc_url( get_term_link( $tag ) ); ?>" class="apd-listing-card__tag">
							<?php echo esc_html( $tag->name ); ?>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

		</div>

		<div class="apd-listing-card__sidebar">
			<div class="apd-listing-card__meta">
				<span class="apd-listing-card__date">
					<span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span>
					<time datetime="<?php echo esc_attr( get_the_date( 'c', $listing_id ) ); ?>">
						<?php echo esc_html( $card_data['post_date'] ); ?>
					</time>
				</span>
			</div>

			<?php
			/**
			 * Fires inside the card sidebar.
			 *
			 * Use this to add favorites button, view count, price, etc.
			 *
			 * @since 1.0.0
			 *
			 * @param int $listing_id The listing post ID.
			 */
			do_action( 'apd_listing_card_footer', $listing_id );
			?>

			<a href="<?php echo esc_url( $card_data['permalink'] ); ?>" class="apd-listing-card__link">
				<?php esc_html_e( 'View Details', 'all-purpose-directory' ); ?>
				<span class="screen-reader-text"><?php echo esc_html( $card_data['title'] ); ?></span>
			</a>
		</div>
	</div>

	<?php
	/**
	 * Fires at the end of the listing card.
	 *
	 * @since 1.0.0
	 *
	 * @param int $listing_id The listing post ID.
	 */
	do_action( 'apd_listing_card_end', $listing_id );
	?>

</article>
