<?php
/**
 * Review Form Template.
 *
 * Template for rendering the review submission form on listing pages.
 *
 * This template can be overridden by copying it to:
 * yourtheme/all-purpose-directory/review/review-form.php
 *
 * @package APD\Templates
 * @since   1.0.0
 *
 * @var int                  $listing_id         Listing post ID.
 * @var int                  $user_id            Current user ID.
 * @var bool                 $is_logged_in       Whether user is logged in.
 * @var bool                 $requires_login     Whether login is required to review.
 * @var array|null           $user_review        Existing user review data or null.
 * @var bool                 $is_edit_mode       Whether editing existing review.
 * @var array<string, mixed> $config             Form configuration.
 * @var string               $nonce_action       Nonce action.
 * @var string               $nonce_name         Nonce field name.
 * @var int                  $star_count         Number of stars for rating.
 * @var int                  $min_content_length Minimum review content length.
 * @var string               $form_classes       CSS classes for form.
 * @var string               $guidelines_text    Review guidelines text.
 * @var string               $user_name          Current user display name (if logged in).
 * @var string               $user_email         Current user email (if logged in).
 * @var int                  $existing_rating    Existing rating (if edit mode).
 * @var string               $existing_title     Existing title (if edit mode).
 * @var string               $existing_content   Existing content (if edit mode).
 * @var int                  $review_id          Existing review ID (if edit mode).
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get any flash errors/data from previous submission attempt.
$flash_errors = [];
$flash_data   = [];

if ( $user_id > 0 ) {
	$flash_errors = get_transient( 'apd_review_errors_' . $user_id );
	$flash_data   = get_transient( 'apd_review_data_' . $user_id );

	if ( $flash_errors ) {
		delete_transient( 'apd_review_errors_' . $user_id );
	}
	if ( $flash_data ) {
		delete_transient( 'apd_review_data_' . $user_id );
	}
}

// Check for success message via transient flash.
$show_success = false;
if ( $user_id > 0 ) {
	$flash_success = get_transient( 'apd_review_success_' . $user_id );
	if ( $flash_success ) {
		$show_success = true;
		delete_transient( 'apd_review_success_' . $user_id );
	}
}
?>

<?php
/**
 * Fires before the review form.
 *
 * @since 1.0.0
 *
 * @param int $listing_id The listing post ID.
 */
do_action( 'apd_before_review_form', $listing_id );
?>

<?php if ( $show_success ) : ?>
	<div class="apd-review-form__success" role="alert">
		<p>
			<?php
			if ( $is_edit_mode ) {
				esc_html_e( 'Your review has been updated successfully.', 'all-purpose-directory' );
			} else {
				esc_html_e( 'Thank you for your review! It has been submitted and is pending approval.', 'all-purpose-directory' );
			}
			?>
		</p>
	</div>
<?php endif; ?>

<?php if ( ! $is_logged_in && $requires_login ) : ?>
	<div class="apd-review-form__login-required">
		<p>
			<?php
			printf(
				wp_kses(
					/* translators: 1: login URL, 2: register URL */
					__( 'Please <a href="%1$s">log in</a> or <a href="%2$s">register</a> to write a review.', 'all-purpose-directory' ),
					[ 'a' => [ 'href' => [] ] ]
				),
				esc_url( wp_login_url( get_permalink( $listing_id ) ) ),
				esc_url( wp_registration_url() )
			);
			?>
		</p>
	</div>
<?php else : ?>

	<div class="apd-review-form__wrapper">

		<h3 class="apd-review-form__heading">
			<?php
			if ( $is_edit_mode ) {
				esc_html_e( 'Edit Your Review', 'all-purpose-directory' );
			} else {
				esc_html_e( 'Write a Review', 'all-purpose-directory' );
			}
			?>
		</h3>

		<?php if ( $config['show_guidelines'] && ! empty( $guidelines_text ) ) : ?>
			<p class="apd-review-form__guidelines">
				<?php echo esc_html( $guidelines_text ); ?>
			</p>
		<?php endif; ?>

		<?php if ( ! empty( $flash_errors ) && is_array( $flash_errors ) ) : ?>
			<div class="apd-review-form__errors" role="alert">
				<p class="apd-review-form__errors-title">
					<?php esc_html_e( 'Please fix the following errors:', 'all-purpose-directory' ); ?>
				</p>
				<ul class="apd-review-form__errors-list">
					<?php foreach ( $flash_errors as $error ) : ?>
						<li><?php echo esc_html( $error ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<form class="<?php echo esc_attr( $form_classes ); ?>"
			action=""
			method="post"
			data-listing-id="<?php echo absint( $listing_id ); ?>"
			data-min-content-length="<?php echo absint( $min_content_length ); ?>"
			novalidate
			aria-label="<?php echo $is_edit_mode ? esc_attr__( 'Edit review', 'all-purpose-directory' ) : esc_attr__( 'Write review', 'all-purpose-directory' ); ?>">

			<?php wp_nonce_field( $nonce_action, $nonce_name ); ?>

			<input type="hidden" name="apd_action" value="submit_review">
			<input type="hidden" name="listing_id" value="<?php echo absint( $listing_id ); ?>">

			<?php if ( $is_edit_mode && isset( $review_id ) ) : ?>
				<input type="hidden" name="review_id" value="<?php echo absint( $review_id ); ?>">
			<?php endif; ?>

			<?php
			/**
			 * Fires at the start of the review form.
			 *
			 * @since 1.0.0
			 *
			 * @param int   $listing_id   Listing post ID.
			 * @param bool  $is_edit_mode Whether in edit mode.
			 */
			do_action( 'apd_review_form_start', $listing_id, $is_edit_mode );
			?>

			<div class="apd-review-form__field apd-review-form__field--rating">
				<label class="apd-review-form__label" id="apd-rating-label">
					<?php esc_html_e( 'Your Rating', 'all-purpose-directory' ); ?>
					<span class="apd-field__required-indicator" aria-hidden="true">*</span>
				</label>
				<p class="apd-review-form__rating-instructions" id="apd-rating-instructions">
					<?php esc_html_e( 'Click a star to rate', 'all-purpose-directory' ); ?>
				</p>
				<?php
				$selected_rating = $is_edit_mode && isset( $existing_rating ) ? $existing_rating : 0;
				if ( ! empty( $flash_data['rating'] ) ) {
					$selected_rating = (int) $flash_data['rating'];
				}

				apd_get_template(
					'review/star-input.php',
					[
						'selected_rating' => $selected_rating,
						'star_count'      => $star_count,
					]
				);
				?>
			</div>

			<?php if ( $config['show_title'] ) : ?>
				<div class="apd-review-form__field apd-review-form__field--title">
					<label class="apd-review-form__label" for="apd-review-title">
						<?php esc_html_e( 'Review Title', 'all-purpose-directory' ); ?>
						<?php if ( $config['title_required'] ) : ?>
							<span class="apd-field__required-indicator" aria-hidden="true">*</span>
						<?php else : ?>
							<span class="apd-review-form__optional"><?php esc_html_e( '(optional)', 'all-purpose-directory' ); ?></span>
						<?php endif; ?>
					</label>
					<input type="text"
						id="apd-review-title"
						name="review_title"
						class="apd-review-form__input"
						value="<?php echo esc_attr( $flash_data['title'] ?? ( $is_edit_mode && isset( $existing_title ) ? $existing_title : '' ) ); ?>"
						<?php echo $config['title_required'] ? 'required aria-required="true"' : ''; ?>
						placeholder="<?php esc_attr_e( 'Summarize your experience', 'all-purpose-directory' ); ?>"
						maxlength="150">
				</div>
			<?php endif; ?>

			<div class="apd-review-form__field apd-review-form__field--content">
				<label class="apd-review-form__label" for="apd-review-content">
					<?php esc_html_e( 'Your Review', 'all-purpose-directory' ); ?>
					<span class="apd-field__required-indicator" aria-hidden="true">*</span>
				</label>
				<textarea
					id="apd-review-content"
					name="review_content"
					class="apd-review-form__textarea"
					rows="5"
					required
					aria-required="true"
					aria-describedby="apd-review-content-desc"
					placeholder="<?php esc_attr_e( 'Share your experience...', 'all-purpose-directory' ); ?>"
					minlength="<?php echo absint( $min_content_length ); ?>"><?php echo esc_textarea( $flash_data['content'] ?? ( $is_edit_mode && isset( $existing_content ) ? $existing_content : '' ) ); ?></textarea>
				<p id="apd-review-content-desc" class="apd-review-form__description apd-char-counter" data-min="<?php echo absint( $min_content_length ); ?>">
					<span class="apd-char-counter__current">0</span> /
					<?php
					printf(
						/* translators: %d: minimum character count */
						esc_html__( '%d characters minimum', 'all-purpose-directory' ),
						absint( $min_content_length )
					);
					?>
				</p>
			</div>

			<?php if ( ! $is_logged_in && ! $requires_login ) : ?>
				<div class="apd-review-form__guest-fields">
					<div class="apd-review-form__field apd-review-form__field--name">
						<label class="apd-review-form__label" for="apd-review-author-name">
							<?php esc_html_e( 'Your Name', 'all-purpose-directory' ); ?>
							<span class="apd-field__required-indicator" aria-hidden="true">*</span>
						</label>
						<input type="text"
							id="apd-review-author-name"
							name="author_name"
							class="apd-review-form__input"
							value="<?php echo esc_attr( $flash_data['author_name'] ?? '' ); ?>"
							required
							aria-required="true">
					</div>

					<div class="apd-review-form__field apd-review-form__field--email">
						<label class="apd-review-form__label" for="apd-review-author-email">
							<?php esc_html_e( 'Your Email', 'all-purpose-directory' ); ?>
							<span class="apd-field__required-indicator" aria-hidden="true">*</span>
						</label>
						<input type="email"
							id="apd-review-author-email"
							name="author_email"
							class="apd-review-form__input"
							value="<?php echo esc_attr( $flash_data['author_email'] ?? '' ); ?>"
							required
							aria-required="true">
						<p class="apd-review-form__description">
							<?php esc_html_e( 'Your email will not be published.', 'all-purpose-directory' ); ?>
						</p>
					</div>
				</div>
			<?php endif; ?>

			<?php
			/**
			 * Fires before the review form submit button.
			 *
			 * @since 1.0.0
			 *
			 * @param int   $listing_id   Listing post ID.
			 * @param bool  $is_edit_mode Whether in edit mode.
			 */
			do_action( 'apd_review_form_before_submit', $listing_id, $is_edit_mode );
			?>

			<div class="apd-review-form__actions">
				<button type="submit" class="apd-review-form__submit apd-button apd-button--primary">
					<?php
					if ( $is_edit_mode ) {
						esc_html_e( 'Update Review', 'all-purpose-directory' );
					} else {
						esc_html_e( 'Submit Review', 'all-purpose-directory' );
					}
					?>
				</button>
			</div>

			<div class="apd-review-form__message" role="status" aria-live="polite"></div>

			<?php
			/**
			 * Fires at the end of the review form.
			 *
			 * @since 1.0.0
			 *
			 * @param int   $listing_id   Listing post ID.
			 * @param bool  $is_edit_mode Whether in edit mode.
			 */
			do_action( 'apd_review_form_end', $listing_id, $is_edit_mode );
			?>

		</form>

	</div>

<?php endif; ?>

<?php
/**
 * Fires after the review form.
 *
 * @since 1.0.0
 *
 * @param int $listing_id The listing post ID.
 */
do_action( 'apd_after_review_form', $listing_id );
?>
