<?php
/**
 * Layout primitives for the custom checkout shell.
 *
 * @package StarterKit
 */

namespace StarterKit\WooCommerce;

use StarterKit\Settings\GlobalSettingsManager;

class CheckoutLayoutManager {
	/**
	 * Settings manager.
	 *
	 * @var GlobalSettingsManager
	 */
	protected $settings;

	/**
	 * Step registry.
	 *
	 * @var CheckoutStepRegistry
	 */
	protected $step_registry;

	/**
	 * Summary registry.
	 *
	 * @var CheckoutSummaryRegistry
	 */
	protected $summary_registry;

	/**
	 * Constructor.
	 *
	 * @param GlobalSettingsManager $settings Settings manager.
	 */
	public function __construct( GlobalSettingsManager $settings ) {
		$this->settings         = $settings;
		$this->step_registry    = new CheckoutStepRegistry( $this );
		$this->summary_registry = new CheckoutSummaryRegistry( $this );

		if ( $this->is_custom_checkout_enabled() ) {
			remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );
		}

		add_filter( 'woocommerce_checkout_posted_data', array( $this, 'filter_posted_data' ) );
		add_action( 'woocommerce_checkout_create_order', array( $this, 'copy_billing_to_shipping_order_address' ), 20, 2 );
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'copy_billing_to_shipping_saved_order_address' ), 20, 2 );
	}

	/**
	 * Render the complete checkout document shell.
	 *
	 * @return void
	 */
	public function render_shell() {
		if ( have_posts() ) {
			the_post();
		}

		$is_order_received = function_exists( 'is_order_received_page' ) && is_order_received_page();
		?>
		<!doctype html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php bloginfo( 'charset' ); ?>">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<?php wp_head(); ?>
		</head>
		<body <?php body_class( $is_order_received ? 'starterkit-checkout-shell-page is-order-received' : 'starterkit-checkout-shell-page' ); ?>>
		<?php wp_body_open(); ?>
		<div id="page" class="starterkit-checkout-shell">
			<?php $this->render_shell_header(); ?>
			<main id="primary" class="starterkit-checkout-shell__main">
				<?php if ( $is_order_received ) : ?>
					<section class="starterkit-checkout-complete" aria-label="<?php esc_attr_e( 'Order confirmation', 'starterkit' ); ?>">
						<?php echo do_shortcode( '[woocommerce_checkout]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</section>
				<?php else : ?>
					<?php echo do_shortcode( '[woocommerce_checkout]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php endif; ?>
			</main>
			<?php $this->render_shell_footer(); ?>
		</div>
		<?php wp_footer(); ?>
		</body>
		</html>
		<?php
	}

	/**
	 * Render checkout shell header.
	 *
	 * @return void
	 */
	public function render_shell_header() {
		$logo_id = (int) $this->settings->get( 'logo_id', 0 );
		?>
		<header class="starterkit-checkout-shell__header">
			<a class="starterkit-checkout-shell__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
				<?php if ( $logo_id ) : ?>
					<?php echo wp_get_attachment_image( $logo_id, 'full', false, array( 'class' => 'starterkit-checkout-shell__logo-image' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php else : ?>
					<span><?php bloginfo( 'name' ); ?></span>
				<?php endif; ?>
			</a>
		</header>
		<?php
	}

	/**
	 * Render checkout shell footer.
	 *
	 * @return void
	 */
	public function render_shell_footer() {
		?>
		<footer class="starterkit-checkout-shell__footer">
			<p><?php esc_html_e( 'Need help with your order? Contact us before placing your order.', 'starterkit' ); ?></p>
		</footer>
		<?php
	}

	/**
	 * Return registered checkout steps.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_steps() {
		return $this->step_registry->all();
	}

	/**
	 * Return registered summary components.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_summary_components() {
		return $this->summary_registry->all();
	}

	/**
	 * Determine whether the current cart needs shipping.
	 *
	 * @return bool
	 */
	public function cart_needs_shipping() {
		return function_exists( 'WC' ) && WC()->cart && WC()->cart->needs_shipping();
	}

	/**
	 * Check whether the custom checkout experience is enabled.
	 *
	 * @return bool
	 */
	protected function is_custom_checkout_enabled() {
		return '1' === (string) $this->settings->get( 'custom_checkout_page', '1' );
	}

	/**
	 * Copy billing address data into shipping so both order addresses match by default.
	 *
	 * @param array<string, mixed> $data Checkout posted data.
	 * @return array<string, mixed>
	 */
	public function filter_posted_data( $data ) {
		if ( ! $this->is_custom_checkout_enabled() || ! $this->cart_needs_shipping() ) {
			return $data;
		}

		$fields = array( 'first_name', 'last_name', 'company', 'country', 'address_1', 'address_2', 'city', 'state', 'postcode' );

		foreach ( $fields as $field ) {
			$billing_key  = 'billing_' . $field;
			$shipping_key = 'shipping_' . $field;

			if ( isset( $data[ $billing_key ] ) ) {
				$data[ $shipping_key ] = $data[ $billing_key ];
			}
		}

		$data['ship_to_different_address'] = false;

		return $data;
	}

	/**
	 * Persist the billing address as the shipping address on the order.
	 *
	 * WooCommerce does not normally save shipping fields when "ship to a different
	 * address" is off, so copy the address at order creation time for this layout.
	 *
	 * @param \WC_Order            $order Order object being created.
	 * @param array<string, mixed> $data  Checkout posted data.
	 * @return void
	 */
	public function copy_billing_to_shipping_order_address( $order, $data = array() ) {
		if ( ! $this->is_custom_checkout_enabled() || ! $order instanceof \WC_Order ) {
			return;
		}

		$address = $this->get_billing_address_for_shipping( $order, $data );

		if ( empty( $address ) ) {
			return;
		}

		$order->set_address( $address, 'shipping' );
	}

	/**
	 * Persist the copied shipping address after WooCommerce has saved the order.
	 *
	 * @param int                  $order_id Order ID.
	 * @param array<string, mixed> $data     Checkout posted data.
	 * @return void
	 */
	public function copy_billing_to_shipping_saved_order_address( $order_id, $data = array() ) {
		if ( ! $this->is_custom_checkout_enabled() || ! function_exists( 'wc_get_order' ) ) {
			return;
		}

		$order = wc_get_order( $order_id );

		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		$address = $this->get_billing_address_for_shipping( $order, $data );

		if ( empty( $address ) ) {
			return;
		}

		$order->set_address( $address, 'shipping' );
		$order->save();
	}

	/**
	 * Build a shipping-compatible address from billing checkout data.
	 *
	 * @param \WC_Order            $order Order object.
	 * @param array<string, mixed> $data  Checkout posted data.
	 * @return array<string, string>
	 */
	protected function get_billing_address_for_shipping( $order, $data ) {
		$address = array();
		$fields  = array( 'first_name', 'last_name', 'company', 'country', 'address_1', 'address_2', 'city', 'state', 'postcode' );

		foreach ( $fields as $field ) {
			$billing_key       = 'billing_' . $field;
			$getter            = 'get_billing_' . $field;
			$address[ $field ] = isset( $data[ $billing_key ] ) ? (string) $data[ $billing_key ] : (string) $order->{$getter}();
		}

		if ( '' === trim( implode( '', $address ) ) ) {
			return array();
		}

		return $address;
	}

	/**
	 * Render the information step.
	 *
	 * @param \WC_Checkout $checkout Checkout object.
	 * @return void
	 */
	public function render_information_step( $checkout ) {
		$needs_shipping = $this->cart_needs_shipping();
		?>
		<div class="starterkit-checkout-step__section starterkit-checkout-step__section--contact">
			<h2 class="starterkit-checkout__section-title"><?php esc_html_e( 'Contact', 'starterkit' ); ?></h2>
			<div class="starterkit-checkout-details__fields">
				<?php $this->render_contact_fields( $checkout ); ?>
				<?php $this->render_account_fields( $checkout ); ?>
			</div>
		</div>

		<div class="starterkit-checkout-step__section starterkit-checkout-step__section--address">
			<h2 class="starterkit-checkout__section-title"><?php esc_html_e( 'Billing address', 'starterkit' ); ?></h2>
			<div class="starterkit-checkout-details__fields">
				<?php if ( $needs_shipping ) : ?>
					<input type="hidden" name="ship_to_different_address" value="0">
				<?php endif; ?>
				<?php $this->render_billing_address_fields( $checkout ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the shipping step.
	 *
	 * @param \WC_Checkout $checkout Checkout object.
	 * @return void
	 */
	public function render_shipping_step( $checkout ) {
		unset( $checkout );
		?>
		<div class="starterkit-checkout-step__section">
			<h2 class="starterkit-checkout__section-title"><?php esc_html_e( 'Shipping method', 'starterkit' ); ?></h2>
			<div class="starterkit-checkout-shipping-methods" data-checkout-shipping-methods>
				<table class="shop_table starterkit-checkout-shipping-methods__table">
					<tbody data-checkout-shipping-methods-body>
						<tr>
							<td><?php esc_html_e( 'Shipping options will appear after the address is entered.', 'starterkit' ); ?></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the payment step.
	 *
	 * @param \WC_Checkout $checkout Checkout object.
	 * @return void
	 */
	public function render_payment_step( $checkout ) {
		?>
		<div class="starterkit-checkout-step__section starterkit-checkout-step__section--notes">
			<?php $this->render_order_fields( $checkout ); ?>
		</div>

		<div class="starterkit-checkout-step__section starterkit-checkout__payment">
			<h2 class="starterkit-checkout__section-title"><?php esc_html_e( 'Payment', 'starterkit' ); ?></h2>
			<?php woocommerce_checkout_payment(); ?>
		</div>
		<?php
	}

	/**
	 * Render summary header.
	 *
	 * @return void
	 */
	public function render_summary_header() {
		?>
		<header class="starterkit-checkout-summary__header">
			<h2 class="starterkit-checkout__section-title"><?php esc_html_e( 'Order summary', 'starterkit' ); ?></h2>
			<p><?php esc_html_e( 'Review items, discounts, shipping, and total.', 'starterkit' ); ?></p>
		</header>
		<?php
	}

	/**
	 * Render summary coupon UI.
	 *
	 * @return void
	 */
	public function render_summary_coupon() {
		?>
		<div class="starterkit-checkout-summary__coupon">
			<label for="checkout_coupon_code" class="screen-reader-text"><?php esc_html_e( 'Coupon code', 'woocommerce' ); ?></label>
			<input type="text" id="checkout_coupon_code" class="input-text" placeholder="<?php esc_attr_e( 'Coupon code', 'woocommerce' ); ?>">
			<button type="button" id="checkout_apply_coupon" class="button"><?php esc_html_e( 'Apply', 'starterkit' ); ?></button>
		</div>
		<?php
	}

	/**
	 * Render summary order review table.
	 *
	 * @return void
	 */
	public function render_summary_order_review() {
		woocommerce_order_review();
	}

	/**
	 * Render summary trust content.
	 *
	 * @return void
	 */
	public function render_summary_trust() {
		?>
		<div class="starterkit-checkout-summary__trust">
			<strong><?php esc_html_e( 'Protected checkout', 'starterkit' ); ?></strong>
			<span><?php esc_html_e( 'Your order details stay encrypted through WooCommerce checkout.', 'starterkit' ); ?></span>
		</div>
		<?php
	}

	/**
	 * Render billing contact fields.
	 *
	 * @param \WC_Checkout $checkout Checkout object.
	 * @return void
	 */
	protected function render_contact_fields( $checkout ) {
		$fields = $checkout->get_checkout_fields( 'billing' );

		foreach ( array( 'billing_email', 'billing_phone' ) as $key ) {
			if ( isset( $fields[ $key ] ) ) {
				woocommerce_form_field( $key, $fields[ $key ], $checkout->get_value( $key ) );
			}
		}
	}

	/**
	 * Render account creation fields.
	 *
	 * @param \WC_Checkout $checkout Checkout object.
	 * @return void
	 */
	protected function render_account_fields( $checkout ) {
		if ( is_user_logged_in() || ! $checkout->is_registration_enabled() ) {
			return;
		}
		?>
		<div class="woocommerce-account-fields starterkit-checkout-account-fields">
			<?php if ( ! $checkout->is_registration_required() ) : ?>
				<p class="form-row form-row-wide create-account">
					<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
						<input class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" id="createaccount" <?php checked( ( true === $checkout->get_value( 'createaccount' ) || ( true === apply_filters( 'woocommerce_create_account_default_checked', false ) ) ), true ); ?> type="checkbox" name="createaccount" value="1">
						<span><?php esc_html_e( 'Create an account?', 'woocommerce' ); ?></span>
					</label>
				</p>
			<?php endif; ?>

			<?php do_action( 'woocommerce_before_checkout_registration_form', $checkout ); ?>

			<?php if ( $checkout->get_checkout_fields( 'account' ) ) : ?>
				<div class="create-account">
					<?php foreach ( $checkout->get_checkout_fields( 'account' ) as $key => $field ) : ?>
						<?php woocommerce_form_field( $key, $field, $checkout->get_value( $key ) ); ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php do_action( 'woocommerce_after_checkout_registration_form', $checkout ); ?>
		</div>
		<?php
	}

	/**
	 * Render billing address fields without contact fields.
	 *
	 * @param \WC_Checkout $checkout Checkout object.
	 * @return void
	 */
	protected function render_billing_address_fields( $checkout ) {
		foreach ( $checkout->get_checkout_fields( 'billing' ) as $key => $field ) {
			if ( in_array( $key, array( 'billing_email', 'billing_phone' ), true ) ) {
				continue;
			}

			woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
		}
	}

	/**
	 * Render order notes fields.
	 *
	 * @param \WC_Checkout $checkout Checkout object.
	 * @return void
	 */
	protected function render_order_fields( $checkout ) {
		do_action( 'woocommerce_before_order_notes', $checkout );

		if ( apply_filters( 'woocommerce_enable_order_notes_field', 'yes' === get_option( 'woocommerce_enable_order_comments', 'yes' ) ) ) {
			$fields = $checkout->get_checkout_fields( 'order' );

			if ( $fields ) {
				?>
				<h2 class="starterkit-checkout__section-title"><?php esc_html_e( 'Order notes', 'starterkit' ); ?></h2>
				<div class="starterkit-checkout-details__fields">
					<?php foreach ( $fields as $key => $field ) : ?>
						<?php woocommerce_form_field( $key, $field, $checkout->get_value( $key ) ); ?>
					<?php endforeach; ?>
				</div>
				<?php
			}
		}

		do_action( 'woocommerce_after_order_notes', $checkout );
	}
}
