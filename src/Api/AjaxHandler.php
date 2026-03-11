<?php
/**
 * AJAX Handler class.
 *
 * Handles AJAX requests for listing filtering, deletion, and status updates.
 *
 * @package APD\Api
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Api;

use APD\Frontend\Dashboard\MyListings;
use APD\Search\FilterRegistry;
use APD\Search\FilterRenderer;
use APD\Search\SearchQuery;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AjaxHandler
 *
 * Registers and processes AJAX endpoints for the plugin.
 *
 * @since 1.0.0
 */
class AjaxHandler {

	/**
	 * Search query instance.
	 *
	 * @var SearchQuery
	 */
	private SearchQuery $search_query;

	/**
	 * Constructor.
	 *
	 * @param SearchQuery $search_query Search query instance.
	 */
	public function __construct( SearchQuery $search_query ) {
		$this->search_query = $search_query;
	}

	/**
	 * Initialize AJAX hooks.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init(): void {
		// Listing filter (public).
		add_action( 'wp_ajax_apd_filter_listings', [ $this, 'filter_listings' ] );
		add_action( 'wp_ajax_nopriv_apd_filter_listings', [ $this, 'filter_listings' ] );

		// Dashboard listing actions (authenticated only).
		add_action( 'wp_ajax_apd_delete_listing', [ $this, 'delete_listing' ] );
		add_action( 'wp_ajax_apd_update_listing_status', [ $this, 'update_listing_status' ] );
	}

	/**
	 * AJAX handler for filtering listings.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function filter_listings(): void {
		// Verify nonce.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$nonce = isset( $_REQUEST['_apd_nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_apd_nonce'] ) ) : '';
		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'apd_filter_listings' ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid security token.', 'all-purpose-directory' ) ], 403 );
		}

		/**
		 * Fires before AJAX filtering starts.
		 *
		 * @since 1.0.0
		 */
		do_action( 'apd_before_ajax_filter' );

		// Get paged parameter.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$paged = isset( $_REQUEST['paged'] ) ? absint( $_REQUEST['paged'] ) : 1;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$request_params = wp_unslash( $_REQUEST );

		$query_overrides = [
			'paged' => $paged,
		];

		// Allow the frontend to pass posts_per_page so AJAX matches the initial query
		// (e.g. shortcode count=12 vs WP default of 10).
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_REQUEST['posts_per_page'] ) ) {
			$per_page = absint( $_REQUEST['posts_per_page'] );
			if ( $per_page >= 1 && $per_page <= 100 ) {
				$query_overrides['posts_per_page'] = $per_page;
			}
		}

		// Run filtered query.
		$query = $this->search_query->get_filtered_listings(
			$query_overrides,
			$request_params
		);

		// Get current view mode.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$view = isset( $_REQUEST['apd_view'] ) ? sanitize_key( $_REQUEST['apd_view'] ) : 'grid';
		if ( ! in_array( $view, [ 'grid', 'list' ], true ) ) {
			$view = 'grid';
		}

		// Build HTML output.
		ob_start();
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();

				// Load the appropriate card template.
				$template_name = $view === 'list' ? 'listing-card-list' : 'listing-card';

				\apd_get_template_part(
					$template_name,
					null,
					[
						'listing_id'    => get_the_ID(),
						'current_view'  => $view,
						'show_image'    => (bool) \apd_get_setting( 'show_thumbnail', true ),
						'show_excerpt'  => (bool) \apd_get_setting( 'show_excerpt', true ),
						'show_category' => (bool) \apd_get_setting( 'show_category', true ),
						'show_rating'   => (bool) \apd_get_setting( 'show_rating', true ),
						'show_favorite' => (bool) \apd_get_setting( 'show_favorite', true ),
					]
				);
			}
			wp_reset_postdata();
		} else {
			$renderer = new FilterRenderer();
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $renderer->render_no_results();
		}
		$html = ob_get_clean();

		// Get active filters.
		$registry       = FilterRegistry::get_instance();
		$active_filters = $registry->get_active_filters( $request_params );
		$active_data    = [];

		foreach ( $active_filters as $name => $data ) {
			$active_data[ $name ] = [
				'label' => $data['filter']->getLabel(),
				'value' => $data['filter']->getDisplayValue( $data['value'] ),
			];
		}

		// Render pagination HTML.
		$pagination_html = \apd_render_pagination( $query );

		$response = [
			'html'            => $html,
			'pagination_html' => $pagination_html,
			'found_posts'     => $query->found_posts,
			'max_pages'       => $query->max_num_pages,
			'current_page'    => $paged,
			'active_filters'  => $active_data,
		];

		/**
		 * Filter the AJAX response data.
		 *
		 * @since 1.0.0
		 *
		 * @param array    $response The response data.
		 * @param WP_Query $query    The query object.
		 */
		$response = apply_filters( 'apd_ajax_filter_response', $response, $query );

		/**
		 * Fires after AJAX filtering completes.
		 *
		 * @since 1.0.0
		 */
		do_action( 'apd_after_ajax_filter' );

		wp_send_json_success( $response );
	}

	/**
	 * AJAX handler for deleting a listing.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function delete_listing(): void {
		// Verify nonce.
		if ( ! check_ajax_referer( MyListings::NONCE_ACTION, '_apd_nonce', false ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid security token.', 'all-purpose-directory' ) ], 403 );
		}

		// Check if user is logged in.
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'message' => __( 'You must be logged in.', 'all-purpose-directory' ) ], 401 );
		}

		// Get listing ID.
		$listing_id = isset( $_POST['listing_id'] ) ? absint( $_POST['listing_id'] ) : 0;
		if ( $listing_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Invalid listing ID.', 'all-purpose-directory' ) ], 400 );
		}

		// Get action type (trash or delete).
		$delete_type = isset( $_POST['delete_type'] ) && $_POST['delete_type'] === 'permanent' ? 'permanent' : 'trash';

		$my_listings = MyListings::get_instance();

		if ( $delete_type === 'permanent' ) {
			$result = $my_listings->delete_listing( $listing_id );
		} else {
			$result = $my_listings->trash_listing( $listing_id );
		}

		if ( $result ) {
			wp_send_json_success(
				[
					'message'    => $delete_type === 'permanent'
						? __( 'Listing permanently deleted.', 'all-purpose-directory' )
						: __( 'Listing moved to trash.', 'all-purpose-directory' ),
					'listing_id' => $listing_id,
				]
			);
		} else {
			wp_send_json_error(
				[
					'message' => __( 'Failed to delete listing. You may not have permission.', 'all-purpose-directory' ),
				],
				403
			);
		}
	}

	/**
	 * AJAX handler for updating listing status.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function update_listing_status(): void {
		// Verify nonce.
		if ( ! check_ajax_referer( MyListings::NONCE_ACTION, '_apd_nonce', false ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid security token.', 'all-purpose-directory' ) ], 403 );
		}

		// Check if user is logged in.
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'message' => __( 'You must be logged in.', 'all-purpose-directory' ) ], 401 );
		}

		// Get listing ID.
		$listing_id = isset( $_POST['listing_id'] ) ? absint( $_POST['listing_id'] ) : 0;
		if ( $listing_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Invalid listing ID.', 'all-purpose-directory' ) ], 400 );
		}

		// Get new status.
		$new_status     = isset( $_POST['status'] ) ? sanitize_key( $_POST['status'] ) : '';
		$valid_statuses = [ 'publish', 'draft', 'pending', 'expired' ];
		if ( ! in_array( $new_status, $valid_statuses, true ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid status.', 'all-purpose-directory' ) ], 400 );
		}

		$my_listings = MyListings::get_instance();
		$result      = $my_listings->update_listing_status( $listing_id, $new_status );

		if ( $result ) {
			$status_badge = $my_listings->get_status_badge( $new_status );

			wp_send_json_success(
				[
					'message'      => __( 'Listing status updated.', 'all-purpose-directory' ),
					'listing_id'   => $listing_id,
					'new_status'   => $new_status,
					'status_badge' => $status_badge,
				]
			);
		} else {
			wp_send_json_error(
				[
					'message' => __( 'Failed to update listing status. You may not have permission.', 'all-purpose-directory' ),
				],
				403
			);
		}
	}
}
