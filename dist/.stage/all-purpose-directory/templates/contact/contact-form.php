<?php
/**
 * Contact form template.
 *
 * This template can be overridden by copying it to yourtheme/all-purpose-directory/contact/contact-form.php.
 *
 * @package All_Purpose_Directory
 * @since 1.0.0
 *
 * @var \APD\Contact\ContactForm $form        ContactForm instance.
 * @var int                      $listing_id  Listing ID.
 * @var \WP_Post                 $listing     Listing post object.
 * @var \WP_User                 $owner       Listing owner.
 * @var array                    $errors      Form errors.
 * @var array                    $values      Form values.
 * @var string                   $nonce_action Nonce action.
 * @var string                   $nonce_name  Nonce field name.
 */

defined( 'ABSPATH' ) || exit;

$has_errors   = ! empty( $errors );
$form_classes = $form->get_css_classes();
?>

<div class="apd-contact-form-wrapper">
	<h3 class="apd-contact-form-title">
		<?php esc_html_e( 'Contact the Owner', 'all-purpose-directory' ); ?>
	</h3>

	<?php if ( $has_errors ) : ?>
		<div class="apd-contact-form-errors apd-notice apd-notice--error" role="alert">
			<ul>
				<?php foreach ( $errors as $error ) : ?>
					<li><?php echo esc_html( $error ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<form
		class="<?php echo esc_attr( $form_classes ); ?>"
		method="post"
		action=""
		novalidate
		data-listing-id="<?php echo esc_attr( $listing_id ); ?>"
		aria-label="<?php esc_attr_e( 'Contact form', 'all-purpose-directory' ); ?>"
	>
		<?php wp_nonce_field( $nonce_action, $nonce_name ); ?>
		<input type="hidden" name="action" value="apd_send_contact">
		<input type="hidden" name="listing_id" value="<?php echo esc_attr( $listing_id ); ?>">
		<?php // Timing token for spam protection (HMAC-signed). ?>
		<?php
		$apd_contact_ts  = (string) time();
		$apd_contact_sig = hash_hmac( 'sha256', $apd_contact_ts, wp_salt( 'nonce' ) );
		?>
		<input type="hidden" name="apd_contact_token" value="<?php echo esc_attr( base64_encode( $apd_contact_ts . '|' . $apd_contact_sig ) ); ?>">
		<?php // Honeypot field — hidden from real users, bots will fill it. ?>
		<div class="apd-field apd-field--hp" aria-hidden="true">
			<label for="apd-contact-website-<?php echo esc_attr( $listing_id ); ?>"><?php esc_html_e( 'Website', 'all-purpose-directory' ); ?></label>
			<input type="text" id="apd-contact-website-<?php echo esc_attr( $listing_id ); ?>" name="contact_website" class="apd-field__hp-input" value="" tabindex="-1" autocomplete="off">
		</div>

		<div class="apd-field apd-field--contact-name">
			<label class="apd-field__label" for="apd-contact-name-<?php echo esc_attr( $listing_id ); ?>">
				<?php esc_html_e( 'Your Name', 'all-purpose-directory' ); ?>
				<span class="apd-field__required-indicator" aria-hidden="true">*</span>
			</label>
			<div class="apd-field__input">
				<input
					type="text"
					id="apd-contact-name-<?php echo esc_attr( $listing_id ); ?>"
					name="contact_name"
					class="apd-field__text"
					value="<?php echo esc_attr( $form->get_value( 'contact_name' ) ); ?>"
					required
					aria-required="true"
					autocomplete="name"
				>
			</div>
		</div>

		<div class="apd-field apd-field--contact-email">
			<label class="apd-field__label" for="apd-contact-email-<?php echo esc_attr( $listing_id ); ?>">
				<?php esc_html_e( 'Your Email', 'all-purpose-directory' ); ?>
				<span class="apd-field__required-indicator" aria-hidden="true">*</span>
			</label>
			<div class="apd-field__input">
				<input
					type="email"
					id="apd-contact-email-<?php echo esc_attr( $listing_id ); ?>"
					name="contact_email"
					class="apd-field__text"
					value="<?php echo esc_attr( $form->get_value( 'contact_email' ) ); ?>"
					required
					aria-required="true"
					autocomplete="email"
				>
			</div>
		</div>

		<?php if ( $form->show_phone() ) : ?>
			<div class="apd-field apd-field--contact-phone">
				<label class="apd-field__label" for="apd-contact-phone-<?php echo esc_attr( $listing_id ); ?>">
					<?php esc_html_e( 'Your Phone', 'all-purpose-directory' ); ?>
					<?php if ( $form->is_phone_required() ) : ?>
						<span class="apd-field__required-indicator" aria-hidden="true">*</span>
					<?php else : ?>
						<span class="apd-field__optional"><?php esc_html_e( '(optional)', 'all-purpose-directory' ); ?></span>
					<?php endif; ?>
				</label>
				<div class="apd-field__input">
					<input
						type="tel"
						id="apd-contact-phone-<?php echo esc_attr( $listing_id ); ?>"
						name="contact_phone"
						class="apd-field__text"
						value="<?php echo esc_attr( $form->get_value( 'contact_phone' ) ); ?>"
						<?php echo $form->is_phone_required() ? 'required aria-required="true"' : ''; ?>
						autocomplete="tel"
					>
				</div>
			</div>
		<?php endif; ?>

		<?php if ( $form->show_subject() ) : ?>
			<div class="apd-field apd-field--contact-subject">
				<label class="apd-field__label" for="apd-contact-subject-<?php echo esc_attr( $listing_id ); ?>">
					<?php esc_html_e( 'Subject', 'all-purpose-directory' ); ?>
					<?php if ( $form->is_subject_required() ) : ?>
						<span class="apd-field__required-indicator" aria-hidden="true">*</span>
					<?php else : ?>
						<span class="apd-field__optional"><?php esc_html_e( '(optional)', 'all-purpose-directory' ); ?></span>
					<?php endif; ?>
				</label>
				<div class="apd-field__input">
					<input
						type="text"
						id="apd-contact-subject-<?php echo esc_attr( $listing_id ); ?>"
						name="contact_subject"
						class="apd-field__text"
						value="<?php echo esc_attr( $form->get_value( 'contact_subject' ) ); ?>"
						<?php echo $form->is_subject_required() ? 'required aria-required="true"' : ''; ?>
					>
				</div>
			</div>
		<?php endif; ?>

		<div class="apd-field apd-field--contact-message">
			<label class="apd-field__label" for="apd-contact-message-<?php echo esc_attr( $listing_id ); ?>">
				<?php esc_html_e( 'Message', 'all-purpose-directory' ); ?>
				<span class="apd-field__required-indicator" aria-hidden="true">*</span>
			</label>
			<div class="apd-field__input">
				<textarea
					id="apd-contact-message-<?php echo esc_attr( $listing_id ); ?>"
					name="contact_message"
					class="apd-field__textarea"
					rows="5"
					required
					aria-required="true"
					aria-describedby="apd-contact-message-desc-<?php echo esc_attr( $listing_id ); ?>"
					minlength="<?php echo esc_attr( $form->get_min_message_length() ); ?>"
				><?php echo esc_textarea( $form->get_value( 'contact_message' ) ); ?></textarea>
			</div>
			<p id="apd-contact-message-desc-<?php echo esc_attr( $listing_id ); ?>" class="apd-field__description apd-char-counter" data-min="<?php echo (int) $form->get_min_message_length(); ?>">
				<span class="apd-char-counter__current">0</span> /
				<?php
				printf(
					/* translators: %d: minimum character count */
					esc_html__( '%d characters minimum', 'all-purpose-directory' ),
					(int) $form->get_min_message_length()
				);
				?>
			</p>
		</div>

		<?php
		/**
		 * Fires after contact form fields, before submit button.
		 *
		 * @since 1.0.0
		 * @param int                      $listing_id Listing ID.
		 * @param \APD\Contact\ContactForm $form       ContactForm instance.
		 */
		do_action( 'apd_contact_form_after_fields', $listing_id, $form );
		?>

		<div class="apd-contact-form__actions">
			<button type="submit" class="apd-button apd-button--primary apd-contact-submit">
				<?php esc_html_e( 'Send Message', 'all-purpose-directory' ); ?>
			</button>
		</div>

		<div class="apd-contact-form-success apd-notice apd-notice--success apd-hidden" role="alert">
			<?php esc_html_e( 'Your message has been sent successfully!', 'all-purpose-directory' ); ?>
		</div>
	</form>
</div>
