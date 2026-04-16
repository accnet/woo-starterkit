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
			'masters'  => array(
				'master-default' => array(
					'id'          => 'master-default',
					'label'       => __( 'Default Master Layout', 'starterkit' ),
					'description' => __( 'Balanced container with standard content rhythm.', 'starterkit' ),
					'template'    => 'template-parts/archive/master-default.php',
					'slots'       => array( 'home_after_header', 'home_before_content', 'home_after_content' ),
				),
				'master-wide'    => array(
					'id'          => 'master-wide',
					'label'       => __( 'Wide Story Layout', 'starterkit' ),
					'description' => __( 'Wider canvas suited to editorial landing pages.', 'starterkit' ),
					'template'    => 'template-parts/archive/master-wide.php',
					'slots'       => array( 'home_after_header', 'home_before_content', 'home_after_content', 'home_before_footer' ),
				),
			),
			'headers'  => array(
				'header-1' => array(
					'id'          => 'header-1',
					'label'       => __( 'Header 1', 'starterkit' ),
					'description' => __( 'Simple logo left and navigation right.', 'starterkit' ),
					'template'    => 'template-parts/headers/header-1.php',
					'slots'       => array( 'header_top', 'header_bottom' ),
				),
				'header-2' => array(
					'id'          => 'header-2',
					'label'       => __( 'Header 2', 'starterkit' ),
					'description' => __( 'Topbar plus branded navigation row.', 'starterkit' ),
					'template'    => 'template-parts/headers/header-2.php',
					'slots'       => array( 'header_top', 'header_bottom', 'home_after_header' ),
				),
				'header-3' => array(
					'id'          => 'header-3',
					'label'       => __( 'Header 3', 'starterkit' ),
					'description' => __( 'Split navigation with prominent action area.', 'starterkit' ),
					'template'    => 'template-parts/headers/header-3.php',
					'slots'       => array( 'header_top', 'header_bottom' ),
				),
			),
			'footers'  => array(
				'footer-1' => array(
					'id'          => 'footer-1',
					'label'       => __( 'Footer 1', 'starterkit' ),
					'description' => __( 'Standard two-zone footer.', 'starterkit' ),
					'template'    => 'template-parts/footers/footer-1.php',
					'slots'       => array( 'footer_top', 'footer_bottom' ),
				),
				'footer-2' => array(
					'id'          => 'footer-2',
					'label'       => __( 'Footer 2', 'starterkit' ),
					'description' => __( 'Newsletter-led footer with stronger CTA.', 'starterkit' ),
					'template'    => 'template-parts/footers/footer-2.php',
					'slots'       => array( 'footer_top', 'footer_bottom' ),
				),
				'footer-3' => array(
					'id'          => 'footer-3',
					'label'       => __( 'Footer 3', 'starterkit' ),
					'description' => __( 'Compact footer for conversion-focused pages.', 'starterkit' ),
					'template'    => 'template-parts/footers/footer-3.php',
					'slots'       => array( 'footer_top', 'footer_bottom' ),
				),
			),
			'products' => array(
				'product-layout-1' => array(
					'id'          => 'product-layout-1',
					'label'       => __( 'Product Layout 1', 'starterkit' ),
					'description' => __( 'Classic gallery left, summary right composition.', 'starterkit' ),
					'template'    => 'template-parts/product/product-layout-1.php',
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
					'template'    => 'template-parts/product/product-layout-2.php',
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
					'template'    => 'template-parts/product/product-layout-3.php',
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
					'template'    => 'template-parts/archive/archive-layout-1.php',
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
