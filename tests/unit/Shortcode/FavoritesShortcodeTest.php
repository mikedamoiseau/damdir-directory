<?php
/**
 * FavoritesShortcode Unit Tests.
 *
 * @package APD\Tests\Unit\Shortcode
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Shortcode;

use APD\Contracts\ViewInterface;
use APD\Shortcode\FavoritesShortcode;
use APD\Frontend\Display\ViewRegistry;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;
use Brain\Monkey\Filters;

/**
 * Stub view for testing.
 *
 * Non-final so ViewRegistry::create_view() can instantiate it.
 */
class StubView implements ViewInterface {

	private array $config;

	public function __construct( array $config = [] ) {
		$this->config = $config;
	}

	public function getType(): string {
		return 'grid';
	}

	public function getLabel(): string {
		return 'Grid';
	}

	public function getIcon(): string {
		return 'dashicons-grid-view';
	}

	public function getTemplate(): string {
		return 'listing-card';
	}

	public function getConfig(): array {
		return $this->config;
	}

	public function setConfig( array $config ): self {
		$this->config = $config;
		return $this;
	}

	public function renderListing( int $listing_id, array $args = [] ): string {
		return '<div class="apd-listing-card">Listing ' . $listing_id . '</div>';
	}

	public function renderListings( \WP_Query|array $listings, array $args = [] ): string {
		return '<div class="apd-listings apd-listings--grid">Rendered listings</div>';
	}

	public function getContainerClasses(): array {
		return [ 'apd-listings', 'apd-listings--grid' ];
	}

	public function getContainerAttributes(): array {
		return [ 'view' => 'grid' ];
	}

	public function supports( string $feature ): bool {
		return $feature === 'columns';
	}
}

/**
 * Test class for FavoritesShortcode.
 */
final class FavoritesShortcodeTest extends UnitTestCase {

	/**
	 * FavoritesShortcode instance.
	 *
	 * @var FavoritesShortcode
	 */
	private FavoritesShortcode $shortcode;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Mock admin settings helpers used in constructor.
		Functions\when( 'apd_get_default_view' )->justReturn( 'grid' );
		Functions\when( 'apd_get_default_grid_columns' )->justReturn( 3 );
		Functions\when( 'apd_get_listings_per_page' )->justReturn( 12 );
		Functions\when( 'apd_get_option' )->alias( function ( $key, $default = null ) {
			return $default;
		} );

		$this->shortcode = new FavoritesShortcode();

		// Reset ViewRegistry singleton.
		ViewRegistry::reset_instance();

		// Reset WP_Query test override.
		\WP_Query::_reset_test_override();
	}

	/**
	 * Set up the ViewRegistry with a stub view for render tests.
	 */
	private function set_up_view_registry(): void {
		$registry = ViewRegistry::get_instance();

		// Mark as initialized to skip core view registration (which needs more WP mocks).
		$reflection = new \ReflectionClass( $registry );
		$prop       = $reflection->getProperty( 'initialized' );
		$prop->setValue( $registry, true );

		// Register our stub view.
		$stub = new StubView();
		$registry->register_view( $stub );
	}

	/**
	 * Set up common mocks for render tests.
	 */
	private function set_up_render_mocks(): void {
		Functions\when( 'shortcode_atts' )->alias( function( $defaults, $atts ) {
			return array_merge( $defaults, is_array( $atts ) ? $atts : [] );
		} );
		Functions\when( 'wp_reset_postdata' )->justReturn( null );
	}

	// -------------------------------------------------------------------------
	// Metadata tests.
	// -------------------------------------------------------------------------

	/**
	 * Test shortcode tag is correct.
	 */
	public function test_tag_is_apd_favorites(): void {
		$this->assertSame( 'apd_favorites', $this->shortcode->get_tag() );
	}

	/**
	 * Test defaults are populated from admin settings.
	 */
	public function test_defaults_come_from_admin_settings(): void {
		$defaults = $this->shortcode->get_defaults();

		$this->assertSame( 'grid', $defaults['view'] );
		$this->assertSame( 3, $defaults['columns'] );
		$this->assertSame( 12, $defaults['count'] );
	}

	/**
	 * Test defaults include show_empty and empty_message.
	 */
	public function test_has_correct_defaults(): void {
		$defaults = $this->shortcode->get_defaults();

		$this->assertSame( 'true', $defaults['show_empty'] );
		$this->assertSame( '', $defaults['empty_message'] );
		$this->assertSame( '', $defaults['class'] );
	}

	/**
	 * Test defaults reflect custom admin settings.
	 */
	public function test_defaults_reflect_custom_admin_settings(): void {
		Functions\when( 'apd_get_default_view' )->justReturn( 'list' );
		Functions\when( 'apd_get_default_grid_columns' )->justReturn( 4 );
		Functions\when( 'apd_get_listings_per_page' )->justReturn( 24 );

		$shortcode = new FavoritesShortcode();
		$defaults  = $shortcode->get_defaults();

		$this->assertSame( 'list', $defaults['view'] );
		$this->assertSame( 4, $defaults['columns'] );
		$this->assertSame( 24, $defaults['count'] );
	}

	/**
	 * Test attribute documentation exists for all attributes.
	 */
	public function test_attribute_docs_exist(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertArrayHasKey( 'view', $docs );
		$this->assertArrayHasKey( 'columns', $docs );
		$this->assertArrayHasKey( 'count', $docs );
		$this->assertArrayHasKey( 'show_empty', $docs );
		$this->assertArrayHasKey( 'empty_message', $docs );
		$this->assertArrayHasKey( 'class', $docs );
	}

	/**
	 * Test view attribute is slug type.
	 */
	public function test_view_is_slug_type(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertSame( 'slug', $docs['view']['type'] );
	}

	/**
	 * Test columns attribute is integer type.
	 */
	public function test_columns_is_integer_type(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertSame( 'integer', $docs['columns']['type'] );
	}

	/**
	 * Test count attribute is integer type.
	 */
	public function test_count_is_integer_type(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertSame( 'integer', $docs['count']['type'] );
	}

	/**
	 * Test show_empty attribute is boolean type.
	 */
	public function test_show_empty_is_boolean_type(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertSame( 'boolean', $docs['show_empty']['type'] );
	}

	/**
	 * Test empty_message attribute is string type.
	 */
	public function test_empty_message_is_string_type(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertSame( 'string', $docs['empty_message']['type'] );
	}

	/**
	 * Test example is valid.
	 */
	public function test_example_is_valid(): void {
		$example = $this->shortcode->get_example();

		$this->assertStringContainsString( '[apd_favorites', $example );
		$this->assertStringContainsString( 'view=', $example );
	}

	/**
	 * Test description is set.
	 */
	public function test_description_is_set(): void {
		$this->assertNotEmpty( $this->shortcode->get_description() );
	}

	// -------------------------------------------------------------------------
	// Sanitization tests.
	// -------------------------------------------------------------------------

	/**
	 * Test sanitize_attributes clamps count to max 100.
	 */
	public function test_count_clamped_to_max_100(): void {
		$method = new \ReflectionMethod( $this->shortcode, 'sanitize_attributes' );

		$result = $method->invoke( $this->shortcode, [
			'view'          => 'grid',
			'columns'       => 3,
			'count'         => 999,
			'show_empty'    => true,
			'empty_message' => '',
			'class'         => '',
		] );

		$this->assertSame( 100, $result['count'] );
	}

	/**
	 * Test sanitize_attributes clamps count to min 1.
	 */
	public function test_count_clamped_to_min_1(): void {
		$method = new \ReflectionMethod( $this->shortcode, 'sanitize_attributes' );

		$result = $method->invoke( $this->shortcode, [
			'view'          => 'grid',
			'columns'       => 3,
			'count'         => 0,
			'show_empty'    => true,
			'empty_message' => '',
			'class'         => '',
		] );

		$this->assertSame( 1, $result['count'] );
	}

	/**
	 * Test sanitize_attributes clamps columns to max 4.
	 */
	public function test_columns_clamped_to_max_4(): void {
		$method = new \ReflectionMethod( $this->shortcode, 'sanitize_attributes' );

		$result = $method->invoke( $this->shortcode, [
			'view'          => 'grid',
			'columns'       => 99,
			'count'         => 12,
			'show_empty'    => true,
			'empty_message' => '',
			'class'         => '',
		] );

		$this->assertSame( 4, $result['columns'] );
	}

	/**
	 * Test sanitize_attributes clamps columns to min 2.
	 */
	public function test_columns_clamped_to_min_2(): void {
		$method = new \ReflectionMethod( $this->shortcode, 'sanitize_attributes' );

		$result = $method->invoke( $this->shortcode, [
			'view'          => 'grid',
			'columns'       => 1,
			'count'         => 12,
			'show_empty'    => true,
			'empty_message' => '',
			'class'         => '',
		] );

		$this->assertSame( 2, $result['columns'] );
	}

	// -------------------------------------------------------------------------
	// Render tests.
	// -------------------------------------------------------------------------

	/**
	 * Test output requires login.
	 */
	public function test_output_requires_login(): void {
		Functions\when( 'is_user_logged_in' )->justReturn( false );
		Functions\when( 'shortcode_atts' )->alias( function( $defaults, $atts ) {
			return array_merge( $defaults, is_array( $atts ) ? $atts : [] );
		} );
		Functions\when( 'wp_login_url' )->justReturn( 'https://example.com/login' );
		Functions\when( 'get_permalink' )->justReturn( 'https://example.com/page/' );

		$result = $this->shortcode->render( [] );

		$this->assertStringContainsString( 'apd-login-required', $result );
		$this->assertStringContainsString( 'Please log in to view your favorites.', $result );
	}

	/**
	 * Test output shows empty message when user has no favorites.
	 */
	public function test_output_shows_empty_message_when_no_favorites(): void {
		Functions\when( 'is_user_logged_in' )->justReturn( true );
		$this->set_up_render_mocks();
		$this->set_up_view_registry();
		Functions\when( 'apd_get_user_favorites' )->justReturn( [] );

		// Bootstrap WP_Query returns have_posts() = false by default.
		$result = $this->shortcode->render( [] );

		$this->assertStringContainsString( 'apd-no-results', $result );
		$this->assertStringContainsString( 'You have no favorite listings yet.', $result );
	}

	/**
	 * Test output hides empty message when show_empty is false.
	 */
	public function test_output_hides_empty_message_when_show_empty_false(): void {
		Functions\when( 'is_user_logged_in' )->justReturn( true );
		$this->set_up_render_mocks();
		$this->set_up_view_registry();
		Functions\when( 'apd_get_user_favorites' )->justReturn( [] );

		$result = $this->shortcode->render( [ 'show_empty' => 'false' ] );

		$this->assertStringNotContainsString( 'apd-no-results', $result );
		$this->assertStringNotContainsString( 'You have no favorite listings yet.', $result );
	}

	/**
	 * Test output shows custom empty message.
	 */
	public function test_output_shows_custom_empty_message(): void {
		Functions\when( 'is_user_logged_in' )->justReturn( true );
		$this->set_up_render_mocks();
		$this->set_up_view_registry();
		Functions\when( 'apd_get_user_favorites' )->justReturn( [] );

		$result = $this->shortcode->render( [ 'empty_message' => 'No favorites here!' ] );

		$this->assertStringContainsString( 'No favorites here!', $result );
	}

	/**
	 * Test output renders listings when user has favorites.
	 */
	public function test_output_renders_listings_with_favorites(): void {
		Functions\when( 'is_user_logged_in' )->justReturn( true );
		$this->set_up_render_mocks();
		$this->set_up_view_registry();
		Functions\when( 'apd_get_user_favorites' )->justReturn( [ 10, 20, 30 ] );

		// Pre-configure WP_Query to return posts.
		\WP_Query::_set_test_override( [
			'posts'         => [ (object) [ 'ID' => 10 ], (object) [ 'ID' => 20 ], (object) [ 'ID' => 30 ] ],
			'max_num_pages' => 1,
		] );

		$result = $this->shortcode->render( [] );

		$this->assertStringContainsString( 'apd-favorites-shortcode', $result );
		$this->assertStringContainsString( 'Rendered listings', $result );
		$this->assertStringNotContainsString( 'apd-no-results', $result );
		$this->assertStringNotContainsString( 'coming soon', $result );
	}

	/**
	 * Test filter override chain is preserved.
	 *
	 * When apd_favorites_enabled returns true, output should come from
	 * apd_favorites_output filter instead of default rendering.
	 */
	public function test_filter_override_returns_custom_output(): void {
		Functions\when( 'is_user_logged_in' )->justReturn( true );
		$this->set_up_render_mocks();

		// Hook the enabled filter to return true.
		Filters\expectApplied( 'apd_favorites_enabled' )
			->once()
			->andReturn( true );

		// Hook the output filter to return custom content.
		Filters\expectApplied( 'apd_favorites_output' )
			->once()
			->andReturn( '<div class="custom-favorites">Custom module output</div>' );

		$result = $this->shortcode->render( [] );

		$this->assertStringContainsString( 'Custom module output', $result );
	}

	/**
	 * Test container has custom CSS class when provided.
	 */
	public function test_output_includes_custom_css_class(): void {
		Functions\when( 'is_user_logged_in' )->justReturn( true );
		$this->set_up_render_mocks();
		$this->set_up_view_registry();
		Functions\when( 'apd_get_user_favorites' )->justReturn( [ 10 ] );

		\WP_Query::_set_test_override( [
			'posts'         => [ (object) [ 'ID' => 10 ] ],
			'max_num_pages' => 1,
		] );

		$result = $this->shortcode->render( [ 'class' => 'my-custom-class' ] );

		$this->assertStringContainsString( 'my-custom-class', $result );
	}

	/**
	 * Test pagination renders when multiple pages exist.
	 */
	public function test_output_renders_pagination_when_multiple_pages(): void {
		Functions\when( 'is_user_logged_in' )->justReturn( true );
		$this->set_up_render_mocks();
		$this->set_up_view_registry();
		Functions\when( 'apd_get_user_favorites' )->justReturn( array_map( 'intval', range( 1, 30 ) ) );
		Functions\when( 'add_query_arg' )->alias( function( $key, $value ) {
			return "https://example.com/?{$key}={$value}";
		} );
		Functions\when( 'paginate_links' )->justReturn(
			'<span class="page-numbers current">1</span><a class="page-numbers" href="?fav_page=2">2</a>'
		);

		\WP_Query::_set_test_override( [
			'posts'         => [ (object) [ 'ID' => 1 ], (object) [ 'ID' => 2 ] ],
			'max_num_pages' => 3,
		] );

		$result = $this->shortcode->render( [] );

		$this->assertStringContainsString( 'apd-pagination', $result );
		$this->assertStringContainsString( 'Favorites pagination', $result );
	}

	/**
	 * Test no pagination when only one page.
	 */
	public function test_output_no_pagination_when_single_page(): void {
		Functions\when( 'is_user_logged_in' )->justReturn( true );
		$this->set_up_render_mocks();
		$this->set_up_view_registry();
		Functions\when( 'apd_get_user_favorites' )->justReturn( [ 10, 20 ] );

		\WP_Query::_set_test_override( [
			'posts'         => [ (object) [ 'ID' => 10 ], (object) [ 'ID' => 20 ] ],
			'max_num_pages' => 1,
		] );

		$result = $this->shortcode->render( [] );

		$this->assertStringNotContainsString( 'apd-pagination', $result );
	}

	/**
	 * Test output does not contain "coming soon" when rendering normally.
	 */
	public function test_output_never_shows_coming_soon(): void {
		Functions\when( 'is_user_logged_in' )->justReturn( true );
		$this->set_up_render_mocks();
		$this->set_up_view_registry();
		Functions\when( 'apd_get_user_favorites' )->justReturn( [] );

		// Bootstrap WP_Query returns have_posts() = false by default.
		$result = $this->shortcode->render( [] );

		$this->assertStringNotContainsString( 'coming soon', strtolower( $result ) );
	}
}
