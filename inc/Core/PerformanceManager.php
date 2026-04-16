<?php
/**
 * Frontend performance toggles driven by theme settings.
 *
 * @package StarterKit
 */

namespace StarterKit\Core;

use WP_Scripts;
use StarterKit\Settings\GlobalSettingsManager;

class PerformanceManager {
	/**
	 * Settings manager.
	 *
	 * @var GlobalSettingsManager
	 */
	protected $settings;

	/**
	 * Constructor.
	 *
	 * @param GlobalSettingsManager $settings Settings manager.
	 */
	public function __construct( GlobalSettingsManager $settings ) {
		$this->settings = $settings;

		add_action( 'init', array( $this, 'configure_runtime' ), 20 );
		add_action( 'wp_enqueue_scripts', array( $this, 'dequeue_assets' ), 100 );
		add_action( 'wp_default_scripts', array( $this, 'remove_jquery_migrate' ) );
		add_action( 'wp_head', array( $this, 'output_resource_hints' ), 1 );
		add_action( 'wp_head', array( $this, 'output_font_preloads' ), 2 );
	}

	/**
	 * Configure feature flags that rely on filters and action removal.
	 *
	 * @return void
	 */
	public function configure_runtime() {
		if ( ! $this->is_enabled( 'lazy_load_images' ) ) {
			add_filter( 'wp_lazy_loading_enabled', '__return_false', 20 );
		}

		if ( $this->is_enabled( 'disable_emojis' ) ) {
			$this->disable_emojis();
		}

		if ( $this->is_enabled( 'async_images' ) ) {
			add_filter( 'wp_get_attachment_image_attributes', array( $this, 'add_async_image_attrs' ), 10, 3 );
		}

		if ( $this->is_enabled( 'disable_oembed' ) ) {
			$this->disable_oembed();
		}
	}

	/**
	 * Dequeue optional frontend assets.
	 *
	 * @return void
	 */
	public function dequeue_assets() {
		if ( is_admin() ) {
			return;
		}

		if ( $this->is_enabled( 'disable_block_css' ) ) {
			wp_dequeue_style( 'wp-block-library' );
			wp_deregister_style( 'wp-block-library' );
			wp_dequeue_style( 'wp-block-library-theme' );
			wp_deregister_style( 'wp-block-library-theme' );
			wp_dequeue_style( 'classic-theme-styles' );
			wp_deregister_style( 'classic-theme-styles' );
			wp_dequeue_style( 'global-styles' );
			wp_deregister_style( 'global-styles' );
		}

		if ( $this->is_enabled( 'disable_mediaelement' ) ) {
			wp_dequeue_style( 'wp-mediaelement' );
			wp_deregister_style( 'wp-mediaelement' );
			wp_dequeue_script( 'wp-mediaelement' );
			wp_deregister_script( 'wp-mediaelement' );
			wp_dequeue_script( 'mediaelement-vimeo' );
			wp_deregister_script( 'mediaelement-vimeo' );
			wp_dequeue_script( 'mediaelement' );
			wp_deregister_script( 'mediaelement' );
		}

		if ( $this->is_enabled( 'disable_cart_fragments' ) && function_exists( 'is_cart' ) && ! is_cart() && ! is_checkout() ) {
			wp_dequeue_script( 'wc-cart-fragments' );
			wp_deregister_script( 'wc-cart-fragments' );
		}

		if ( $this->is_enabled( 'disable_wc_block_css' ) ) {
			wp_dequeue_style( 'wc-blocks-style' );
			wp_deregister_style( 'wc-blocks-style' );
			wp_dequeue_style( 'wc-blocks-vendors-style' );
			wp_deregister_style( 'wc-blocks-vendors-style' );
		}
	}

	/**
	 * Remove jQuery Migrate from frontend dependencies.
	 *
	 * @param WP_Scripts $scripts Scripts object.
	 * @return void
	 */
	public function remove_jquery_migrate( $scripts ) {
		if ( is_admin() || ! $this->is_enabled( 'disable_jquery_migrate' ) || ! ( $scripts instanceof WP_Scripts ) ) {
			return;
		}

		if ( isset( $scripts->registered['jquery'] ) && is_array( $scripts->registered['jquery']->deps ) ) {
			$scripts->registered['jquery']->deps = array_diff( $scripts->registered['jquery']->deps, array( 'jquery-migrate' ) );
		}
	}

	/**
	 * Remove emoji scripts, styles, and content transforms.
	 *
	 * @return void
	 */
	protected function disable_emojis() {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		add_filter(
			'tiny_mce_plugins',
			function( $plugins ) {
				if ( ! is_array( $plugins ) ) {
					return array();
				}

				return array_diff( $plugins, array( 'wpemoji' ) );
			}
		);
		add_filter( 'emoji_svg_url', '__return_false' );
	}

	/**
	 * Output preconnect and dns-prefetch resource hints.
	 *
	 * @return void
	 */
	public function output_resource_hints() {
		if ( is_admin() || ! $this->is_enabled( 'preconnect_hints' ) ) {
			return;
		}

		$has_local_fonts = function_exists( 'starterkit' )
			&& starterkit()->font_embed_manager()
			&& starterkit()->font_embed_manager()->has_current_embed();

		if ( ! $has_local_fonts ) {
			echo '<link rel="dns-prefetch" href="//fonts.googleapis.com">' . "\n";
			echo '<link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>' . "\n";
			echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
		}
	}

	/**
	 * Preload locally embedded WOFF2 font files.
	 *
	 * @return void
	 */
	public function output_font_preloads() {
		if ( is_admin() || ! $this->is_enabled( 'preload_fonts' ) ) {
			return;
		}

		if ( ! function_exists( 'starterkit' ) ) {
			return;
		}

		$fem = starterkit()->font_embed_manager();

		if ( ! $fem || ! $fem->has_current_embed() ) {
			return;
		}

		$font_dir = get_template_directory() . '/assets/fonts/generated/';
		$font_uri = get_template_directory_uri() . '/assets/fonts/generated/';

		if ( ! is_dir( $font_dir ) ) {
			return;
		}

		$files = glob( $font_dir . '*.woff2' );

		if ( empty( $files ) ) {
			return;
		}

		foreach ( $files as $file ) {
			$filename = basename( $file );
			echo '<link rel="preload" href="' . esc_url( $font_uri . $filename ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
		}
	}

	/**
	 * Add decoding=async and fetchpriority to attachment images.
	 *
	 * @param array<string, string> $attr       Image attributes.
	 * @param \WP_Post              $attachment Attachment post.
	 * @param string|int[]          $size       Image size.
	 * @return array<string, string>
	 */
	public function add_async_image_attrs( $attr, $attachment, $size ) {
		if ( ! isset( $attr['decoding'] ) ) {
			$attr['decoding'] = 'async';
		}

		if ( ! isset( $attr['fetchpriority'] ) && ! isset( $attr['loading'] ) ) {
			$attr['fetchpriority'] = 'low';
		}

		return $attr;
	}

	/**
	 * Remove oEmbed discovery, scripts and REST route.
	 *
	 * @return void
	 */
	protected function disable_oembed() {
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
		remove_action( 'wp_head', 'wp_oembed_add_host_js' );
		remove_filter( 'oembed_dataparse', 'wp_filter_oembed_result', 10 );
		remove_action( 'rest_api_init', 'wp_oembed_register_route' );
		add_filter( 'embed_oembed_discover', '__return_false' );
	}

	/**
	 * Check whether a toggle is enabled.
	 *
	 * @param string $key Setting key.
	 * @return bool
	 */
	protected function is_enabled( $key ) {
		return '1' === (string) $this->settings->get( $key, '0' );
	}
}
