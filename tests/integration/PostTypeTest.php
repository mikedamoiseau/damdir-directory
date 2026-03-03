<?php
/**
 * Integration tests for Listing Post Type.
 *
 * Tests post type registration and behavior with WordPress.
 *
 * @package APD\Tests\Integration
 */

declare(strict_types=1);

namespace APD\Tests\Integration;

use APD\Listing\PostType;
use APD\Listing\AdminColumns;
use APD\Core\Capabilities;
use APD\Tests\TestCase;

/**
 * Test case for Listing Post Type.
 *
 * @covers \APD\Listing\PostType
 */
class PostTypeTest extends TestCase
{
    /**
     * Test post type is registered.
     */
    public function testPostTypeIsRegistered(): void
    {
        $this->assertTrue(
            post_type_exists(PostType::POST_TYPE),
            'Post type apd_listing should be registered.'
        );
    }

    /**
     * Test post type labels.
     */
    public function testPostTypeLabels(): void
    {
        $post_type_object = get_post_type_object(PostType::POST_TYPE);

        $this->assertNotNull($post_type_object);
        $this->assertEquals('Listings', $post_type_object->labels->name);
        $this->assertEquals('Listing', $post_type_object->labels->singular_name);
        $this->assertEquals('Add New Listing', $post_type_object->labels->add_new_item);
        $this->assertEquals('Edit Listing', $post_type_object->labels->edit_item);
        $this->assertEquals('View Listing', $post_type_object->labels->view_item);
        $this->assertEquals('All Listings', $post_type_object->labels->all_items);
        $this->assertEquals('Search Listings', $post_type_object->labels->search_items);
        $this->assertEquals('No listings found.', $post_type_object->labels->not_found);
    }

    /**
     * Test post type supports.
     */
    public function testPostTypeSupports(): void
    {
        $this->assertTrue(
            post_type_supports(PostType::POST_TYPE, 'title'),
            'Post type should support title.'
        );
        $this->assertTrue(
            post_type_supports(PostType::POST_TYPE, 'editor'),
            'Post type should support editor.'
        );
        $this->assertTrue(
            post_type_supports(PostType::POST_TYPE, 'thumbnail'),
            'Post type should support thumbnail.'
        );
        $this->assertTrue(
            post_type_supports(PostType::POST_TYPE, 'author'),
            'Post type should support author.'
        );
        $this->assertTrue(
            post_type_supports(PostType::POST_TYPE, 'excerpt'),
            'Post type should support excerpt.'
        );
        $this->assertFalse(
            post_type_supports(PostType::POST_TYPE, 'comments'),
            'Post type should not support comments by default.'
        );
    }

    /**
     * Test post type is public.
     */
    public function testPostTypeIsPublic(): void
    {
        $post_type_object = get_post_type_object(PostType::POST_TYPE);

        $this->assertNotNull($post_type_object);
        $this->assertTrue($post_type_object->public);
        $this->assertTrue($post_type_object->publicly_queryable);
        $this->assertTrue($post_type_object->show_ui);
        $this->assertTrue($post_type_object->show_in_menu);
    }

    /**
     * Test post type has archive.
     */
    public function testPostTypeHasArchive(): void
    {
        $post_type_object = get_post_type_object(PostType::POST_TYPE);

        $this->assertNotNull($post_type_object);
        $this->assertTrue($post_type_object->has_archive);
    }

    /**
     * Test post type core REST API exposure setting.
     */
    public function testPostTypeCoreRestExposure(): void
    {
        $post_type_object = get_post_type_object(PostType::POST_TYPE);

        $this->assertNotNull($post_type_object);
        $this->assertTrue($post_type_object->show_in_rest);
    }

    /**
     * Test custom rewrite rules.
     */
    public function testPostTypeRewriteRules(): void
    {
        $post_type_object = get_post_type_object(PostType::POST_TYPE);

        $this->assertNotNull($post_type_object);
        $this->assertIsArray($post_type_object->rewrite);
        $this->assertEquals('listings', $post_type_object->rewrite['slug']);
        $this->assertFalse($post_type_object->rewrite['with_front']);
    }

    /**
     * Test custom capabilities are set.
     */
    public function testPostTypeCapabilities(): void
    {
        $post_type_object = get_post_type_object(PostType::POST_TYPE);

        $this->assertNotNull($post_type_object);

        // Check that our custom capabilities are mapped.
        $this->assertEquals(
            Capabilities::EDIT_LISTING,
            $post_type_object->cap->edit_post
        );
        $this->assertEquals(
            Capabilities::READ_LISTING,
            $post_type_object->cap->read_post
        );
        $this->assertEquals(
            Capabilities::DELETE_LISTING,
            $post_type_object->cap->delete_post
        );
        $this->assertEquals(
            Capabilities::EDIT_LISTINGS,
            $post_type_object->cap->edit_posts
        );
        $this->assertEquals(
            Capabilities::EDIT_OTHERS_LISTINGS,
            $post_type_object->cap->edit_others_posts
        );
        $this->assertEquals(
            Capabilities::PUBLISH_LISTINGS,
            $post_type_object->cap->publish_posts
        );
        $this->assertEquals(
            Capabilities::DELETE_LISTINGS,
            $post_type_object->cap->delete_posts
        );
    }

    /**
     * Test creating a listing.
     */
    public function testCreateListing(): void
    {
        $listing_id = $this->createListing([
            'post_title'   => 'Test Business',
            'post_content' => 'A test business listing.',
        ]);

        $this->assertIsInt($listing_id);
        $this->assertGreaterThan(0, $listing_id);

        $post = get_post($listing_id);
        $this->assertNotNull($post);
        $this->assertEquals(PostType::POST_TYPE, $post->post_type);
        $this->assertEquals('Test Business', $post->post_title);
        $this->assertEquals('A test business listing.', $post->post_content);
    }

    /**
     * Test listing default status.
     */
    public function testListingDefaultStatus(): void
    {
        // Create a published listing.
        $listing_id = $this->createListing([
            'post_status' => 'publish',
        ]);

        $post = get_post($listing_id);
        $this->assertEquals('publish', $post->post_status);

        // Create a draft listing.
        $draft_id = $this->createListing([
            'post_status' => 'draft',
        ]);

        $draft = get_post($draft_id);
        $this->assertEquals('draft', $draft->post_status);

        // Create a pending listing.
        $pending_id = $this->createListing([
            'post_status' => 'pending',
        ]);

        $pending = get_post($pending_id);
        $this->assertEquals('pending', $pending->post_status);
    }

    /**
     * Test custom post statuses are registered.
     */
    public function testCustomPostStatusesRegistered(): void
    {
        global $wp_post_statuses;

        $this->assertArrayHasKey(
            PostType::STATUS_EXPIRED,
            $wp_post_statuses,
            'Expired status should be registered.'
        );

        $expired_status = $wp_post_statuses[PostType::STATUS_EXPIRED];
        $this->assertEquals('Expired', $expired_status->label);
        $this->assertFalse($expired_status->public);
        $this->assertTrue($expired_status->exclude_from_search);
        $this->assertTrue($expired_status->show_in_admin_status_list);
    }

    /**
     * Test expired status.
     */
    public function testExpiredStatus(): void
    {
        $listing_id = $this->createListing([
            'post_status' => PostType::STATUS_EXPIRED,
        ]);

        $post = get_post($listing_id);
        $this->assertEquals(PostType::STATUS_EXPIRED, $post->post_status);

        // Verify expired listings are excluded from search by default.
        $expired_status = get_post_status_object(PostType::STATUS_EXPIRED);
        $this->assertTrue($expired_status->exclude_from_search);
    }

    /**
     * Test admin columns are added.
     */
    public function testAdminColumnsAdded(): void
    {
        $admin_columns = new AdminColumns();
        $columns = $admin_columns->add_columns([
            'cb'     => '<input type="checkbox" />',
            'title'  => 'Title',
            'author' => 'Author',
            'date'   => 'Date',
        ]);

        // Check that custom columns are added.
        $this->assertArrayHasKey('thumbnail', $columns, 'Thumbnail column should be added.');
        $this->assertArrayHasKey('apd_category', $columns, 'Category column should be added.');
        $this->assertArrayHasKey('listing_status', $columns, 'Status column should be added.');
        $this->assertArrayHasKey('views_count', $columns, 'Views count column should be added.');

        // Check column order (thumbnail should be second, after cb).
        $column_keys = array_keys($columns);
        $this->assertEquals('cb', $column_keys[0], 'Checkbox should be first.');
        $this->assertEquals('thumbnail', $column_keys[1], 'Thumbnail should be second.');
        $this->assertEquals('title', $column_keys[2], 'Title should be third.');

        // Check standard columns are preserved.
        $this->assertArrayHasKey('author', $columns, 'Author column should be preserved.');
        $this->assertArrayHasKey('date', $columns, 'Date column should be preserved.');
    }

    /**
     * Test admin columns have correct labels.
     */
    public function testAdminColumnLabels(): void
    {
        $admin_columns = new AdminColumns();
        $columns = $admin_columns->add_columns([]);

        $this->assertEquals('Image', $columns['thumbnail']);
        $this->assertEquals('Category', $columns['apd_category']);
        $this->assertEquals('Status', $columns['listing_status']);
        $this->assertEquals('Views', $columns['views_count']);
    }

    /**
     * Test admin column sorting configuration.
     */
    public function testAdminColumnSorting(): void
    {
        $admin_columns = new AdminColumns();
        $sortable = $admin_columns->sortable_columns([]);

        // Check views_count is sortable.
        $this->assertArrayHasKey('views_count', $sortable, 'Views count should be sortable.');
        $this->assertEquals(['views_count', false], $sortable['views_count']);

        // Check listing_status is sortable.
        $this->assertArrayHasKey('listing_status', $sortable, 'Listing status should be sortable.');
        $this->assertEquals(['listing_status', false], $sortable['listing_status']);
    }

    /**
     * Test views count meta operations.
     */
    public function testViewsCountMeta(): void
    {
        $listing_id = $this->createListing();

        // Initial views should be 0.
        $views = AdminColumns::get_views($listing_id);
        $this->assertEquals(0, $views, 'Initial views count should be 0.');

        // Increment views.
        $new_views = AdminColumns::increment_views($listing_id);
        $this->assertEquals(1, $new_views, 'Views count should be 1 after first increment.');

        // Increment again.
        $new_views = AdminColumns::increment_views($listing_id);
        $this->assertEquals(2, $new_views, 'Views count should be 2 after second increment.');

        // Verify persisted value.
        $views = AdminColumns::get_views($listing_id);
        $this->assertEquals(2, $views, 'Persisted views count should be 2.');
    }

    /**
     * Test status column renders correct badge classes.
     */
    public function testStatusColumnRendersCorrectBadges(): void
    {
        $admin_columns = new AdminColumns();

        // Test published status.
        $listing_id = $this->createListing(['post_status' => 'publish']);
        ob_start();
        $admin_columns->render_column('listing_status', $listing_id);
        $output = ob_get_clean();
        $this->assertStringContainsString('apd-status-publish', $output);
        $this->assertStringContainsString('Published', $output);

        // Test pending status.
        $pending_id = $this->createListing(['post_status' => 'pending']);
        ob_start();
        $admin_columns->render_column('listing_status', $pending_id);
        $output = ob_get_clean();
        $this->assertStringContainsString('apd-status-pending', $output);
        $this->assertStringContainsString('Pending', $output);

        // Test draft status.
        $draft_id = $this->createListing(['post_status' => 'draft']);
        ob_start();
        $admin_columns->render_column('listing_status', $draft_id);
        $output = ob_get_clean();
        $this->assertStringContainsString('apd-status-draft', $output);
        $this->assertStringContainsString('Draft', $output);

        // Test expired status.
        $expired_id = $this->createListing(['post_status' => PostType::STATUS_EXPIRED]);
        ob_start();
        $admin_columns->render_column('listing_status', $expired_id);
        $output = ob_get_clean();
        $this->assertStringContainsString('apd-status-expired', $output);
        $this->assertStringContainsString('Expired', $output);
    }

    /**
     * Test thumbnail column renders no-image placeholder when no thumbnail.
     */
    public function testThumbnailColumnNoImage(): void
    {
        $admin_columns = new AdminColumns();
        $listing_id = $this->createListing();

        ob_start();
        $admin_columns->render_column('thumbnail', $listing_id);
        $output = ob_get_clean();

        $this->assertStringContainsString('apd-no-image', $output);
        $this->assertStringContainsString('screen-reader-text', $output);
        $this->assertStringContainsString('No image', $output);
    }

    /**
     * Test category column renders no-category placeholder when no categories.
     */
    public function testCategoryColumnNoCategory(): void
    {
        $admin_columns = new AdminColumns();
        $listing_id = $this->createListing();

        ob_start();
        $admin_columns->render_column('apd_category', $listing_id);
        $output = ob_get_clean();

        $this->assertStringContainsString('apd-no-category', $output);
        $this->assertStringContainsString('screen-reader-text', $output);
        $this->assertStringContainsString('No category', $output);
    }

    /**
     * Test views column renders formatted number.
     */
    public function testViewsColumnRendersFormattedNumber(): void
    {
        $admin_columns = new AdminColumns();
        $listing_id = $this->createListing();

        // Set a high views count.
        update_post_meta($listing_id, AdminColumns::META_VIEWS, 1234567);

        ob_start();
        $admin_columns->render_column('views_count', $listing_id);
        $output = ob_get_clean();

        $this->assertStringContainsString('apd-views-count', $output);
        // Check for formatted number (locale-dependent, but should contain digits).
        $this->assertMatchesRegularExpression('/[\d,\.]+/', $output);
    }
}

