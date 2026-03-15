<?php
/**
 * Single Listing Template.
 *
 * This template displays a single listing's full details.
 *
 * This template can be overridden by copying it to:
 * yourtheme/all-purpose-directory/single-listing.php
 *
 * @package APD\Templates
 * @since   1.0.0
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

/**
 * Fires before the single listing content.
 *
 * @since 1.0.0
 */
do_action( 'apd_before_single_listing' );
?>

<div class="apd-single-wrapper">

	<?php
	/**
	 * Fires at the start of the single wrapper.
	 *
	 * @since 1.0.0
	 */
	do_action( 'apd_single_wrapper_start' );
	?>

	<?php
	while ( have_posts() ) :
		the_post();
		?>

		<?php
		$listing_id    = get_the_ID();
		$categories    = apd_get_listing_categories( $listing_id );
		$tags          = apd_get_listing_tags( $listing_id );
		$single_layout = apd_get_option( 'single_layout', 'sidebar' );

		/**
		 * Filter the single listing data.
		 *
		 * @since 1.0.0
		 *
		 * @param array $data       Single listing data.
		 * @param int   $listing_id The listing post ID.
		 */
		$listing_data = apply_filters(
			'apd_single_listing_data',
			[
				'listing_id'    => $listing_id,
				'title'         => get_the_title(),
				'permalink'     => get_permalink(),
				'content'       => get_the_content(),
				'excerpt'       => get_the_excerpt(),
				'categories'    => $categories,
				'tags'          => $tags,
				'has_thumbnail' => has_post_thumbnail(),
				'author_id'     => get_post_field( 'post_author', $listing_id ),
				'post_date'     => function_exists( 'apd_get_listing_date' ) ? apd_get_listing_date() : get_the_date(),
				'modified_date' => get_the_modified_date(),
			],
			$listing_id
		);
		?>

		<article id="listing-<?php echo esc_attr( $listing_id ); ?>" <?php post_class( 'apd-single-listing' ); ?>>

			<?php
			/**
			 * Fires at the start of the single listing article.
			 *
			 * @since 1.0.0
			 *
			 * @param int $listing_id The listing post ID.
			 */
			do_action( 'apd_single_listing_start', $listing_id );
			?>

			<header class="apd-single-listing__header">

				<?php if ( ! empty( $listing_data['categories'] ) ) : ?>
					<div class="apd-single-listing__categories">
						<?php foreach ( $listing_data['categories'] as $category ) : ?>
							<?php
							$icon  = apd_get_category_icon( $category );
							$color = apd_get_category_color( $category );
							?>
							<a href="<?php echo esc_url( get_term_link( $category ) ); ?>"
								class="apd-single-listing__category"
								<?php echo $color ? 'style="--apd-category-color:' . esc_attr( $color ) . '"' : ''; ?>>
								<?php if ( $icon ) : ?>
									<span class="dashicons <?php echo esc_attr( $icon ); ?>" aria-hidden="true"></span>
								<?php endif; ?>
								<?php echo esc_html( $category->name ); ?>
							</a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<h1 class="apd-single-listing__title"><?php echo esc_html( $listing_data['title'] ); ?></h1>

				<div class="apd-single-listing__meta">
					<span class="apd-single-listing__date">
						<span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span>
						<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
							<?php echo esc_html( $listing_data['post_date'] ); ?>
						</time>
					</span>

					<?php
					/**
					 * Fires inside the listing meta area.
					 *
					 * Use this to add view count, rating summary, etc.
					 *
					 * @since 1.0.0
					 *
					 * @param int $listing_id The listing post ID.
					 */
					do_action( 'apd_single_listing_meta', $listing_id );
					?>
				</div>

				<?php
				/**
				 * Fires after the listing header.
				 *
				 * @since 1.0.0
				 *
				 * @param int $listing_id The listing post ID.
				 */
				do_action( 'apd_single_listing_header', $listing_id );
				?>

			</header>

			<div class="apd-single-listing__layout apd-single-listing__layout--<?php echo esc_attr( $single_layout ); ?>">

				<div class="apd-single-listing__main">

					<?php if ( $listing_data['has_thumbnail'] ) : ?>
						<div class="apd-single-listing__featured-image">
							<?php the_post_thumbnail( 'large', [ 'class' => 'apd-single-listing__image' ] ); ?>

							<?php
							/**
							 * Fires inside the featured image area.
							 *
							 * Use this to add gallery, image overlay, etc.
							 *
							 * @since 1.0.0
							 *
							 * @param int $listing_id The listing post ID.
							 */
							do_action( 'apd_single_listing_image', $listing_id );
							?>
						</div>
					<?php endif; ?>

					<?php
					/**
					 * Fires before the listing content.
					 *
					 * @since 1.0.0
					 *
					 * @param int $listing_id The listing post ID.
					 */
					do_action( 'apd_single_listing_before_content', $listing_id );
					?>

					<div class="apd-single-listing__content">
						<?php
						// Output the content with WordPress formatting.
						the_content();
						?>
					</div>

					<?php
					/**
					 * Fires after the listing content.
					 *
					 * @since 1.0.0
					 *
					 * @param int $listing_id The listing post ID.
					 */
					do_action( 'apd_single_listing_after_content', $listing_id );
					?>

					<?php
					// Display custom fields.
					$custom_fields_html = apd_render_display_fields( $listing_id );
					if ( ! empty( $custom_fields_html ) ) :
						?>
						<div class="apd-single-listing__fields">
							<h2 class="apd-single-listing__section-title">
								<?php esc_html_e( 'Details', 'all-purpose-directory' ); ?>
							</h2>
							<?php
							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							echo $custom_fields_html;
							?>
						</div>
					<?php endif; ?>

					<?php
					/**
					 * Fires after custom fields.
					 *
					 * @since 1.0.0
					 *
					 * @param int $listing_id The listing post ID.
					 */
					do_action( 'apd_single_listing_after_fields', $listing_id );
					?>

					<?php if ( ! empty( $listing_data['tags'] ) ) : ?>
						<div class="apd-single-listing__tags">
							<h3 class="apd-single-listing__tags-title">
								<span class="dashicons dashicons-tag" aria-hidden="true"></span>
								<?php esc_html_e( 'Tags', 'all-purpose-directory' ); ?>
							</h3>
							<div class="apd-single-listing__tags-list">
								<?php foreach ( $listing_data['tags'] as $tag ) : ?>
									<a href="<?php echo esc_url( get_term_link( $tag ) ); ?>" class="apd-single-listing__tag">
										<?php echo esc_html( $tag->name ); ?>
									</a>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endif; ?>

					<?php
					/**
					 * Fires in the reviews section area.
					 *
					 * Use this to output the reviews section.
					 *
					 * @since 1.0.0
					 *
					 * @param int $listing_id The listing post ID.
					 */
					do_action( 'apd_single_listing_reviews', $listing_id );
					?>

				</div>

				<?php if ( 'sidebar' === $single_layout ) : ?>
				<aside class="apd-single-listing__sidebar">

					<?php
					/**
					 * Fires at the start of the sidebar.
					 *
					 * @since 1.0.0
					 *
					 * @param int $listing_id The listing post ID.
					 */
					do_action( 'apd_single_listing_sidebar_start', $listing_id );
					?>

					<?php
					// Author info section.
					$author_id   = $listing_data['author_id'];
					$author_name = get_the_author_meta( 'display_name', $author_id );
					?>
					<div class="apd-single-listing__author apd-sidebar-widget">
						<h3 class="apd-sidebar-widget__title">
							<?php esc_html_e( 'Listed by', 'all-purpose-directory' ); ?>
						</h3>
						<div class="apd-single-listing__author-card">
							<div class="apd-single-listing__author-avatar">
								<?php echo get_avatar( $author_id, 64, '', '', [ 'class' => 'apd-single-listing__avatar' ] ); ?>
							</div>
							<div class="apd-single-listing__author-info">
								<span class="apd-single-listing__author-name">
									<?php echo esc_html( $author_name ); ?>
								</span>
								<?php
								$author_listings_count = count_user_posts( $author_id, 'apd_listing' );
								?>
								<span class="apd-single-listing__author-listings">
									<?php
									printf(
										/* translators: %d: number of listings */
										esc_html( _n( '%d listing', '%d listings', $author_listings_count, 'all-purpose-directory' ) ),
										esc_html( number_format_i18n( $author_listings_count ) )
									);
									?>
								</span>
							</div>
						</div>

						<?php
						/**
						 * Fires inside the author widget.
						 *
						 * Use this to add contact buttons, social links, etc.
						 *
						 * @since 1.0.0
						 *
						 * @param int $author_id  The author user ID.
						 * @param int $listing_id The listing post ID.
						 */
						do_action( 'apd_single_listing_author', $author_id, $listing_id );
						?>
					</div>

					<?php
					/**
					 * Fires in the contact form section.
					 *
					 * Use this to output a contact form.
					 *
					 * @since 1.0.0
					 *
					 * @param int $listing_id The listing post ID.
					 */
					do_action( 'apd_single_listing_contact_form', $listing_id );
					?>

					<?php
					/**
					 * Fires at the end of the sidebar.
					 *
					 * @since 1.0.0
					 *
					 * @param int $listing_id The listing post ID.
					 */
					do_action( 'apd_single_listing_sidebar_end', $listing_id );
					?>

				</aside>
				<?php endif; ?>

			</div>

			<?php
			/**
			 * Fires at the end of the single listing article.
			 *
			 * @since 1.0.0
			 *
			 * @param int $listing_id The listing post ID.
			 */
			do_action( 'apd_single_listing_end', $listing_id );
			?>

		</article>

		<?php
		// Related listings section.
		$related_listings = apd_get_related_listings( $listing_id );

		if ( ! empty( $related_listings ) ) :
			?>
			<section class="apd-related-listings">
				<h2 class="apd-related-listings__title">
					<?php esc_html_e( 'Related Listings', 'all-purpose-directory' ); ?>
				</h2>

				<?php
				/**
				 * Fires before related listings.
				 *
				 * @since 1.0.0
				 *
				 * @param int $listing_id The current listing post ID.
				 */
				do_action( 'apd_before_related_listings', $listing_id );
				?>

				<div class="apd-related-listings__grid">
					<?php foreach ( $related_listings as $related_listing ) : ?>
						<?php
						apd_get_template_part(
							'listing-card',
							null,
							[
								'listing_id'   => $related_listing->ID,
								'current_view' => 'grid',
							]
						);
						?>
					<?php endforeach; ?>
				</div>

				<?php
				/**
				 * Fires after related listings.
				 *
				 * @since 1.0.0
				 *
				 * @param int $listing_id The current listing post ID.
				 */
				do_action( 'apd_after_related_listings', $listing_id );
				?>
			</section>
		<?php endif; ?>

	<?php endwhile; ?>

	<?php
	/**
	 * Fires at the end of the single wrapper.
	 *
	 * @since 1.0.0
	 */
	do_action( 'apd_single_wrapper_end' );
	?>

</div>

<?php
/**
 * Fires after the single listing content.
 *
 * @since 1.0.0
 */
do_action( 'apd_after_single_listing' );

get_footer();
