<?php
/**
 * Admin columns for the listing post type.
 *
 * @package APD\Listing
 */

declare(strict_types=1);

namespace APD\Listing;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AdminColumns
 *
 * Handles custom admin columns for the listing post type list table.
 *
 * @since 1.0.0
 */
final class AdminColumns {

	/**
	 * Meta key for views count.
	 */
	public const META_VIEWS = '_apd_views_count';

	/**
	 * Initialize the admin columns.
	 *
	 * @return void
	 */
	public function init(): void {
		// Only run in admin context.
		if ( ! is_admin() ) {
			return;
		}

		add_filter( 'manage_' . PostType::POST_TYPE . '_posts_columns', [ $this, 'add_columns' ] );
		add_action( 'manage_' . PostType::POST_TYPE . '_posts_custom_column', [ $this, 'render_column' ], 10, 2 );
		add_filter( 'manage_edit-' . PostType::POST_TYPE . '_sortable_columns', [ $this, 'sortable_columns' ] );
		add_action( 'pre_get_posts', [ $this, 'sort_columns' ] );

		// Admin filters.
		add_action( 'restrict_manage_posts', [ $this, 'render_filters' ] );
		add_action( 'pre_get_posts', [ $this, 'apply_filters' ] );
	}

	/**
	 * Add custom columns to the listing admin list.
	 *
	 * @param array<string, string> $columns Existing columns.
	 * @return array<string, string> Modified columns.
	 */
	public function add_columns( array $columns ): array {
		$new_columns = [];

		// Checkbox column first.
		if ( isset( $columns['cb'] ) ) {
			$new_columns['cb'] = $columns['cb'];
		}

		// Featured image thumbnail.
		$new_columns['thumbnail'] = __( 'Image', 'all-purpose-directory' );

		// Title.
		if ( isset( $columns['title'] ) ) {
			$new_columns['title'] = $columns['title'];
		}

		// Category.
		$new_columns['apd_category'] = __( 'Category', 'all-purpose-directory' );

		// Listing type (only when 2+ types exist).
		if ( $this->has_multiple_listing_types() ) {
			$new_columns['listing_type'] = __( 'Type', 'all-purpose-directory' );
		}

		// Status badge.
		$new_columns['listing_status'] = __( 'Status', 'all-purpose-directory' );

		// Views count.
		$new_columns['views_count'] = __( 'Views', 'all-purpose-directory' );

		// Author (if exists).
		if ( isset( $columns['author'] ) ) {
			$new_columns['author'] = $columns['author'];
		}

		// Date.
		if ( isset( $columns['date'] ) ) {
			$new_columns['date'] = $columns['date'];
		}

		return $new_columns;
	}

	/**
	 * Render content for custom columns.
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public function render_column( string $column, int $post_id ): void {
		switch ( $column ) {
			case 'thumbnail':
				$this->render_thumbnail_column( $post_id );
				break;

			case 'apd_category':
				$this->render_category_column( $post_id );
				break;

			case 'listing_type':
				$this->render_listing_type_column( $post_id );
				break;

			case 'listing_status':
				$this->render_status_column( $post_id );
				break;

			case 'views_count':
				$this->render_views_column( $post_id );
				break;
		}
	}

	/**
	 * Render the thumbnail column.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	private function render_thumbnail_column( int $post_id ): void {
		$thumbnail_id = get_post_thumbnail_id( $post_id );

		if ( $thumbnail_id ) {
			$thumbnail = wp_get_attachment_image(
				$thumbnail_id,
				[ 50, 50 ],
				false,
				[
					'class' => 'apd-admin-thumbnail',
					'alt'   => esc_attr( get_the_title( $post_id ) ),
				]
			);
			echo wp_kses_post( $thumbnail );
		} else {
			echo '<span class="apd-no-image" aria-hidden="true">—</span>';
			echo '<span class="screen-reader-text">' . esc_html__( 'No image', 'all-purpose-directory' ) . '</span>';
		}
	}

	/**
	 * Render the category column.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	private function render_category_column( int $post_id ): void {
		$terms = get_the_terms( $post_id, 'apd_category' );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			echo '<span class="apd-no-category" aria-hidden="true">—</span>';
			echo '<span class="screen-reader-text">' . esc_html__( 'No category', 'all-purpose-directory' ) . '</span>';
			return;
		}

		$term_links = [];
		foreach ( $terms as $term ) {
			$term_links[] = sprintf(
				'<a href="%s">%s</a>',
				esc_url(
					add_query_arg(
						[
							'post_type'    => PostType::POST_TYPE,
							'apd_category' => $term->slug,
						],
						admin_url( 'edit.php' )
					)
				),
				esc_html( $term->name )
			);
		}

		echo wp_kses(
			implode( ', ', $term_links ),
			[
				'a' => [
					'href' => [],
				],
			]
		);
	}

	/**
	 * Render the listing type column.
	 *
	 * Shows the listing type as a filter link matching the category column pattern.
	 *
	 * @since 1.1.0
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	private function render_listing_type_column( int $post_id ): void {
		$type = apd_get_listing_type( $post_id );
		$term = apd_get_listing_type_term( $type );

		if ( $term === null ) {
			echo '<span aria-hidden="true">—</span>';
			echo '<span class="screen-reader-text">' . esc_html__( 'No type', 'all-purpose-directory' ) . '</span>';
			return;
		}

		printf(
			'<a href="%s">%s</a>',
			esc_url(
				add_query_arg(
					[
						'post_type' => PostType::POST_TYPE,
						\APD\Taxonomy\ListingTypeTaxonomy::TAXONOMY => $term->slug,
					],
					admin_url( 'edit.php' )
				)
			),
			esc_html( $term->name )
		);
	}

	/**
	 * Check whether 2+ listing type terms exist.
	 *
	 * @since 1.1.0
	 *
	 * @return bool
	 */
	private function has_multiple_listing_types(): bool {
		if ( ! function_exists( 'apd_get_listing_types' ) ) {
			return false;
		}

		$types = apd_get_listing_types( false );

		return count( $types ) >= 2;
	}

	/**
	 * Render the status badge column.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	private function render_status_column( int $post_id ): void {
		$post   = get_post( $post_id );
		$status = $post->post_status;

		$status_labels = [
			'publish'  => __( 'Published', 'all-purpose-directory' ),
			'pending'  => __( 'Pending', 'all-purpose-directory' ),
			'draft'    => __( 'Draft', 'all-purpose-directory' ),
			'expired'  => __( 'Expired', 'all-purpose-directory' ),
			'rejected' => __( 'Rejected', 'all-purpose-directory' ),
		];

		$label = $status_labels[ $status ] ?? ucfirst( $status );

		printf(
			'<span class="apd-status-badge apd-status-%s">%s</span>',
			esc_attr( $status ),
			esc_html( $label )
		);
	}

	/**
	 * Render the views count column.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	private function render_views_column( int $post_id ): void {
		$views = (int) get_post_meta( $post_id, self::META_VIEWS, true );

		printf(
			'<span class="apd-views-count">%s</span>',
			esc_html( number_format_i18n( $views ) )
		);
	}

	/**
	 * Define sortable columns.
	 *
	 * @param array<string, string|array> $columns Sortable columns.
	 * @return array<string, string|array> Modified sortable columns.
	 */
	public function sortable_columns( array $columns ): array {
		$columns['views_count']    = [ 'views_count', false ];
		$columns['listing_status'] = [ 'listing_status', false ];

		return $columns;
	}

	/**
	 * Handle custom column sorting.
	 *
	 * @param \WP_Query $query The query object.
	 * @return void
	 */
	public function sort_columns( \WP_Query $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		// Ensure we're on the listing edit screen.
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || $screen->id !== 'edit-' . PostType::POST_TYPE ) {
			return;
		}

		$orderby = $query->get( 'orderby' );

		if ( 'views_count' === $orderby ) {
			$query->set( 'meta_key', self::META_VIEWS );
			$query->set( 'orderby', 'meta_value_num' );
		}

		if ( 'listing_status' === $orderby ) {
			$query->set( 'orderby', 'post_status' );
		}
	}

	/**
	 * Render admin filter dropdowns.
	 *
	 * @param string $post_type The current post type.
	 * @return void
	 */
	public function render_filters( string $post_type ): void {
		if ( PostType::POST_TYPE !== $post_type ) {
			return;
		}

		$this->render_category_filter();
		$this->render_listing_type_filter();
		$this->render_status_filter();
	}

	/**
	 * Render the category dropdown filter.
	 *
	 * @return void
	 */
	private function render_category_filter(): void {
		$taxonomy = 'apd_category';

		// Check if taxonomy exists.
		if ( ! taxonomy_exists( $taxonomy ) ) {
			return;
		}

		// Get selected category from query string.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only filter display.
		$selected = isset( $_GET[ $taxonomy ] ) ? sanitize_text_field( wp_unslash( $_GET[ $taxonomy ] ) ) : '';

		wp_dropdown_categories(
			[
				'show_option_all' => __( 'All Categories', 'all-purpose-directory' ),
				'taxonomy'        => $taxonomy,
				'name'            => $taxonomy,
				'orderby'         => 'name',
				'selected'        => $selected,
				'hierarchical'    => true,
				'show_count'      => true,
				'hide_empty'      => false,
				'value_field'     => 'slug',
			]
		);
	}

	/**
	 * Render the listing type dropdown filter.
	 *
	 * @return void
	 */
	private function render_listing_type_filter(): void {
		$taxonomy = \APD\Taxonomy\ListingTypeTaxonomy::TAXONOMY;

		$terms = get_terms(
			[
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			]
		);

		// Only show filter when 2+ listing types exist.
		if ( is_wp_error( $terms ) || count( $terms ) < 2 ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only filter display.
		$selected = isset( $_GET[ $taxonomy ] ) ? sanitize_text_field( wp_unslash( $_GET[ $taxonomy ] ) ) : '';

		echo '<select name="' . esc_attr( $taxonomy ) . '" id="filter-by-listing-type">';
		echo '<option value="">' . esc_html__( 'All Listing Types', 'all-purpose-directory' ) . '</option>';

		foreach ( $terms as $term ) {
			$count = $this->get_listing_type_admin_count( $term->slug );

			printf(
				'<option value="%s" %s>%s (%d)</option>',
				esc_attr( $term->slug ),
				selected( $selected, $term->slug, false ),
				esc_html( $term->name ),
				absint( $count )
			);
		}

		echo '</select>';
	}

	/**
	 * Get listing count for a listing type in the current admin status context.
	 *
	 * @param string $type_slug Listing type term slug.
	 * @return int
	 */
	private function get_listing_type_admin_count( string $type_slug ): int {
		$post_status = $this->get_listing_type_count_statuses();

		$query = new \WP_Query(
			[
				'post_type'              => PostType::POST_TYPE,
				'post_status'            => $post_status,
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'no_found_rows'          => false,
				'orderby'                => 'none',
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'tax_query'              => [
					[
						'taxonomy' => \APD\Taxonomy\ListingTypeTaxonomy::TAXONOMY,
						'field'    => 'slug',
						'terms'    => sanitize_key( $type_slug ),
					],
				],
			]
		);

		return (int) $query->found_posts;
	}

	/**
	 * Resolve which post statuses should be used for listing type counts.
	 *
	 * Mirrors the admin list table context:
	 * 1) custom APD status dropdown (`listing_status`)
	 * 2) core post status tabs (`post_status`)
	 * 3) default "all" admin-visible statuses.
	 *
	 * @return string|string[] Post status argument for WP_Query.
	 */
	private function get_listing_type_count_statuses(): string|array {
		$available_statuses = get_post_stati( [ 'show_in_admin_status_list' => true ], 'names' );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only filter context.
		if ( ! empty( $_GET['listing_status'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$listing_status = sanitize_text_field( wp_unslash( $_GET['listing_status'] ) );

			if ( in_array( $listing_status, $available_statuses, true ) ) {
				return $listing_status;
			}
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only filter context.
		if ( ! empty( $_GET['post_status'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$post_status = sanitize_text_field( wp_unslash( $_GET['post_status'] ) );

			if ( 'all' !== $post_status && in_array( $post_status, $available_statuses, true ) ) {
				return $post_status;
			}
		}

		$all_admin_statuses = get_post_stati( [ 'show_in_admin_all_list' => true ], 'names' );

		return empty( $all_admin_statuses ) ? 'publish' : array_values( $all_admin_statuses );
	}

	/**
	 * Render the status dropdown filter.
	 *
	 * @return void
	 */
	private function render_status_filter(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only filter display.
		$selected = isset( $_GET['listing_status'] ) ? sanitize_text_field( wp_unslash( $_GET['listing_status'] ) ) : '';

		$statuses = [
			''         => __( 'All Statuses', 'all-purpose-directory' ),
			'publish'  => __( 'Published', 'all-purpose-directory' ),
			'pending'  => __( 'Pending', 'all-purpose-directory' ),
			'draft'    => __( 'Draft', 'all-purpose-directory' ),
			'expired'  => __( 'Expired', 'all-purpose-directory' ),
			'rejected' => __( 'Rejected', 'all-purpose-directory' ),
		];

		echo '<select name="listing_status" id="filter-by-listing-status">';
		echo '<option value="">' . esc_html__( 'All Statuses', 'all-purpose-directory' ) . '</option>';

		foreach ( $statuses as $value => $label ) {
			if ( '' === $value ) {
				continue; // Skip the "All Statuses" option since we already output it.
			}

			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $value ),
				selected( $selected, $value, false ),
				esc_html( $label )
			);
		}

		echo '</select>';
	}

	/**
	 * Apply admin filter query modifications.
	 *
	 * @param \WP_Query $query The query object.
	 * @return void
	 */
	public function apply_filters( \WP_Query $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		// Ensure we're on the listing edit screen.
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || $screen->id !== 'edit-' . PostType::POST_TYPE ) {
			return;
		}

		// Apply status filter.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only filter query.
		if ( ! empty( $_GET['listing_status'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$status = sanitize_text_field( wp_unslash( $_GET['listing_status'] ) );

			// Validate status value.
			$valid_statuses = [ 'publish', 'pending', 'draft', 'expired', 'rejected' ];
			if ( in_array( $status, $valid_statuses, true ) ) {
				$query->set( 'post_status', $status );
			}
		}

		// Apply listing type filter.
		$listing_type_taxonomy = \APD\Taxonomy\ListingTypeTaxonomy::TAXONOMY;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only filter query.
		if ( ! empty( $_GET[ $listing_type_taxonomy ] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$type_slug = sanitize_text_field( wp_unslash( $_GET[ $listing_type_taxonomy ] ) );

			$tax_query   = $query->get( 'tax_query' ) ?: [];
			$tax_query[] = [
				'taxonomy' => $listing_type_taxonomy,
				'field'    => 'slug',
				'terms'    => $type_slug,
			];
			$query->set( 'tax_query', $tax_query );
		}
	}

	/**
	 * Increment the views count for a listing.
	 *
	 * This is a utility method for use by other components.
	 *
	 * @param int $post_id Post ID.
	 * @return int The new views count.
	 */
	public static function increment_views( int $post_id ): int {
		$views = (int) get_post_meta( $post_id, self::META_VIEWS, true );
		++$views;

		update_post_meta( $post_id, self::META_VIEWS, $views );

		return $views;
	}

	/**
	 * Get the views count for a listing.
	 *
	 * @param int $post_id Post ID.
	 * @return int The views count.
	 */
	public static function get_views( int $post_id ): int {
		return (int) get_post_meta( $post_id, self::META_VIEWS, true );
	}
}
