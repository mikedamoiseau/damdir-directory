<?php
/**
 * Address Data Set for Demo Data.
 *
 * Provides realistic address generation for listings.
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
 * Class Addresses
 *
 * Generates realistic US addresses for demo listings.
 *
 * @since 1.0.0
 */
final class Addresses {

	/**
	 * Street number range.
	 *
	 * @var int[]
	 */
	private static array $street_numbers = [ 100, 9999 ];

	/**
	 * Street names.
	 *
	 * @var string[]
	 */
	private static array $street_names = [
		'Main',
		'Oak',
		'Maple',
		'Cedar',
		'Pine',
		'Elm',
		'Park',
		'Washington',
		'Lincoln',
		'Jefferson',
		'Madison',
		'Franklin',
		'Adams',
		'Central',
		'Market',
		'Church',
		'Mill',
		'River',
		'Lake',
		'Spring',
		'Highland',
		'Summit',
		'Valley',
		'Mountain',
		'Sunset',
		'Sunrise',
		'Harbor',
		'Bay',
		'Ocean',
		'Beach',
		'Forest',
		'Meadow',
		'Garden',
		'Orchard',
		'Willow',
		'Birch',
		'Walnut',
		'Chestnut',
		'Cherry',
		'Poplar',
		'Commerce',
		'Industrial',
		'Business',
		'Corporate',
		'Tech',
		'Innovation',
	];

	/**
	 * Street types.
	 *
	 * @var string[]
	 */
	private static array $street_types = [
		'Street',
		'Avenue',
		'Boulevard',
		'Drive',
		'Road',
		'Lane',
		'Way',
		'Court',
		'Place',
		'Circle',
		'Terrace',
		'Parkway',
		'Highway',
	];

	/**
	 * Suite/unit prefixes.
	 *
	 * @var string[]
	 */
	private static array $suite_prefixes = [
		'Suite',
		'Unit',
		'#',
		'Apt',
		'Floor',
	];

	/**
	 * Cities with states.
	 *
	 * @var array<string, string>
	 */
	private static array $cities = [
		'Los Angeles'   => 'CA',
		'San Francisco' => 'CA',
		'San Diego'     => 'CA',
		'Sacramento'    => 'CA',
		'San Jose'      => 'CA',
		'Fresno'        => 'CA',
		'Oakland'       => 'CA',
		'New York'      => 'NY',
		'Brooklyn'      => 'NY',
		'Manhattan'     => 'NY',
		'Buffalo'       => 'NY',
		'Albany'        => 'NY',
		'Houston'       => 'TX',
		'Dallas'        => 'TX',
		'Austin'        => 'TX',
		'San Antonio'   => 'TX',
		'Fort Worth'    => 'TX',
		'Chicago'       => 'IL',
		'Springfield'   => 'IL',
		'Naperville'    => 'IL',
		'Phoenix'       => 'AZ',
		'Tucson'        => 'AZ',
		'Scottsdale'    => 'AZ',
		'Seattle'       => 'WA',
		'Tacoma'        => 'WA',
		'Spokane'       => 'WA',
		'Denver'        => 'CO',
		'Boulder'       => 'CO',
		'Aurora'        => 'CO',
		'Miami'         => 'FL',
		'Orlando'       => 'FL',
		'Tampa'         => 'FL',
		'Jacksonville'  => 'FL',
		'Atlanta'       => 'GA',
		'Savannah'      => 'GA',
		'Boston'        => 'MA',
		'Cambridge'     => 'MA',
		'Portland'      => 'OR',
		'Eugene'        => 'OR',
		'Las Vegas'     => 'NV',
		'Reno'          => 'NV',
		'Nashville'     => 'TN',
		'Memphis'       => 'TN',
		'Charlotte'     => 'NC',
		'Raleigh'       => 'NC',
		'Minneapolis'   => 'MN',
		'St. Paul'      => 'MN',
		'Detroit'       => 'MI',
		'Ann Arbor'     => 'MI',
		'Philadelphia'  => 'PA',
		'Pittsburgh'    => 'PA',
	];

	/**
	 * State to zip code prefix mapping.
	 *
	 * @var array<string, string[]>
	 */
	private static array $state_zips = [
		'CA' => [ '90', '91', '92', '93', '94', '95', '96' ],
		'NY' => [ '10', '11', '12', '13', '14' ],
		'TX' => [ '75', '76', '77', '78', '79' ],
		'IL' => [ '60', '61', '62' ],
		'AZ' => [ '85', '86' ],
		'WA' => [ '98', '99' ],
		'CO' => [ '80', '81' ],
		'FL' => [ '32', '33', '34' ],
		'GA' => [ '30', '31' ],
		'MA' => [ '01', '02' ],
		'OR' => [ '97' ],
		'NV' => [ '89' ],
		'TN' => [ '37', '38' ],
		'NC' => [ '27', '28' ],
		'MN' => [ '55', '56' ],
		'MI' => [ '48', '49' ],
		'PA' => [ '15', '16', '17', '18', '19' ],
	];

	/**
	 * Phone area codes by state.
	 *
	 * @var array<string, string[]>
	 */
	private static array $area_codes = [
		'CA' => [ '213', '310', '323', '408', '415', '510', '619', '714', '818', '916' ],
		'NY' => [ '212', '347', '516', '518', '585', '607', '718', '914' ],
		'TX' => [ '214', '281', '512', '713', '817', '832', '972' ],
		'IL' => [ '312', '630', '708', '773', '815', '847' ],
		'AZ' => [ '480', '520', '602', '623' ],
		'WA' => [ '206', '253', '360', '425', '509' ],
		'CO' => [ '303', '719', '720' ],
		'FL' => [ '305', '321', '352', '407', '561', '813', '904', '941' ],
		'GA' => [ '404', '470', '678', '706', '770', '912' ],
		'MA' => [ '339', '413', '508', '617', '774', '781', '857' ],
		'OR' => [ '503', '541', '971' ],
		'NV' => [ '702', '725', '775' ],
		'TN' => [ '423', '615', '629', '731', '865', '901' ],
		'NC' => [ '336', '704', '828', '910', '919', '980' ],
		'MN' => [ '218', '320', '507', '612', '651', '763', '952' ],
		'MI' => [ '231', '248', '269', '313', '517', '586', '616', '734', '810', '906', '947' ],
		'PA' => [ '215', '267', '412', '484', '570', '610', '717', '724', '814', '878' ],
	];

	/**
	 * Neighborhoods/districts for larger context.
	 *
	 * @var string[]
	 */
	private static array $neighborhoods = [
		'Downtown',
		'Midtown',
		'Uptown',
		'Eastside',
		'Westside',
		'Northside',
		'Southside',
		'Old Town',
		'Historic District',
		'Arts District',
		'Financial District',
		'Tech District',
		'Entertainment District',
		'Medical Center',
		'University Area',
		'Waterfront',
		'Harbor Area',
		'Industrial District',
		'Suburban Village',
		'Shopping District',
	];

	/**
	 * Generate a complete address.
	 *
	 * @since 1.0.0
	 *
	 * @return array{street: string, city: string, state: string, zip: string, phone: string, neighborhood: string}
	 */
	public static function generate(): array {
		// Pick a random city.
		$city  = array_rand( self::$cities );
		$state = self::$cities[ $city ];

		return [
			'street'       => self::generate_street_address(),
			'city'         => $city,
			'state'        => $state,
			'zip'          => self::generate_zip( $state ),
			'phone'        => self::generate_phone( $state ),
			'neighborhood' => self::$neighborhoods[ array_rand( self::$neighborhoods ) ],
		];
	}

	/**
	 * Generate a street address.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $include_suite Whether to sometimes include suite numbers.
	 * @return string
	 */
	public static function generate_street_address( bool $include_suite = true ): string {
		$number = wp_rand( self::$street_numbers[0], self::$street_numbers[1] );
		$street = self::$street_names[ array_rand( self::$street_names ) ];
		$type   = self::$street_types[ array_rand( self::$street_types ) ];

		$address = "{$number} {$street} {$type}";

		// Optionally add suite/unit.
		if ( $include_suite && wp_rand( 0, 3 ) === 0 ) {
			$prefix    = self::$suite_prefixes[ array_rand( self::$suite_prefixes ) ];
			$suite_num = wp_rand( 1, 500 );
			$address  .= ", {$prefix} {$suite_num}";
		}

		return $address;
	}

	/**
	 * Generate a ZIP code for a state.
	 *
	 * @since 1.0.0
	 *
	 * @param string $state State abbreviation.
	 * @return string
	 */
	public static function generate_zip( string $state ): string {
		$prefixes = self::$state_zips[ $state ] ?? [ '00' ];
		$prefix   = $prefixes[ array_rand( $prefixes ) ];
		$suffix   = str_pad( (string) wp_rand( 0, 999 ), 3, '0', STR_PAD_LEFT );

		return $prefix . $suffix;
	}

	/**
	 * Generate a phone number for a state.
	 *
	 * @since 1.0.0
	 *
	 * @param string $state State abbreviation.
	 * @return string
	 */
	public static function generate_phone( string $state ): string {
		$area_codes = self::$area_codes[ $state ] ?? [ '555' ];
		$area_code  = $area_codes[ array_rand( $area_codes ) ];
		$exchange   = str_pad( (string) wp_rand( 200, 999 ), 3, '0', STR_PAD_LEFT );
		$subscriber = str_pad( (string) wp_rand( 0, 9999 ), 4, '0', STR_PAD_LEFT );

		return "({$area_code}) {$exchange}-{$subscriber}";
	}

	/**
	 * Generate a website URL based on business name.
	 *
	 * @since 1.0.0
	 *
	 * @param string $business_name Business name.
	 * @return string
	 */
	public static function generate_website( string $business_name ): string {
		// Clean and slugify the business name.
		$slug = sanitize_title( $business_name );
		$slug = preg_replace( '/[^a-z0-9]+/', '', strtolower( $slug ) );

		// Truncate if too long.
		if ( strlen( $slug ) > 20 ) {
			$slug = substr( $slug, 0, 20 );
		}

		return "https://{$slug}.example.com";
	}

	/**
	 * Generate a business email based on business name.
	 *
	 * @since 1.0.0
	 *
	 * @param string $business_name Business name.
	 * @param string $prefix        Email prefix (e.g., 'info', 'contact', 'hello').
	 * @return string
	 */
	public static function generate_email( string $business_name, string $prefix = '' ): string {
		// Clean and slugify the business name.
		$slug = sanitize_title( $business_name );
		$slug = preg_replace( '/[^a-z0-9]+/', '', strtolower( $slug ) );

		// Truncate if too long.
		if ( strlen( $slug ) > 20 ) {
			$slug = substr( $slug, 0, 20 );
		}

		// Choose a prefix.
		$prefixes = [ 'info', 'contact', 'hello', 'support', 'mail' ];
		if ( empty( $prefix ) ) {
			$prefix = $prefixes[ array_rand( $prefixes ) ];
		}

		return "{$prefix}@{$slug}.example.com";
	}

	/**
	 * Get business hours based on category.
	 *
	 * @since 1.0.0
	 *
	 * @param string $category_slug Category slug.
	 * @return string
	 */
	public static function get_hours( string $category_slug ): string {
		$hours = [
			'cafes-coffee'      => 'Mon-Fri: 6am-6pm, Sat-Sun: 7am-5pm',
			'fine-dining'       => 'Tue-Sun: 5pm-10pm',
			'fast-food'         => 'Daily: 10am-11pm',
			'restaurants'       => 'Mon-Sat: 11am-10pm, Sun: 11am-9pm',
			'hotels'            => '24/7 Front Desk',
			'bed-breakfast'     => 'Check-in: 3pm, Check-out: 11am',
			'vacation-rentals'  => 'Check-in: 4pm, Check-out: 10am',
			'clothing'          => 'Mon-Sat: 10am-8pm, Sun: 12pm-6pm',
			'electronics'       => 'Daily: 9am-9pm',
			'grocery'           => 'Daily: 7am-10pm',
			'auto-repair'       => 'Mon-Fri: 7am-6pm, Sat: 8am-4pm',
			'home-services'     => 'Mon-Fri: 8am-5pm',
			'professional'      => 'Mon-Fri: 9am-5pm',
			'nightlife'         => 'Thu-Sat: 10pm-4am',
			'movies-theater'    => 'Daily: 11am-12am',
			'sports-recreation' => 'Open 24 Hours',
			'doctors'           => 'Mon-Fri: 8am-5pm',
			'dentists'          => 'Mon-Fri: 8am-5pm',
			'pharmacies'        => 'Mon-Sat: 8am-9pm, Sun: 10am-6pm',
		];

		return $hours[ $category_slug ] ?? 'Mon-Fri: 9am-5pm';
	}

	/**
	 * Get price range for category.
	 *
	 * @since 1.0.0
	 *
	 * @param string $category_slug Category slug.
	 * @return string
	 */
	public static function get_price_range( string $category_slug ): string {
		$ranges = [
			'fine-dining'   => '$$$$',
			'hotels'        => [ '$$$', '$$$$' ],
			'professional'  => [ '$$$', '$$$$' ],
			'bed-breakfast' => '$$$',
			'fast-food'     => '$',
			'grocery'       => '$$',
		];

		if ( isset( $ranges[ $category_slug ] ) ) {
			$range = $ranges[ $category_slug ];
			if ( is_array( $range ) ) {
				return $range[ array_rand( $range ) ];
			}
			return $range;
		}

		// Random for others, weighted toward middle.
		$options = [ '$', '$$', '$$', '$$$', '$$$', '$$$$' ];
		return $options[ array_rand( $options ) ];
	}
}
