<?php
/**
 * WooCommerce empty cart template override.
 *
 * @package StarterKit
 */

defined( 'ABSPATH' ) || exit;

$shop_url = wc_get_page_permalink( 'shop' );
?>
<div class="starterkit-cart starterkit-woocommerce-cart">
	<div class="starterkit-cart-empty">
		<div class="starterkit-cart-empty__icon" aria-hidden="true">
			<svg width="56" height="56" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M3 4H5L7.2 14.39C7.29 14.82 7.53 15.2 7.89 15.46C8.24 15.72 8.68 15.84 9.11 15.79H17.8C18.21 15.79 18.61 15.65 18.92 15.4C19.24 15.15 19.46 14.8 19.55 14.41L21 7H6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
				<circle cx="9" cy="20" r="1.25" fill="currentColor"/>
				<circle cx="18" cy="20" r="1.25" fill="currentColor"/>
			</svg>
		</div>
		<h1 class="starterkit-cart-empty__title"><?php esc_html_e( 'Your cart is empty', 'starterkit' ); ?></h1>
		<p class="starterkit-cart-empty__text"><?php esc_html_e( 'Add a few products and come back here to complete your order.', 'starterkit' ); ?></p>
		<?php if ( $shop_url ) : ?>
			<a class="button starterkit-cart-summary__checkout" href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'Continue shopping', 'starterkit' ); ?></a>
		<?php endif; ?>
	</div>
</div>
