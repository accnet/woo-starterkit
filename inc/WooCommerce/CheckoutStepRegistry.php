<?php
/**
 * Registry for the custom checkout step flow.
 *
 * @package StarterKit
 */

namespace StarterKit\WooCommerce;

class CheckoutStepRegistry {
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
	 * Return ordered checkout steps.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function all() {
		$steps = array(
			array(
				'id'              => 'information',
				'label'           => __( 'Information', 'starterkit' ),
				'priority'        => 10,
				'enabled'         => true,
				'render_callback' => array( $this->layout_manager, 'render_information_step' ),
			),
			array(
				'id'              => 'shipping',
				'label'           => __( 'Shipping', 'starterkit' ),
				'priority'        => 20,
				'enabled'         => $this->layout_manager->cart_needs_shipping(),
				'render_callback' => array( $this->layout_manager, 'render_shipping_step' ),
			),
			array(
				'id'              => 'payment',
				'label'           => __( 'Payment', 'starterkit' ),
				'priority'        => 30,
				'enabled'         => true,
				'render_callback' => array( $this->layout_manager, 'render_payment_step' ),
			),
		);

		$steps = array_values(
			array_filter(
				apply_filters( 'starterkit_checkout_steps', $steps ),
				function( $step ) {
					return ! empty( $step['enabled'] );
				}
			)
		);

		usort(
			$steps,
			function( $a, $b ) {
				return (int) $a['priority'] <=> (int) $b['priority'];
			}
		);

		return $steps;
	}
}
