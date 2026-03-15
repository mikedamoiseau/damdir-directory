<?php
/**
 * Field Group Template.
 *
 * Template for rendering a field group/section in the submission form.
 *
 * This template can be overridden by copying it to:
 * yourtheme/all-purpose-directory/submission/field-group.php
 *
 * @package APD\Templates
 * @since   1.0.0
 *
 * @var string                   $group_id    Group identifier.
 * @var array<string, mixed>     $group       Group configuration.
 * @var string                   $fields_html Rendered fields HTML.
 * @var APD\Fields\FieldRenderer $renderer    Field renderer instance.
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$is_collapsible = ! empty( $group['collapsible'] );
$is_collapsed   = ! empty( $group['collapsed'] );

$wrapper_class = 'apd-submission-form__group';
if ( $is_collapsible ) {
	$wrapper_class .= ' apd-submission-form__group--collapsible';
	if ( $is_collapsed ) {
		$wrapper_class .= ' apd-submission-form__group--collapsed';
	}
}
?>

<div class="<?php echo esc_attr( $wrapper_class ); ?>" data-group-id="<?php echo esc_attr( $group_id ); ?>">
	<?php if ( ! empty( $group['title'] ) ) : ?>
		<div class="apd-submission-form__group-header">
			<?php if ( $is_collapsible ) : ?>
				<button type="button"
					class="apd-submission-form__group-toggle"
					aria-expanded="<?php echo $is_collapsed ? 'false' : 'true'; ?>"
					aria-controls="apd-group-<?php echo esc_attr( $group_id ); ?>-body">
					<span class="apd-submission-form__group-title">
						<?php echo esc_html( $group['title'] ); ?>
					</span>
					<span class="apd-submission-form__group-indicator" aria-hidden="true"></span>
				</button>
			<?php else : ?>
				<h3 class="apd-submission-form__group-title">
					<?php echo esc_html( $group['title'] ); ?>
				</h3>
			<?php endif; ?>

			<?php if ( ! empty( $group['description'] ) ) : ?>
				<p class="apd-submission-form__group-description">
					<?php echo esc_html( $group['description'] ); ?>
				</p>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<div class="apd-submission-form__group-body"
		id="apd-group-<?php echo esc_attr( $group_id ); ?>-body"
		<?php echo $is_collapsible && $is_collapsed ? 'hidden' : ''; ?>>
		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Contains pre-escaped field HTML.
		echo $fields_html;
		?>
	</div>
</div>
