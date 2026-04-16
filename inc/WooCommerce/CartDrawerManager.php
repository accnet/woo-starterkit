<?php
/**
 * Store API powered cart drawer for WooCommerce.
 *
 * @package StarterKit
 */

namespace StarterKit\WooCommerce;

use StarterKit\Settings\GlobalSettingsManager;

class CartDrawerManager {
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

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_footer', array( $this, 'render_drawer' ), 30 );
		add_filter( 'woocommerce_add_cart_item_data', array( $this, 'fix_wootify_unique_key' ), 99, 3 );
	}

	/**
	 * Fix Wootify's non-deterministic unique_key so same variant merges quantity.
	 *
	 * @param array $cart_item_data Cart item data.
	 * @param int   $product_id    Product ID.
	 * @param int   $variation_id  Variation ID.
	 * @return array
	 */
	public function fix_wootify_unique_key( $cart_item_data, $product_id, $variation_id ) {
		if ( isset( $cart_item_data['wootify_variant_id'] ) && isset( $cart_item_data['unique_key'] ) ) {
			$cart_item_data['unique_key'] = md5(
				'wootify_' . (int) $cart_item_data['wootify_variant_id'] . '_' .
				serialize( $cart_item_data['wootify_customizer_values'] ?? array() )
			);
		}

		return $cart_item_data;
	}

	/**
	 * Enqueue cart drawer assets.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		if ( ! $this->is_available() ) {
			return;
		}

		$css_path = get_template_directory() . '/assets/css/cart-drawer.css';
		$js_path  = get_template_directory() . '/assets/js/cart-drawer.js';

		wp_enqueue_style(
			'starterkit-cart-drawer',
			get_template_directory_uri() . '/assets/css/cart-drawer.css',
			array( 'starterkit-theme' ),
			file_exists( $css_path ) ? (string) filemtime( $css_path ) : wp_get_theme()->get( 'Version' )
		);

		wp_enqueue_script(
			'starterkit-cart-drawer',
			get_template_directory_uri() . '/assets/js/cart-drawer.js',
			array( 'starterkit-commerce-store' ),
			file_exists( $js_path ) ? (string) filemtime( $js_path ) : wp_get_theme()->get( 'Version' ),
			true
		);

		wp_localize_script(
			'starterkit-cart-drawer',
			'starterkitCartDrawer',
			array(
				'cartUrl'               => function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' ),
				'checkoutUrl'           => function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/checkout/' ),
				'freeShippingThreshold' => (float) apply_filters( 'starterkit_cart_drawer_free_shipping_threshold', (float) $this->settings->get( 'free_shipping_threshold', '0' ) ),
				'upsells'               => $this->get_upsell_products_data(),
				'i18n'                  => array(
					'updating' => __( 'Updating your cart...', 'starterkit' ),
					'error'    => __( 'We could not update your cart. Please try again.', 'starterkit' ),
				),
			)
		);
	}

	/**
	 * Render drawer shell.
	 *
	 * @return void
	 */
	public function render_drawer() {
		if ( ! $this->is_available() ) {
			return;
		}

		echo '<div id="starterkit-cart-drawer" class="starterkit-cart-drawer" aria-hidden="true">';
		echo '<button type="button" class="starterkit-cart-drawer__overlay" data-cart-drawer-close aria-label="' . esc_attr__( 'Close cart drawer', 'starterkit' ) . '"></button>';
		echo '<div class="starterkit-cart-drawer__toast" aria-live="polite" aria-atomic="true"></div>';
		echo '<aside class="starterkit-cart-drawer__panel" aria-label="' . esc_attr__( 'Shopping cart', 'starterkit' ) . '">';
		echo '<div class="starterkit-cart-drawer__inner" data-cart-drawer-root>';
		echo '<div class="starterkit-cart-drawer__body"><div class="starterkit-cart-drawer__progress"><p>' . esc_html__( 'Loading your cart...', 'starterkit' ) . '</p></div></div>';
		echo '</div>';
		echo '</aside>';
		echo '</div>';
	}

	/**
	 * Return upsell product candidates.
	 *
	 * @return array<int, \WC_Product>
	 */
	protected function get_upsell_products() {
		$exclude = array();

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			if ( ! empty( $cart_item['product_id'] ) ) {
				$exclude[] = (int) $cart_item['product_id'];
			}
		}

		$args = array(
			'status'  => 'publish',
			'limit'   => 2,
			'orderby' => 'date',
			'order'   => 'DESC',
			'exclude' => array_unique( $exclude ),
		);

		$products = function_exists( 'wc_get_products' ) ? wc_get_products( array_merge( $args, array( 'featured' => true ) ) ) : array();

		if ( empty( $products ) && function_exists( 'wc_get_products' ) ) {
			$products = wc_get_products( $args );
		}

		return is_array( $products ) ? $products : array();
	}

	/**
	 * Normalize upsells for the custom drawer frontend.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	protected function get_upsell_products_data() {
		$products = array();

		foreach ( $this->get_upsell_products() as $product ) {
			$products[] = array(
				'id'          => (int) $product->get_id(),
				'name'        => $product->get_name(),
				'permalink'   => $product->get_permalink(),
				'image_html'  => $product->get_image( 'woocommerce_thumbnail' ),
				'price_html'  => $product->get_price_html(),
				'add_to_text' => $product->add_to_cart_text(),
				'can_add'     => $product->is_purchasable() && $product->is_in_stock() && $product->supports( 'ajax_add_to_cart' ) && 'simple' === $product->get_type(),
			);
		}

		return $products;
	}

	/**
	 * Determine if WooCommerce cart APIs are available.
	 *
	 * @return bool
	 */
	protected function is_available() {
		return class_exists( 'WooCommerce' ) && function_exists( 'WC' ) && WC()->cart;
	}
}
