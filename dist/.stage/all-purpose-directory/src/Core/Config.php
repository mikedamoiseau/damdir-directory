<?php
/**
 * Runtime configuration access service.
 *
 * @package APD\Core
 */

declare(strict_types=1);

namespace APD\Core;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Config
 *
 * Bridges WordPress/global configuration APIs at the composition boundary.
 */
class Config {

	/**
	 * Get a plugin setting value.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public function get_setting( string $key, mixed $default = null ): mixed {
		return \apd_get_setting( $key, $default );
	}

	/**
	 * Get a WordPress option value.
	 *
	 * @param string $option  Option key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public function get_option( string $option, mixed $default = false ): mixed {
		return get_option( $option, $default );
	}
}
