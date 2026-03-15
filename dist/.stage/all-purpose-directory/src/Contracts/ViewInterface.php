<?php
/**
 * View Interface.
 *
 * Defines the contract for listing display views (grid, list, etc.).
 *
 * @package APD\Contracts
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Contracts;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface ViewInterface
 *
 * @since 1.0.0
 */
interface ViewInterface {

	/**
	 * Get the view type identifier.
	 *
	 * @since 1.0.0
	 *
	 * @return string View type (e.g., 'grid', 'list').
	 */
	public function getType(): string;

	/**
	 * Get the view label for display.
	 *
	 * @since 1.0.0
	 *
	 * @return string Human-readable label.
	 */
	public function getLabel(): string;

	/**
	 * Get the dashicon class for the view.
	 *
	 * @since 1.0.0
	 *
	 * @return string Dashicon class (e.g., 'dashicons-grid-view').
	 */
	public function getIcon(): string;

	/**
	 * Get the template name for rendering a single listing.
	 *
	 * @since 1.0.0
	 *
	 * @return string Template file name (without .php extension).
	 */
	public function getTemplate(): string;

	/**
	 * Get view-specific configuration.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed> Configuration options.
	 */
	public function getConfig(): array;

	/**
	 * Set view configuration.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $config Configuration options.
	 * @return self
	 */
	public function setConfig( array $config ): self;

	/**
	 * Render a single listing.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $listing_id Listing post ID.
	 * @param array $args       Additional arguments.
	 * @return string Rendered HTML.
	 */
	public function renderListing( int $listing_id, array $args = [] ): string;

	/**
	 * Render multiple listings.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_Query|array<int> $listings WP_Query or array of listing IDs.
	 * @param array                $args     Additional arguments.
	 * @return string Rendered HTML.
	 */
	public function renderListings( \WP_Query|array $listings, array $args = [] ): string;

	/**
	 * Get the CSS classes for the listings container.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string> CSS classes.
	 */
	public function getContainerClasses(): array;

	/**
	 * Get data attributes for the listings container.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string> Data attributes (without 'data-' prefix).
	 */
	public function getContainerAttributes(): array;

	/**
	 * Check if the view supports a specific feature.
	 *
	 * @since 1.0.0
	 *
	 * @param string $feature Feature name (e.g., 'columns', 'masonry').
	 * @return bool True if supported.
	 */
	public function supports( string $feature ): bool;
}
