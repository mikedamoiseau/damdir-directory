<?php
/**
 * Submission Form Class.
 *
 * Handles rendering of the frontend listing submission form.
 * Filters fields to show only those marked for submission,
 * organizes them into sections, and includes category/tag selectors,
 * featured image upload, and terms acceptance.
 *
 * @package APD\Frontend\Submission
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Frontend\Submission;

use APD\Fields\FieldRegistry;
use APD\Fields\FieldRenderer;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SubmissionForm
 *
 * Renders the frontend listing submission form.
 *
 * @since 1.0.0
 */
class SubmissionForm {

	/**
	 * The field registry instance.
	 *
	 * @var FieldRegistry
	 */
	private FieldRegistry $field_registry;

	/**
	 * The field renderer instance.
	 *
	 * @var FieldRenderer
	 */
	private FieldRenderer $field_renderer;

	/**
	 * Form configuration.
	 *
	 * @var array<string, mixed>
	 */
	private array $config = [];

	/**
	 * Default configuration.
	 *
	 * @var array<string, mixed>
	 */
	private const DEFAULTS = [
		'redirect'               => '',
		'show_title'             => true,
		'show_content'           => true,
		'show_excerpt'           => false,
		'show_categories'        => true,
		'show_tags'              => true,
		'show_featured_image'    => true,
		'show_terms'             => false,
		'terms_text'             => '',
		'terms_link'             => '',
		'terms_required'         => true,
		'submit_text'            => '',
		'class'                  => '',
		'listing_id'             => 0,
		'submitted_values'       => [],
		'nonce_action'           => 'apd_submit_listing',
		'nonce_name'             => 'apd_submission_nonce',
		'enable_spam_protection' => true,
	];

	/**
	 * Validation errors.
	 *
	 * @var array<string, string[]>
	 */
	private array $errors = [];

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $config         Form configuration.
	 * @param FieldRegistry|null   $field_registry Optional. Field registry instance.
	 * @param FieldRenderer|null   $field_renderer Optional. Field renderer instance.
	 */
	public function __construct(
		array $config = [],
		?FieldRegistry $field_registry = null,
		?FieldRenderer $field_renderer = null
	) {
		$this->config         = wp_parse_args( $config, self::DEFAULTS );
		$this->field_registry = $field_registry ?? FieldRegistry::get_instance();
		$this->field_renderer = $field_renderer ?? new FieldRenderer( $this->field_registry );

		// Set default terms text if not provided.
		if ( empty( $this->config['terms_text'] ) ) {
			$this->config['terms_text'] = __( 'I agree to the terms and conditions', 'all-purpose-directory' );
		}

		// Set default submit text if not provided.
		if ( empty( $this->config['submit_text'] ) ) {
			$this->config['submit_text'] = $this->config['listing_id'] > 0
				? __( 'Update Listing', 'all-purpose-directory' )
				: __( 'Submit Listing', 'all-purpose-directory' );
		}
	}

	/**
	 * Get the form configuration.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed> Form configuration.
	 */
	public function get_config(): array {
		return $this->config;
	}

	/**
	 * Get a specific configuration value.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key     Configuration key.
	 * @param mixed  $default Default value if key doesn't exist.
	 * @return mixed Configuration value.
	 */
	public function get_config_value( string $key, mixed $default = null ): mixed {
		return $this->config[ $key ] ?? $default;
	}

	/**
	 * Set validation errors.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, string[]>|\WP_Error $errors Errors keyed by field name.
	 * @return self
	 */
	public function set_errors( array|\WP_Error $errors ): self {
		if ( is_wp_error( $errors ) ) {
			$this->errors = [];
			foreach ( $errors->get_error_codes() as $code ) {
				$field_name = $code;
				$messages   = $errors->get_error_messages( $code );
				if ( ! isset( $this->errors[ $field_name ] ) ) {
					$this->errors[ $field_name ] = [];
				}
				$this->errors[ $field_name ] = array_merge( $this->errors[ $field_name ], $messages );
			}
		} else {
			$this->errors = $errors;
		}

		// Pass errors to field renderer.
		$this->field_renderer->set_errors( $this->errors );

		return $this;
	}

	/**
	 * Get validation errors.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string[]> Errors keyed by field name.
	 */
	public function get_errors(): array {
		return $this->errors;
	}

	/**
	 * Check if form has errors.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if has errors.
	 */
	public function has_errors(): bool {
		return ! empty( $this->errors );
	}

	/**
	 * Get fields configured for submission.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array<string, mixed>> Fields keyed by name.
	 */
	public function get_submission_fields(): array {
		$all_fields = $this->field_registry->get_fields(
			[
				'orderby'    => 'priority',
				'order'      => 'ASC',
				'admin_only' => false,
			]
		);

		/**
		 * Filter the fields to show in the submission form.
		 *
		 * By default, all non-admin-only fields are shown.
		 * Use this filter to further restrict fields or add custom logic.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, array> $fields All non-admin fields.
		 * @param int                  $listing_id Listing ID (0 for new).
		 */
		return apply_filters( 'apd_submission_fields', $all_fields, $this->config['listing_id'] );
	}

	/**
	 * Get field groups for the submission form.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array<string, mixed>> Groups keyed by ID.
	 */
	public function get_field_groups(): array {
		/**
		 * Filter the field groups for the submission form.
		 *
		 * Groups allow organizing fields into collapsible sections.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, array> $groups Field groups configuration.
		 * @param int                  $listing_id Listing ID (0 for new).
		 */
		return apply_filters( 'apd_submission_field_groups', [], $this->config['listing_id'] );
	}

	/**
	 * Get current field values.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed> Field values keyed by name.
	 */
	public function get_field_values(): array {
		$listing_id = $this->config['listing_id'];
		$submitted  = $this->config['submitted_values'];

		// If we have submitted values (after validation failure), use those.
		if ( ! empty( $submitted ) ) {
			return $submitted;
		}

		// If editing an existing listing, get its values.
		if ( $listing_id > 0 ) {
			$values = [];
			$fields = $this->get_submission_fields();

			foreach ( $fields as $field_name => $field ) {
				$meta_key = $this->field_registry->get_meta_key( $field_name );
				$value    = get_post_meta( $listing_id, $meta_key, true );

				// Apply field type transformation.
				$field_type = $this->field_registry->get_field_type( $field['type'] );
				if ( $field_type !== null ) {
					$value = $field_type->prepareValueFromStorage( $value );
				}

				// Use field default if no value stored.
				if ( $value === '' || $value === null ) {
					$value = $field['default'] ?? null;
				}

				$values[ $field_name ] = $value;
			}

			return $values;
		}

		return [];
	}

	/**
	 * Get the listing title value.
	 *
	 * @since 1.0.0
	 *
	 * @return string Listing title.
	 */
	public function get_title_value(): string {
		$submitted = $this->config['submitted_values'];
		if ( isset( $submitted['listing_title'] ) ) {
			return (string) $submitted['listing_title'];
		}

		$listing_id = $this->config['listing_id'];
		if ( $listing_id > 0 ) {
			$post = get_post( $listing_id );
			if ( $post ) {
				return $post->post_title;
			}
		}

		return '';
	}

	/**
	 * Get the listing content value.
	 *
	 * @since 1.0.0
	 *
	 * @return string Listing content.
	 */
	public function get_content_value(): string {
		$submitted = $this->config['submitted_values'];
		if ( isset( $submitted['listing_content'] ) ) {
			return (string) $submitted['listing_content'];
		}

		$listing_id = $this->config['listing_id'];
		if ( $listing_id > 0 ) {
			$post = get_post( $listing_id );
			if ( $post ) {
				return $post->post_content;
			}
		}

		return '';
	}

	/**
	 * Get the listing excerpt value.
	 *
	 * @since 1.0.0
	 *
	 * @return string Listing excerpt.
	 */
	public function get_excerpt_value(): string {
		$submitted = $this->config['submitted_values'];
		if ( isset( $submitted['listing_excerpt'] ) ) {
			return (string) $submitted['listing_excerpt'];
		}

		$listing_id = $this->config['listing_id'];
		if ( $listing_id > 0 ) {
			$post = get_post( $listing_id );
			if ( $post ) {
				return $post->post_excerpt;
			}
		}

		return '';
	}

	/**
	 * Get selected category IDs.
	 *
	 * @since 1.0.0
	 *
	 * @return int[] Selected category term IDs.
	 */
	public function get_selected_categories(): array {
		$submitted = $this->config['submitted_values'];
		if ( isset( $submitted['listing_categories'] ) ) {
			return array_map( 'absint', (array) $submitted['listing_categories'] );
		}

		$listing_id = $this->config['listing_id'];
		if ( $listing_id > 0 ) {
			$terms = get_the_terms( $listing_id, 'apd_category' );
			if ( $terms && ! is_wp_error( $terms ) ) {
				return array_map( fn( $term ) => $term->term_id, $terms );
			}
		}

		return [];
	}

	/**
	 * Get selected tag IDs.
	 *
	 * @since 1.0.0
	 *
	 * @return int[] Selected tag term IDs.
	 */
	public function get_selected_tags(): array {
		$submitted = $this->config['submitted_values'];
		if ( isset( $submitted['listing_tags'] ) ) {
			return array_map( 'absint', (array) $submitted['listing_tags'] );
		}

		$listing_id = $this->config['listing_id'];
		if ( $listing_id > 0 ) {
			$terms = get_the_terms( $listing_id, 'apd_tag' );
			if ( $terms && ! is_wp_error( $terms ) ) {
				return array_map( fn( $term ) => $term->term_id, $terms );
			}
		}

		return [];
	}

	/**
	 * Get the featured image ID.
	 *
	 * @since 1.0.0
	 *
	 * @return int Featured image attachment ID.
	 */
	public function get_featured_image_id(): int {
		$submitted = $this->config['submitted_values'];
		if ( isset( $submitted['featured_image'] ) ) {
			return absint( $submitted['featured_image'] );
		}

		$listing_id = $this->config['listing_id'];
		if ( $listing_id > 0 ) {
			return absint( get_post_thumbnail_id( $listing_id ) );
		}

		return 0;
	}

	/**
	 * Get all categories for selection.
	 *
	 * @since 1.0.0
	 *
	 * @return \WP_Term[] Array of category terms.
	 */
	public function get_categories(): array {
		$terms = get_terms(
			[
				'taxonomy'   => 'apd_category',
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			]
		);

		if ( is_wp_error( $terms ) ) {
			return [];
		}

		return $terms;
	}

	/**
	 * Get all tags for selection.
	 *
	 * @since 1.0.0
	 *
	 * @return \WP_Term[] Array of tag terms.
	 */
	public function get_tags(): array {
		$terms = get_terms(
			[
				'taxonomy'   => 'apd_tag',
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			]
		);

		if ( is_wp_error( $terms ) ) {
			return [];
		}

		return $terms;
	}

	/**
	 * Build hierarchical category options for dropdown.
	 *
	 * @since 1.0.0
	 *
	 * @param int $parent_id Parent term ID.
	 * @param int $depth     Current depth level.
	 * @return array<int, array{id: int, name: string, depth: int}> Category options.
	 */
	public function get_category_options( int $parent_id = 0, int $depth = 0 ): array {
		$options = [];
		$terms   = get_terms(
			[
				'taxonomy'   => 'apd_category',
				'hide_empty' => false,
				'parent'     => $parent_id,
				'orderby'    => 'name',
				'order'      => 'ASC',
			]
		);

		if ( is_wp_error( $terms ) ) {
			return $options;
		}

		foreach ( $terms as $term ) {
			$options[] = [
				'id'    => $term->term_id,
				'name'  => $term->name,
				'depth' => $depth,
			];

			// Get children recursively.
			$children = $this->get_category_options( $term->term_id, $depth + 1 );
			$options  = array_merge( $options, $children );
		}

		return $options;
	}

	/**
	 * Render the submission form.
	 *
	 * @since 1.0.0
	 *
	 * @return string Rendered form HTML.
	 */
	public function render(): string {
		// Set renderer context to frontend.
		$this->field_renderer->set_context( FieldRenderer::CONTEXT_FRONTEND );

		// Register field groups.
		$groups = $this->get_field_groups();
		foreach ( $groups as $group_id => $group_config ) {
			$this->field_renderer->register_group( $group_id, $group_config );
		}

		// Build template args.
		$args = [
			'form'                    => $this,
			'config'                  => $this->config,
			'fields'                  => $this->get_submission_fields(),
			'field_values'            => $this->get_field_values(),
			'field_renderer'          => $this->field_renderer,
			'errors'                  => $this->errors,
			'listing_id'              => $this->config['listing_id'],
			'title_value'             => $this->get_title_value(),
			'content_value'           => $this->get_content_value(),
			'excerpt_value'           => $this->get_excerpt_value(),
			'categories'              => $this->get_categories(),
			'tags'                    => $this->get_tags(),
			'category_options'        => $this->get_category_options(),
			'selected_categories'     => $this->get_selected_categories(),
			'selected_tags'           => $this->get_selected_tags(),
			'featured_image_id'       => $this->get_featured_image_id(),
			'form_classes'            => $this->get_form_classes(),
			'nonce_action'            => $this->config['nonce_action'],
			'nonce_name'              => $this->config['nonce_name'],
			'spam_protection_enabled' => $this->is_spam_protection_enabled(),
			'honeypot_field_html'     => $this->render_honeypot_field(),
			'timestamp_field_html'    => $this->render_timestamp_field(),
		];

		/**
		 * Fires before the submission form is rendered.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, mixed> $args Template arguments.
		 */
		do_action( 'apd_before_submission_form', $args );

		/**
		 * Filter the submission form template arguments.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, mixed> $args Template arguments.
		 */
		$args = apply_filters( 'apd_submission_form_args', $args );

		// Render the template.
		ob_start();
		\apd_get_template( 'submission/submission-form.php', $args );
		$output = ob_get_clean();

		/**
		 * Fires after the submission form is rendered.
		 *
		 * @since 1.0.0
		 *
		 * @param string               $output The form HTML.
		 * @param array<string, mixed> $args   Template arguments.
		 */
		do_action( 'apd_after_submission_form', $output, $args );

		/**
		 * Filter the submission form output.
		 *
		 * @since 1.0.0
		 *
		 * @param string               $output The form HTML.
		 * @param array<string, mixed> $args   Template arguments.
		 */
		return apply_filters( 'apd_submission_form_html', $output, $args );
	}

	/**
	 * Get form CSS classes.
	 *
	 * @since 1.0.0
	 *
	 * @return string CSS classes string.
	 */
	private function get_form_classes(): string {
		$classes = [ 'apd-submission-form' ];

		if ( $this->config['listing_id'] > 0 ) {
			$classes[] = 'apd-submission-form--edit';
		} else {
			$classes[] = 'apd-submission-form--new';
		}

		if ( $this->has_errors() ) {
			$classes[] = 'apd-submission-form--has-errors';
		}

		if ( ! empty( $this->config['class'] ) ) {
			$classes[] = $this->config['class'];
		}

		/**
		 * Filter the submission form CSS classes.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string>        $classes CSS classes.
		 * @param array<string, mixed> $config  Form configuration.
		 */
		$classes = apply_filters( 'apd_submission_form_classes', $classes, $this->config );

		return implode( ' ', array_filter( $classes ) );
	}

	/**
	 * Check if form is in edit mode.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if editing existing listing.
	 */
	public function is_edit_mode(): bool {
		return $this->config['listing_id'] > 0;
	}

	/**
	 * Get the redirect URL.
	 *
	 * @since 1.0.0
	 *
	 * @return string Redirect URL or empty string.
	 */
	public function get_redirect_url(): string {
		return $this->config['redirect'];
	}

	/**
	 * Check if spam protection is enabled.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if spam protection is enabled.
	 */
	public function is_spam_protection_enabled(): bool {
		return (bool) $this->config['enable_spam_protection'];
	}

	/**
	 * Get the honeypot field name.
	 *
	 * @since 1.0.0
	 *
	 * @return string The honeypot field name.
	 */
	public function get_honeypot_field_name(): string {
		/**
		 * Filter the honeypot field name.
		 *
		 * Choose a name that looks legitimate to bots but won't be
		 * filled in by real users since the field is hidden.
		 *
		 * @since 1.0.0
		 *
		 * @param string $field_name The honeypot field name.
		 */
		return apply_filters( 'apd_honeypot_field_name', 'website_url' );
	}

	/**
	 * Get the timestamp field value.
	 *
	 * Returns the current timestamp for form load tracking.
	 * This is used to detect submissions that happen too quickly.
	 *
	 * @since 1.0.0
	 *
	 * @return string Encoded timestamp.
	 */
	public function get_form_timestamp(): string {
		$timestamp = (string) time();
		$signature = hash_hmac( 'sha256', $timestamp, wp_salt( 'nonce' ) );

		return base64_encode( $timestamp . '|' . $signature );
	}

	/**
	 * Render the honeypot field HTML.
	 *
	 * The honeypot field is hidden via CSS and should remain empty.
	 * Bots that fill all form fields will be detected.
	 *
	 * @since 1.0.0
	 *
	 * @return string The honeypot field HTML.
	 */
	public function render_honeypot_field(): string {
		if ( ! $this->is_spam_protection_enabled() ) {
			return '';
		}

		$field_name = $this->get_honeypot_field_name();

		return sprintf(
			'<div class="apd-field apd-field--hp" aria-hidden="true">
				<label class="apd-field__label" for="apd-field-%1$s">%2$s</label>
				<input type="text"
					id="apd-field-%1$s"
					name="%1$s"
					class="apd-field__text apd-field__hp-input"
					value=""
					autocomplete="off"
					tabindex="-1">
			</div>',
			esc_attr( $field_name ),
			esc_html__( 'Website URL', 'all-purpose-directory' )
		);
	}

	/**
	 * Render the timestamp hidden field HTML.
	 *
	 * This field tracks when the form was loaded to detect
	 * submissions that happen too quickly (bot behavior).
	 *
	 * @since 1.0.0
	 *
	 * @return string The timestamp field HTML.
	 */
	public function render_timestamp_field(): string {
		if ( ! $this->is_spam_protection_enabled() ) {
			return '';
		}

		return sprintf(
			'<input type="hidden" name="apd_form_token" value="%s">',
			esc_attr( $this->get_form_timestamp() )
		);
	}
}
