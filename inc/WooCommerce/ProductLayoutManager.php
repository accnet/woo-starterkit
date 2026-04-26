<?php
/**
 * Single product layout output.
 *
 * @package StarterKit
 */

namespace StarterKit\WooCommerce;

use StarterKit\Layouts\LayoutSettingsManager;
use StarterKit\Layouts\LayoutResolver;

class ProductLayoutManager {
	/**
	 * Layout resolver.
	 *
	 * @var LayoutResolver
	 */
	protected $layout_resolver;

	/**
	 * Layout settings manager.
	 *
	 * @var LayoutSettingsManager
	 */
	protected $layout_settings_manager;

	/**
	 * Whether the default layout wrapper is currently open.
	 *
	 * @var bool
	 */
	protected $layout_wrapper_open = false;

	/**
	 * Constructor.
	 *
	 * @param LayoutResolver $layout_resolver Resolver.
	 */
	public function __construct( LayoutResolver $layout_resolver, LayoutSettingsManager $layout_settings_manager ) {
		$this->layout_resolver         = $layout_resolver;
		$this->layout_settings_manager = $layout_settings_manager;
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
		$this->layout_wrapper_open = true;
	}

	/**
	 * Close product layout wrapper.
	 *
	 * @return void
	 */
	public function render_layout_close() {
		if ( ! $this->layout_wrapper_open ) {
			return;
		}

		echo '</div>';
		$this->layout_wrapper_open = false;
	}

	/**
	 * Open gallery column wrapper for layouts that split gallery and summary.
	 *
	 * @return void
	 */
	public function render_gallery_column_open() {
		if ( ! $this->uses_split_columns() ) {
			return;
		}

		echo '<div class="starterkit-product-layout__gallery-column">';
	}

	/**
	 * Close gallery column wrapper.
	 *
	 * @return void
	 */
	public function render_gallery_column_close() {
		if ( ! $this->uses_split_columns() ) {
			return;
		}

		echo '</div>';
	}

	/**
	 * Open summary column wrapper for layouts that split gallery and summary.
	 *
	 * @return void
	 */
	public function render_summary_column_open() {
		if ( ! $this->uses_split_columns() ) {
			return;
		}

		echo '<div class="starterkit-product-layout__summary-column">';
	}

	/**
	 * Close summary column wrapper.
	 *
	 * @return void
	 */
	public function render_summary_column_close() {
		if ( ! $this->uses_split_columns() ) {
			return;
		}

		echo '</div>';
	}

	/**
	 * Determine whether the Description tab should be shown.
	 *
	 * @return bool
	 */
	public function should_show_description_tab() {
		$settings = $this->get_current_layout_settings();

		return '1' === (string) ( isset( $settings['product_show_description_tab'] ) ? $settings['product_show_description_tab'] : '1' );
	}

	/**
	 * Determine whether current product layout uses a split gallery/summary composition.
	 *
	 * @return bool
	 */
	protected function uses_split_columns() {
		$layout = $this->layout_resolver->resolve( 'product' );

		if ( empty( $layout['id'] ) ) {
			return false;
		}

		return in_array( (string) $layout['id'], array( 'product-layout-1', 'product-layout-3' ), true );
	}

	/**
	 * Return sanitized settings for the active product layout.
	 *
	 * @return array<string, mixed>
	 */
	protected function get_current_layout_settings() {
		$layout = $this->layout_resolver->resolve( 'product' );
		$layout_id = isset( $layout['id'] ) ? (string) $layout['id'] : '';

		if ( '' === $layout_id ) {
			return array();
		}

		return $this->layout_settings_manager->get_layout_settings( $layout_id );
	}
}
