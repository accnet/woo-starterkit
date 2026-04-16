<?php
/**
 * Empty cart state.
 *
 * @package StarterKit
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="starterkit-cart-empty">
	<svg class="starterkit-cart-empty__icon" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
		<path d="M3 4h2l1.3 8.2a2 2 0 0 0 2 1.8h7.9a2 2 0 0 0 2-1.6L20 7H7"></path>
		<circle cx="10" cy="19" r="1.5"></circle>
		<circle cx="17" cy="19" r="1.5"></circle>
	</svg>
	<h2 class="starterkit-cart-empty__title"><?php esc_html_e( 'Your cart is empty', 'starterkit' ); ?></h2>
	<p class="starterkit-cart-empty__text"><?php esc_html_e( 'Looks like you haven\'t added anything to your cart yet.', 'starterkit' ); ?></p>
	<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="button button-primary">
		<?php esc_html_e( 'Browse Products', 'starterkit' ); ?>
	</a>
</div>
