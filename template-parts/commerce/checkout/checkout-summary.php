<?php
/**
 * Checkout order summary sidebar — product list + discount code + totals.
 *
 * @package StarterKit
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="starterkit-checkout-summary">
	<!-- Product items -->
	<div class="starterkit-checkout-summary__products">
		<?php
		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) :
			$_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
			if ( ! $_product || ! $_product->exists() || $cart_item['quantity'] <= 0 ) {
				continue;
			}
			if ( ! apply_filters( 'woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
				continue;
			}
			?>
			<div class="starterkit-checkout-summary__product">
				<div class="starterkit-checkout-summary__product-image">
					<?php echo wp_kses_post( $_product->get_image( 'woocommerce_gallery_thumbnail' ) ); ?>
					<span class="starterkit-checkout-summary__product-qty"><?php echo esc_html( $cart_item['quantity'] ); ?></span>
				</div>
				<div class="starterkit-checkout-summary__product-info">
					<span class="starterkit-checkout-summary__product-name">
						<?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key ) ); ?>
					</span>
					<?php echo wc_get_formatted_cart_item_data( $cart_item ); ?>
				</div>
				<span class="starterkit-checkout-summary__product-price">
					<?php echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); ?>
				</span>
			</div>
		<?php endforeach; ?>
	</div>

	<!-- Discount code -->
	<?php if ( wc_coupons_enabled() ) : ?>
		<div class="starterkit-checkout-summary__discount">
			<div class="starterkit-checkout-summary__discount-field">
				<input type="text" name="coupon_code" class="starterkit-checkout-summary__discount-input" id="checkout_coupon_code" placeholder="<?php esc_attr_e( 'Discount code', 'starterkit' ); ?>" />
				<button type="button" class="starterkit-checkout-summary__discount-btn" id="checkout_apply_coupon"><?php esc_html_e( 'Apply', 'starterkit' ); ?></button>
			</div>
		</div>
	<?php endif; ?>

	<!-- Totals -->
	<div class="starterkit-checkout-summary__totals">
		<div class="starterkit-checkout-summary__row">
			<span><?php esc_html_e( 'Subtotal', 'starterkit' ); ?></span>
			<span><?php wc_cart_totals_subtotal_html(); ?></span>
		</div>

		<?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
			<div class="starterkit-checkout-summary__row starterkit-checkout-summary__row--coupon">
				<span><?php wc_cart_totals_coupon_label( $coupon ); ?></span>
				<span><?php wc_cart_totals_coupon_html( $coupon ); ?></span>
			</div>
		<?php endforeach; ?>

		<?php if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) : ?>
			<div class="starterkit-checkout-summary__row">
				<span><?php esc_html_e( 'Shipping', 'starterkit' ); ?></span>
				<span><?php echo wp_kses_post( WC()->cart->get_cart_shipping_total() ); ?></span>
			</div>
		<?php else : ?>
			<div class="starterkit-checkout-summary__row">
				<span><?php esc_html_e( 'Shipping', 'starterkit' ); ?></span>
				<span class="starterkit-checkout-summary__row-note"><?php esc_html_e( 'Enter shipping address', 'starterkit' ); ?></span>
			</div>
		<?php endif; ?>

		<?php foreach ( WC()->cart->get_fees() as $fee ) : ?>
			<div class="starterkit-checkout-summary__row">
				<span><?php echo esc_html( $fee->name ); ?></span>
				<span><?php wc_cart_totals_fee_html( $fee ); ?></span>
			</div>
		<?php endforeach; ?>

		<?php if ( wc_tax_enabled() && ! WC()->cart->display_prices_including_tax() ) : ?>
			<?php if ( 'itemized' === get_option( 'woocommerce_tax_total_display' ) ) : ?>
				<?php foreach ( WC()->cart->get_tax_totals() as $code => $tax ) : ?>
					<div class="starterkit-checkout-summary__row">
						<span><?php echo esc_html( $tax->label ); ?></span>
						<span><?php echo wp_kses_post( $tax->formatted_amount ); ?></span>
					</div>
				<?php endforeach; ?>
			<?php else : ?>
				<div class="starterkit-checkout-summary__row">
					<span><?php echo esc_html( WC()->countries->tax_or_vat() ); ?></span>
					<span><?php wc_cart_totals_taxes_total_html(); ?></span>
				</div>
			<?php endif; ?>
		<?php endif; ?>

		<div class="starterkit-checkout-summary__row starterkit-checkout-summary__row--total">
			<span>
				<?php esc_html_e( 'Total', 'starterkit' ); ?>
				<?php if ( WC()->cart->get_total_tax() > 0 ) : ?>
					<small class="starterkit-checkout-summary__currency"><?php echo esc_html( get_woocommerce_currency() ); ?></small>
				<?php endif; ?>
			</span>
			<span><?php wc_cart_totals_order_total_html(); ?></span>
		</div>
	</div>
</div>
