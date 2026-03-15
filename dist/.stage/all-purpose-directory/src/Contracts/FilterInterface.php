<?php
/**
 * Filter Interface.
 *
 * Defines the contract for all search filters in the directory plugin.
 * Filters handle rendering, sanitization, and WP_Query modification.
 *
 * @package APD\Contracts
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Contracts;

use WP_Query;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface FilterInterface
 *
 * All filter types must implement this interface to be used with the
 * FilterRegistry and SearchQuery systems.
 *
 * Filter configuration array structure:
 * - name: (string) Unique filter identifier
 * - type: (string) Filter type (select, checkbox, range, date_range, text)
 * - label: (string) Display label
 * - source: (string) Data source (taxonomy, field, custom)
 * - source_key: (string) Taxonomy name or field name
 * - options: (array) Available options
 * - multiple: (bool) Allow multiple selections
 * - empty_option: (string) Placeholder for empty selection
 * - query_callback: (callable|null) Custom query modification callback
 * - priority: (int) Display order (lower = earlier)
 * - active: (bool) Whether filter is currently active
 *
 * @since 1.0.0
 */
interface FilterInterface {

	/**
	 * Get the filter name identifier.
	 *
	 * Returns the unique string identifier for this filter.
	 * Used for registration and URL parameter matching.
	 *
	 * @since 1.0.0
	 *
	 * @return string The filter name identifier.
	 */
	public function getName(): string;

	/**
	 * Get the filter type.
	 *
	 * Returns the type of filter (select, checkbox, range, date_range, text).
	 *
	 * @since 1.0.0
	 *
	 * @return string The filter type.
	 */
	public function getType(): string;

	/**
	 * Render the filter HTML.
	 *
	 * Generates the HTML markup for displaying the filter control.
	 * Must return escaped, ready-to-output HTML.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Current filter value from request.
	 * @return string The rendered HTML markup.
	 */
	public function render( mixed $value ): string;

	/**
	 * Sanitize the filter value.
	 *
	 * Cleans and sanitizes the input value from the request.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The raw input value to sanitize.
	 * @return mixed The sanitized value.
	 */
	public function sanitize( mixed $value ): mixed;

	/**
	 * Modify the WP_Query to apply this filter.
	 *
	 * Adds tax_query, meta_query, or other modifications to filter results.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Query $query The query to modify.
	 * @param mixed    $value The sanitized filter value.
	 * @return void
	 */
	public function modifyQuery( WP_Query $query, mixed $value ): void;

	/**
	 * Get available options for this filter.
	 *
	 * Returns options for select/checkbox filters.
	 * May return empty array for text/range filters.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string> Options keyed by value.
	 */
	public function getOptions(): array;

	/**
	 * Check if this filter is currently active.
	 *
	 * Returns true if the filter has a non-empty value applied.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The current filter value.
	 * @return bool True if filter is active.
	 */
	public function isActive( mixed $value ): bool;

	/**
	 * Get the filter configuration.
	 *
	 * Returns the full configuration array for this filter.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed> The filter configuration.
	 */
	public function getConfig(): array;

	/**
	 * Get the URL parameter name for this filter.
	 *
	 * Returns the parameter name used in URLs (e.g., 'apd_category').
	 *
	 * @since 1.0.0
	 *
	 * @return string The URL parameter name.
	 */
	public function getUrlParam(): string;

	/**
	 * Get the display label for this filter.
	 *
	 * @since 1.0.0
	 *
	 * @return string The display label.
	 */
	public function getLabel(): string;

	/**
	 * Get the display value for the active filter.
	 *
	 * Used for showing active filter chips/badges.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The current filter value.
	 * @return string Human-readable display value.
	 */
	public function getDisplayValue( mixed $value ): string;
}
