<?php
/**
 * Route cart and checkout requests through theme-owned shells.
 *
 * @package StarterKit
 */

namespace StarterKit\WooCommerce;

use StarterKit\Settings\GlobalSettingsManager;

class CommerceTemplateManager {
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

		add_filter( 'template_include', array( $this, 'filter_template_include' ), 20 );
		add_filter( 'woocommerce_locate_template', array( $this, 'filter_woocommerce_template' ), 10, 3 );
	}

	/**
	 * Swap the page template for cart/checkout routes when theme shells are enabled.
	 *
	 * @param string $template Resolved template path.
	 * @return string
	 */
	public function filter_template_include( $template ) {
		if ( $this->should_use_cart_shell() ) {
			$cart_template = get_template_directory() . '/woocommerce/cart-page.php';

			if ( file_exists( $cart_template ) ) {
				return $cart_template;
			}
		}

		if ( $this->should_use_checkout_shell() ) {
			$checkout_template = get_template_directory() . '/woocommerce/checkout-page.php';

			if ( file_exists( $checkout_template ) ) {
				return $checkout_template;
			}
		}

		return $template;
	}

	/**
	 * Disable theme cart/checkout partial overrides when the custom shell is turned off.
	 *
	 * @param string $template Resolved template path.
	 * @param string $template_name WooCommerce template name.
	 * @param string $template_path WooCommerce template path.
	 * @return string
	 */
	public function filter_woocommerce_template( $template, $template_name, $template_path ) {
		unset( $template_path );

		$cart_templates = array(
			'cart/cart.php',
			'cart/cart-empty.php',
		);

		if ( in_array( $template_name, $cart_templates, true ) && ! $this->is_custom_cart_enabled() ) {
			return $this->get_default_woocommerce_template( $template_name, $template );
		}

		if ( 'checkout/form-checkout.php' === $template_name && ! $this->is_custom_checkout_enabled() ) {
			return $this->get_default_woocommerce_template( $template_name, $template );
		}

		return $template;
	}

	/**
	 * Resolve the plugin template path as a fallback when theme overrides are disabled.
	 *
	 * @param string $template_name WooCommerce template name.
	 * @param string $fallback Current resolved template.
	 * @return string
	 */
	protected function get_default_woocommerce_template( $template_name, $fallback ) {
		if ( ! function_exists( 'WC' ) || ! WC() ) {
			return $fallback;
		}

		$default_template = trailingslashit( WC()->plugin_path() ) . 'templates/' . ltrim( $template_name, '/' );

		return file_exists( $default_template ) ? $default_template : $fallback;
	}

	/**
	 * Determine whether the theme should own the cart route.
	 *
	 * @return bool
	 */
	protected function should_use_cart_shell() {
		return function_exists( 'is_cart' ) && is_cart() && $this->is_custom_cart_enabled();
	}

	/**
	 * Determine whether the theme should own the checkout route, including endpoints.
	 *
	 * @return bool
	 */
	protected function should_use_checkout_shell() {
		return function_exists( 'is_checkout' ) && is_checkout() && $this->is_custom_checkout_enabled();
	}

	/**
	 * Check whether the custom cart page is enabled.
	 *
	 * @return bool
	 */
	protected function is_custom_cart_enabled() {
		return '1' === (string) $this->settings->get( 'custom_cart_page', '1' );
	}

	/**
	 * Check whether the custom checkout page is enabled.
	 *
	 * @return bool
	 */
	protected function is_custom_checkout_enabled() {
		return '1' === (string) $this->settings->get( 'custom_checkout_page', '1' );
	}
}
