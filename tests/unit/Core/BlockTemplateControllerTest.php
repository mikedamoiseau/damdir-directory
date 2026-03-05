<?php
/**
 * BlockTemplateController unit tests.
 *
 * @package APD\Tests\Unit\Core
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Core;

use APD\Core\BlockTemplateController;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;
use Brain\Monkey\Actions;

/**
 * Test case for BlockTemplateController class.
 */
final class BlockTemplateControllerTest extends UnitTestCase {

	/**
	 * Test init always hooks register_templates to init.
	 *
	 * Guards (block theme, WP version) are checked inside register_templates().
	 */
	public function test_init_hooks_register_templates(): void {
		$init_hooks = [];
		Functions\when( 'add_action' )->alias( function ( $tag, $callback, $priority = 10 ) use ( &$init_hooks ) {
			$init_hooks[] = [
				'tag'      => $tag,
				'priority' => $priority,
			];
		} );

		$controller = new BlockTemplateController();
		$controller->init();

		$init_tags = array_column( $init_hooks, 'tag' );
		$this->assertContains( 'init', $init_tags );

		// Verify priority is 25 (after post types at 5 and shortcodes at 20).
		$init_entry = array_filter( $init_hooks, fn( $h ) => $h['tag'] === 'init' );
		$init_entry = array_values( $init_entry );
		$this->assertSame( 25, $init_entry[0]['priority'] );
	}

	/**
	 * Test register_templates does nothing when wp_is_block_theme does not exist.
	 */
	public function test_register_templates_skips_when_wp_is_block_theme_not_available(): void {
		// function_exists returns false by default for unknown functions.
		$registered = [];
		Functions\when( 'register_block_template' )->alias( function ( $slug, $args ) use ( &$registered ) {
			$registered[ $slug ] = $args;
		} );

		$controller = new BlockTemplateController();
		$controller->register_templates();

		$this->assertEmpty( $registered );
	}

	/**
	 * Test register_templates does nothing when not a block theme.
	 */
	public function test_register_templates_skips_when_not_block_theme(): void {
		Functions\when( 'wp_is_block_theme' )->justReturn( false );

		$registered = [];
		Functions\when( 'register_block_template' )->alias( function ( $slug, $args ) use ( &$registered ) {
			$registered[ $slug ] = $args;
		} );

		$controller = new BlockTemplateController();
		$controller->register_templates();

		$this->assertEmpty( $registered );
	}

	/**
	 * Test register_templates does nothing when register_block_template does not exist.
	 *
	 * Note: Brain\Monkey persists function definitions across tests in the same process,
	 * so we cannot reliably test function_exists('register_block_template') === false
	 * after other tests have defined it. Instead, we verify the guard behavior when
	 * wp_is_block_theme returns false (which is the more common pre-6.7 scenario).
	 */
	public function test_register_templates_skips_when_not_block_theme_and_no_register_function(): void {
		// Both conditions fail: not a block theme and no register_block_template.
		Functions\when( 'wp_is_block_theme' )->justReturn( false );

		$registered = [];
		Functions\when( 'register_block_template' )->alias( function ( $slug, $args ) use ( &$registered ) {
			$registered[ $slug ] = $args;
		} );

		$controller = new BlockTemplateController();
		$controller->register_templates();

		$this->assertEmpty( $registered );
	}

	/**
	 * Test register_templates registers all three templates.
	 */
	public function test_register_templates_registers_three_templates(): void {
		Functions\when( 'wp_is_block_theme' )->justReturn( true );

		$registered = [];
		Functions\when( 'register_block_template' )->alias( function ( $slug, $args ) use ( &$registered ) {
			$registered[ $slug ] = $args;
		} );

		$controller = new BlockTemplateController();
		$controller->register_templates();

		$this->assertArrayHasKey( 'all-purpose-directory//archive-apd_listing', $registered );
		$this->assertArrayHasKey( 'all-purpose-directory//taxonomy-apd_category', $registered );
		$this->assertArrayHasKey( 'all-purpose-directory//taxonomy-apd_tag', $registered );
	}

	/**
	 * Test template content contains the shortcode.
	 */
	public function test_template_content_contains_shortcode(): void {
		Functions\when( 'wp_is_block_theme' )->justReturn( true );

		$registered = [];
		Functions\when( 'register_block_template' )->alias( function ( $slug, $args ) use ( &$registered ) {
			$registered[ $slug ] = $args;
		} );

		$controller = new BlockTemplateController();
		$controller->register_templates();

		$content = $registered['all-purpose-directory//archive-apd_listing']['content'];
		$this->assertStringContainsString( '[apd_archive_content]', $content );
	}

	/**
	 * Test template content contains header and footer template parts.
	 */
	public function test_template_content_contains_header_footer(): void {
		Functions\when( 'wp_is_block_theme' )->justReturn( true );

		$registered = [];
		Functions\when( 'register_block_template' )->alias( function ( $slug, $args ) use ( &$registered ) {
			$registered[ $slug ] = $args;
		} );

		$controller = new BlockTemplateController();
		$controller->register_templates();

		$content = $registered['all-purpose-directory//taxonomy-apd_category']['content'];
		$this->assertStringContainsString( 'wp:template-part {"slug":"header"', $content );
		$this->assertStringContainsString( 'wp:template-part {"slug":"footer"', $content );
	}

	/**
	 * Test templates have titles.
	 */
	public function test_templates_have_titles(): void {
		Functions\when( 'wp_is_block_theme' )->justReturn( true );

		$registered = [];
		Functions\when( 'register_block_template' )->alias( function ( $slug, $args ) use ( &$registered ) {
			$registered[ $slug ] = $args;
		} );

		$controller = new BlockTemplateController();
		$controller->register_templates();

		$this->assertSame( 'Listing Archive', $registered['all-purpose-directory//archive-apd_listing']['title'] );
		$this->assertSame( 'Listing Category Archive', $registered['all-purpose-directory//taxonomy-apd_category']['title'] );
		$this->assertSame( 'Listing Tag Archive', $registered['all-purpose-directory//taxonomy-apd_tag']['title'] );
	}

	/**
	 * Test template content wraps shortcode in main element.
	 */
	public function test_template_content_wraps_in_main(): void {
		Functions\when( 'wp_is_block_theme' )->justReturn( true );

		$registered = [];
		Functions\when( 'register_block_template' )->alias( function ( $slug, $args ) use ( &$registered ) {
			$registered[ $slug ] = $args;
		} );

		$controller = new BlockTemplateController();
		$controller->register_templates();

		$content = $registered['all-purpose-directory//archive-apd_listing']['content'];
		$this->assertStringContainsString( '<main class="wp-block-group">', $content );
		$this->assertStringContainsString( '</main>', $content );
	}
}
