<?php
/**
 * Search Form Template.
 *
 * Template for rendering the listing search form with filters.
 *
 * This template can be overridden by copying it to:
 * yourtheme/all-purpose-directory/search/search-form.php
 *
 * @package APD\Templates
 * @since   1.0.0
 *
 * @var array<string, mixed>           $args    Render arguments.
 * @var array<string, FilterInterface> $filters Filters to render.
 * @var array<string>                  $classes CSS classes.
 * @var array<string, mixed>           $request Request data.
 * @var FilterRenderer                 $this    Renderer instance.
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$action = $args['action'] ?: get_post_type_archive_link( 'apd_listing' );
?>
<form class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
	action="<?php echo esc_url( $action ); ?>"
	method="<?php echo esc_attr( $args['method'] ); ?>"
	data-ajax="<?php echo $args['ajax'] ? 'true' : 'false'; ?>"
	role="search"
	aria-label="<?php esc_attr_e( 'Search listings', 'all-purpose-directory' ); ?>">

	<?php
	/**
	 * Fires before the filters are rendered.
	 *
	 * @since 1.0.0
	 */
	do_action( 'apd_before_filters' );
	?>

	<div class="apd-search-form__filters">
		<?php
		foreach ( $filters as $filter ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $this->render_filter( $filter->getName(), $request );
		}
		?>
	</div>

	<?php
	/**
	 * Fires after the filters are rendered.
	 *
	 * @since 1.0.0
	 */
	do_action( 'apd_after_filters' );
	?>

	<?php if ( $args['show_orderby'] ) : ?>
		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->render_orderby( $request );
		?>
	<?php endif; ?>

	<?php if ( $args['show_submit'] ) : ?>
		<div class="apd-search-form__actions">
			<button type="submit" class="apd-search-form__submit">
				<?php
				$submit_label = ! empty( $args['submit_text'] )
					? sanitize_text_field( $args['submit_text'] )
					: __( 'Search', 'all-purpose-directory' );
				echo esc_html( $submit_label );
				?>
			</button>
			<a href="<?php echo esc_url( $action ); ?>" class="apd-search-form__clear">
				<?php esc_html_e( 'Clear Filters', 'all-purpose-directory' ); ?>
			</a>
		</div>
	<?php endif; ?>

</form>
