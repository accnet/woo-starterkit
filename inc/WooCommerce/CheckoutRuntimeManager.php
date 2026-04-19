<?php
/**
 * Runtime data for the custom checkout frontend.
 *
 * @package StarterKit
 */

namespace StarterKit\WooCommerce;

use StarterKit\Settings\GlobalSettingsManager;

class CheckoutRuntimeManager {
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

		add_action( 'wp_enqueue_scripts', array( $this, 'localize' ), 20 );
	}

	/**
	 * Localize checkout runtime data after checkout assets are enqueued.
	 *
	 * @return void
	 */
	public function localize() {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || '1' !== (string) $this->settings->get( 'custom_checkout_page', '1' ) ) {
			return;
		}

		if ( ! wp_script_is( 'starterkit-checkout', 'enqueued' ) ) {
			return;
		}

		wp_localize_script(
			'starterkit-checkout',
			'starterkitCheckoutRuntime',
			array(
				'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
				'wcAjaxUrl'        => function_exists( 'WC_AJAX' ) ? \WC_AJAX::get_endpoint( '%%endpoint%%' ) : '',
				'applyCouponNonce' => wp_create_nonce( 'update-order-review' ),
				'requiresShipping' => function_exists( 'WC' ) && WC()->cart ? WC()->cart->needs_shipping() : false,
				'shippingEnabled'  => function_exists( 'WC' ) && WC()->cart ? WC()->cart->needs_shipping() : false,
				'orderReceived'    => function_exists( 'is_order_received_page' ) && is_order_received_page(),
				'customCheckout'   => true,
				'labels'           => array(
					'applyCoupon' => __( 'Apply', 'starterkit' ),
					'applying'    => __( 'Applying...', 'starterkit' ),
				),
			)
		);
	}
}
