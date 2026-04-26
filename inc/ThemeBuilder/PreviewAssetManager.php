<?php
/**
 * Preview asset manager for frontend builder mode.
 *
 * @package StarterKit
 */

namespace StarterKit\ThemeBuilder;

use StarterKit\Core\AssetVersion;

class PreviewAssetManager {
	/**
	 * Builder mode.
	 *
	 * @var BuilderMode
	 */
	protected $builder_mode;

	/**
	 * Constructor.
	 *
	 * @param BuilderMode $builder_mode Builder mode.
	 */
	public function __construct( BuilderMode $builder_mode ) {
		$this->builder_mode = $builder_mode;

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_preview_assets' ) );
		add_filter( 'show_admin_bar', array( $this, 'hide_admin_bar_in_preview' ) );
		add_filter( 'body_class', array( $this, 'add_preview_body_class' ) );
	}

	/**
	 * Enqueue frontend preview assets when builder mode is active.
	 *
	 * @return void
	 */
	public function enqueue_preview_assets() {
		if ( ! $this->builder_mode->is_builder_mode() ) {
			return;
		}

		wp_enqueue_style(
			'starterkit-theme-builder-preview',
			get_template_directory_uri() . '/assets/css/theme-builder.css',
			array(),
			AssetVersion::for_file( get_template_directory() . '/assets/css/theme-builder.css' )
		);

		wp_enqueue_script(
			'starterkit-theme-builder-preview',
			get_template_directory_uri() . '/assets/js/theme-builder-preview.js',
			array(),
			AssetVersion::for_file( get_template_directory() . '/assets/js/theme-builder-preview.js' ),
			true
		);
	}

	/**
	 * Hide the WordPress admin bar inside builder preview iframes.
	 *
	 * @param bool $show Whether to show the admin bar.
	 * @return bool
	 */
	public function hide_admin_bar_in_preview( $show ) {
		if ( $this->builder_mode->is_builder_mode() ) {
			return false;
		}

		return $show;
	}

	/**
	 * Add a preview mode body class for scoped preview styling.
	 *
	 * @param array<int, string> $classes Body classes.
	 * @return array<int, string>
	 */
	public function add_preview_body_class( $classes ) {
		if ( $this->builder_mode->is_builder_mode() ) {
			$classes[] = 'starterkit-builder-preview-mode';
		}

		return $classes;
	}
}
