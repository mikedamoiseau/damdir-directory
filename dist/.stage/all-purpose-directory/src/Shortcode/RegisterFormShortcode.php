<?php
/**
 * Register Form Shortcode Class.
 *
 * Displays a registration form for new users.
 *
 * @package APD\Shortcode
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Shortcode;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RegisterFormShortcode
 *
 * @since 1.0.0
 */
final class RegisterFormShortcode extends AbstractShortcode {

	/**
	 * Shortcode tag.
	 *
	 * @var string
	 */
	protected string $tag = 'apd_register_form';

	/**
	 * Shortcode description.
	 *
	 * @var string
	 */
	protected string $description = 'Display a registration form for new users.';

	/**
	 * Default attributes.
	 *
	 * @var array<string, mixed>
	 */
	protected array $defaults = [
		'redirect'          => '',
		'show_login'        => 'true',
		'logged_in_message' => '',
		'class'             => '',
	];

	/**
	 * Attribute documentation.
	 *
	 * @var array<string, array>
	 */
	protected array $attribute_docs = [
		'redirect'          => [
			'type'        => 'string',
			'description' => 'URL to redirect to after registration.',
			'default'     => 'current page',
		],
		'show_login'        => [
			'type'        => 'boolean',
			'description' => 'Show link to login page.',
			'default'     => 'true',
		],
		'logged_in_message' => [
			'type'        => 'string',
			'description' => 'Message to show when user is logged in.',
			'default'     => '',
		],
		'class'             => [
			'type'        => 'string',
			'description' => 'Additional CSS classes.',
			'default'     => '',
		],
	];

	/**
	 * Get example usage.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_example(): string {
		return '[apd_register_form redirect="/welcome" show_login="true"]';
	}

	/**
	 * Generate the shortcode output.
	 *
	 * @since 1.0.0
	 *
	 * @param array       $atts    Parsed shortcode attributes.
	 * @param string|null $content Shortcode content.
	 * @return string Shortcode output.
	 */
	protected function output( array $atts, ?string $content ): string {
		// Check if registration is allowed.
		if ( ! get_option( 'users_can_register' ) ) {
			return $this->render_registration_disabled();
		}

		// If user is already logged in, show message.
		if ( is_user_logged_in() ) {
			return $this->render_logged_in_message( $atts );
		}

		// Start output buffering.
		ob_start();

		// Container classes.
		$container_classes = [ 'apd-register-form-shortcode', 'apd-form-container' ];
		if ( ! empty( $atts['class'] ) ) {
			$container_classes[] = $atts['class'];
		}

		// Handle form submission.
		$errors  = $this->process_registration();
		$success = false;

		if ( is_array( $errors ) && empty( $errors ) ) {
			$success = true;
		}

		?>
		<div class="<?php echo esc_attr( implode( ' ', $container_classes ) ); ?>">
			<?php
			/**
			 * Fires before register form shortcode output.
			 *
			 * @since 1.0.0
			 *
			 * @param array $atts The shortcode attributes.
			 */
			do_action( 'apd_before_register_form_shortcode', $atts );

			if ( $success ) {
				$this->render_success_message();
			} else {
				// Show errors if any.
				if ( is_array( $errors ) && ! empty( $errors ) ) {
					$this->render_errors( $errors );
				}

				// Render the form.
				$this->render_form( $atts );

				// Login link.
				if ( $atts['show_login'] ) {
					$this->render_login_link();
				}
			}

			/**
			 * Fires after register form shortcode output.
			 *
			 * @since 1.0.0
			 *
			 * @param array $atts The shortcode attributes.
			 */
			do_action( 'apd_after_register_form_shortcode', $atts );
			?>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Render the registration form.
	 *
	 * @since 1.0.0
	 *
	 * @param array $atts Shortcode attributes.
	 * @return void
	 */
	private function render_form( array $atts ): void {
		$redirect = ! empty( $atts['redirect'] ) ? $atts['redirect'] : get_permalink();

		// Get previously submitted values for repopulation.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$username = isset( $_POST['apd_register_username'] ) ? sanitize_user( wp_unslash( $_POST['apd_register_username'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$email = isset( $_POST['apd_register_email'] ) ? sanitize_email( wp_unslash( $_POST['apd_register_email'] ) ) : '';

		?>
		<form method="post" class="apd-register-form" id="apd-register-form">
			<?php wp_nonce_field( 'apd_register_user', 'apd_register_nonce' ); ?>
			<input type="hidden" name="apd_register_redirect" value="<?php echo esc_url( $redirect ); ?>">

			<div class="apd-form-field">
				<label for="apd-register-username" class="apd-form-label">
					<?php esc_html_e( 'Username', 'all-purpose-directory' ); ?>
					<span class="apd-required">*</span>
				</label>
				<input type="text"
						name="apd_register_username"
						id="apd-register-username"
						class="apd-form-input"
						value="<?php echo esc_attr( $username ); ?>"
						required
						autocomplete="username">
			</div>

			<div class="apd-form-field">
				<label for="apd-register-email" class="apd-form-label">
					<?php esc_html_e( 'Email Address', 'all-purpose-directory' ); ?>
					<span class="apd-required">*</span>
				</label>
				<input type="email"
						name="apd_register_email"
						id="apd-register-email"
						class="apd-form-input"
						value="<?php echo esc_attr( $email ); ?>"
						required
						autocomplete="email">
			</div>

			<p class="apd-form-note">
				<?php esc_html_e( 'A password will be sent to your email address.', 'all-purpose-directory' ); ?>
			</p>

			<?php
			/**
			 * Fires inside the registration form, before the submit button.
			 *
			 * @since 1.0.0
			 *
			 * @param array $atts The shortcode attributes.
			 */
			do_action( 'apd_register_form_fields', $atts );
			?>

			<div class="apd-form-field apd-form-field--submit">
				<button type="submit" name="apd_register_submit" class="apd-button apd-button--primary">
					<?php esc_html_e( 'Register', 'all-purpose-directory' ); ?>
				</button>
			</div>
		</form>
		<?php
	}

	/**
	 * Process registration form submission.
	 *
	 * @since 1.0.0
	 *
	 * @return array|null Array of errors, empty array on success, null if not submitted.
	 */
	private function process_registration(): ?array {
		// Check if form was submitted.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST['apd_register_submit'] ) ) {
			return null;
		}

		// Verify nonce.
		if ( ! isset( $_POST['apd_register_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['apd_register_nonce'] ) ), 'apd_register_user' ) ) {
			return [ __( 'Security check failed. Please try again.', 'all-purpose-directory' ) ];
		}

		$errors = [];

		// Get and validate username.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$username = isset( $_POST['apd_register_username'] ) ? sanitize_user( wp_unslash( $_POST['apd_register_username'] ) ) : '';
		if ( empty( $username ) ) {
			$errors[] = __( 'Please enter a username.', 'all-purpose-directory' );
		} elseif ( username_exists( $username ) ) {
			$errors[] = __( 'This username is already registered.', 'all-purpose-directory' );
		} elseif ( ! validate_username( $username ) ) {
			$errors[] = __( 'Invalid username. Please use only letters, numbers, and underscores.', 'all-purpose-directory' );
		}

		// Get and validate email.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$email = isset( $_POST['apd_register_email'] ) ? sanitize_email( wp_unslash( $_POST['apd_register_email'] ) ) : '';
		if ( empty( $email ) ) {
			$errors[] = __( 'Please enter an email address.', 'all-purpose-directory' );
		} elseif ( ! is_email( $email ) ) {
			$errors[] = __( 'Please enter a valid email address.', 'all-purpose-directory' );
		} elseif ( email_exists( $email ) ) {
			$errors[] = __( 'This email address is already registered.', 'all-purpose-directory' );
		}

		/**
		 * Filter registration errors.
		 *
		 * @since 1.0.0
		 *
		 * @param array  $errors   The error messages.
		 * @param string $username The submitted username.
		 * @param string $email    The submitted email.
		 */
		$errors = apply_filters( 'apd_register_form_errors', $errors, $username, $email );

		// If there are errors, return them.
		if ( ! empty( $errors ) ) {
			return $errors;
		}

		// Create the user.
		$user_id = wp_create_user( $username, wp_generate_password(), $email );

		if ( is_wp_error( $user_id ) ) {
			return [ $user_id->get_error_message() ];
		}

		// Send notification email.
		wp_new_user_notification( $user_id, null, 'both' );

		/**
		 * Fires after a user is registered via the shortcode.
		 *
		 * @since 1.0.0
		 *
		 * @param int    $user_id  The new user ID.
		 * @param string $username The username.
		 * @param string $email    The email address.
		 */
		do_action( 'apd_user_registered', $user_id, $username, $email );

		return []; // Empty array indicates success.
	}

	/**
	 * Render error messages.
	 *
	 * @since 1.0.0
	 *
	 * @param array $errors Error messages.
	 * @return void
	 */
	private function render_errors( array $errors ): void {
		?>
		<div class="apd-form-errors" role="alert">
			<ul class="apd-form-errors__list">
				<?php foreach ( $errors as $error ) : ?>
					<li class="apd-form-errors__item"><?php echo esc_html( $error ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}

	/**
	 * Render success message.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function render_success_message(): void {
		?>
		<div class="apd-form-success" role="status">
			<p class="apd-form-success__message">
				<?php esc_html_e( 'Registration successful! Please check your email for your password.', 'all-purpose-directory' ); ?>
			</p>
			<p class="apd-form-success__action">
				<a href="<?php echo esc_url( wp_login_url() ); ?>" class="apd-button">
					<?php esc_html_e( 'Log In', 'all-purpose-directory' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * Render login link.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function render_login_link(): void {
		?>
		<p class="apd-register-login-link">
			<?php esc_html_e( 'Already have an account?', 'all-purpose-directory' ); ?>
			<a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>">
				<?php esc_html_e( 'Log In', 'all-purpose-directory' ); ?>
			</a>
		</p>
		<?php
	}

	/**
	 * Render message when registration is disabled.
	 *
	 * @since 1.0.0
	 *
	 * @return string HTML output.
	 */
	private function render_registration_disabled(): string {
		return sprintf(
			'<div class="apd-register-form-shortcode apd-notice apd-notice--info">
				<p>%s</p>
			</div>',
			esc_html__( 'User registration is currently disabled.', 'all-purpose-directory' )
		);
	}

	/**
	 * Render message for logged-in users.
	 *
	 * @since 1.0.0
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	private function render_logged_in_message( array $atts ): string {
		$user = wp_get_current_user();

		if ( ! empty( $atts['logged_in_message'] ) ) {
			$message = $atts['logged_in_message'];
		} else {
			$message = sprintf(
				/* translators: %s: User display name */
				__( 'You are already logged in as %s.', 'all-purpose-directory' ),
				$user->display_name
			);
		}

		$logout_url = wp_logout_url( get_permalink() );

		return sprintf(
			'<div class="apd-register-form-shortcode apd-logged-in">
				<p class="apd-logged-in__message">%s</p>
				<p class="apd-logged-in__action"><a href="%s" class="apd-button apd-button--secondary">%s</a></p>
			</div>',
			esc_html( $message ),
			esc_url( $logout_url ),
			esc_html__( 'Log Out', 'all-purpose-directory' )
		);
	}
}
