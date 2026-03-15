<?php
/**
 * Submission Form Shortcode Class.
 *
 * Displays the listing submission form for frontend submission.
 *
 * @package APD\Shortcode
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Shortcode;

use APD\Frontend\Submission\SubmissionForm;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SubmissionFormShortcode
 *
 * @since 1.0.0
 */
final class SubmissionFormShortcode extends AbstractShortcode {

	/**
	 * Shortcode tag.
	 *
	 * @var string
	 */
	protected string $tag = 'apd_submission_form';

	/**
	 * Shortcode description.
	 *
	 * @var string
	 */
	protected string $description = 'Display the listing submission form.';

	/**
	 * Default attributes.
	 *
	 * @var array<string, mixed>
	 */
	protected array $defaults = [
		'require_login'       => 'true',
		'redirect'            => '',
		'show_title'          => 'true',
		'show_content'        => 'true',
		'show_excerpt'        => 'false',
		'show_categories'     => 'true',
		'show_tags'           => 'true',
		'show_featured_image' => 'true',
		'show_terms'          => 'false',
		'terms_text'          => '',
		'terms_link'          => '',
		'terms_required'      => 'true',
		'submit_text'         => '',
		'class'               => '',
		'listing_id'          => '0',
	];

	/**
	 * Attribute documentation.
	 *
	 * @var array<string, array>
	 */
	protected array $attribute_docs = [
		'require_login'       => [
			'type'        => 'boolean',
			'description' => 'Require user to be logged in.',
			'default'     => 'true',
		],
		'redirect'            => [
			'type'        => 'string',
			'description' => 'URL to redirect to after submission.',
			'default'     => '',
		],
		'show_title'          => [
			'type'        => 'boolean',
			'description' => 'Show listing title field.',
			'default'     => 'true',
		],
		'show_content'        => [
			'type'        => 'boolean',
			'description' => 'Show listing description/content field.',
			'default'     => 'true',
		],
		'show_excerpt'        => [
			'type'        => 'boolean',
			'description' => 'Show listing excerpt/short description field.',
			'default'     => 'false',
		],
		'show_categories'     => [
			'type'        => 'boolean',
			'description' => 'Show category selection.',
			'default'     => 'true',
		],
		'show_tags'           => [
			'type'        => 'boolean',
			'description' => 'Show tag selection.',
			'default'     => 'true',
		],
		'show_featured_image' => [
			'type'        => 'boolean',
			'description' => 'Show featured image upload.',
			'default'     => 'true',
		],
		'show_terms'          => [
			'type'        => 'boolean',
			'description' => 'Show terms and conditions checkbox.',
			'default'     => 'false',
		],
		'terms_text'          => [
			'type'        => 'string',
			'description' => 'Terms and conditions checkbox text.',
			'default'     => '',
		],
		'terms_link'          => [
			'type'        => 'string',
			'description' => 'URL to terms and conditions page.',
			'default'     => '',
		],
		'terms_required'      => [
			'type'        => 'boolean',
			'description' => 'Whether terms acceptance is required.',
			'default'     => 'true',
		],
		'submit_text'         => [
			'type'        => 'string',
			'description' => 'Submit button text.',
			'default'     => '',
		],
		'class'               => [
			'type'        => 'string',
			'description' => 'Additional CSS classes.',
			'default'     => '',
		],
		'listing_id'          => [
			'type'        => 'integer',
			'description' => 'Listing ID to edit. Also supports ?edit_listing= URL parameter.',
			'default'     => '0',
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
		return '[apd_submission_form require_login="true" show_terms="true"]';
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
		// Check if this is a success state (after form submission).
		if ( \apd_is_submission_success() ) {
			return $this->render_success_state();
		}

		// Check login requirement using admin settings.
		$who_can_submit = \apd_get_option( 'who_can_submit', 'anyone' );
		$guest_allowed  = (bool) \apd_get_option( 'guest_submission', false );
		$is_logged_in   = is_user_logged_in();

		if ( 'logged_in' === $who_can_submit || 'specific_roles' === $who_can_submit ) {
			if ( ! $is_logged_in ) {
				return $this->require_login( __( 'Please log in to submit a listing.', 'all-purpose-directory' ) );
			}
		} elseif ( 'anyone' === $who_can_submit && ! $guest_allowed && ! $is_logged_in ) {
			return $this->require_login( __( 'Please log in to submit a listing.', 'all-purpose-directory' ) );
		}

		// Check specific role restriction (admins always pass).
		if ( 'specific_roles' === $who_can_submit && $is_logged_in ) {
			$user = wp_get_current_user();
			if ( ! in_array( 'administrator', (array) $user->roles, true ) ) {
				$allowed_roles = (array) \apd_get_option( 'submission_roles', [] );
				if ( empty( array_intersect( (array) $user->roles, $allowed_roles ) ) ) {
					return '<div class="apd-notice apd-notice--error"><p>' .
						esc_html__( 'Your user role does not have permission to submit listings.', 'all-purpose-directory' ) .
						'</p></div>';
				}
			}
		}

		// Determine listing ID for edit mode.
		$listing_id = $this->get_listing_id_for_edit( $atts );

		// If in edit mode, verify permissions.
		if ( $listing_id > 0 ) {
			$permission_check = $this->check_edit_permission( $listing_id );
			if ( $permission_check !== true ) {
				return $permission_check;
			}
		}

		// Build form configuration from attributes.
		$config = $this->build_form_config( $atts, $listing_id );

		/**
		 * Filter the submission form configuration.
		 *
		 * @since 1.0.0
		 *
		 * @param array $config Form configuration.
		 * @param array $atts   Shortcode attributes.
		 */
		$config = apply_filters( 'apd_submission_form_shortcode_config', $config, $atts );

		// Create submission form instance.
		$form = new SubmissionForm( $config );

		// Check for submitted errors in session/transient.
		$errors = $this->get_submission_errors();
		if ( ! empty( $errors ) ) {
			$form->set_errors( $errors );
		}

		// Start output buffering.
		ob_start();

		// Container classes.
		$container_classes = [ 'apd-submission-form-shortcode' ];
		if ( ! empty( $atts['class'] ) ) {
			$container_classes[] = $atts['class'];
		}
		if ( $listing_id > 0 ) {
			$container_classes[] = 'apd-submission-form-shortcode--edit';
		}

		?>
		<div class="<?php echo esc_attr( implode( ' ', $container_classes ) ); ?>">
			<?php
			/**
			 * Fires before submission form shortcode output.
			 *
			 * @since 1.0.0
			 *
			 * @param array          $atts The shortcode attributes.
			 * @param SubmissionForm $form The form instance.
			 */
			do_action( 'apd_before_submission_form_shortcode', $atts, $form );

			// Render the submission form.
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $form->render();

			/**
			 * Fires after submission form shortcode output.
			 *
			 * @since 1.0.0
			 *
			 * @param array          $atts The shortcode attributes.
			 * @param SubmissionForm $form The form instance.
			 */
			do_action( 'apd_after_submission_form_shortcode', $atts, $form );
			?>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Get the listing ID for edit mode.
	 *
	 * Checks shortcode attribute first, then URL parameter.
	 *
	 * @since 1.0.0
	 *
	 * @param array $atts Shortcode attributes.
	 * @return int Listing ID or 0 for new submission.
	 */
	private function get_listing_id_for_edit( array $atts ): int {
		// Check shortcode attribute first.
		if ( ! empty( $atts['listing_id'] ) ) {
			$listing_id = absint( $atts['listing_id'] );
			if ( $listing_id > 0 ) {
				return $listing_id;
			}
		}

		// Check URL parameter.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Just getting listing ID from URL.
		if ( isset( $_GET['edit_listing'] ) ) {
			return absint( $_GET['edit_listing'] );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		return 0;
	}

	/**
	 * Check if the current user can edit the listing.
	 *
	 * @since 1.0.0
	 *
	 * @param int $listing_id The listing ID to edit.
	 * @return bool|string True if allowed, or error HTML string.
	 */
	private function check_edit_permission( int $listing_id ): bool|string {
		// Check if listing exists and is correct post type.
		$post = get_post( $listing_id );
		if ( ! $post || $post->post_type !== 'apd_listing' ) {
			return $this->render_edit_not_allowed(
				__( 'Listing Not Found', 'all-purpose-directory' ),
				__( 'The listing you are trying to edit does not exist or has been removed.', 'all-purpose-directory' )
			);
		}

		// Check edit permission using helper function.
		if ( ! \apd_user_can_edit_listing( $listing_id ) ) {
			return $this->render_edit_not_allowed(
				__( 'Permission Denied', 'all-purpose-directory' ),
				__( 'You do not have permission to edit this listing. You can only edit listings that you have created.', 'all-purpose-directory' )
			);
		}

		return true;
	}

	/**
	 * Render the edit not allowed message.
	 *
	 * @since 1.0.0
	 *
	 * @param string $title   Error title.
	 * @param string $message Error message.
	 * @return string The error HTML.
	 */
	private function render_edit_not_allowed( string $title, string $message ): string {
		$args = [
			'title'    => $title,
			'message'  => $message,
			'home_url' => home_url(),
		];

		/**
		 * Filter the edit not allowed template arguments.
		 *
		 * @since 1.0.0
		 *
		 * @param array $args Template arguments.
		 */
		$args = apply_filters( 'apd_edit_not_allowed_args', $args );

		return \apd_get_template_html( 'submission/edit-not-allowed.php', $args );
	}

	/**
	 * Render the success state after submission.
	 *
	 * @since 1.0.0
	 *
	 * @return string Success message HTML.
	 */
	private function render_success_state(): string {
		// Get the current page URL without the success parameters for "submit another" link.
		$submit_url = remove_query_arg( [ 'apd_submission', 'listing_id', 'is_update' ] );

		// Check if this was an update.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Just checking update flag.
		$is_update = isset( $_GET['is_update'] ) && $_GET['is_update'] === '1';

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in template.
		return \apd_render_submission_success( 0, $submit_url, $is_update );
	}

	/**
	 * Build form configuration from shortcode attributes.
	 *
	 * @since 1.0.0
	 *
	 * @param array $atts       Shortcode attributes.
	 * @param int   $listing_id Optional. Listing ID for edit mode.
	 * @return array Form configuration.
	 */
	private function build_form_config( array $atts, int $listing_id = 0 ): array {
		$show_terms = $atts['show_terms'];
		$terms_link = $atts['terms_link'];

		// Auto-enable terms when a terms page is configured in admin settings.
		$terms_page_id = (int) \apd_get_option( 'terms_page', 0 );
		if ( $terms_page_id > 0 && empty( $terms_link ) ) {
			$terms_link = get_permalink( $terms_page_id );
			if ( ! $show_terms ) {
				$show_terms = true;
			}
		}

		return [
			'redirect'            => $atts['redirect'],
			'show_title'          => $atts['show_title'],
			'show_content'        => $atts['show_content'],
			'show_excerpt'        => $atts['show_excerpt'],
			'show_categories'     => $atts['show_categories'],
			'show_tags'           => $atts['show_tags'],
			'show_featured_image' => $atts['show_featured_image'],
			'show_terms'          => $show_terms,
			'terms_text'          => $atts['terms_text'],
			'terms_link'          => $terms_link,
			'terms_required'      => $atts['terms_required'],
			'submit_text'         => $atts['submit_text'],
			'class'               => '',
			'listing_id'          => $listing_id,
			'submitted_values'    => $this->get_submitted_values(),
		];
	}

	/**
	 * Get submitted values from previous form submission.
	 *
	 * Used to repopulate form after validation failure.
	 *
	 * @since 1.0.0
	 *
	 * @return array Submitted values or empty array.
	 */
	private function get_submitted_values(): array {
		$user_id = get_current_user_id();
		if ( $user_id <= 0 ) {
			return [];
		}

		$transient_key = 'apd_submission_values_' . $user_id;
		$values        = get_transient( $transient_key );

		if ( $values === false ) {
			return [];
		}

		// Clear the transient after reading.
		delete_transient( $transient_key );

		return $values;
	}

	/**
	 * Get submission errors from previous form submission.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string[]> Errors keyed by field name.
	 */
	private function get_submission_errors(): array {
		$user_id = get_current_user_id();
		if ( $user_id <= 0 ) {
			return [];
		}

		$transient_key = 'apd_submission_errors_' . $user_id;
		$errors        = get_transient( $transient_key );

		if ( $errors === false ) {
			return [];
		}

		// Clear the transient after reading.
		delete_transient( $transient_key );

		return $errors;
	}
}
