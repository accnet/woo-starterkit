<?php
/**
 * Custom single-product rendering for product layout 1.
 *
 * @package StarterKit
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product instanceof \WC_Product ) {
	return;
}

$show_variant_price_top = false;

if ( class_exists( '\WootifyCore\Services\ProductService' ) && (int) get_option( 'wootify_core_product_page_move_variant_price_top', 0 ) === 1 ) {
	$wootify_service = new \WootifyCore\Services\ProductService();
	$wootify_data    = $wootify_service->get_product_data( (int) $product->get_id() );

	if ( is_array( $wootify_data ) && ! empty( $wootify_data['variants'] ) ) {
		$show_variant_price_top = true;
	}
}
?>
<div class="starterkit-product-layout product-layout-1">
	<div class="starterkit-product-layout__product-shell">
		<div class="starterkit-product-layout__gallery-column">
			<?php starterkit_render_slot( 'product_before_gallery' ); ?>
			<?php woocommerce_show_product_sale_flash(); ?>
			<?php woocommerce_show_product_images(); ?>
			<?php starterkit_render_slot( 'product_after_gallery' ); ?>
		</div>

		<div class="starterkit-product-layout__summary-column">
			<div class="summary entry-summary<?php echo $show_variant_price_top ? ' has-wootify-variant-price-top' : ''; ?>">
				<?php starterkit_render_slot( 'product_before_summary' ); ?>
				<?php woocommerce_template_single_title(); ?>
				<?php if ( $show_variant_price_top ) : ?>
					<div id="wootify-variant-price-top" class="wootify-variant-price-top" style="display:none;">
						<span class="wootify-variant-price-top__value"></span>
					</div>
				<?php endif; ?>
				<?php woocommerce_template_single_rating(); ?>
				<?php woocommerce_template_single_price(); ?>
				<?php woocommerce_template_single_excerpt(); ?>
				<?php woocommerce_template_single_add_to_cart(); ?>
				<?php woocommerce_template_single_meta(); ?>
				<?php woocommerce_template_single_sharing(); ?>
				<?php starterkit_render_slot( 'product_after_summary' ); ?>
			</div>
		</div>
	</div>

	<div class="starterkit-product-layout__supporting">
		<?php starterkit_render_slot( 'product_before_tabs' ); ?>
		<?php woocommerce_output_product_data_tabs(); ?>
		<?php starterkit_render_slot( 'product_after_tabs' ); ?>
		<?php starterkit_render_slot( 'product_before_related' ); ?>
		<?php woocommerce_upsell_display(); ?>
		<?php woocommerce_output_related_products(); ?>
		<?php starterkit_render_slot( 'product_after_related' ); ?>
	</div>
</div>
