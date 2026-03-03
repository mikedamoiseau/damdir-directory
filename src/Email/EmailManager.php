<?php
/**
 * Email Manager class.
 *
 * Handles email notifications with template support and placeholder replacement.
 *
 * @package All_Purpose_Directory
 * @since 1.0.0
 */

declare(strict_types=1);

namespace APD\Email;

use APD\Core\Config;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * EmailManager class.
 *
 * Centralized email management with HTML templates and placeholder replacement.
 */
class EmailManager {

	/**
	 * Single instance.
	 *
	 * @var EmailManager|null
	 */
	private static ?EmailManager $instance = null;

	/**
	 * Registered placeholders.
	 *
	 * @var array<string, callable>
	 */
	private array $placeholders = [];

	/**
	 * Configuration.
	 *
	 * @var array
	 */
	private array $config = [
		'from_name'     => '',
		'from_email'    => '',
		'admin_email'   => '',
		'content_type'  => 'text/html',
		'charset'       => 'UTF-8',
		'enable_html'   => true,
		'use_templates' => true,
		'notifications' => [
			'listing_submitted' => true,
			'listing_approved'  => true,
			'listing_rejected'  => true,
			'listing_expiring'  => true,
			'listing_expired'   => true,
			'new_review'        => true,
			'new_inquiry'       => true,
		],
	];

	/**
	 * Configuration service.
	 *
	 * @var Config
	 */
	private Config $config_service;

	/**
	 * Get single instance.
	 *
	 * @param array       $config         Optional. Configuration options.
	 * @param Config|null $config_service Optional. Configuration service.
	 * @return EmailManager
	 */
	public static function get_instance( array $config = [], ?Config $config_service = null ): EmailManager {
		if ( null === self::$instance || ! empty( $config ) || $config_service instanceof Config ) {
			self::$instance = new self( $config, $config_service );
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @param array       $config         Configuration options.
	 * @param Config|null $config_service Optional. Configuration service.
	 */
	private function __construct( array $config = [], ?Config $config_service = null ) {
		$this->config_service = $config_service ?? new Config();
		$this->config         = array_merge( $this->config, $config );
		$this->register_default_placeholders();
	}

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
	 * Initialize hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		// Load email settings from plugin options.
		$this->load_settings();

		// Hook into listing submission.
		add_action( 'apd_after_submission', [ $this, 'on_listing_submitted' ], 10, 2 );

		// Hook into listing status changes.
		add_action( 'apd_listing_status_changed', [ $this, 'on_listing_status_changed' ], 10, 3 );

		// Hook into new review.
		add_action( 'apd_review_created', [ $this, 'on_review_created' ], 10, 3 );

		// Hook into new inquiry.
		add_action( 'apd_inquiry_logged', [ $this, 'on_inquiry_logged' ], 10, 2 );

		/**
		 * Fires after email manager initializes.
		 *
		 * @since 1.0.0
		 */
		do_action( 'apd_email_manager_init' );
	}

	/**
	 * Load email settings from plugin options.
	 *
	 * Reads from_name, from_email, admin_email, and notification toggles
	 * from the apd_options WordPress option (managed by the Settings class).
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function load_settings(): void {
		$options = $this->config_service->get_option( 'apd_options', [] );

		if ( ! is_array( $options ) ) {
			return;
		}

		$config = [];

		if ( ! empty( $options['from_name'] ) ) {
			$config['from_name'] = $options['from_name'];
		}

		if ( ! empty( $options['from_email'] ) ) {
			$config['from_email'] = $options['from_email'];
		}

		if ( ! empty( $options['admin_email'] ) ) {
			$config['admin_email'] = $options['admin_email'];
		}

		// Map Settings notification keys to EmailManager notification config.
		$notification_map = [
			'notify_submission' => 'listing_submitted',
			'notify_approved'   => 'listing_approved',
			'notify_rejected'   => 'listing_rejected',
			'notify_expiring'   => 'listing_expiring',
			'notify_review'     => 'new_review',
			'notify_inquiry'    => 'new_inquiry',
		];

		foreach ( $notification_map as $setting_key => $notification_key ) {
			if ( isset( $options[ $setting_key ] ) ) {
				$config['notifications'][ $notification_key ] = (bool) $options[ $setting_key ];
			}
		}

		if ( ! empty( $config ) ) {
			$this->set_config( $config );
		}
	}

	/**
	 * Register default placeholders.
	 *
	 * @return void
	 */
	private function register_default_placeholders(): void {
		// Site placeholders.
		$this->register_placeholder(
			'site_name',
			function () {
				return get_bloginfo( 'name' );
			}
		);

		$this->register_placeholder(
			'site_url',
			function () {
				return home_url();
			}
		);

		$this->register_placeholder(
			'admin_email',
			function () {
				return $this->get_admin_email();
			}
		);

		$this->register_placeholder(
			'current_date',
			function () {
				return wp_date( (string) $this->config_service->get_option( 'date_format' ) );
			}
		);

		$this->register_placeholder(
			'current_time',
			function () {
				return wp_date( (string) $this->config_service->get_option( 'time_format' ) );
			}
		);
	}

	/**
	 * Register a placeholder.
	 *
	 * @param string   $name     Placeholder name (without braces).
	 * @param callable $callback Callback returning the replacement value.
	 * @return self
	 */
	public function register_placeholder( string $name, callable $callback ): self {
		$this->placeholders[ $name ] = $callback;
		return $this;
	}

	/**
	 * Unregister a placeholder.
	 *
	 * @param string $name Placeholder name.
	 * @return self
	 */
	public function unregister_placeholder( string $name ): self {
		unset( $this->placeholders[ $name ] );
		return $this;
	}

	/**
	 * Get registered placeholders.
	 *
	 * @return array<string, callable>
	 */
	public function get_placeholders(): array {
		return $this->placeholders;
	}

	/**
	 * Replace placeholders in text.
	 *
	 * Placeholders use format: {placeholder_name}
	 *
	 * @param string               $text    Text containing placeholders.
	 * @param array<string, mixed> $context Additional context data for placeholders.
	 * @return string
	 */
	public function replace_placeholders( string $text, array $context = [] ): string {
		// First, replace context-based placeholders.
		foreach ( $context as $key => $value ) {
			if ( is_scalar( $value ) ) {
				$text = str_replace( '{' . $key . '}', (string) $value, $text );
			}
		}

		// Then, replace registered dynamic placeholders.
		foreach ( $this->placeholders as $name => $callback ) {
			$placeholder = '{' . $name . '}';
			if ( strpos( $text, $placeholder ) !== false ) {
				$value = call_user_func( $callback, $context );
				$text  = str_replace( $placeholder, (string) $value, $text );
			}
		}

		/**
		 * Filter the text after placeholder replacement.
		 *
		 * @since 1.0.0
		 * @param string $text    The processed text.
		 * @param array  $context The context data.
		 */
		return apply_filters( 'apd_email_replace_placeholders', $text, $context );
	}

	/**
	 * Send an email.
	 *
	 * @param string       $to      Recipient email address.
	 * @param string       $subject Email subject.
	 * @param string       $message Email body.
	 * @param array        $headers Additional headers.
	 * @param array        $context Placeholder context.
	 * @param array|string $attachments Attachments.
	 * @return bool
	 */
	public function send(
		string $to,
		string $subject,
		string $message,
		array $headers = [],
		array $context = [],
		array|string $attachments = []
	): bool {
		// Replace placeholders in subject and message.
		$subject = $this->replace_placeholders( $subject, $context );
		$message = $this->replace_placeholders( $message, $context );

		// Build headers.
		$default_headers = $this->get_default_headers();
		$headers         = array_merge( $default_headers, $headers );

		/**
		 * Filter the email recipient.
		 *
		 * @since 1.0.0
		 * @param string $to      Recipient email.
		 * @param string $subject Email subject.
		 * @param array  $context Context data.
		 */
		$to = apply_filters( 'apd_email_to', $to, $subject, $context );

		/**
		 * Filter the email subject.
		 *
		 * @since 1.0.0
		 * @param string $subject Email subject.
		 * @param string $to      Recipient email.
		 * @param array  $context Context data.
		 */
		$subject = apply_filters( 'apd_email_subject', $subject, $to, $context );

		/**
		 * Filter the email message.
		 *
		 * @since 1.0.0
		 * @param string $message Email message.
		 * @param string $to      Recipient email.
		 * @param string $subject Email subject.
		 * @param array  $context Context data.
		 */
		$message = apply_filters( 'apd_email_message', $message, $to, $subject, $context );

		/**
		 * Filter the email headers.
		 *
		 * @since 1.0.0
		 * @param array  $headers Email headers.
		 * @param string $to      Recipient email.
		 * @param string $subject Email subject.
		 * @param array  $context Context data.
		 */
		$headers = apply_filters( 'apd_email_headers', $headers, $to, $subject, $context );

		/**
		 * Fires before sending an email.
		 *
		 * @since 1.0.0
		 * @param string $to      Recipient email.
		 * @param string $subject Email subject.
		 * @param string $message Email message.
		 * @param array  $headers Email headers.
		 * @param array  $context Context data.
		 */
		do_action( 'apd_before_send_email', $to, $subject, $message, $headers, $context );

		// Send the email.
		$sent = wp_mail( $to, $subject, $message, $headers, $attachments );

		/**
		 * Fires after sending an email.
		 *
		 * @since 1.0.0
		 * @param bool   $sent    Whether the email was sent.
		 * @param string $to      Recipient email.
		 * @param string $subject Email subject.
		 * @param array  $context Context data.
		 */
		do_action( 'apd_after_send_email', $sent, $to, $subject, $context );

		return $sent;
	}

	/**
	 * Get default email headers.
	 *
	 * @return array
	 */
	public function get_default_headers(): array {
		$headers = [];

		// Content type.
		if ( $this->config['enable_html'] ) {
			$headers[] = sprintf(
				'Content-Type: %s; charset=%s',
				$this->config['content_type'],
				$this->config['charset']
			);
		}

		// From header.
		$from_name  = $this->get_from_name();
		$from_email = $this->get_from_email();

		if ( $from_name && $from_email ) {
			$headers[] = sprintf( 'From: %s <%s>', $from_name, $from_email );
		}

		return $headers;
	}

	/**
	 * Get from name.
	 *
	 * @return string
	 */
	public function get_from_name(): string {
		$name = $this->config['from_name'];

		if ( empty( $name ) ) {
			$name = get_bloginfo( 'name' );
		}

		/**
		 * Filter the from name for emails.
		 *
		 * @since 1.0.0
		 * @param string $name From name.
		 */
		return apply_filters( 'apd_email_from_name', $name );
	}

	/**
	 * Get from email.
	 *
	 * @return string
	 */
	public function get_from_email(): string {
		$email = $this->config['from_email'];

		if ( empty( $email ) ) {
			$email = (string) $this->config_service->get_option( 'admin_email' );
		}

		/**
		 * Filter the from email for emails.
		 *
		 * @since 1.0.0
		 * @param string $email From email.
		 */
		return apply_filters( 'apd_email_from_email', $email );
	}

	/**
	 * Get admin email for notifications.
	 *
	 * @return string
	 */
	public function get_admin_email(): string {
		$email = $this->config['admin_email'];

		if ( empty( $email ) ) {
			$email = (string) $this->config_service->get_option( 'admin_email' );
		}

		/**
		 * Filter the admin email for notifications.
		 *
		 * @since 1.0.0
		 * @param string $email Admin email.
		 */
		return apply_filters( 'apd_email_admin_email', $email );
	}

	/**
	 * Check if a notification type is enabled.
	 *
	 * @param string $type Notification type.
	 * @return bool
	 */
	public function is_notification_enabled( string $type ): bool {
		$enabled = $this->config['notifications'][ $type ] ?? false;

		/**
		 * Filter whether a notification type is enabled.
		 *
		 * @since 1.0.0
		 * @param bool   $enabled Whether enabled.
		 * @param string $type    Notification type.
		 */
		return apply_filters( 'apd_email_notification_enabled', $enabled, $type );
	}

	/**
	 * Enable or disable a notification type.
	 *
	 * @param string $type    Notification type.
	 * @param bool   $enabled Whether to enable.
	 * @return self
	 */
	public function set_notification_enabled( string $type, bool $enabled ): self {
		$this->config['notifications'][ $type ] = $enabled;
		return $this;
	}

	/**
	 * Get listing-related context for placeholders.
	 *
	 * @param int|\WP_Post $listing Listing ID or post object.
	 * @return array
	 */
	public function get_listing_context( int|\WP_Post $listing ): array {
		if ( is_int( $listing ) ) {
			$listing = get_post( $listing );
		}

		if ( ! $listing || $listing->post_type !== 'apd_listing' ) {
			return [];
		}

		$author = get_userdata( $listing->post_author );

		return [
			'listing_id'       => $listing->ID,
			'listing_title'    => $listing->post_title,
			'listing_url'      => get_permalink( $listing->ID ),
			'listing_edit_url' => get_edit_post_link( $listing->ID, 'raw' ),
			'listing_status'   => $listing->post_status,
			'author_name'      => $author ? $author->display_name : '',
			'author_email'     => $author ? $author->user_email : '',
			'author_id'        => $listing->post_author,
			'admin_url'        => admin_url( 'edit.php?post_type=apd_listing' ),
		];
	}

	/**
	 * Get user-related context for placeholders.
	 *
	 * @param int|\WP_User $user User ID or object.
	 * @return array
	 */
	public function get_user_context( int|\WP_User $user ): array {
		if ( is_int( $user ) ) {
			$user = get_userdata( $user );
		}

		if ( ! $user ) {
			return [];
		}

		return [
			'user_id'         => $user->ID,
			'user_name'       => $user->display_name,
			'user_email'      => $user->user_email,
			'user_login'      => $user->user_login,
			'user_first_name' => $user->first_name,
			'user_last_name'  => $user->last_name,
		];
	}

	/**
	 * Get review-related context for placeholders.
	 *
	 * @param int|\WP_Comment $review Review ID or comment object.
	 * @return array
	 */
	public function get_review_context( int|\WP_Comment $review ): array {
		if ( is_int( $review ) ) {
			$review = get_comment( $review );
		}

		if ( ! $review ) {
			return [];
		}

		$rating = get_comment_meta( $review->comment_ID, '_apd_rating', true );
		$title  = get_comment_meta( $review->comment_ID, '_apd_review_title', true );

		return [
			'review_id'      => $review->comment_ID,
			'review_author'  => $review->comment_author,
			'review_email'   => $review->comment_author_email,
			'review_content' => $review->comment_content,
			'review_rating'  => $rating ? (int) $rating : 0,
			'review_title'   => $title ?: '',
			'review_date'    => get_comment_date( '', $review ),
		];
	}

	/**
	 * Handler for listing submitted action.
	 *
	 * @param int   $listing_id Listing ID.
	 * @param array $data       Submission data.
	 * @return void
	 */
	public function on_listing_submitted( int $listing_id, array $data ): void {
		if ( ! $this->is_notification_enabled( 'listing_submitted' ) ) {
			return;
		}

		$this->send_listing_submitted( $listing_id );
	}

	/**
	 * Handler for listing status changed action.
	 *
	 * @param int    $listing_id Listing ID.
	 * @param string $new_status New status.
	 * @param string $old_status Old status.
	 * @return void
	 */
	public function on_listing_status_changed( int $listing_id, string $new_status, string $old_status ): void {
		if ( $new_status === 'publish' && $old_status !== 'publish' ) {
			if ( $this->is_notification_enabled( 'listing_approved' ) ) {
				$this->send_listing_approved( $listing_id );
			}
		}

		if ( $new_status === 'rejected' ) {
			if ( $this->is_notification_enabled( 'listing_rejected' ) ) {
				$this->send_listing_rejected( $listing_id );
			}
		}

		if ( $new_status === 'expired' && $old_status !== 'expired' ) {
			if ( $this->is_notification_enabled( 'listing_expired' ) ) {
				$this->send_listing_expired( $listing_id );
			}
		}
	}

	/**
	 * Handler for review created action.
	 *
	 * @param int   $review_id  Review comment ID.
	 * @param int   $listing_id Listing post ID.
	 * @param array $data       Review data.
	 * @return void
	 */
	public function on_review_created( int $review_id, int $listing_id, array $data ): void {
		if ( ! $this->is_notification_enabled( 'new_review' ) ) {
			return;
		}

		$this->send_new_review( $review_id );
	}

	/**
	 * Handler for inquiry logged action.
	 *
	 * @param int   $inquiry_id Inquiry post ID.
	 * @param array $data       Inquiry data.
	 * @return void
	 */
	public function on_inquiry_logged( int $inquiry_id, array $data ): void {
		if ( ! $this->is_notification_enabled( 'new_inquiry' ) ) {
			return;
		}

		// Note: The contact handler already sends the email.
		// This hook is for additional processing if needed.
	}

	/**
	 * Send listing submitted notification to admin.
	 *
	 * @param int $listing_id Listing ID.
	 * @return bool
	 */
	public function send_listing_submitted( int $listing_id ): bool {
		$context = $this->get_listing_context( $listing_id );

		if ( empty( $context ) ) {
			return false;
		}

		$to      = $this->get_admin_email();
		$subject = __( '[{site_name}] New listing submitted: {listing_title}', 'all-purpose-directory' );
		$message = $this->get_template_html( 'listing-submitted', $context );

		return $this->send( $to, $subject, $message, [], $context );
	}

	/**
	 * Send listing approved notification to author.
	 *
	 * @param int $listing_id Listing ID.
	 * @return bool
	 */
	public function send_listing_approved( int $listing_id ): bool {
		$context = $this->get_listing_context( $listing_id );

		if ( empty( $context ) || empty( $context['author_email'] ) ) {
			return false;
		}

		$to      = $context['author_email'];
		$subject = __( '[{site_name}] Your listing has been approved: {listing_title}', 'all-purpose-directory' );
		$message = $this->get_template_html( 'listing-approved', $context );

		return $this->send( $to, $subject, $message, [], $context );
	}

	/**
	 * Send listing rejected notification to author.
	 *
	 * @param int    $listing_id Listing ID.
	 * @param string $reason     Optional rejection reason.
	 * @return bool
	 */
	public function send_listing_rejected( int $listing_id, string $reason = '' ): bool {
		$context = $this->get_listing_context( $listing_id );

		if ( empty( $context ) || empty( $context['author_email'] ) ) {
			return false;
		}

		$context['rejection_reason'] = $reason;

		$to      = $context['author_email'];
		$subject = __( '[{site_name}] Your listing was not approved: {listing_title}', 'all-purpose-directory' );
		$message = $this->get_template_html( 'listing-rejected', $context );

		return $this->send( $to, $subject, $message, [], $context );
	}

	/**
	 * Send listing expiring soon notification to author.
	 *
	 * @param int $listing_id Listing ID.
	 * @param int $days_left  Days until expiration.
	 * @return bool
	 */
	public function send_listing_expiring( int $listing_id, int $days_left = 7 ): bool {
		$context = $this->get_listing_context( $listing_id );

		if ( empty( $context ) || empty( $context['author_email'] ) ) {
			return false;
		}

		$context['days_left'] = $days_left;

		$to      = $context['author_email'];
		$subject = __( '[{site_name}] Your listing expires soon: {listing_title}', 'all-purpose-directory' );
		$message = $this->get_template_html( 'listing-expiring', $context );

		return $this->send( $to, $subject, $message, [], $context );
	}

	/**
	 * Send listing expired notification to author.
	 *
	 * @param int $listing_id Listing ID.
	 * @return bool
	 */
	public function send_listing_expired( int $listing_id ): bool {
		$context = $this->get_listing_context( $listing_id );

		if ( empty( $context ) || empty( $context['author_email'] ) ) {
			return false;
		}

		$to      = $context['author_email'];
		$subject = __( '[{site_name}] Your listing has expired: {listing_title}', 'all-purpose-directory' );
		$message = $this->get_template_html( 'listing-expired', $context );

		return $this->send( $to, $subject, $message, [], $context );
	}

	/**
	 * Send new review notification to listing author.
	 *
	 * @param int $review_id Review comment ID.
	 * @return bool
	 */
	public function send_new_review( int $review_id ): bool {
		$review = get_comment( $review_id );

		if ( ! $review ) {
			return false;
		}

		$listing_id = (int) $review->comment_post_ID;
		$context    = array_merge(
			$this->get_listing_context( $listing_id ),
			$this->get_review_context( $review_id )
		);

		if ( empty( $context['author_email'] ) ) {
			return false;
		}

		$to      = $context['author_email'];
		$subject = __( '[{site_name}] New review on your listing: {listing_title}', 'all-purpose-directory' );
		$message = $this->get_template_html( 'new-review', $context );

		return $this->send( $to, $subject, $message, [], $context );
	}

	/**
	 * Send new inquiry notification to listing author.
	 *
	 * @param int   $listing_id Listing ID.
	 * @param array $inquiry    Inquiry data.
	 * @return bool
	 */
	public function send_new_inquiry( int $listing_id, array $inquiry ): bool {
		$context = array_merge(
			$this->get_listing_context( $listing_id ),
			[
				'inquiry_name'    => $inquiry['name'] ?? '',
				'inquiry_email'   => $inquiry['email'] ?? '',
				'inquiry_phone'   => $inquiry['phone'] ?? '',
				'inquiry_message' => $inquiry['message'] ?? '',
			]
		);

		if ( empty( $context['author_email'] ) ) {
			return false;
		}

		$to      = $context['author_email'];
		$subject = __( '[{site_name}] New inquiry about your listing: {listing_title}', 'all-purpose-directory' );
		$message = $this->get_template_html( 'new-inquiry', $context );

		return $this->send( $to, $subject, $message, [], $context );
	}

	/**
	 * Get email template HTML with wrapper.
	 *
	 * @param string $template Template name (without extension).
	 * @param array  $context  Template context.
	 * @return string
	 */
	public function get_template_html( string $template, array $context = [] ): string {
		if ( ! $this->config['use_templates'] ) {
			return $this->get_plain_text_message( $template, $context );
		}

		// Try to load the template.
		$template_instance = \APD\Core\Template::get_instance();
		$template_file     = 'emails/' . $template . '.php';

		if ( ! $template_instance->template_exists( $template_file ) ) {
			// Fall back to plain text.
			return $this->get_plain_text_message( $template, $context );
		}

		// Load the email template with wrapper.
		$content = $template_instance->get_template_html( $template_file, $context );

		// Wrap in HTML email wrapper.
		return $this->wrap_html_email( $content, $context );
	}

	/**
	 * Wrap content in HTML email template.
	 *
	 * @param string $content Email content.
	 * @param array  $context Context data.
	 * @return string
	 */
	public function wrap_html_email( string $content, array $context = [] ): string {
		// Check for custom wrapper template if templates are enabled.
		if ( $this->config['use_templates'] ) {
			$template = \APD\Core\Template::get_instance();

			if ( $template->template_exists( 'emails/email-wrapper.php' ) ) {
				return $template->get_template_html(
					'emails/email-wrapper.php',
					array_merge( $context, [ 'email_content' => $content ] )
				);
			}
		}

		// Default wrapper.
		$site_name = get_bloginfo( 'name' );
		$site_url  = home_url();

		return sprintf(
			'<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>%1$s</title>
<style type="text/css">
body { margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; font-size: 16px; line-height: 1.5; color: #333; background-color: #f5f5f5; }
.email-wrapper { max-width: 600px; margin: 0 auto; padding: 20px; }
.email-header { background-color: #0073aa; color: #fff; padding: 20px; text-align: center; border-radius: 4px 4px 0 0; }
.email-header h1 { margin: 0; font-size: 24px; }
.email-body { background-color: #fff; padding: 30px; border: 1px solid #ddd; border-top: none; }
.email-footer { background-color: #f9f9f9; padding: 20px; text-align: center; font-size: 12px; color: #666; border: 1px solid #ddd; border-top: none; border-radius: 0 0 4px 4px; }
.email-footer a { color: #0073aa; text-decoration: none; }
.button { display: inline-block; padding: 12px 24px; background-color: #0073aa; color: #fff; text-decoration: none; border-radius: 4px; }
.button:hover { background-color: #005a87; }
</style>
</head>
<body>
<div class="email-wrapper">
<div class="email-header">
<h1>%1$s</h1>
</div>
<div class="email-body">
%2$s
</div>
<div class="email-footer">
<p>&copy; %3$s <a href="%4$s">%1$s</a></p>
<p>%5$s</p>
</div>
</div>
</body>
</html>',
			esc_html( $site_name ),
			$content,
			gmdate( 'Y' ),
			esc_url( $site_url ),
			esc_html__( 'This is an automated message. Please do not reply directly to this email.', 'all-purpose-directory' )
		);
	}

	/**
	 * Get plain text message for a template.
	 *
	 * @param string $template Template name.
	 * @param array  $context  Context data.
	 * @return string
	 */
	public function get_plain_text_message( string $template, array $context = [] ): string {
		// phpcs:disable Generic.Strings.UnnecessaryStringConcat.Found
		$messages = [
			'listing-submitted' => __(
				"A new listing has been submitted on {site_name}.\n\nTitle: {listing_title}\nAuthor: {author_name}\nStatus: {listing_status}\n\nView listing: {listing_url}\nEdit listing: {listing_edit_url}\n\nManage listings: {admin_url}",
				'all-purpose-directory'
			),
			'listing-approved'  => __(
				"Great news! Your listing has been approved on {site_name}.\n\nTitle: {listing_title}\n\nYour listing is now live and can be viewed at:\n{listing_url}\n\nThank you for your submission!",
				'all-purpose-directory'
			),
			'listing-rejected'  => __(
				"We're sorry, but your listing was not approved on {site_name}.\n\nTitle: {listing_title}\n\nIf you have any questions, please contact us.\n\nThank you for your understanding.",
				'all-purpose-directory'
			),
			'listing-expiring'  => __(
				"Your listing on {site_name} will expire soon.\n\nTitle: {listing_title}\nDays remaining: {days_left}\n\nTo keep your listing active, please renew it.\n\nView listing: {listing_url}",
				'all-purpose-directory'
			),
			'listing-expired'   => __(
				"Your listing on {site_name} has expired.\n\nTitle: {listing_title}\n\nYour listing is no longer visible to the public.\nTo reactivate your listing, please contact us or submit a new listing.",
				'all-purpose-directory'
			),
			'new-review'        => __(
				"Your listing has received a new review on {site_name}.\n\nListing: {listing_title}\nReviewer: {review_author}\nRating: {review_rating}/5\n\nReview:\n{review_content}\n\nView listing: {listing_url}",
				'all-purpose-directory'
			),
			'new-inquiry'       => __(
				"You have received a new inquiry about your listing on {site_name}.\n\nListing: {listing_title}\n\nFrom: {inquiry_name}\nEmail: {inquiry_email}\nPhone: {inquiry_phone}\n\nMessage:\n{inquiry_message}\n\nView listing: {listing_url}",
				'all-purpose-directory'
			),
		];
		// phpcs:enable Generic.Strings.UnnecessaryStringConcat.Found

		$message = $messages[ $template ] ?? '';

		/**
		 * Filter the plain text email message.
		 *
		 * @since 1.0.0
		 * @param string $message  The message.
		 * @param string $template Template name.
		 * @param array  $context  Context data.
		 */
		return apply_filters( 'apd_email_plain_text_message', $message, $template, $context );
	}

	/**
	 * Get configuration value.
	 *
	 * @param string $key     Config key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public function get_config( string $key, $default = null ) {
		return $this->config[ $key ] ?? $default;
	}

	/**
	 * Set configuration.
	 *
	 * @param array $config Configuration array.
	 * @return self
	 */
	public function set_config( array $config ): self {
		$this->config = array_merge( $this->config, $config );
		return $this;
	}
}
