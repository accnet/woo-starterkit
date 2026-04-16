<?php
/**
 * Register the section instance CPT.
 *
 * @package StarterKit
 */

namespace StarterKit\Sections;

class SectionPostType {
	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
	}

	/**
	 * Register post type.
	 *
	 * @return void
	 */
	public function register() {
		register_post_type(
			'theme_section',
			array(
				'labels'          => array(
					'name'          => __( 'Theme Sections', 'starterkit' ),
					'singular_name' => __( 'Theme Section', 'starterkit' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => 'starterkit-theme-builder',
				'menu_position'   => null,
				'menu_icon'       => 'dashicons-screenoptions',
				'supports'        => array( 'title', 'page-attributes' ),
				'capability_type' => 'post',
			)
		);
	}
}
