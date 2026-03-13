<?php
/**
 * WordPress Privacy API integration.
 *
 * Registers privacy policy content, personal data exporter,
 * and personal data eraser for GDPR compliance.
 *
 * @package APD\Core
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Core;

use APD\Contact\InquiryTracker;
use APD\Frontend\Dashboard\Profile;
use APD\Listing\PostType;
use APD\Review\RatingCalculator;
use APD\Review\ReviewManager;
use APD\User\Favorites;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Privacy
 *
 * Handles WordPress Privacy API integration: policy content,
 * personal data export, and personal data erasure.
 *
 * @since 1.0.0
 */
class Privacy {

	/**
	 * Items processed per pagination page.
	 *
	 * @var int
	 */
	private const BATCH_SIZE = 10;

	/**
	 * Exporter/eraser identifier registered with WordPress.
	 *
	 * @var string
	 */
	private const HANDLER_NAME = 'all-purpose-directory';

	/**
	 * Profile meta keys exported and erased.
	 *
	 * Does not include _apd_favorites (handled separately via Favorites::clear).
	 *
	 * @var string[]
	 */
	private const PROFILE_META_KEYS = [
		'_apd_phone',
		'_apd_avatar',
		'_apd_favorites_view_mode',
	];

	/**
	 * Listing contact meta keys exported and anonymized on erase.
	 *
	 * @var string[]
	 */
	private const LISTING_CONTACT_META_KEYS = [
		'_apd_phone',
		'_apd_email',
		'_apd_website',
		'_apd_address',
		'_apd_city',
		'_apd_state',
		'_apd_zip',
		'_apd_hours',
		'_apd_price_range',
	];

	/**
	 * Register WordPress hooks.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'admin_init', [ $this, 'add_policy_content' ] );
		add_filter( 'wp_privacy_personal_data_exporters', [ $this, 'register_exporter' ] );
		add_filter( 'wp_privacy_personal_data_erasers', [ $this, 'register_eraser' ] );
	}

	/**
	 * Register the personal data exporter.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, array<string, mixed>> $exporters Registered exporters.
	 * @return array<string, array<string, mixed>>
	 */
	public function register_exporter( array $exporters ): array {
		$exporters[ self::HANDLER_NAME ] = [
			'exporter_friendly_name' => __( 'All Purpose Directory', 'all-purpose-directory' ),
			'callback'               => [ $this, 'export_user_data' ],
		];
		return $exporters;
	}

	/**
	 * Register the personal data eraser.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, array<string, mixed>> $erasers Registered erasers.
	 * @return array<string, array<string, mixed>>
	 */
	public function register_eraser( array $erasers ): array {
		$erasers[ self::HANDLER_NAME ] = [
			'eraser_friendly_name' => __( 'All Purpose Directory', 'all-purpose-directory' ),
			'callback'             => [ $this, 'erase_user_data' ],
		];
		return $erasers;
	}

	/**
	 * Add suggested privacy policy content.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_policy_content(): void {
		$content  = '<h2>' . esc_html__( 'Directory Listings', 'all-purpose-directory' ) . '</h2>';
		$content .= '<p>' . esc_html__( 'When you submit a listing, we store your listing content along with any contact information you provide, including email address, phone number, physical address, and website URL. This information is stored as part of your listing and may be publicly visible.', 'all-purpose-directory' ) . '</p>';

		$content .= '<h2>' . esc_html__( 'Reviews', 'all-purpose-directory' ) . '</h2>';
		$content .= '<p>' . esc_html__( 'When you submit a review, we store your name, email address, star rating, review title, and review content associated with your account. Approved reviews are displayed publicly on the listing page.', 'all-purpose-directory' ) . '</p>';

		$content .= '<h2>' . esc_html__( 'Contact Inquiries', 'all-purpose-directory' ) . '</h2>';
		$content .= '<p>' . esc_html__( 'When you send a contact inquiry through a listing, we store your name, email address, phone number, and message. This data is retained so that listing owners can manage and respond to inquiries.', 'all-purpose-directory' ) . '</p>';

		$content .= '<h2>' . esc_html__( 'Favorites', 'all-purpose-directory' ) . '</h2>';
		$content .= '<p>' . esc_html__( 'When you mark listings as favorites while logged in, we store a list of those listing IDs in your user account. If guest favorites are enabled, we store this list in a browser cookie named "apd_guest_favorites" for up to 30 days.', 'all-purpose-directory' ) . '</p>';

		$content .= '<h2>' . esc_html__( 'Profile Data', 'all-purpose-directory' ) . '</h2>';
		$content .= '<p>' . esc_html__( 'When you update your directory profile, we may store additional information including your phone number, a profile avatar image, and links to your social media profiles (Facebook, Twitter, LinkedIn, Instagram).', 'all-purpose-directory' ) . '</p>';

		wp_add_privacy_policy_content(
			__( 'All Purpose Directory', 'all-purpose-directory' ),
			wp_kses_post( $content )
		);
	}

	/**
	 * Export personal data for a given email address.
	 *
	 * WordPress calls this repeatedly with an incrementing $page (1-based)
	 * until done === true is returned.
	 *
	 * @since 1.0.0
	 *
	 * @param string $email Email address.
	 * @param int    $page  Current page (1-based).
	 * @return array{ data: array<int, array<string, mixed>>, done: bool }
	 */
	public function export_user_data( string $email, int $page = 1 ): array {
		$user   = $this->resolve_user( $email );
		$data   = [];
		$done   = true;
		$offset = ( $page - 1 ) * self::BATCH_SIZE;

		// Profile data is a single record, export on page 1 only.
		if ( 1 === $page && $user instanceof \WP_User ) {
			$profile_result = $this->export_profile( $user );
			$data           = array_merge( $data, $profile_result['data'] );
		}

		if ( $user instanceof \WP_User ) {
			$listings_result = $this->export_listings( $user, $offset );
			$data            = array_merge( $data, $listings_result['data'] );
			if ( ! $listings_result['done'] ) {
				$done = false;
			}

			$reviews_result = $this->export_reviews_by_user( $user, $offset );
			$data           = array_merge( $data, $reviews_result['data'] );
			if ( ! $reviews_result['done'] ) {
				$done = false;
			}

			$received_result = $this->export_inquiries_received( $user, $offset );
			$data            = array_merge( $data, $received_result['data'] );
			if ( ! $received_result['done'] ) {
				$done = false;
			}
		}

		// Reviews and inquiries sent are matched by email (covers guest submissions).
		$reviews_email_result = $this->export_reviews_by_email( $email, $offset );
		$data                 = array_merge( $data, $reviews_email_result['data'] );
		if ( ! $reviews_email_result['done'] ) {
			$done = false;
		}

		$sent_result = $this->export_inquiries_sent( $email, $offset );
		$data        = array_merge( $data, $sent_result['data'] );
		if ( ! $sent_result['done'] ) {
			$done = false;
		}

		return [
			'data' => $data,
			'done' => $done,
		];
	}

	/**
	 * Erase personal data for a given email address.
	 *
	 * @since 1.0.0
	 *
	 * @param string $email Email address.
	 * @param int    $page  Current page (1-based).
	 * @return array{ items_removed: bool, items_retained: bool, messages: string[], done: bool }
	 */
	public function erase_user_data( string $email, int $page = 1 ): array {
		$user           = $this->resolve_user( $email );
		$items_removed  = false;
		$items_retained = false;
		$messages       = [];
		$done           = true;
		$offset         = ( $page - 1 ) * self::BATCH_SIZE;

		// Profile erasure on page 1 only (single record).
		if ( 1 === $page && $user instanceof \WP_User ) {
			$result         = $this->erase_profile( $user );
			$items_removed  = $items_removed || $result['items_removed'];
			$items_retained = $items_retained || $result['items_retained'];
			$messages       = array_merge( $messages, $result['messages'] );
		}

		if ( $user instanceof \WP_User ) {
			$listings_result = $this->erase_listings( $user, $offset );
			$items_removed   = $items_removed || $listings_result['items_removed'];
			$items_retained  = $items_retained || $listings_result['items_retained'];
			$messages        = array_merge( $messages, $listings_result['messages'] );
			if ( ! $listings_result['done'] ) {
				$done = false;
			}

			$received_result = $this->erase_inquiries_received( $user );
			$items_removed   = $items_removed || $received_result['items_removed'];
			$items_retained  = $items_retained || $received_result['items_retained'];
			$messages        = array_merge( $messages, $received_result['messages'] );
			if ( ! $received_result['done'] ) {
				$done = false;
			}
		}

		// Reviews matched by email (covers both registered and guest reviews).
		$reviews_result = $this->erase_reviews( $email, $user );
		$items_removed  = $items_removed || $reviews_result['items_removed'];
		$items_retained = $items_retained || $reviews_result['items_retained'];
		$messages       = array_merge( $messages, $reviews_result['messages'] );
		if ( ! $reviews_result['done'] ) {
			$done = false;
		}

		$sent_result    = $this->erase_inquiries_sent( $email, $offset );
		$items_removed  = $items_removed || $sent_result['items_removed'];
		$items_retained = $items_retained || $sent_result['items_retained'];
		$messages       = array_merge( $messages, $sent_result['messages'] );
		if ( ! $sent_result['done'] ) {
			$done = false;
		}

		return [
			'items_removed'  => $items_removed,
			'items_retained' => $items_retained,
			'messages'       => $messages,
			'done'           => $done,
		];
	}

	// -------------------------------------------------------------------------
	// Private: Export helpers
	// -------------------------------------------------------------------------

	/**
	 * Export profile meta for a user (always fits on one page).
	 *
	 * @param \WP_User $user User object.
	 * @return array{ data: array<int, array<string, mixed>>, done: bool }
	 */
	private function export_profile( \WP_User $user ): array {
		$profile_data = [];

		// Plugin-specific meta.
		$phone = get_user_meta( $user->ID, '_apd_phone', true );
		if ( '' !== $phone && false !== $phone ) {
			$profile_data[] = [
				'name'  => __( 'Phone', 'all-purpose-directory' ),
				'value' => $phone,
			];
		}

		$avatar_id = (int) get_user_meta( $user->ID, '_apd_avatar', true );
		if ( $avatar_id > 0 ) {
			$avatar_url     = wp_get_attachment_url( $avatar_id );
			$profile_data[] = [
				'name'  => __( 'Avatar', 'all-purpose-directory' ),
				'value' => $avatar_url ? $avatar_url : (string) $avatar_id,
			];
		}

		// Social links.
		foreach ( Profile::SOCIAL_PLATFORMS as $platform ) {
			$value = get_user_meta( $user->ID, "_apd_social_{$platform}", true );
			if ( ! empty( $value ) ) {
				$profile_data[] = [
					'name'  => sprintf(
						/* translators: %s: social platform name (e.g. Facebook, Twitter) */
						__( 'Social link (%s)', 'all-purpose-directory' ),
						ucfirst( $platform )
					),
					'value' => $value,
				];
			}
		}

		// Favorites.
		$favorites = get_user_meta( $user->ID, Favorites::META_KEY, true );
		if ( is_array( $favorites ) && ! empty( $favorites ) ) {
			$titles         = array_map(
				function ( int $id ): string {
					$title = get_the_title( $id );
					return $title ? $title : "#{$id}";
				},
				array_map( 'absint', $favorites )
			);
			$profile_data[] = [
				'name'  => __( 'Favorite listings', 'all-purpose-directory' ),
				'value' => implode( ', ', $titles ),
			];
		}

		$data = [];
		if ( ! empty( $profile_data ) ) {
			$data[] = [
				'group_id'    => 'apd-profile',
				'group_label' => __( 'Directory Profile', 'all-purpose-directory' ),
				'item_id'     => "apd-profile-{$user->ID}",
				'data'        => $profile_data,
			];
		}

		return [
			'data' => $data,
			'done' => true,
		];
	}

	/**
	 * Export listings authored by a user with pagination.
	 *
	 * @param \WP_User $user   User object.
	 * @param int      $offset Offset into results.
	 * @return array{ data: array<int, array<string, mixed>>, done: bool }
	 */
	private function export_listings( \WP_User $user, int $offset ): array {
		$posts = get_posts(
			[
				'post_type'      => PostType::POST_TYPE,
				'post_status'    => 'any',
				'author'         => $user->ID,
				'posts_per_page' => self::BATCH_SIZE,
				'offset'         => $offset,
				'orderby'        => 'ID',
				'order'          => 'ASC',
			]
		);

		$data = [];
		foreach ( $posts as $post ) {
			$item_data = [
				[
					'name'  => __( 'Title', 'all-purpose-directory' ),
					'value' => $post->post_title,
				],
				[
					'name'  => __( 'Status', 'all-purpose-directory' ),
					'value' => $post->post_status,
				],
				[
					'name'  => __( 'Date', 'all-purpose-directory' ),
					'value' => $post->post_date,
				],
				[
					'name'  => __( 'Content', 'all-purpose-directory' ),
					'value' => $post->post_content,
				],
			];

			foreach ( self::LISTING_CONTACT_META_KEYS as $meta_key ) {
				$value = get_post_meta( $post->ID, $meta_key, true );
				if ( '' !== $value && false !== $value ) {
					$label       = ucfirst( str_replace( [ '_apd_', '_' ], [ '', ' ' ], $meta_key ) );
					$item_data[] = [
						'name'  => $label,
						'value' => (string) $value,
					];
				}
			}

			$data[] = [
				'group_id'    => 'apd-listings',
				'group_label' => __( 'Directory Listings', 'all-purpose-directory' ),
				'item_id'     => "apd-listing-{$post->ID}",
				'data'        => $item_data,
			];
		}

		return [
			'data' => $data,
			'done' => count( $posts ) < self::BATCH_SIZE,
		];
	}

	/**
	 * Export reviews authored by user ID (logged-in reviews).
	 *
	 * @param \WP_User $user   User object.
	 * @param int      $offset Offset into results.
	 * @return array{ data: array<int, array<string, mixed>>, done: bool }
	 */
	private function export_reviews_by_user( \WP_User $user, int $offset ): array {
		$comments = get_comments(
			[
				'type'    => ReviewManager::COMMENT_TYPE,
				'user_id' => $user->ID,
				'number'  => self::BATCH_SIZE,
				'offset'  => $offset,
				'status'  => 'any',
				'orderby' => 'comment_ID',
				'order'   => 'ASC',
			]
		);

		return $this->format_review_export( $comments );
	}

	/**
	 * Export reviews matched by email only (guest reviews, avoids duplicates with user query).
	 *
	 * @param string $email  Email address.
	 * @param int    $offset Offset into results.
	 * @return array{ data: array<int, array<string, mixed>>, done: bool }
	 */
	private function export_reviews_by_email( string $email, int $offset ): array {
		$comments = get_comments(
			[
				'type'         => ReviewManager::COMMENT_TYPE,
				'author_email' => $email,
				'user_id'      => 0, // Only guest reviews to avoid duplicates.
				'number'       => self::BATCH_SIZE,
				'offset'       => $offset,
				'status'       => 'any',
				'orderby'      => 'comment_ID',
				'order'        => 'ASC',
			]
		);

		return $this->format_review_export( $comments );
	}

	/**
	 * Format review comments into export data entries.
	 *
	 * @param \WP_Comment[] $comments Comment objects.
	 * @return array{ data: array<int, array<string, mixed>>, done: bool }
	 */
	private function format_review_export( array $comments ): array {
		$data = [];
		foreach ( $comments as $comment ) {
			$listing_title = get_the_title( (int) $comment->comment_post_ID );
			$rating        = get_comment_meta( $comment->comment_ID, ReviewManager::META_RATING, true );
			$title         = get_comment_meta( $comment->comment_ID, ReviewManager::META_TITLE, true );

			$data[] = [
				'group_id'    => 'apd-reviews',
				'group_label' => __( 'Directory Reviews', 'all-purpose-directory' ),
				'item_id'     => "apd-review-{$comment->comment_ID}",
				'data'        => [
					[
						'name'  => __( 'Listing', 'all-purpose-directory' ),
						'value' => $listing_title,
					],
					[
						'name'  => __( 'Rating', 'all-purpose-directory' ),
						'value' => (string) $rating,
					],
					[
						'name'  => __( 'Title', 'all-purpose-directory' ),
						'value' => (string) $title,
					],
					[
						'name'  => __( 'Review', 'all-purpose-directory' ),
						'value' => $comment->comment_content,
					],
					[
						'name'  => __( 'Date', 'all-purpose-directory' ),
						'value' => $comment->comment_date,
					],
					[
						'name'  => __( 'Author', 'all-purpose-directory' ),
						'value' => $comment->comment_author,
					],
					[
						'name'  => __( 'Email', 'all-purpose-directory' ),
						'value' => $comment->comment_author_email,
					],
				],
			];
		}

		return [
			'data' => $data,
			'done' => count( $comments ) < self::BATCH_SIZE,
		];
	}

	/**
	 * Export inquiries sent by email (sender meta query).
	 *
	 * @param string $email  Email address.
	 * @param int    $offset Offset into results.
	 * @return array{ data: array<int, array<string, mixed>>, done: bool }
	 */
	private function export_inquiries_sent( string $email, int $offset ): array {
		$posts = get_posts(
			[
				'post_type'      => InquiryTracker::POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => self::BATCH_SIZE,
				'offset'         => $offset,
				'orderby'        => 'ID',
				'order'          => 'ASC',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'meta_query'     => [
					[
						'key'   => InquiryTracker::META_SENDER_EMAIL,
						'value' => $email,
					],
				],
			]
		);

		$data = [];
		foreach ( $posts as $post ) {
			$listing_id    = (int) get_post_meta( $post->ID, InquiryTracker::META_LISTING_ID, true );
			$listing_title = $listing_id ? get_the_title( $listing_id ) : '';

			$data[] = [
				'group_id'    => 'apd-inquiries-sent',
				'group_label' => __( 'Directory Inquiries Sent', 'all-purpose-directory' ),
				'item_id'     => "apd-inquiry-sent-{$post->ID}",
				'data'        => [
					[
						'name'  => __( 'Listing', 'all-purpose-directory' ),
						'value' => $listing_title,
					],
					[
						'name'  => __( 'Sender Name', 'all-purpose-directory' ),
						'value' => (string) get_post_meta( $post->ID, InquiryTracker::META_SENDER_NAME, true ),
					],
					[
						'name'  => __( 'Sender Email', 'all-purpose-directory' ),
						'value' => (string) get_post_meta( $post->ID, InquiryTracker::META_SENDER_EMAIL, true ),
					],
					[
						'name'  => __( 'Sender Phone', 'all-purpose-directory' ),
						'value' => (string) get_post_meta( $post->ID, InquiryTracker::META_SENDER_PHONE, true ),
					],
					[
						'name'  => __( 'Subject', 'all-purpose-directory' ),
						'value' => (string) get_post_meta( $post->ID, InquiryTracker::META_SUBJECT, true ),
					],
					[
						'name'  => __( 'Message', 'all-purpose-directory' ),
						'value' => $post->post_content,
					],
					[
						'name'  => __( 'Date', 'all-purpose-directory' ),
						'value' => $post->post_date,
					],
				],
			];
		}

		return [
			'data' => $data,
			'done' => count( $posts ) < self::BATCH_SIZE,
		];
	}

	/**
	 * Export inquiries received by a listing author.
	 *
	 * @param \WP_User $user   User object.
	 * @param int      $offset Offset into results.
	 * @return array{ data: array<int, array<string, mixed>>, done: bool }
	 */
	private function export_inquiries_received( \WP_User $user, int $offset ): array {
		$posts = get_posts(
			[
				'post_type'      => InquiryTracker::POST_TYPE,
				'post_status'    => 'any',
				'author'         => $user->ID,
				'posts_per_page' => self::BATCH_SIZE,
				'offset'         => $offset,
				'orderby'        => 'ID',
				'order'          => 'ASC',
			]
		);

		$data = [];
		foreach ( $posts as $post ) {
			$listing_id    = (int) get_post_meta( $post->ID, InquiryTracker::META_LISTING_ID, true );
			$listing_title = $listing_id ? get_the_title( $listing_id ) : '';

			// Only export listing and date — sender PII belongs to a third party.
			$data[] = [
				'group_id'    => 'apd-inquiries-received',
				'group_label' => __( 'Directory Inquiries Received', 'all-purpose-directory' ),
				'item_id'     => "apd-inquiry-received-{$post->ID}",
				'data'        => [
					[
						'name'  => __( 'Listing', 'all-purpose-directory' ),
						'value' => $listing_title,
					],
					[
						'name'  => __( 'Date', 'all-purpose-directory' ),
						'value' => $post->post_date,
					],
				],
			];
		}

		return [
			'data' => $data,
			'done' => count( $posts ) < self::BATCH_SIZE,
		];
	}

	// -------------------------------------------------------------------------
	// Private: Erase helpers
	// -------------------------------------------------------------------------

	/**
	 * Erase profile data for a user.
	 *
	 * @param \WP_User $user User object.
	 * @return array{ items_removed: bool, items_retained: bool, messages: string[], done: bool }
	 */
	private function erase_profile( \WP_User $user ): array {
		$items_removed = false;

		// Delete avatar attachment before removing the meta reference.
		$avatar_id = (int) get_user_meta( $user->ID, '_apd_avatar', true );
		if ( $avatar_id > 0 ) {
			wp_delete_attachment( $avatar_id, true );
		}

		// Delete plugin-specific profile meta.
		foreach ( self::PROFILE_META_KEYS as $meta_key ) {
			if ( metadata_exists( 'user', $user->ID, $meta_key ) ) {
				delete_user_meta( $user->ID, $meta_key );
				$items_removed = true;
			}
		}

		// Delete social links.
		foreach ( Profile::SOCIAL_PLATFORMS as $platform ) {
			$meta_key = "_apd_social_{$platform}";
			if ( metadata_exists( 'user', $user->ID, $meta_key ) ) {
				delete_user_meta( $user->ID, $meta_key );
				$items_removed = true;
			}
		}

		// Clear favorites via Favorites::clear() to keep listing counts consistent.
		$favorites     = Favorites::get_instance();
		$has_favorites = ! empty( $favorites->get_favorites( $user->ID ) );
		if ( $has_favorites ) {
			$favorites->clear( $user->ID );
			$items_removed = true;
		}

		return [
			'items_removed'  => $items_removed,
			'items_retained' => false,
			'messages'       => [],
			'done'           => true,
		];
	}

	/**
	 * Anonymize listings: set post_author to 0 and remove contact meta.
	 *
	 * Listing content is retained (directory data, not personal data).
	 *
	 * @param \WP_User $user   User object.
	 * @param int      $offset Offset into results.
	 * @return array{ items_removed: bool, items_retained: bool, messages: string[], done: bool }
	 */
	private function erase_listings( \WP_User $user, int $offset ): array {
		$posts = get_posts(
			[
				'post_type'      => PostType::POST_TYPE,
				'post_status'    => 'any',
				'author'         => $user->ID,
				'posts_per_page' => self::BATCH_SIZE,
				'offset'         => $offset,
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'fields'         => 'ids',
			]
		);

		$items_removed  = false;
		$items_retained = false;

		foreach ( $posts as $post_id ) {
			wp_update_post(
				[
					'ID'          => $post_id,
					'post_author' => 0,
				]
			);

			foreach ( self::LISTING_CONTACT_META_KEYS as $meta_key ) {
				delete_post_meta( $post_id, $meta_key );
			}

			$items_removed  = true;
			$items_retained = true;
		}

		$messages = [];
		if ( $items_retained ) {
			$messages[] = __( 'Directory listing content was retained with personal contact fields removed and author anonymized.', 'all-purpose-directory' );
		}

		return [
			'items_removed'  => $items_removed,
			'items_retained' => $items_retained,
			'messages'       => $messages,
			'done'           => count( $posts ) < self::BATCH_SIZE,
		];
	}

	/**
	 * Delete all reviews associated with the given email.
	 *
	 * Queries by both author_email and user_id to cover registered and guest reviews,
	 * deduplicating by comment ID.
	 *
	 * @param string        $email Email address.
	 * @param \WP_User|null $user  Resolved user (avoids redundant DB lookup).
	 * @return array{ items_removed: bool, items_retained: bool, messages: string[], done: bool }
	 */
	private function erase_reviews( string $email, ?\WP_User $user ): array {
		// Always query from offset 0: records are deleted each pass,
		// so remaining items shift down between paginated calls.
		$comments_by_email = get_comments(
			[
				'type'         => ReviewManager::COMMENT_TYPE,
				'author_email' => $email,
				'number'       => self::BATCH_SIZE,
				'offset'       => 0,
				'status'       => 'any',
				'orderby'      => 'comment_ID',
				'order'        => 'ASC',
			]
		);

		$comments_by_user = [];
		if ( $user instanceof \WP_User ) {
			$comments_by_user = get_comments(
				[
					'type'    => ReviewManager::COMMENT_TYPE,
					'user_id' => $user->ID,
					'number'  => self::BATCH_SIZE,
					'offset'  => 0,
					'status'  => 'any',
					'orderby' => 'comment_ID',
					'order'   => 'ASC',
				]
			);
		}

		// Deduplicate by comment_ID.
		$seen     = [];
		$comments = [];
		foreach ( array_merge( $comments_by_email, $comments_by_user ) as $comment ) {
			if ( ! isset( $seen[ $comment->comment_ID ] ) ) {
				$seen[ $comment->comment_ID ] = true;
				$comments[]                   = $comment;
			}
		}

		$listing_ids   = [];
		$items_removed = false;

		foreach ( $comments as $comment ) {
			$listing_ids[] = (int) $comment->comment_post_ID;
			wp_delete_comment( (int) $comment->comment_ID, true );
			$items_removed = true;
		}

		// Recalculate rating aggregates for affected listings.
		$calculator = RatingCalculator::get_instance();
		foreach ( array_unique( $listing_ids ) as $listing_id ) {
			$calculator->recalculate( $listing_id );
		}

		$done = count( $comments_by_email ) < self::BATCH_SIZE
			&& count( $comments_by_user ) < self::BATCH_SIZE;

		return [
			'items_removed'  => $items_removed,
			'items_retained' => false,
			'messages'       => [],
			'done'           => $done,
		];
	}

	/**
	 * Anonymize inquiries sent: clear sender PII meta and update post title.
	 *
	 * The inquiry record is retained for the listing owner's reference.
	 *
	 * @param string $email  Email address.
	 * @param int    $offset Offset into results.
	 * @return array{ items_removed: bool, items_retained: bool, messages: string[], done: bool }
	 */
	private function erase_inquiries_sent( string $email, int $offset ): array {
		$posts = get_posts(
			[
				'post_type'      => InquiryTracker::POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => self::BATCH_SIZE,
				'offset'         => $offset,
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'fields'         => 'ids',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'meta_query'     => [
					[
						'key'   => InquiryTracker::META_SENDER_EMAIL,
						'value' => $email,
					],
				],
			]
		);

		$items_removed  = false;
		$items_retained = false;

		foreach ( $posts as $post_id ) {
			delete_post_meta( $post_id, InquiryTracker::META_SENDER_NAME );
			delete_post_meta( $post_id, InquiryTracker::META_SENDER_EMAIL );
			delete_post_meta( $post_id, InquiryTracker::META_SENDER_PHONE );

			wp_update_post(
				[
					'ID'         => $post_id,
					'post_title' => __( '[Anonymized]', 'all-purpose-directory' ),
				]
			);

			$items_removed  = true;
			$items_retained = true;
		}

		$messages = [];
		if ( $items_retained ) {
			$messages[] = __( 'Inquiry records were retained with sender information removed.', 'all-purpose-directory' );
		}

		return [
			'items_removed'  => $items_removed,
			'items_retained' => $items_retained,
			'messages'       => $messages,
			'done'           => count( $posts ) < self::BATCH_SIZE,
		];
	}

	/**
	 * Delete inquiries received by a listing owner.
	 *
	 * @param \WP_User $user User object.
	 * @return array{ items_removed: bool, items_retained: bool, messages: string[], done: bool }
	 */
	private function erase_inquiries_received( \WP_User $user ): array {
		// Always query from offset 0: records are deleted each pass.
		$posts = get_posts(
			[
				'post_type'      => InquiryTracker::POST_TYPE,
				'post_status'    => 'any',
				'author'         => $user->ID,
				'posts_per_page' => self::BATCH_SIZE,
				'offset'         => 0,
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'fields'         => 'ids',
			]
		);

		$items_removed = false;

		foreach ( $posts as $post_id ) {
			wp_delete_post( $post_id, true );
			$items_removed = true;
		}

		return [
			'items_removed'  => $items_removed,
			'items_retained' => false,
			'messages'       => [],
			'done'           => count( $posts ) < self::BATCH_SIZE,
		];
	}

	// -------------------------------------------------------------------------
	// Private: Utility
	// -------------------------------------------------------------------------

	/**
	 * Resolve a WP_User from an email address.
	 *
	 * @param string $email Email address.
	 * @return \WP_User|null
	 */
	private function resolve_user( string $email ): ?\WP_User {
		$user = get_user_by( 'email', $email );
		return ( $user instanceof \WP_User ) ? $user : null;
	}
}
