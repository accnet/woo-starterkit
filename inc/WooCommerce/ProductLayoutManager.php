<?php
/**
 * Single product layout output.
 *
 * @package StarterKit
 */

namespace StarterKit\WooCommerce;

use StarterKit\Layouts\LayoutResolver;

class ProductLayoutManager {
	/**
	 * Layout resolver.
	 *
	 * @var LayoutResolver
	 */
	protected $layout_resolver;

	/**
	 * Constructor.
	 *
	 * @param LayoutResolver $layout_resolver Resolver.
	 */
	public function __construct( LayoutResolver $layout_resolver ) {
		$this->layout_resolver = $layout_resolver;
	}

	/**
	 * Render product layout wrapper.
	 *
	 * @return void
	 */
	public function render_layout_open() {
		$layout = $this->layout_resolver->resolve( 'product' );
		$class  = $layout ? $layout['id'] : 'product-layout-default';

		echo '<div class="starterkit-product-layout ' . esc_attr( $class ) . '">';
	}

	/**
	 * Close product layout wrapper.
	 *
	 * @return void
	 */
	public function render_layout_close() {
		echo '</div>';
	}
}
