<?php
/**
 * Custom checkout page layout — Shopify-style.
 *
 * @package StarterKit
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="starterkit-checkout">
	<?php do_action( 'woocommerce_before_checkout_form', $checkout ); ?>

	<form name="checkout" method="post" class="starterkit-checkout__form checkout woocommerce-checkout" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data">
		<div class="starterkit-checkout__grid">
			<div class="starterkit-checkout__main">
				<?php get_template_part( 'template-parts/commerce/checkout/checkout', 'customer-details' ); ?>

				<div class="starterkit-checkout__payment">
					<h2 class="starterkit-checkout__section-title"><?php esc_html_e( 'Payment', 'starterkit' ); ?></h2>
					<p class="starterkit-checkout__section-desc"><?php esc_html_e( 'All transactions are secure and encrypted.', 'starterkit' ); ?></p>
					<?php woocommerce_checkout_payment(); ?>
				</div>
			</div>

			<aside class="starterkit-checkout__sidebar">
				<?php get_template_part( 'template-parts/commerce/checkout/checkout', 'summary' ); ?>
			</aside>
		</div>
	</form>

	<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
</div>
