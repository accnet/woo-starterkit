<?php
/**
 * Lightweight autoloader for theme classes.
 *
 * @package StarterKit
 */

namespace StarterKit\Core;

class Autoloader {
	/**
	 * Register autoload callback.
	 *
	 * @return void
	 */
	public static function register() {
		spl_autoload_register( array( __CLASS__, 'autoload' ) );
	}

	/**
	 * Load theme classes by namespace.
	 *
	 * @param string $class Fully qualified class name.
	 * @return void
	 */
	public static function autoload( $class ) {
		$compatibility_prefix = 'StarterKit\\Compatibility\\WootifyCore\\';

		if ( 0 === strpos( $class, $compatibility_prefix ) ) {
			$relative = substr( $class, strlen( $compatibility_prefix ) );
			$relative = str_replace( '\\', '/', $relative );
			$file     = get_template_directory() . '/compatibility/wootify-core/' . $relative . '.php';

			if ( file_exists( $file ) ) {
				require_once $file;
			}

			return;
		}

		$prefix = 'StarterKit\\';

		if ( 0 !== strpos( $class, $prefix ) ) {
			return;
		}

		$relative = substr( $class, strlen( $prefix ) );
		$relative = str_replace( '\\', '/', $relative );
		$file     = get_template_directory() . '/inc/' . $relative . '.php';

		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
}
