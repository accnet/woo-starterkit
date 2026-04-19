<?php
/**
 * Preview asset manager for frontend builder mode.
 *
 * @package StarterKit
 */

namespace StarterKit\ThemeBuilder;

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
			filemtime( get_template_directory() . '/assets/css/theme-builder.css' )
		);

		wp_enqueue_script(
			'starterkit-theme-builder-preview',
			get_template_directory_uri() . '/assets/js/theme-builder-preview.js',
			array(),
			filemtime( get_template_directory() . '/assets/js/theme-builder-preview.js' ),
			true
		);
	}
}
