<?php
/**
 * Registry for checkout summary components.
 *
 * @package StarterKit
 */

namespace StarterKit\WooCommerce;

class CheckoutSummaryRegistry {
	/**
	 * Checkout layout manager.
	 *
	 * @var CheckoutLayoutManager
	 */
	protected $layout_manager;

	/**
	 * Constructor.
	 *
	 * @param CheckoutLayoutManager $layout_manager Layout manager.
	 */
	public function __construct( CheckoutLayoutManager $layout_manager ) {
		$this->layout_manager = $layout_manager;
	}

	/**
	 * Return ordered checkout summary components.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function all() {
		$components = array(
			array(
				'id'              => 'summary_header',
				'priority'        => 10,
				'enabled'         => true,
				'render_callback' => array( $this->layout_manager, 'render_summary_header' ),
			),
			array(
				'id'              => 'coupon',
				'priority'        => 20,
				'enabled'         => function_exists( 'wc_coupons_enabled' ) && wc_coupons_enabled(),
				'render_callback' => array( $this->layout_manager, 'render_summary_coupon' ),
			),
			array(
				'id'              => 'order_review',
				'priority'        => 30,
				'enabled'         => true,
				'render_callback' => array( $this->layout_manager, 'render_summary_order_review' ),
			),
			array(
				'id'              => 'trust',
				'priority'        => 40,
				'enabled'         => true,
				'render_callback' => array( $this->layout_manager, 'render_summary_trust' ),
			),
		);

		$components = array_values(
			array_filter(
				apply_filters( 'starterkit_checkout_summary_components', $components ),
				function( $component ) {
					return ! empty( $component['enabled'] );
				}
			)
		);

		usort(
			$components,
			function( $a, $b ) {
				return (int) $a['priority'] <=> (int) $b['priority'];
			}
		);

		return $components;
	}
}
