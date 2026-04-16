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

		add_filter( 'woocommerce_breadcrumb_defaults', array( $this, 'filter_breadcrumb_defaults' ) );
		add_action( 'woocommerce_before_single_product_summary', array( $this, 'product_before_gallery' ), 5 );
		add_action( 'woocommerce_before_single_product_summary', array( $this->product_layout_manager, 'render_layout_open' ), 1 );
		add_action( 'woocommerce_before_single_product_summary', array( $this, 'product_after_gallery' ), 25 );
		add_action( 'woocommerce_after_single_product', array( $this->product_layout_manager, 'render_layout_close' ), 99 );
		add_action( 'woocommerce_single_product_summary', array( $this, 'product_before_summary' ), 4 );
		add_action( 'woocommerce_single_product_summary', array( $this, 'product_after_summary' ), 35 );
		add_action( 'woocommerce_after_single_product_summary', array( $this, 'product_before_tabs' ), 4 );
		add_action( 'woocommerce_after_single_product_summary', array( $this, 'product_after_tabs' ), 15 );
		add_action( 'woocommerce_after_single_product_summary', array( $this, 'product_before_related' ), 18 );
		add_action( 'woocommerce_after_single_product_summary', array( $this, 'product_after_related' ), 30 );

		add_action( 'woocommerce_before_main_content', array( $this->archive_layout_manager, 'render_layout_open' ), 5 );
		add_action( 'woocommerce_after_main_content', array( $this->archive_layout_manager, 'render_layout_close' ), 50 );
		add_action( 'woocommerce_before_shop_loop', array( $this, 'archive_before_loop' ), 5 );
		add_action( 'woocommerce_after_shop_loop', array( $this, 'archive_after_loop' ), 50 );
	}

	/**
	 * Render single-product slot.
	 *
	 * @return void
	 */
	public function product_before_gallery() {
		starterkit_render_slot( 'product_before_gallery' );
	}

	/**
	 * Render single-product slot.
	 *
	 * @return void
	 */
	public function product_before_summary() {
		starterkit_render_slot( 'product_before_summary' );
	}

	/**
	 * Render single-product slot.
	 *
	 * @return void
	 */
	public function product_after_gallery() {
		starterkit_render_slot( 'product_after_gallery' );
	}

	/**
	 * Render single-product slot.
	 *
	 * @return void
	 */
	public function product_after_summary() {
		starterkit_render_slot( 'product_after_summary' );
	}

	/**
	 * Render single-product slot.
	 *
	 * @return void
	 */
	public function product_before_tabs() {
		starterkit_render_slot( 'product_before_tabs' );
	}

	/**
	 * Render single-product slot.
	 *
	 * @return void
	 */
	public function product_after_tabs() {
		starterkit_render_slot( 'product_after_tabs' );
	}

	/**
	 * Render single-product slot.
	 *
	 * @return void
	 */
	public function product_before_related() {
		starterkit_render_slot( 'product_before_related' );
	}

	/**
	 * Render single-product slot.
	 *
	 * @return void
	 */
	public function product_after_related() {
		starterkit_render_slot( 'product_after_related' );
	}

	/**
	 * Render archive slot.
	 *
	 * @return void
	 */
	public function archive_before_loop() {
		starterkit_render_slot( 'archive_before_loop' );
	}

	/**
	 * Render archive slot.
	 *
	 * @return void
	 */
	public function archive_after_loop() {
		starterkit_render_slot( 'archive_after_loop' );
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

}
