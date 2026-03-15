<?php
/**
 * Login Form Shortcode Class.
 *
 * Displays a login form for users.
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
 * Class LoginFormShortcode
 *
 * @since 1.0.0
 */
final class LoginFormShortcode extends AbstractShortcode {

	/**
	 * Shortcode tag.
	 *
	 * @var string
	 */
	protected string $tag = 'apd_login_form';

	/**
	 * Shortcode description.
	 *
	 * @var string
	 */
	protected string $description = 'Display a login form.';

	/**
	 * Default attributes.
	 *
	 * @var array<string, mixed>
	 */
	protected array $defaults = [
		'redirect'           => '',
		'label_username'     => '',
		'label_password'     => '',
		'label_remember'     => '',
		'label_log_in'       => '',
		'show_remember'      => 'true',
		'show_register'      => 'true',
		'show_lost_password' => 'true',
		'logged_in_message'  => '',
		'class'              => '',
	];

	/**
	 * Attribute documentation.
	 *
	 * @var array<string, array>
	 */
	protected array $attribute_docs = [
		'redirect'           => [
			'type'        => 'string',
			'description' => 'URL to redirect to after login.',
			'default'     => 'current page',
		],
		'label_username'     => [
			'type'        => 'string',
			'description' => 'Label for username field.',
			'default'     => 'Username or Email Address',
		],
		'label_password'     => [
			'type'        => 'string',
			'description' => 'Label for password field.',
			'default'     => 'Password',
		],
		'label_remember'     => [
			'type'        => 'string',
			'description' => 'Label for remember me checkbox.',
			'default'     => 'Remember Me',
		],
		'label_log_in'       => [
			'type'        => 'string',
			'description' => 'Text for login button.',
			'default'     => 'Log In',
		],
		'show_remember'      => [
			'type'        => 'boolean',
			'description' => 'Show remember me checkbox.',
			'default'     => 'true',
		],
		'show_register'      => [
			'type'        => 'boolean',
			'description' => 'Show registration link.',
			'default'     => 'true',
		],
		'show_lost_password' => [
			'type'        => 'boolean',
			'description' => 'Show lost password link.',
			'default'     => 'true',
		],
		'logged_in_message'  => [
			'type'        => 'string',
			'description' => 'Message to show when user is logged in.',
			'default'     => '',
		],
		'class'              => [
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
		return '[apd_login_form redirect="/dashboard" show_register="true"]';
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
		// If user is already logged in, show message or redirect info.
		if ( is_user_logged_in() ) {
			return $this->render_logged_in_message( $atts );
		}

		// Start output buffering.
		ob_start();

		// Container classes.
		$container_classes = [ 'apd-login-form-shortcode', 'apd-form-container' ];
		if ( ! empty( $atts['class'] ) ) {
			$container_classes[] = $atts['class'];
		}

		?>
		<div class="<?php echo esc_attr( implode( ' ', $container_classes ) ); ?>">
			<?php
			/**
			 * Fires before login form shortcode output.
			 *
			 * @since 1.0.0
			 *
			 * @param array $atts The shortcode attributes.
			 */
			do_action( 'apd_before_login_form_shortcode', $atts );

			// Build login form args.
			$form_args = $this->build_form_args( $atts );

			// Render WordPress login form.
			wp_login_form( $form_args );

			// Additional links.
			$this->render_additional_links( $atts );

			/**
			 * Fires after login form shortcode output.
			 *
			 * @since 1.0.0
			 *
			 * @param array $atts The shortcode attributes.
			 */
			do_action( 'apd_after_login_form_shortcode', $atts );
			?>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Build wp_login_form arguments.
	 *
	 * @since 1.0.0
	 *
	 * @param array $atts Shortcode attributes.
	 * @return array Form arguments.
	 */
	private function build_form_args( array $atts ): array {
		$args = [
			'echo'        => true,
			'remember'    => $atts['show_remember'],
			'form_id'     => 'apd-login-form',
			'id_username' => 'apd-user-login',
			'id_password' => 'apd-user-pass',
			'id_remember' => 'apd-remember-me',
			'id_submit'   => 'apd-login-submit',
		];

		// Redirect URL.
		if ( ! empty( $atts['redirect'] ) ) {
			$args['redirect'] = esc_url( $atts['redirect'] );
		} else {
			$args['redirect'] = get_permalink();
		}

		// Custom labels.
		if ( ! empty( $atts['label_username'] ) ) {
			$args['label_username'] = $atts['label_username'];
		}

		if ( ! empty( $atts['label_password'] ) ) {
			$args['label_password'] = $atts['label_password'];
		}

		if ( ! empty( $atts['label_remember'] ) ) {
			$args['label_remember'] = $atts['label_remember'];
		}

		if ( ! empty( $atts['label_log_in'] ) ) {
			$args['label_log_in'] = $atts['label_log_in'];
		}

		/**
		 * Filter the login form arguments.
		 *
		 * @since 1.0.0
		 *
		 * @param array $args The form arguments.
		 * @param array $atts The shortcode attributes.
		 */
		return apply_filters( 'apd_login_form_shortcode_args', $args, $atts );
	}

	/**
	 * Render additional links below the form.
	 *
	 * @since 1.0.0
	 *
	 * @param array $atts Shortcode attributes.
	 * @return void
	 */
	private function render_additional_links( array $atts ): void {
		$links = [];

		// Lost password link.
		if ( $atts['show_lost_password'] ) {
			$links[] = sprintf(
				'<a href="%s" class="apd-login-link apd-login-link--lost-password">%s</a>',
				esc_url( wp_lostpassword_url( get_permalink() ) ),
				esc_html__( 'Lost your password?', 'all-purpose-directory' )
			);
		}

		// Registration link.
		if ( $atts['show_register'] && get_option( 'users_can_register' ) ) {
			$links[] = sprintf(
				'<a href="%s" class="apd-login-link apd-login-link--register">%s</a>',
				esc_url( wp_registration_url() ),
				esc_html__( 'Register', 'all-purpose-directory' )
			);
		}

		if ( ! empty( $links ) ) {
			printf(
				'<div class="apd-login-links">%s</div>',
				implode( ' <span class="apd-login-links__separator">|</span> ', $links ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			);
		}
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
				__( 'You are logged in as %s.', 'all-purpose-directory' ),
				$user->display_name
			);
		}

		$logout_url = wp_logout_url( get_permalink() );

		return sprintf(
			'<div class="apd-login-form-shortcode apd-logged-in">
				<p class="apd-logged-in__message">%s</p>
				<p class="apd-logged-in__action"><a href="%s" class="apd-button apd-button--secondary">%s</a></p>
			</div>',
			esc_html( $message ),
			esc_url( $logout_url ),
			esc_html__( 'Log Out', 'all-purpose-directory' )
		);
	}
}
