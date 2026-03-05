<?php
/**
 * Listing Archive Template.
 *
 * This template displays the listing archive/search results page.
 *
 * This template can be overridden by copying it to:
 * yourtheme/all-purpose-directory/archive-listing.php
 *
 * @package APD\Templates
 * @since   1.0.0
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$template_loader = new \APD\Core\TemplateLoader();

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo $template_loader->render_archive_content();

get_footer();
