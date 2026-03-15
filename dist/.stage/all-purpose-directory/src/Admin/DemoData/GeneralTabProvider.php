<?php
/**
 * General Tab Provider.
 *
 * Implements TabProviderInterface for the "General" tab on the demo data page.
 * Handles core data types: categories, tags, listings, reviews, inquiries, favorites.
 *
 * @package APD\Admin\DemoData
 * @since   1.2.0
 */

declare(strict_types=1);

namespace APD\Admin\DemoData;

use APD\Contracts\TabProviderInterface;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class GeneralTabProvider
 *
 * @since 1.2.0
 */
final class GeneralTabProvider implements TabProviderInterface {

	/**
	 * Get the unique tab slug.
	 *
	 * @since 1.2.0
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return DemoDataTracker::GENERAL_MODULE;
	}

	/**
	 * Get the tab display name.
	 *
	 * @since 1.2.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return __( 'General', 'all-purpose-directory' );
	}

	/**
	 * Get the dashicon class for the tab label.
	 *
	 * @since 1.2.0
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return 'dashicons-admin-generic';
	}

	/**
	 * Get tab display priority.
	 *
	 * @since 1.2.0
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return 0;
	}

	/**
	 * Get current demo data counts for the General tab.
	 *
	 * @since 1.2.0
	 *
	 * @param DemoDataTracker $tracker Tracker instance.
	 * @return array<string, int>
	 */
	public function get_counts( DemoDataTracker $tracker ): array {
		return $tracker->count_demo_data( DemoDataTracker::GENERAL_MODULE );
	}

	/**
	 * Get the total demo data count for this tab.
	 *
	 * @since 1.2.0
	 *
	 * @param DemoDataTracker $tracker Tracker instance.
	 * @return int
	 */
	public function get_total( DemoDataTracker $tracker ): int {
		$counts = $this->get_counts( $tracker );
		return array_sum( $counts );
	}

	/**
	 * Render the status counts table rows.
	 *
	 * @since 1.2.0
	 *
	 * @param DemoDataTracker $tracker Tracker instance.
	 * @return void
	 */
	public function render_status_section( DemoDataTracker $tracker ): void {
		$counts = $this->get_counts( $tracker );
		$slug   = $this->get_slug();

		$rows = [
			'categories' => [
				'icon'  => 'dashicons-category',
				'label' => __( 'Categories', 'all-purpose-directory' ),
			],
			'tags'       => [
				'icon'  => 'dashicons-tag',
				'label' => __( 'Tags', 'all-purpose-directory' ),
			],
			'listings'   => [
				'icon'  => 'dashicons-location',
				'label' => __( 'Listings', 'all-purpose-directory' ),
			],
			'reviews'    => [
				'icon'  => 'dashicons-star-filled',
				'label' => __( 'Reviews', 'all-purpose-directory' ),
			],
			'inquiries'  => [
				'icon'  => 'dashicons-email',
				'label' => __( 'Inquiries', 'all-purpose-directory' ),
			],
		];

		foreach ( $rows as $type => $row ) :
			?>
			<div class="apd-stat-item">
				<span class="dashicons <?php echo esc_attr( $row['icon'] ); ?>" aria-hidden="true"></span>
				<span class="apd-stat-label"><?php echo esc_html( $row['label'] ); ?></span>
				<span class="apd-stat-count" data-type="<?php echo esc_attr( $slug . '_' . $type ); ?>">
					<?php echo esc_html( number_format_i18n( $counts[ $type ] ?? 0 ) ); ?>
				</span>
			</div>
			<?php
		endforeach;
	}

	/**
	 * Render the generation form fields.
	 *
	 * @since 1.2.0
	 *
	 * @param array<string, int> $defaults Default quantities.
	 * @return void
	 */
	public function render_generate_form( array $defaults ): void {
		?>
		<p class="apd-tab-intro">
			<?php esc_html_e( 'Generate general-purpose directory data with business categories like Restaurants, Hotels, and Shopping.', 'all-purpose-directory' ); ?>
		</p>

		<div class="apd-form-row">
			<label class="apd-checkbox-label">
				<input type="checkbox" name="generate_categories" value="1" checked>
				<?php esc_html_e( 'Categories', 'all-purpose-directory' ); ?>
			</label>
			<span class="description"><?php esc_html_e( 'Restaurants, Hotels, Shopping, Services, Entertainment, Healthcare + children', 'all-purpose-directory' ); ?></span>
		</div>

		<div class="apd-form-row">
			<label class="apd-checkbox-label">
				<input type="checkbox" name="generate_tags" value="1" checked>
				<?php esc_html_e( 'Tags', 'all-purpose-directory' ); ?>
			</label>
			<input type="number" name="tags_count" value="<?php echo esc_attr( (string) ( $defaults['tags'] ?? 10 ) ); ?>" min="1" max="10" class="small-text">
			<span class="description"><?php esc_html_e( 'tags (max 10)', 'all-purpose-directory' ); ?></span>
		</div>

		<div class="apd-form-row">
			<label class="apd-checkbox-label">
				<input type="checkbox" name="generate_listings" value="1" checked>
				<?php esc_html_e( 'Listings', 'all-purpose-directory' ); ?>
			</label>
			<input type="number" name="listings_count" value="<?php echo esc_attr( (string) ( $defaults['listings'] ?? 25 ) ); ?>" min="1" max="100" class="small-text">
			<span class="description"><?php esc_html_e( 'listings (max 100)', 'all-purpose-directory' ); ?></span>
		</div>

		<div class="apd-form-row">
			<label class="apd-checkbox-label">
				<input type="checkbox" name="generate_reviews" value="1" checked>
				<?php esc_html_e( 'Reviews', 'all-purpose-directory' ); ?>
			</label>
			<span class="description"><?php esc_html_e( '2-4 reviews per listing', 'all-purpose-directory' ); ?></span>
		</div>

		<div class="apd-form-row">
			<label class="apd-checkbox-label">
				<input type="checkbox" name="generate_inquiries" value="1" checked>
				<?php esc_html_e( 'Inquiries', 'all-purpose-directory' ); ?>
			</label>
			<span class="description"><?php esc_html_e( '0-2 inquiries per listing (random)', 'all-purpose-directory' ); ?></span>
		</div>

		<div class="apd-form-row">
			<label class="apd-checkbox-label">
				<input type="checkbox" name="generate_favorites" value="1" checked>
				<?php esc_html_e( 'Favorites', 'all-purpose-directory' ); ?>
			</label>
			<span class="description"><?php esc_html_e( '1-5 favorites per user', 'all-purpose-directory' ); ?></span>
		</div>
		<?php
	}

	/**
	 * Render the delete section.
	 *
	 * @since 1.2.0
	 *
	 * @param DemoDataTracker $tracker Tracker instance.
	 * @return void
	 */
	public function render_delete_section( DemoDataTracker $tracker ): void {
		$total = $this->get_total( $tracker );

		if ( $total > 0 ) :
			?>
			<div class="apd-warning">
				<span class="dashicons dashicons-warning" aria-hidden="true"></span>
				<p>
					<?php
					printf(
						/* translators: %s: Number of demo data items */
						esc_html__( 'This tab has %s demo data items. Deleting will permanently remove all General categories, tags, listings, reviews, inquiries, and favorites.', 'all-purpose-directory' ),
						'<strong>' . esc_html( number_format_i18n( $total ) ) . '</strong>'
					);
					?>
				</p>
			</div>

			<button type="button" class="button button-link-delete button-large apd-delete-tab-btn" data-module="<?php echo esc_attr( $this->get_slug() ); ?>">
				<span class="dashicons dashicons-trash" aria-hidden="true"></span>
				<?php esc_html_e( 'Delete General Demo Data', 'all-purpose-directory' ); ?>
			</button>
		<?php else : ?>
			<p class="apd-no-data">
				<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
				<?php esc_html_e( 'No General demo data found.', 'all-purpose-directory' ); ?>
			</p>
			<?php
		endif;
	}

	/**
	 * Handle AJAX generate request.
	 *
	 * @since 1.2.0
	 *
	 * @param array<string, mixed> $post_data Sanitized POST data.
	 * @return array{created: array<string, int>, counts: array<string, int>}
	 */
	public function handle_generate( array $post_data ): array {
		$generator = DemoDataGenerator::get_instance();
		$tracker   = DemoDataTracker::get_instance();
		$module    = DemoDataTracker::GENERAL_MODULE;
		$created   = [];

		$generate_categories = ! empty( $post_data['generate_categories'] );
		$generate_tags       = ! empty( $post_data['generate_tags'] );
		$generate_listings   = ! empty( $post_data['generate_listings'] );
		$generate_reviews    = ! empty( $post_data['generate_reviews'] );
		$generate_inquiries  = ! empty( $post_data['generate_inquiries'] );
		$generate_favorites  = ! empty( $post_data['generate_favorites'] );

		$tags_count     = min( absint( $post_data['tags_count'] ?? 10 ), 10 );
		$listings_count = min( absint( $post_data['listings_count'] ?? 25 ), 100 );

		$user_ids    = $tracker->get_demo_user_ids();
		$listing_ids = [];

		// Generate categories.
		if ( $generate_categories ) {
			$generator->reset_state();
			$category_ids          = $generator->generate_categories( $module );
			$created['categories'] = count( $category_ids );
		}

		// Generate tags.
		if ( $generate_tags ) {
			$tag_ids         = $generator->generate_tags( $tags_count, $module );
			$created['tags'] = count( $tag_ids );
		}

		// Generate listings.
		if ( $generate_listings ) {
			$listing_ids         = $generator->generate_listings( $listings_count, $module );
			$created['listings'] = count( $listing_ids );
		}

		// Generate reviews (requires listings).
		if ( $generate_reviews && ! empty( $listing_ids ) ) {
			$review_ids         = $generator->generate_reviews( $listing_ids, $user_ids, $module );
			$created['reviews'] = count( $review_ids );
		}

		// Generate inquiries (requires listings).
		if ( $generate_inquiries && ! empty( $listing_ids ) ) {
			$inquiry_ids          = $generator->generate_inquiries( $listing_ids, $module );
			$created['inquiries'] = count( $inquiry_ids );
		}

		// Generate favorites (requires listings and users).
		if ( $generate_favorites && ! empty( $listing_ids ) && ! empty( $user_ids ) ) {
			$created['favorites'] = $generator->generate_favorites( $listing_ids, $user_ids );
		}

		$counts = $tracker->count_demo_data( $module );

		return [
			'created' => $created,
			'counts'  => $counts,
		];
	}

	/**
	 * Handle AJAX delete request.
	 *
	 * @since 1.2.0
	 *
	 * @param DemoDataTracker $tracker Tracker instance.
	 * @return array<string, int>
	 */
	public function handle_delete( DemoDataTracker $tracker ): array {
		return $tracker->delete_by_module( DemoDataTracker::GENERAL_MODULE );
	}
}
