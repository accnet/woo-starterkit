<?php
/**
 * Product layout preset 3.
 *
 * @package StarterKit
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product instanceof \WC_Product ) {
	return;
}

$zone_renderer = starterkit()->zone_renderer();
$layout_settings_manager = starterkit()->layout_settings_manager();
$layout_inline_style = $layout_settings_manager->product_split_layout_inline_style(
	$layout_settings_manager->get_layout_settings( 'product-layout-3' )
);
?>
<div class="starterkit-product-layout starterkit-product-layout--preset-3">
	<div class="starterkit-product-layout__product-shell starterkit-product-layout__product-shell--preset-3" style="<?php echo esc_attr( $layout_inline_style ); ?>">
		<div class="starterkit-product-layout__gallery-column">
			<?php $zone_renderer->render( 'product_before_gallery', array( 'context' => 'product' ) ); ?>
			<?php woocommerce_show_product_images(); ?>
			<?php $zone_renderer->render( 'product_after_gallery', array( 'context' => 'product' ) ); ?>
		</div>
		<div class="starterkit-product-layout__summary-column">
			<?php $zone_renderer->render( 'product_before_summary', array( 'context' => 'product' ) ); ?>
			<div class="summary entry-summary">
				<?php woocommerce_template_single_title(); ?>
				<?php woocommerce_template_single_rating(); ?>
				<?php woocommerce_template_single_price(); ?>
				<?php woocommerce_template_single_excerpt(); ?>
				<?php woocommerce_template_single_add_to_cart(); ?>
				<?php woocommerce_template_single_meta(); ?>
				<?php woocommerce_template_single_sharing(); ?>
			</div>
			<?php $zone_renderer->render( 'product_after_summary', array( 'context' => 'product' ) ); ?>
		</div>
	</div>
	<div class="starterkit-product-layout__supporting starterkit-product-layout__supporting--preset-3">
		<?php $zone_renderer->render( 'product_before_tabs', array( 'context' => 'product' ) ); ?>
		<?php woocommerce_output_product_data_tabs(); ?>
		<?php woocommerce_upsell_display(); ?>
		<?php $zone_renderer->render( 'product_after_tabs', array( 'context' => 'product' ) ); ?>
		<?php $zone_renderer->render( 'product_before_related', array( 'context' => 'product' ) ); ?>
		<?php woocommerce_output_related_products(); ?>
		<?php $zone_renderer->render( 'product_after_related', array( 'context' => 'product' ) ); ?>
	</div>
</div>
