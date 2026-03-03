<?php
/**
 * Field Renderer.
 *
 * Handles rendering of custom fields for both admin meta boxes and
 * frontend submission forms. Supports field groups/sections and
 * conditional display logic via hooks.
 *
 * @package APD\Fields
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Fields;

use APD\Contracts\FieldTypeInterface;
use WP_Error;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FieldRenderer
 *
 * Central class for rendering custom fields in various contexts.
 *
 * @since 1.0.0
 */
class FieldRenderer {

	/**
	 * Render context: Admin meta box.
	 */
	public const CONTEXT_ADMIN = 'admin';

	/**
	 * Render context: Frontend form.
	 */
	public const CONTEXT_FRONTEND = 'frontend';

	/**
	 * Render context: Display only (read-only output).
	 */
	public const CONTEXT_DISPLAY = 'display';

	/**
	 * The field registry instance.
	 *
	 * @var FieldRegistry
	 */
	private FieldRegistry $registry;

	/**
	 * Current render context.
	 *
	 * @var string
	 */
	private string $context = self::CONTEXT_ADMIN;

	/**
	 * Validation errors from form processing.
	 *
	 * @var array<string, string[]>
	 */
	private array $errors = [];

	/**
	 * Field groups/sections configuration.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private array $groups = [];

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param FieldRegistry|null $registry Optional. Field registry instance.
	 */
	public function __construct( ?FieldRegistry $registry = null ) {
		$this->registry = $registry ?? FieldRegistry::get_instance();
	}

	/**
	 * Set the render context.
	 *
	 * @since 1.0.0
	 *
	 * @param string $context Render context (admin, frontend, display).
	 * @return self
	 */
	public function set_context( string $context ): self {
		$allowed_contexts = [ self::CONTEXT_ADMIN, self::CONTEXT_FRONTEND, self::CONTEXT_DISPLAY ];

		if ( in_array( $context, $allowed_contexts, true ) ) {
			$this->context = $context;
		}

		return $this;
	}

	/**
	 * Get the current render context.
	 *
	 * @since 1.0.0
	 *
	 * @return string Current context.
	 */
	public function get_context(): string {
		return $this->context;
	}

	/**
	 * Set validation errors to display.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, string[]>|WP_Error $errors Errors keyed by field name.
	 * @return self
	 */
	public function set_errors( array|WP_Error $errors ): self {
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
	 * Clear validation errors.
	 *
	 * @since 1.0.0
	 *
	 * @return self
	 */
	public function clear_errors(): self {
		$this->errors = [];
		return $this;
	}

	/**
	 * Register a field group/section.
	 *
	 * Groups allow organizing fields into collapsible sections.
	 *
	 * @since 1.0.0
	 *
	 * @param string               $group_id Group identifier.
	 * @param array<string, mixed> $config   Group configuration.
	 *                                       - title: (string) Group title.
	 *                                       - description: (string) Group description.
	 *                                       - priority: (int) Display order (default 10).
	 *                                       - collapsible: (bool) Whether group can collapse (default false).
	 *                                       - collapsed: (bool) Initial collapsed state (default false).
	 *                                       - fields: (array) Array of field names in this group.
	 * @return self
	 */
	public function register_group( string $group_id, array $config ): self {
		$defaults = [
			'title'       => '',
			'description' => '',
			'priority'    => 10,
			'collapsible' => false,
			'collapsed'   => false,
			'fields'      => [],
		];

		$this->groups[ $group_id ] = wp_parse_args( $config, $defaults );

		return $this;
	}

	/**
	 * Unregister a field group.
	 *
	 * @since 1.0.0
	 *
	 * @param string $group_id Group identifier.
	 * @return self
	 */
	public function unregister_group( string $group_id ): self {
		unset( $this->groups[ $group_id ] );
		return $this;
	}

	/**
	 * Get registered groups.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array<string, mixed>> Groups keyed by ID.
	 */
	public function get_groups(): array {
		// Sort by priority.
		$groups = $this->groups;
		uasort(
			$groups,
			fn( $a, $b ) => ( $a['priority'] ?? 10 ) <=> ( $b['priority'] ?? 10 )
		);

		return $groups;
	}

	/**
	 * Render a single field.
	 *
	 * @since 1.0.0
	 *
	 * @param string $field_name Field name.
	 * @param mixed  $value      Current value.
	 * @param int    $listing_id Optional. Listing ID for context.
	 * @return string Rendered HTML.
	 */
	public function render_field( string $field_name, mixed $value = null, int $listing_id = 0 ): string {
		$field = $this->registry->get_field( $field_name );

		if ( $field === null ) {
			return '';
		}

		// Check if field should be displayed in current context.
		$should_display = $this->should_display_field( $field, $listing_id );

		if ( ! $should_display ) {
			// In admin context, render hidden fields as display:none so JS can
			// toggle them when listing type changes. Skip admin_only check since
			// should_display_field already handles it and we're in admin context.
			if ( $this->context !== self::CONTEXT_ADMIN ) {
				return '';
			}
		}

		// Get the field type handler.
		$field_type = $this->registry->get_field_type( $field['type'] );
		if ( $field_type === null ) {
			return '';
		}

		// Use field default if no value provided.
		if ( $value === null ) {
			$value = $field['default'] ?? $field_type->getDefaultValue();
		}

		// Render based on context.
		if ( $this->context === self::CONTEXT_DISPLAY ) {
			return $this->render_field_display( $field, $field_type, $value, $listing_id );
		}

		return $this->render_field_input( $field, $field_type, $value, $listing_id, ! $should_display );
	}

	/**
	 * Render a field as a form input.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $field      Field configuration.
	 * @param FieldTypeInterface   $field_type Field type handler.
	 * @param mixed                $value      Current value.
	 * @param int                  $listing_id Listing ID for context.
	 * @param bool                 $hidden     Whether the field should render hidden.
	 * @return string Rendered HTML.
	 */
	private function render_field_input( array $field, FieldTypeInterface $field_type, mixed $value, int $listing_id, bool $hidden = false ): string {
		$field_name    = $field['name'];
		$field_id      = 'apd-field-' . $field_name;
		$is_admin      = $this->context === self::CONTEXT_ADMIN;
		$has_errors    = ! empty( $this->errors[ $field_name ] );
		$wrapper_class = $is_admin ? 'apd-field apd-field--admin' : 'apd-field apd-field--frontend';

		if ( $has_errors ) {
			$wrapper_class .= ' apd-field--has-error';
		}

		if ( ! empty( $field['required'] ) ) {
			$wrapper_class .= ' apd-field--required';
		}

		/**
		 * Filter the field wrapper CSS class.
		 *
		 * @since 1.0.0
		 *
		 * @param string               $wrapper_class The wrapper CSS class.
		 * @param array<string, mixed> $field         Field configuration.
		 * @param string               $context       Render context.
		 */
		$wrapper_class = apply_filters( 'apd_field_wrapper_class', $wrapper_class, $field, $this->context );

		// Build listing type data attribute for JS type switching.
		$listing_type_attr = '';
		$listing_type      = $field['listing_type'] ?? null;

		if ( $listing_type !== null ) {
			$types_value       = is_array( $listing_type ) ? implode( ',', $listing_type ) : $listing_type;
			$listing_type_attr = sprintf( ' data-listing-types="%s"', esc_attr( $types_value ) );
		}

		// Hide field via inline style when PHP-side filtering says it shouldn't
		// display but we still need the DOM element for JS type switching.
		$hidden_attr = $hidden ? ' style="display:none;"' : '';

		$html = sprintf(
			'<div class="%s" data-field-name="%s" data-field-type="%s"%s%s>',
			esc_attr( $wrapper_class ),
			esc_attr( $field_name ),
			esc_attr( $field['type'] ),
			$listing_type_attr,
			$hidden_attr
		);

		// Render label.
		$html .= $this->render_label( $field, $field_id );

		// Field input container.
		$html .= '<div class="apd-field__input">';

		// Render the field input via field type.
		$html .= $field_type->render( $field, $value );

		$html .= '</div>'; // .apd-field__input

		// Render errors if present.
		if ( $has_errors ) {
			$html .= $this->render_field_errors( $field_name );
		}

		$html .= '</div>'; // .apd-field

		/**
		 * Filter the rendered field HTML.
		 *
		 * @since 1.0.0
		 *
		 * @param string               $html       The rendered HTML.
		 * @param array<string, mixed> $field      Field configuration.
		 * @param mixed                $value      Current value.
		 * @param string               $context    Render context.
		 * @param int                  $listing_id Listing ID.
		 */
		return apply_filters( 'apd_render_field', $html, $field, $value, $this->context, $listing_id );
	}

	/**
	 * Render a field for display (read-only).
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $field      Field configuration.
	 * @param FieldTypeInterface   $field_type Field type handler.
	 * @param mixed                $value      Current value.
	 * @param int                  $listing_id Listing ID for context.
	 * @return string Rendered HTML.
	 */
	private function render_field_display( array $field, FieldTypeInterface $field_type, mixed $value, int $listing_id ): string {
		// Skip empty values in display context.
		if ( $this->is_empty_value( $value ) ) {
			return '';
		}

		$formatted_value = $field_type->formatValue( $value, $field );

		// Skip if formatted value is empty.
		if ( trim( $formatted_value ) === '' ) {
			return '';
		}

		$field_name     = $field['name'];
		$display_format = $field['display_format'] ?? 'default';

		switch ( $display_format ) {
			case 'inline':
				$html = $this->render_field_display_inline( $field_name, $field['label'], $formatted_value );
				break;

			case 'value-only':
				$html = $this->render_field_display_value_only( $field_name, $formatted_value );
				break;

			default:
				$html = $this->render_field_display_default( $field_name, $field['label'], $formatted_value );
				break;
		}

		/**
		 * Filter the rendered field display HTML.
		 *
		 * @since 1.0.0
		 *
		 * @param string               $html       The rendered HTML.
		 * @param array<string, mixed> $field      Field configuration.
		 * @param mixed                $value      Current value.
		 * @param int                  $listing_id Listing ID.
		 */
		return apply_filters( 'apd_render_field_display', $html, $field, $value, $listing_id );
	}

	/**
	 * Render field display in default format (dt/dd).
	 *
	 * @since 1.0.0
	 *
	 * @param string $field_name      Field name.
	 * @param string $label           Field label.
	 * @param string $formatted_value Formatted value.
	 * @return string HTML output.
	 */
	private function render_field_display_default( string $field_name, string $label, string $formatted_value ): string {
		$html = sprintf(
			'<div class="apd-field-display apd-field-display--%s">',
			esc_attr( $field_name )
		);

		$html .= sprintf(
			'<dt class="apd-field-display__label">%s</dt>',
			esc_html( $label )
		);

		$html .= sprintf(
			'<dd class="apd-field-display__value">%s</dd>',
			$formatted_value // Already escaped by formatValue().
		);

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render field display in inline format (label: value on one line).
	 *
	 * @since 1.0.0
	 *
	 * @param string $field_name      Field name.
	 * @param string $label           Field label.
	 * @param string $formatted_value Formatted value.
	 * @return string HTML output.
	 */
	private function render_field_display_inline( string $field_name, string $label, string $formatted_value ): string {
		return sprintf(
			'<div class="apd-field-display apd-field-display--%s apd-field-display--inline">'
			. '<span class="apd-field-display__label">%s:</span> '
			. '<span class="apd-field-display__value">%s</span>'
			. '</div>',
			esc_attr( $field_name ),
			esc_html( $label ),
			$formatted_value
		);
	}

	/**
	 * Render field display in value-only format (no label).
	 *
	 * @since 1.0.0
	 *
	 * @param string $field_name      Field name.
	 * @param string $formatted_value Formatted value.
	 * @return string HTML output.
	 */
	private function render_field_display_value_only( string $field_name, string $formatted_value ): string {
		return sprintf(
			'<div class="apd-field-display apd-field-display--%s apd-field-display--value-only">'
			. '<span class="apd-field-display__value">%s</span>'
			. '</div>',
			esc_attr( $field_name ),
			$formatted_value
		);
	}

	/**
	 * Render field label.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $field    Field configuration.
	 * @param string               $field_id Field ID attribute.
	 * @return string Label HTML.
	 */
	private function render_label( array $field, string $field_id ): string {
		$label = $field['label'] ?? '';

		if ( empty( $label ) ) {
			return '';
		}

		$required_indicator = '';
		if ( ! empty( $field['required'] ) ) {
			$required_indicator = sprintf(
				' <span class="apd-field__required-indicator" aria-hidden="true">%s</span>',
				esc_html__( '*', 'all-purpose-directory' )
			);
		}

		return sprintf(
			'<label class="apd-field__label" for="%s">%s%s</label>',
			esc_attr( $field_id ),
			esc_html( $label ),
			$required_indicator
		);
	}

	/**
	 * Render field errors.
	 *
	 * @since 1.0.0
	 *
	 * @param string $field_name Field name.
	 * @return string Errors HTML.
	 */
	private function render_field_errors( string $field_name ): string {
		if ( empty( $this->errors[ $field_name ] ) ) {
			return '';
		}

		$html = sprintf(
			'<div class="apd-field__errors" role="alert" aria-live="polite" id="%s-errors">',
			esc_attr( 'apd-field-' . $field_name )
		);

		foreach ( $this->errors[ $field_name ] as $error ) {
			$html .= sprintf(
				'<p class="apd-field__error">%s</p>',
				esc_html( $error )
			);
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render multiple fields.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $values     Field values keyed by name.
	 * @param array<string, mixed> $args       Optional. Arguments.
	 *                                         - fields: (array) Specific field names to render.
	 *                                         - exclude: (array) Field names to exclude.
	 * @param int                  $listing_id Optional. Listing ID for context.
	 * @return string Rendered HTML.
	 */
	public function render_fields( array $values = [], array $args = [], int $listing_id = 0 ): string {
		$defaults = [
			'fields'  => [],
			'exclude' => [],
		];

		$args = wp_parse_args( $args, $defaults );

		// Get fields to render.
		if ( ! empty( $args['fields'] ) ) {
			$field_names = $args['fields'];
		} else {
			// Get all fields for current context.
			$query_args = [
				'orderby' => 'priority',
				'order'   => 'ASC',
			];

			if ( $this->context === self::CONTEXT_FRONTEND ) {
				$query_args['admin_only'] = false;
			}

			$fields      = $this->registry->get_fields( $query_args );
			$field_names = array_keys( $fields );
		}

		// Apply exclusions.
		if ( ! empty( $args['exclude'] ) ) {
			$field_names = array_diff( $field_names, $args['exclude'] );
		}

		$html = '';

		foreach ( $field_names as $field_name ) {
			$value = $values[ $field_name ] ?? null;
			$html .= $this->render_field( $field_name, $value, $listing_id );
		}

		return $html;
	}

	/**
	 * Render fields organized by groups.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $values     Field values keyed by name.
	 * @param int                  $listing_id Optional. Listing ID for context.
	 * @return string Rendered HTML.
	 */
	public function render_grouped_fields( array $values = [], int $listing_id = 0 ): string {
		$groups = $this->get_groups();

		if ( empty( $groups ) ) {
			return $this->render_fields( $values, [], $listing_id );
		}

		$html = '';

		// Track which fields are in groups.
		$grouped_fields = [];

		foreach ( $groups as $group_id => $group ) {
			$group_fields   = $group['fields'] ?? [];
			$grouped_fields = array_merge( $grouped_fields, $group_fields );

			$group_html = $this->render_group( $group_id, $group, $values, $listing_id );

			if ( ! empty( $group_html ) ) {
				$html .= $group_html;
			}
		}

		// Render ungrouped fields.
		$all_field_names  = array_keys( $this->registry->get_fields() );
		$ungrouped_fields = array_diff( $all_field_names, $grouped_fields );

		if ( ! empty( $ungrouped_fields ) ) {
			$html .= $this->render_fields( $values, [ 'fields' => $ungrouped_fields ], $listing_id );
		}

		return $html;
	}

	/**
	 * Render a field group.
	 *
	 * @since 1.0.0
	 *
	 * @param string               $group_id   Group identifier.
	 * @param array<string, mixed> $group      Group configuration.
	 * @param array<string, mixed> $values     Field values keyed by name.
	 * @param int                  $listing_id Listing ID for context.
	 * @return string Rendered HTML.
	 */
	public function render_group( string $group_id, array $group, array $values = [], int $listing_id = 0 ): string {
		$fields = $group['fields'] ?? [];

		if ( empty( $fields ) ) {
			return '';
		}

		// Render group fields first to check if any are visible.
		$fields_html = $this->render_fields( $values, [ 'fields' => $fields ], $listing_id );

		if ( empty( trim( $fields_html ) ) ) {
			return '';
		}

		$wrapper_class  = 'apd-field-group';
		$is_collapsible = ! empty( $group['collapsible'] );
		$is_collapsed   = ! empty( $group['collapsed'] );

		if ( $is_collapsible ) {
			$wrapper_class .= ' apd-field-group--collapsible';
			if ( $is_collapsed ) {
				$wrapper_class .= ' apd-field-group--collapsed';
			}
		}

		/**
		 * Filter the field group wrapper CSS class.
		 *
		 * @since 1.0.0
		 *
		 * @param string               $wrapper_class The wrapper CSS class.
		 * @param string               $group_id      Group identifier.
		 * @param array<string, mixed> $group         Group configuration.
		 */
		$wrapper_class = apply_filters( 'apd_field_group_wrapper_class', $wrapper_class, $group_id, $group );

		$html = sprintf(
			'<div class="%s" data-group-id="%s">',
			esc_attr( $wrapper_class ),
			esc_attr( $group_id )
		);

		// Group header.
		if ( ! empty( $group['title'] ) ) {
			$html .= '<div class="apd-field-group__header">';

			if ( $is_collapsible ) {
				$html .= sprintf(
					'<button type="button" class="apd-field-group__toggle" aria-expanded="%s" aria-controls="apd-group-%s-body">',
					$is_collapsed ? 'false' : 'true',
					esc_attr( $group_id )
				);
				$html .= sprintf(
					'<span class="apd-field-group__title">%s</span>',
					esc_html( $group['title'] )
				);
				$html .= '<span class="apd-field-group__indicator" aria-hidden="true"></span>';
				$html .= '</button>';
			} else {
				$html .= sprintf(
					'<h3 class="apd-field-group__title">%s</h3>',
					esc_html( $group['title'] )
				);
			}

			if ( ! empty( $group['description'] ) ) {
				$html .= sprintf(
					'<p class="apd-field-group__description">%s</p>',
					esc_html( $group['description'] )
				);
			}

			$html .= '</div>'; // .apd-field-group__header
		}

		// Group body with fields.
		$body_attrs = [
			'class' => 'apd-field-group__body',
			'id'    => 'apd-group-' . $group_id . '-body',
		];

		if ( $is_collapsible && $is_collapsed ) {
			$body_attrs['hidden'] = 'hidden';
		}

		$html .= sprintf(
			'<div %s>',
			$this->build_attributes( $body_attrs )
		);

		$html .= $fields_html;

		$html .= '</div>'; // .apd-field-group__body
		$html .= '</div>'; // .apd-field-group

		/**
		 * Filter the rendered field group HTML.
		 *
		 * @since 1.0.0
		 *
		 * @param string               $html       The rendered HTML.
		 * @param string               $group_id   Group identifier.
		 * @param array<string, mixed> $group      Group configuration.
		 * @param array<string, mixed> $values     Field values.
		 * @param int                  $listing_id Listing ID.
		 */
		return apply_filters( 'apd_render_field_group', $html, $group_id, $group, $values, $listing_id );
	}

	/**
	 * Render a complete form for admin meta box.
	 *
	 * @since 1.0.0
	 *
	 * @param int                  $listing_id Listing post ID.
	 * @param array<string, mixed> $args       Optional. Arguments.
	 *                                         - nonce_action: (string) Nonce action name.
	 *                                         - nonce_name: (string) Nonce field name.
	 * @return string Rendered HTML.
	 */
	public function render_admin_fields( int $listing_id, array $args = [] ): string {
		$this->set_context( self::CONTEXT_ADMIN );

		$defaults = [
			'nonce_action' => 'apd_save_listing_fields',
			'nonce_name'   => 'apd_fields_nonce',
		];

		$args = wp_parse_args( $args, $defaults );

		// Get current field values.
		$values = $this->get_listing_values( $listing_id );

		$html = '';

		// Add nonce field.
		$html .= wp_nonce_field( $args['nonce_action'], $args['nonce_name'], true, false );

		// Render fields (grouped or ungrouped).
		if ( ! empty( $this->groups ) ) {
			$html .= $this->render_grouped_fields( $values, $listing_id );
		} else {
			$html .= $this->render_fields( $values, [], $listing_id );
		}

		/**
		 * Fires after admin fields are rendered.
		 *
		 * @since 1.0.0
		 *
		 * @param int                  $listing_id Listing post ID.
		 * @param array<string, mixed> $values     Current field values.
		 */
		do_action( 'apd_after_admin_fields', $listing_id, $values );

		return $html;
	}

	/**
	 * Render fields for frontend submission form.
	 *
	 * @since 1.0.0
	 *
	 * @param int                  $listing_id Optional. Listing ID for editing.
	 * @param array<string, mixed> $args       Optional. Arguments.
	 *                                         - nonce_action: (string) Nonce action name.
	 *                                         - nonce_name: (string) Nonce field name.
	 *                                         - submitted_values: (array) Previously submitted values.
	 * @return string Rendered HTML.
	 */
	public function render_frontend_fields( int $listing_id = 0, array $args = [] ): string {
		$this->set_context( self::CONTEXT_FRONTEND );

		$defaults = [
			'nonce_action'     => 'apd_submit_listing',
			'nonce_name'       => 'apd_submission_nonce',
			'submitted_values' => [],
		];

		$args = wp_parse_args( $args, $defaults );

		// Get field values.
		if ( $listing_id > 0 ) {
			$values = $this->get_listing_values( $listing_id );
		} else {
			$values = $args['submitted_values'];
		}

		$html = '';

		// Add nonce field.
		$html .= wp_nonce_field( $args['nonce_action'], $args['nonce_name'], true, false );

		// Hidden field for listing ID if editing.
		if ( $listing_id > 0 ) {
			$html .= sprintf(
				'<input type="hidden" name="apd_listing_id" value="%d">',
				absint( $listing_id )
			);
		}

		// Render fields (grouped or ungrouped, excluding admin-only).
		if ( ! empty( $this->groups ) ) {
			$html .= $this->render_grouped_fields( $values, $listing_id );
		} else {
			$html .= $this->render_fields( $values, [], $listing_id );
		}

		/**
		 * Fires after frontend fields are rendered.
		 *
		 * @since 1.0.0
		 *
		 * @param int                  $listing_id Listing post ID (0 if new).
		 * @param array<string, mixed> $values     Current field values.
		 */
		do_action( 'apd_after_frontend_fields', $listing_id, $values );

		return $html;
	}

	/**
	 * Render fields for display (single listing view).
	 *
	 * @since 1.0.0
	 *
	 * @param int                  $listing_id Listing post ID.
	 * @param array<string, mixed> $args       Optional. Arguments.
	 *                                         - fields: (array) Specific field names.
	 *                                         - exclude: (array) Field names to exclude.
	 * @return string Rendered HTML.
	 */
	public function render_display_fields( int $listing_id, array $args = [] ): string {
		$this->set_context( self::CONTEXT_DISPLAY );

		$defaults = [
			'fields'  => [],
			'exclude' => [],
		];

		$args = wp_parse_args( $args, $defaults );

		// Exclude admin-only fields from display.
		if ( empty( $args['fields'] ) ) {
			$admin_only_fields = array_keys( $this->registry->get_admin_fields() );
			$args['exclude']   = array_merge( $args['exclude'], $admin_only_fields );
		}

		// Get current field values.
		$values = $this->get_listing_values( $listing_id );

		$html  = '<dl class="apd-field-display-list">';
		$html .= $this->render_fields( $values, $args, $listing_id );
		$html .= '</dl>';

		/**
		 * Filter the display fields HTML.
		 *
		 * @since 1.0.0
		 *
		 * @param string               $html       The rendered HTML.
		 * @param int                  $listing_id Listing post ID.
		 * @param array<string, mixed> $values     Field values.
		 */
		return apply_filters( 'apd_render_display_fields', $html, $listing_id, $values );
	}

	/**
	 * Check if a field should be displayed in the current context.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $field      Field configuration.
	 * @param int                  $listing_id Listing ID for context.
	 * @return bool True if field should be displayed.
	 */
	private function should_display_field( array $field, int $listing_id ): bool {
		// Check admin_only flag for frontend contexts.
		if ( $this->context !== self::CONTEXT_ADMIN && ! empty( $field['admin_only'] ) ) {
			return false;
		}

		/**
		 * Filter whether a field should be displayed.
		 *
		 * Allows for conditional field display based on custom logic.
		 *
		 * @since 1.0.0
		 *
		 * @param bool                 $display    Whether to display the field.
		 * @param array<string, mixed> $field      Field configuration.
		 * @param string               $context    Render context.
		 * @param int                  $listing_id Listing ID.
		 */
		return apply_filters( 'apd_should_display_field', true, $field, $this->context, $listing_id );
	}

	/**
	 * Get all field values for a listing.
	 *
	 * @since 1.0.0
	 *
	 * @param int $listing_id Listing post ID.
	 * @return array<string, mixed> Field values keyed by name.
	 */
	private function get_listing_values( int $listing_id ): array {
		if ( $listing_id <= 0 ) {
			return [];
		}

		$values = [];
		$fields = $this->registry->get_fields();

		foreach ( $fields as $field_name => $field ) {
			$meta_key = $this->registry->get_meta_key( $field_name );
			$value    = get_post_meta( $listing_id, $meta_key, true );

			// Apply field type transformation.
			$field_type = $this->registry->get_field_type( $field['type'] );
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

	/**
	 * Check if a value is considered empty.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Value to check.
	 * @return bool True if empty.
	 */
	private function is_empty_value( mixed $value ): bool {
		if ( is_array( $value ) ) {
			return empty( $value );
		}

		if ( is_string( $value ) ) {
			return trim( $value ) === '';
		}

		return $value === null;
	}

	/**
	 * Build HTML attributes string.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $attributes Attributes key-value pairs.
	 * @return string Attributes string.
	 */
	private function build_attributes( array $attributes ): string {
		$parts = [];

		foreach ( $attributes as $key => $value ) {
			if ( $value === true ) {
				$parts[] = esc_attr( $key );
			} elseif ( $value !== false && $value !== null ) {
				$parts[] = sprintf( '%s="%s"', esc_attr( $key ), esc_attr( (string) $value ) );
			}
		}

		return implode( ' ', $parts );
	}

	/**
	 * Reset groups.
	 *
	 * Primarily used for testing.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function reset_groups(): void {
		$this->groups = [];
	}
}
