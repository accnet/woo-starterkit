<?php
/**
 * Archive layout wrapper output.
 *
 * @package StarterKit
 */

namespace StarterKit\WooCommerce;

use StarterKit\Layouts\LayoutResolver;

class ArchiveLayoutManager {
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
	 * Open archive wrapper.
	 *
	 * @return void
	 */
	public function render_layout_open() {
		$layout = $this->layout_resolver->resolve( 'archive' );
		$class  = $layout ? $layout['id'] : 'archive-layout-default';

		echo '<div class="starterkit-archive-layout ' . esc_attr( $class ) . '">';
	}

	/**
	 * Close archive wrapper.
	 *
	 * @return void
	 */
	public function render_layout_close() {
		echo '</div>';
	}
}
