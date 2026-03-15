<?php
/**
 * Admin Settings Page Class.
 *
 * Provides admin settings interface with tabbed sections for
 * configuring plugin options.
 *
 * @package APD\Admin
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Admin;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Settings
 *
 * Admin interface for plugin settings.
 *
 * @since 1.0.0
 */
final class Settings {

	/**
	 * Admin page slug.
	 */
	public const PAGE_SLUG = 'apd-settings';

	/**
	 * Settings option group.
	 */
	public const OPTION_GROUP = 'apd_settings';

	/**
	 * Settings option name.
	 */
	public const OPTION_NAME = 'apd_options';

	/**
	 * Nonce action for settings.
	 */
	public const NONCE_ACTION = 'apd_settings_save';

	/**
	 * Nonce field name.
	 */
	public const NONCE_NAME = 'apd_settings_nonce';

	/**
	 * Parent menu slug (apd_listing post type).
	 */
	public const PARENT_MENU = 'edit.php?post_type=apd_listing';

	/**
	 * Capability required to manage settings.
	 */
	public const CAPABILITY = 'manage_options';

	/**
	 * Singleton instance.
	 *
	 * @var Settings|null
	 */
	private static ?Settings $instance = null;

	/**
	 * Available tabs configuration.
	 *
	 * @var array<string, array{label: string, callback: callable}>
	 */
	private array $tabs = [];

	/**
	 * Get the singleton instance.
	 *
	 * @since 1.0.0
	 *
	 * @return Settings
	 */
	public static function get_instance(): Settings {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		// Tabs are registered in init() to avoid early translation loading.
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
	 * Initialize the settings.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init(): void {
		// Only run in admin context.
		if ( ! is_admin() ) {
			return;
		}

		// Register tabs on init to ensure textdomain is loaded (WP 6.7+).
		add_action( 'init', [ $this, 'register_tabs' ] );

		add_action( 'admin_menu', [ $this, 'register_admin_page' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );

		/**
		 * Fires after settings are initialized.
		 *
		 * @since 1.0.0
		 *
		 * @param Settings $settings The settings instance.
		 */
		do_action( 'apd_settings_init', $this );
	}

	/**
	 * Register default tabs.
	 *
	 * Called on 'init' hook to ensure textdomain is loaded (WP 6.7+).
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_tabs(): void {
		$this->tabs = [
			'general'    => [
				'label'    => __( 'General', 'all-purpose-directory' ),
				'callback' => [ $this, 'render_general_tab' ],
			],
			'listings'   => [
				'label'    => __( 'Listings', 'all-purpose-directory' ),
				'callback' => [ $this, 'render_listings_tab' ],
			],
			'submission' => [
				'label'    => __( 'Submission', 'all-purpose-directory' ),
				'callback' => [ $this, 'render_submission_tab' ],
			],
			'display'    => [
				'label'    => __( 'Display', 'all-purpose-directory' ),
				'callback' => [ $this, 'render_display_tab' ],
			],
			'email'      => [
				'label'    => __( 'Email', 'all-purpose-directory' ),
				'callback' => [ $this, 'render_email_tab' ],
			],
			'advanced'   => [
				'label'    => __( 'Advanced', 'all-purpose-directory' ),
				'callback' => [ $this, 'render_advanced_tab' ],
			],
		];

		/**
		 * Filter the available settings tabs.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, array{label: string, callback: callable}> $tabs The tabs configuration.
		 */
		$this->tabs = apply_filters( 'apd_settings_tabs', $this->tabs );
	}

	/**
	 * Register the admin menu page.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_admin_page(): void {
		add_submenu_page(
			self::PARENT_MENU,
			__( 'Settings', 'all-purpose-directory' ),
			__( 'Settings', 'all-purpose-directory' ),
			self::CAPABILITY,
			self::PAGE_SLUG,
			[ $this, 'render_page' ]
		);
	}

	/**
	 * Register plugin settings.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting(
			self::OPTION_GROUP,
			self::OPTION_NAME,
			[
				'type'              => 'array',
				'sanitize_callback' => [ $this, 'sanitize_settings' ],
				'default'           => $this->get_defaults(),
			]
		);

		// Register sections and fields for each tab.
		$this->register_general_settings();
		$this->register_listings_settings();
		$this->register_submission_settings();
		$this->register_display_settings();
		$this->register_email_settings();
		$this->register_advanced_settings();

		/**
		 * Fires after settings are registered.
		 *
		 * Use this hook to register additional settings sections and fields.
		 *
		 * @since 1.0.0
		 *
		 * @param Settings $settings The settings instance.
		 */
		do_action( 'apd_register_settings', $this );
	}

	/**
	 * Register General settings section and fields.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function register_general_settings(): void {
		$section_id = 'apd_general_section';
		$page       = self::PAGE_SLUG . '_general';

		add_settings_section(
			$section_id,
			__( 'General Settings', 'all-purpose-directory' ),
			function () {
				echo '<p>' . esc_html__( 'Configure general plugin settings.', 'all-purpose-directory' ) . '</p>';
			},
			$page
		);

		add_settings_field(
			'currency_symbol',
			__( 'Currency Symbol', 'all-purpose-directory' ),
			[ $this, 'render_text_field' ],
			$page,
			$section_id,
			[
				'field'       => 'currency_symbol',
				'description' => __( 'Currency symbol to display with prices (e.g., $, €, £).', 'all-purpose-directory' ),
				'class'       => 'small-text',
			]
		);

		add_settings_field(
			'currency_position',
			__( 'Currency Position', 'all-purpose-directory' ),
			[ $this, 'render_select_field' ],
			$page,
			$section_id,
			[
				'field'       => 'currency_position',
				'options'     => [
					'before' => __( 'Before amount ($99)', 'all-purpose-directory' ),
					'after'  => __( 'After amount (99$)', 'all-purpose-directory' ),
				],
				'description' => __( 'Where to display the currency symbol.', 'all-purpose-directory' ),
			]
		);

		add_settings_field(
			'date_format',
			__( 'Date Format', 'all-purpose-directory' ),
			[ $this, 'render_select_field' ],
			$page,
			$section_id,
			[
				'field'       => 'date_format',
				'options'     => [
					'default' => __( 'WordPress Default', 'all-purpose-directory' ),
					'Y-m-d'   => 'YYYY-MM-DD (2024-01-15)',
					'm/d/Y'   => 'MM/DD/YYYY (01/15/2024)',
					'd/m/Y'   => 'DD/MM/YYYY (15/01/2024)',
					'F j, Y'  => 'Month Day, Year (January 15, 2024)',
					'j F Y'   => 'Day Month Year (15 January 2024)',
				],
				'description' => __( 'Date format for listing dates.', 'all-purpose-directory' ),
			]
		);

		add_settings_field(
			'distance_unit',
			__( 'Distance Unit', 'all-purpose-directory' ),
			[ $this, 'render_select_field' ],
			$page,
			$section_id,
			[
				'field'       => 'distance_unit',
				'options'     => [
					'km'    => __( 'Kilometers (km)', 'all-purpose-directory' ),
					'miles' => __( 'Miles', 'all-purpose-directory' ),
				],
				'description' => __( 'Unit for distance measurements.', 'all-purpose-directory' ),
			]
		);

		// Pages section.
		$pages_section = 'apd_general_pages_section';
		add_settings_section(
			$pages_section,
			__( 'Pages', 'all-purpose-directory' ),
			function () {
				echo '<p>' . esc_html__( 'Pages created during activation. You can reassign them here.', 'all-purpose-directory' ) . '</p>';
			},
			$page
		);

		add_settings_field(
			'directory_page',
			__( 'Directory Page', 'all-purpose-directory' ),
			[ $this, 'render_page_select_field' ],
			$page,
			$pages_section,
			[
				'field'       => 'directory_page',
				'description' => __( 'Main directory page with search form and listings.', 'all-purpose-directory' ),
			]
		);

		add_settings_field(
			'submit_page',
			__( 'Submit Listing Page', 'all-purpose-directory' ),
			[ $this, 'render_page_select_field' ],
			$page,
			$pages_section,
			[
				'field'       => 'submit_page',
				'description' => __( 'Page with the listing submission form.', 'all-purpose-directory' ),
			]
		);

		add_settings_field(
			'dashboard_page',
			__( 'Dashboard Page', 'all-purpose-directory' ),
			[ $this, 'render_page_select_field' ],
			$page,
			$pages_section,
			[
				'field'       => 'dashboard_page',
				'description' => __( 'User dashboard page.', 'all-purpose-directory' ),
			]
		);
	}

	/**
	 * Register Listings settings section and fields.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function register_listings_settings(): void {
		$section_id = 'apd_listings_section';
		$page       = self::PAGE_SLUG . '_listings';

		add_settings_section(
			$section_id,
			__( 'Listing Settings', 'all-purpose-directory' ),
			function () {
				echo '<p>' . esc_html__( 'Configure listing display and behavior.', 'all-purpose-directory' ) . '</p>';
			},
			$page
		);

		add_settings_field(
			'listings_per_page',
			__( 'Listings Per Page', 'all-purpose-directory' ),
			[ $this, 'render_number_field' ],
			$page,
			$section_id,
			[
				'field'       => 'listings_per_page',
				'min'         => 1,
				'max'         => 100,
				'description' => __( 'Number of listings to display per page (1-100).', 'all-purpose-directory' ),
			]
		);

		add_settings_field(
			'default_status',
			__( 'Default Listing Status', 'all-purpose-directory' ),
			[ $this, 'render_select_field' ],
			$page,
			$section_id,
			[
				'field'       => 'default_status',
				'options'     => [
					'publish' => __( 'Published', 'all-purpose-directory' ),
					'pending' => __( 'Pending Review', 'all-purpose-directory' ),
					'draft'   => __( 'Draft', 'all-purpose-directory' ),
				],
				'description' => __( 'Default status for new listing submissions.', 'all-purpose-directory' ),
			]
		);

		add_settings_field(
			'expiration_days',
			__( 'Listing Expiration', 'all-purpose-directory' ),
			[ $this, 'render_number_field' ],
			$page,
			$section_id,
			[
				'field'       => 'expiration_days',
				'min'         => 0,
				'max'         => 365,
				'description' => __( 'Days until listings expire (0 = never expire).', 'all-purpose-directory' ),
			]
		);

		// Features section.
		$features_section = 'apd_listings_features_section';
		add_settings_section(
			$features_section,
			__( 'Features', 'all-purpose-directory' ),
			function () {
				echo '<p>' . esc_html__( 'Enable or disable listing features.', 'all-purpose-directory' ) . '</p>';
			},
			$page
		);

		add_settings_field(
			'enable_reviews',
			__( 'Enable Reviews', 'all-purpose-directory' ),
			[ $this, 'render_checkbox_field' ],
			$page,
			$features_section,
			[
				'field' => 'enable_reviews',
				'label' => __( 'Allow users to leave reviews on listings.', 'all-purpose-directory' ),
			]
		);

		add_settings_field(
			'enable_favorites',
			__( 'Enable Favorites', 'all-purpose-directory' ),
			[ $this, 'render_checkbox_field' ],
			$page,
			$features_section,
			[
				'field' => 'enable_favorites',
				'label' => __( 'Allow users to save listings to favorites.', 'all-purpose-directory' ),
			]
		);

		add_settings_field(
			'enable_contact_form',
			__( 'Enable Contact Form', 'all-purpose-directory' ),
			[ $this, 'render_checkbox_field' ],
			$page,
			$features_section,
			[
				'field' => 'enable_contact_form',
				'label' => __( 'Show contact form on listing pages.', 'all-purpose-directory' ),
			]
		);
	}

	/**
	 * Register Submission settings section and fields.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function register_submission_settings(): void {
		$section_id = 'apd_submission_section';
		$page       = self::PAGE_SLUG . '_submission';

		add_settings_section(
			$section_id,
			__( 'Submission Settings', 'all-purpose-directory' ),
			function () {
				echo '<p>' . esc_html__( 'Configure frontend listing submission settings.', 'all-purpose-directory' ) . '</p>';
			},
			$page
		);

		add_settings_field(
			'who_can_submit',
			__( 'Who Can Submit', 'all-purpose-directory' ),
			[ $this, 'render_select_field' ],
			$page,
			$section_id,
			[
				'field'       => 'who_can_submit',
				'options'     => [
					'anyone'         => __( 'Anyone (including guests)', 'all-purpose-directory' ),
					'logged_in'      => __( 'Logged-in Users Only', 'all-purpose-directory' ),
					'specific_roles' => __( 'Specific User Roles', 'all-purpose-directory' ),
				],
				'description' => __( 'Who can submit new listings from the frontend.', 'all-purpose-directory' ),
			]
		);

		add_settings_field(
			'submission_roles',
			__( 'Allowed Roles', 'all-purpose-directory' ),
			[ $this, 'render_roles_field' ],
			$page,
			$section_id,
			[
				'field'       => 'submission_roles',
				'description' => __( 'Select which user roles can submit listings.', 'all-purpose-directory' ),
			]
		);

		add_settings_field(
			'guest_submission',
			__( 'Guest Submission', 'all-purpose-directory' ),
			[ $this, 'render_checkbox_field' ],
			$page,
			$section_id,
			[
				'field' => 'guest_submission',
				'label' => __( 'Allow guests to submit listings with email address.', 'all-purpose-directory' ),
			]
		);

		add_settings_field(
			'terms_page',
			__( 'Terms & Conditions Page', 'all-purpose-directory' ),
			[ $this, 'render_page_select_field' ],
			$page,
			$section_id,
			[
				'field'       => 'terms_page',
				'description' => __( 'Page containing terms and conditions (optional).', 'all-purpose-directory' ),
			]
		);

		add_settings_field(
			'redirect_after',
			__( 'After Submission Redirect', 'all-purpose-directory' ),
			[ $this, 'render_select_field' ],
			$page,
			$section_id,
			[
				'field'       => 'redirect_after',
				'options'     => [
					'listing'   => __( 'View Listing', 'all-purpose-directory' ),
					'dashboard' => __( 'User Dashboard', 'all-purpose-directory' ),
					'custom'    => __( 'Custom URL', 'all-purpose-directory' ),
				],
				'description' => __( 'Where to redirect users after successful submission.', 'all-purpose-directory' ),
			]
		);

		add_settings_field(
			'redirect_custom_url',
			__( 'Custom Redirect URL', 'all-purpose-directory' ),
			[ $this, 'render_text_field' ],
			$page,
			$section_id,
			[
				'field'       => 'redirect_custom_url',
				'class'       => 'regular-text code',
				'description' => __( 'URL on this site to redirect to (e.g., /thank-you). If empty, redirects to the submitted listing.', 'all-purpose-directory' ),
			]
		);
	}

	/**
	 * Register Display settings section and fields.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function register_display_settings(): void {
		$section_id = 'apd_display_section';
		$page       = self::PAGE_SLUG . '_display';

		add_settings_section(
			$section_id,
			__( 'Display Settings', 'all-purpose-directory' ),
			function () {
				echo '<p>' . esc_html__( 'Configure how listings are displayed.', 'all-purpose-directory' ) . '</p>';
			},
			$page
		);

		add_settings_field(
			'default_view',
			__( 'Default View', 'all-purpose-directory' ),
			[ $this, 'render_select_field' ],
			$page,
			$section_id,
			[
				'field'       => 'default_view',
				'options'     => [
					'grid' => __( 'Grid View', 'all-purpose-directory' ),
					'list' => __( 'List View', 'all-purpose-directory' ),
				],
				'description' => __( 'Default listing view on archive pages.', 'all-purpose-directory' ),
			]
		);

		add_settings_field(
			'grid_columns',
			__( 'Grid Columns', 'all-purpose-directory' ),
			[ $this, 'render_select_field' ],
			$page,
			$section_id,
			[
				'field'       => 'grid_columns',
				'options'     => [
					'2' => __( '2 Columns', 'all-purpose-directory' ),
					'3' => __( '3 Columns', 'all-purpose-directory' ),
					'4' => __( '4 Columns', 'all-purpose-directory' ),
				],
				'description' => __( 'Number of columns in grid view.', 'all-purpose-directory' ),
			]
		);

		// Card elements section.
		$elements_section = 'apd_display_elements_section';
		add_settings_section(
			$elements_section,
			__( 'Card Elements', 'all-purpose-directory' ),
			function () {
				echo '<p>' . esc_html__( 'Choose which elements to show on listing cards.', 'all-purpose-directory' ) . '</p>';
			},
			$page
		);

		add_settings_field(
			'show_thumbnail',
			__( 'Show Thumbnail', 'all-purpose-directory' ),
			[ $this, 'render_checkbox_field' ],
			$page,
			$elements_section,
			[
				'field' => 'show_thumbnail',
				'label' => __( 'Display featured image on listing cards.', 'all-purpose-directory' ),
			]
		);

		add_settings_field(
			'show_excerpt',
			__( 'Show Excerpt', 'all-purpose-directory' ),
			[ $this, 'render_checkbox_field' ],
			$page,
			$elements_section,
			[
				'field' => 'show_excerpt',
				'label' => __( 'Display excerpt/short description on cards.', 'all-purpose-directory' ),
			]
		);

		add_settings_field(
			'show_category',
			__( 'Show Category', 'all-purpose-directory' ),
			[ $this, 'render_checkbox_field' ],
			$page,
			$elements_section,
			[
				'field' => 'show_category',
				'label' => __( 'Display category badge on cards.', 'all-purpose-directory' ),
			]
		);

		add_settings_field(
			'show_rating',
			__( 'Show Rating', 'all-purpose-directory' ),
			[ $this, 'render_checkbox_field' ],
			$page,
			$elements_section,
			[
				'field' => 'show_rating',
				'label' => __( 'Display star rating on cards.', 'all-purpose-directory' ),
			]
		);

		add_settings_field(
			'show_favorite',
			__( 'Show Favorite Button', 'all-purpose-directory' ),
			[ $this, 'render_checkbox_field' ],
			$page,
			$elements_section,
			[
				'field' => 'show_favorite',
				'label' => __( 'Display favorite/heart button on cards.', 'all-purpose-directory' ),
			]
		);

		// Layout section.
		$layout_section = 'apd_display_layout_section';
		add_settings_section(
			$layout_section,
			__( 'Layout', 'all-purpose-directory' ),
			function () {
				echo '<p>' . esc_html__( 'Configure page layouts.', 'all-purpose-directory' ) . '</p>';
			},
			$page
		);

		add_settings_field(
			'archive_title',
			__( 'Archive Page Title', 'all-purpose-directory' ),
			[ $this, 'render_text_field' ],
			$page,
			$layout_section,
			[
				'field'       => 'archive_title',
				'description' => __( 'Custom title for the listings archive page (leave empty for default).', 'all-purpose-directory' ),
			]
		);

		add_settings_field(
			'single_layout',
			__( 'Single Listing Layout', 'all-purpose-directory' ),
			[ $this, 'render_select_field' ],
			$page,
			$layout_section,
			[
				'field'       => 'single_layout',
				'options'     => [
					'full'    => __( 'Full Width', 'all-purpose-directory' ),
					'sidebar' => __( 'With Sidebar', 'all-purpose-directory' ),
				],
				'description' => __( 'Layout for single listing pages.', 'all-purpose-directory' ),
			]
		);
	}

	/**
	 * Register Email settings section and fields.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function register_email_settings(): void {
		$section_id = 'apd_email_section';
		$page       = self::PAGE_SLUG . '_email';

		add_settings_section(
			$section_id,
			__( 'Email Settings', 'all-purpose-directory' ),
			function () {
				echo '<p>' . esc_html__( 'Configure email sender information.', 'all-purpose-directory' ) . '</p>';
			},
			$page
		);

		add_settings_field(
			'from_name',
			__( 'From Name', 'all-purpose-directory' ),
			[ $this, 'render_text_field' ],
			$page,
			$section_id,
			[
				'field'       => 'from_name',
				'description' => __( 'Name shown as the email sender (leave empty for site name).', 'all-purpose-directory' ),
			]
		);

		add_settings_field(
			'from_email',
			__( 'From Email', 'all-purpose-directory' ),
			[ $this, 'render_email_field' ],
			$page,
			$section_id,
			[
				'field'       => 'from_email',
				'description' => __( 'Email address shown as sender (leave empty for admin email).', 'all-purpose-directory' ),
			]
		);

		add_settings_field(
			'admin_email',
			__( 'Admin Notification Email', 'all-purpose-directory' ),
			[ $this, 'render_email_field' ],
			$page,
			$section_id,
			[
				'field'       => 'admin_email',
				'description' => __( 'Email for admin notifications (leave empty for default admin email).', 'all-purpose-directory' ),
			]
		);

		// Notifications section.
		$notifications_section = 'apd_email_notifications_section';
		add_settings_section(
			$notifications_section,
			__( 'Email Notifications', 'all-purpose-directory' ),
			function () {
				echo '<p>' . esc_html__( 'Enable or disable email notifications.', 'all-purpose-directory' ) . '</p>';
			},
			$page
		);

		add_settings_field(
			'notify_submission',
			__( 'New Submission', 'all-purpose-directory' ),
			[ $this, 'render_checkbox_field' ],
			$page,
			$notifications_section,
			[
				'field' => 'notify_submission',
				'label' => __( 'Notify admin when a new listing is submitted.', 'all-purpose-directory' ),
			]
		);

		add_settings_field(
			'notify_approved',
			__( 'Listing Approved', 'all-purpose-directory' ),
			[ $this, 'render_checkbox_field' ],
			$page,
			$notifications_section,
			[
				'field' => 'notify_approved',
				'label' => __( 'Notify author when their listing is approved.', 'all-purpose-directory' ),
			]
		);

		add_settings_field(
			'notify_rejected',
			__( 'Listing Rejected', 'all-purpose-directory' ),
			[ $this, 'render_checkbox_field' ],
			$page,
			$notifications_section,
			[
				'field' => 'notify_rejected',
				'label' => __( 'Notify author when their listing is rejected.', 'all-purpose-directory' ),
			]
		);

		add_settings_field(
			'notify_expiring',
			__( 'Listing Expiring', 'all-purpose-directory' ),
			[ $this, 'render_checkbox_field' ],
			$page,
			$notifications_section,
			[
				'field' => 'notify_expiring',
				'label' => __( 'Notify author when their listing is about to expire.', 'all-purpose-directory' ),
			]
		);

		add_settings_field(
			'notify_review',
			__( 'New Review', 'all-purpose-directory' ),
			[ $this, 'render_checkbox_field' ],
			$page,
			$notifications_section,
			[
				'field' => 'notify_review',
				'label' => __( 'Notify listing author when they receive a new review.', 'all-purpose-directory' ),
			]
		);

		add_settings_field(
			'notify_inquiry',
			__( 'New Inquiry', 'all-purpose-directory' ),
			[ $this, 'render_checkbox_field' ],
			$page,
			$notifications_section,
			[
				'field' => 'notify_inquiry',
				'label' => __( 'Notify listing author when they receive a contact inquiry.', 'all-purpose-directory' ),
			]
		);
	}

	/**
	 * Register Advanced settings section and fields.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function register_advanced_settings(): void {
		$section_id = 'apd_advanced_section';
		$page       = self::PAGE_SLUG . '_advanced';

		add_settings_section(
			$section_id,
			__( 'Advanced Settings', 'all-purpose-directory' ),
			function () {
				echo '<p>' . esc_html__( 'Advanced configuration options.', 'all-purpose-directory' ) . '</p>';
			},
			$page
		);

		add_settings_field(
			'delete_data',
			__( 'Delete Data on Uninstall', 'all-purpose-directory' ),
			[ $this, 'render_checkbox_field' ],
			$page,
			$section_id,
			[
				'field'       => 'delete_data',
				'label'       => __( 'Remove all plugin data when uninstalling.', 'all-purpose-directory' ),
				'description' => __( 'Warning: This will permanently delete all listings, reviews, and settings.', 'all-purpose-directory' ),
			]
		);

		add_settings_field(
			'custom_css',
			__( 'Custom CSS', 'all-purpose-directory' ),
			[ $this, 'render_textarea_field' ],
			$page,
			$section_id,
			[
				'field'       => 'custom_css',
				'rows'        => 10,
				'description' => __( 'Add custom CSS styles for the plugin.', 'all-purpose-directory' ),
			]
		);

		add_settings_field(
			'debug_mode',
			__( 'Debug Mode', 'all-purpose-directory' ),
			[ $this, 'render_checkbox_field' ],
			$page,
			$section_id,
			[
				'field'       => 'debug_mode',
				'label'       => __( 'Enable debug logging for troubleshooting.', 'all-purpose-directory' ),
				'description' => __( 'Logs will be written to the WordPress debug log.', 'all-purpose-directory' ),
			]
		);
	}

	/**
	 * Render a text input field.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Field arguments.
	 * @return void
	 */
	public function render_text_field( array $args ): void {
		$field = $args['field'];
		$value = $this->get( $field );
		$class = $args['class'] ?? 'regular-text';

		printf(
			'<input type="text" id="%s" name="%s[%s]" value="%s" class="%s">',
			esc_attr( $field ),
			esc_attr( self::OPTION_NAME ),
			esc_attr( $field ),
			esc_attr( $value ),
			esc_attr( $class )
		);

		if ( ! empty( $args['description'] ) ) {
			printf( '<p class="description">%s</p>', esc_html( $args['description'] ) );
		}
	}

	/**
	 * Render a number input field.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Field arguments.
	 * @return void
	 */
	public function render_number_field( array $args ): void {
		$field = $args['field'];
		$value = $this->get( $field );
		$min   = $args['min'] ?? 0;
		$max   = $args['max'] ?? '';

		printf(
			'<input type="number" id="%s" name="%s[%s]" value="%d" min="%d" %s class="small-text">',
			esc_attr( $field ),
			esc_attr( self::OPTION_NAME ),
			esc_attr( $field ),
			(int) $value,
			(int) $min,
			$max !== '' ? 'max="' . (int) $max . '"' : ''
		);

		if ( ! empty( $args['description'] ) ) {
			printf( '<p class="description">%s</p>', esc_html( $args['description'] ) );
		}
	}

	/**
	 * Render an email input field.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Field arguments.
	 * @return void
	 */
	public function render_email_field( array $args ): void {
		$field = $args['field'];
		$value = $this->get( $field );

		printf(
			'<input type="email" id="%s" name="%s[%s]" value="%s" class="regular-text">',
			esc_attr( $field ),
			esc_attr( self::OPTION_NAME ),
			esc_attr( $field ),
			esc_attr( $value )
		);

		if ( ! empty( $args['description'] ) ) {
			printf( '<p class="description">%s</p>', esc_html( $args['description'] ) );
		}
	}

	/**
	 * Render a textarea field.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Field arguments.
	 * @return void
	 */
	public function render_textarea_field( array $args ): void {
		$field = $args['field'];
		$value = $this->get( $field );
		$rows  = $args['rows'] ?? 5;

		printf(
			'<textarea id="%s" name="%s[%s]" rows="%d" class="large-text code">%s</textarea>',
			esc_attr( $field ),
			esc_attr( self::OPTION_NAME ),
			esc_attr( $field ),
			(int) $rows,
			esc_textarea( $value )
		);

		if ( ! empty( $args['description'] ) ) {
			printf( '<p class="description">%s</p>', esc_html( $args['description'] ) );
		}
	}

	/**
	 * Render a select dropdown field.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Field arguments.
	 * @return void
	 */
	public function render_select_field( array $args ): void {
		$field   = $args['field'];
		$value   = $this->get( $field );
		$options = $args['options'] ?? [];

		printf(
			'<select id="%s" name="%s[%s]">',
			esc_attr( $field ),
			esc_attr( self::OPTION_NAME ),
			esc_attr( $field )
		);

		foreach ( $options as $option_value => $option_label ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $option_value ),
				selected( $value, $option_value, false ),
				esc_html( $option_label )
			);
		}

		echo '</select>';

		if ( ! empty( $args['description'] ) ) {
			printf( '<p class="description">%s</p>', esc_html( $args['description'] ) );
		}
	}

	/**
	 * Render a page select dropdown field.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Field arguments.
	 * @return void
	 */
	public function render_page_select_field( array $args ): void {
		$field = sanitize_key( $args['field'] );
		$value = $this->get( $field );

		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_dropdown_pages handles escaping internally.
		wp_dropdown_pages(
			[
				'name'              => self::OPTION_NAME . '[' . $field . ']',
				'id'                => $field,
				'selected'          => (int) $value,
				'show_option_none'  => __( '— Select Page —', 'all-purpose-directory' ),
				'option_none_value' => 0,
			]
		);
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped

		if ( ! empty( $args['description'] ) ) {
			printf( '<p class="description">%s</p>', esc_html( $args['description'] ) );
		}
	}

	/**
	 * Render a checkbox field.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Field arguments.
	 * @return void
	 */
	public function render_checkbox_field( array $args ): void {
		$field = $args['field'];
		$value = $this->get( $field );
		$label = $args['label'] ?? '';

		printf(
			'<label for="%s"><input type="checkbox" id="%s" name="%s[%s]" value="1" %s> %s</label>',
			esc_attr( $field ),
			esc_attr( $field ),
			esc_attr( self::OPTION_NAME ),
			esc_attr( $field ),
			checked( $value, true, false ),
			esc_html( $label )
		);

		if ( ! empty( $args['description'] ) ) {
			printf( '<p class="description">%s</p>', esc_html( $args['description'] ) );
		}
	}

	/**
	 * Render a roles checkbox field.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Field arguments.
	 * @return void
	 */
	public function render_roles_field( array $args ): void {
		$field          = $args['field'];
		$selected       = $this->get( $field, [] );
		$who_can        = $this->get( 'who_can_submit' );
		$hidden_class   = $who_can !== 'specific_roles' ? 'hidden' : '';
		$editable_roles = wp_roles()->roles;

		printf(
			'<fieldset id="%s-wrapper" class="apd-roles-field %s">',
			esc_attr( $field ),
			esc_attr( $hidden_class )
		);

		foreach ( $editable_roles as $role_slug => $role_data ) {
			// Skip administrator - they always have access.
			if ( 'administrator' === $role_slug ) {
				continue;
			}

			$checked = in_array( $role_slug, (array) $selected, true );

			printf(
				'<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="%s[%s][]" value="%s" %s> %s</label>',
				esc_attr( self::OPTION_NAME ),
				esc_attr( $field ),
				esc_attr( $role_slug ),
				checked( $checked, true, false ),
				esc_html( translate_user_role( $role_data['name'] ) )
			);
		}

		echo '</fieldset>';

		if ( ! empty( $args['description'] ) ) {
			printf(
				'<p class="description" id="%s-description" %s>%s</p>',
				esc_attr( $field ),
				$who_can !== 'specific_roles' ? 'style="display:none;"' : '',
				esc_html( $args['description'] )
			);
		}
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		// Only load on our settings page.
		if ( 'apd_listing_page_' . self::PAGE_SLUG !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'apd-admin-base',
			APD_PLUGIN_URL . 'assets/css/admin-base.css',
			[],
			APD_VERSION
		);

		wp_enqueue_style(
			'apd-admin-settings',
			APD_PLUGIN_URL . 'assets/css/admin-settings.css',
			[ 'apd-admin-base' ],
			APD_VERSION
		);

		wp_enqueue_script(
			'apd-admin-settings',
			APD_PLUGIN_URL . 'assets/js/admin-settings.js',
			[ 'jquery' ],
			APD_VERSION,
			true
		);

		// Enqueue color picker for certain tabs.
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
	}

	/**
	 * Get the current active tab.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_current_tab(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';

		if ( ! array_key_exists( $tab, $this->tabs ) ) {
			$tab = 'general';
		}

		return $tab;
	}

	/**
	 * Get all registered tabs.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array{label: string, callback: callable}>
	 */
	public function get_tabs(): array {
		return $this->tabs;
	}

	/**
	 * Check if a tab exists.
	 *
	 * @since 1.0.0
	 *
	 * @param string $tab_id Tab identifier.
	 * @return bool
	 */
	public function has_tab( string $tab_id ): bool {
		return array_key_exists( $tab_id, $this->tabs );
	}

	/**
	 * Render the settings page.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function render_page(): void {
		// Check user capabilities.
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'all-purpose-directory' ) );
		}

		$current_tab = $this->get_current_tab();

		/**
		 * Fires before settings page is rendered.
		 *
		 * @since 1.0.0
		 *
		 * @param string $current_tab The current active tab.
		 */
		do_action( 'apd_before_settings_page', $current_tab );

		?>
		<div class="wrap apd-settings-wrap">
			<div class="apd-page-header">
				<div class="apd-page-header__icon">
					<span class="dashicons dashicons-admin-settings" aria-hidden="true"></span>
				</div>
				<div class="apd-page-header__content">
					<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
				</div>
				<a href="https://damoiseau.xyz/docs/all-purpose-directory/user-guide/#settings" class="apd-page-header__docs" target="_blank" rel="noopener noreferrer">
					<span class="dashicons dashicons-external" aria-hidden="true"></span>
					<?php esc_html_e( 'Documentation', 'all-purpose-directory' ); ?>
				</a>
			</div>

			<?php $this->render_tabs( $current_tab ); ?>

			<div class="apd-settings-content">
				<?php
				// Show settings saved notice.
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] === 'true' ) {
					?>
					<div class="notice notice-success is-dismissible">
						<p><?php esc_html_e( 'Settings saved.', 'all-purpose-directory' ); ?></p>
					</div>
					<?php
				}
				?>

				<form method="post" action="options.php" class="apd-settings-form">
					<?php
					settings_fields( self::OPTION_GROUP );
					?>
					<input type="hidden" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[_active_tab]" value="<?php echo esc_attr( $current_tab ); ?>" />
					<?php

					// Render the current tab content.
					$this->render_tab_content( $current_tab );

					submit_button();
					?>
				</form>
			</div>
		</div>
		<?php

		/**
		 * Fires after settings page is rendered.
		 *
		 * @since 1.0.0
		 *
		 * @param string $current_tab The current active tab.
		 */
		do_action( 'apd_after_settings_page', $current_tab );
	}

	/**
	 * Render the tab navigation.
	 *
	 * @since 1.0.0
	 *
	 * @param string $current_tab The current active tab.
	 * @return void
	 */
	public function render_tabs( string $current_tab ): void {
		$base_url = admin_url( self::PARENT_MENU . '&page=' . self::PAGE_SLUG );

		?>
		<nav class="nav-tab-wrapper apd-settings-tabs" aria-label="<?php esc_attr_e( 'Settings tabs', 'all-purpose-directory' ); ?>">
			<?php foreach ( $this->tabs as $tab_id => $tab ) : ?>
				<?php
				$tab_url   = add_query_arg( 'tab', $tab_id, $base_url );
				$is_active = $current_tab === $tab_id;
				$classes   = 'nav-tab';
				if ( $is_active ) {
					$classes .= ' nav-tab-active';
				}
				?>
				<a href="<?php echo esc_url( $tab_url ); ?>"
					class="<?php echo esc_attr( $classes ); ?>"
					<?php echo $is_active ? 'aria-current="page"' : ''; ?>>
					<?php echo esc_html( $tab['label'] ); ?>
				</a>
			<?php endforeach; ?>
		</nav>
		<?php
	}

	/**
	 * Render the tab content.
	 *
	 * @since 1.0.0
	 *
	 * @param string $tab_id The tab identifier.
	 * @return void
	 */
	public function render_tab_content( string $tab_id ): void {
		if ( ! isset( $this->tabs[ $tab_id ] ) ) {
			return;
		}

		$tab = $this->tabs[ $tab_id ];

		/**
		 * Fires before tab content is rendered.
		 *
		 * @since 1.0.0
		 *
		 * @param string $tab_id The tab identifier.
		 */
		do_action( 'apd_before_settings_tab', $tab_id );

		?>
		<div class="apd-settings-tab apd-settings-tab--<?php echo esc_attr( $tab_id ); ?>">
			<?php
			if ( is_callable( $tab['callback'] ) ) {
				call_user_func( $tab['callback'] );
			}
			?>
		</div>
		<?php

		/**
		 * Fires after tab content is rendered.
		 *
		 * @since 1.0.0
		 *
		 * @param string $tab_id The tab identifier.
		 */
		do_action( 'apd_after_settings_tab', $tab_id );
	}

	/**
	 * Render the General settings tab.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function render_general_tab(): void {
		do_settings_sections( self::PAGE_SLUG . '_general' );
	}

	/**
	 * Render the Listings settings tab.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function render_listings_tab(): void {
		do_settings_sections( self::PAGE_SLUG . '_listings' );
	}

	/**
	 * Render the Submission settings tab.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function render_submission_tab(): void {
		do_settings_sections( self::PAGE_SLUG . '_submission' );
	}

	/**
	 * Render the Display settings tab.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function render_display_tab(): void {
		do_settings_sections( self::PAGE_SLUG . '_display' );
	}

	/**
	 * Render the Email settings tab.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function render_email_tab(): void {
		do_settings_sections( self::PAGE_SLUG . '_email' );
	}

	/**
	 * Render the Advanced settings tab.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function render_advanced_tab(): void {
		do_settings_sections( self::PAGE_SLUG . '_advanced' );
	}

	/**
	 * Get default settings values.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed>
	 */
	public function get_defaults(): array {
		$defaults = [
			// General.
			'currency_symbol'     => '$',
			'currency_position'   => 'before',
			'date_format'         => 'default',
			'distance_unit'       => 'km',
			'directory_page'      => 0,
			'submit_page'         => 0,
			'dashboard_page'      => 0,

			// Listings.
			'listings_per_page'   => 12,
			'default_status'      => 'pending',
			'expiration_days'     => 0,
			'enable_reviews'      => true,
			'enable_favorites'    => true,
			'enable_contact_form' => true,

			// Submission.
			'who_can_submit'      => 'logged_in',
			'submission_roles'    => [],
			'guest_submission'    => false,
			'terms_page'          => 0,
			'redirect_after'      => 'listing',
			'redirect_custom_url' => '',

			// Display.
			'default_view'        => 'grid',
			'grid_columns'        => 3,
			'show_thumbnail'      => true,
			'show_excerpt'        => true,
			'show_category'       => true,
			'show_rating'         => true,
			'show_favorite'       => true,
			'archive_title'       => '',
			'single_layout'       => 'sidebar',

			// Email.
			'from_name'           => '',
			'from_email'          => '',
			'admin_email'         => '',
			'notify_submission'   => true,
			'notify_approved'     => true,
			'notify_rejected'     => true,
			'notify_expiring'     => true,
			'notify_review'       => true,
			'notify_inquiry'      => true,

			// Advanced.
			'delete_data'         => false,
			'custom_css'          => '',
			'debug_mode'          => false,
		];

		/**
		 * Filter the default settings values.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, mixed> $defaults The default settings.
		 */
		return apply_filters( 'apd_settings_defaults', $defaults );
	}

	/**
	 * Get a single setting value.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key     The setting key.
	 * @param mixed  $default Optional. Default value if not set.
	 * @return mixed
	 */
	public function get( string $key, $default = null ) {
		$options  = get_option( self::OPTION_NAME, [] );
		$defaults = $this->get_defaults();

		if ( $default === null && isset( $defaults[ $key ] ) ) {
			$default = $defaults[ $key ];
		}

		return $options[ $key ] ?? $default;
	}

	/**
	 * Get all settings values.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed>
	 */
	public function get_all(): array {
		$options  = get_option( self::OPTION_NAME, [] );
		$defaults = $this->get_defaults();

		return array_merge( $defaults, $options );
	}

	/**
	 * Update a single setting value.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key   The setting key.
	 * @param mixed  $value The setting value.
	 * @return bool
	 */
	public function set( string $key, $value ): bool {
		$options         = get_option( self::OPTION_NAME, [] );
		$options[ $key ] = $value;

		return update_option( self::OPTION_NAME, $options );
	}

	/**
	 * Sanitize settings before saving.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $input The raw input.
	 * @return array<string, mixed>
	 */
	public function sanitize_settings( $input ): array {
		$sanitized = [];
		$defaults  = $this->get_defaults();
		$existing  = get_option( self::OPTION_NAME, [] );

		// General settings.
		$sanitized['currency_symbol'] = isset( $input['currency_symbol'] )
			? sanitize_text_field( $input['currency_symbol'] )
			: ( $existing['currency_symbol'] ?? $defaults['currency_symbol'] );

		$sanitized['currency_position'] = isset( $input['currency_position'] )
			&& in_array( $input['currency_position'], [ 'before', 'after' ], true )
			? $input['currency_position']
			: ( $existing['currency_position'] ?? $defaults['currency_position'] );

		$sanitized['date_format'] = isset( $input['date_format'] )
			? sanitize_text_field( $input['date_format'] )
			: ( $existing['date_format'] ?? $defaults['date_format'] );

		$sanitized['distance_unit'] = isset( $input['distance_unit'] )
			&& in_array( $input['distance_unit'], [ 'km', 'miles' ], true )
			? $input['distance_unit']
			: ( $existing['distance_unit'] ?? $defaults['distance_unit'] );

		// Page settings.
		$sanitized['directory_page'] = isset( $input['directory_page'] )
			? absint( $input['directory_page'] )
			: ( $existing['directory_page'] ?? $defaults['directory_page'] );

		$sanitized['submit_page'] = isset( $input['submit_page'] )
			? absint( $input['submit_page'] )
			: ( $existing['submit_page'] ?? $defaults['submit_page'] );

		$sanitized['dashboard_page'] = isset( $input['dashboard_page'] )
			? absint( $input['dashboard_page'] )
			: ( $existing['dashboard_page'] ?? $defaults['dashboard_page'] );

		// Listings settings.
		$sanitized['listings_per_page'] = isset( $input['listings_per_page'] )
			? absint( $input['listings_per_page'] )
			: ( $existing['listings_per_page'] ?? $defaults['listings_per_page'] );
		$sanitized['listings_per_page'] = max( 1, min( 100, $sanitized['listings_per_page'] ) );

		$sanitized['default_status'] = isset( $input['default_status'] )
			&& in_array( $input['default_status'], [ 'publish', 'pending', 'draft' ], true )
			? $input['default_status']
			: ( $existing['default_status'] ?? $defaults['default_status'] );

		$sanitized['expiration_days'] = isset( $input['expiration_days'] )
			? absint( $input['expiration_days'] )
			: ( $existing['expiration_days'] ?? $defaults['expiration_days'] );

		// Checkbox fields are only submitted when checked. When saving a tab
		// that doesn't contain these fields, preserve the existing values.
		$active_tab = $input['_active_tab'] ?? '';

		if ( 'listings' === $active_tab ) {
			$sanitized['enable_reviews']      = ! empty( $input['enable_reviews'] );
			$sanitized['enable_favorites']    = ! empty( $input['enable_favorites'] );
			$sanitized['enable_contact_form'] = ! empty( $input['enable_contact_form'] );
		} else {
			$sanitized['enable_reviews']      = ! empty( $existing['enable_reviews'] ?? $defaults['enable_reviews'] );
			$sanitized['enable_favorites']    = ! empty( $existing['enable_favorites'] ?? $defaults['enable_favorites'] );
			$sanitized['enable_contact_form'] = ! empty( $existing['enable_contact_form'] ?? $defaults['enable_contact_form'] );
		}

		// Submission settings.
		$sanitized['who_can_submit'] = isset( $input['who_can_submit'] )
			&& in_array( $input['who_can_submit'], [ 'anyone', 'logged_in', 'specific_roles' ], true )
			? $input['who_can_submit']
			: ( $existing['who_can_submit'] ?? $defaults['who_can_submit'] );

		// Sanitize submission roles - only valid WordPress role slugs.
		$sanitized['submission_roles'] = [];
		if ( ! empty( $input['submission_roles'] ) && is_array( $input['submission_roles'] ) ) {
			$valid_roles = array_keys( wp_roles()->roles );
			foreach ( $input['submission_roles'] as $role ) {
				$role = sanitize_key( $role );
				if ( in_array( $role, $valid_roles, true ) ) {
					$sanitized['submission_roles'][] = $role;
				}
			}
		}

		if ( 'submission' === $active_tab ) {
			$sanitized['guest_submission'] = ! empty( $input['guest_submission'] );
		} else {
			$sanitized['guest_submission'] = ! empty( $existing['guest_submission'] ?? $defaults['guest_submission'] );
		}

		$sanitized['terms_page'] = isset( $input['terms_page'] )
			? absint( $input['terms_page'] )
			: ( $existing['terms_page'] ?? $defaults['terms_page'] );

		$sanitized['redirect_after'] = isset( $input['redirect_after'] )
			&& in_array( $input['redirect_after'], [ 'listing', 'dashboard', 'custom' ], true )
			? $input['redirect_after']
			: ( $existing['redirect_after'] ?? $defaults['redirect_after'] );

		$sanitized['redirect_custom_url'] = isset( $input['redirect_custom_url'] )
			? esc_url_raw( trim( $input['redirect_custom_url'] ) )
			: ( $existing['redirect_custom_url'] ?? $defaults['redirect_custom_url'] );

		// Display settings.
		$sanitized['default_view'] = isset( $input['default_view'] )
			&& in_array( $input['default_view'], [ 'grid', 'list' ], true )
			? $input['default_view']
			: ( $existing['default_view'] ?? $defaults['default_view'] );

		$sanitized['grid_columns'] = isset( $input['grid_columns'] )
			? absint( $input['grid_columns'] )
			: ( $existing['grid_columns'] ?? $defaults['grid_columns'] );
		$sanitized['grid_columns'] = max( 2, min( 4, $sanitized['grid_columns'] ) );

		if ( 'display' === $active_tab ) {
			$sanitized['show_thumbnail'] = ! empty( $input['show_thumbnail'] );
			$sanitized['show_excerpt']   = ! empty( $input['show_excerpt'] );
			$sanitized['show_category']  = ! empty( $input['show_category'] );
			$sanitized['show_rating']    = ! empty( $input['show_rating'] );
			$sanitized['show_favorite']  = ! empty( $input['show_favorite'] );
		} else {
			$sanitized['show_thumbnail'] = ! empty( $existing['show_thumbnail'] ?? $defaults['show_thumbnail'] );
			$sanitized['show_excerpt']   = ! empty( $existing['show_excerpt'] ?? $defaults['show_excerpt'] );
			$sanitized['show_category']  = ! empty( $existing['show_category'] ?? $defaults['show_category'] );
			$sanitized['show_rating']    = ! empty( $existing['show_rating'] ?? $defaults['show_rating'] );
			$sanitized['show_favorite']  = ! empty( $existing['show_favorite'] ?? $defaults['show_favorite'] );
		}

		$sanitized['archive_title'] = isset( $input['archive_title'] )
			? sanitize_text_field( $input['archive_title'] )
			: ( $existing['archive_title'] ?? $defaults['archive_title'] );

		$sanitized['single_layout'] = isset( $input['single_layout'] )
			&& in_array( $input['single_layout'], [ 'full', 'sidebar' ], true )
			? $input['single_layout']
			: ( $existing['single_layout'] ?? $defaults['single_layout'] );

		// Email settings.
		$sanitized['from_name'] = isset( $input['from_name'] )
			? sanitize_text_field( $input['from_name'] )
			: ( $existing['from_name'] ?? $defaults['from_name'] );

		$sanitized['from_email'] = isset( $input['from_email'] )
			? sanitize_email( $input['from_email'] )
			: ( $existing['from_email'] ?? $defaults['from_email'] );

		$sanitized['admin_email'] = isset( $input['admin_email'] )
			? sanitize_email( $input['admin_email'] )
			: ( $existing['admin_email'] ?? $defaults['admin_email'] );

		if ( 'email' === $active_tab ) {
			$sanitized['notify_submission'] = ! empty( $input['notify_submission'] );
			$sanitized['notify_approved']   = ! empty( $input['notify_approved'] );
			$sanitized['notify_rejected']   = ! empty( $input['notify_rejected'] );
			$sanitized['notify_expiring']   = ! empty( $input['notify_expiring'] );
			$sanitized['notify_review']     = ! empty( $input['notify_review'] );
			$sanitized['notify_inquiry']    = ! empty( $input['notify_inquiry'] );
		} else {
			$sanitized['notify_submission'] = ! empty( $existing['notify_submission'] ?? $defaults['notify_submission'] );
			$sanitized['notify_approved']   = ! empty( $existing['notify_approved'] ?? $defaults['notify_approved'] );
			$sanitized['notify_rejected']   = ! empty( $existing['notify_rejected'] ?? $defaults['notify_rejected'] );
			$sanitized['notify_expiring']   = ! empty( $existing['notify_expiring'] ?? $defaults['notify_expiring'] );
			$sanitized['notify_review']     = ! empty( $existing['notify_review'] ?? $defaults['notify_review'] );
			$sanitized['notify_inquiry']    = ! empty( $existing['notify_inquiry'] ?? $defaults['notify_inquiry'] );
		}

		// Advanced settings.
		if ( 'advanced' === $active_tab ) {
			$sanitized['delete_data'] = ! empty( $input['delete_data'] );
			$sanitized['debug_mode']  = ! empty( $input['debug_mode'] );
		} else {
			$sanitized['delete_data'] = ! empty( $existing['delete_data'] ?? $defaults['delete_data'] );
			$sanitized['debug_mode']  = ! empty( $existing['debug_mode'] ?? $defaults['debug_mode'] );
		}

		$sanitized['custom_css'] = isset( $input['custom_css'] )
			? wp_strip_all_tags( $input['custom_css'] )
			: ( $existing['custom_css'] ?? $defaults['custom_css'] );

		/**
		 * Filter the sanitized settings.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, mixed> $sanitized The sanitized settings.
		 * @param array<string, mixed> $input     The raw input.
		 */
		return apply_filters( 'apd_sanitize_settings', $sanitized, $input );
	}

	/**
	 * Get the settings page URL.
	 *
	 * @since 1.0.0
	 *
	 * @param string $tab Optional. Tab to link to.
	 * @return string
	 */
	public function get_settings_url( string $tab = '' ): string {
		$url = admin_url( self::PARENT_MENU . '&page=' . self::PAGE_SLUG );

		if ( ! empty( $tab ) && $this->has_tab( $tab ) ) {
			$url = add_query_arg( 'tab', $tab, $url );
		}

		return $url;
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
