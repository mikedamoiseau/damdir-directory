<?php
/**
 * Dashboard Shortcode Class.
 *
 * Displays the user dashboard for managing listings.
 *
 * @package APD\Shortcode
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Shortcode;

use APD\Frontend\Dashboard\Dashboard;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DashboardShortcode
 *
 * @since 1.0.0
 */
final class DashboardShortcode extends AbstractShortcode {

	/**
	 * Shortcode tag.
	 *
	 * @var string
	 */
	protected string $tag = 'apd_dashboard';

	/**
	 * Shortcode description.
	 *
	 * @var string
	 */
	protected string $description = 'Display the user dashboard for managing listings.';

	/**
	 * Default attributes.
	 *
	 * @var array<string, mixed>
	 */
	protected array $defaults = [
		'default_tab' => 'my-listings',
		'show_stats'  => 'true',
		'class'       => '',
	];

	/**
	 * Attribute documentation.
	 *
	 * @var array<string, array>
	 */
	protected array $attribute_docs = [
		'default_tab' => [
			'type'        => 'string',
			'description' => 'Default tab to display (my-listings, add-new, favorites, profile).',
			'default'     => 'my-listings',
		],
		'show_stats'  => [
			'type'        => 'boolean',
			'description' => 'Show statistics overview section.',
			'default'     => 'true',
		],
		'class'       => [
			'type'        => 'string',
			'description' => 'Additional CSS classes.',
			'default'     => '',
		],
	];

	/**
	 * Get example usage.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_example(): string {
		return '[apd_dashboard default_tab="my-listings" show_stats="true"]';
	}

	/**
	 * Generate the shortcode output.
	 *
	 * @since 1.0.0
	 *
	 * @param array       $atts    Parsed shortcode attributes.
	 * @param string|null $content Shortcode content.
	 * @return string Shortcode output.
	 */
	protected function output( array $atts, ?string $content ): string {
		// Build dashboard configuration.
		$config = [
			'default_tab' => $atts['default_tab'],
			'show_stats'  => $atts['show_stats'],
			'class'       => $atts['class'],
		];

		// Get dashboard instance (singleton).
		$dashboard = Dashboard::get_instance( $config );

		// Render the dashboard.
		$output = $dashboard->render();

		// Wrap in shortcode container.
		return sprintf(
			'<div class="apd-dashboard-shortcode">%s</div>',
			$output
		);
	}
}
