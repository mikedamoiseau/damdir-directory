<?php
/**
 * ShortcodeManager Unit Tests.
 *
 * @package APD\Tests\Unit\Shortcode
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Shortcode;

use APD\Shortcode\ShortcodeManager;
use APD\Shortcode\AbstractShortcode;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test class for ShortcodeManager.
 */
final class ShortcodeManagerTest extends UnitTestCase {

	/**
	 * Reset singleton between tests.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Reset singleton using reflection.
		$reflection = new \ReflectionClass( ShortcodeManager::class );
		$instance   = $reflection->getProperty( 'instance' );
		$instance->setValue( null, null );

		// Also reset initialized flag.
		$initialized = $reflection->getProperty( 'initialized' );
		// We'll need to access after getting instance.
	}

	/**
	 * Test get_instance returns singleton.
	 */
	public function test_get_instance_returns_singleton(): void {
		$instance1 = ShortcodeManager::get_instance();
		$instance2 = ShortcodeManager::get_instance();

		$this->assertSame( $instance1, $instance2 );
	}

	/**
	 * Test get_instance returns ShortcodeManager.
	 */
	public function test_get_instance_returns_shortcode_manager(): void {
		$manager = ShortcodeManager::get_instance();

		$this->assertInstanceOf( ShortcodeManager::class, $manager );
	}

	/**
	 * Test register adds shortcode.
	 */
	public function test_register_adds_shortcode(): void {
		Functions\when( 'add_shortcode' )->justReturn( null );

		$manager   = ShortcodeManager::get_instance();
		$shortcode = $this->createMockShortcode( 'test_shortcode' );

		$result = $manager->register( $shortcode );

		$this->assertTrue( $result );
		$this->assertTrue( $manager->has( 'test_shortcode' ) );
	}

	/**
	 * Test register returns false for empty tag.
	 */
	public function test_register_returns_false_for_empty_tag(): void {
		$manager   = ShortcodeManager::get_instance();
		$shortcode = $this->createMockShortcode( '' );

		$result = $manager->register( $shortcode );

		$this->assertFalse( $result );
	}

	/**
	 * Test unregister removes shortcode.
	 */
	public function test_unregister_removes_shortcode(): void {
		Functions\when( 'add_shortcode' )->justReturn( null );
		Functions\when( 'remove_shortcode' )->justReturn( null );

		$manager   = ShortcodeManager::get_instance();
		$shortcode = $this->createMockShortcode( 'test_shortcode' );
		$manager->register( $shortcode );

		$result = $manager->unregister( 'test_shortcode' );

		$this->assertTrue( $result );
		$this->assertFalse( $manager->has( 'test_shortcode' ) );
	}

	/**
	 * Test unregister returns false for non-existent shortcode.
	 */
	public function test_unregister_returns_false_for_nonexistent(): void {
		$manager = ShortcodeManager::get_instance();

		$result = $manager->unregister( 'nonexistent' );

		$this->assertFalse( $result );
	}

	/**
	 * Test get returns registered shortcode.
	 */
	public function test_get_returns_registered_shortcode(): void {
		Functions\when( 'add_shortcode' )->justReturn( null );

		$manager   = ShortcodeManager::get_instance();
		$shortcode = $this->createMockShortcode( 'test_shortcode' );
		$manager->register( $shortcode );

		$retrieved = $manager->get( 'test_shortcode' );

		$this->assertSame( $shortcode, $retrieved );
	}

	/**
	 * Test get returns null for non-existent shortcode.
	 */
	public function test_get_returns_null_for_nonexistent(): void {
		$manager = ShortcodeManager::get_instance();

		$result = $manager->get( 'nonexistent' );

		$this->assertNull( $result );
	}

	/**
	 * Test has returns true for registered shortcode.
	 */
	public function test_has_returns_true_for_registered(): void {
		Functions\when( 'add_shortcode' )->justReturn( null );

		$manager   = ShortcodeManager::get_instance();
		$shortcode = $this->createMockShortcode( 'test_shortcode' );
		$manager->register( $shortcode );

		$this->assertTrue( $manager->has( 'test_shortcode' ) );
	}

	/**
	 * Test has returns false for non-existent shortcode.
	 */
	public function test_has_returns_false_for_nonexistent(): void {
		$manager = ShortcodeManager::get_instance();

		$this->assertFalse( $manager->has( 'nonexistent' ) );
	}

	/**
	 * Test get_all returns all registered shortcodes.
	 */
	public function test_get_all_returns_all_shortcodes(): void {
		Functions\when( 'add_shortcode' )->justReturn( null );

		$manager    = ShortcodeManager::get_instance();
		$shortcode1 = $this->createMockShortcode( 'shortcode_1' );
		$shortcode2 = $this->createMockShortcode( 'shortcode_2' );

		$manager->register( $shortcode1 );
		$manager->register( $shortcode2 );

		$all = $manager->get_all();

		$this->assertCount( 2, $all );
		$this->assertArrayHasKey( 'shortcode_1', $all );
		$this->assertArrayHasKey( 'shortcode_2', $all );
	}

	/**
	 * Test get_documentation returns docs for all shortcodes.
	 */
	public function test_get_documentation_returns_docs(): void {
		Functions\when( 'add_shortcode' )->justReturn( null );

		$manager   = ShortcodeManager::get_instance();
		$shortcode = $this->createMockShortcode( 'test_shortcode', 'Test description' );
		$manager->register( $shortcode );

		$docs = $manager->get_documentation();

		$this->assertArrayHasKey( 'test_shortcode', $docs );
		$this->assertSame( 'test_shortcode', $docs['test_shortcode']['tag'] );
		$this->assertSame( 'Test description', $docs['test_shortcode']['description'] );
	}

	/**
	 * Test render_archive_content_shortcode returns empty on non-archive pages.
	 */
	public function test_render_archive_content_shortcode_returns_empty_on_non_archive(): void {
		Functions\when( 'is_post_type_archive' )->justReturn( false );
		Functions\when( 'is_tax' )->justReturn( false );

		$manager = ShortcodeManager::get_instance();

		$this->assertSame( '', $manager->render_archive_content_shortcode() );
	}

	/**
	 * Test render_archive_content_shortcode returns content on post type archive.
	 */
	public function test_render_archive_content_shortcode_returns_content_on_archive(): void {
		Functions\when( 'is_post_type_archive' )->alias( function ( $type ) {
			return $type === 'apd_listing';
		} );
		Functions\when( 'is_tax' )->justReturn( false );
		Functions\when( 'apd_get_option' )->justReturn( 'grid' );
		Functions\when( 'wp_kses_post' )->returnArg();
		Functions\when( 'apd_render_search_form' )->justReturn( '<form></form>' );
		Functions\when( 'apd_render_active_filters' )->justReturn( '' );
		Functions\when( 'apd_render_no_results' )->justReturn( '<p>No results</p>' );
		Functions\when( 'have_posts' )->justReturn( false );
		Functions\when( 'paginate_links' )->justReturn( '' );
		Functions\when( 'get_post_type_object' )->justReturn( (object) [
			'labels'      => (object) [ 'name' => 'Listings' ],
			'description' => '',
		] );
		Functions\when( 'post_type_archive_title' )->justReturn( 'Listings' );
		Functions\when( 'get_post_type_archive_link' )->justReturn( 'https://example.com/listings/' );
		Functions\when( 'home_url' )->justReturn( 'https://example.com' );
		Functions\when( 'add_query_arg' )->justReturn( 'https://example.com/listings/' );

		// Set up global wp_query (WP_Query defined in bootstrap.php).
		$GLOBALS['wp_query'] = new class extends \WP_Query {
			public int $found_posts  = 0;
			public int $max_num_pages = 0;
		};

		$manager = ShortcodeManager::get_instance();
		$output  = $manager->render_archive_content_shortcode();

		$this->assertStringContainsString( 'apd-archive-wrapper', $output );

		unset( $GLOBALS['wp_query'] );
	}

	/**
	 * Test render_archive_content_shortcode returns content on category archive.
	 */
	public function test_render_archive_content_shortcode_returns_content_on_category_archive(): void {
		Functions\when( 'is_post_type_archive' )->justReturn( false );
		Functions\when( 'is_tax' )->alias( function ( $taxonomy = '' ) {
			return $taxonomy === 'apd_category';
		} );
		Functions\when( 'apd_get_option' )->justReturn( 'grid' );
		Functions\when( 'wp_kses_post' )->returnArg();
		Functions\when( 'apd_render_search_form' )->justReturn( '<form></form>' );
		Functions\when( 'apd_render_active_filters' )->justReturn( '' );
		Functions\when( 'apd_render_no_results' )->justReturn( '<p>No results</p>' );
		Functions\when( 'have_posts' )->justReturn( false );
		Functions\when( 'paginate_links' )->justReturn( '' );
		Functions\when( 'single_term_title' )->justReturn( 'Entertainment' );
		Functions\when( 'term_description' )->justReturn( '' );
		Functions\when( 'home_url' )->justReturn( 'https://example.com' );
		Functions\when( 'add_query_arg' )->justReturn( 'https://example.com/listing-category/entertainment/' );

		// Set up global wp_query (WP_Query defined in bootstrap.php).
		$GLOBALS['wp_query'] = new class extends \WP_Query {
			public int $found_posts  = 0;
			public int $max_num_pages = 0;
		};

		$manager = ShortcodeManager::get_instance();
		$output  = $manager->render_archive_content_shortcode();

		$this->assertStringContainsString( 'apd-archive-wrapper', $output );

		unset( $GLOBALS['wp_query'] );
	}

	/**
	 * Create a mock shortcode for testing.
	 *
	 * @param string $tag         Shortcode tag.
	 * @param string $description Shortcode description.
	 * @return AbstractShortcode Mock shortcode.
	 */
	private function createMockShortcode( string $tag, string $description = '' ): AbstractShortcode {
		$shortcode = Mockery::mock( AbstractShortcode::class );
		$shortcode->shouldReceive( 'get_tag' )->andReturn( $tag );
		$shortcode->shouldReceive( 'get_description' )->andReturn( $description );
		$shortcode->shouldReceive( 'get_attribute_docs' )->andReturn( [] );
		$shortcode->shouldReceive( 'get_example' )->andReturn( '[' . $tag . ']' );
		$shortcode->shouldReceive( 'render' )->andReturn( '' );

		return $shortcode;
	}
}
