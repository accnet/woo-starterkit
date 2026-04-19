<?php
/**
 * Builder preset schema registry.
 *
 * @package StarterKit
 */

namespace StarterKit\ThemeBuilder;

use StarterKit\Layouts\LayoutResolver;

class PresetSchemaRegistry {
	/**
	 * Layout resolver.
	 *
	 * @var LayoutResolver
	 */
	protected $layout_resolver;

	/**
	 * Constructor.
	 *
	 * @param LayoutResolver $layout_resolver Layout resolver.
	 */
	public function __construct( LayoutResolver $layout_resolver ) {
		$this->layout_resolver = $layout_resolver;
	}

	/**
	 * Return all builder preset schemas.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function all() {
		return array(
			'header-1'         => $this->header_schema(
				'header-1',
				array(
					'header_top'    => array(
						'label'            => __( 'Header Top', 'starterkit' ),
						'allowed_elements' => array( 'topbar', 'topbar-1', 'promo-banner', 'trust-badges' ),
					),
					'header_bottom' => array(
						'label'            => __( 'Header Bottom', 'starterkit' ),
						'allowed_elements' => array( 'promo-banner', 'trust-badges', 'payment-icons' ),
					),
				)
			),
			'header-2'         => $this->header_schema(
				'header-2',
				array(
					'header_top'       => array(
						'label'            => __( 'Header Top', 'starterkit' ),
						'allowed_elements' => array( 'topbar', 'topbar-1', 'promo-banner' ),
					),
					'header_bottom'    => array(
						'label'            => __( 'Header Bottom', 'starterkit' ),
						'allowed_elements' => array( 'promo-banner', 'trust-badges', 'payment-icons' ),
					),
					'home_after_header' => array(
						'label'            => __( 'After Header', 'starterkit' ),
						'allowed_elements' => array( 'promo-banner', 'trust-badges' ),
					),
				)
			),
			'header-3'         => $this->header_schema(
				'header-3',
				array(
					'header_top'    => array(
						'label'            => __( 'Header Top', 'starterkit' ),
						'allowed_elements' => array( 'topbar', 'topbar-1', 'promo-banner' ),
					),
					'header_bottom' => array(
						'label'            => __( 'Header Bottom', 'starterkit' ),
						'allowed_elements' => array( 'promo-banner', 'trust-badges', 'payment-icons' ),
					),
				)
			),
			'footer-1'         => $this->footer_schema( 'footer-1' ),
			'footer-2'         => $this->footer_schema( 'footer-2' ),
			'footer-3'         => $this->footer_schema( 'footer-3' ),
			'product-layout-1' => $this->product_schema(
				'product-layout-1',
				array(
					'product_before_gallery' => array( 'review-summary', 'countdown', 'trust-badge', 'shipping-info' ),
					'product_after_gallery'  => array( 'review-summary', 'countdown', 'trust-badge', 'shipping-info' ),
					'product_before_summary' => array( 'review-summary', 'countdown', 'trust-badge', 'shipping-info', 'guarantee' ),
					'product_after_summary'  => array( 'review-summary', 'countdown', 'trust-badge', 'shipping-info', 'faq', 'guarantee' ),
					'product_before_related' => array( 'faq', 'guarantee', 'trust-badge' ),
					'product_after_related'  => array( 'faq', 'guarantee', 'trust-badge' ),
				)
			),
			'product-layout-2' => $this->product_schema(
				'product-layout-2',
				array(
					'product_before_summary' => array( 'review-summary', 'countdown', 'trust-badge', 'shipping-info', 'guarantee' ),
					'product_after_summary'  => array( 'faq', 'guarantee', 'trust-badge' ),
					'product_before_tabs'    => array( 'faq', 'guarantee', 'shipping-info' ),
					'product_after_tabs'     => array( 'faq', 'guarantee', 'trust-badge' ),
				)
			),
			'product-layout-3' => $this->product_schema(
				'product-layout-3',
				array(
					'product_before_gallery' => array( 'review-summary', 'countdown', 'trust-badge', 'shipping-info' ),
					'product_after_gallery'  => array( 'review-summary', 'countdown', 'trust-badge', 'shipping-info' ),
					'product_before_summary' => array( 'review-summary', 'countdown', 'trust-badge', 'shipping-info', 'guarantee' ),
					'product_after_summary'  => array( 'review-summary', 'countdown', 'trust-badge', 'shipping-info', 'faq', 'guarantee' ),
					'product_before_tabs'    => array( 'faq', 'shipping-info', 'guarantee' ),
					'product_after_tabs'     => array( 'faq', 'guarantee', 'trust-badge' ),
					'product_before_related' => array( 'faq', 'guarantee', 'trust-badge' ),
					'product_after_related'  => array( 'faq', 'guarantee', 'trust-badge' ),
				)
			),
			'archive-layout-1' => $this->archive_schema( 'archive-layout-1' ),
			'archive-layout-2' => $this->archive_schema( 'archive-layout-2' ),
		);
	}

	/**
	 * Get a preset by id.
	 *
	 * @param string $preset_id Preset id.
	 * @return array<string, mixed>|null
	 */
	public function get_preset( $preset_id ) {
		$all = $this->all();

		return isset( $all[ $preset_id ] ) ? $all[ $preset_id ] : null;
	}

	/**
	 * Return presets for a context.
	 *
	 * @param string $context Builder context.
	 * @return array<string, array<string, mixed>>
	 */
	public function get_presets_by_context( $context ) {
		$schemas = array();

		foreach ( $this->all() as $preset_id => $schema ) {
			if ( isset( $schema['context'] ) && $context === $schema['context'] ) {
				$schemas[ $preset_id ] = $schema;
			}
		}

		return $schemas;
	}

	/**
	 * Get the zone schema for a preset.
	 *
	 * @param string $preset_id Preset id.
	 * @param string $zone_id Zone id.
	 * @return array<string, mixed>|null
	 */
	public function get_zone( $preset_id, $zone_id ) {
		$preset = $this->get_preset( $preset_id );

		if ( empty( $preset['zones'] ) ) {
			return null;
		}

		foreach ( $preset['zones'] as $zone ) {
			if ( isset( $zone['id'] ) && $zone_id === $zone['id'] ) {
				return $zone;
			}
		}

		return null;
	}

	/**
	 * Return active preset ids grouped for builder contexts.
	 *
	 * @return array<string, mixed>
	 */
	public function resolve_active_preset_ids() {
		$header  = $this->layout_resolver->resolve( 'header' );
		$footer  = $this->layout_resolver->resolve( 'footer' );
		$product = $this->layout_resolver->resolve( 'product' );
		$archive = $this->layout_resolver->resolve( 'archive' );

		return array(
			BuilderContext::MASTER  => array(
				'header' => isset( $header['id'] ) ? $header['id'] : 'header-1',
				'footer' => isset( $footer['id'] ) ? $footer['id'] : 'footer-1',
			),
			BuilderContext::PRODUCT => isset( $product['id'] ) ? $product['id'] : 'product-layout-1',
			BuilderContext::ARCHIVE => isset( $archive['id'] ) ? $archive['id'] : 'archive-layout-1',
		);
	}

	/**
	 * Return active schemas for the requested builder context.
	 *
	 * @param string $context Builder context.
	 * @return array<string, array<string, mixed>>
	 */
	public function get_active_schemas( $context ) {
		$active  = $this->resolve_active_preset_ids();
		$schemas = array();

		if ( BuilderContext::MASTER === $context ) {
			foreach ( array( 'header', 'footer' ) as $part ) {
				$preset_id = isset( $active[ BuilderContext::MASTER ][ $part ] ) ? $active[ BuilderContext::MASTER ][ $part ] : '';
				$schema    = $this->get_preset( $preset_id );

				if ( $schema ) {
					$schemas[ $preset_id ] = $schema;
				}
			}

			return $schemas;
		}

		$preset_id = isset( $active[ $context ] ) ? $active[ $context ] : '';
		$schema    = $this->get_preset( $preset_id );

		if ( $schema ) {
			$schemas[ $preset_id ] = $schema;
		}

		return $schemas;
	}

	/**
	 * Find the active preset that owns a zone in the current builder context.
	 *
	 * @param string $context Builder context.
	 * @param string $zone_id Zone id.
	 * @return array<string, mixed>|null
	 */
	public function find_active_zone( $context, $zone_id ) {
		foreach ( $this->get_active_schemas( $context ) as $preset_id => $schema ) {
			$zone = $this->get_zone( $preset_id, $zone_id );

			if ( $zone ) {
				$zone['preset_id'] = $preset_id;

				return $zone;
			}
		}

		return null;
	}

	/**
	 * Build a standard header schema.
	 *
	 * @param string                                $preset_id Preset id.
	 * @param array<string, array<string, string[]>> $zones Zone definitions.
	 * @return array<string, mixed>
	 */
	protected function header_schema( $preset_id, array $zones ) {
		return array(
			'id'      => $preset_id,
			'context' => BuilderContext::MASTER,
			'part'    => 'header',
			'template' => 'header',
			'zones'   => $this->build_zones( $zones ),
		);
	}

	/**
	 * Build a footer schema.
	 *
	 * @param string $preset_id Preset id.
	 * @return array<string, mixed>
	 */
	protected function footer_schema( $preset_id ) {
		return array(
			'id'      => $preset_id,
			'context' => BuilderContext::MASTER,
			'part'    => 'footer',
			'template' => 'footer',
			'zones'   => $this->build_zones(
				array(
					'footer_top'    => array(
						'label'            => __( 'Footer Top', 'starterkit' ),
						'allowed_elements' => array( 'newsletter', 'trust-badges', 'payment-icons', 'intro-text' ),
					),
					'footer_bottom' => array(
						'label'            => __( 'Footer Bottom', 'starterkit' ),
						'allowed_elements' => array( 'trust-badges', 'payment-icons', 'promo-banner' ),
					),
				)
			),
		);
	}

	/**
	 * Build a product schema.
	 *
	 * @param string                       $preset_id Preset id.
	 * @param array<string, string[]> $zones Allowed elements by zone.
	 * @return array<string, mixed>
	 */
	protected function product_schema( $preset_id, array $zones ) {
		$definitions = array();

		foreach ( $zones as $zone_id => $allowed_elements ) {
			$definitions[ $zone_id ] = array(
				'label'            => ucwords( str_replace( '_', ' ', $zone_id ) ),
				'allowed_elements' => $allowed_elements,
			);
		}

		return array(
			'id'      => $preset_id,
			'context' => BuilderContext::PRODUCT,
			'part'    => 'product',
			'template' => 'product',
			'zones'   => $this->build_zones( $definitions ),
		);
	}

	/**
	 * Build an archive schema.
	 *
	 * @param string $preset_id Preset id.
	 * @return array<string, mixed>
	 */
	protected function archive_schema( $preset_id ) {
		return array(
			'id'      => $preset_id,
			'context' => BuilderContext::ARCHIVE,
			'part'    => 'archive',
			'template' => 'archive',
			'zones'   => $this->build_zones(
				array(
					'archive_before_title'  => array(
						'label'            => __( 'Before Archive Title', 'starterkit' ),
						'allowed_elements' => array( 'category-banner', 'promo-banner', 'intro-text' ),
					),
					'archive_after_title'   => array(
						'label'            => __( 'After Archive Title', 'starterkit' ),
						'allowed_elements' => array( 'category-banner', 'promo-banner', 'intro-text', 'trust-strip' ),
					),
					'archive_before_loop'   => array(
						'label'            => __( 'Before Product Loop', 'starterkit' ),
						'allowed_elements' => array( 'promo-banner', 'trust-strip', 'newsletter' ),
					),
					'archive_after_loop'    => array(
						'label'            => __( 'After Product Loop', 'starterkit' ),
						'allowed_elements' => array( 'newsletter', 'faq', 'trust-strip' ),
					),
					'archive_sidebar_top'   => array(
						'label'            => __( 'Archive Sidebar Top', 'starterkit' ),
						'allowed_elements' => array( 'promo-banner', 'trust-strip', 'newsletter' ),
					),
					'archive_sidebar_bottom' => array(
						'label'            => __( 'Archive Sidebar Bottom', 'starterkit' ),
						'allowed_elements' => array( 'newsletter', 'faq', 'trust-strip' ),
					),
				)
			),
		);
	}

	/**
	 * Normalize zone definitions.
	 *
	 * @param array<string, array<string, mixed>> $zones Zone definitions.
	 * @return array<int, array<string, mixed>>
	 */
	protected function build_zones( array $zones ) {
		$output = array();

		foreach ( $zones as $zone_id => $zone ) {
			$output[] = array(
				'id'               => $zone_id,
				'label'            => isset( $zone['label'] ) ? $zone['label'] : ucwords( str_replace( '_', ' ', $zone_id ) ),
				'droppable'        => true,
				'sortable'         => true,
				'allowed_elements' => isset( $zone['allowed_elements'] ) ? array_values( array_map( 'strval', (array) $zone['allowed_elements'] ) ) : array(),
				'settings_schema'  => isset( $zone['settings_schema'] ) ? (array) $zone['settings_schema'] : array(),
				'constraints'      => isset( $zone['constraints'] ) ? (array) $zone['constraints'] : array( 'max_items' => 12 ),
			);
		}

		return $output;
	}
}
