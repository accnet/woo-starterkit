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

		add_action( 'widgets_init', array( $this, 'register_sidebars' ) );
	}

	/**
	 * Register widget areas.
	 *
	 * @return void
	 */
	public function register_sidebars() {
		for ( $i = 1; $i <= 4; $i++ ) {
			register_sidebar(
				array(
					/* translators: %d: widget area number */
					'name'          => sprintf( __( 'Footer Column %d', 'starterkit' ), $i ),
					'id'            => 'footer-' . $i,
					'description'   => sprintf( __( 'Widget area for footer column %d.', 'starterkit' ), $i ),
					'before_widget' => '<div id="%1$s" class="widget %2$s">',
					'after_widget'  => '</div>',
					'before_title'  => '<h3 class="widget-title">',
					'after_title'   => '</h3>',
				)
			);
		}
	}
}