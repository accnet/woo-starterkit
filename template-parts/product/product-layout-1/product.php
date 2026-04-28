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

$gallery_items = \StarterKit\Helpers\ProductGallery::get_items( $product );
$zone_renderer = starterkit()->zone_renderer();
$layout_settings_manager = starterkit()->layout_settings_manager();
$layout_settings = $layout_settings_manager->get_layout_settings( 'product-layout-1' );
$layout_inline_style = $layout_settings_manager->product_split_layout_inline_style( $layout_settings );
$related_products_settings = $layout_settings_manager->product_layout_1_related_products_settings( $layout_settings );
$layout_inline_style .= '--starterkit-related-products-columns:' . (int) $related_products_settings['columns'] . ';';
?>
<div class="starterkit-product-layout product-layout-1" style="<?php echo esc_attr( $layout_inline_style ); ?>">
	<div class="starterkit-product-layout__product-shell">
		<div class="starterkit-product-layout__gallery-column">
			<?php $zone_renderer->render( 'product_before_gallery', array( 'context' => 'product' ) ); ?>
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
										<?php
										if ( ! empty( $gallery_item['id'] ) ) {
											echo wp_get_attachment_image(
												(int) $gallery_item['id'],
												'woocommerce_thumbnail',
												false,
												array(
													'class'    => 'starterkit-product-gallery__thumb-image',
													'alt'      => (string) ( $gallery_item['alt'] ?? '' ),
													'loading'  => 'lazy',
													'decoding' => 'async',
												)
											); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										} else {
											?>
											<img class="starterkit-product-gallery__thumb-image" src="<?php echo esc_url( (string) $gallery_item['thumb_src'] ); ?>" alt="<?php echo esc_attr( (string) ( $gallery_item['alt'] ?? '' ) ); ?>" loading="lazy" decoding="async">
											<?php
										}
										?>
									</button>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>

				<div class="starterkit-product-gallery__stage">
					<div class="swiper starterkit-product-gallery__main">
						<div class="swiper-wrapper">
							<?php foreach ( $gallery_items as $index => $gallery_item ) : ?>
								<div
									class="swiper-slide"
									data-image-id="<?php echo esc_attr( (string) $gallery_item['id'] ); ?>"
									data-image-src="<?php echo esc_attr( (string) $gallery_item['src'] ); ?>"
									data-variant-ids="<?php echo esc_attr( wp_json_encode( array_values( array_map( 'intval', (array) $gallery_item['variant_ids'] ) ) ) ); ?>"
									data-featured-variant-ids="<?php echo esc_attr( wp_json_encode( array_values( array_map( 'intval', (array) $gallery_item['featured_variant_ids'] ) ) ) ); ?>"
								>
									<div class="starterkit-product-gallery__image-link">
										<?php
										if ( ! empty( $gallery_item['id'] ) ) {
											echo wp_get_attachment_image(
												(int) $gallery_item['id'],
												'full',
												false,
												array(
													'class'         => 'starterkit-product-gallery__image-image',
													'alt'           => (string) ( $gallery_item['alt'] ?? '' ),
													'loading'       => 0 === $index ? 'eager' : 'lazy',
													'decoding'      => 'async',
													'fetchpriority' => 0 === $index ? 'high' : 'auto',
												)
											); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										} else {
											?>
											<img class="starterkit-product-gallery__image-image" src="<?php echo esc_url( (string) $gallery_item['src'] ); ?>" alt="<?php echo esc_attr( (string) ( $gallery_item['alt'] ?? '' ) ); ?>" loading="<?php echo 0 === $index ? 'eager' : 'lazy'; ?>" decoding="async" fetchpriority="<?php echo 0 === $index ? 'high' : 'auto'; ?>">
											<?php
										}
										?>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				</div>
			</div>
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

	<div class="starterkit-product-layout__supporting">
		<?php $zone_renderer->render( 'product_before_related', array( 'context' => 'product' ) ); ?>
		<?php woocommerce_output_product_data_tabs(); ?>
		<?php woocommerce_upsell_display(); ?>
		<?php if ( '1' === (string) $related_products_settings['show'] ) : ?>
			<?php
			woocommerce_related_products(
				array(
					'posts_per_page' => (int) $related_products_settings['limit'],
					'columns'        => (int) $related_products_settings['columns'],
				)
			);
			?>
		<?php endif; ?>
		<?php $zone_renderer->render( 'product_after_related', array( 'context' => 'product' ) ); ?>
	</div>
</div>
