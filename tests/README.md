# All Purpose Directory - Test Suite

This directory contains the test suite for the All Purpose Directory WordPress plugin.

## Test Structure

```
tests/
├── bootstrap.php           # Integration test bootstrap (WordPress test suite)
├── TestCase.php            # Base test case for integration tests
├── unit/
│   ├── bootstrap.php       # Unit test bootstrap (Brain Monkey mocks)
│   ├── UnitTestCase.php    # Base test case for unit tests
│   ├── Fields/             # Field type unit tests
│   └── Listing/            # Listing component unit tests
├── integration/
│   ├── PostTypeTest.php    # Custom post type tests
│   ├── TaxonomyTest.php    # Taxonomy tests
│   ├── SubmissionTest.php  # Frontend submission tests
│   ├── SearchQueryTest.php # Search and filter tests
│   └── RestApiTest.php     # REST API endpoint tests
├── factories/
│   ├── ListingFactory.php  # Generate test listings
│   ├── CategoryFactory.php # Generate test categories
│   ├── ReviewFactory.php   # Generate test reviews
│   └── UserFactory.php     # Generate test users
├── fixtures/
│   ├── sample-listings.sql # 25 sample listings
│   ├── sample-categories.sql # Hierarchical categories
│   ├── sample-reviews.sql  # Review data
│   └── load-fixtures.sh    # Script to load fixtures
└── e2e/
    ├── playwright.config.ts
    ├── global-setup.ts     # Auth setup
    ├── fixtures.ts         # Page objects
    └── *.spec.ts           # E2E test specs
```

## Test Philosophy

### What to Test

**Unit Tests:**
- Pure PHP classes with no WordPress dependencies
- Field type validation logic
- Data transformation methods
- Business logic calculations

**Integration Tests:**
- Custom post type registration and behavior
- Taxonomy functionality
- Database operations via repositories
- WordPress hooks and filters
- REST API endpoints

**E2E Tests:**
- User-facing workflows (submission, search, dashboard)
- JavaScript interactions (AJAX filtering, favorites toggle)
- Admin functionality (listing management, settings)

### Testing Approach

1. **Test behavior, not implementation** - Focus on what code does, not how it does it
2. **Keep tests isolated** - Each test should be independent
3. **Use factories for test data** - Consistent, realistic test data
4. **Test edge cases** - Empty inputs, invalid data, boundary conditions
5. **Write tests alongside features** - Not after the fact

## Running Tests

### Prerequisites

1. Docker installed and running.

2. Build the local test image:
   ```bash
   ./bin/docker-test.sh build-image
   ```

3. Install Composer dependencies in the container:
   ```bash
   ./bin/docker-test.sh composer-install
   ```

4. For WordPress Plugin Check, use the bundled isolated stack runner:
   ```bash
   ./bin/plugin-check-local.sh
   ```

### Unit Tests (Fast, No WordPress)

```bash
# Run all unit tests (recommended)
./bin/docker-test.sh test-unit

# Run specific test file
./bin/docker-test.sh run "./vendor/bin/phpunit -c phpunit-unit.xml tests/unit/Fields/FieldRegistryTest.php"

# Run with coverage
./bin/docker-test.sh run "./vendor/bin/phpunit -c phpunit-unit.xml --coverage-html coverage/"
```

Unit tests use [Brain Monkey](https://brain-wp.github.io/BrainMonkey/) to mock WordPress functions.

### Integration Tests (Requires WordPress)

```bash
# Run all integration tests
./bin/docker-test.sh test-integration

# Run specific test
./bin/docker-test.sh run "./vendor/bin/phpunit tests/integration/PostTypeTest.php"
```

Integration tests require a WordPress test suite + DB setup. The command above runs inside the portable Docker toolchain, but you still need WP test suite env vars/data configured for integration tests.

### E2E Tests (Browser Tests)

```bash
# Install Playwright browsers (first time only)
npx playwright install

# Run all E2E tests
npm run test:e2e

# Run with UI (interactive mode)
npm run test:e2e:ui

# Run in headed browser mode
npm run test:e2e:headed

# Run specific test file
npx playwright test tests/e2e/submission.spec.ts
```

## Using Test Factories

Factories generate realistic test data for consistent, readable tests.

### ListingFactory

```php
use APD\Tests\Factories\ListingFactory;

// Create a published listing with defaults
$listing_id = ListingFactory::create();

// Create with specific attributes
$listing_id = ListingFactory::create([
    'post_title' => 'Test Restaurant',
    'post_status' => 'pending',
]);

// Create with custom field values
$listing_id = ListingFactory::create([
    'post_title' => 'My Business',
], [
    '_apd_phone' => '555-1234',
    '_apd_email' => 'test@example.com',
    '_apd_price_range' => '$$',
]);

// Get data array without inserting (for unit tests)
$data = ListingFactory::make();
```

### CategoryFactory

```php
use APD\Tests\Factories\CategoryFactory;

// Create a root category
$term_id = CategoryFactory::create();

// Create with specific name and parent
$term_id = CategoryFactory::create([
    'name' => 'Restaurants',
    'description' => 'Places to eat',
]);

// Create child category
$child_id = CategoryFactory::create([
    'name' => 'Fast Food',
    'parent' => $parent_id,
]);

// Create with meta (icon, color)
$term_id = CategoryFactory::create([
    'name' => 'Shopping',
], [
    '_apd_icon' => 'dashicons-cart',
    '_apd_color' => '#4CAF50',
]);
```

### ReviewFactory

```php
use APD\Tests\Factories\ReviewFactory;

// Create approved review for a listing
$review_id = ReviewFactory::create([
    'listing_id' => $listing_id,
]);

// Create with specific rating and content
$review_id = ReviewFactory::create([
    'listing_id' => $listing_id,
    'rating' => 5,
    'title' => 'Excellent!',
    'content' => 'Great experience.',
    'author_name' => 'John Doe',
    'author_email' => 'john@example.com',
]);

// Create pending review
$review_id = ReviewFactory::create([
    'listing_id' => $listing_id,
    'approved' => false,
]);
```

### UserFactory

```php
use APD\Tests\Factories\UserFactory;

// Create subscriber (default)
$user_id = UserFactory::create();

// Create with specific role
$user_id = UserFactory::create(['role' => 'author']);

// Create admin
$admin_id = UserFactory::create(['role' => 'administrator']);

// Create with specific attributes
$user_id = UserFactory::create([
    'user_login' => 'testuser',
    'user_email' => 'test@example.com',
    'display_name' => 'Test User',
]);
```

## Using SQL Fixtures

SQL fixtures provide pre-populated test data for E2E tests or manual testing.

### Loading Fixtures

From inside the Docker container:

```bash
# Load all fixtures
./tests/fixtures/load-fixtures.sh

# Load specific fixture
./tests/fixtures/load-fixtures.sh categories
./tests/fixtures/load-fixtures.sh listings
./tests/fixtures/load-fixtures.sh reviews
```

Or directly with MySQL:

```bash
mysql -h mysql -u root -proot wordpress < tests/fixtures/sample-categories.sql
mysql -h mysql -u root -proot wordpress < tests/fixtures/sample-listings.sql
mysql -h mysql -u root -proot wordpress < tests/fixtures/sample-reviews.sql
```

### Fixture Data Summary

**Categories (sample-categories.sql):**
- 6 parent categories: Restaurants, Hotels, Shopping, Services, Entertainment, Healthcare
- 15 child categories (subcategories)
- 10 tags (Pet Friendly, Free WiFi, etc.)
- Each with icons and colors

**Listings (sample-listings.sql):**
- 25 listings total
- Status mix: 19 published, 3 pending, 2 draft, 1 expired
- Across all categories
- With phone, email, address, hours, price range, etc.
- Assigned to 3 different authors

**Reviews (sample-reviews.sql):**
- 31 reviews across multiple listings
- Rating range: 1-5 stars
- Mix of approved and pending
- Some listings with many reviews, some with none

## Writing New Tests

### Unit Test Template

```php
<?php

namespace APD\Tests\Unit\Fields;

use APD\Tests\Unit\UnitTestCase;

class MyNewTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Test setup
    }

    public function test_it_does_something(): void
    {
        // Arrange
        $input = 'test';

        // Act
        $result = my_function($input);

        // Assert
        $this->assertEquals('expected', $result);
    }

    public function test_it_handles_edge_case(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        my_function(null);
    }
}
```

### Integration Test Template

```php
<?php

namespace APD\Tests\Integration;

use APD\Tests\TestCase;
use APD\Tests\Factories\ListingFactory;

class MyIntegrationTest extends TestCase
{
    public function test_listing_is_created_correctly(): void
    {
        $listing_id = ListingFactory::create([
            'post_title' => 'Test Listing',
        ]);

        $listing = get_post($listing_id);

        $this->assertEquals('Test Listing', $listing->post_title);
        $this->assertEquals('apd_listing', $listing->post_type);
    }

    public function test_custom_field_is_saved(): void
    {
        $listing_id = ListingFactory::create([], [
            '_apd_phone' => '555-1234',
        ]);

        $phone = get_post_meta($listing_id, '_apd_phone', true);

        $this->assertEquals('555-1234', $phone);
    }
}
```

### E2E Test Template

```typescript
import { test, expect } from '@playwright/test';

test.describe('Feature Name', () => {
  test.beforeEach(async ({ page }) => {
    // Navigate to starting point
    await page.goto('/');
  });

  test('user can perform action', async ({ page }) => {
    // Click button
    await page.click('button[data-action="submit"]');

    // Fill form
    await page.fill('#name', 'Test Name');

    // Submit
    await page.click('button[type="submit"]');

    // Assert result
    await expect(page.locator('.success-message')).toBeVisible();
  });

  test('handles validation errors', async ({ page }) => {
    // Submit empty form
    await page.click('button[type="submit"]');

    // Check error appears
    await expect(page.locator('.error-message')).toContainText('Required');
  });
});
```

## Troubleshooting

### Unit tests fail with "Call to undefined function"

Make sure Brain Monkey mocks are set up. Check `tests/unit/bootstrap.php` includes the function you're trying to call.

### Integration tests can't connect to database

1. Ensure Docker is running: `docker ps`
2. Confirm your integration-test DB host/credentials and `WP_TESTS_DIR` are set correctly
3. Re-run the WordPress test suite install script if needed (`bin/install-wp-tests.sh ...`)

### E2E tests timeout

1. Check the site is accessible: `curl http://localhost/`
2. Increase timeout in `playwright.config.ts`
3. Run with `--debug` flag: `npx playwright test --debug`

### Fixtures fail to load

1. Check you're running from inside Docker container
2. Verify database credentials in `load-fixtures.sh`
3. Make sure categories load before listings (listings depend on category IDs)
