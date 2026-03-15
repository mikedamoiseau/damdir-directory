<?php
/**
 * Business Names Data Set for Demo Data.
 *
 * Provides realistic business name generation.
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
 * Class BusinessNames
 *
 * Generates realistic business names based on category.
 *
 * @since 1.0.0
 */
final class BusinessNames {

	/**
	 * Generic prefixes that work for most business types.
	 *
	 * @var string[]
	 */
	private static array $generic_prefixes = [
		'Golden',
		'Grand',
		'Downtown',
		'Premier',
		'Elite',
		'Classic',
		'Modern',
		'Central',
		'Metro',
		'Royal',
		'Sunrise',
		'Sunset',
		'Pacific',
		'Atlantic',
		'Mountain',
		'Valley',
		'Riverside',
		'Lakeside',
		'Hillside',
		'Parkview',
		'Evergreen',
		'Oak',
		'Maple',
		'Cedar',
		'Pine',
		'First',
		'Prime',
		'Select',
		'Quality',
		'Express',
	];

	/**
	 * Category-specific prefixes.
	 *
	 * @var array<string, string[]>
	 */
	private static array $category_prefixes = [
		'restaurants'       => [ 'Taste of', 'The', 'Chef\'s', 'Mama\'s', 'Papa\'s', 'Little', 'Big', 'Hungry', 'Fresh' ],
		'cafes-coffee'      => [ 'Morning', 'Daily', 'Bean', 'Brew', 'Roast', 'Cup', 'Sip', 'Perk', 'Java' ],
		'fine-dining'       => [ 'The', 'Maison', 'Le', 'La', 'Casa', 'Chateau', 'Villa', 'Bistro', 'Chez' ],
		'fast-food'         => [ 'Quick', 'Speedy', 'Fast', 'Super', 'Mega', 'Jumbo', 'Big', 'Lucky' ],
		'hotels'            => [ 'Grand', 'Royal', 'Regal', 'Imperial', 'Majestic', 'Luxury', 'Palace', 'Heritage' ],
		'bed-breakfast'     => [ 'Cozy', 'Charming', 'Quaint', 'Historic', 'Victorian', 'Country', 'Garden', 'Rosewood' ],
		'vacation-rentals'  => [ 'Beach', 'Ocean', 'Sea', 'Lake', 'Mountain', 'Vista', 'Paradise', 'Haven' ],
		'shopping'          => [ 'Super', 'Mega', 'Best', 'Top', 'Value', 'Discount', 'Smart', 'Plus' ],
		'clothing'          => [ 'Fashion', 'Style', 'Urban', 'Chic', 'Trendy', 'Modern', 'Classic', 'Vogue' ],
		'electronics'       => [ 'Tech', 'Digital', 'Smart', 'Cyber', 'Electro', 'Gadget', 'Power', 'Pro' ],
		'grocery'           => [ 'Fresh', 'Organic', 'Natural', 'Farm', 'Market', 'Green', 'Healthy', 'Local' ],
		'services'          => [ 'Pro', 'Expert', 'Master', 'Premier', 'First', 'Best', 'Top', 'Reliable' ],
		'auto-repair'       => [ 'Precision', 'Master', 'Expert', 'Quick', 'Pro', 'Speedy', 'Ace', 'Top' ],
		'home-services'     => [ 'Handy', 'Quick', 'Pro', 'Express', 'Reliable', 'Quality', 'Premier', 'Expert' ],
		'professional'      => [ 'Smith &', 'Johnson', 'Anderson', 'Williams', 'Brown', 'Davis', 'Miller', 'Taylor' ],
		'entertainment'     => [ 'Fun', 'Star', 'Neon', 'Cosmic', 'Galaxy', 'Nova', 'Luna', 'Solar' ],
		'nightlife'         => [ 'Neon', 'Velvet', 'Midnight', 'Electric', 'Club', 'Pulse', 'Vibe', 'Groove' ],
		'movies-theater'    => [ 'Star', 'Cinema', 'Regal', 'Palace', 'Starlight', 'Galaxy', 'Silver', 'Golden' ],
		'sports-recreation' => [ 'Fit', 'Peak', 'Power', 'Active', 'Champion', 'Victory', 'Elite', 'Pro' ],
		'healthcare'        => [ 'City', 'Community', 'Family', 'Care', 'Wellness', 'Health', 'Life', 'Premier' ],
		'doctors'           => [ 'City', 'Family', 'Community', 'Central', 'Wellness', 'Care', 'Metro', 'Premier' ],
		'dentists'          => [ 'Bright', 'Smile', 'Happy', 'Gentle', 'Family', 'Perfect', 'Radiant', 'Sparkling' ],
		'pharmacies'        => [ 'Community', 'Family', 'Care', 'Health', 'Wellness', 'Local', 'Trusted', 'Reliable' ],
	];

	/**
	 * Category-specific suffixes.
	 *
	 * @var array<string, string[]>
	 */
	private static array $category_suffixes = [
		'restaurants'       => [ 'Kitchen', 'Grill', 'Diner', 'Eatery', 'Table', 'Place', 'Bistro', 'Cafe', 'Restaurant' ],
		'cafes-coffee'      => [ 'Cafe', 'Coffee', 'Roasters', 'House', 'Beans', 'Brew', 'Corner', 'Co.', 'Coffee House' ],
		'fine-dining'       => [ 'Restaurant', 'Dining', 'Table', 'Room', 'Kitchen', '', 'Supper Club', 'Brasserie' ],
		'fast-food'         => [ 'Bites', 'Burgers', 'Express', 'Stop', 'To Go', 'Drive-In', 'Shack', 'Joint' ],
		'hotels'            => [ 'Hotel', 'Inn', 'Suites', 'Resort', 'Lodge', 'Plaza', 'Place', 'Tower' ],
		'bed-breakfast'     => [ 'B&B', 'Inn', 'House', 'Manor', 'Cottage', 'Lodge', 'Retreat', 'Stay' ],
		'vacation-rentals'  => [ 'Condo', 'Villa', 'Retreat', 'Getaway', 'Escape', 'House', 'Cottage', 'Rental' ],
		'shopping'          => [ 'Store', 'Market', 'Mart', 'Center', 'Outlet', 'Shop', 'Emporium', 'Warehouse' ],
		'clothing'          => [ 'Boutique', 'Fashion', 'Apparel', 'Clothing', 'Style', 'Wear', 'Thread', 'Wardrobe' ],
		'electronics'       => [ 'Zone', 'World', 'Hub', 'Store', 'Shop', 'Tech', 'Center', 'Electronics' ],
		'grocery'           => [ 'Market', 'Grocery', 'Foods', 'Farm', 'Store', 'Produce', 'Mart', 'Co-op' ],
		'services'          => [ 'Services', 'Solutions', 'Co.', 'Group', 'Pros', 'Team', 'Associates', 'LLC' ],
		'auto-repair'       => [ 'Auto Care', 'Garage', 'Auto Shop', 'Mechanics', 'Auto Service', 'Motor Works', 'Auto Repair' ],
		'home-services'     => [ 'Plumbing', 'Electric', 'Handyman', 'Services', 'Repair', 'Maintenance', 'Fix-It', 'Solutions' ],
		'professional'      => [ 'Associates', 'Law Firm', 'Consulting', 'Group', 'Partners', 'Advisors', '& Associates', 'LLP' ],
		'entertainment'     => [ 'Entertainment', 'Fun Zone', 'Center', 'World', 'Park', 'Arena', 'Plaza', 'Experience' ],
		'nightlife'         => [ 'Lounge', 'Club', 'Bar', 'Night Club', 'Nightclub', 'Taproom', 'Pub', 'Speakeasy' ],
		'movies-theater'    => [ 'Cinema', 'Theater', 'Cinemas', 'Theatre', 'Multiplex', 'Pictures', 'Movies', 'Screen' ],
		'sports-recreation' => [ 'Gym', 'Fitness', 'Sports Center', 'Athletic Club', 'Wellness', 'Training', 'CrossFit' ],
		'healthcare'        => [ 'Medical', 'Clinic', 'Health Center', 'Medical Center', 'Healthcare', 'Care Center', 'Wellness' ],
		'doctors'           => [ 'Medical Clinic', 'Clinic', 'Family Practice', 'Medical Group', 'Health', 'Medicine', 'Care' ],
		'dentists'          => [ 'Dental', 'Dentistry', 'Dental Care', 'Dental Center', 'Dental Group', 'Family Dental', 'Dental Clinic' ],
		'pharmacies'        => [ 'Pharmacy', 'Drug', 'Rx', 'Drugstore', 'Apothecary', 'Prescriptions', 'Drugs' ],
	];

	/**
	 * Names to avoid repetition.
	 *
	 * @var string[]
	 */
	private static array $used_names = [];

	/**
	 * Generate a business name for a category.
	 *
	 * @since 1.0.0
	 *
	 * @param string $category_slug Category slug.
	 * @return string Generated business name.
	 */
	public static function generate( string $category_slug ): string {
		$max_attempts = 20;
		$attempts     = 0;

		do {
			$name = self::build_name( $category_slug );
			++$attempts;
		} while ( in_array( $name, self::$used_names, true ) && $attempts < $max_attempts );

		self::$used_names[] = $name;

		return $name;
	}

	/**
	 * Build a single name.
	 *
	 * @since 1.0.0
	 *
	 * @param string $category_slug Category slug.
	 * @return string
	 */
	private static function build_name( string $category_slug ): string {
		// Get prefixes for this category or use generic.
		$prefixes = self::$category_prefixes[ $category_slug ] ?? self::$generic_prefixes;

		// Mix in some generic prefixes for variety.
		if ( wp_rand( 0, 2 ) === 0 ) {
			$prefixes = self::$generic_prefixes;
		}

		// Get suffixes for this category.
		$suffixes = self::$category_suffixes[ $category_slug ] ?? [ 'Place', 'Center', 'Shop', 'Store' ];

		$prefix = $prefixes[ array_rand( $prefixes ) ];
		$suffix = $suffixes[ array_rand( $suffixes ) ];

		// Clean up the name.
		$name = trim( "{$prefix} {$suffix}" );

		// Remove double spaces.
		$name = preg_replace( '/\s+/', ' ', $name );

		return $name;
	}

	/**
	 * Generate an excerpt/tagline for a business.
	 *
	 * @since 1.0.0
	 *
	 * @param string $category_slug Category slug.
	 * @return string
	 */
	public static function generate_tagline( string $category_slug ): string {
		$taglines = [
			'restaurants'       => [
				'Delicious food made with love',
				'Where great taste meets great service',
				'A culinary experience to remember',
				'Fresh ingredients, bold flavors',
				'Your new favorite dining spot',
			],
			'cafes-coffee'      => [
				'Artisan coffee and fresh pastries daily',
				'Your neighborhood coffee destination',
				'Craft coffee for discerning tastes',
				'Where every cup is crafted with care',
				'Start your day the right way',
			],
			'fine-dining'       => [
				'An unforgettable dining experience',
				'Where culinary art meets hospitality',
				'Excellence in every detail',
				'Award-winning cuisine',
				'The finest dining in the city',
			],
			'fast-food'         => [
				'Quick, delicious, and always fresh',
				'Fast food done right',
				'Great taste, fast service',
				'Your favorite meals, made fast',
				'Satisfying cravings since day one',
			],
			'hotels'            => [
				'Luxury accommodations for discerning travelers',
				'Your home away from home',
				'Where comfort meets elegance',
				'Experience hospitality at its finest',
				'Rest, relax, and rejuvenate',
			],
			'bed-breakfast'     => [
				'Charming accommodations with homemade breakfast',
				'A cozy retreat from everyday life',
				'Experience true hospitality',
				'Where every guest becomes family',
				'Your peaceful escape awaits',
			],
			'vacation-rentals'  => [
				'Your perfect vacation starts here',
				'Make memories in comfort and style',
				'Home comforts, vacation vibes',
				'Space to spread out and relax',
				'Live like a local on your getaway',
			],
			'clothing'          => [
				'Fashion for every occasion',
				'Curated styles for the modern wardrobe',
				'Where style meets quality',
				'Dress to impress',
				'Discover your signature look',
			],
			'electronics'       => [
				'The latest tech at the best prices',
				'Your one-stop tech shop',
				'Innovation meets affordability',
				'Expert advice, quality products',
				'Power your digital life',
			],
			'grocery'           => [
				'Fresh, local, and sustainable',
				'Quality groceries for your family',
				'Farm-fresh produce daily',
				'Healthy choices made easy',
				'Your neighborhood market',
			],
			'auto-repair'       => [
				'Honest, reliable auto repair',
				'Keeping you on the road safely',
				'Expert care for your vehicle',
				'Quality service you can trust',
				'Where quality meets reliability',
			],
			'home-services'     => [
				'Professional service, quality results',
				'Your trusted home improvement partner',
				'Fast, reliable, and professional',
				'No job too big or too small',
				'Quality workmanship guaranteed',
			],
			'professional'      => [
				'Expert solutions for your needs',
				'Professional service with a personal touch',
				'Excellence in every engagement',
				'Trusted advisors for over a decade',
				'Your success is our priority',
			],
			'nightlife'         => [
				'Where the night comes alive',
				'Your ultimate nightlife destination',
				'Dance, drink, and celebrate',
				'The party never stops',
				'Where memories are made',
			],
			'movies-theater'    => [
				'The ultimate movie experience',
				'See it on the big screen',
				'Entertainment for the whole family',
				'Where stories come to life',
				'Blockbusters and indie gems',
			],
			'sports-recreation' => [
				'Achieve your fitness goals',
				'Your fitness journey starts here',
				'Train hard, live strong',
				'Building stronger communities',
				'Excellence in fitness',
			],
			'doctors'           => [
				'Compassionate care for your whole family',
				'Your health is our priority',
				'Expert medical care you can trust',
				'Caring for our community',
				'Healthcare with a personal touch',
			],
			'dentists'          => [
				'Gentle dental care for all ages',
				'Creating beautiful, healthy smiles',
				'Your comfort is our priority',
				'Expert care, gentle touch',
				'Smile with confidence',
			],
			'pharmacies'        => [
				'Your trusted neighborhood pharmacy',
				'Personalized pharmaceutical care',
				'Caring for your health needs',
				'Expert advice, quality products',
				'Health and wellness for all',
			],
		];

		$category_taglines = $taglines[ $category_slug ] ?? [
			'Quality service you can count on',
			'Serving the community with pride',
			'Excellence in everything we do',
			'Your trusted local business',
			'Where quality meets value',
		];

		return $category_taglines[ array_rand( $category_taglines ) ];
	}

	/**
	 * Reset used names (for testing or new generation sessions).
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function reset(): void {
		self::$used_names = [];
	}
}
