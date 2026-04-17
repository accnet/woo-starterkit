<?php
/**
 * Frontend asset registration.
 *
 * @package StarterKit
 */

namespace StarterKit\Core;

use StarterKit\Rules\DisplayRuleEvaluator;
use StarterKit\Rules\PageContextResolver;
use StarterKit\Settings\GlobalSettingsManager;
use StarterKit\Layouts\LayoutRegistry;
use StarterKit\Layouts\LayoutResolver;

class AssetManager {
	/**
	 * Settings manager.
	 *
	 * @var GlobalSettingsManager
	 */
	protected $settings;

	/**
	 * Layout registry.
	 *
	 * @var LayoutRegistry
	 */
	protected $layout_registry;

	/**
	 * Context resolver.
	 *
	 * @var PageContextResolver
	 */
	protected $context_resolver;

	/**
	 * Rule evaluator.
	 *
	 * @var DisplayRuleEvaluator
	 */
	protected $rule_evaluator;

	/**
	 * Layout resolver.
	 *
	 * @var LayoutResolver
	 */
	protected $layout_resolver;

	/**
	 * Hook registration.
	 *
	 * @param GlobalSettingsManager $settings Settings manager.
	 */
	public function __construct( GlobalSettingsManager $settings, LayoutRegistry $layout_registry, PageContextResolver $context_resolver, DisplayRuleEvaluator $rule_evaluator, LayoutResolver $layout_resolver ) {
		$this->settings         = $settings;
		$this->layout_registry  = $layout_registry;
		$this->context_resolver = $context_resolver;
		$this->rule_evaluator   = $rule_evaluator;
		$this->layout_resolver  = $layout_resolver;

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Enqueue theme assets.
	 *
	 * @return void
	 */
	public function enqueue() {
		$version  = wp_get_theme()->get( 'Version' );
		$context  = $this->context_resolver->resolve();
		$font_url = $this->google_fonts_url();

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

		$this->enqueue_active_layout_assets( $context );
		$this->enqueue_commerce_assets();
	}

	/**
	 * Enqueue CSS and JS for layouts actually used on the current request.
	 *
	 * @param array<string, mixed> $context Request context.
	 * @return void
	 */
	protected function enqueue_active_layout_assets( array $context ) {
		$layout_ids = array_filter(
			array_unique(
				array(
					(string) $this->settings->get( 'header_layout', 'header-1' ),
					(string) $this->settings->get( 'footer_layout', 'footer-1' ),
					! empty( $context['is_product'] ) ? (string) $this->settings->get( 'product_layout', 'product-layout-1' ) : '',
					! empty( $context['is_product_archive'] ) ? (string) $this->settings->get( 'archive_layout', 'archive-layout-1' ) : '',
				)
			)
		);

		foreach ( $layout_ids as $layout_id ) {
			$layout = $this->layout_registry->get( $layout_id );

			if ( empty( $layout['asset_base'] ) ) {
				continue;
			}

			$this->enqueue_asset_bundle( 'starterkit-layout-', $layout_id, (string) $layout['asset_base'] );
		}
	}

	/**
	 * Conditionally enqueue cart / checkout page assets.
	 *
	 * @return void
	 */
	protected function enqueue_commerce_assets() {
		if ( ! function_exists( 'is_cart' ) ) {
			return;
		}

		$base = get_template_directory();
		$uri  = get_template_directory_uri();

		if ( is_cart() ) {
			$css = $base . '/assets/css/cart.css';
			$js  = $base . '/assets/js/cart.js';

			if ( file_exists( $css ) ) {
				wp_enqueue_style( 'starterkit-cart', $uri . '/assets/css/cart.css', array( 'starterkit-theme' ), (string) filemtime( $css ) );
			}
			if ( file_exists( $js ) ) {
				wp_enqueue_script( 'starterkit-cart', $uri . '/assets/js/cart.js', array(), (string) filemtime( $js ), true );
			}
		}

		if ( is_checkout() ) {
			$css = $base . '/assets/css/checkout.css';
			$js  = $base . '/assets/js/checkout.js';

			if ( file_exists( $css ) ) {
				wp_enqueue_style( 'starterkit-checkout', $uri . '/assets/css/checkout.css', array( 'starterkit-theme' ), (string) filemtime( $css ) );
			}
			if ( file_exists( $js ) ) {
				wp_enqueue_script( 'starterkit-checkout', $uri . '/assets/js/checkout.js', array( 'jquery', 'wc-checkout' ), (string) filemtime( $js ), true );
			}
		}

		if ( function_exists( 'is_product' ) && is_product() ) {
			$product_layout = (string) $this->settings->get( 'product_layout', 'product-layout-1' );

			if ( 'product-layout-1' === $product_layout ) {
				wp_enqueue_script(
					'starterkit-swiper',
					'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js',
					array(),
					'11.2.6',
					true
				);
			}
		}
	}

	/**
	 * Enqueue an asset bundle when local preset files are present.
	 *
	 * @param string $handle_prefix Handle prefix.
	 * @param string $asset_id Asset identifier.
	 * @param string $asset_base Asset base path.
	 * @return void
	 */
	protected function enqueue_asset_bundle( $handle_prefix, $asset_id, $asset_base ) {
		$asset_base  = trim( $asset_base, '/' );
		$style_path  = get_template_directory() . '/' . $asset_base . '/style.css';
		$script_path = get_template_directory() . '/' . $asset_base . '/script.js';
		$script_deps = array();

		if ( 'template-parts/product/product-layout-1' === $asset_base ) {
			$script_deps[] = 'starterkit-swiper';
		}

		if ( file_exists( $style_path ) ) {
			wp_enqueue_style(
				$handle_prefix . sanitize_key( $asset_id ),
				get_template_directory_uri() . '/' . $asset_base . '/style.css',
				array( 'starterkit-theme' ),
				(string) filemtime( $style_path )
			);
		}

		if ( file_exists( $script_path ) ) {
			wp_enqueue_script(
				$handle_prefix . sanitize_key( $asset_id ),
				get_template_directory_uri() . '/' . $asset_base . '/script.js',
				$script_deps,
				(string) filemtime( $script_path ),
				true
			);
		}
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

}
