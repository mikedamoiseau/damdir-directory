<?php
/**
 * Star Input Template.
 *
 * Renders an interactive star rating input for review forms.
 * Uses radio buttons for accessibility with visual star display.
 *
 * This template can be overridden by copying it to:
 * yourtheme/all-purpose-directory/review/star-input.php
 *
 * @package APD\Templates
 * @since   1.0.0
 *
 * @var int $selected_rating Currently selected rating (0 if none).
 * @var int $star_count      Number of stars to display.
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$selected_rating = $selected_rating ?? 0;
$star_count      = $star_count ?? 5;
?>

<div class="apd-star-input"
	role="radiogroup"
	aria-labelledby="apd-rating-label"
	aria-describedby="apd-rating-instructions"
	data-selected="<?php echo absint( $selected_rating ); ?>">

	<div class="apd-star-input__stars" aria-hidden="true">
		<?php for ( $i = 1; $i <= $star_count; $i++ ) : ?>
			<span class="apd-star-input__star<?php echo $i <= $selected_rating ? ' apd-star-input__star--active' : ''; ?>"
				data-value="<?php echo absint( $i ); ?>"
				tabindex="-1">
				<span class="apd-star-input__star-icon"></span>
			</span>
		<?php endfor; ?>
	</div>

	<div class="apd-star-input__radios">
		<?php for ( $i = 1; $i <= $star_count; $i++ ) : ?>
			<?php
			$star_label = $i === 1
				/* translators: %d: number of stars (singular) */
				? sprintf( __( '%d star', 'all-purpose-directory' ), $i )
				/* translators: %d: number of stars (plural) */
				: sprintf( __( '%d stars', 'all-purpose-directory' ), $i );
			?>
			<label class="apd-star-input__radio-label">
				<input type="radio"
					name="rating"
					value="<?php echo absint( $i ); ?>"
					class="apd-star-input__radio"
					<?php checked( $selected_rating, $i ); ?>
					required
					aria-label="<?php echo esc_attr( $star_label ); ?>">
				<span class="apd-star-input__radio-text"><?php echo esc_html( $star_label ); ?></span>
			</label>
		<?php endfor; ?>
	</div>

	<div class="apd-star-input__label" aria-live="polite">
		<?php if ( $selected_rating > 0 ) : ?>
			<span class="apd-star-input__selected-text">
				<?php
				echo esc_html(
					$selected_rating === 1
					/* translators: %d: number of stars (singular) */
					? sprintf( __( '%d star selected', 'all-purpose-directory' ), $selected_rating )
					/* translators: %d: number of stars (plural) */
					: sprintf( __( '%d stars selected', 'all-purpose-directory' ), $selected_rating )
				);
				?>
			</span>
		<?php else : ?>
			<span class="apd-star-input__prompt-text">
				<?php esc_html_e( 'Select a rating', 'all-purpose-directory' ); ?>
			</span>
		<?php endif; ?>
	</div>

</div>
