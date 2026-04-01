<?php
/**
 * URL helper utilities.
 *
 * @package APD\Core
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Core;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * URL utility helpers.
 */
final class Url {

	/**
	 * Recursively URL-encode query argument values.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Query arg value or nested array of values.
	 * @return mixed Encoded value tree.
	 */
	public static function encode_deep( $value ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $key => $item ) {
				$value[ $key ] = self::encode_deep( $item );
			}

			return $value;
		}

		if ( is_scalar( $value ) || null === $value ) {
			return rawurlencode( (string) $value );
		}

		return $value;
	}
}
