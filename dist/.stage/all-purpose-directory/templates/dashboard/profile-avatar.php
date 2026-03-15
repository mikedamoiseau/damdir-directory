<?php
/**
 * Profile Avatar Template.
 *
 * Template for the avatar upload section in the Profile tab.
 *
 * This template can be overridden by copying it to:
 * yourtheme/all-purpose-directory/dashboard/profile-avatar.php
 *
 * @package APD\Templates
 * @since   1.0.0
 *
 * @var APD\Frontend\Dashboard\Profile $profile    The Profile instance.
 * @var int                            $user_id    Current user ID.
 * @var string                         $avatar_url User's avatar URL.
 * @var bool                           $has_avatar Whether user has a custom avatar.
 * @var array<string, mixed>           $config     Configuration options.
 */

use APD\Frontend\Dashboard\Profile;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$avatar_size = $config['avatar_size'] ?? 150;
?>

<div class="apd-profile-avatar" data-has-avatar="<?php echo $has_avatar ? 'true' : 'false'; ?>">

	<div class="apd-profile-avatar__preview">
		<img
			src="<?php echo esc_url( $avatar_url ); ?>"
			alt="<?php esc_attr_e( 'Profile photo', 'all-purpose-directory' ); ?>"
			class="apd-profile-avatar__image"
			width="<?php echo esc_attr( $avatar_size ); ?>"
			height="<?php echo esc_attr( $avatar_size ); ?>"
			id="apd-avatar-preview">
	</div>

	<div class="apd-profile-avatar__actions">
		<div class="apd-profile-avatar__upload">
			<label for="apd-avatar-input" class="apd-button apd-button--secondary apd-profile-avatar__upload-btn">
				<span class="dashicons dashicons-upload" aria-hidden="true"></span>
				<?php esc_html_e( 'Upload New Photo', 'all-purpose-directory' ); ?>
			</label>
			<input
				type="file"
				id="apd-avatar-input"
				name="apd_avatar"
				accept="image/jpeg,image/png,image/gif,image/webp"
				class="apd-profile-avatar__input"
				aria-describedby="apd-avatar-help">
		</div>

		<?php if ( $has_avatar ) : ?>
			<label class="apd-profile-avatar__remove">
				<input
					type="checkbox"
					name="apd_remove_avatar"
					value="1"
					class="apd-profile-avatar__remove-checkbox"
					id="apd-remove-avatar">
				<span class="apd-profile-avatar__remove-label">
					<?php esc_html_e( 'Remove custom photo', 'all-purpose-directory' ); ?>
				</span>
			</label>
		<?php endif; ?>
	</div>

	<p class="apd-profile-avatar__help" id="apd-avatar-help">
		<?php
		printf(
			/* translators: %s: Maximum file size */
			esc_html__( 'Accepted formats: JPEG, PNG, GIF, or WebP. Maximum size: %s.', 'all-purpose-directory' ),
			esc_html( size_format( Profile::MAX_AVATAR_SIZE ) )
		);
		?>
		<?php if ( ! $has_avatar ) : ?>
			<?php esc_html_e( 'Currently using Gravatar.', 'all-purpose-directory' ); ?>
		<?php endif; ?>
	</p>

</div>
