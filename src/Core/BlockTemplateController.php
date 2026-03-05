<?php
/**
 * Block Template Controller.
 *
 * Registers block templates for listing archives and taxonomy archives
 * so block themes (FSE) display the plugin's styled card grid instead
 * of the theme's default post list.
 *
 * Requires WordPress 6.7+ (register_block_template API).
 *
 * @package APD\Core
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Core;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BlockTemplateController
 *
 * @since 1.0.0
 */
final class BlockTemplateController {

	/**
	 * Plugin slug used as the template namespace.
	 *
	 * @var string
	 */
	private const PLUGIN_SLUG = 'all-purpose-directory';

	/**
	 * Initialize the controller.
	 *
	 * Hooks template registration for block themes on WP 6.7+.
	 * Theme and API checks are deferred to the callback so they run
	 * at the correct WordPress lifecycle point (after post types at
	 * priority 5 and shortcodes at priority 20).
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'init', [ $this, 'register_templates' ], 25 );
	}

	/**
	 * Register block templates for listing archives and taxonomy archives.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_templates(): void {
		if ( ! function_exists( 'wp_is_block_theme' ) || ! wp_is_block_theme() ) {
			return;
		}

		if ( ! function_exists( 'register_block_template' ) ) {
			return;
		}
		$template_content = $this->get_template_content();

		register_block_template(
			self::PLUGIN_SLUG . '//archive-apd_listing',
			[
				'title'       => __( 'Listing Archive', 'all-purpose-directory' ),
				'description' => __( 'Displays the listing archive with search, filters, and card grid.', 'all-purpose-directory' ),
				'content'     => $template_content,
			]
		);

		register_block_template(
			self::PLUGIN_SLUG . '//taxonomy-apd_category',
			[
				'title'       => __( 'Listing Category Archive', 'all-purpose-directory' ),
				'description' => __( 'Displays listings in a category with search, filters, and card grid.', 'all-purpose-directory' ),
				'content'     => $template_content,
			]
		);

		register_block_template(
			self::PLUGIN_SLUG . '//taxonomy-apd_tag',
			[
				'title'       => __( 'Listing Tag Archive', 'all-purpose-directory' ),
				'description' => __( 'Displays listings with a tag with search, filters, and card grid.', 'all-purpose-directory' ),
				'content'     => $template_content,
			]
		);
	}

	/**
	 * Get the block template content.
	 *
	 * Uses the theme's header/footer template parts with the archive content
	 * shortcode in a constrained main group.
	 *
	 * @since 1.0.0
	 *
	 * @return string Block template markup.
	 */
	private function get_template_content(): string {
		return '<!-- wp:template-part {"slug":"header","tagName":"header"} /-->'
			. '<!-- wp:group {"tagName":"main","layout":{"type":"constrained"}} -->'
			. '<main class="wp-block-group">'
			. '<!-- wp:shortcode -->[apd_archive_content]<!-- /wp:shortcode -->'
			. '</main>'
			. '<!-- /wp:group -->'
			. '<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->';
	}
}
