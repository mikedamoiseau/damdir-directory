<?php
/**
 * Submission Form Template.
 *
 * Template for rendering the listing submission form on the frontend.
 *
 * This template can be overridden by copying it to:
 * yourtheme/all-purpose-directory/submission/submission-form.php
 *
 * @package APD\Templates
 * @since   1.0.0
 *
 * @var APD\Frontend\Submission\SubmissionForm $form              The form instance.
 * @var array<string, mixed>                   $config            Form configuration.
 * @var array<string, array>                   $fields            Custom fields to render.
 * @var array<string, mixed>                   $field_values      Current field values.
 * @var APD\Fields\FieldRenderer               $field_renderer    Field renderer instance.
 * @var array<string, string[]>                $errors            Validation errors.
 * @var int                                    $listing_id        Listing ID (0 for new).
 * @var string                                 $title_value       Listing title value.
 * @var string                                 $content_value     Listing content value.
 * @var string                                 $excerpt_value     Listing excerpt value.
 * @var \WP_Term[]                             $categories        Available categories.
 * @var \WP_Term[]                             $tags              Available tags.
 * @var array                                  $category_options  Hierarchical category options.
 * @var int[]                                  $selected_categories Selected category IDs.
 * @var int[]                                  $selected_tags     Selected tag IDs.
 * @var int                                    $featured_image_id Featured image ID.
 * @var string                                 $form_classes      CSS classes for form.
 * @var string                                 $nonce_action      Nonce action.
 * @var string                                 $nonce_name        Nonce field name.
 * @var bool                                   $spam_protection_enabled Whether spam protection is enabled.
 * @var string                                 $honeypot_field_html     Honeypot field HTML.
 * @var string                                 $timestamp_field_html    Timestamp field HTML.
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<form class="<?php echo esc_attr( $form_classes ); ?>"
	action=""
	method="post"
	enctype="multipart/form-data"
	data-validate="true"
	aria-label="<?php echo $listing_id > 0 ? esc_attr__( 'Edit listing', 'all-purpose-directory' ) : esc_attr__( 'Submit listing', 'all-purpose-directory' ); ?>">

	<?php wp_nonce_field( $nonce_action, $nonce_name ); ?>

	<?php
	// Output spam protection fields.
	if ( $spam_protection_enabled ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML is pre-escaped in SubmissionForm.
		echo $timestamp_field_html;
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML is pre-escaped in SubmissionForm.
		echo $honeypot_field_html;
	}
	?>

	<?php if ( $listing_id > 0 ) : ?>
		<input type="hidden" name="apd_listing_id" value="<?php echo absint( $listing_id ); ?>">
	<?php endif; ?>

	<input type="hidden" name="apd_action" value="submit_listing">

	<?php if ( ! empty( $config['redirect'] ) ) : ?>
		<input type="hidden" name="apd_redirect" value="<?php echo esc_url( $config['redirect'] ); ?>">
	<?php endif; ?>

	<?php
	/**
	 * Fires at the start of the submission form, inside the form tag.
	 *
	 * @since 1.0.0
	 *
	 * @param array $config Form configuration.
	 * @param int   $listing_id Listing ID (0 for new).
	 */
	do_action( 'apd_submission_form_start', $config, $listing_id );
	?>

	<?php
	// Build progress steps from visible sections.
	$progress_steps = [];
	if ( $config['show_title'] || $config['show_content'] ) {
		$progress_steps[] = [
			'id'    => 'apd-section-basic',
			'label' => __( 'Basic Info', 'all-purpose-directory' ),
		];
	}
	if ( ! empty( $fields ) ) {
		$progress_steps[] = [
			'id'    => 'apd-section-details',
			'label' => __( 'Details', 'all-purpose-directory' ),
		];
	}
	if ( ( $config['show_categories'] && ! empty( $categories ) ) || ( $config['show_tags'] && ! empty( $tags ) ) ) {
		$progress_steps[] = [
			'id'    => 'apd-section-taxonomy',
			'label' => __( 'Categories', 'all-purpose-directory' ),
		];
	}
	if ( $config['show_featured_image'] ) {
		$progress_steps[] = [
			'id'    => 'apd-section-image',
			'label' => __( 'Image', 'all-purpose-directory' ),
		];
	}
	?>
	<?php if ( count( $progress_steps ) > 1 ) : ?>
		<nav class="apd-submission-progress" aria-label="<?php esc_attr_e( 'Form sections', 'all-purpose-directory' ); ?>">
			<ol class="apd-submission-progress__steps">
				<?php foreach ( $progress_steps as $step_index => $step ) : ?>
					<li class="apd-submission-progress__step">
						<a href="#<?php echo esc_attr( $step['id'] ); ?>" class="apd-submission-progress__link">
							<span class="apd-submission-progress__number" aria-hidden="true"><?php echo absint( $step_index + 1 ); ?></span>
							<span class="apd-submission-progress__label"><?php echo esc_html( $step['label'] ); ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ol>
		</nav>
	<?php endif; ?>

	<?php if ( $form->has_errors() ) : ?>
		<div class="apd-submission-form__errors" role="alert">
			<p class="apd-submission-form__errors-title">
				<?php esc_html_e( 'Please fix the following errors:', 'all-purpose-directory' ); ?>
			</p>
			<ul class="apd-submission-form__errors-list">
				<?php foreach ( $errors as $field_name => $field_errors ) : ?>
					<?php foreach ( $field_errors as $error ) : ?>
						<li><?php echo esc_html( $error ); ?></li>
					<?php endforeach; ?>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<?php if ( $config['show_title'] ) : ?>
		<div id="apd-section-basic" class="apd-submission-form__section apd-submission-form__section--basic">
			<div class="apd-field apd-field--frontend apd-field--required <?php echo isset( $errors['listing_title'] ) ? 'apd-field--has-error' : ''; ?>"
				data-field-name="listing_title"
				data-field-type="text">
				<label class="apd-field__label" for="apd-field-listing-title">
					<?php esc_html_e( 'Listing Title', 'all-purpose-directory' ); ?>
					<span class="apd-field__required-indicator" aria-hidden="true">*</span>
				</label>
				<div class="apd-field__input">
					<input type="text"
						id="apd-field-listing-title"
						name="listing_title"
						class="apd-field__text"
						value="<?php echo esc_attr( $title_value ); ?>"
						required
						aria-required="true"
						aria-describedby="apd-field-listing-title-desc"
						placeholder="<?php esc_attr_e( 'Enter a descriptive title for your listing', 'all-purpose-directory' ); ?>">
				</div>
				<p id="apd-field-listing-title-desc" class="apd-field__description">
					<?php esc_html_e( 'Choose a clear and descriptive title that summarizes your listing.', 'all-purpose-directory' ); ?>
				</p>
				<?php if ( isset( $errors['listing_title'] ) ) : ?>
					<div class="apd-field__errors" role="alert" aria-live="polite">
						<?php foreach ( $errors['listing_title'] as $error ) : ?>
							<p class="apd-field__error"><?php echo esc_html( $error ); ?></p>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>

	<?php if ( $config['show_content'] ) : ?>
		<div class="apd-submission-form__section apd-submission-form__section--content">
			<div class="apd-field apd-field--frontend apd-field--required <?php echo isset( $errors['listing_content'] ) ? 'apd-field--has-error' : ''; ?>"
				data-field-name="listing_content"
				data-field-type="textarea">
				<label class="apd-field__label" for="apd-field-listing-content">
					<?php esc_html_e( 'Description', 'all-purpose-directory' ); ?>
					<span class="apd-field__required-indicator" aria-hidden="true">*</span>
				</label>
				<div class="apd-field__input">
					<textarea
						id="apd-field-listing-content"
						name="listing_content"
						class="apd-field__textarea"
						rows="8"
						required
						aria-required="true"
						aria-describedby="apd-field-listing-content-desc"
						placeholder="<?php esc_attr_e( 'Provide a detailed description of your listing', 'all-purpose-directory' ); ?>"><?php echo esc_textarea( $content_value ); ?></textarea>
				</div>
				<p id="apd-field-listing-content-desc" class="apd-field__description">
					<?php esc_html_e( 'Provide a comprehensive description to help users understand your listing.', 'all-purpose-directory' ); ?>
				</p>
				<?php if ( isset( $errors['listing_content'] ) ) : ?>
					<div class="apd-field__errors" role="alert" aria-live="polite">
						<?php foreach ( $errors['listing_content'] as $error ) : ?>
							<p class="apd-field__error"><?php echo esc_html( $error ); ?></p>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>

	<?php if ( $config['show_excerpt'] ) : ?>
		<div class="apd-submission-form__section apd-submission-form__section--excerpt">
			<div class="apd-field apd-field--frontend <?php echo isset( $errors['listing_excerpt'] ) ? 'apd-field--has-error' : ''; ?>"
				data-field-name="listing_excerpt"
				data-field-type="textarea">
				<label class="apd-field__label" for="apd-field-listing-excerpt">
					<?php esc_html_e( 'Short Description', 'all-purpose-directory' ); ?>
				</label>
				<div class="apd-field__input">
					<textarea
						id="apd-field-listing-excerpt"
						name="listing_excerpt"
						class="apd-field__textarea apd-field__textarea--short"
						rows="3"
						aria-describedby="apd-field-listing-excerpt-desc"
						placeholder="<?php esc_attr_e( 'Brief summary for search results', 'all-purpose-directory' ); ?>"><?php echo esc_textarea( $excerpt_value ); ?></textarea>
				</div>
				<p id="apd-field-listing-excerpt-desc" class="apd-field__description">
					<?php esc_html_e( 'Optional short summary that appears in listing previews.', 'all-purpose-directory' ); ?>
				</p>
				<?php if ( isset( $errors['listing_excerpt'] ) ) : ?>
					<div class="apd-field__errors" role="alert" aria-live="polite">
						<?php foreach ( $errors['listing_excerpt'] as $error ) : ?>
							<p class="apd-field__error"><?php echo esc_html( $error ); ?></p>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>

	<?php
	/**
	 * Fires after the basic fields (title, content, excerpt) in the submission form.
	 *
	 * @since 1.0.0
	 *
	 * @param array $config Form configuration.
	 * @param int   $listing_id Listing ID (0 for new).
	 */
	do_action( 'apd_submission_form_after_basic_fields', $config, $listing_id );
	?>

	<?php if ( ! empty( $fields ) ) : ?>
		<div id="apd-section-details" class="apd-submission-form__section apd-submission-form__section--custom-fields">
			<h3 class="apd-submission-form__section-title">
				<?php esc_html_e( 'Additional Details', 'all-purpose-directory' ); ?>
			</h3>
			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $field_renderer->render_fields( $field_values, [ 'fields' => array_keys( $fields ) ], $listing_id );
			?>
		</div>
	<?php endif; ?>

	<?php
	/**
	 * Fires after custom fields in the submission form.
	 *
	 * @since 1.0.0
	 *
	 * @param array $config Form configuration.
	 * @param int   $listing_id Listing ID (0 for new).
	 */
	do_action( 'apd_submission_form_after_custom_fields', $config, $listing_id );
	?>

	<?php if ( $config['show_categories'] && ! empty( $categories ) ) : ?>
		<div id="apd-section-taxonomy" class="apd-submission-form__section apd-submission-form__section--categories">
			<?php
			apd_get_template(
				'submission/category-selector.php',
				[
					'categories'          => $categories,
					'category_options'    => $category_options,
					'selected_categories' => $selected_categories,
					'errors'              => $errors['listing_categories'] ?? [],
				]
			);
			?>
		</div>
	<?php endif; ?>

	<?php if ( $config['show_tags'] && ! empty( $tags ) ) : ?>
		<div class="apd-submission-form__section apd-submission-form__section--tags">
			<?php
			apd_get_template(
				'submission/tag-selector.php',
				[
					'tags'          => $tags,
					'selected_tags' => $selected_tags,
					'errors'        => $errors['listing_tags'] ?? [],
				]
			);
			?>
		</div>
	<?php endif; ?>

	<?php
	/**
	 * Fires after taxonomy fields in the submission form.
	 *
	 * @since 1.0.0
	 *
	 * @param array $config Form configuration.
	 * @param int   $listing_id Listing ID (0 for new).
	 */
	do_action( 'apd_submission_form_after_taxonomies', $config, $listing_id );
	?>

	<?php if ( $config['show_featured_image'] ) : ?>
		<div id="apd-section-image" class="apd-submission-form__section apd-submission-form__section--image">
			<?php
			apd_get_template(
				'submission/image-upload.php',
				[
					'featured_image_id' => $featured_image_id,
					'errors'            => $errors['featured_image'] ?? [],
				]
			);
			?>
		</div>
	<?php endif; ?>

	<?php
	/**
	 * Fires after the featured image field in the submission form.
	 *
	 * @since 1.0.0
	 *
	 * @param array $config Form configuration.
	 * @param int   $listing_id Listing ID (0 for new).
	 */
	do_action( 'apd_submission_form_after_image', $config, $listing_id );
	?>

	<?php if ( $config['show_terms'] ) : ?>
		<div class="apd-submission-form__section apd-submission-form__section--terms">
			<div class="apd-field apd-field--frontend <?php echo $config['terms_required'] ? 'apd-field--required' : ''; ?> <?php echo isset( $errors['terms_accepted'] ) ? 'apd-field--has-error' : ''; ?>"
				data-field-name="terms_accepted"
				data-field-type="checkbox">
				<div class="apd-field__input">
					<label class="apd-field__checkbox-label">
						<input type="checkbox"
							id="apd-field-terms-accepted"
							name="terms_accepted"
							value="1"
							class="apd-field__checkbox"
							<?php echo $config['terms_required'] ? 'required aria-required="true"' : ''; ?>>
						<span class="apd-field__checkbox-text">
							<?php
							if ( ! empty( $config['terms_link'] ) ) {
								// Output terms text as a link.
								echo '<a href="' . esc_url( $config['terms_link'] ) . '" target="_blank" rel="noopener">';
								echo esc_html( $config['terms_text'] );
								echo '</a>';
							} else {
								echo esc_html( $config['terms_text'] );
							}

							if ( $config['terms_required'] ) {
								echo ' <span class="apd-field__required-indicator" aria-hidden="true">*</span>';
							}
							?>
						</span>
					</label>
				</div>
				<?php if ( isset( $errors['terms_accepted'] ) ) : ?>
					<div class="apd-field__errors" role="alert" aria-live="polite">
						<?php foreach ( $errors['terms_accepted'] as $error ) : ?>
							<p class="apd-field__error"><?php echo esc_html( $error ); ?></p>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>

	<?php
	/**
	 * Fires before the submit button in the submission form.
	 *
	 * @since 1.0.0
	 *
	 * @param array $config Form configuration.
	 * @param int   $listing_id Listing ID (0 for new).
	 */
	do_action( 'apd_submission_form_before_submit', $config, $listing_id );
	?>

	<div class="apd-submission-form__actions">
		<button type="submit" class="apd-submission-form__submit apd-button apd-button--primary">
			<?php echo esc_html( $config['submit_text'] ); ?>
		</button>
	</div>

	<?php
	/**
	 * Fires at the end of the submission form, inside the form tag.
	 *
	 * @since 1.0.0
	 *
	 * @param array $config Form configuration.
	 * @param int   $listing_id Listing ID (0 for new).
	 */
	do_action( 'apd_submission_form_end', $config, $listing_id );
	?>

</form>
