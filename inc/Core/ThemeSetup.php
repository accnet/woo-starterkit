<?php
/**
 * Theme support registration.
 *
 * @package StarterKit
 */

namespace StarterKit\Core;

class ThemeSetup {
	/**
	 * Hook registration.
	 */
	public function __construct() {
		add_action( 'after_setup_theme', array( $this, 'register' ) );
	}

	/**
	 * Register theme support.
	 *
	 * @return void
	 */
	public function register() {
		load_theme_textdomain( 'starterkit', get_template_directory() . '/languages' );

		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
		add_theme_support( 'custom-logo' );
		add_theme_support( 'woocommerce' );

		register_nav_menus(
			array(
				'primary' => __( 'Primary Menu', 'starterkit' ),
				'footer'  => __( 'Footer Menu', 'starterkit' ),
			)
		);
	}
}
