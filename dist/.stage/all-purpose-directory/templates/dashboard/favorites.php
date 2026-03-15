<?php
/**
 * Favorites Template.
 *
 * Main template for the Favorites dashboard tab.
 *
 * This template can be overridden by copying it to:
 * yourtheme/all-purpose-directory/dashboard/favorites.php
 *
 * @package APD\Templates
 * @since   1.0.0
 *
 * @var APD\Frontend\Dashboard\FavoritesPage $favorites_page The FavoritesPage instance.
 * @var WP_Query                             $favorites      Query result with favorited listings.
 * @var string                               $view_mode      Current view mode (grid/list).
 * @var int                                  $paged          Current page number.
 * @var int                                  $total          Total number of favorites.
 * @var int                                  $max_pages      Total number of pages.
 * @var int                                  $user_id        Current user ID.
 * @var array                                $config         Configuration options.
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="apd-favorites" data-view="<?php echo esc_attr( $view_mode ); ?>">

	<?php
	/**
	 * Fires at the start of the Favorites content.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Template arguments.
	 */
	do_action( 'apd_favorites_start', $args );
	?>

	<div class="apd-favorites__header">
		<div class="apd-favorites__title-row">
			<h2 class="apd-favorites__title">
				<?php esc_html_e( 'Your Favorites', 'all-purpose-directory' ); ?>
				<?php if ( $total > 0 ) : ?>
					<span class="apd-favorites__count">(<?php echo esc_html( number_format_i18n( $total ) ); ?>)</span>
				<?php endif; ?>
			</h2>

			<?php if ( $total > 0 && $config['show_view_toggle'] ) : ?>
				<div class="apd-favorites__view-toggle" role="group" aria-label="<?php esc_attr_e( 'View mode', 'all-purpose-directory' ); ?>">
					<a href="<?php echo esc_url( $favorites_page->get_view_mode_url( 'grid' ) ); ?>"
						class="apd-view-toggle__button <?php echo $view_mode === 'grid' ? 'apd-view-toggle__button--active' : ''; ?>"
						aria-label="<?php esc_attr_e( 'Grid view', 'all-purpose-directory' ); ?>"
						<?php echo $view_mode === 'grid' ? 'aria-current="true"' : ''; ?>>
						<span class="dashicons dashicons-grid-view" aria-hidden="true"></span>
					</a>
					<a href="<?php echo esc_url( $favorites_page->get_view_mode_url( 'list' ) ); ?>"
						class="apd-view-toggle__button <?php echo $view_mode === 'list' ? 'apd-view-toggle__button--active' : ''; ?>"
						aria-label="<?php esc_attr_e( 'List view', 'all-purpose-directory' ); ?>"
						<?php echo $view_mode === 'list' ? 'aria-current="true"' : ''; ?>>
						<span class="dashicons dashicons-list-view" aria-hidden="true"></span>
					</a>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<?php if ( $favorites->have_posts() ) : ?>

		<div class="apd-favorites__listings apd-listings apd-listings--<?php echo esc_attr( $view_mode ); ?> <?php echo $view_mode === 'grid' ? 'apd-listings--columns-' . esc_attr( $config['columns'] ) : ''; ?>">
			<?php
			while ( $favorites->have_posts() ) :
				$favorites->the_post();
				$listing_id = get_the_ID();
				$template   = $view_mode === 'grid' ? 'listing-card' : 'listing-card-list';

				apd_get_template_part(
					$template,
					null,
					[
						'listing_id'    => $listing_id,
						'current_view'  => $view_mode,
						'show_favorite' => true,
					]
				);
			endwhile;
			wp_reset_postdata();
			?>
		</div>

		<?php if ( $max_pages > 1 ) : ?>
			<nav class="apd-favorites__pagination" aria-label="<?php esc_attr_e( 'Favorites pagination', 'all-purpose-directory' ); ?>">
				<?php
				$pagination = paginate_links(
					[
						'base'      => add_query_arg( 'fav_page', '%#%' ),
						'format'    => '',
						'current'   => $paged,
						'total'     => $max_pages,
						'prev_text' => sprintf(
							'<span class="screen-reader-text">%s</span><span aria-hidden="true">&laquo;</span>',
							esc_html__( 'Previous page', 'all-purpose-directory' )
						),
						'next_text' => sprintf(
							'<span class="screen-reader-text">%s</span><span aria-hidden="true">&raquo;</span>',
							esc_html__( 'Next page', 'all-purpose-directory' )
						),
					]
				);

				if ( $pagination ) {
					echo '<div class="apd-pagination__links">' . wp_kses_post( $pagination ) . '</div>';
				}
				?>
			</nav>
		<?php endif; ?>

	<?php else : ?>

		<?php apd_get_template( 'dashboard/favorites-empty.php', [ 'favorites_page' => $favorites_page ] ); ?>

	<?php endif; ?>

	<?php
	/**
	 * Fires at the end of the Favorites content.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Template arguments.
	 */
	do_action( 'apd_favorites_end', $args );
	?>

</div>
