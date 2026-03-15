<?php
/**
 * Contact Form class.
 *
 * Renders the contact form for listings.
 *
 * @package APD\Contact
 * @since 1.0.0
 */

declare(strict_types=1);

namespace APD\Contact;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ContactForm class.
 */
class ContactForm {

	/**
	 * Single instance.
	 *
	 * @var ContactForm|null
	 */
	private static ?ContactForm $instance = null;

	/**
	 * Default configuration.
	 *
	 * @var array
	 */
	private array $config = [
		'show_phone'         => true,
		'phone_required'     => false,
		'show_subject'       => false,
		'subject_required'   => false,
		'min_message_length' => 10,
		'class'              => '',
	];

	/**
	 * Current listing ID.
	 *
	 * @var int
	 */
	private int $listing_id = 0;

	/**
	 * Form errors.
	 *
	 * @var array
	 */
	private array $errors = [];

	/**
	 * Form values (for repopulating after error).
	 *
	 * @var array
	 */
	private array $values = [];

	/**
	 * Nonce action.
	 */
	public const NONCE_ACTION = 'apd_contact_form';

	/**
	 * Nonce field name.
	 */
	public const NONCE_NAME = 'apd_contact_nonce';

	/**
	 * Get single instance.
	 *
	 * @param array $config Optional. Configuration options.
	 * @return ContactForm
	 */
	public static function get_instance( array $config = [] ): ContactForm {
		if ( null === self::$instance || ! empty( $config ) ) {
			self::$instance = new self( $config );
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @param array $config Configuration options.
	 */
	private function __construct( array $config = [] ) {
		$this->config = array_merge( $this->config, $config );
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

		// Hook into single listing template.
		add_action( 'apd_single_listing_contact_form', [ $this, 'render_on_listing' ] );

		/**
		 * Fires after contact form system initializes.
		 *
		 * @since 1.0.0
		 */
		do_action( 'apd_contact_form_init' );
	}

	/**
	 * Set the listing ID.
	 *
	 * @param int $listing_id Listing ID.
	 * @return self
	 */
	public function set_listing_id( int $listing_id ): self {
		$this->listing_id = $listing_id;
		return $this;
	}

	/**
	 * Get the listing ID.
	 *
	 * @return int
	 */
	public function get_listing_id(): int {
		return $this->listing_id;
	}

	/**
	 * Set form errors.
	 *
	 * @param array $errors Errors array.
	 * @return self
	 */
	public function set_errors( array $errors ): self {
		$this->errors = $errors;
		return $this;
	}

	/**
	 * Get form errors.
	 *
	 * @return array
	 */
	public function get_errors(): array {
		return $this->errors;
	}

	/**
	 * Set form values.
	 *
	 * @param array $values Values array.
	 * @return self
	 */
	public function set_values( array $values ): self {
		$this->values = $values;
		return $this;
	}

	/**
	 * Get form values.
	 *
	 * @return array
	 */
	public function get_values(): array {
		return $this->values;
	}

	/**
	 * Get a specific value.
	 *
	 * @param string $key     Field key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public function get_value( string $key, $default = '' ) {
		return $this->values[ $key ] ?? $default;
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

	/**
	 * Check if phone field should be shown.
	 *
	 * @return bool
	 */
	public function show_phone(): bool {
		return (bool) $this->config['show_phone'];
	}

	/**
	 * Check if phone is required.
	 *
	 * @return bool
	 */
	public function is_phone_required(): bool {
		return (bool) $this->config['phone_required'];
	}

	/**
	 * Check if subject field should be shown.
	 *
	 * @return bool
	 */
	public function show_subject(): bool {
		return (bool) $this->config['show_subject'];
	}

	/**
	 * Check if subject is required.
	 *
	 * @return bool
	 */
	public function is_subject_required(): bool {
		return (bool) $this->config['subject_required'];
	}

	/**
	 * Get minimum message length.
	 *
	 * @return int
	 */
	public function get_min_message_length(): int {
		return (int) $this->config['min_message_length'];
	}

	/**
	 * Get CSS classes for the form.
	 *
	 * @return string
	 */
	public function get_css_classes(): string {
		$classes = [ 'apd-contact-form' ];

		if ( ! empty( $this->config['class'] ) ) {
			$classes[] = $this->config['class'];
		}

		/**
		 * Filter contact form CSS classes.
		 *
		 * @since 1.0.0
		 * @param array $classes    CSS classes.
		 * @param int   $listing_id Listing ID.
		 */
		$classes = apply_filters( 'apd_contact_form_classes', $classes, $this->listing_id );

		return implode( ' ', array_map( 'sanitize_html_class', $classes ) );
	}

	/**
	 * Render the contact form on a listing.
	 *
	 * @param int $listing_id Listing ID.
	 * @return void
	 */
	public function render_on_listing( int $listing_id ): void {
		$this->set_listing_id( $listing_id );
		$this->render();
	}

	/**
	 * Render the contact form.
	 *
	 * @param int|null $listing_id Optional listing ID.
	 * @return void
	 */
	public function render( ?int $listing_id = null ): void {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML is escaped in get_html().
		echo $this->get_html( $listing_id );
	}

	/**
	 * Get the contact form HTML.
	 *
	 * @param int|null $listing_id Optional listing ID.
	 * @return string
	 */
	public function get_html( ?int $listing_id = null ): string {
		if ( null !== $listing_id ) {
			$this->set_listing_id( $listing_id );
		}

		// Validate listing exists.
		if ( $this->listing_id <= 0 ) {
			return '';
		}

		$listing = get_post( $this->listing_id );
		if ( ! $listing || 'apd_listing' !== $listing->post_type ) {
			return '';
		}

		// Get listing owner.
		$owner_id = (int) $listing->post_author;
		$owner    = get_userdata( $owner_id );

		if ( ! $owner ) {
			return '';
		}

		/**
		 * Filter contact form template arguments.
		 *
		 * @since 1.0.0
		 * @param array       $args    Template arguments.
		 * @param int         $listing_id Listing ID.
		 * @param ContactForm $form    ContactForm instance.
		 */
		$args = apply_filters(
			'apd_contact_form_args',
			[
				'form'         => $this,
				'listing_id'   => $this->listing_id,
				'listing'      => $listing,
				'owner'        => $owner,
				'errors'       => $this->errors,
				'values'       => $this->values,
				'nonce_action' => self::NONCE_ACTION,
				'nonce_name'   => self::NONCE_NAME,
			],
			$this->listing_id,
			$this
		);

		ob_start();

		if ( function_exists( 'apd_get_template' ) ) {
			apd_get_template( 'contact/contact-form.php', $args );
		}

		$html = ob_get_clean();

		/**
		 * Filter contact form HTML output.
		 *
		 * @since 1.0.0
		 * @param string $html       Form HTML.
		 * @param int    $listing_id Listing ID.
		 */
		return apply_filters( 'apd_contact_form_html', $html, $this->listing_id );
	}

	/**
	 * Check if the listing can receive contact messages.
	 *
	 * @param int $listing_id Listing ID.
	 * @return bool
	 */
	public function can_receive_contact( int $listing_id ): bool {
		$listing = get_post( $listing_id );

		if ( ! $listing || 'apd_listing' !== $listing->post_type ) {
			return false;
		}

		// Check listing is published.
		if ( 'publish' !== $listing->post_status ) {
			return false;
		}

		// Check owner has valid email.
		$owner = get_userdata( $listing->post_author );
		if ( ! $owner || ! is_email( $owner->user_email ) ) {
			return false;
		}

		/**
		 * Filter whether a listing can receive contact messages.
		 *
		 * @since 1.0.0
		 * @param bool     $can_receive Whether listing can receive messages.
		 * @param int      $listing_id  Listing ID.
		 * @param \WP_Post $listing     Listing post object.
		 */
		return apply_filters( 'apd_listing_can_receive_contact', true, $listing_id, $listing );
	}
}
