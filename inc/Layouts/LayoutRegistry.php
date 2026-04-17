<?php
/**
 * Registry of layout presets.
 *
 * @package StarterKit
 */

namespace StarterKit\Layouts;

class LayoutRegistry {
	/**
	 * Registered layout presets.
	 *
	 * @var array<string, array<string, array<string, mixed>>>
	 */
	protected $layouts;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->layouts = array(
			'headers'  => array(
				'header-1' => array(
					'id'          => 'header-1',
					'label'       => __( 'Header 1', 'starterkit' ),
					'description' => __( 'Simple logo left and navigation right.', 'starterkit' ),
					'template'    => 'template-parts/headers/header-1/header.php',
					'asset_base'  => 'template-parts/headers/header-1',
					'slots'       => array( 'header_top', 'header_bottom' ),
				),
				'header-2' => array(
					'id'          => 'header-2',
					'label'       => __( 'Header 2', 'starterkit' ),
					'description' => __( 'Topbar plus branded navigation row.', 'starterkit' ),
					'template'    => 'template-parts/headers/header-2/header.php',
					'asset_base'  => 'template-parts/headers/header-2',
					'slots'       => array( 'header_top', 'header_bottom', 'home_after_header' ),
				),
				'header-3' => array(
					'id'          => 'header-3',
					'label'       => __( 'Header 3', 'starterkit' ),
					'description' => __( 'Split navigation with prominent action area.', 'starterkit' ),
					'template'    => 'template-parts/headers/header-3/header.php',
					'asset_base'  => 'template-parts/headers/header-3',
					'slots'       => array( 'header_top', 'header_bottom' ),
				),
			),
			'footers'  => array(
				'footer-1' => array(
					'id'          => 'footer-1',
					'label'       => __( 'Footer 1', 'starterkit' ),
					'description' => __( 'Standard two-zone footer.', 'starterkit' ),
					'template'    => 'template-parts/footers/footer-1/footer.php',
					'asset_base'  => 'template-parts/footers/footer-1',
					'slots'       => array( 'footer_top', 'footer_bottom' ),
				),
				'footer-2' => array(
					'id'          => 'footer-2',
					'label'       => __( 'Footer 2', 'starterkit' ),
					'description' => __( 'Newsletter-led footer with stronger CTA.', 'starterkit' ),
					'template'    => 'template-parts/footers/footer-2/footer.php',
					'asset_base'  => 'template-parts/footers/footer-2',
					'slots'       => array( 'footer_top', 'footer_bottom' ),
				),
				'footer-3' => array(
					'id'          => 'footer-3',
					'label'       => __( 'Footer 3', 'starterkit' ),
					'description' => __( 'Compact footer for conversion-focused pages.', 'starterkit' ),
					'template'    => 'template-parts/footers/footer-3/footer.php',
					'asset_base'  => 'template-parts/footers/footer-3',
					'slots'       => array( 'footer_top', 'footer_bottom' ),
				),
			),
			'products' => array(
				'product-layout-1' => array(
					'id'          => 'product-layout-1',
					'label'       => __( 'Product Layout 1', 'starterkit' ),
					'description' => __( 'Classic gallery left, summary right composition.', 'starterkit' ),
					'template'    => 'template-parts/product/product-layout-1/product.php',
					'asset_base'  => 'template-parts/product/product-layout-1',
					'slots'       => array(
						'product_before_gallery',
						'product_after_gallery',
						'product_before_summary',
						'product_after_summary',
						'product_before_related',
						'product_after_related',
					),
				),
				'product-layout-2' => array(
					'id'          => 'product-layout-2',
					'label'       => __( 'Product Layout 2', 'starterkit' ),
					'description' => __( 'Stacked modern product story layout.', 'starterkit' ),
					'template'    => 'template-parts/product/product-layout-2/product.php',
					'asset_base'  => 'template-parts/product/product-layout-2',
					'slots'       => array(
						'product_before_summary',
						'product_after_summary',
						'product_before_tabs',
						'product_after_tabs',
					),
				),
				'product-layout-3' => array(
					'id'          => 'product-layout-3',
					'label'       => __( 'Product Layout 3', 'starterkit' ),
					'description' => __( 'Sticky-summary commerce layout with extended merchandising slots.', 'starterkit' ),
					'template'    => 'template-parts/product/product-layout-3/product.php',
					'asset_base'  => 'template-parts/product/product-layout-3',
					'slots'       => array(
						'product_before_gallery',
						'product_after_gallery',
						'product_before_summary',
						'product_after_summary',
						'product_before_tabs',
						'product_after_tabs',
						'product_before_related',
						'product_after_related',
					),
				),
			),
			'archives' => array(
				'archive-layout-1' => array(
					'id'          => 'archive-layout-1',
					'label'       => __( 'Archive Layout 1', 'starterkit' ),
					'description' => __( 'Standard grid with title and banner support.', 'starterkit' ),
					'template'    => 'template-parts/archive/archive-layout-1/archive.php',
					'asset_base'  => 'template-parts/archive/archive-layout-1',
					'slots'       => array(
						'archive_before_title',
						'archive_after_title',
						'archive_before_loop',
						'archive_after_loop',
						'archive_sidebar_top',
						'archive_sidebar_bottom',
					),
				),
				'archive-layout-2' => array(
					'id'          => 'archive-layout-2',
					'label'       => __( 'Archive Layout 2', 'starterkit' ),
					'description' => __( 'Grid with sidebar merchandising pockets.', 'starterkit' ),
					'template'    => 'template-parts/archive/archive-layout-2.php',
					'slots'       => array(
						'archive_before_title',
						'archive_after_title',
						'archive_before_loop',
						'archive_after_loop',
						'archive_sidebar_top',
						'archive_sidebar_bottom',
					),
				),
			),
		);
	}

	/**
	 * Return all layout groups.
	 *
	 * @return array<string, array<string, array<string, mixed>>>
	 */
	public function all() {
		return $this->layouts;
	}

	/**
	 * Get one group.
	 *
	 * @param string $group Group key.
	 * @return array<string, array<string, mixed>>
	 */
	public function group( $group ) {
		return isset( $this->layouts[ $group ] ) ? $this->layouts[ $group ] : array();
	}

	/**
	 * Find a layout preset across all groups.
	 *
	 * @param string $id Layout identifier.
	 * @return array<string, mixed>|null
	 */
	public function get( $id ) {
		foreach ( $this->layouts as $group ) {
			if ( isset( $group[ $id ] ) ) {
				return $group[ $id ];
			}
		}

		return null;
	}

	/**
	 * Resolve the group for a layout id.
	 *
	 * @param string $id Layout identifier.
	 * @return string
	 */
	public function group_for_layout( $id ) {
		foreach ( $this->layouts as $group_key => $group ) {
			if ( isset( $group[ $id ] ) ) {
				return $group_key;
			}
		}

		return '';
	}
}
