<?php
/**
 * Frontend asset registration.
 *
 * @package StarterKit
 */

namespace StarterKit\Core;

use StarterKit\Settings\FontEmbedManager;
use StarterKit\Settings\GlobalSettingsManager;

class AssetManager {
	/**
	 * Settings manager.
	 *
	 * @var GlobalSettingsManager
	 */
	protected $settings;

	/**
	 * Local font embed manager.
	 *
	 * @var FontEmbedManager|null
	 */
	protected $font_embed_manager;

	/**
	 * Hook registration.
	 *
	 * @param GlobalSettingsManager $settings Settings manager.
	 */
	public function __construct( GlobalSettingsManager $settings ) {
		$this->settings = $settings;

		if ( function_exists( 'starterkit' ) ) {
			$this->font_embed_manager = starterkit()->font_embed_manager();
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Enqueue theme assets.
	 *
	 * @return void
	 */
	public function enqueue() {
		$version = wp_get_theme()->get( 'Version' );
		$font_url = $this->local_font_url();

		if ( ! $font_url ) {
			$font_url = $this->google_fonts_url();
		}

		if ( $font_url ) {
			wp_enqueue_style(
				'starterkit-theme-fonts',
				$font_url,
				array(),
				null
			);
		}

		wp_enqueue_style(
			'starterkit-theme',
			get_template_directory_uri() . '/assets/css/theme.css',
			array(),
			$version
		);
	}

	/**
	 * Build Google Fonts URL from selected font families.
	 *
	 * @return string
	 */
	protected function google_fonts_url() {
		$families = array_unique(
			array_filter(
				array(
					(string) $this->settings->get( 'heading_font', 'Poppins' ),
					(string) $this->settings->get( 'body_font', 'Inter' ),
				)
			)
		);

		$options = $this->settings->google_font_options();
		$query_families = array();

		foreach ( $families as $family ) {
			if ( isset( $options[ $family ]['query'] ) ) {
				$query_families[] = $options[ $family ]['query'];
			}
		}

		if ( empty( $query_families ) ) {
			return '';
		}

		return add_query_arg(
			array(
				'family'  => implode( '&family=', $query_families ),
				'display' => 'swap',
			),
			'https://fonts.googleapis.com/css2'
		);
	}

	/**
	 * Return local generated font CSS URL when available.
	 *
	 * @return string
	 */
	protected function local_font_url() {
		if ( ! $this->font_embed_manager || ! $this->font_embed_manager->has_current_embed() ) {
			return '';
		}

		return $this->font_embed_manager->local_css_url();
	}
}
