<?php
/**
 * Product layout preset 2.
 *
 * @package StarterKit
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product instanceof \WC_Product ) {
	return;
}

$zone_renderer = starterkit()->zone_renderer();
?>
<div class="starterkit-product-layout starterkit-product-layout--preset-2">
	<div class="starterkit-product-layout__stack starterkit-product-layout__stack--preset-2">
		<?php $zone_renderer->render( 'product_before_summary', array( 'context' => 'product' ) ); ?>
		<div class="starterkit-product-layout__summary-card">
			<?php woocommerce_template_single_title(); ?>
			<?php woocommerce_template_single_rating(); ?>
			<?php woocommerce_template_single_price(); ?>
			<?php woocommerce_template_single_excerpt(); ?>
			<?php woocommerce_template_single_add_to_cart(); ?>
			<?php woocommerce_template_single_meta(); ?>
		</div>
		<?php $zone_renderer->render( 'product_after_summary', array( 'context' => 'product' ) ); ?>
		<?php $zone_renderer->render( 'product_before_tabs', array( 'context' => 'product' ) ); ?>
		<div class="starterkit-product-layout__tabs-card">
			<?php woocommerce_output_product_data_tabs(); ?>
			<?php woocommerce_output_related_products(); ?>
		</div>
		<?php $zone_renderer->render( 'product_after_tabs', array( 'context' => 'product' ) ); ?>
	</div>
</div>
