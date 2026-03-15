<?php
/**
 * List View Class.
 *
 * Displays listings in a horizontal list layout with more details visible.
 *
 * @package APD\Frontend\Display
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Frontend\Display;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ListView
 *
 * @since 1.0.0
 */
final class ListView extends AbstractView {

	/**
	 * View type identifier.
	 *
	 * @var string
	 */
	protected string $type = 'list';

	/**
	 * View label.
	 *
	 * @var string
	 */
	protected string $label = 'List';

	/**
	 * Dashicon class.
	 *
	 * @var string
	 */
	protected string $icon = 'dashicons-list-view';

	/**
	 * Template name.
	 *
	 * @var string
	 */
	protected string $template = 'listing-card-list';

	/**
	 * Supported features.
	 *
	 * @var array<string>
	 */
	protected array $supports = [
		'image',
		'excerpt',
		'tags',
		'date',
		'sidebar',
	];

	/**
	 * Default configuration values.
	 *
	 * @var array<string, mixed>
	 */
	protected array $defaults = [
		'show_image'        => true,
		'show_excerpt'      => true,
		'excerpt_length'    => 30,
		'show_category'     => true,
		'show_tags'         => true,
		'max_tags'          => 5,
		'show_date'         => true,
		'show_price'        => true,
		'show_rating'       => true,
		'show_favorite'     => true,
		'show_view_details' => true,
		'image_size'        => 'medium',
		'image_width'       => 280,
	];

	/**
	 * Constructor.
	 *
	 * Reads admin display settings as baseline defaults before applying
	 * any view-specific config overrides.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $config Optional. Initial configuration.
	 */
	public function __construct( array $config = [] ) {
		$this->defaults['show_image']    = (bool) \apd_get_option( 'show_thumbnail', true );
		$this->defaults['show_excerpt']  = (bool) \apd_get_option( 'show_excerpt', true );
		$this->defaults['show_category'] = (bool) \apd_get_option( 'show_category', true );
		$this->defaults['show_rating']   = (bool) \apd_get_option( 'show_rating', true );
		$this->defaults['show_favorite'] = (bool) \apd_get_option( 'show_favorite', true );

		parent::__construct( $config );
	}

	/**
	 * Get the view label.
	 *
	 * @since 1.0.0
	 *
	 * @return string Human-readable label.
	 */
	public function getLabel(): string {
		return __( 'List', 'all-purpose-directory' );
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
		// Build shared args from config, then add list-specific values.
		$args              = array_merge( $this->buildRenderArgs(), $args );
		$args['show_tags'] = $this->getConfigValue( 'show_tags', true );
		$args['max_tags']  = $this->getConfigValue( 'max_tags', 5 );
		$args['show_date'] = $this->getConfigValue( 'show_date', true );

		return parent::renderListing( $listing_id, $args );
	}

	/**
	 * Get the image width for list view.
	 *
	 * @since 1.0.0
	 *
	 * @return int Image width in pixels.
	 */
	public function getImageWidth(): int {
		return (int) $this->getConfigValue( 'image_width', 280 );
	}

	/**
	 * Set the image width.
	 *
	 * @since 1.0.0
	 *
	 * @param int $width Image width in pixels.
	 * @return self
	 */
	public function setImageWidth( int $width ): self {
		$this->setConfigValue( 'image_width', max( 100, min( 400, $width ) ) );
		return $this;
	}

	/**
	 * Get the maximum number of tags to display.
	 *
	 * @since 1.0.0
	 *
	 * @return int Maximum tags.
	 */
	public function getMaxTags(): int {
		return (int) $this->getConfigValue( 'max_tags', 5 );
	}

	/**
	 * Set the maximum number of tags.
	 *
	 * @since 1.0.0
	 *
	 * @param int $max Maximum tags to display.
	 * @return self
	 */
	public function setMaxTags( int $max ): self {
		$this->setConfigValue( 'max_tags', max( 0, min( 20, $max ) ) );
		return $this;
	}

	/**
	 * Get responsive layout information.
	 *
	 * List view adapts to a vertical layout on smaller screens.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string> Breakpoint => layout mapping.
	 */
	public function getResponsiveLayout(): array {
		$responsive = [
			'desktop' => 'horizontal',
			'tablet'  => 'vertical',
			'mobile'  => 'vertical',
		];

		/**
		 * Filter the responsive layout configuration.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, string> $responsive Layout per breakpoint.
		 * @param ListView              $view       The view instance.
		 */
		return apply_filters( 'apd_list_responsive_layout', $responsive, $this );
	}

	/**
	 * Render list with specific query arguments.
	 *
	 * Convenience method for rendering a list with custom query args.
	 *
	 * @since 1.0.0
	 *
	 * @param array $query_args WP_Query arguments.
	 * @param array $render_args Render arguments.
	 * @return string Rendered HTML.
	 */
	public function render( array $query_args = [], array $render_args = [] ): string {
		$query = $this->getListings( $query_args );
		return $this->renderListings( $query, $render_args );
	}

	/**
	 * Render list with pagination.
	 *
	 * @since 1.0.0
	 *
	 * @param array $query_args WP_Query arguments.
	 * @param array $render_args Render arguments.
	 * @return string Rendered HTML with pagination.
	 */
	public function renderWithPagination( array $query_args = [], array $render_args = [] ): string {
		$query   = $this->getListings( $query_args );
		$output  = $this->renderListings( $query, $render_args );
		$output .= $this->renderPagination( $query );

		return $output;
	}
}
