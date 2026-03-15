<?php
/**
 * Listing Row Template.
 *
 * Single listing row for the My Listings table.
 *
 * This template can be overridden by copying it to:
 * yourtheme/all-purpose-directory/dashboard/listing-row.php
 *
 * @package APD\Templates
 * @since   1.0.0
 *
 * @var APD\Frontend\Dashboard\MyListings $my_listings The MyListings instance.
 * @var WP_Post                           $post        The listing post object.
 * @var array                             $config      Configuration options.
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$listing_id = $post->ID;
$status     = $post->post_status;
$views      = apd_get_listing_views( $listing_id );
$actions    = $my_listings->get_listing_actions( $post );
$edit_url   = $my_listings->get_edit_url( $listing_id );
?>

<tr class="apd-listing-row" data-listing-id="<?php echo esc_attr( $listing_id ); ?>">
	<?php if ( $config['show_thumbnail'] ) : ?>
		<td class="apd-listing-row__image">
			<?php if ( has_post_thumbnail( $listing_id ) ) : ?>
				<a href="<?php echo esc_url( $edit_url ?: get_permalink( $listing_id ) ); ?>" class="apd-listing-row__thumbnail-link">
					<?php
					echo get_the_post_thumbnail(
						$listing_id,
						'thumbnail',
						[
							'class'    => 'apd-listing-row__thumbnail',
							'loading'  => 'lazy',
							'decoding' => 'async',
						]
					);
					?>
				</a>
			<?php else : ?>
				<div class="apd-listing-row__thumbnail-placeholder">
					<span class="dashicons dashicons-format-image" aria-hidden="true"></span>
				</div>
			<?php endif; ?>
		</td>
	<?php endif; ?>

	<td class="apd-listing-row__title">
		<div class="apd-listing-row__title-content">
			<?php if ( $edit_url ) : ?>
				<a href="<?php echo esc_url( $edit_url ); ?>" class="apd-listing-row__title-link">
					<?php echo esc_html( get_the_title( $listing_id ) ); ?>
				</a>
			<?php else : ?>
				<span class="apd-listing-row__title-text">
					<?php echo esc_html( get_the_title( $listing_id ) ); ?>
				</span>
			<?php endif; ?>

			<?php
			// Show categories below title.
			$categories = apd_get_listing_categories( $listing_id );
			if ( ! empty( $categories ) ) :
				?>
				<div class="apd-listing-row__categories">
					<?php
					$cat_names = array_map( fn( $cat ) => $cat->name, array_slice( $categories, 0, 2 ) );
					echo esc_html( implode( ', ', $cat_names ) );
					if ( count( $categories ) > 2 ) {
						/* translators: %d: number of additional categories */
						printf( esc_html__( ' +%d more', 'all-purpose-directory' ), count( $categories ) - 2 );
					}
					?>
				</div>
			<?php endif; ?>
		</div>

		<!-- Mobile-only actions -->
		<div class="apd-listing-row__mobile-actions">
			<?php foreach ( $actions as $action_key => $action ) : ?>
				<a href="<?php echo esc_url( $action['url'] ); ?>"
					class="apd-listing-action <?php echo esc_attr( $action['class'] ); ?>"
					<?php if ( ! empty( $action['confirm'] ) ) : ?>
						data-confirm="<?php echo esc_attr( $action['confirm'] ); ?>"
					<?php endif; ?>>
					<?php echo esc_html( $action['label'] ); ?>
				</a>
			<?php endforeach; ?>
		</div>
	</td>

	<td class="apd-listing-row__status">
		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Badge is already escaped in the method.
		echo $my_listings->get_status_badge( $status );
		?>
	</td>

	<?php if ( $config['show_views'] ) : ?>
		<td class="apd-listing-row__views">
			<span class="apd-listing-row__views-count">
				<?php echo esc_html( number_format_i18n( $views ) ); ?>
			</span>
			<span class="apd-listing-row__views-label screen-reader-text">
				<?php esc_html_e( 'views', 'all-purpose-directory' ); ?>
			</span>
		</td>
	<?php endif; ?>

	<?php if ( $config['show_inquiries'] ) : ?>
		<?php $inquiry_count = $my_listings->get_listing_inquiry_count( $listing_id ); ?>
		<td class="apd-listing-row__inquiries">
			<span class="apd-listing-row__inquiries-count">
				<?php echo esc_html( number_format_i18n( $inquiry_count ) ); ?>
			</span>
			<span class="apd-listing-row__inquiries-label screen-reader-text">
				<?php esc_html_e( 'inquiries', 'all-purpose-directory' ); ?>
			</span>
		</td>
	<?php endif; ?>

	<?php if ( $config['show_date'] ) : ?>
		<td class="apd-listing-row__date">
			<time datetime="<?php echo esc_attr( get_the_date( 'c', $listing_id ) ); ?>">
				<?php echo esc_html( function_exists( 'apd_get_listing_date' ) ? apd_get_listing_date( $listing_id ) : get_the_date( '', $listing_id ) ); ?>
			</time>
		</td>
	<?php endif; ?>

	<td class="apd-listing-row__actions">
		<div class="apd-listing-row__actions-list">
			<?php foreach ( $actions as $action_key => $action ) : ?>
				<a href="<?php echo esc_url( $action['url'] ); ?>"
					class="apd-listing-action <?php echo esc_attr( $action['class'] ); ?>"
					<?php if ( ! empty( $action['confirm'] ) ) : ?>
						data-confirm="<?php echo esc_attr( $action['confirm'] ); ?>"
					<?php endif; ?>
					title="<?php echo esc_attr( $action['label'] ); ?>">
					<?php echo esc_html( $action['label'] ); ?>
				</a>
			<?php endforeach; ?>
		</div>
	</td>
</tr>
