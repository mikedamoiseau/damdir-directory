<?php
/**
 * Listing Comments Template.
 *
 * Replaces the default WordPress comments template for listings.
 * When reviews are enabled, renders the review section via the
 * `apd_single_listing_reviews` action. When disabled, outputs nothing.
 *
 * This template can be overridden by copying it to:
 * yourtheme/all-purpose-directory/comments-listing.php
 *
 * @package APD\Templates
 * @since   1.0.0
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Only render reviews if the feature is enabled.
if ( ! apd_reviews_enabled() ) {
	return;
}

$listing_id = get_the_ID();

if ( ! $listing_id ) {
	return;
}

/**
 * Fires in the reviews section area for listings.
 *
 * This action is used by ReviewDisplay to render the full reviews section
 * including the review list, rating summary, and review form.
 *
 * @since 1.0.0
 *
 * @param int $listing_id The listing post ID.
 */
do_action( 'apd_single_listing_reviews', $listing_id );
