<?php
/**
 * Profile Template.
 *
 * Template for the Profile dashboard tab.
 *
 * This template can be overridden by copying it to:
 * yourtheme/all-purpose-directory/dashboard/profile.php
 *
 * @package APD\Templates
 * @since   1.0.0
 *
 * @var APD\Frontend\Dashboard\Profile $profile    The Profile instance.
 * @var array<string, mixed>           $user_data  User's profile data.
 * @var array<string, mixed>           $config     Configuration options.
 * @var array<string, string>|null     $message    Message to display (type, message).
 * @var string                         $nonce      Security nonce.
 * @var int                            $user_id    Current user ID.
 * @var string                         $avatar_url User's avatar URL.
 */

use APD\Frontend\Dashboard\Profile;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$social_labels = $profile->get_social_labels();
$social_icons  = $profile->get_social_icons();
$has_avatar    = $profile->has_custom_avatar( $user_id );
?>

<div class="apd-profile">

	<?php
	/**
	 * Fires at the start of the Profile content.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Template arguments.
	 */
	do_action( 'apd_profile_start', $args );
	?>

	<?php if ( $message ) : ?>
		<div class="apd-notice apd-notice--<?php echo esc_attr( $message['type'] ); ?>" role="alert">
			<p><?php echo esc_html( $message['message'] ); ?></p>
		</div>
	<?php endif; ?>

	<form method="post" enctype="multipart/form-data" class="apd-profile-form" novalidate>
		<input type="hidden" name="apd_profile_action" value="save">
		<input type="hidden" name="<?php echo esc_attr( Profile::NONCE_NAME ); ?>" value="<?php echo esc_attr( $nonce ); ?>">

		<?php if ( $config['show_avatar'] ) : ?>
			<!-- Avatar Section -->
			<div class="apd-profile-form__section apd-profile-form__section--avatar">
				<h3 class="apd-profile-form__section-title">
					<?php esc_html_e( 'Profile Photo', 'all-purpose-directory' ); ?>
				</h3>

				<?php
				apd_get_template(
					'dashboard/profile-avatar.php',
					[
						'profile'    => $profile,
						'user_id'    => $user_id,
						'avatar_url' => $avatar_url,
						'has_avatar' => $has_avatar,
						'config'     => $config,
					]
				);
				?>
			</div>
		<?php endif; ?>

		<!-- Basic Information Section -->
		<div class="apd-profile-form__section">
			<h3 class="apd-profile-form__section-title">
				<?php esc_html_e( 'Basic Information', 'all-purpose-directory' ); ?>
			</h3>

			<div class="apd-profile-form__fields">
				<!-- Display Name -->
				<div class="apd-field">
					<label for="apd-display-name" class="apd-field__label">
						<?php esc_html_e( 'Display Name', 'all-purpose-directory' ); ?>
						<span class="apd-field__required-indicator" aria-hidden="true">*</span>
					</label>
					<div class="apd-field__input">
						<input type="text"
							id="apd-display-name"
							name="display_name"
							value="<?php echo esc_attr( $user_data['display_name'] ); ?>"
							class="apd-field__text"
							required
							aria-required="true">
					</div>
					<p class="apd-field__description">
						<?php esc_html_e( 'This is how your name will appear publicly.', 'all-purpose-directory' ); ?>
					</p>
				</div>

				<!-- First Name / Last Name Row -->
				<div class="apd-profile-form__row apd-profile-form__row--half">
					<!-- First Name -->
					<div class="apd-field">
						<label for="apd-first-name" class="apd-field__label">
							<?php esc_html_e( 'First Name', 'all-purpose-directory' ); ?>
						</label>
						<div class="apd-field__input">
							<input type="text"
								id="apd-first-name"
								name="first_name"
								value="<?php echo esc_attr( $user_data['first_name'] ); ?>"
								class="apd-field__text">
						</div>
					</div>

					<!-- Last Name -->
					<div class="apd-field">
						<label for="apd-last-name" class="apd-field__label">
							<?php esc_html_e( 'Last Name', 'all-purpose-directory' ); ?>
						</label>
						<div class="apd-field__input">
							<input type="text"
								id="apd-last-name"
								name="last_name"
								value="<?php echo esc_attr( $user_data['last_name'] ); ?>"
								class="apd-field__text">
						</div>
					</div>
				</div>

				<!-- Email -->
				<div class="apd-field">
					<label for="apd-email" class="apd-field__label">
						<?php esc_html_e( 'Email Address', 'all-purpose-directory' ); ?>
						<span class="apd-field__required-indicator" aria-hidden="true">*</span>
					</label>
					<div class="apd-field__input">
						<input type="email"
							id="apd-email"
							name="user_email"
							value="<?php echo esc_attr( $user_data['user_email'] ); ?>"
							class="apd-field__text"
							required
							aria-required="true">
					</div>
				</div>

				<!-- Phone -->
				<div class="apd-field">
					<label for="apd-phone" class="apd-field__label">
						<?php esc_html_e( 'Phone Number', 'all-purpose-directory' ); ?>
					</label>
					<div class="apd-field__input">
						<input type="tel"
							id="apd-phone"
							name="phone"
							value="<?php echo esc_attr( $user_data['phone'] ); ?>"
							class="apd-field__text">
					</div>
				</div>

				<!-- Website -->
				<div class="apd-field">
					<label for="apd-website" class="apd-field__label">
						<?php esc_html_e( 'Website', 'all-purpose-directory' ); ?>
					</label>
					<div class="apd-field__input">
						<input type="url"
							id="apd-website"
							name="user_url"
							value="<?php echo esc_attr( $user_data['user_url'] ); ?>"
							class="apd-field__text"
							placeholder="https://">
					</div>
				</div>

				<!-- Bio -->
				<div class="apd-field">
					<label for="apd-bio" class="apd-field__label">
						<?php esc_html_e( 'Bio', 'all-purpose-directory' ); ?>
					</label>
					<div class="apd-field__input">
						<textarea
							id="apd-bio"
							name="description"
							class="apd-field__textarea apd-field__textarea--short"
							rows="4"><?php echo esc_textarea( $user_data['description'] ); ?></textarea>
					</div>
					<p class="apd-field__description">
						<?php esc_html_e( 'A short description about yourself.', 'all-purpose-directory' ); ?>
					</p>
				</div>
			</div>
		</div>

		<?php if ( $config['show_social'] ) : ?>
			<!-- Social Links Section -->
			<div class="apd-profile-form__section">
				<h3 class="apd-profile-form__section-title">
					<?php esc_html_e( 'Social Links', 'all-purpose-directory' ); ?>
				</h3>

				<div class="apd-profile-form__fields">
					<?php foreach ( Profile::SOCIAL_PLATFORMS as $platform ) : ?>
						<div class="apd-field apd-field--social">
							<label for="apd-social-<?php echo esc_attr( $platform ); ?>" class="apd-field__label">
								<span class="dashicons <?php echo esc_attr( $social_icons[ $platform ] ); ?>" aria-hidden="true"></span>
								<?php echo esc_html( $social_labels[ $platform ] ); ?>
							</label>
							<div class="apd-field__input">
								<input type="url"
									id="apd-social-<?php echo esc_attr( $platform ); ?>"
									name="social_<?php echo esc_attr( $platform ); ?>"
									value="<?php echo esc_attr( $user_data['social'][ $platform ] ?? '' ); ?>"
									class="apd-field__text"
									placeholder="https://">
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif; ?>

		<!-- Submit Section -->
		<div class="apd-profile-form__section apd-profile-form__section--actions">
			<button type="submit"
				class="apd-button apd-button--primary apd-profile-form__submit"
				data-submitting-text="<?php esc_attr_e( 'Saving…', 'all-purpose-directory' ); ?>">
				<?php esc_html_e( 'Save Profile', 'all-purpose-directory' ); ?>
			</button>
		</div>

	</form>

	<?php
	/**
	 * Fires at the end of the Profile content.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Template arguments.
	 */
	do_action( 'apd_profile_end', $args );
	?>

</div>
