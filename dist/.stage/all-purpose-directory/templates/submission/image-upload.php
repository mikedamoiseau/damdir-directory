<?php
/**
 * Image Upload Template.
 *
 * Template for rendering the featured image upload in the submission form.
 *
 * This template can be overridden by copying it to:
 * yourtheme/all-purpose-directory/submission/image-upload.php
 *
 * @package APD\Templates
 * @since   1.0.0
 *
 * @var int   $featured_image_id Currently selected image attachment ID.
 * @var array $errors            Validation errors for this field.
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$has_errors = ! empty( $errors );
$has_image  = $featured_image_id > 0;
$image_url  = '';
$image_alt  = '';

if ( $has_image ) {
	$image_url = wp_get_attachment_image_url( $featured_image_id, 'medium' );
	$image_alt = get_post_meta( $featured_image_id, '_wp_attachment_image_alt', true );
}
?>

<div class="apd-field apd-field--frontend apd-field--image-upload <?php echo $has_errors ? 'apd-field--has-error' : ''; ?> <?php echo $has_image ? 'apd-field--has-image' : ''; ?>"
	data-field-name="featured_image"
	data-field-type="image">
	<label class="apd-field__label" id="apd-field-featured-image-label">
		<?php esc_html_e( 'Featured Image', 'all-purpose-directory' ); ?>
	</label>
	<p id="apd-field-featured-image-desc" class="apd-field__description">
		<?php esc_html_e( 'Upload an image to represent your listing. Recommended size: 1200x800 pixels.', 'all-purpose-directory' ); ?>
	</p>

	<div class="apd-field__input">
		<div class="apd-image-upload" aria-labelledby="apd-field-featured-image-label" aria-describedby="apd-field-featured-image-desc">
			<div class="apd-image-upload__preview <?php echo $has_image ? 'apd-image-upload__preview--visible' : ''; ?>">
				<?php if ( $has_image && $image_url ) : ?>
					<img src="<?php echo esc_url( $image_url ); ?>"
						alt="<?php echo esc_attr( $image_alt ?: __( 'Featured image preview', 'all-purpose-directory' ) ); ?>"
						class="apd-image-upload__image">
				<?php endif; ?>
			</div>

			<div class="apd-image-upload__controls">
				<input type="hidden"
					name="featured_image"
					id="apd-field-featured-image"
					value="<?php echo absint( $featured_image_id ); ?>"
					class="apd-image-upload__input">

				<input type="file"
					name="featured_image_file"
					id="apd-field-featured-image-file"
					accept="image/jpeg,image/png,image/gif,image/webp"
					class="apd-image-upload__file"
					aria-describedby="apd-field-featured-image-desc">

				<label for="apd-field-featured-image-file" class="apd-image-upload__button apd-button apd-button--secondary">
					<span class="apd-image-upload__button-icon" aria-hidden="true">
						<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
							<circle cx="8.5" cy="8.5" r="1.5"></circle>
							<polyline points="21 15 16 10 5 21"></polyline>
						</svg>
					</span>
					<span class="apd-image-upload__button-text">
						<?php echo $has_image ? esc_html__( 'Change Image', 'all-purpose-directory' ) : esc_html__( 'Select Image', 'all-purpose-directory' ); ?>
					</span>
				</label>

				<?php if ( $has_image ) : ?>
					<button type="button"
						class="apd-image-upload__remove apd-button apd-button--text"
						aria-label="<?php esc_attr_e( 'Remove featured image', 'all-purpose-directory' ); ?>">
						<?php esc_html_e( 'Remove', 'all-purpose-directory' ); ?>
					</button>
				<?php endif; ?>
			</div>

			<p class="apd-image-upload__hint">
				<?php
				printf(
					/* translators: %s: maximum file size */
					esc_html__( 'Accepted formats: JPG, PNG, GIF, WebP. Maximum size: %s.', 'all-purpose-directory' ),
					esc_html( size_format( wp_max_upload_size() ) )
				);
				?>
			</p>
		</div>
	</div>

	<?php if ( $has_errors ) : ?>
		<div class="apd-field__errors" role="alert" aria-live="polite">
			<?php foreach ( $errors as $error ) : ?>
				<p class="apd-field__error"><?php echo esc_html( $error ); ?></p>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
