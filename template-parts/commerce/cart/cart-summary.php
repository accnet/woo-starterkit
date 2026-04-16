<?php
/**
 * Cart summary / order totals sidebar.
 *
 * @package StarterKit
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="starterkit-cart-summary">
	<h2 class="starterkit-cart-summary__title"><?php esc_html_e( 'Order Summary', 'starterkit' ); ?></h2>

	<div class="starterkit-cart-summary__rows">
		<div class="starterkit-cart-summary__row">
			<span><?php esc_html_e( 'Subtotal', 'starterkit' ); ?></span>
			<span><?php echo wp_kses_post( WC()->cart->get_cart_subtotal() ); ?></span>
		</div>

		<?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
			<div class="starterkit-cart-summary__row starterkit-cart-summary__row--coupon">
				<span><?php echo esc_html( sprintf( __( 'Coupon: %s', 'starterkit' ), $code ) ); ?></span>
				<span><?php wc_cart_totals_coupon_html( $coupon ); ?></span>
			</div>
		<?php endforeach; ?>

		<?php if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) : ?>
			<div class="starterkit-cart-summary__row starterkit-cart-summary__row--shipping">
				<?php wc_cart_totals_shipping_html(); ?>
			</div>
		<?php endif; ?>

		<?php foreach ( WC()->cart->get_fees() as $fee ) : ?>
			<div class="starterkit-cart-summary__row">
				<span><?php echo esc_html( $fee->name ); ?></span>
				<span><?php wc_cart_totals_fee_html( $fee ); ?></span>
			</div>
		<?php endforeach; ?>

		<?php if ( wc_tax_enabled() && ! WC()->cart->display_prices_including_tax() ) : ?>
			<?php foreach ( WC()->cart->get_tax_totals() as $tax_code => $tax ) : ?>
				<div class="starterkit-cart-summary__row">
					<span><?php echo esc_html( $tax->label ); ?></span>
					<span><?php echo wp_kses_post( $tax->formatted_amount ); ?></span>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>

		<div class="starterkit-cart-summary__row starterkit-cart-summary__row--total">
			<span><?php esc_html_e( 'Total', 'starterkit' ); ?></span>
			<span><?php echo wp_kses_post( WC()->cart->get_total() ); ?></span>
		</div>
	</div>

	<div class="starterkit-cart-summary__actions">
		<a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="button button-primary starterkit-cart-summary__checkout">
			<?php esc_html_e( 'Proceed to Checkout', 'starterkit' ); ?>
		</a>
		<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="button button-secondary">
			<?php esc_html_e( 'Continue Shopping', 'starterkit' ); ?>
		</a>
	</div>
</div>
