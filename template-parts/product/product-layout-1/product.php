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

/**
 * wootify_variant_price_top_active is set by WootifyCore\Modules\Frontend\TemplateHook
 * during the woocommerce_single_product_summary hook (priority 6).
 * We suppress the plugin's built-in placeholder so the theme controls placement.
 */
add_filter( 'wootify_suppress_variant_price_top_placeholder', '__return_true' );
$show_variant_price_top = (bool) apply_filters( 'wootify_variant_price_top_active', false, (int) $product->get_id() );
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
