<?php
/**
 * Category Data Set for Demo Data.
 *
 * Provides category hierarchy data for demo generation.
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
 * Class CategoryData
 *
 * Provides static category hierarchy data with icons and colors.
 *
 * @since 1.0.0
 */
final class CategoryData {

	/**
	 * Get the full category hierarchy.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array{name: string, description: string, icon: string, color: string, children?: array<string, array{name: string, description: string, icon: string, color: string}>}>
	 */
	public static function get_categories(): array {
		$categories = [
			'restaurants'   => [
				'name'        => __( 'Restaurants', 'all-purpose-directory' ),
				'description' => __( 'Places to eat and drink', 'all-purpose-directory' ),
				'icon'        => 'dashicons-food',
				'color'       => '#FF5722',
				'children'    => [
					'cafes-coffee' => [
						'name'        => __( 'Cafes & Coffee', 'all-purpose-directory' ),
						'description' => __( 'Coffee shops and cafes', 'all-purpose-directory' ),
						'icon'        => 'dashicons-coffee',
						'color'       => '#8D6E63',
					],
					'fine-dining'  => [
						'name'        => __( 'Fine Dining', 'all-purpose-directory' ),
						'description' => __( 'Upscale dining experiences', 'all-purpose-directory' ),
						'icon'        => 'dashicons-star-filled',
						'color'       => '#D4AF37',
					],
					'fast-food'    => [
						'name'        => __( 'Fast Food', 'all-purpose-directory' ),
						'description' => __( 'Quick service restaurants', 'all-purpose-directory' ),
						'icon'        => 'dashicons-food',
						'color'       => '#FF9800',
					],
				],
			],
			'hotels'        => [
				'name'        => __( 'Hotels & Lodging', 'all-purpose-directory' ),
				'description' => __( 'Places to stay', 'all-purpose-directory' ),
				'icon'        => 'dashicons-building',
				'color'       => '#3F51B5',
				'children'    => [
					'bed-breakfast'    => [
						'name'        => __( 'Bed & Breakfast', 'all-purpose-directory' ),
						'description' => __( 'Cozy B&B accommodations', 'all-purpose-directory' ),
						'icon'        => 'dashicons-admin-home',
						'color'       => '#E91E63',
					],
					'vacation-rentals' => [
						'name'        => __( 'Vacation Rentals', 'all-purpose-directory' ),
						'description' => __( 'Short-term rental properties', 'all-purpose-directory' ),
						'icon'        => 'dashicons-palmtree',
						'color'       => '#00BCD4',
					],
				],
			],
			'shopping'      => [
				'name'        => __( 'Shopping', 'all-purpose-directory' ),
				'description' => __( 'Retail stores and malls', 'all-purpose-directory' ),
				'icon'        => 'dashicons-cart',
				'color'       => '#4CAF50',
				'children'    => [
					'clothing'    => [
						'name'        => __( 'Clothing & Apparel', 'all-purpose-directory' ),
						'description' => __( 'Fashion and clothing stores', 'all-purpose-directory' ),
						'icon'        => 'dashicons-tag',
						'color'       => '#673AB7',
					],
					'electronics' => [
						'name'        => __( 'Electronics', 'all-purpose-directory' ),
						'description' => __( 'Tech and electronics stores', 'all-purpose-directory' ),
						'icon'        => 'dashicons-laptop',
						'color'       => '#2196F3',
					],
					'grocery'     => [
						'name'        => __( 'Grocery & Markets', 'all-purpose-directory' ),
						'description' => __( 'Food and grocery stores', 'all-purpose-directory' ),
						'icon'        => 'dashicons-carrot',
						'color'       => '#8BC34A',
					],
				],
			],
			'services'      => [
				'name'        => __( 'Services', 'all-purpose-directory' ),
				'description' => __( 'Local services and businesses', 'all-purpose-directory' ),
				'icon'        => 'dashicons-hammer',
				'color'       => '#795548',
				'children'    => [
					'auto-repair'   => [
						'name'        => __( 'Auto Repair', 'all-purpose-directory' ),
						'description' => __( 'Auto mechanics and repair shops', 'all-purpose-directory' ),
						'icon'        => 'dashicons-car',
						'color'       => '#607D8B',
					],
					'home-services' => [
						'name'        => __( 'Home Services', 'all-purpose-directory' ),
						'description' => __( 'Plumbers, electricians, contractors', 'all-purpose-directory' ),
						'icon'        => 'dashicons-admin-tools',
						'color'       => '#CDDC39',
					],
					'professional'  => [
						'name'        => __( 'Professional Services', 'all-purpose-directory' ),
						'description' => __( 'Legal, accounting, consulting', 'all-purpose-directory' ),
						'icon'        => 'dashicons-businessperson',
						'color'       => '#455A64',
					],
				],
			],
			'entertainment' => [
				'name'        => __( 'Entertainment', 'all-purpose-directory' ),
				'description' => __( 'Fun and leisure activities', 'all-purpose-directory' ),
				'icon'        => 'dashicons-tickets-alt',
				'color'       => '#9C27B0',
				'children'    => [
					'nightlife'         => [
						'name'        => __( 'Nightlife', 'all-purpose-directory' ),
						'description' => __( 'Bars, clubs, nightlife venues', 'all-purpose-directory' ),
						'icon'        => 'dashicons-drumstick',
						'color'       => '#311B92',
					],
					'movies-theater'    => [
						'name'        => __( 'Movies & Theater', 'all-purpose-directory' ),
						'description' => __( 'Cinemas and performing arts', 'all-purpose-directory' ),
						'icon'        => 'dashicons-video-alt3',
						'color'       => '#B71C1C',
					],
					'sports-recreation' => [
						'name'        => __( 'Sports & Recreation', 'all-purpose-directory' ),
						'description' => __( 'Gyms, sports facilities, parks', 'all-purpose-directory' ),
						'icon'        => 'dashicons-universal-access',
						'color'       => '#1B5E20',
					],
				],
			],
			'healthcare'    => [
				'name'        => __( 'Healthcare', 'all-purpose-directory' ),
				'description' => __( 'Medical and health services', 'all-purpose-directory' ),
				'icon'        => 'dashicons-heart',
				'color'       => '#F44336',
				'children'    => [
					'doctors'    => [
						'name'        => __( 'Doctors & Clinics', 'all-purpose-directory' ),
						'description' => __( 'Medical doctors and clinics', 'all-purpose-directory' ),
						'icon'        => 'dashicons-heart',
						'color'       => '#C62828',
					],
					'dentists'   => [
						'name'        => __( 'Dentists', 'all-purpose-directory' ),
						'description' => __( 'Dental care providers', 'all-purpose-directory' ),
						'icon'        => 'dashicons-smiley',
						'color'       => '#00ACC1',
					],
					'pharmacies' => [
						'name'        => __( 'Pharmacies', 'all-purpose-directory' ),
						'description' => __( 'Pharmacies and drug stores', 'all-purpose-directory' ),
						'icon'        => 'dashicons-plus-alt',
						'color'       => '#43A047',
					],
				],
			],
		];

		/**
		 * Filter the demo category hierarchy data.
		 *
		 * @since 1.0.0
		 *
		 * @param array $categories Category hierarchy.
		 */
		return apply_filters( 'apd_demo_category_data', $categories );
	}

	/**
	 * Get flat list of all category slugs.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $include_parents Whether to include parent categories.
	 * @return string[]
	 */
	public static function get_category_slugs( bool $include_parents = true ): array {
		$categories = self::get_categories();
		$slugs      = [];

		foreach ( $categories as $slug => $category ) {
			if ( $include_parents ) {
				$slugs[] = $slug;
			}

			if ( ! empty( $category['children'] ) ) {
				$slugs = array_merge( $slugs, array_keys( $category['children'] ) );
			}
		}

		return $slugs;
	}

	/**
	 * Get random category slugs for a listing.
	 *
	 * @since 1.0.0
	 *
	 * @param int $count Number of categories to return (1-3).
	 * @return string[]
	 */
	public static function get_random_category_slugs( int $count = 1 ): array {
		$slugs = self::get_category_slugs( false ); // Only child categories.
		shuffle( $slugs );

		return array_slice( $slugs, 0, min( $count, count( $slugs ) ) );
	}

	/**
	 * Get the parent slug for a child category.
	 *
	 * @since 1.0.0
	 *
	 * @param string $child_slug Child category slug.
	 * @return string|null Parent slug or null if not found.
	 */
	public static function get_parent_slug( string $child_slug ): ?string {
		$categories = self::get_categories();

		foreach ( $categories as $parent_slug => $category ) {
			if ( ! empty( $category['children'] ) && isset( $category['children'][ $child_slug ] ) ) {
				return $parent_slug;
			}
		}

		return null;
	}
}
