<?php
/**
 * Favorite Toggle UI Class.
 *
 * Handles rendering the favorite button and AJAX toggle functionality.
 *
 * @package APD\User
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\User;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FavoriteToggle
 *
 * @since 1.0.0
 */
class FavoriteToggle {

	/**
	 * AJAX action name.
	 *
	 * @var string
	 */
	public const AJAX_ACTION = 'apd_toggle_favorite';

	/**
	 * Nonce action for AJAX requests.
	 *
	 * @var string
	 */
	public const NONCE_ACTION = 'apd_favorite_nonce';

	/**
	 * Singleton instance.
	 *
	 * @var FavoriteToggle|null
	 */
	private static ?FavoriteToggle $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @since 1.0.0
	 *
	 * @return FavoriteToggle
	 */
	public static function get_instance(): FavoriteToggle {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {}

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
	 * Reset singleton instance (for testing).
	 *
	 * @return void
	 */
	public static function reset_instance(): void {
		self::$instance = null;
	}

	/**
	 * Initialize the favorite toggle system.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init(): void {
		// Register AJAX handlers.
		add_action( 'wp_ajax_' . self::AJAX_ACTION, [ $this, 'handle_ajax_toggle' ] );
		add_action( 'wp_ajax_nopriv_' . self::AJAX_ACTION, [ $this, 'handle_ajax_toggle' ] );

		// Add favorite button to listing cards.
		add_action( 'apd_listing_card_image', [ $this, 'render_card_button' ], 10, 1 );
		add_action( 'apd_listing_card_footer', [ $this, 'render_card_button_fallback' ], 5, 1 );

		// Add favorite button to single listing.
		add_action( 'apd_single_listing_meta', [ $this, 'render_single_button' ], 10, 1 );

		// Add localized script data.
		add_filter( 'apd_frontend_script_data', [ $this, 'add_script_data' ] );
	}

	/**
	 * Handle AJAX toggle request.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function handle_ajax_toggle(): void {
		// Verify nonce.
		$nonce = isset( $_POST['_apd_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_apd_nonce'] ) ) : '';
		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			wp_send_json_error(
				[
					'code'    => 'invalid_nonce',
					'message' => __( 'Invalid security token. Please refresh the page and try again.', 'all-purpose-directory' ),
				],
				403
			);
		}

		// Get listing ID.
		$listing_id = isset( $_POST['listing_id'] ) ? absint( $_POST['listing_id'] ) : 0;
		if ( $listing_id <= 0 ) {
			wp_send_json_error(
				[
					'code'    => 'invalid_listing',
					'message' => __( 'Invalid listing ID.', 'all-purpose-directory' ),
				],
				400
			);
		}

		// Check if login is required.
		$favorites = Favorites::get_instance();

		if ( ! is_user_logged_in() && $favorites->requires_login() ) {
			$redirect_url = add_query_arg(
				'redirect_to',
				rawurlencode( wp_get_referer() ?: home_url() ),
				wp_login_url()
			);

			wp_send_json_error(
				[
					'code'      => 'login_required',
					'message'   => __( 'Please log in to save favorites.', 'all-purpose-directory' ),
					'login_url' => $redirect_url,
				],
				401
			);
		}

		// Toggle favorite status.
		$new_state = $favorites->toggle( $listing_id );

		if ( $new_state === null ) {
			wp_send_json_error(
				[
					'code'    => 'toggle_failed',
					'message' => __( 'Failed to update favorite status.', 'all-purpose-directory' ),
				],
				500
			);
		}

		// Get updated count.
		$count = $favorites->get_listing_favorite_count( $listing_id );

		// Generate new button HTML.
		$button_html = $this->get_button(
			$listing_id,
			[
				'show_count' => true,
				'size'       => 'medium',
			]
		);

		$message = $new_state
			? __( 'Added to favorites.', 'all-purpose-directory' )
			: __( 'Removed from favorites.', 'all-purpose-directory' );

		wp_send_json_success(
			[
				'is_favorite' => $new_state,
				'count'       => $count,
				'button_html' => $button_html,
				'message'     => $message,
			]
		);
	}

	/**
	 * Render favorite button on card image overlay (for cards with thumbnails).
	 *
	 * @since 1.0.0
	 *
	 * @param int $listing_id The listing post ID.
	 * @return void
	 */
	public function render_card_button( int $listing_id ): void {
		if ( ! \apd_favorites_enabled() ) {
			return;
		}

		// Only render in image area if we have a thumbnail.
		if ( ! has_post_thumbnail( $listing_id ) ) {
			return;
		}

		$this->render_button(
			$listing_id,
			[
				'show_count' => false,
				'size'       => 'small',
				'class'      => 'apd-favorite-button--overlay',
			]
		);
	}

	/**
	 * Render favorite button in card footer (fallback for cards without thumbnails).
	 *
	 * @since 1.0.0
	 *
	 * @param int $listing_id The listing post ID.
	 * @return void
	 */
	public function render_card_button_fallback( int $listing_id ): void {
		if ( ! \apd_favorites_enabled() ) {
			return;
		}

		// Only render in footer if we don't have a thumbnail.
		// Cards with thumbnails get the button in the image overlay.
		if ( has_post_thumbnail( $listing_id ) ) {
			return;
		}

		$this->render_button(
			$listing_id,
			[
				'show_count' => true,
				'size'       => 'small',
				'class'      => '',
			]
		);
	}

	/**
	 * Render favorite button on single listing.
	 *
	 * @since 1.0.0
	 *
	 * @param int $listing_id The listing post ID.
	 * @return void
	 */
	public function render_single_button( int $listing_id ): void {
		if ( ! \apd_favorites_enabled() ) {
			return;
		}

		$count = apd_get_listing_favorites_count( $listing_id );
		?>
		<span class="apd-single-listing__favorites">
			<?php
			$this->render_button(
				$listing_id,
				[
					'show_count' => true,
					'size'       => 'medium',
					'class'      => '',
				]
			);

			if ( $count > 0 ) :
				?>
				<span class="apd-favorite-summary">
					<?php
					printf(
						/* translators: %s: number of people who favorited */
						esc_html( _n( '%s person favorited this', '%s people favorited this', $count, 'all-purpose-directory' ) ),
						esc_html( number_format_i18n( $count ) )
					);
					?>
				</span>
			<?php endif; ?>
		</span>
		<?php
	}

	/**
	 * Render the favorite button HTML.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $listing_id Listing post ID.
	 * @param array $args       Optional. Button arguments.
	 * @return void
	 */
	public function render_button( int $listing_id, array $args = [] ): void {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML escaped in get_button() via esc_attr/esc_html.
		echo $this->get_button( $listing_id, $args );
	}

	/**
	 * Get the favorite button HTML as a string.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $listing_id Listing post ID.
	 * @param array $args       Optional. Button arguments.
	 *                          - show_count: bool Whether to show favorite count.
	 *                          - size: string Button size (small, medium, large).
	 *                          - class: string Additional CSS classes.
	 * @return string Button HTML.
	 */
	public function get_button( int $listing_id, array $args = [] ): string {
		$defaults = [
			'show_count' => false,
			'size'       => 'medium',
			'class'      => '',
		];

		$args = wp_parse_args( $args, $defaults );

		// Validate size.
		$valid_sizes = [ 'small', 'medium', 'large' ];
		if ( ! in_array( $args['size'], $valid_sizes, true ) ) {
			$args['size'] = 'medium';
		}

		$favorites   = Favorites::get_instance();
		$is_favorite = $favorites->is_favorite( $listing_id );
		$count       = $favorites->get_listing_favorite_count( $listing_id );
		$nonce       = wp_create_nonce( self::NONCE_ACTION );

		// Build CSS classes.
		$classes = [
			'apd-favorite-button',
			'apd-favorite-button--' . $args['size'],
		];

		if ( $is_favorite ) {
			$classes[] = 'apd-favorite-button--active';
		}

		if ( ! empty( $args['class'] ) ) {
			$classes[] = $args['class'];
		}

		/**
		 * Filter the favorite button CSS classes.
		 *
		 * @since 1.0.0
		 *
		 * @param array $classes    CSS classes.
		 * @param int   $listing_id The listing post ID.
		 * @param bool  $is_favorite Whether the listing is favorited.
		 */
		$classes = apply_filters( 'apd_favorite_button_classes', $classes, $listing_id, $is_favorite );

		// Determine ARIA label.
		$aria_label = $is_favorite
			? __( 'Remove from favorites', 'all-purpose-directory' )
			: __( 'Add to favorites', 'all-purpose-directory' );

		// Build button HTML.
		$html = sprintf(
			'<button type="button" class="%1$s" data-listing-id="%2$d" data-nonce="%3$s" aria-label="%4$s" aria-pressed="%5$s">',
			esc_attr( implode( ' ', $classes ) ),
			esc_attr( $listing_id ),
			esc_attr( $nonce ),
			esc_attr( $aria_label ),
			esc_attr( $is_favorite ? 'true' : 'false' )
		);

		// Heart icon (SVG for better control and accessibility).
		$html .= '<span class="apd-favorite-icon" aria-hidden="true">';
		$html .= $this->get_heart_svg( $is_favorite );
		$html .= '</span>';

		// Count (optional).
		if ( $args['show_count'] ) {
			$html .= sprintf(
				'<span class="apd-favorite-count" data-count="%1$d">%2$s</span>',
				esc_attr( $count ),
				esc_html( $count > 0 ? number_format_i18n( $count ) : '' )
			);
		}

		$html .= '</button>';

		/**
		 * Filter the favorite button HTML.
		 *
		 * @since 1.0.0
		 *
		 * @param string $html       Button HTML.
		 * @param int    $listing_id The listing post ID.
		 * @param array  $args       Button arguments.
		 */
		return apply_filters( 'apd_favorite_button_html', $html, $listing_id, $args );
	}

	/**
	 * Get the heart SVG icon.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $filled Whether the heart should be filled.
	 * @return string SVG HTML.
	 */
	private function get_heart_svg( bool $filled ): string {
		if ( $filled ) {
			// Filled heart.
			return '<svg class="apd-heart-icon apd-heart-icon--filled" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>';
		}

		// Outline heart.
		return '<svg class="apd-heart-icon apd-heart-icon--outline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>';
	}

	/**
	 * Add script data for frontend JavaScript.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Script data.
	 * @return array Modified script data.
	 */
	public function add_script_data( array $data ): array {
		$favorites = Favorites::get_instance();

		$data['favoriteNonce']  = wp_create_nonce( self::NONCE_ACTION );
		$data['favoriteAction'] = self::AJAX_ACTION;
		$data['requiresLogin']  = $favorites->requires_login();

		// Add i18n strings for favorites.
		if ( ! isset( $data['i18n'] ) ) {
			$data['i18n'] = [];
		}

		$data['i18n']['addedToFavorites']     = __( 'Added to favorites.', 'all-purpose-directory' );
		$data['i18n']['removedFromFavorites'] = __( 'Removed from favorites.', 'all-purpose-directory' );
		$data['i18n']['addToFavorites']       = __( 'Add to favorites', 'all-purpose-directory' );
		$data['i18n']['removeFromFavorites']  = __( 'Remove from favorites', 'all-purpose-directory' );
		$data['i18n']['loginRequired']        = __( 'Please log in to save favorites.', 'all-purpose-directory' );
		$data['i18n']['favoriteError']        = __( 'Failed to update favorite. Please try again.', 'all-purpose-directory' );

		return $data;
	}
}
