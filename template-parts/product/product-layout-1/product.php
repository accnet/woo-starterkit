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
$gallery_items = array();

if ( class_exists( '\WootifyCore\Services\ProductService' ) ) {
	$wootify_service     = new \WootifyCore\Services\ProductService();
	$gallery_items        = $wootify_service->get_theme_gallery_items( (int) $product->get_id() );
}
?>
<div class="starterkit-product-layout product-layout-1">
	<div class="starterkit-product-layout__product-shell">
		<div class="starterkit-product-layout__gallery-column">
			<?php starterkit_render_slot( 'product_before_gallery' ); ?>
			<?php woocommerce_show_product_sale_flash(); ?>
			<div class="starterkit-product-gallery<?php echo count( $gallery_items ) > 1 ? ' starterkit-product-gallery--has-thumbs' : ''; ?>">
				<?php if ( count( $gallery_items ) > 1 ) : ?>
					<div class="swiper starterkit-product-gallery__thumbs">
						<div class="swiper-wrapper">
							<?php foreach ( $gallery_items as $index => $gallery_item ) : ?>
								<div
									class="swiper-slide"
									data-image-id="<?php echo esc_attr( (string) $gallery_item['id'] ); ?>"
									data-image-src="<?php echo esc_attr( (string) $gallery_item['src'] ); ?>"
									data-variant-ids="<?php echo esc_attr( wp_json_encode( array_values( array_map( 'intval', (array) $gallery_item['variant_ids'] ) ) ) ); ?>"
									data-featured-variant-ids="<?php echo esc_attr( wp_json_encode( array_values( array_map( 'intval', (array) $gallery_item['featured_variant_ids'] ) ) ) ); ?>"
								>
									<button
										class="starterkit-product-gallery__thumb-button"
										type="button"
										aria-label="<?php echo esc_attr( sprintf( __( 'View image %d', 'starterkit' ), $index + 1 ) ); ?>"
									>
										<img class="starterkit-product-gallery__thumb-image" src="<?php echo esc_url( (string) $gallery_item['thumb_src'] ); ?>" alt="<?php echo esc_attr( (string) ( $gallery_item['alt'] ?? '' ) ); ?>" loading="lazy">
									</button>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>

				<div class="starterkit-product-gallery__stage">
					<div class="swiper starterkit-product-gallery__main">
						<div class="swiper-wrapper">
							<?php foreach ( $gallery_items as $gallery_item ) : ?>
								<div
									class="swiper-slide"
									data-image-id="<?php echo esc_attr( (string) $gallery_item['id'] ); ?>"
									data-image-src="<?php echo esc_attr( (string) $gallery_item['src'] ); ?>"
									data-variant-ids="<?php echo esc_attr( wp_json_encode( array_values( array_map( 'intval', (array) $gallery_item['variant_ids'] ) ) ) ); ?>"
									data-featured-variant-ids="<?php echo esc_attr( wp_json_encode( array_values( array_map( 'intval', (array) $gallery_item['featured_variant_ids'] ) ) ) ); ?>"
								>
									<a class="starterkit-product-gallery__image-link" href="<?php echo esc_url( $gallery_item['full'] ); ?>">
										<img class="starterkit-product-gallery__image-image" src="<?php echo esc_url( (string) $gallery_item['src'] ); ?>" alt="<?php echo esc_attr( (string) ( $gallery_item['alt'] ?? '' ) ); ?>" loading="<?php echo 0 === $index ? 'eager' : 'lazy'; ?>" fetchpriority="<?php echo 0 === $index ? 'high' : 'auto'; ?>">
									</a>
								</div>
							<?php endforeach; ?>
						</div>
					</div>

					<?php if ( count( $gallery_items ) > 1 ) : ?>
						<button class="starterkit-product-gallery__nav starterkit-product-gallery__nav--prev" type="button" aria-label="<?php esc_attr_e( 'Previous image', 'starterkit' ); ?>">
							<span aria-hidden="true">&larr;</span>
						</button>
						<button class="starterkit-product-gallery__nav starterkit-product-gallery__nav--next" type="button" aria-label="<?php esc_attr_e( 'Next image', 'starterkit' ); ?>">
							<span aria-hidden="true">&rarr;</span>
						</button>
					<?php endif; ?>
				</div>
			</div>
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
