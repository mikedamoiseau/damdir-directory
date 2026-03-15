<?php
/**
 * Tag Data Set for Demo Data.
 *
 * Provides tag data for demo generation.
 *
 * @package APD\Admin\DemoData\DataSets
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Admin\DemoData\DataSets;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TagData
 *
 * Provides static tag data for demo listings.
 *
 * @since 1.0.0
 */
final class TagData {

	/**
	 * Get the default tags.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array{name: string, description: string}>
	 */
	public static function get_tags(): array {
		return [
			'pet-friendly'          => [
				'name'        => __( 'Pet Friendly', 'all-purpose-directory' ),
				'description' => __( 'Welcomes pets', 'all-purpose-directory' ),
			],
			'wheelchair-accessible' => [
				'name'        => __( 'Wheelchair Accessible', 'all-purpose-directory' ),
				'description' => __( 'Fully accessible', 'all-purpose-directory' ),
			],
			'free-wifi'             => [
				'name'        => __( 'Free WiFi', 'all-purpose-directory' ),
				'description' => __( 'Complimentary WiFi', 'all-purpose-directory' ),
			],
			'parking-available'     => [
				'name'        => __( 'Parking Available', 'all-purpose-directory' ),
				'description' => __( 'On-site parking', 'all-purpose-directory' ),
			],
			'open-late'             => [
				'name'        => __( 'Open Late', 'all-purpose-directory' ),
				'description' => __( 'Late night hours', 'all-purpose-directory' ),
			],
			'family-friendly'       => [
				'name'        => __( 'Family Friendly', 'all-purpose-directory' ),
				'description' => __( 'Great for families', 'all-purpose-directory' ),
			],
			'outdoor-seating'       => [
				'name'        => __( 'Outdoor Seating', 'all-purpose-directory' ),
				'description' => __( 'Patio or outdoor area', 'all-purpose-directory' ),
			],
			'delivery-available'    => [
				'name'        => __( 'Delivery Available', 'all-purpose-directory' ),
				'description' => __( 'Offers delivery service', 'all-purpose-directory' ),
			],
			'accepts-credit-cards'  => [
				'name'        => __( 'Accepts Credit Cards', 'all-purpose-directory' ),
				'description' => __( 'Credit card payments accepted', 'all-purpose-directory' ),
			],
			'reservations'          => [
				'name'        => __( 'Reservations', 'all-purpose-directory' ),
				'description' => __( 'Takes reservations', 'all-purpose-directory' ),
			],
		];
	}

	/**
	 * Get all tag slugs.
	 *
	 * @since 1.0.0
	 *
	 * @return string[]
	 */
	public static function get_tag_slugs(): array {
		return array_keys( self::get_tags() );
	}

	/**
	 * Get random tag slugs for a listing.
	 *
	 * @since 1.0.0
	 *
	 * @param int $count Number of tags to return (1-5).
	 * @return string[]
	 */
	public static function get_random_tag_slugs( int $count = 3 ): array {
		$slugs = self::get_tag_slugs();
		shuffle( $slugs );

		return array_slice( $slugs, 0, min( $count, count( $slugs ) ) );
	}

	/**
	 * Get tags relevant to a category.
	 *
	 * Some tags are more relevant to certain categories.
	 *
	 * @since 1.0.0
	 *
	 * @param string $category_slug Category slug.
	 * @param int    $count         Number of tags to return.
	 * @return string[]
	 */
	public static function get_tags_for_category( string $category_slug, int $count = 3 ): array {
		// Define tag relevance by category.
		$category_tags = [
			'restaurants'       => [ 'outdoor-seating', 'delivery-available', 'reservations', 'accepts-credit-cards', 'family-friendly', 'pet-friendly' ],
			'cafes-coffee'      => [ 'free-wifi', 'outdoor-seating', 'pet-friendly', 'accepts-credit-cards' ],
			'fine-dining'       => [ 'reservations', 'accepts-credit-cards', 'wheelchair-accessible' ],
			'fast-food'         => [ 'delivery-available', 'parking-available', 'family-friendly', 'open-late' ],
			'hotels'            => [ 'free-wifi', 'parking-available', 'wheelchair-accessible', 'pet-friendly' ],
			'bed-breakfast'     => [ 'free-wifi', 'parking-available', 'pet-friendly' ],
			'vacation-rentals'  => [ 'pet-friendly', 'free-wifi', 'parking-available', 'family-friendly' ],
			'shopping'          => [ 'wheelchair-accessible', 'parking-available', 'accepts-credit-cards' ],
			'clothing'          => [ 'wheelchair-accessible', 'accepts-credit-cards' ],
			'electronics'       => [ 'wheelchair-accessible', 'parking-available', 'accepts-credit-cards' ],
			'grocery'           => [ 'wheelchair-accessible', 'parking-available', 'delivery-available', 'accepts-credit-cards' ],
			'services'          => [ 'wheelchair-accessible', 'accepts-credit-cards', 'free-wifi' ],
			'auto-repair'       => [ 'wheelchair-accessible', 'accepts-credit-cards' ],
			'home-services'     => [ 'accepts-credit-cards' ],
			'professional'      => [ 'wheelchair-accessible', 'parking-available', 'accepts-credit-cards', 'free-wifi' ],
			'entertainment'     => [ 'wheelchair-accessible', 'parking-available', 'family-friendly', 'accepts-credit-cards' ],
			'nightlife'         => [ 'open-late', 'accepts-credit-cards', 'reservations' ],
			'movies-theater'    => [ 'wheelchair-accessible', 'parking-available', 'family-friendly', 'accepts-credit-cards' ],
			'sports-recreation' => [ 'wheelchair-accessible', 'parking-available', 'open-late', 'family-friendly' ],
			'healthcare'        => [ 'wheelchair-accessible', 'parking-available', 'accepts-credit-cards' ],
			'doctors'           => [ 'wheelchair-accessible', 'parking-available', 'accepts-credit-cards' ],
			'dentists'          => [ 'wheelchair-accessible', 'parking-available', 'accepts-credit-cards', 'family-friendly' ],
			'pharmacies'        => [ 'wheelchair-accessible', 'parking-available', 'delivery-available', 'accepts-credit-cards' ],
		];

		$relevant_tags = $category_tags[ $category_slug ] ?? self::get_tag_slugs();
		shuffle( $relevant_tags );

		return array_slice( $relevant_tags, 0, min( $count, count( $relevant_tags ) ) );
	}
}
