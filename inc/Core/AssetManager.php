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
		$theme_css = get_template_directory() . '/assets/css/theme.css';

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
			AssetVersion::for_file( $theme_css, $version )
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
		foreach ( $this->get_active_layouts_for_request( $context ) as $layout ) {
			$layout_id = isset( $layout['id'] ) ? (string) $layout['id'] : '';

			if ( '' === $layout_id || empty( $layout['asset_base'] ) ) {
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
		if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'is_cart' ) ) {
			return;
		}

		$base = get_template_directory();
		$uri  = get_template_directory_uri();

		if ( is_cart() && '1' === (string) $this->settings->get( 'custom_cart_page', '1' ) ) {
			$css = $base . '/assets/css/cart.css';
			$js  = $base . '/assets/js/cart.js';

			if ( file_exists( $css ) ) {
				wp_enqueue_style( 'starterkit-cart', $uri . '/assets/css/cart.css', array( 'starterkit-theme' ), AssetVersion::for_file( $css ) );
			}
			if ( file_exists( $js ) ) {
				wp_enqueue_script( 'starterkit-cart', $uri . '/assets/js/cart.js', array(), AssetVersion::for_file( $js ), true );
			}
		}

		if ( is_checkout() && '1' === (string) $this->settings->get( 'custom_checkout_page', '1' ) ) {
			$css = $base . '/assets/css/checkout.css';
			$js  = $base . '/assets/js/checkout.js';
			$is_order_received = function_exists( 'is_order_received_page' ) && is_order_received_page();

			if ( file_exists( $css ) ) {
				wp_enqueue_style( 'starterkit-checkout', $uri . '/assets/css/checkout.css', array( 'starterkit-theme' ), AssetVersion::for_file( $css ) );
			}
			if ( ! $is_order_received && file_exists( $js ) ) {
				wp_enqueue_script( 'starterkit-checkout', $uri . '/assets/js/checkout.js', array( 'jquery', 'wc-checkout' ), AssetVersion::for_file( $js ), true );
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
		$script_deps = $this->script_dependencies_for_asset_base( $asset_base );

		if ( file_exists( $style_path ) ) {
			wp_enqueue_style(
				$handle_prefix . sanitize_key( $asset_id ),
				get_template_directory_uri() . '/' . $asset_base . '/style.css',
				array( 'starterkit-theme' ),
				AssetVersion::for_file( $style_path )
			);
		}

		if ( file_exists( $script_path ) ) {
			wp_enqueue_script(
				$handle_prefix . sanitize_key( $asset_id ),
				get_template_directory_uri() . '/' . $asset_base . '/script.js',
				$script_deps,
				AssetVersion::for_file( $script_path ),
				true
			);
		}
	}

	/**
	 * Return layouts active on the current request.
	 *
	 * @param array<string, mixed> $context Request context.
	 * @return array<int, array<string, mixed>>
	 */
	protected function get_active_layouts_for_request( array $context ) {
		$layouts = array(
			$this->layout_resolver->resolve( 'header' ),
			$this->layout_resolver->resolve( 'footer' ),
		);

		if ( ! empty( $context['is_product'] ) ) {
			$layouts[] = $this->layout_resolver->resolve( 'product' );
		}

		if ( ! empty( $context['is_product_archive'] ) ) {
			$layouts[] = $this->layout_resolver->resolve( 'archive' );
		}

		$unique = array();

		foreach ( $layouts as $layout ) {
			$layout_id = isset( $layout['id'] ) ? (string) $layout['id'] : '';

			if ( '' === $layout_id ) {
				continue;
			}

			$unique[ $layout_id ] = $layout;
		}

		return array_values( $unique );
	}

	/**
	 * Return JS dependencies for a layout asset bundle.
	 *
	 * @param string $asset_base Asset base path.
	 * @return string[]
	 */
	protected function script_dependencies_for_asset_base( $asset_base ) {
		$deps = array();

		if ( 'template-parts/product/product-layout-1' === $asset_base ) {
			$this->register_swiper_dependency();
			$deps[] = 'starterkit-swiper';
		}

		return $deps;
	}

	/**
	 * Register the shared Swiper dependency.
	 *
	 * @return void
	 */
	public function register_swiper_dependency() {
		if ( wp_script_is( 'starterkit-swiper', 'registered' ) ) {
			return;
		}

		wp_register_script(
			'starterkit-swiper',
			'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js',
			array(),
			'11.2.6',
			true
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

}
