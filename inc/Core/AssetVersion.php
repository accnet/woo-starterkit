<?php
/**
 * Request-local asset version helper.
 *
 * @package StarterKit
 */

namespace StarterKit\Core;

class AssetVersion {
	/**
	 * Filemtime cache.
	 *
	 * @var array<string, string>
	 */
	protected static $cache = array();

	/**
	 * Return a cached file modification version.
	 *
	 * @param string $path Asset file path.
	 * @param string $fallback Fallback version.
	 * @return string
	 */
	public static function for_file( $path, $fallback = '' ) {
		$path = (string) $path;

		if ( '' === $path || ! file_exists( $path ) ) {
			return '' !== $fallback ? (string) $fallback : (string) wp_get_theme()->get( 'Version' );
		}

		if ( ! isset( self::$cache[ $path ] ) ) {
			self::$cache[ $path ] = (string) filemtime( $path );
		}

		return self::$cache[ $path ];
	}
}
