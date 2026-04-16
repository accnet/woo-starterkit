<?php
/**
 * Custom cart page layout.
 *
 * @package StarterKit
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="starterkit-commerce starterkit-commerce--cart">
	<div class="starterkit-commerce__shell starterkit-commerce__shell--cart">
		<header class="starterkit-commerce__hero">
			<div>
				<p class="starterkit-commerce__eyebrow"><?php esc_html_e( 'Cart', 'starterkit' ); ?></p>
				<h1 class="starterkit-commerce__title"><?php esc_html_e( 'Your bag', 'starterkit' ); ?></h1>
			</div>
			<p class="starterkit-commerce__description"><?php esc_html_e( 'Review items, adjust quantity, apply discounts, then continue to checkout.', 'starterkit' ); ?></p>
		</header>

		<div class="starterkit-commerce__grid">
			<section class="starterkit-commerce__main">
				<div class="starterkit-commerce__notice" data-commerce-notice></div>
				<div class="starterkit-commerce__content" id="starterkit-cart-app" data-commerce-cart-root>
					<div class="starterkit-commerce__loading"><?php esc_html_e( 'Loading your cart...', 'starterkit' ); ?></div>
				</div>
			</section>

			<aside class="starterkit-commerce__aside" id="starterkit-cart-summary" data-commerce-cart-summary>
				<div class="starterkit-commerce__loading"><?php esc_html_e( 'Loading your cart...', 'starterkit' ); ?></div>
			</aside>
		</div>
	</div>
</div>
