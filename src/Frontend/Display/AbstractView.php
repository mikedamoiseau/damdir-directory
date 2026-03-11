<?php
/**
 * Abstract View Base Class.
 *
 * Provides common functionality for listing display views.
 *
 * @package APD\Frontend\Display
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Frontend\Display;

use APD\Contracts\ViewInterface;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AbstractView
 *
 * @since 1.0.0
 */
abstract class AbstractView implements ViewInterface {

	/**
	 * View type identifier.
	 *
	 * @var string
	 */
	protected string $type = '';

	/**
	 * View label.
	 *
	 * @var string
	 */
	protected string $label = '';

	/**
	 * Dashicon class.
	 *
	 * @var string
	 */
	protected string $icon = '';

	/**
	 * Template name.
	 *
	 * @var string
	 */
	protected string $template = '';

	/**
	 * View configuration.
	 *
	 * @var array<string, mixed>
	 */
	protected array $config = [];

	/**
	 * Supported features.
	 *
	 * @var array<string>
	 */
	protected array $supports = [];

	/**
	 * Default configuration values.
	 *
	 * @var array<string, mixed>
	 */
	protected array $defaults = [];

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $config Optional. Initial configuration.
	 */
	public function __construct( array $config = [] ) {
		$this->config = wp_parse_args( $config, $this->defaults );
	}

	/**
	 * Get the view type identifier.
	 *
	 * @since 1.0.0
	 *
	 * @return string View type.
	 */
	public function getType(): string {
		return $this->type;
	}

	/**
	 * Get the view label.
	 *
	 * @since 1.0.0
	 *
	 * @return string Human-readable label.
	 */
	public function getLabel(): string {
		return $this->label;
	}

	/**
	 * Get the dashicon class.
	 *
	 * @since 1.0.0
	 *
	 * @return string Dashicon class.
	 */
	public function getIcon(): string {
		return $this->icon;
	}

	/**
	 * Get the template name.
	 *
	 * @since 1.0.0
	 *
	 * @return string Template file name.
	 */
	public function getTemplate(): string {
		return $this->template;
	}

	/**
	 * Get view configuration.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed> Configuration options.
	 */
	public function getConfig(): array {
		return $this->config;
	}

	/**
	 * Set view configuration.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $config Configuration options.
	 * @return self
	 */
	public function setConfig( array $config ): self {
		$this->config = wp_parse_args( $config, $this->config );
		return $this;
	}

	/**
	 * Get a single config value.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key     Configuration key.
	 * @param mixed  $default Default value.
	 * @return mixed Configuration value.
	 */
	public function getConfigValue( string $key, mixed $default = null ): mixed {
		return $this->config[ $key ] ?? $default;
	}

	/**
	 * Set a single config value.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key   Configuration key.
	 * @param mixed  $value Configuration value.
	 * @return self
	 */
	public function setConfigValue( string $key, mixed $value ): self {
		$this->config[ $key ] = $value;
		return $this;
	}

	/**
	 * Check if the view supports a feature.
	 *
	 * @since 1.0.0
	 *
	 * @param string $feature Feature name.
	 * @return bool True if supported.
	 */
	public function supports( string $feature ): bool {
		return in_array( $feature, $this->supports, true );
	}

	/**
	 * Get the CSS classes for the listings container.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string> CSS classes.
	 */
	public function getContainerClasses(): array {
		$classes = [
			'apd-listings',
			'apd-listings--' . $this->type,
		];

		/**
		 * Filter the container CSS classes.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string> $classes CSS classes.
		 * @param ViewInterface $view    The view instance.
		 */
		return apply_filters( 'apd_view_container_classes', $classes, $this );
	}

	/**
	 * Get data attributes for the listings container.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string> Data attributes.
	 */
	public function getContainerAttributes(): array {
		$attributes = [
			'view' => $this->type,
		];

		if ( ! empty( $this->config['posts_per_page'] ) ) {
			$attributes['posts-per-page'] = (string) $this->config['posts_per_page'];
		}

		/**
		 * Filter the container data attributes.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, string> $attributes Data attributes.
		 * @param ViewInterface         $view       The view instance.
		 */
		return apply_filters( 'apd_view_container_attributes', $attributes, $this );
	}

	/**
	 * Build the container opening tag.
	 *
	 * @since 1.0.0
	 *
	 * @return string HTML opening tag.
	 */
	protected function buildContainerOpen(): string {
		$classes    = $this->getContainerClasses();
		$attributes = $this->getContainerAttributes();

		$class_string = esc_attr( implode( ' ', $classes ) );

		$attr_string = '';
		foreach ( $attributes as $key => $value ) {
			$attr_string .= sprintf( ' data-%s="%s"', esc_attr( $key ), esc_attr( $value ) );
		}

		return sprintf( '<div class="%s"%s>', $class_string, $attr_string );
	}

	/**
	 * Build the container closing tag.
	 *
	 * @since 1.0.0
	 *
	 * @return string HTML closing tag.
	 */
	protected function buildContainerClose(): string {
		return '</div>';
	}

	/**
	 * Build shared render args from view config.
	 *
	 * Maps common config values (show_image, show_excerpt, etc.) into
	 * template arguments. Subclasses call this then add view-specific args.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed> Shared render arguments.
	 */
	protected function buildRenderArgs(): array {
		$shared_keys = [
			'show_image',
			'show_excerpt',
			'excerpt_length',
			'show_category',
			'show_price',
			'show_rating',
			'show_favorite',
			'show_view_details',
			'image_size',
		];

		$args = [];
		foreach ( $shared_keys as $key ) {
			if ( isset( $this->config[ $key ] ) ) {
				$args[ $key ] = $this->config[ $key ];
			}
		}

		return $args;
	}

	/**
	 * Render a single listing.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $listing_id Listing post ID.
	 * @param array $args       Additional arguments.
	 * @return string Rendered HTML.
	 */
	public function renderListing( int $listing_id, array $args = [] ): string {
		$template_args = wp_parse_args(
			$args,
			[
				'listing_id'    => $listing_id,
				'current_view'  => $this->type,
				'view_config'   => $this->config,
				'show_image'    => (bool) \apd_get_setting( 'show_thumbnail', true ),
				'show_excerpt'  => (bool) \apd_get_setting( 'show_excerpt', true ),
				'show_category' => (bool) \apd_get_setting( 'show_category', true ),
				'show_rating'   => (bool) \apd_get_setting( 'show_rating', true ),
				'show_favorite' => (bool) \apd_get_setting( 'show_favorite', true ),
			]
		);

		/**
		 * Filter the template arguments.
		 *
		 * @since 1.0.0
		 *
		 * @param array         $template_args Template arguments.
		 * @param int           $listing_id    Listing post ID.
		 * @param ViewInterface $view          The view instance.
		 */
		$template_args = apply_filters( 'apd_view_listing_args', $template_args, $listing_id, $this );

		return \apd_get_template_part_html( $this->template, null, $template_args );
	}

	/**
	 * Render multiple listings.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_Query|array<int> $listings WP_Query or array of listing IDs.
	 * @param array                $args     Additional arguments.
	 * @return string Rendered HTML.
	 */
	public function renderListings( \WP_Query|array $listings, array $args = [] ): string {
		$defaults = [
			'show_container'  => true,
			'show_no_results' => true,
		];
		$args     = wp_parse_args( $args, $defaults );

		// Normalize listings to array of IDs.
		$listing_ids = $this->normalizeListings( $listings );

		// Handle empty results.
		if ( empty( $listing_ids ) ) {
			if ( $args['show_no_results'] ) {
				return \apd_render_no_results();
			}
			return '';
		}

		$output = '';

		if ( $args['show_container'] ) {
			$output .= $this->buildContainerOpen();
		}

		foreach ( $listing_ids as $listing_id ) {
			$output .= $this->renderListing( $listing_id, $args );
		}

		if ( $args['show_container'] ) {
			$output .= $this->buildContainerClose();
		}

		return $output;
	}

	/**
	 * Normalize listings to array of IDs.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_Query|array<int|\WP_Post> $listings WP_Query or array.
	 * @return array<int> Array of listing IDs.
	 */
	protected function normalizeListings( \WP_Query|array $listings ): array {
		if ( $listings instanceof \WP_Query ) {
			return wp_list_pluck( $listings->posts, 'ID' );
		}

		// Handle array of posts or IDs.
		$ids = [];
		foreach ( $listings as $listing ) {
			if ( $listing instanceof \WP_Post ) {
				$ids[] = $listing->ID;
			} elseif ( is_numeric( $listing ) ) {
				$ids[] = (int) $listing;
			}
		}

		return $ids;
	}

	/**
	 * Render pagination for a query.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_Query $query The query to paginate.
	 * @return string Pagination HTML.
	 */
	public function renderPagination( \WP_Query $query ): string {
		return \apd_render_pagination( $query );
	}

	/**
	 * Get listings from a query or array.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Query arguments.
	 * @return \WP_Query The query result.
	 */
	public function getListings( array $args = [] ): \WP_Query {
		$defaults = [
			'post_type'      => \apd_get_listing_post_type(),
			'post_status'    => 'publish',
			'posts_per_page' => \apd_get_listings_per_page(),
			'paged'          => max( 1, get_query_var( 'paged', 1 ) ),
		];

		$query_args = wp_parse_args( $args, $defaults );

		/**
		 * Filter the listings query arguments.
		 *
		 * @since 1.0.0
		 *
		 * @param array         $query_args Query arguments.
		 * @param ViewInterface $view       The view instance.
		 */
		$query_args = apply_filters( 'apd_view_listings_query_args', $query_args, $this );

		return new \WP_Query( $query_args );
	}
}
