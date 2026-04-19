<?php
/**
 * Theme-owned checkout page shell.
 *
 * @package StarterKit
 */

defined( 'ABSPATH' ) || exit;

if ( function_exists( 'starterkit' ) ) {
	starterkit()->checkout_layout_manager()->render_shell();
	return;
}

wp_die( esc_html__( 'StarterKit checkout shell is unavailable.', 'starterkit' ) );
