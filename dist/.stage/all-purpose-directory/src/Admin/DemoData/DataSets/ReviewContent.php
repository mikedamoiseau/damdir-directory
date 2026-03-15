<?php
/**
 * Review Content Data Set for Demo Data.
 *
 * Provides realistic review content generation.
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
 * Class ReviewContent
 *
 * Generates realistic review titles and content by rating.
 *
 * @since 1.0.0
 */
final class ReviewContent {

	/**
	 * Review titles by rating.
	 *
	 * @var array<int, string[]>
	 */
	private static array $titles = [
		5 => [
			'Absolutely Amazing!',
			'Best Experience Ever',
			'Exceeded All Expectations',
			'Highly Recommended!',
			'Five Stars All the Way',
			'Outstanding in Every Way',
			'Couldn\'t Be Happier',
			'A Perfect Experience',
			'Top Notch Service',
			'Will Definitely Return',
			'Simply the Best',
			'Incredible Experience',
			'Beyond Expectations',
			'Truly Exceptional',
			'An Absolute Gem',
		],
		4 => [
			'Great Experience Overall',
			'Very Satisfied',
			'Highly Recommend',
			'Really Good',
			'Impressed',
			'Will Come Back',
			'Above Average',
			'Pleasantly Surprised',
			'Worth the Visit',
			'Very Good Service',
			'Quality Experience',
			'Quite Impressed',
			'Really Enjoyed It',
			'Solid Choice',
			'Would Recommend',
		],
		3 => [
			'Pretty Good',
			'Decent Experience',
			'Average Overall',
			'It Was Okay',
			'Not Bad',
			'Met Expectations',
			'Room for Improvement',
			'Mixed Feelings',
			'Some Good, Some Bad',
			'Middle of the Road',
			'Fair Experience',
			'Could Be Better',
			'Acceptable',
			'Mediocre',
			'Nothing Special',
		],
		2 => [
			'Disappointing',
			'Below Average',
			'Not Impressed',
			'Could Be Much Better',
			'Wouldn\'t Recommend',
			'Needs Improvement',
			'Subpar Experience',
			'Left Wanting More',
			'Not Worth It',
			'Fell Short',
			'Underwhelming',
			'Expected Better',
			'Unsatisfied',
			'Poor Experience',
			'Very Disappointing',
		],
		1 => [
			'Terrible Experience',
			'Avoid This Place',
			'Complete Waste',
			'Never Again',
			'Absolutely Awful',
			'The Worst',
			'Extremely Disappointed',
			'Save Your Money',
			'Horrible Service',
			'Total Disaster',
			'Would Give Zero Stars If Possible',
			'Stay Away',
			'Nightmare Experience',
			'Completely Unacceptable',
			'Regret Coming Here',
		],
	];

	/**
	 * Review content by rating.
	 *
	 * @var array<int, string[]>
	 */
	private static array $content = [
		5 => [
			'From the moment we arrived, we were treated like royalty. The staff went above and beyond to make our experience special. The quality is exceptional and the attention to detail is remarkable. I can\'t say enough good things about this place. We\'ll definitely be back and I\'m recommending it to everyone I know!',
			'This was truly an outstanding experience. Everything was perfect from start to finish. The team clearly takes pride in what they do and it shows in every aspect. I was thoroughly impressed and can\'t wait to return. If you\'re on the fence, just go - you won\'t regret it!',
			'Exceptional in every way! I\'ve tried many similar places but this one stands out from the rest. The service was impeccable, the quality was superb, and the overall experience exceeded my already high expectations. This is now my go-to recommendation.',
			'I don\'t usually write reviews but I had to make an exception for this gem. Everything was absolutely perfect. The staff remembered our preferences and made thoughtful suggestions. The level of care and attention here is truly rare. Five stars isn\'t enough!',
			'Simply the best! I\'ve been coming here for years and they consistently deliver excellence. Every single visit has been wonderful. The team is professional, friendly, and clearly passionate about what they do. This place is a treasure.',
		],
		4 => [
			'Really great experience overall. The service was excellent and the quality was high. There were a couple of minor things that could be improved, but honestly, I\'m nitpicking. I would definitely recommend this place and will be coming back. Very satisfied customer here!',
			'Very impressed with my visit. The staff was friendly and knowledgeable, and everything was handled professionally. The only reason I\'m not giving five stars is because of a small wait, but that\'s understandable. Overall, a great choice and I\'d happily return.',
			'Had a wonderful experience here. The attention to quality is evident and the team genuinely cares about customer satisfaction. One or two small improvements could make it perfect, but as it stands, it\'s definitely worth your time and money.',
			'Great place! I came in with high expectations and they were mostly met. The service was attentive and the results were impressive. A few small tweaks could elevate this to five-star territory, but I\'m very happy with my experience.',
			'Really solid experience from start to finish. Professional, efficient, and high quality. I appreciated the personal touches and the team\'s expertise. Would happily recommend to friends and family, and I\'ll definitely be returning.',
		],
		3 => [
			'It was an okay experience. Some things were good, some things could use improvement. The staff was nice enough but seemed a bit overwhelmed. I didn\'t have any major complaints, but I also wasn\'t blown away. It\'s fine for what it is.',
			'Pretty average overall. There wasn\'t anything particularly wrong, but nothing really stood out either. The quality was acceptable and the service was adequate. I might come back if I\'m in the area, but I wouldn\'t go out of my way.',
			'Mixed feelings about this place. Some aspects were quite good while others fell short of expectations. I feel like with a few changes, it could be much better. As it stands, it\'s just okay. Neither great nor terrible.',
			'Decent experience but room for improvement. The basics were fine but there was nothing memorable about the visit. The staff seemed indifferent at times. It\'s not bad, just not particularly good either. Average is the best way to describe it.',
			'It met my basic expectations, nothing more, nothing less. I wouldn\'t say I had a bad time, but I also wouldn\'t rave about it to friends. Some things worked well, others needed work. An okay choice if you don\'t have better options.',
		],
		2 => [
			'Unfortunately, I was disappointed with my experience. The quality wasn\'t what I expected based on the reviews and the price. The staff seemed disinterested and rushed. I had hoped for better and left feeling let down. Probably won\'t be returning.',
			'Below average experience. Several things went wrong and the staff didn\'t seem to care. I\'ve had much better experiences elsewhere for similar prices. It\'s a shame because the potential is there, but the execution was lacking.',
			'Not impressed at all. The service was slow and the quality was mediocre at best. When I raised concerns, they were dismissed. I expected much more based on what I\'d heard. Can\'t recommend this to others in good conscience.',
			'Disappointing visit. Multiple issues that weren\'t addressed properly. The team seemed overwhelmed and undertrained. I tried to give them the benefit of the doubt but left frustrated. There are better options out there.',
			'I really wanted to like this place but it fell short in too many ways. From poor communication to subpar quality, the experience was frustrating. Perhaps they were having an off day, but I\'m not eager to give them another chance.',
		],
		1 => [
			'This was a complete disaster from start to finish. Nothing went right and the staff was rude when we complained. I\'ve never experienced such poor service anywhere. Save yourself the trouble and go literally anywhere else. What a waste of time and money.',
			'Absolutely terrible experience. I can\'t believe they\'re still in business with this level of quality. Ignored by staff, overcharged, and the actual service/product was awful. I want my time back. Do not make the same mistake I did.',
			'The worst experience I\'ve ever had. Management was dismissive of legitimate complaints and the whole operation seemed like a scam. I left angry and frustrated. This place should be avoided at all costs. Genuinely shocking how bad it was.',
			'I regret ever coming here. Everything that could go wrong did go wrong. Staff was unprofessional, the quality was non-existent, and they didn\'t seem to care about making it right. Save your money and sanity - stay far away!',
			'Zero stars would be more accurate. An absolute nightmare experience. Rude staff, terrible quality, and they had the audacity to charge full price. I\'ve filed a complaint and will be warning everyone I know. Unacceptable on every level.',
		],
	];

	/**
	 * Generate review content for a rating.
	 *
	 * @since 1.0.0
	 *
	 * @param int $rating Rating (1-5).
	 * @return array{title: string, content: string}
	 */
	public static function generate( int $rating ): array {
		$rating = max( 1, min( 5, $rating ) );

		return [
			'title'   => self::$titles[ $rating ][ array_rand( self::$titles[ $rating ] ) ],
			'content' => self::$content[ $rating ][ array_rand( self::$content[ $rating ] ) ],
		];
	}

	/**
	 * Generate a random rating with weighted distribution.
	 *
	 * The distribution favors positive reviews (realistic for most businesses).
	 *
	 * @since 1.0.0
	 *
	 * @param string $bias Optional bias ('positive', 'negative', 'neutral'). Default 'positive'.
	 * @return int Rating 1-5.
	 */
	public static function generate_rating( string $bias = 'positive' ): int {
		// Define weight distributions.
		$distributions = [
			'positive' => [
				1 => 2,
				2 => 5,
				3 => 10,
				4 => 35,
				5 => 48,
			],   // Most reviews 4-5 stars.
			'negative' => [
				1 => 30,
				2 => 35,
				3 => 20,
				4 => 10,
				5 => 5,
			],  // Most reviews 1-2 stars.
			'neutral'  => [
				1 => 10,
				2 => 15,
				3 => 50,
				4 => 15,
				5 => 10,
			], // Most reviews 3 stars.
			'mixed'    => [
				1 => 15,
				2 => 15,
				3 => 20,
				4 => 25,
				5 => 25,
			], // Even distribution.
		];

		$weights = $distributions[ $bias ] ?? $distributions['positive'];

		// Create weighted random selection.
		$total      = array_sum( $weights );
		$random     = wp_rand( 1, $total );
		$cumulative = 0;

		foreach ( $weights as $rating => $weight ) {
			$cumulative += $weight;
			if ( $random <= $cumulative ) {
				return $rating;
			}
		}

		return 4; // Fallback.
	}

	/**
	 * Generate a review date within a given range.
	 *
	 * @since 1.0.0
	 *
	 * @param int $days_ago_max Maximum days ago for the review.
	 * @param int $days_ago_min Minimum days ago for the review.
	 * @return string Date in MySQL format.
	 */
	public static function generate_date( int $days_ago_max = 365, int $days_ago_min = 1 ): string {
		$days_ago  = wp_rand( $days_ago_min, $days_ago_max );
		$timestamp = strtotime( "-{$days_ago} days" );

		// Add random time component.
		$hours   = wp_rand( 8, 22 ); // Business-ish hours.
		$minutes = wp_rand( 0, 59 );
		$seconds = wp_rand( 0, 59 );

		$date = gmdate( 'Y-m-d', $timestamp );
		$time = sprintf( '%02d:%02d:%02d', $hours, $minutes, $seconds );

		return "{$date} {$time}";
	}

	/**
	 * Generate reviewer name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function generate_reviewer_name(): string {
		$first_names = [
			'James',
			'John',
			'Robert',
			'Michael',
			'William',
			'David',
			'Richard',
			'Joseph',
			'Thomas',
			'Charles',
			'Mary',
			'Patricia',
			'Jennifer',
			'Linda',
			'Elizabeth',
			'Barbara',
			'Susan',
			'Jessica',
			'Sarah',
			'Karen',
			'Emma',
			'Olivia',
			'Ava',
			'Isabella',
			'Sophia',
			'Mia',
			'Charlotte',
			'Amelia',
			'Harper',
			'Evelyn',
			'Liam',
			'Noah',
			'Oliver',
			'Elijah',
			'Lucas',
			'Mason',
			'Logan',
			'Alexander',
			'Ethan',
			'Jacob',
			'Michael',
			'Daniel',
			'Henry',
			'Jackson',
			'Sebastian',
		];

		$last_initials = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

		$first_name   = $first_names[ array_rand( $first_names ) ];
		$last_initial = $last_initials[ wp_rand( 0, strlen( $last_initials ) - 1 ) ];

		return "{$first_name} {$last_initial}.";
	}
}
