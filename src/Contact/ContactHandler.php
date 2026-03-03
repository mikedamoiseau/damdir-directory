<?php
/**
 * Contact Handler class.
 *
 * Processes contact form submissions.
 *
 * @package APD\Contact
 * @since 1.0.0
 */

declare(strict_types=1);

namespace APD\Contact;

use APD\Core\Config;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ContactHandler class.
 */
class ContactHandler {

	/**
	 * Single instance.
	 *
	 * @var ContactHandler|null
	 */
	private static ?ContactHandler $instance = null;

	/**
	 * Configuration.
	 *
	 * @var array
	 */
	private array $config = [
		'min_message_length' => 10,
		'phone_required'     => false,
		'subject_required'   => false,
		'send_admin_copy'    => false,
		'admin_email'        => '',
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
	 * @return ContactHandler
	 */
	public static function get_instance( array $config = [], ?Config $config_service = null ): ContactHandler {
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
		if ( ! apd_contact_form_enabled() ) {
			return;
		}

		// Register AJAX handlers.
		add_action( 'wp_ajax_apd_send_contact', [ $this, 'handle_ajax' ] );
		add_action( 'wp_ajax_nopriv_apd_send_contact', [ $this, 'handle_ajax' ] );

		/**
		 * Fires after contact handler initializes.
		 *
		 * @since 1.0.0
		 */
		do_action( 'apd_contact_handler_init' );
	}

	/**
	 * Handle AJAX contact form submission.
	 *
	 * @return void
	 */
	public function handle_ajax(): void {
		// Verify nonce.
		if ( ! $this->verify_nonce() ) {
			wp_send_json_error(
				[
					'message' => __( 'Security check failed. Please refresh and try again.', 'all-purpose-directory' ),
					'code'    => 'nonce_failed',
				]
			);
			return;
		}

		$result = $this->process();

		if ( is_wp_error( $result ) ) {
			wp_send_json_error(
				[
					'message' => $result->get_error_message(),
					'code'    => $result->get_error_code(),
					'errors'  => $result->get_error_messages(),
				]
			);
			return;
		}

		wp_send_json_success(
			[
				'message' => __( 'Your message has been sent successfully!', 'all-purpose-directory' ),
			]
		);
	}

	/**
	 * Verify nonce.
	 *
	 * @return bool
	 */
	public function verify_nonce(): bool {
		$nonce = isset( $_POST[ ContactForm::NONCE_NAME ] )
			? sanitize_text_field( wp_unslash( $_POST[ ContactForm::NONCE_NAME ] ) )
			: '';

		return (bool) wp_verify_nonce( $nonce, ContactForm::NONCE_ACTION );
	}

	/**
	 * Process the contact form submission.
	 *
	 * @return true|\WP_Error True on success, WP_Error on failure.
	 */
	public function process(): bool|\WP_Error {
		// Run spam protection checks.
		$spam_check = $this->check_spam_protection();
		if ( is_wp_error( $spam_check ) ) {
			return $spam_check;
		}

		// Get and sanitize data.
		$data = $this->get_sanitized_data();

		// Validate.
		$validation = $this->validate( $data );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		// Check listing can receive contact.
		$listing_id = (int) $data['listing_id'];
		$form       = ContactForm::get_instance();

		if ( ! $form->can_receive_contact( $listing_id ) ) {
			return new \WP_Error(
				'listing_unavailable',
				__( 'This listing cannot receive messages at this time.', 'all-purpose-directory' )
			);
		}

		// Get listing and owner.
		$listing = get_post( $listing_id );

		if ( ! $listing ) {
			return new \WP_Error(
				'listing_not_found',
				__( 'The listing could not be found.', 'all-purpose-directory' )
			);
		}

		$owner = get_userdata( $listing->post_author );
		if ( ! ( $owner instanceof \WP_User ) ) {
			return new \WP_Error(
				'listing_owner_not_found',
				__( 'The listing owner could not be found.', 'all-purpose-directory' )
			);
		}

		/**
		 * Fires before sending contact message.
		 *
		 * @since 1.0.0
		 * @param array    $data    Sanitized form data.
		 * @param \WP_Post $listing Listing post.
		 * @param \WP_User $owner   Listing owner.
		 */
		do_action( 'apd_before_send_contact', $data, $listing, $owner );

		// Send email.
		$sent = $this->send_email( $data, $listing, $owner );

		if ( ! $sent ) {
			return new \WP_Error(
				'email_failed',
				__( 'Failed to send message. Please try again later.', 'all-purpose-directory' )
			);
		}

		/**
		 * Fires after contact message is sent successfully.
		 *
		 * @since 1.0.0
		 * @param array    $data    Sanitized form data.
		 * @param \WP_Post $listing Listing post.
		 * @param \WP_User $owner   Listing owner.
		 */
		do_action( 'apd_contact_sent', $data, $listing, $owner );

		return true;
	}

	/**
	 * Get sanitized form data.
	 *
	 * Nonce verification is done in handle_ajax() and handle_form() before this method is called.
	 *
	 * @return array
	 */
	public function get_sanitized_data(): array {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified in handle_ajax/handle_form.
		return [
			'listing_id'      => isset( $_POST['listing_id'] ) ? absint( $_POST['listing_id'] ) : 0,
			'contact_name'    => isset( $_POST['contact_name'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_name'] ) ) : '',
			'contact_email'   => isset( $_POST['contact_email'] ) ? sanitize_email( wp_unslash( $_POST['contact_email'] ) ) : '',
			'contact_phone'   => isset( $_POST['contact_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_phone'] ) ) : '',
			'contact_subject' => isset( $_POST['contact_subject'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_subject'] ) ) : '',
			'contact_message' => isset( $_POST['contact_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['contact_message'] ) ) : '',
		];
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Validate form data.
	 *
	 * @param array $data Sanitized form data.
	 * @return true|\WP_Error True if valid, WP_Error with messages if not.
	 */
	public function validate( array $data ): bool|\WP_Error {
		$errors = new \WP_Error();

		// Listing ID required.
		if ( empty( $data['listing_id'] ) ) {
			$errors->add( 'listing_id', __( 'Invalid listing.', 'all-purpose-directory' ) );
		}

		// Name required.
		if ( empty( $data['contact_name'] ) ) {
			$errors->add( 'contact_name', __( 'Please enter your name.', 'all-purpose-directory' ) );
		}

		// Email required and valid.
		if ( empty( $data['contact_email'] ) ) {
			$errors->add( 'contact_email', __( 'Please enter your email address.', 'all-purpose-directory' ) );
		} elseif ( ! is_email( $data['contact_email'] ) ) {
			$errors->add( 'contact_email', __( 'Please enter a valid email address.', 'all-purpose-directory' ) );
		}

		// Phone required (if configured).
		if ( $this->config['phone_required'] && empty( $data['contact_phone'] ) ) {
			$errors->add( 'contact_phone', __( 'Please enter your phone number.', 'all-purpose-directory' ) );
		}

		// Subject required (if configured).
		if ( $this->config['subject_required'] && empty( $data['contact_subject'] ) ) {
			$errors->add( 'contact_subject', __( 'Please enter a subject.', 'all-purpose-directory' ) );
		}

		// Message required.
		if ( empty( $data['contact_message'] ) ) {
			$errors->add( 'contact_message', __( 'Please enter your message.', 'all-purpose-directory' ) );
		} elseif ( strlen( $data['contact_message'] ) < $this->config['min_message_length'] ) {
			$errors->add(
				'contact_message',
				sprintf(
					/* translators: %d: minimum number of characters */
					__( 'Message must be at least %d characters.', 'all-purpose-directory' ),
					$this->config['min_message_length']
				)
			);
		}

		/**
		 * Filter contact form validation errors.
		 *
		 * @since 1.0.0
		 * @param \WP_Error $errors Validation errors.
		 * @param array     $data   Form data.
		 */
		$errors = apply_filters( 'apd_contact_validation_errors', $errors, $data );

		if ( $errors->has_errors() ) {
			return $errors;
		}

		return true;
	}

	/**
	 * Send the contact email.
	 *
	 * @param array    $data    Form data.
	 * @param \WP_Post $listing Listing post.
	 * @param \WP_User $owner   Listing owner.
	 * @return bool
	 */
	public function send_email( array $data, \WP_Post $listing, \WP_User $owner ): bool {
		$to = $owner->user_email;

		/**
		 * Filter contact email recipient.
		 *
		 * @since 1.0.0
		 * @param string   $to      Recipient email.
		 * @param array    $data    Form data.
		 * @param \WP_Post $listing Listing post.
		 */
		$to = apply_filters( 'apd_contact_email_to', $to, $data, $listing );

		// Build subject.
		$subject = ! empty( $data['contact_subject'] )
			? $data['contact_subject']
			: sprintf(
				/* translators: %s: listing title */
				__( 'New inquiry about: %s', 'all-purpose-directory' ),
				$listing->post_title
			);

		/**
		 * Filter contact email subject.
		 *
		 * @since 1.0.0
		 * @param string   $subject Email subject.
		 * @param array    $data    Form data.
		 * @param \WP_Post $listing Listing post.
		 */
		$subject = apply_filters( 'apd_contact_email_subject', $subject, $data, $listing );

		// Build message.
		$message = $this->build_email_message( $data, $listing );

		/**
		 * Filter contact email message.
		 *
		 * @since 1.0.0
		 * @param string   $message Email message.
		 * @param array    $data    Form data.
		 * @param \WP_Post $listing Listing post.
		 */
		$message = apply_filters( 'apd_contact_email_message', $message, $data, $listing );

		// Headers.
		// Sanitize name for email header safety (strip control chars and header-breaking characters).
		$safe_name = preg_replace( '/[\r\n\t:;<>"]/', '', $data['contact_name'] );
		$headers   = [
			'Content-Type: text/html; charset=UTF-8',
			sprintf( 'Reply-To: %s <%s>', $safe_name, $data['contact_email'] ),
		];

		/**
		 * Filter contact email headers.
		 *
		 * @since 1.0.0
		 * @param array    $headers Email headers.
		 * @param array    $data    Form data.
		 * @param \WP_Post $listing Listing post.
		 */
		$headers = apply_filters( 'apd_contact_email_headers', $headers, $data, $listing );

		// Send to owner.
		$sent = wp_mail( $to, $subject, $message, $headers );

		// Send admin copy if configured.
		if ( $sent && $this->should_send_admin_copy() ) {
			$admin_email = $this->get_admin_email();
			if ( $admin_email ) {
				wp_mail( $admin_email, '[Copy] ' . $subject, $message, $headers );
			}
		}

		return $sent;
	}

	/**
	 * Build the email message body.
	 *
	 * @param array    $data    Form data.
	 * @param \WP_Post $listing Listing post.
	 * @return string
	 */
	public function build_email_message( array $data, \WP_Post $listing ): string {
		$message = '<html><body>';

		$message .= '<h2>' . sprintf(
			/* translators: %s: listing title */
			esc_html__( 'New inquiry about: %s', 'all-purpose-directory' ),
			esc_html( $listing->post_title )
		) . '</h2>';

		$message .= '<table style="border-collapse: collapse; width: 100%; max-width: 600px;">';

		$message .= '<tr><td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>'
			. esc_html__( 'From:', 'all-purpose-directory' ) . '</strong></td>'
			. '<td style="padding: 8px; border-bottom: 1px solid #ddd;">'
			. esc_html( $data['contact_name'] ) . '</td></tr>';

		$message .= '<tr><td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>'
			. esc_html__( 'Email:', 'all-purpose-directory' ) . '</strong></td>'
			. '<td style="padding: 8px; border-bottom: 1px solid #ddd;">'
			. '<a href="mailto:' . esc_attr( $data['contact_email'] ) . '">'
			. esc_html( $data['contact_email'] ) . '</a></td></tr>';

		if ( ! empty( $data['contact_phone'] ) ) {
			$message .= '<tr><td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>'
				. esc_html__( 'Phone:', 'all-purpose-directory' ) . '</strong></td>'
				. '<td style="padding: 8px; border-bottom: 1px solid #ddd;">'
				. esc_html( $data['contact_phone'] ) . '</td></tr>';
		}

		$message .= '</table>';

		$message .= '<h3>' . esc_html__( 'Message:', 'all-purpose-directory' ) . '</h3>';
		$message .= '<div style="background: #f9f9f9; padding: 15px; border-radius: 4px;">'
			. nl2br( esc_html( $data['contact_message'] ) ) . '</div>';

		$message .= '<hr style="margin: 20px 0; border: 0; border-top: 1px solid #ddd;">';
		$message .= '<p style="color: #666; font-size: 12px;">'
			. sprintf(
				/* translators: %s: listing URL */
				esc_html__( 'This message was sent via the contact form on: %s', 'all-purpose-directory' ),
				'<a href="' . esc_url( get_permalink( $listing->ID ) ) . '">'
				. esc_html( $listing->post_title ) . '</a>'
			)
			. '</p>';

		$message .= '</body></html>';

		return $message;
	}

	/**
	 * Check if admin copy should be sent.
	 *
	 * @return bool
	 */
	public function should_send_admin_copy(): bool {
		/**
		 * Filter whether to send admin copy of contact emails.
		 *
		 * @since 1.0.0
		 * @param bool $send_copy Whether to send admin copy.
		 */
		return apply_filters( 'apd_contact_send_admin_copy', $this->config['send_admin_copy'] );
	}

	/**
	 * Get admin email for copies.
	 *
	 * @return string
	 */
	public function get_admin_email(): string {
		$email = $this->config['admin_email'];

		if ( empty( $email ) ) {
			$email = (string) $this->config_service->get_option( 'admin_email' );
		}

		/**
		 * Filter admin email for contact copies.
		 *
		 * @since 1.0.0
		 * @param string $email Admin email address.
		 */
		return apply_filters( 'apd_contact_admin_email', $email );
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

	// =========================================================================
	// Spam Protection Methods
	// =========================================================================

	/**
	 * Run all spam protection checks.
	 *
	 * Checks honeypot field, submission timing, rate limiting,
	 * and allows extensions to add custom checks (e.g., reCAPTCHA).
	 *
	 * @since 1.0.0
	 *
	 * @return true|\WP_Error True if passed, WP_Error if spam detected.
	 */
	private function check_spam_protection(): bool|\WP_Error {
		/**
		 * Filter whether to bypass contact form spam protection.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $bypass  Whether to bypass. Default false.
		 * @param int  $user_id The current user ID.
		 */
		$bypass = apply_filters( 'apd_contact_bypass_spam_protection', false, get_current_user_id() );

		if ( $bypass ) {
			return true;
		}

		// Check honeypot field.
		if ( $this->is_honeypot_filled() ) {
			$this->log_spam_attempt( 'honeypot' );
			return $this->get_generic_spam_error();
		}

		// Check submission timing (too fast = likely bot).
		if ( $this->is_submission_too_fast() ) {
			$this->log_spam_attempt( 'timing' );
			return $this->get_generic_spam_error();
		}

		// Check rate limiting.
		$rate_limit_check = $this->check_rate_limit();
		if ( is_wp_error( $rate_limit_check ) ) {
			$this->log_spam_attempt( 'rate_limit' );
			return $rate_limit_check;
		}

		/**
		 * Filter to run custom spam checks on the contact form.
		 *
		 * Third-party integrations (like reCAPTCHA) can hook here
		 * to add additional spam checking. Return WP_Error to block.
		 *
		 * @since 1.0.0
		 *
		 * @param true|\WP_Error $result  Current result. True if passed.
		 * @param array          $post_data The $_POST data.
		 * @param int            $user_id   The current user ID.
		 */
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in handle_ajax().
		$custom_check = apply_filters( 'apd_contact_spam_check', true, $_POST, get_current_user_id() );

		if ( is_wp_error( $custom_check ) ) {
			$this->log_spam_attempt( 'custom' );
			return $custom_check;
		}

		return true;
	}

	/**
	 * Check if the honeypot field was filled.
	 *
	 * The honeypot field is hidden from real users but visible to bots.
	 * If it contains a value, this is likely a bot submission.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if honeypot was filled (spam).
	 */
	private function is_honeypot_filled(): bool {
		/**
		 * Filter the contact form honeypot field name.
		 *
		 * @since 1.0.0
		 *
		 * @param string $field_name The honeypot field name.
		 */
		$honeypot_field = apply_filters( 'apd_contact_honeypot_field_name', 'contact_website' );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in handle_ajax().
		if ( ! isset( $_POST[ $honeypot_field ] ) ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$value = wp_unslash( $_POST[ $honeypot_field ] );

		return ! hash_equals( '', (string) $value );
	}

	/**
	 * Check if the submission happened too quickly.
	 *
	 * Bots typically submit forms instantly. Real users need time
	 * to read and fill out the form.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if submission was too fast (spam).
	 */
	private function is_submission_too_fast(): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in handle_ajax().
		if ( ! isset( $_POST['apd_contact_token'] ) ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$token = wp_unslash( $_POST['apd_contact_token'] );

		$decoded = base64_decode( (string) $token, true );
		if ( $decoded === false || strpos( $decoded, '|' ) === false ) {
			return true;
		}

		[ $timestamp_str, $signature ] = explode( '|', $decoded, 2 );
		$expected_signature            = hash_hmac( 'sha256', $timestamp_str, wp_salt( 'nonce' ) );

		if ( ! hash_equals( $expected_signature, $signature ) ) {
			return true;
		}

		$form_load_time = (int) $timestamp_str;
		$current_time   = time();

		// Check if timestamp is valid (not in the future, not too old).
		$day_in_seconds = defined( 'DAY_IN_SECONDS' ) ? DAY_IN_SECONDS : 86400;
		if ( $form_load_time > $current_time || $form_load_time < ( $current_time - $day_in_seconds ) ) {
			return true;
		}

		$elapsed = $current_time - $form_load_time;

		/**
		 * Filter the minimum time required for contact form submission.
		 *
		 * @since 1.0.0
		 *
		 * @param int $min_time Minimum seconds before submission is allowed. Default 2.
		 */
		$min_time = apply_filters( 'apd_contact_min_time', 2 );

		return $elapsed < $min_time;
	}

	/**
	 * Check rate limiting for contact submissions.
	 *
	 * Prevents users/IPs from sending too many messages in a time period.
	 *
	 * @since 1.0.0
	 *
	 * @return true|\WP_Error True if within limit, WP_Error if rate limited.
	 */
	private function check_rate_limit(): bool|\WP_Error {
		$identifier = $this->get_rate_limit_identifier();

		/**
		 * Filter the contact form rate limit.
		 *
		 * @since 1.0.0
		 *
		 * @param int $limit Maximum contact submissions in the time period. Default 10.
		 */
		$limit = apply_filters( 'apd_contact_rate_limit', 10 );

		/**
		 * Filter the contact form rate limit time period.
		 *
		 * @since 1.0.0
		 *
		 * @param int $period Time period in seconds. Default 3600 (1 hour).
		 */
		$hour_in_seconds = defined( 'HOUR_IN_SECONDS' ) ? HOUR_IN_SECONDS : 3600;
		$period          = apply_filters( 'apd_contact_rate_period', $hour_in_seconds );

		$transient_key = 'apd_contact_count_' . $identifier;
		$count         = (int) get_transient( $transient_key );

		if ( $count >= $limit ) {
			return new \WP_Error(
				'rate_limited',
				__( 'You have sent too many messages. Please try again later.', 'all-purpose-directory' )
			);
		}

		set_transient( $transient_key, $count + 1, $period );

		return true;
	}

	/**
	 * Get the identifier for rate limiting.
	 *
	 * Uses user ID for logged-in users, IP address for guests.
	 *
	 * @since 1.0.0
	 *
	 * @return string Rate limit identifier.
	 */
	private function get_rate_limit_identifier(): string {
		$user_id = get_current_user_id();

		if ( $user_id > 0 ) {
			return 'user_' . $user_id;
		}

		$ip = $this->get_client_ip();

		return 'ip_' . md5( $ip );
	}

	/**
	 * Get the client IP address.
	 *
	 * @since 1.0.0
	 *
	 * @return string Client IP address.
	 */
	private function get_client_ip(): string {
		$remote_addr = $this->extract_first_valid_ip( sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) ) );

		// Default to REMOTE_ADDR unless this request came through a trusted proxy.
		$client_ip = $remote_addr;
		if ( '' !== $remote_addr && $this->is_trusted_proxy_ip( $remote_addr ) ) {
			foreach ( [ 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP' ] as $header ) {
				if ( empty( $_SERVER[ $header ] ) ) {
					continue;
				}

				$forwarded_ip = $this->extract_first_valid_ip( sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) ) );
				if ( '' !== $forwarded_ip ) {
					$client_ip = $forwarded_ip;
					break;
				}
			}
		}

		return '' !== $client_ip ? $client_ip : '0.0.0.0';
	}

	/**
	 * Extract the first valid IP from a potentially comma-separated value.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value Header/raw IP value.
	 * @return string Valid IP or empty string when invalid.
	 */
	private function extract_first_valid_ip( string $value ): string {
		$candidates = array_map( 'trim', explode( ',', $value ) );

		foreach ( $candidates as $candidate ) {
			if ( filter_var( $candidate, FILTER_VALIDATE_IP ) ) {
				return $candidate;
			}
		}

		return '';
	}

	/**
	 * Check whether an IP belongs to a trusted proxy.
	 *
	 * @since 1.0.0
	 *
	 * @param string $ip IP address to validate.
	 * @return bool
	 */
	private function is_trusted_proxy_ip( string $ip ): bool {
		$trusted_proxies = apply_filters( 'apd_contact_trusted_proxies', [] );
		if ( ! is_array( $trusted_proxies ) ) {
			return false;
		}

		foreach ( $trusted_proxies as $proxy ) {
			if ( ! is_string( $proxy ) ) {
				continue;
			}

			$proxy = trim( $proxy );
			if ( '' === $proxy ) {
				continue;
			}

			if ( $this->ip_matches_proxy( $ip, $proxy ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Match an IP against exact/CIDR trusted proxy definitions.
	 *
	 * @since 1.0.0
	 *
	 * @param string $ip    Client IP.
	 * @param string $proxy Trusted proxy value (single IP or CIDR).
	 * @return bool
	 */
	private function ip_matches_proxy( string $ip, string $proxy ): bool {
		if ( strpos( $proxy, '/' ) === false ) {
			return $ip === $proxy;
		}

		[ $subnet, $mask_length ] = explode( '/', $proxy, 2 );
		$subnet                   = trim( $subnet );
		$mask_length              = (int) $mask_length;

		$ip_binary     = inet_pton( $ip );
		$subnet_binary = inet_pton( $subnet );

		if ( false === $ip_binary || false === $subnet_binary || strlen( $ip_binary ) !== strlen( $subnet_binary ) ) {
			return false;
		}

		$max_bits = strlen( $ip_binary ) * 8;
		if ( $mask_length < 0 || $mask_length > $max_bits ) {
			return false;
		}

		$full_bytes = intdiv( $mask_length, 8 );
		$extra_bits = $mask_length % 8;

		if ( $full_bytes > 0 && substr( $ip_binary, 0, $full_bytes ) !== substr( $subnet_binary, 0, $full_bytes ) ) {
			return false;
		}

		if ( 0 === $extra_bits ) {
			return true;
		}

		$mask = 0xFF << ( 8 - $extra_bits );

		return ( ord( $ip_binary[ $full_bytes ] ) & $mask ) === ( ord( $subnet_binary[ $full_bytes ] ) & $mask );
	}

	/**
	 * Get a generic spam error message.
	 *
	 * We don't reveal which check failed to avoid helping spammers.
	 *
	 * @since 1.0.0
	 *
	 * @return \WP_Error Generic spam error.
	 */
	private function get_generic_spam_error(): \WP_Error {
		return new \WP_Error(
			'submission_failed',
			__( 'Submission failed. Please try again.', 'all-purpose-directory' )
		);
	}

	/**
	 * Log a spam attempt.
	 *
	 * Fires an action hook for admin logging/notification.
	 *
	 * @since 1.0.0
	 *
	 * @param string $type Type of spam detected (honeypot, timing, rate_limit, custom).
	 * @return void
	 */
	private function log_spam_attempt( string $type ): void {
		/**
		 * Fires when a contact form spam attempt is detected.
		 *
		 * @since 1.0.0
		 *
		 * @param string $type      Type of spam detected.
		 * @param string $ip        Client IP address.
		 * @param int    $user_id   User ID (0 for guests).
		 */
		do_action(
			'apd_contact_spam_attempt_detected',
			$type,
			$this->get_client_ip(),
			get_current_user_id()
		);
	}
}
