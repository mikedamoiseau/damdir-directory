<?php
/**
 * Tag Selector Template.
 *
 * Template for rendering the tag selection in the submission form.
 * Uses a checkbox list for selecting tags.
 *
 * This template can be overridden by copying it to:
 * yourtheme/all-purpose-directory/submission/tag-selector.php
 *
 * @package APD\Templates
 * @since   1.0.0
 *
 * @var \WP_Term[] $tags          Available tags.
 * @var int[]      $selected_tags Currently selected tag IDs.
 * @var array      $errors        Validation errors for this field.
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$has_errors = ! empty( $errors );
?>

<div class="apd-field apd-field--frontend apd-field--checkbox-group <?php echo $has_errors ? 'apd-field--has-error' : ''; ?>"
	data-field-name="listing_tags"
	data-field-type="checkbox_group">
	<fieldset class="apd-field__fieldset">
		<legend class="apd-field__legend">
			<?php esc_html_e( 'Tags', 'all-purpose-directory' ); ?>
		</legend>
		<p id="apd-field-listing-tags-desc" class="apd-field__description">
			<?php esc_html_e( 'Select tags to help users find your listing.', 'all-purpose-directory' ); ?>
		</p>
		<div class="apd-field__input">
			<div class="apd-field__checkbox-options apd-field__checkbox-options--tags" role="group" aria-describedby="apd-field-listing-tags-desc">
				<?php foreach ( $tags as $tag ) : ?>
					<div class="apd-field__checkbox-option">
						<label class="apd-field__checkbox-label apd-field__checkbox-label--tag">
							<input type="checkbox"
								name="listing_tags[]"
								value="<?php echo absint( $tag->term_id ); ?>"
								class="apd-field__checkbox"
								<?php checked( in_array( $tag->term_id, $selected_tags, true ) ); ?>>
							<span class="apd-field__checkbox-text apd-field__checkbox-text--tag">
								<?php echo esc_html( $tag->name ); ?>
							</span>
						</label>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</fieldset>
	<?php if ( $has_errors ) : ?>
		<div class="apd-field__errors" role="alert" aria-live="polite">
			<?php foreach ( $errors as $error ) : ?>
				<p class="apd-field__error"><?php echo esc_html( $error ); ?></p>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
