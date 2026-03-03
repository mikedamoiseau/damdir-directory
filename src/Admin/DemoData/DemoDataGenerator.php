<?php
/**
 * Demo Data Generator Class.
 *
 * Generates sample data for testing the plugin.
 *
 * @package APD\Admin\DemoData
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Admin\DemoData;

use APD\Admin\DemoData\DataSets\CategoryData;
use APD\Admin\DemoData\DataSets\TagData;
use APD\Admin\DemoData\DataSets\BusinessNames;
use APD\Admin\DemoData\DataSets\Addresses;
use APD\Admin\DemoData\DataSets\ReviewContent;
use APD\Taxonomy\ListingTypeTaxonomy;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DemoDataGenerator
 *
 * Orchestrates the generation of demo data.
 *
 * @since 1.0.0
 */
final class DemoDataGenerator {

	/**
	 * Singleton instance.
	 *
	 * @var DemoDataGenerator|null
	 */
	private static ?DemoDataGenerator $instance = null;

	/**
	 * Demo data tracker instance.
	 *
	 * @var DemoDataTracker
	 */
	private DemoDataTracker $tracker;

	/**
	 * Created category term IDs mapped by slug.
	 *
	 * @var array<string, int>
	 */
	private array $category_ids = [];

	/**
	 * Created tag term IDs mapped by slug.
	 *
	 * @var array<string, int>
	 */
	private array $tag_ids = [];

	/**
	 * Get the singleton instance.
	 *
	 * @since 1.0.0
	 *
	 * @return DemoDataGenerator
	 */
	public static function get_instance(): DemoDataGenerator {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->tracker = DemoDataTracker::get_instance();
	}

	/**
	 * Prevent cloning.
	 */
	private function __clone() {}

	/**
	 * Prevent unserialization.
	 *
	 * @throws \Exception Always throws exception.
	 */
	public function __wakeup(): void {
		throw new \Exception( 'Cannot unserialize singleton.' );
	}

	/**
	 * Generate demo users.
	 *
	 * Users are always tracked as shared ('users'), not module-specific.
	 *
	 * @since 1.0.0
	 *
	 * @param int $count Number of users to create.
	 * @return int[] Created user IDs.
	 */
	public function generate_users( int $count = 5 ): array {
		$user_ids = [];
		$roles    = [ 'subscriber', 'subscriber', 'contributor', 'author', 'author' ];

		$first_names = [ 'John', 'Jane', 'Mike', 'Sarah', 'David', 'Emily', 'Chris', 'Amanda', 'Alex', 'Lisa' ];
		$last_names  = [ 'Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Miller', 'Davis', 'Wilson', 'Taylor', 'Anderson' ];

		for ( $i = 0; $i < $count; $i++ ) {
			$first_name = $first_names[ array_rand( $first_names ) ];
			$last_name  = $last_names[ array_rand( $last_names ) ];
			$role       = $roles[ $i % count( $roles ) ];
			$username   = strtolower( $first_name . $last_name . wp_rand( 100, 999 ) );
			$email      = $username . '@example.com';

			// Check if username exists.
			if ( username_exists( $username ) ) {
				$username .= wp_rand( 1000, 9999 );
				$email     = $username . '@example.com';
			}

			$user_id = wp_insert_user(
				[
					'user_login'   => $username,
					'user_email'   => $email,
					'user_pass'    => wp_generate_password( 16 ),
					'first_name'   => $first_name,
					'last_name'    => $last_name,
					'display_name' => "{$first_name} {$last_name}",
					'role'         => $role,
				]
			);

			if ( ! is_wp_error( $user_id ) ) {
				$this->tracker->mark_user_as_demo( $user_id );
				$user_ids[] = $user_id;
			}
		}

		return $user_ids;
	}

	/**
	 * Generate demo categories.
	 *
	 * Creates a category hierarchy from the provided data (or defaults).
	 * Sets _apd_listing_type term meta for category scoping when a module is specified.
	 *
	 * @since 1.0.0
	 *
	 * @param string $module        Module slug for tracking (default: 'general').
	 * @param array  $category_data Optional custom category hierarchy. Defaults to core CategoryData.
	 * @return int[] Created term IDs.
	 */
	public function generate_categories( string $module = DemoDataTracker::GENERAL_MODULE, array $category_data = [] ): array {
		$term_ids = [];

		if ( empty( $category_data ) ) {
			$category_data = CategoryData::get_categories();
		}

		/**
		 * Filter the demo category hierarchy data.
		 *
		 * @since 1.0.0
		 *
		 * @param array  $category_data Category hierarchy.
		 * @param string $module        Module slug generating the categories.
		 */
		$category_data = apply_filters( 'apd_demo_category_data', $category_data, $module );

		foreach ( $category_data as $slug => $category ) {
			// Create parent category.
			$parent_id = $this->create_category( $slug, $category, 0, $module );

			if ( $parent_id ) {
				$term_ids[]                  = $parent_id;
				$this->category_ids[ $slug ] = $parent_id;

				// Create child categories.
				if ( ! empty( $category['children'] ) ) {
					foreach ( $category['children'] as $child_slug => $child_data ) {
						$child_id = $this->create_category( $child_slug, $child_data, $parent_id, $module );
						if ( $child_id ) {
							$term_ids[]                        = $child_id;
							$this->category_ids[ $child_slug ] = $child_id;
						}
					}
				}
			}
		}

		return $term_ids;
	}

	/**
	 * Create a single category.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug   Category slug.
	 * @param array  $data   Category data.
	 * @param int    $parent Parent term ID.
	 * @param string $module Module slug for tracking and scoping.
	 * @return int|null Term ID or null on failure.
	 */
	private function create_category( string $slug, array $data, int $parent, string $module = DemoDataTracker::GENERAL_MODULE ): ?int {
		// Check if term already exists.
		$existing = term_exists( $slug, 'apd_category' );
		if ( $existing ) {
			$term_id = is_array( $existing ) ? (int) $existing['term_id'] : (int) $existing;
			$this->tracker->mark_term_as_demo( $term_id, $module );
			// Set listing type scope on existing term.
			update_term_meta( $term_id, '_apd_listing_type', $module );
			return $term_id;
		}

		$result = wp_insert_term(
			$data['name'],
			'apd_category',
			[
				'slug'        => $slug,
				'description' => $data['description'] ?? '',
				'parent'      => $parent,
			]
		);

		if ( is_wp_error( $result ) ) {
			return null;
		}

		$term_id = (int) $result['term_id'];

		// Add meta for icon and color (using correct meta keys).
		if ( ! empty( $data['icon'] ) ) {
			update_term_meta( $term_id, '_apd_category_icon', $data['icon'] );
		}
		if ( ! empty( $data['color'] ) ) {
			update_term_meta( $term_id, '_apd_category_color', $data['color'] );
		}

		// Set listing type scope for category.
		update_term_meta( $term_id, '_apd_listing_type', $module );

		$this->tracker->mark_term_as_demo( $term_id, $module );

		return $term_id;
	}

	/**
	 * Generate demo tags.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $count  Number of tags to create (uses defaults if count matches).
	 * @param string $module Module slug for tracking (default: 'general').
	 * @return int[] Created term IDs.
	 */
	public function generate_tags( int $count = 10, string $module = DemoDataTracker::GENERAL_MODULE ): array {
		$term_ids = [];
		$tags     = TagData::get_tags();

		// Limit to requested count.
		$tags = array_slice( $tags, 0, $count, true );

		foreach ( $tags as $slug => $tag ) {
			// Check if term already exists.
			$existing = term_exists( $slug, 'apd_tag' );
			if ( $existing ) {
				$term_id = is_array( $existing ) ? (int) $existing['term_id'] : (int) $existing;
				$this->tracker->mark_term_as_demo( $term_id, $module );
				$this->tag_ids[ $slug ] = $term_id;
				$term_ids[]             = $term_id;
				continue;
			}

			$result = wp_insert_term(
				$tag['name'],
				'apd_tag',
				[
					'slug'        => $slug,
					'description' => $tag['description'] ?? '',
				]
			);

			if ( ! is_wp_error( $result ) ) {
				$term_id = (int) $result['term_id'];
				$this->tracker->mark_term_as_demo( $term_id, $module );
				$this->tag_ids[ $slug ] = $term_id;
				$term_ids[]             = $term_id;
			}
		}

		return $term_ids;
	}

	/**
	 * Generate demo listings.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $count   Number of listings to create.
	 * @param string $module  Module slug for tracking (default: 'general').
	 * @param array  $options Generation options.
	 * @return int[] Created post IDs.
	 */
	public function generate_listings( int $count = 25, string $module = DemoDataTracker::GENERAL_MODULE, array $options = [] ): array {
		$post_ids = [];

		// Get available authors (demo users + admin).
		$demo_users = $this->tracker->get_demo_user_ids();
		$authors    = ! empty( $demo_users ) ? $demo_users : [ 1 ];

		// Get all child category slugs (we only assign to child categories).
		$category_slugs = ! empty( $this->category_ids )
			? array_keys( $this->category_ids )
			: CategoryData::get_category_slugs( false );

		// Filter to only child categories by checking if they have a parent in the map.
		$child_slugs = [];
		foreach ( $category_slugs as $slug ) {
			$parent_slug = CategoryData::get_parent_slug( $slug );
			if ( $parent_slug !== null ) {
				$child_slugs[] = $slug;
			}
		}

		// Fall back to all category slugs if no children found (e.g., module categories).
		if ( empty( $child_slugs ) ) {
			$child_slugs = $category_slugs;
		}

		// Status distribution (weighted).
		$statuses = [
			'publish' => 75,
			'pending' => 10,
			'draft'   => 10,
			'expired' => 5,
		];

		// Reset business names to avoid duplicates.
		BusinessNames::reset();

		for ( $i = 0; $i < $count; $i++ ) {
			// Select random category.
			$category_slug = ! empty( $child_slugs )
				? $child_slugs[ array_rand( $child_slugs ) ]
				: '';

			// Generate business data.
			$business_name = BusinessNames::generate( $category_slug );
			$tagline       = BusinessNames::generate_tagline( $category_slug );
			$address_data  = Addresses::generate();

			// Generate content.
			$content = $this->generate_listing_content( $business_name, $category_slug, $address_data );

			// Select status.
			$status = $this->weighted_random( $statuses );

			// Select author.
			$author = $authors[ array_rand( $authors ) ];

			// Create post date (within last year).
			$days_ago  = wp_rand( 1, 365 );
			$post_date = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days_ago} days" ) );

			$listing_data = [
				'post_type'    => 'apd_listing',
				'post_title'   => $business_name,
				'post_excerpt' => $tagline,
				'post_content' => $content,
				'post_status'  => $status,
				'post_author'  => $author,
				'post_date'    => $post_date,
			];

			/**
			 * Filter the demo listing data before creation.
			 *
			 * @since 1.0.0
			 *
			 * @param array  $listing_data  Listing post data.
			 * @param string $category_slug Category slug.
			 * @param int    $index         Listing index.
			 */
			$listing_data = apply_filters( 'apd_demo_listing_data', $listing_data, $category_slug, $i );

			$post_id = wp_insert_post( $listing_data );

			if ( is_wp_error( $post_id ) ) {
				continue;
			}

			// Mark as demo data with module slug.
			$this->tracker->mark_post_as_demo( $post_id, $module );

			// Add meta fields.
			$this->add_listing_meta( $post_id, $business_name, $category_slug, $address_data );

			// Assign listing type taxonomy term.
			if ( taxonomy_exists( ListingTypeTaxonomy::TAXONOMY ) ) {
				wp_set_object_terms( $post_id, $module, ListingTypeTaxonomy::TAXONOMY );
			}

			// Assign categories.
			$this->assign_listing_categories( $post_id, $category_slug );

			// Assign tags.
			$this->assign_listing_tags( $post_id, $category_slug );

			$post_ids[] = $post_id;
		}

		return $post_ids;
	}

	/**
	 * Generate listing content.
	 *
	 * @since 1.0.0
	 *
	 * @param string $business_name  Business name.
	 * @param string $category_slug  Category slug.
	 * @param array  $address_data   Address data.
	 * @return string
	 */
	private function generate_listing_content( string $business_name, string $category_slug, array $address_data ): string {
		$templates = [
			"Welcome to {business}, your destination for {service} in {city}. We've been proudly serving our community with dedication and excellence. Our experienced team is committed to providing you with the best possible experience. Stop by and discover why our customers keep coming back!",
			"{business} offers exceptional {service} in the heart of {neighborhood}. Our mission is to exceed your expectations with every visit. Whether you're a first-time visitor or a long-time customer, we treat everyone like family. Experience the difference today!",
			"At {business}, we believe in quality above all else. Located in {city}, {state}, we've built our reputation on outstanding {service} and genuine customer care. Our dedicated staff is here to make every interaction memorable. We look forward to serving you!",
			'Discover {business} - where quality meets convenience in {neighborhood}. Our commitment to excellence has made us a trusted name in {service}. From our welcoming atmosphere to our attention to detail, everything we do is designed with you in mind.',
			'{business} has been a cornerstone of the {city} community. We take pride in offering top-notch {service} with a personal touch. Our team is passionate about what we do, and it shows in every customer interaction. Come see what sets us apart!',
		];

		$template = $templates[ array_rand( $templates ) ];

		// Service descriptions by category.
		$services = [
			'restaurants'       => 'dining experiences',
			'cafes-coffee'      => 'artisan coffee and pastries',
			'fine-dining'       => 'culinary excellence',
			'fast-food'         => 'quick and delicious meals',
			'hotels'            => 'luxury accommodations',
			'bed-breakfast'     => 'charming hospitality',
			'vacation-rentals'  => 'vacation rentals',
			'clothing'          => 'fashion and style',
			'electronics'       => 'technology and gadgets',
			'grocery'           => 'fresh groceries and produce',
			'auto-repair'       => 'automotive services',
			'home-services'     => 'home improvement services',
			'professional'      => 'professional services',
			'nightlife'         => 'entertainment and nightlife',
			'movies-theater'    => 'entertainment',
			'sports-recreation' => 'fitness and recreation',
			'doctors'           => 'healthcare services',
			'dentists'          => 'dental care',
			'pharmacies'        => 'pharmaceutical care',
		];

		$service = $services[ $category_slug ] ?? 'quality service';

		return str_replace(
			[ '{business}', '{service}', '{city}', '{state}', '{neighborhood}' ],
			[ $business_name, $service, $address_data['city'], $address_data['state'], $address_data['neighborhood'] ],
			$template
		);
	}

	/**
	 * Add meta fields to a listing.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $post_id        Post ID.
	 * @param string $business_name  Business name.
	 * @param string $category_slug  Category slug.
	 * @param array  $address_data   Address data.
	 * @return void
	 */
	private function add_listing_meta( int $post_id, string $business_name, string $category_slug, array $address_data ): void {
		// Contact info.
		update_post_meta( $post_id, '_apd_phone', $address_data['phone'] );
		update_post_meta( $post_id, '_apd_email', Addresses::generate_email( $business_name ) );
		update_post_meta( $post_id, '_apd_website', Addresses::generate_website( $business_name ) );

		// Address.
		update_post_meta( $post_id, '_apd_address', $address_data['street'] );
		update_post_meta( $post_id, '_apd_city', $address_data['city'] );
		update_post_meta( $post_id, '_apd_state', $address_data['state'] );
		update_post_meta( $post_id, '_apd_zip', $address_data['zip'] );

		// Business info.
		update_post_meta( $post_id, '_apd_hours', Addresses::get_hours( $category_slug ) );
		update_post_meta( $post_id, '_apd_price_range', Addresses::get_price_range( $category_slug ) );

		// Stats (will be updated by reviews).
		update_post_meta( $post_id, '_apd_views_count', wp_rand( 50, 5000 ) );
	}

	/**
	 * Assign categories to a listing.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $post_id       Post ID.
	 * @param string $category_slug Primary category slug.
	 * @return void
	 */
	private function assign_listing_categories( int $post_id, string $category_slug ): void {
		$term_ids = [];

		// Add the primary child category.
		if ( isset( $this->category_ids[ $category_slug ] ) ) {
			$term_ids[] = $this->category_ids[ $category_slug ];
		}

		// Add the parent category.
		$parent_slug = CategoryData::get_parent_slug( $category_slug );
		if ( $parent_slug && isset( $this->category_ids[ $parent_slug ] ) ) {
			$term_ids[] = $this->category_ids[ $parent_slug ];
		}

		if ( ! empty( $term_ids ) ) {
			wp_set_object_terms( $post_id, $term_ids, 'apd_category' );
		}
	}

	/**
	 * Assign tags to a listing.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $post_id       Post ID.
	 * @param string $category_slug Category slug for relevant tags.
	 * @return void
	 */
	private function assign_listing_tags( int $post_id, string $category_slug ): void {
		// Get relevant tags for this category (2-4 tags).
		$tag_count = wp_rand( 2, 4 );
		$tag_slugs = TagData::get_tags_for_category( $category_slug, $tag_count );

		$term_ids = [];
		foreach ( $tag_slugs as $slug ) {
			if ( isset( $this->tag_ids[ $slug ] ) ) {
				$term_ids[] = $this->tag_ids[ $slug ];
			}
		}

		if ( ! empty( $term_ids ) ) {
			wp_set_object_terms( $post_id, $term_ids, 'apd_tag' );
		}
	}

	/**
	 * Generate demo reviews.
	 *
	 * @since 1.0.0
	 *
	 * @param int[]  $listing_ids Listing IDs to add reviews to.
	 * @param int[]  $user_ids    User IDs to use as reviewers.
	 * @param string $module      Module slug for tracking (default: 'general').
	 * @return int[] Created comment IDs.
	 */
	public function generate_reviews( array $listing_ids, array $user_ids = [], string $module = DemoDataTracker::GENERAL_MODULE ): array {
		$comment_ids = [];

		// Fallback to admin if no users.
		if ( empty( $user_ids ) ) {
			$user_ids = [ 1 ];
		}

		foreach ( $listing_ids as $listing_id ) {
			// Each listing gets 2-4 reviews.
			$review_count = wp_rand( 2, 4 );

			// Track users who have reviewed this listing.
			$reviewers_used = [];

			for ( $i = 0; $i < $review_count; $i++ ) {
				// Pick a user who hasn't reviewed yet.
				$available_users = array_diff( $user_ids, $reviewers_used );
				if ( empty( $available_users ) ) {
					// All users have reviewed, use anonymous reviewer.
					$user_id        = 0;
					$reviewer_name  = ReviewContent::generate_reviewer_name();
					$reviewer_email = strtolower( str_replace( [ ' ', '.' ], '', $reviewer_name ) ) . '@example.com';
				} else {
					$user_id = $available_users[ array_rand( $available_users ) ];
					$user    = get_userdata( $user_id );
					if ( $user ) {
						$reviewer_name  = $user->display_name;
						$reviewer_email = $user->user_email;
					} else {
						$reviewer_name  = ReviewContent::generate_reviewer_name();
						$reviewer_email = 'anonymous@example.com';
					}
					$reviewers_used[] = $user_id;
				}

				// Generate rating and content.
				$rating      = ReviewContent::generate_rating( 'positive' );
				$review_data = ReviewContent::generate( $rating );
				$review_date = ReviewContent::generate_date( 180, 1 );

				// Generate status with distribution: 70% approved, 20% pending, 5% spam, 5% trash.
				$status_rand = wp_rand( 1, 100 );
				if ( $status_rand <= 70 ) {
					$comment_status = 1; // Approved.
				} elseif ( $status_rand <= 90 ) {
					$comment_status = 0; // Pending.
				} elseif ( $status_rand <= 95 ) {
					$comment_status = 'spam';
				} else {
					$comment_status = 'trash';
				}

				$comment_data = [
					'comment_post_ID'      => $listing_id,
					'comment_author'       => $reviewer_name,
					'comment_author_email' => $reviewer_email,
					'comment_content'      => $review_data['content'],
					'comment_type'         => 'apd_review',
					'comment_approved'     => $comment_status,
					'comment_date'         => $review_date,
					'user_id'              => $user_id,
				];

				$comment_id = wp_insert_comment( $comment_data );

				if ( $comment_id && ! is_wp_error( $comment_id ) ) {
					// Add review meta.
					update_comment_meta( $comment_id, '_apd_rating', $rating );
					update_comment_meta( $comment_id, '_apd_review_title', $review_data['title'] );

					$this->tracker->mark_comment_as_demo( $comment_id, $module );
					$comment_ids[] = $comment_id;
				}
			}

			// Update listing rating aggregate.
			$this->update_listing_rating( $listing_id );
		}

		return $comment_ids;
	}

	/**
	 * Update the aggregate rating for a listing.
	 *
	 * @since 1.0.0
	 *
	 * @param int $listing_id Listing ID.
	 * @return void
	 */
	private function update_listing_rating( int $listing_id ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$ratings = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT cm.meta_value FROM {$wpdb->commentmeta} cm
				INNER JOIN {$wpdb->comments} c ON cm.comment_id = c.comment_ID
				WHERE c.comment_post_ID = %d
				AND c.comment_type = 'apd_review'
				AND c.comment_approved = 1
				AND cm.meta_key = '_apd_rating'",
				$listing_id
			)
		);

		if ( ! empty( $ratings ) ) {
			$average = array_sum( array_map( 'floatval', $ratings ) ) / count( $ratings );
			update_post_meta( $listing_id, '_apd_rating', round( $average, 1 ) );
			update_post_meta( $listing_id, '_apd_review_count', count( $ratings ) );
		}
	}

	/**
	 * Generate demo inquiries.
	 *
	 * @since 1.0.0
	 *
	 * @param int[]  $listing_ids Listing IDs to add inquiries to.
	 * @param string $module      Module slug for tracking (default: 'general').
	 * @return int[] Created inquiry post IDs.
	 */
	public function generate_inquiries( array $listing_ids, string $module = DemoDataTracker::GENERAL_MODULE ): array {
		$post_ids = [];

		// Check if inquiry post type exists.
		if ( ! post_type_exists( 'apd_inquiry' ) ) {
			return $post_ids;
		}

		$inquiry_messages = [
			'Hi, I\'m interested in learning more about your services. Could you please send me more information?',
			'Hello! I was wondering about your availability. Do you have any openings this week?',
			'I came across your listing and I\'m very interested. Can we schedule a time to discuss?',
			'Hi there! I have a few questions about your offerings. Could someone get back to me?',
			'I\'m looking for exactly what you offer. Please contact me at your earliest convenience.',
			'Great listing! I\'d love to know more about your pricing and availability.',
			'Hello, I\'m interested in your services for an upcoming event. Could you provide more details?',
		];

		foreach ( $listing_ids as $listing_id ) {
			// 50% chance of having inquiries.
			if ( wp_rand( 0, 1 ) === 0 ) {
				continue;
			}

			// 1-2 inquiries per listing.
			$inquiry_count = wp_rand( 1, 2 );

			for ( $i = 0; $i < $inquiry_count; $i++ ) {
				$sender_name  = ReviewContent::generate_reviewer_name();
				$sender_email = strtolower( str_replace( [ ' ', '.' ], '', $sender_name ) ) . '@example.com';
				$message      = $inquiry_messages[ array_rand( $inquiry_messages ) ];

				$days_ago     = wp_rand( 1, 60 );
				$inquiry_date = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days_ago} days" ) );

				$inquiry_data = [
					'post_type'    => 'apd_inquiry',
					'post_title'   => sprintf(
						/* translators: 1: Sender name, 2: Listing title */
						__( 'Inquiry from %1$s about %2$s', 'all-purpose-directory' ),
						$sender_name,
						get_the_title( $listing_id )
					),
					'post_content' => $message,
					'post_status'  => 'publish',
					'post_date'    => $inquiry_date,
					'meta_input'   => [
						'_apd_listing_id'   => $listing_id,
						'_apd_sender_name'  => $sender_name,
						'_apd_sender_email' => $sender_email,
						'_apd_read'         => wp_rand( 0, 1 ) ? '1' : '',
					],
				];

				$inquiry_id = wp_insert_post( $inquiry_data );

				if ( ! is_wp_error( $inquiry_id ) ) {
					$this->tracker->mark_post_as_demo( $inquiry_id, $module );
					$post_ids[] = $inquiry_id;
				}
			}
		}

		return $post_ids;
	}

	/**
	 * Generate demo favorites.
	 *
	 * @since 1.0.0
	 *
	 * @param int[] $listing_ids Listing IDs to favorite.
	 * @param int[] $user_ids    User IDs to add favorites to.
	 * @return int Number of favorites added.
	 */
	public function generate_favorites( array $listing_ids, array $user_ids = [] ): int {
		$count = 0;

		if ( empty( $listing_ids ) || empty( $user_ids ) ) {
			return $count;
		}

		foreach ( $user_ids as $user_id ) {
			// Each user favorites 1-5 random listings.
			$num_favorites = wp_rand( 1, min( 5, count( $listing_ids ) ) );

			// Get random listings.
			$shuffled = $listing_ids;
			shuffle( $shuffled );
			$favorites = array_slice( $shuffled, 0, $num_favorites );

			// Get existing favorites.
			$existing = get_user_meta( $user_id, '_apd_favorites', true );
			if ( ! is_array( $existing ) ) {
				$existing = [];
			}

			// Merge and save.
			$new_favorites = array_unique( array_merge( $existing, $favorites ) );
			update_user_meta( $user_id, '_apd_favorites', $new_favorites );

			$count += count( $favorites );
		}

		return $count;
	}

	/**
	 * Weighted random selection.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, int> $options Options with weights.
	 * @return string Selected option.
	 */
	private function weighted_random( array $options ): string {
		$total      = array_sum( $options );
		$random     = wp_rand( 1, $total );
		$cumulative = 0;

		foreach ( $options as $option => $weight ) {
			$cumulative += $weight;
			if ( $random <= $cumulative ) {
				return $option;
			}
		}

		return array_key_first( $options );
	}

	/**
	 * Get the demo data tracker instance.
	 *
	 * @since 1.0.0
	 *
	 * @return DemoDataTracker
	 */
	public function get_tracker(): DemoDataTracker {
		return $this->tracker;
	}

	/**
	 * Get the internal category IDs map.
	 *
	 * @since 1.2.0
	 *
	 * @return array<string, int> Category IDs keyed by slug.
	 */
	public function get_category_ids(): array {
		return $this->category_ids;
	}

	/**
	 * Reset internal state for fresh generation.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	public function reset_state(): void {
		$this->category_ids = [];
		$this->tag_ids      = [];
	}

	/**
	 * Reset singleton instance for testing.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function reset_instance(): void {
		self::$instance = null;
	}
}
