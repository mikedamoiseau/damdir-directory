<?php
/**
 * Documentation tests.
 *
 * @package APD\Tests\Unit\Documentation
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Documentation;

use PHPUnit\Framework\TestCase;

/**
 * Tests for documentation files.
 */
class DocumentationTest extends TestCase {

    /**
     * Plugin root directory.
     *
     * @var string
     */
    private string $plugin_dir;

    /**
     * Set up test fixtures.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->plugin_dir = dirname( __DIR__, 3 );
    }

    /**
     * Test README.txt exists.
     */
    public function test_readme_txt_exists(): void {
        $this->assertFileExists( $this->plugin_dir . '/README.txt' );
    }

    /**
     * Test README.txt has required WordPress.org sections.
     */
    public function test_readme_txt_has_required_sections(): void {
        $readme = file_get_contents( $this->plugin_dir . '/README.txt' );

        // Required header
        $this->assertStringContainsString( '=== All Purpose Directory ===', $readme );

        // Required metadata
        $this->assertStringContainsString( 'Contributors:', $readme );
        $this->assertStringContainsString( 'Tags:', $readme );
        $this->assertStringContainsString( 'Requires at least:', $readme );
        $this->assertStringContainsString( 'Tested up to:', $readme );
        $this->assertStringContainsString( 'Requires PHP:', $readme );
        $this->assertStringContainsString( 'Stable tag:', $readme );
        $this->assertStringContainsString( 'License:', $readme );

        // Required sections
        $this->assertStringContainsString( '== Description ==', $readme );
        $this->assertStringContainsString( '== Installation ==', $readme );
        $this->assertStringContainsString( '== Frequently Asked Questions ==', $readme );
        $this->assertStringContainsString( '== Screenshots ==', $readme );
        $this->assertStringContainsString( '== Changelog ==', $readme );
        $this->assertStringContainsString( '== Upgrade Notice ==', $readme );
    }

    /**
     * Test README.txt has correct PHP version requirement.
     */
    public function test_readme_txt_php_version(): void {
        $readme = file_get_contents( $this->plugin_dir . '/README.txt' );
        $this->assertStringContainsString( 'Requires PHP: 8.0', $readme );
    }

    /**
     * Test README.txt has correct WordPress version requirement.
     */
    public function test_readme_txt_wp_version(): void {
        $readme = file_get_contents( $this->plugin_dir . '/README.txt' );
        $this->assertStringContainsString( 'Requires at least: 6.0', $readme );
    }

    /**
     * Test README.txt documents all shortcodes.
     */
    public function test_readme_txt_documents_shortcodes(): void {
        $readme = file_get_contents( $this->plugin_dir . '/README.txt' );

        $shortcodes = [
            '[apd_listings]',
            '[apd_search_form]',
            '[apd_categories]',
            '[apd_submission_form]',
            '[apd_dashboard]',
            '[apd_favorites]',
            '[apd_login_form]',
            '[apd_register_form]',
        ];

        foreach ( $shortcodes as $shortcode ) {
            $this->assertStringContainsString( $shortcode, $readme, "README should document $shortcode" );
        }
    }

    /**
     * Test README.txt has privacy policy section.
     */
    public function test_readme_txt_has_privacy_policy(): void {
        $readme = file_get_contents( $this->plugin_dir . '/README.txt' );
        $this->assertStringContainsString( '== Privacy Policy ==', $readme );
    }

    /**
     * Test CHANGELOG.md exists.
     */
    public function test_changelog_exists(): void {
        $this->assertFileExists( $this->plugin_dir . '/CHANGELOG.md' );
    }

    /**
     * Test CHANGELOG.md follows Keep a Changelog format.
     */
    public function test_changelog_follows_format(): void {
        $changelog = file_get_contents( $this->plugin_dir . '/CHANGELOG.md' );

        // Header
        $this->assertStringContainsString( '# Changelog', $changelog );

        // Keep a Changelog reference
        $this->assertStringContainsString( 'keepachangelog.com', $changelog );

        // Semantic Versioning reference
        $this->assertStringContainsString( 'semver.org', $changelog );

        // Has a version section
        $this->assertStringContainsString( '## [1.0.0]', $changelog );

        // Has change type sections
        $this->assertStringContainsString( '### Added', $changelog );
    }

    /**
     * Test CHANGELOG.md documents all phases.
     */
    public function test_changelog_documents_phases(): void {
        $changelog = file_get_contents( $this->plugin_dir . '/CHANGELOG.md' );

        $phases = [
            'Core Plugin Infrastructure',
            'Listing Post Type',
            'Custom Fields Engine',
            'Taxonomies',
            'Search & Filtering',
            'Display System',
            'Shortcodes',
            'Gutenberg Blocks',
            'Frontend Submission',
            'User Dashboard',
            'Favorites System',
            'Reviews & Ratings',
            'Contact & Inquiries',
            'Email Notifications',
            'Admin Settings',
            'REST API',
            'Internationalization',
            'Security',
            'Performance',
        ];

        foreach ( $phases as $phase ) {
            $this->assertStringContainsString( $phase, $changelog, "CHANGELOG should document $phase" );
        }
    }

    /**
     * Test USER-GUIDE.md exists.
     */
    public function test_user_guide_exists(): void {
        $this->assertFileExists( $this->plugin_dir . '/docs/USER-GUIDE.md' );
    }

    /**
     * Test USER-GUIDE.md has table of contents.
     */
    public function test_user_guide_has_toc(): void {
        $guide = file_get_contents( $this->plugin_dir . '/docs/USER-GUIDE.md' );
        $this->assertStringContainsString( '## Table of Contents', $guide );
    }

    /**
     * Test USER-GUIDE.md covers essential topics.
     */
    public function test_user_guide_covers_topics(): void {
        $guide = file_get_contents( $this->plugin_dir . '/docs/USER-GUIDE.md' );

        $topics = [
            'Getting Started',
            'Creating Listings',
            'Categories and Tags',
            'Frontend Submission',
            'User Dashboard',
            'Search and Filtering',
            'Reviews and Ratings',
            'Favorites',
            'Contact Forms',
            'Email Notifications',
            'Shortcodes Reference',
            'Settings Reference',
            'Template Customization',
            'Troubleshooting',
        ];

        foreach ( $topics as $topic ) {
            $this->assertStringContainsString( $topic, $guide, "User guide should cover $topic" );
        }
    }

    /**
     * Test USER-GUIDE.md documents all shortcodes with attributes.
     */
    public function test_user_guide_documents_shortcode_attributes(): void {
        $guide = file_get_contents( $this->plugin_dir . '/docs/USER-GUIDE.md' );

        // Should have shortcode reference section with attributes
        $this->assertStringContainsString( '### [apd_listings]', $guide );
        $this->assertStringContainsString( '| Attribute |', $guide );
    }

    /**
     * Test DEVELOPER.md exists.
     */
    public function test_developer_guide_exists(): void {
        $this->assertFileExists( $this->plugin_dir . '/docs/DEVELOPER.md' );
    }

    /**
     * Test DEVELOPER.md has table of contents.
     */
    public function test_developer_guide_has_toc(): void {
        $guide = file_get_contents( $this->plugin_dir . '/docs/DEVELOPER.md' );
        $this->assertStringContainsString( '## Table of Contents', $guide );
    }

    /**
     * Test DEVELOPER.md documents action hooks.
     */
    public function test_developer_guide_documents_action_hooks(): void {
        $guide = file_get_contents( $this->plugin_dir . '/docs/DEVELOPER.md' );
        $hooks = $this->get_plugin_hook_inventory();

        $this->assertStringContainsString( '## Action Hooks', $guide );
        $this->assertSame(
            $hooks['actions'],
            $this->get_documented_hook_table_entries( $guide, 'Action Hooks', 'Filter Hooks' ),
            'Developer guide action hook table should exactly match the codebase.'
        );
    }

    /**
     * Test DEVELOPER.md documents filter hooks.
     */
    public function test_developer_guide_documents_filter_hooks(): void {
        $guide = file_get_contents( $this->plugin_dir . '/docs/DEVELOPER.md' );
        $hooks = $this->get_plugin_hook_inventory();

        $this->assertStringContainsString( '## Filter Hooks', $guide );
        $this->assertSame(
            $hooks['filters'],
            $this->get_documented_hook_table_entries( $guide, 'Filter Hooks', 'Custom Fields' ),
            'Developer guide filter hook table should exactly match the codebase.'
        );
    }

    /**
     * Test DEVELOPER.md examples only reference existing hooks.
     */
    public function test_developer_guide_hook_examples_reference_existing_hooks(): void {
        $guide      = file_get_contents( $this->plugin_dir . '/docs/DEVELOPER.md' );
        $hooks      = $this->get_plugin_hook_inventory();
        $actions    = $this->get_example_hook_references( $guide, [ 'add_action', 'do_action' ] );
        $filters    = $this->get_example_hook_references( $guide, [ 'add_filter' ] );
        $bad_action = array_values( array_diff( $actions, $hooks['actions'] ) );
        $bad_filter = array_values( array_diff( $filters, $hooks['filters'] ) );

        $this->assertSame( [], $bad_action, 'Developer guide examples should only use existing action hooks.' );
        $this->assertSame( [], $bad_filter, 'Developer guide examples should only use existing filter hooks.' );
    }

    /**
     * Test DEVELOPER.md documents custom fields.
     */
    public function test_developer_guide_documents_custom_fields(): void {
        $guide = file_get_contents( $this->plugin_dir . '/docs/DEVELOPER.md' );

        $this->assertStringContainsString( '## Custom Fields', $guide );
        $this->assertStringContainsString( 'apd_register_field_type', $guide );
        $this->assertStringContainsString( 'FieldTypeInterface', $guide );
    }

    /**
     * Test DEVELOPER.md documents REST API.
     */
    public function test_developer_guide_documents_rest_api(): void {
        $guide = file_get_contents( $this->plugin_dir . '/docs/DEVELOPER.md' );

        $this->assertStringContainsString( '## REST API', $guide );
        $this->assertStringContainsString( 'apd/v1', $guide );
        $this->assertStringContainsString( '/listings', $guide );
        $this->assertStringContainsString( '/categories', $guide );
        $this->assertStringContainsString( '/favorites', $guide );
        $this->assertStringContainsString( '/reviews', $guide );
    }

    /**
     * Test DEVELOPER.md documents helper functions.
     */
    public function test_developer_guide_documents_helper_functions(): void {
        $guide = file_get_contents( $this->plugin_dir . '/docs/DEVELOPER.md' );

        $this->assertStringContainsString( '## Helper Functions', $guide );

        $functions = [
            'apd_get_listing_field',
            'apd_get_listing_categories',
            'apd_add_favorite',
            'apd_get_listing_rating',
            'apd_get_setting',
            'apd_get_template',
        ];

        foreach ( $functions as $function ) {
            $this->assertStringContainsString( $function, $guide, "Developer guide should document $function" );
        }
    }

    /**
     * Test DEVELOPER.md documents database schema.
     */
    public function test_developer_guide_documents_database_schema(): void {
        $guide = file_get_contents( $this->plugin_dir . '/docs/DEVELOPER.md' );

        $this->assertStringContainsString( '## Database Schema', $guide );
        $this->assertStringContainsString( 'apd_listing', $guide );
        $this->assertStringContainsString( '_apd_views_count', $guide );
        $this->assertStringContainsString( 'apd_category', $guide );
        $this->assertStringContainsString( 'apd_review', $guide );
    }

    /**
     * Test DEVELOPER.md documents coding standards.
     */
    public function test_developer_guide_documents_coding_standards(): void {
        $guide = file_get_contents( $this->plugin_dir . '/docs/DEVELOPER.md' );

        $this->assertStringContainsString( '## Coding Standards', $guide );
        $this->assertStringContainsString( 'WordPress Coding Standards', $guide );
        $this->assertStringContainsString( 'Security', $guide );
        $this->assertStringContainsString( 'esc_html', $guide );
        $this->assertStringContainsString( 'sanitize_text_field', $guide );
    }

    /**
     * Test DEVELOPER.md provides code examples.
     */
    public function test_developer_guide_has_code_examples(): void {
        $guide = file_get_contents( $this->plugin_dir . '/docs/DEVELOPER.md' );

        // Should have PHP code blocks
        $this->assertStringContainsString( '```php', $guide );

        // Should have add_filter examples
        $this->assertStringContainsString( "add_filter( 'apd_listing_fields'", $guide );

        // Should have add_action examples
        $this->assertStringContainsString( "add_action( 'apd_init'", $guide );
    }

    /**
     * Get the current plugin hook inventory from source files.
     *
     * @return array{actions: string[], filters: string[]}
     */
    private function get_plugin_hook_inventory(): array {
        $actions = [];
        $filters = [];

        foreach ( $this->get_hook_source_files() as $file ) {
            $content = file_get_contents( $file );

            preg_match_all( '/do_action\s*\(\s*[\'"](?P<hook>apd_[^\'"]+)[\'"]/', $content, $action_matches );
            preg_match_all( '/apply_filters\s*\(\s*[\'"](?P<hook>apd_[^\'"]+)[\'"]/', $content, $filter_matches );

            foreach ( $action_matches['hook'] as $hook ) {
                $actions[ $hook ] = true;
            }

            foreach ( $filter_matches['hook'] as $hook ) {
                $filters[ $hook ] = true;
            }
        }

        $actions = array_keys( $actions );
        $filters = array_keys( $filters );

        sort( $actions );
        sort( $filters );

        return [
            'actions' => $actions,
            'filters' => $filters,
        ];
    }

    /**
     * Get source files that define plugin hooks.
     *
     * @return string[]
     */
    private function get_hook_source_files(): array {
        $files = [ $this->plugin_dir . '/all-purpose-directory.php' ];
        $roots = [
            $this->plugin_dir . '/src',
            $this->plugin_dir . '/includes',
            $this->plugin_dir . '/templates',
        ];

        foreach ( $roots as $root ) {
            $iterator = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $root ) );

            foreach ( $iterator as $file ) {
                if ( $file->isDir() || 'php' !== $file->getExtension() ) {
                    continue;
                }

                $files[] = $file->getPathname();
            }
        }

        sort( $files );

        return $files;
    }

    /**
     * Extract hook names from a hook table section in DEVELOPER.md.
     *
     * @param string $guide         Full guide contents.
     * @param string $start_heading Section heading without hashes.
     * @param string $end_heading   Following section heading without hashes.
     * @return string[]
     */
    private function get_documented_hook_table_entries( string $guide, string $start_heading, string $end_heading ): array {
        $pattern = sprintf(
            '/## %s\n(?P<section>.*?)(?:\n---\n\n## %s|\n## %s)/s',
            preg_quote( $start_heading, '/' ),
            preg_quote( $end_heading, '/' ),
            preg_quote( $end_heading, '/' )
        );

        $this->assertSame( 1, preg_match( $pattern, $guide, $matches ), "Could not find $start_heading section." );

        preg_match_all( '/^\| `(?P<hook>apd_[^`]+)` \|/m', $matches['section'], $hook_matches );

        $hooks = $hook_matches['hook'];
        sort( $hooks );

        return array_values( array_unique( $hooks ) );
    }

    /**
     * Extract hook names referenced in add_action/do_action/add_filter examples.
     *
     * @param string   $guide Full guide contents.
     * @param string[] $calls Function names to scan.
     * @return string[]
     */
    private function get_example_hook_references( string $guide, array $calls ): array {
        $hooks = [];

        foreach ( $calls as $call ) {
            preg_match_all(
                sprintf( '/%s\s*\(\s*[\'"](?P<hook>apd_[^\'"]+)[\'"]/', preg_quote( $call, '/' ) ),
                $guide,
                $matches
            );

            foreach ( $matches['hook'] as $hook ) {
                $hooks[ $hook ] = true;
            }
        }

        $hooks = array_keys( $hooks );
        sort( $hooks );

        return $hooks;
    }

    /**
     * Test POT file exists.
     */
    public function test_pot_file_exists(): void {
        $this->assertFileExists( $this->plugin_dir . '/languages/all-purpose-directory.pot' );
    }

    /**
     * Test POT file has proper header.
     */
    public function test_pot_file_has_header(): void {
        $pot = file_get_contents( $this->plugin_dir . '/languages/all-purpose-directory.pot' );

        $this->assertStringContainsString( 'Project-Id-Version: All Purpose Directory', $pot );
        $this->assertStringContainsString( 'Content-Type: text/plain; charset=UTF-8', $pot );
    }

    /**
     * Test translation guide exists.
     */
    public function test_translation_guide_exists(): void {
        $this->assertFileExists( $this->plugin_dir . '/languages/TRANSLATING.md' );
    }

    /**
     * Test main plugin file has PHPDoc header.
     */
    public function test_main_plugin_file_has_phpdoc(): void {
        $content = file_get_contents( $this->plugin_dir . '/all-purpose-directory.php' );

        $this->assertStringContainsString( '/**', $content );
        $this->assertStringContainsString( '* Plugin Name:', $content );
        $this->assertStringContainsString( '* @package APD', $content );
    }

    /**
     * Test docs folder exists.
     */
    public function test_docs_folder_exists(): void {
        $this->assertDirectoryExists( $this->plugin_dir . '/docs' );
    }

    /**
     * Test all docs files are markdown.
     */
    public function test_docs_files_are_markdown(): void {
        $docs_dir = $this->plugin_dir . '/docs';
        $files    = glob( $docs_dir . '/*' );

        foreach ( $files as $file ) {
            if ( is_file( $file ) ) {
                $this->assertStringEndsWith( '.md', $file, 'Documentation files should be Markdown' );
            }
        }
    }
}
