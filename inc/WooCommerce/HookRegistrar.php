<?php
/**
 * Register WooCommerce hooks used by the slot system.
 *
 * @package StarterKit
 */

namespace StarterKit\WooCommerce;

class HookRegistrar {
	/**
	 * Product layout manager.
	 *
	 * @var ProductLayoutManager
	 */
	protected $product_layout_manager;

	/**
	 * Archive layout manager.
	 *
	 * @var ArchiveLayoutManager
	 */
	protected $archive_layout_manager;

	/**
	 * Constructor.
	 *
	 * @param ProductLayoutManager $product_layout_manager Product layout manager.
	 * @param ArchiveLayoutManager $archive_layout_manager Archive layout manager.
	 */
	public function __construct( ProductLayoutManager $product_layout_manager, ArchiveLayoutManager $archive_layout_manager ) {
		$this->product_layout_manager = $product_layout_manager;
		$this->archive_layout_manager = $archive_layout_manager;

		add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );
		add_filter( 'woocommerce_breadcrumb_defaults', array( $this, 'filter_breadcrumb_defaults' ) );
		add_action( 'woocommerce_before_single_product_summary', array( $this->product_layout_manager, 'render_gallery_column_open' ), 2 );
		add_action( 'woocommerce_before_single_product_summary', array( $this->product_layout_manager, 'render_layout_open' ), 1 );
		add_action( 'woocommerce_before_single_product_summary', array( $this->product_layout_manager, 'render_gallery_column_close' ), 30 );
		add_action( 'woocommerce_single_product_summary', array( $this->product_layout_manager, 'render_summary_column_open' ), 1 );
		add_action( 'woocommerce_after_single_product', array( $this->product_layout_manager, 'render_layout_close' ), 99 );
		add_action( 'woocommerce_single_product_summary', array( $this->product_layout_manager, 'render_summary_column_close' ), 36 );

		add_action( 'woocommerce_before_main_content', array( $this->archive_layout_manager, 'render_layout_open' ), 5 );
		add_action( 'woocommerce_after_main_content', array( $this->archive_layout_manager, 'render_layout_close' ), 50 );
		add_filter( 'loop_shop_per_page', array( $this, 'filter_loop_shop_per_page' ), 20 );
		add_filter( 'loop_shop_columns', array( $this, 'filter_loop_shop_columns' ), 20 );
		add_action( 'wp_enqueue_scripts', array( $this, 'dequeue_woocommerce_styles' ), 100 );

		add_action( 'wp_ajax_starterkit_apply_coupon', array( $this, 'ajax_apply_coupon' ) );
		add_action( 'wp_ajax_nopriv_starterkit_apply_coupon', array( $this, 'ajax_apply_coupon' ) );
	}

	/**
	 * Force 25 products per page when archive layout 1 is active on Woo archives.
	 *
	 * @param int $per_page Products per page.
	 * @return int
	 */
	public function filter_loop_shop_per_page( $per_page ) {
		$settings       = get_option( 'starterkit_global_settings', array() );
		$archive_layout = isset( $settings['archive_layout'] ) ? (string) $settings['archive_layout'] : 'archive-layout-1';

		if ( 'archive-layout-1' !== $archive_layout ) {
			return (int) $per_page;
		}

		if ( function_exists( 'is_shop' ) && is_shop() ) {
			return 25;
		}

		if ( function_exists( 'is_product_category' ) && is_product_category() ) {
			return 25;
		}

		if ( function_exists( 'is_product_tag' ) && is_product_tag() ) {
			return 25;
		}

		return (int) $per_page;
	}

	/**
	 * Force 5 columns when archive layout 1 is active on Woo archives.
	 *
	 * @param int $columns Product columns.
	 * @return int
	 */
	public function filter_loop_shop_columns( $columns ) {
		$settings       = get_option( 'starterkit_global_settings', array() );
		$archive_layout = isset( $settings['archive_layout'] ) ? (string) $settings['archive_layout'] : 'archive-layout-1';

		if ( 'archive-layout-1' !== $archive_layout ) {
			return (int) $columns;
		}

		if ( function_exists( 'is_shop' ) && is_shop() ) {
			return 5;
		}

		if ( function_exists( 'is_product_category' ) && is_product_category() ) {
			return 5;
		}

		if ( function_exists( 'is_product_tag' ) && is_product_tag() ) {
			return 5;
		}

		return (int) $columns;
	}

	/**
	 * Wrap WooCommerce breadcrumb in the theme container.
	 *
	 * @param array<string, string> $defaults Breadcrumb defaults.
	 * @return array<string, string>
	 */
	public function filter_breadcrumb_defaults( array $defaults ) {
		$defaults['wrap_before'] = '<nav class="woocommerce-breadcrumb" aria-label="Breadcrumb">';
		$defaults['wrap_after']  = '</nav>';

		return $defaults;
	}

	/**
	 * Remove default WooCommerce frontend styles so the theme fully controls commerce UI.
	 *
	 * @return void
	 */
	public function dequeue_woocommerce_styles() {
		$settings                = get_option( 'starterkit_global_settings', array() );
		$custom_cart_page        = isset( $settings['custom_cart_page'] ) ? (string) $settings['custom_cart_page'] : '1';
		$custom_checkout_page    = isset( $settings['custom_checkout_page'] ) ? (string) $settings['custom_checkout_page'] : '1';
		$preserve_cart_styles    = function_exists( 'is_cart' ) && is_cart() && '1' !== $custom_cart_page;
		$preserve_checkout_styles = function_exists( 'is_checkout' ) && is_checkout() && '1' !== $custom_checkout_page;

		if ( $preserve_cart_styles || $preserve_checkout_styles ) {
			return;
		}

		wp_dequeue_style( 'woocommerce-general' );
		wp_deregister_style( 'woocommerce-general' );
		wp_dequeue_style( 'woocommerce-layout' );
		wp_deregister_style( 'woocommerce-layout' );
		wp_dequeue_style( 'woocommerce-smallscreen' );
		wp_deregister_style( 'woocommerce-smallscreen' );
	}

	/**
	 * Apply coupon via AJAX (checkout sidebar discount code).
	 *
	 * @return void
	 */
	public function ajax_apply_coupon() {
		check_ajax_referer( 'update-order-review', 'security', false );

		$coupon_code = isset( $_POST['coupon_code'] ) ? wc_format_coupon_code( wp_unslash( $_POST['coupon_code'] ) ) : '';

		if ( empty( $coupon_code ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a coupon code.', 'starterkit' ) ) );
		}

		if ( ! WC()->cart->has_discount( $coupon_code ) ) {
			WC()->cart->apply_coupon( $coupon_code );
		}

		wp_send_json_success();
	}
}
