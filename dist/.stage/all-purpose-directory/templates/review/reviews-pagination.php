<?php
/**
 * Reviews Pagination Template.
 *
 * Template for rendering pagination controls for the reviews list.
 *
 * This template can be overridden by copying it to:
 * yourtheme/all-purpose-directory/review/reviews-pagination.php
 *
 * @package APD\Templates
 * @since   1.0.0
 *
 * @var int    $listing_id   Listing post ID.
 * @var int    $current_page Current page number.
 * @var int    $total_pages  Total number of pages.
 * @var string $base_url     Base URL for pagination links.
 * @var string $page_param   URL parameter name for page number.
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Don't render if only one page.
if ( $total_pages <= 1 ) {
	return;
}

$display = \APD\Review\ReviewDisplay::get_instance();

// Calculate page range to display.
$range      = 2; // Pages to show on each side of current.
$start_page = max( 1, $current_page - $range );
$end_page   = min( $total_pages, $current_page + $range );

// Ensure we always show at least 5 pages if available.
if ( $end_page - $start_page < 4 ) {
	if ( $start_page === 1 ) {
		$end_page = min( $total_pages, 5 );
	} elseif ( $end_page === $total_pages ) {
		$start_page = max( 1, $total_pages - 4 );
	}
}
?>

<nav class="apd-reviews-pagination" aria-label="<?php esc_attr_e( 'Reviews navigation', 'all-purpose-directory' ); ?>">

	<ul class="apd-reviews-pagination__list">

		<?php // Previous link. ?>
		<li class="apd-reviews-pagination__item apd-reviews-pagination__item--prev">
			<?php if ( $current_page > 1 ) : ?>
				<a href="<?php echo esc_url( $display->build_pagination_url( $base_url, $current_page - 1 ) . '#reviews' ); ?>"
					class="apd-reviews-pagination__link"
					aria-label="<?php esc_attr_e( 'Previous page of reviews', 'all-purpose-directory' ); ?>">
					<span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span>
					<span class="apd-reviews-pagination__prev-text"><?php esc_html_e( 'Previous', 'all-purpose-directory' ); ?></span>
				</a>
			<?php else : ?>
				<span class="apd-reviews-pagination__link apd-reviews-pagination__link--disabled" aria-hidden="true">
					<span class="dashicons dashicons-arrow-left-alt2"></span>
					<span class="apd-reviews-pagination__prev-text"><?php esc_html_e( 'Previous', 'all-purpose-directory' ); ?></span>
				</span>
			<?php endif; ?>
		</li>

		<?php // First page + ellipsis if needed. ?>
		<?php if ( $start_page > 1 ) : ?>
			<li class="apd-reviews-pagination__item">
				<a href="<?php echo esc_url( $display->build_pagination_url( $base_url, 1 ) . '#reviews' ); ?>"
					class="apd-reviews-pagination__link apd-reviews-pagination__link--number"
					aria-label="<?php esc_attr_e( 'Page 1', 'all-purpose-directory' ); ?>">
					1
				</a>
			</li>
			<?php if ( $start_page > 2 ) : ?>
				<li class="apd-reviews-pagination__item apd-reviews-pagination__item--ellipsis" aria-hidden="true">
					<span class="apd-reviews-pagination__ellipsis">&hellip;</span>
				</li>
			<?php endif; ?>
		<?php endif; ?>

		<?php // Page numbers. ?>
		<?php for ( $page = $start_page; $page <= $end_page; $page++ ) : ?>
			<li class="apd-reviews-pagination__item">
				<?php if ( $page === $current_page ) : ?>
					<span class="apd-reviews-pagination__link apd-reviews-pagination__link--number apd-reviews-pagination__link--current"
							aria-current="page">
						<?php echo esc_html( $page ); ?>
					</span>
				<?php else : ?>
					<a href="<?php echo esc_url( $display->build_pagination_url( $base_url, $page ) . '#reviews' ); ?>"
						class="apd-reviews-pagination__link apd-reviews-pagination__link--number"
						aria-label="
						<?php
							/* translators: %d: Page number */
							echo esc_attr( sprintf( __( 'Page %d', 'all-purpose-directory' ), $page ) );
						?>
						">
						<?php echo esc_html( $page ); ?>
					</a>
				<?php endif; ?>
			</li>
		<?php endfor; ?>

		<?php // Last page + ellipsis if needed. ?>
		<?php if ( $end_page < $total_pages ) : ?>
			<?php if ( $end_page < $total_pages - 1 ) : ?>
				<li class="apd-reviews-pagination__item apd-reviews-pagination__item--ellipsis" aria-hidden="true">
					<span class="apd-reviews-pagination__ellipsis">&hellip;</span>
				</li>
			<?php endif; ?>
			<li class="apd-reviews-pagination__item">
				<a href="<?php echo esc_url( $display->build_pagination_url( $base_url, $total_pages ) . '#reviews' ); ?>"
					class="apd-reviews-pagination__link apd-reviews-pagination__link--number"
					aria-label="
					<?php
						/* translators: %d: Page number */
						echo esc_attr( sprintf( __( 'Page %d', 'all-purpose-directory' ), $total_pages ) );
					?>
					">
					<?php echo esc_html( $total_pages ); ?>
				</a>
			</li>
		<?php endif; ?>

		<?php // Next link. ?>
		<li class="apd-reviews-pagination__item apd-reviews-pagination__item--next">
			<?php if ( $current_page < $total_pages ) : ?>
				<a href="<?php echo esc_url( $display->build_pagination_url( $base_url, $current_page + 1 ) . '#reviews' ); ?>"
					class="apd-reviews-pagination__link"
					aria-label="<?php esc_attr_e( 'Next page of reviews', 'all-purpose-directory' ); ?>">
					<span class="apd-reviews-pagination__next-text"><?php esc_html_e( 'Next', 'all-purpose-directory' ); ?></span>
					<span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
				</a>
			<?php else : ?>
				<span class="apd-reviews-pagination__link apd-reviews-pagination__link--disabled" aria-hidden="true">
					<span class="apd-reviews-pagination__next-text"><?php esc_html_e( 'Next', 'all-purpose-directory' ); ?></span>
					<span class="dashicons dashicons-arrow-right-alt2"></span>
				</span>
			<?php endif; ?>
		</li>

	</ul>

	<div class="apd-reviews-pagination__info">
		<?php
		printf(
			/* translators: 1: current page, 2: total pages */
			esc_html__( 'Page %1$d of %2$d', 'all-purpose-directory' ),
			esc_html( number_format_i18n( $current_page ) ),
			esc_html( number_format_i18n( $total_pages ) )
		);
		?>
	</div>

</nav>
