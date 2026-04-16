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

if ( ! function_exists( 'starterkit_render_slot' ) ) {
	/**
	 * Render a configured slot.
	 *
	 * @param string $slot_name Slot identifier.
	 * @param array  $context   Optional context overrides.
	 * @return void
	 */
	function starterkit_render_slot( $slot_name, array $context = array() ) {
		starterkit()->slot_renderer()->render( $slot_name, $context );
	}
}

starterkit()->boot();
