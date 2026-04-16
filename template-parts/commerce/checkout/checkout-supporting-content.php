<?php
/**
 * Checkout supporting content: trust badges, guarantees, etc.
 *
 * @package StarterKit
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="starterkit-checkout-support">
	<div class="starterkit-checkout-support__badges">
		<div class="starterkit-checkout-support__badge">
			<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
				<rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
				<path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
			</svg>
			<div>
				<strong><?php esc_html_e( 'Secure Checkout', 'starterkit' ); ?></strong>
				<span><?php esc_html_e( 'SSL encrypted payment', 'starterkit' ); ?></span>
			</div>
		</div>
		<div class="starterkit-checkout-support__badge">
			<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
				<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
				<circle cx="12" cy="10" r="3"></circle>
			</svg>
			<div>
				<strong><?php esc_html_e( 'Fast Shipping', 'starterkit' ); ?></strong>
				<span><?php esc_html_e( 'Free on qualifying orders', 'starterkit' ); ?></span>
			</div>
		</div>
		<div class="starterkit-checkout-support__badge">
			<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
				<polyline points="20 6 9 17 4 12"></polyline>
			</svg>
			<div>
				<strong><?php esc_html_e( 'Money-back Guarantee', 'starterkit' ); ?></strong>
				<span><?php esc_html_e( '30-day return policy', 'starterkit' ); ?></span>
			</div>
		</div>
	</div>
</div>
