<?php
/**
 * My Listings Template.
 *
 * Main template for the My Listings dashboard tab.
 *
 * This template can be overridden by copying it to:
 * yourtheme/all-purpose-directory/dashboard/my-listings.php
 *
 * @package APD\Templates
 * @since   1.0.0
 *
 * @var APD\Frontend\Dashboard\MyListings $my_listings The MyListings instance.
 * @var WP_Query                          $listings    Query result with user's listings.
 * @var string                            $status      Current status filter.
 * @var string                            $orderby     Current orderby filter.
 * @var string                            $order       Current order direction.
 * @var int                               $paged       Current page number.
 * @var int                               $total       Total number of listings.
 * @var int                               $max_pages   Total number of pages.
 * @var int                               $user_id     Current user ID.
 * @var array                             $config      Configuration options.
 * @var string                            $nonce       Security nonce.
 * @var array                             $statuses    Available status options.
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get any pending message.
$message = $my_listings->get_message();
?>

<div class="apd-my-listings" data-nonce="<?php echo esc_attr( $nonce ); ?>">

	<?php
	/**
	 * Fires at the start of the My Listings content.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Template arguments.
	 */
	do_action( 'apd_my_listings_start', $args );
	?>

	<?php if ( $message ) : ?>
		<div class="apd-notice apd-notice--<?php echo esc_attr( $message['type'] ); ?>" role="alert">
			<p><?php echo esc_html( $message['message'] ); ?></p>
		</div>
	<?php endif; ?>

	<div class="apd-my-listings__header">
		<div class="apd-my-listings__title-row">
			<h2 class="apd-my-listings__title">
				<?php esc_html_e( 'My Listings', 'all-purpose-directory' ); ?>
				<span class="apd-my-listings__count">(<?php echo esc_html( number_format_i18n( $total ) ); ?>)</span>
			</h2>
		</div>

		<div class="apd-my-listings__filters">
			<!-- Status Filter Tabs -->
			<nav class="apd-my-listings__status-tabs" aria-label="<?php esc_attr_e( 'Filter by status', 'all-purpose-directory' ); ?>">
				<ul class="apd-status-tabs">
					<?php foreach ( $statuses as $status_key => $status_data ) : ?>
						<?php
						$is_active = ( $status === $status_key );
						$tab_url   = add_query_arg( 'status', $status_key );
						$tab_url   = remove_query_arg( 'paged', $tab_url );
						?>
						<li class="apd-status-tabs__item">
							<a href="<?php echo esc_url( $tab_url ); ?>"
								class="apd-status-tabs__link <?php echo $is_active ? 'apd-status-tabs__link--active' : ''; ?>"
								<?php if ( $is_active ) : ?>
									aria-current="page"
								<?php endif; ?>>
								<?php echo esc_html( $status_data['label'] ); ?>
								<?php if ( $status_data['count'] > 0 ) : ?>
									<span class="apd-status-tabs__count"><?php echo esc_html( number_format_i18n( $status_data['count'] ) ); ?></span>
								<?php endif; ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</nav>

			<!-- Sort Options -->
			<div class="apd-my-listings__sort">
				<label for="apd-sort-select" class="apd-my-listings__sort-label">
					<?php esc_html_e( 'Sort by:', 'all-purpose-directory' ); ?>
				</label>
				<select id="apd-sort-select" class="apd-my-listings__sort-select">
					<?php
					$orderby_options = $my_listings->get_orderby_options();
					foreach ( $orderby_options as $orderby_key => $orderby_label ) :
						$sort_url    = add_query_arg( 'orderby', $orderby_key );
						$sort_url    = remove_query_arg( 'paged', $sort_url );
						$is_selected = ( $orderby === $orderby_key );
						?>
						<option value="<?php echo esc_url( $sort_url ); ?>" <?php selected( $is_selected ); ?>>
							<?php echo esc_html( $orderby_label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>
	</div>

	<?php if ( $listings->have_posts() ) : ?>

		<div class="apd-my-listings__table-wrapper">
			<table class="apd-my-listings__table">
				<thead>
					<tr>
						<?php if ( $config['show_thumbnail'] ) : ?>
							<th class="apd-my-listings__col-image" scope="col">
								<span class="screen-reader-text"><?php esc_html_e( 'Image', 'all-purpose-directory' ); ?></span>
							</th>
						<?php endif; ?>
						<th class="apd-my-listings__col-title" scope="col"><?php esc_html_e( 'Listing', 'all-purpose-directory' ); ?></th>
						<th class="apd-my-listings__col-status" scope="col"><?php esc_html_e( 'Status', 'all-purpose-directory' ); ?></th>
						<?php if ( $config['show_views'] ) : ?>
							<th class="apd-my-listings__col-views" scope="col"><?php esc_html_e( 'Views', 'all-purpose-directory' ); ?></th>
						<?php endif; ?>
						<?php if ( $config['show_inquiries'] ) : ?>
							<th class="apd-my-listings__col-inquiries" scope="col"><?php esc_html_e( 'Inquiries', 'all-purpose-directory' ); ?></th>
						<?php endif; ?>
						<?php if ( $config['show_date'] ) : ?>
							<th class="apd-my-listings__col-date" scope="col"><?php esc_html_e( 'Date', 'all-purpose-directory' ); ?></th>
						<?php endif; ?>
						<th class="apd-my-listings__col-actions" scope="col"><?php esc_html_e( 'Actions', 'all-purpose-directory' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					while ( $listings->have_posts() ) :
						$listings->the_post();
						apd_get_template(
							'dashboard/listing-row.php',
							[
								'my_listings' => $my_listings,
								'post'        => get_post(),
								'config'      => $config,
							]
						);
					endwhile;
					wp_reset_postdata();
					?>
				</tbody>
			</table>
		</div>

		<?php if ( $max_pages > 1 ) : ?>
			<nav class="apd-my-listings__pagination" aria-label="<?php esc_attr_e( 'Listings pagination', 'all-purpose-directory' ); ?>">
				<?php
				$pagination = paginate_links(
					[
						'base'      => add_query_arg( 'paged', '%#%' ),
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

		<?php apd_get_template( 'dashboard/my-listings-empty.php', [ 'status' => $status ] ); ?>

	<?php endif; ?>

	<?php
	/**
	 * Fires at the end of the My Listings content.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Template arguments.
	 */
	do_action( 'apd_my_listings_end', $args );
	?>

</div>
