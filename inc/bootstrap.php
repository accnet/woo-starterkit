<?php
/**
 * Bootstrap the theme services.
 *
 * @package StarterKit
 */

require_once __DIR__ . '/Core/Autoloader.php';

\StarterKit\Core\Autoloader::register();

if ( ! function_exists( 'starterkit' ) ) {
	/**
	 * Return the theme application singleton.
	 *
	 * @return \StarterKit\Core\App
	 */
	function starterkit() {
		return \StarterKit\Core\App::instance();
	}
}

starterkit()->boot();
