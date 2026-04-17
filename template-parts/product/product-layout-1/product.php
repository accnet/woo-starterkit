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
	$wootify_gallery_map = $wootify_service->get_product_gallery_map( (int) $product->get_id() );

	if ( ! empty( $wootify_gallery_map['items'] ) && is_array( $wootify_gallery_map['items'] ) ) {
		foreach ( array_values( $wootify_gallery_map['items'] ) as $index => $gallery_item ) {
			$src = trim( (string) ( $gallery_item['src'] ?? '' ) );

			if ( '' === $src ) {
				continue;
			}

			$alt_text = $product->get_name();

			$gallery_items[] = array(
				'id'                   => $index + 1,
				'full'                 => $src,
				'src'                  => $src,
				'variant_ids'          => array_values( array_unique( array_map( 'intval', (array) ( $gallery_item['variant_ids'] ?? array() ) ) ) ),
				'featured_variant_ids' => array_values( array_unique( array_map( 'intval', (array) ( $gallery_item['featured_variant_ids'] ?? array() ) ) ) ),
				'main'                 => sprintf(
					'<img class="starterkit-product-gallery__image-image" src="%1$s" alt="%2$s" loading="%3$s" fetchpriority="%4$s">',
					esc_url( $src ),
					esc_attr( $alt_text ),
					0 === $index ? 'eager' : 'lazy',
					0 === $index ? 'high' : 'auto'
				),
				'thumb'                => sprintf(
					'<img class="starterkit-product-gallery__thumb-image" src="%1$s" alt="%2$s" loading="lazy">',
					esc_url( $src ),
					esc_attr( $alt_text )
				),
			);
		}
	}
}

if ( empty( $gallery_items ) ) {
	$image_ids = array();

	if ( $product->get_image_id() ) {
		$image_ids[] = (int) $product->get_image_id();
	}

	$image_ids = array_values(
		array_unique(
			array_merge(
				$image_ids,
				array_map( 'intval', $product->get_gallery_image_ids() )
			)
		)
	);

	foreach ( $image_ids as $index => $image_id ) {
		$full_url = wp_get_attachment_image_url( $image_id, 'full' );

		if ( ! $full_url ) {
			continue;
		}

		$alt_text = (string) get_post_meta( $image_id, '_wp_attachment_image_alt', true );
		$alt_text = '' !== trim( $alt_text ) ? $alt_text : $product->get_name();

		$gallery_items[] = array(
			'id'                   => $image_id,
			'full'                 => $full_url,
			'src'                  => (string) wp_get_attachment_image_url( $image_id, 'woocommerce_single' ),
			'variant_ids'          => array(),
			'featured_variant_ids' => array(),
			'main'                 => wp_get_attachment_image(
				$image_id,
				'woocommerce_single',
				false,
				array(
					'class'         => 'starterkit-product-gallery__image-image',
					'alt'           => $alt_text,
					'loading'       => 0 === $index ? 'eager' : 'lazy',
					'fetchpriority' => 0 === $index ? 'high' : 'auto',
				)
			),
			'thumb'                => wp_get_attachment_image(
				$image_id,
				'woocommerce_gallery_thumbnail',
				false,
				array(
					'class'   => 'starterkit-product-gallery__thumb-image',
					'alt'     => $alt_text,
					'loading' => 'lazy',
				)
			),
		);
	}
}

if ( empty( $gallery_items ) ) {
	$gallery_items[] = array(
		'id'                   => 0,
		'full'                 => wc_placeholder_img_src( 'woocommerce_single' ),
		'src'                  => wc_placeholder_img_src( 'woocommerce_single' ),
		'variant_ids'          => array(),
		'featured_variant_ids' => array(),
		'main'                 => wc_placeholder_img( 'woocommerce_single', array( 'class' => 'starterkit-product-gallery__image-image' ) ),
		'thumb'                => wc_placeholder_img( 'woocommerce_gallery_thumbnail', array( 'class' => 'starterkit-product-gallery__thumb-image' ) ),
	);
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
										<?php echo wp_kses_post( $gallery_item['thumb'] ); ?>
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
										<?php echo wp_kses_post( $gallery_item['main'] ); ?>
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
