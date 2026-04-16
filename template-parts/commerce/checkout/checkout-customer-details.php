<?php
/**
 * Checkout customer details — Shopify-style sections.
 *
 * @package StarterKit
 */

defined( 'ABSPATH' ) || exit;

$checkout = WC()->checkout();
?>
<div class="starterkit-checkout-details">
	<!-- Contact -->
	<div class="starterkit-checkout-details__section" id="checkout-contact">
		<div class="starterkit-checkout-details__section-header">
			<h2 class="starterkit-checkout__section-title"><?php esc_html_e( 'Contact', 'starterkit' ); ?></h2>
			<?php if ( ! is_user_logged_in() && $checkout->is_registration_enabled() ) : ?>
				<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="starterkit-checkout-details__sign-in"><?php esc_html_e( 'Sign in', 'starterkit' ); ?></a>
			<?php endif; ?>
		</div>
		<div class="starterkit-checkout-details__fields">
			<?php do_action( 'woocommerce_checkout_billing' ); ?>
		</div>
	</div>

	<!-- Delivery / Shipping -->
	<?php if ( WC()->cart->needs_shipping() ) : ?>
		<div class="starterkit-checkout-details__section" id="checkout-delivery">
			<h2 class="starterkit-checkout__section-title"><?php esc_html_e( 'Delivery', 'starterkit' ); ?></h2>

			<div class="starterkit-checkout-details__fields">
				<?php if ( WC()->cart->show_shipping() ) : ?>
					<?php do_action( 'woocommerce_checkout_shipping' ); ?>
				<?php endif; ?>
			</div>
		</div>

		<!-- Shipping method -->
		<div class="starterkit-checkout-details__section" id="checkout-shipping-method">
			<h2 class="starterkit-checkout__section-title"><?php esc_html_e( 'Shipping method', 'starterkit' ); ?></h2>
			<div class="starterkit-checkout-details__shipping-method">
				<?php if ( WC()->cart->show_shipping() ) : ?>
					<?php wc_cart_totals_shipping_html(); ?>
				<?php else : ?>
					<div class="starterkit-checkout-details__shipping-notice">
						<p><?php esc_html_e( 'Enter your shipping address to view available shipping methods.', 'starterkit' ); ?></p>
					</div>
				<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>

	<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>
</div>
