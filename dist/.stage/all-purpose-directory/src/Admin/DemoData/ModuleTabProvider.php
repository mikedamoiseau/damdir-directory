<?php
/**
 * Module Tab Provider.
 *
 * Implements TabProviderInterface by wrapping a DemoDataModuleProviderInterface.
 * Each module that registers a DemoDataModuleProviderInterface gets its own
 * tab on the demo data page via this adapter.
 *
 * @package APD\Admin\DemoData
 * @since   1.2.0
 */

declare(strict_types=1);

namespace APD\Admin\DemoData;

use APD\Contracts\DemoDataModuleProviderInterface;
use APD\Contracts\TabProviderInterface;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ModuleTabProvider
 *
 * @since 1.2.0
 */
final class ModuleTabProvider implements TabProviderInterface {

	/**
	 * The wrapped module provider.
	 *
	 * @var DemoDataModuleProviderInterface
	 */
	private DemoDataModuleProviderInterface $provider;

	/**
	 * Constructor.
	 *
	 * @param DemoDataModuleProviderInterface $provider Module provider instance.
	 */
	public function __construct( DemoDataModuleProviderInterface $provider ) {
		$this->provider = $provider;
	}

	/**
	 * Get the unique tab slug.
	 *
	 * @since 1.2.0
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return $this->provider->get_slug();
	}

	/**
	 * Get the tab display name.
	 *
	 * @since 1.2.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->provider->get_name();
	}

	/**
	 * Get the dashicon class for the tab label.
	 *
	 * @since 1.2.0
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return $this->provider->get_icon();
	}

	/**
	 * Get tab display priority.
	 *
	 * @since 1.2.0
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return 10;
	}

	/**
	 * Get current demo data counts for this module.
	 *
	 * @since 1.2.0
	 *
	 * @param DemoDataTracker $tracker Tracker instance.
	 * @return array<string, int>
	 */
	public function get_counts( DemoDataTracker $tracker ): array {
		$module = $this->get_slug();

		// Core counts scoped to this module.
		$core_counts = $tracker->count_demo_data( $module );

		// Module-specific counts (e.g., URLs for url-directory).
		$module_counts = $this->provider->count( $tracker );

		return array_merge( $core_counts, $module_counts );
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
		$module      = $this->get_slug();
		$core_counts = $tracker->count_demo_data( $module );

		$core_rows = [
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

		foreach ( $core_rows as $type => $row ) :
			?>
			<div class="apd-stat-item">
				<span class="dashicons <?php echo esc_attr( $row['icon'] ); ?>" aria-hidden="true"></span>
				<span class="apd-stat-label"><?php echo esc_html( $row['label'] ); ?></span>
				<span class="apd-stat-count" data-type="<?php echo esc_attr( $module . '_' . $type ); ?>">
					<?php echo esc_html( number_format_i18n( $core_counts[ $type ] ?? 0 ) ); ?>
				</span>
			</div>
			<?php
		endforeach;

		// Module-specific counts.
		$module_counts = $this->provider->count( $tracker );

		foreach ( $module_counts as $type => $count ) :
			?>
			<div class="apd-stat-item">
				<span class="dashicons <?php echo esc_attr( $this->get_icon() ); ?>" aria-hidden="true"></span>
				<span class="apd-stat-label"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $type ) ) ); ?></span>
				<span class="apd-stat-count" data-type="<?php echo esc_attr( $module . '_module_' . $type ); ?>">
					<?php echo esc_html( number_format_i18n( $count ) ); ?>
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
		$module = $this->get_slug();

		?>
		<p class="apd-tab-intro">
			<?php
			printf(
				/* translators: %s: Module name */
				esc_html__( 'Generate %s directory data with module-specific categories and fields.', 'all-purpose-directory' ),
				esc_html( $this->get_name() )
			);
			?>
		</p>

		<div class="apd-form-row">
			<label class="apd-checkbox-label">
				<input type="checkbox" name="generate_categories" value="1" checked>
				<?php esc_html_e( 'Categories', 'all-purpose-directory' ); ?>
			</label>
			<span class="description">
				<?php echo esc_html( $this->get_category_description() ); ?>
			</span>
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
		// Module-specific data summary (always shown for modules).
		$this->render_module_data_summary();

		// Module-specific form fields.
		$form_fields = $this->provider->get_form_fields();

		if ( ! empty( $form_fields ) ) :
			?>
			<div class="apd-form-divider">
				<?php
				printf(
					/* translators: %s: Module name */
					esc_html__( '%s Options', 'all-purpose-directory' ),
					esc_html( $this->get_name() )
				);
				?>
			</div>

			<?php foreach ( $form_fields as $field ) : ?>
				<div class="apd-form-row">
					<label class="apd-checkbox-label">
						<?php echo esc_html( $field['label'] ); ?>
					</label>
					<input
						type="<?php echo esc_attr( $field['type'] ); ?>"
						name="module_<?php echo esc_attr( $module ); ?>_<?php echo esc_attr( $field['name'] ); ?>"
						value="<?php echo esc_attr( (string) $field['default'] ); ?>"
						min="<?php echo esc_attr( (string) ( $field['min'] ?? 1 ) ); ?>"
						max="<?php echo esc_attr( (string) ( $field['max'] ?? 100 ) ); ?>"
						class="small-text"
					>
				</div>
			<?php endforeach; ?>
			<?php
		endif;
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
						/* translators: 1: Number of items, 2: Module name */
						esc_html__( 'This tab has %1$s demo data items. Deleting will permanently remove all %2$s demo data.', 'all-purpose-directory' ),
						'<strong>' . esc_html( number_format_i18n( $total ) ) . '</strong>',
						esc_html( $this->get_name() )
					);
					?>
				</p>
			</div>

			<button type="button" class="button button-link-delete button-large apd-delete-tab-btn" data-module="<?php echo esc_attr( $this->get_slug() ); ?>">
				<span class="dashicons dashicons-trash" aria-hidden="true"></span>
				<?php
				printf(
					/* translators: %s: Module name */
					esc_html__( 'Delete %s Demo Data', 'all-purpose-directory' ),
					esc_html( $this->get_name() )
				);
				?>
			</button>
		<?php else : ?>
			<p class="apd-no-data">
				<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
				<?php
				printf(
					/* translators: %s: Module name */
					esc_html__( 'No %s demo data found.', 'all-purpose-directory' ),
					esc_html( $this->get_name() )
				);
				?>
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
		$module    = $this->get_slug();
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

		// Generate module-specific categories.
		if ( $generate_categories ) {
			$generator->reset_state();
			$category_data         = $this->provider->get_category_data();
			$category_ids          = $generator->generate_categories( $module, $category_data );
			$created['categories'] = count( $category_ids );
		}

		// Generate tags.
		if ( $generate_tags ) {
			$tag_ids         = $generator->generate_tags( $tags_count, $module );
			$created['tags'] = count( $tag_ids );
		}

		// Generate listings with listing type.
		if ( $generate_listings ) {
			$listing_ids         = $generator->generate_listings( $listings_count, $module );
			$created['listings'] = count( $listing_ids );
		}

		// Generate reviews.
		if ( $generate_reviews && ! empty( $listing_ids ) ) {
			$review_ids         = $generator->generate_reviews( $listing_ids, $user_ids, $module );
			$created['reviews'] = count( $review_ids );
		}

		// Generate inquiries.
		if ( $generate_inquiries && ! empty( $listing_ids ) ) {
			$inquiry_ids          = $generator->generate_inquiries( $listing_ids, $module );
			$created['inquiries'] = count( $inquiry_ids );
		}

		// Generate favorites.
		if ( $generate_favorites && ! empty( $listing_ids ) && ! empty( $user_ids ) ) {
			$created['favorites'] = $generator->generate_favorites( $listing_ids, $user_ids );
		}

		// Generate module-specific data (e.g., URLs for url-directory).
		$context = [
			'user_ids'     => $user_ids,
			'listing_ids'  => $listing_ids,
			'category_ids' => isset( $category_ids ) ? $category_ids : [],
			'tag_ids'      => isset( $tag_ids ) ? $tag_ids : [],
			'options'      => $this->extract_module_options( $post_data ),
		];

		$module_results = $this->provider->generate( $context, $tracker );
		foreach ( $module_results as $type => $count ) {
			$created[ 'module_' . $type ] = $count;
		}

		$counts = $this->get_counts( $tracker );

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
		return $tracker->delete_by_module( $this->get_slug() );
	}

	/**
	 * Get the wrapped module provider.
	 *
	 * @since 1.2.0
	 *
	 * @return DemoDataModuleProviderInterface
	 */
	public function get_provider(): DemoDataModuleProviderInterface {
		return $this->provider;
	}

	/**
	 * Build a human-readable category description from the module's category data.
	 *
	 * @since 1.2.0
	 *
	 * @return string
	 */
	private function get_category_description(): string {
		$category_data = $this->provider->get_category_data();

		if ( empty( $category_data ) ) {
			return sprintf(
				/* translators: %s: Module name */
				__( '%s categories with icons and colors', 'all-purpose-directory' ),
				$this->get_name()
			);
		}

		$parent_names = [];
		$total        = 0;

		foreach ( $category_data as $category ) {
			$parent_names[] = $category['name'];
			++$total;

			if ( ! empty( $category['children'] ) ) {
				$total += count( $category['children'] );
			}
		}

		// Show first 3 parent names, then "& more" if there are more.
		$preview = array_slice( $parent_names, 0, 3 );
		$suffix  = count( $parent_names ) > 3
			? sprintf(
				/* translators: %d: number of additional categories */
				__( ' & %d more', 'all-purpose-directory' ),
				count( $parent_names ) - 3
			)
			: '';

		return implode( ', ', $preview ) . $suffix . ' + ' . __( 'children', 'all-purpose-directory' );
	}

	/**
	 * Render a summary of module-specific data that will be generated.
	 *
	 * Shows what extra data this module adds beyond the core types (categories, tags, listings, etc).
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	private function render_module_data_summary(): void {
		$description = $this->provider->get_description();

		if ( empty( $description ) ) {
			return;
		}

		?>
		<div class="apd-form-divider">
			<?php
			printf(
				/* translators: %s: Module name */
				esc_html__( '%s Fields', 'all-purpose-directory' ),
				esc_html( $this->get_name() )
			);
			?>
		</div>
		<div class="apd-form-row apd-module-data-row">
			<span class="apd-module-data-label">
				<span class="dashicons <?php echo esc_attr( $this->get_icon() ); ?>" aria-hidden="true"></span>
				<?php echo esc_html( $this->get_name() ); ?>
			</span>
			<span class="description"><?php echo esc_html( $description ); ?></span>
		</div>
		<?php
	}

	/**
	 * Extract module-specific options from POST data.
	 *
	 * @param array<string, mixed> $post_data POST data.
	 * @return array<string, mixed> Module options.
	 */
	private function extract_module_options( array $post_data ): array {
		$module  = $this->get_slug();
		$options = [];
		$prefix  = 'module_' . $module . '_';

		foreach ( $post_data as $key => $value ) {
			if ( strpos( $key, $prefix ) === 0 ) {
				$option_key             = substr( $key, strlen( $prefix ) );
				$options[ $option_key ] = $value;
			}
		}

		return $options;
	}
}
