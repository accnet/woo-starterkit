<?php
/**
 * Category banner element render template.
 *
 * @package StarterKit
 */

$queried_object = get_queried_object();
$custom_title   = isset( $settings['title'] ) ? trim( (string) $settings['title'] ) : '';
$custom_content = isset( $settings['content'] ) ? trim( (string) $settings['content'] ) : '';
$title_source   = isset( $settings['title_source'] ) ? sanitize_key( (string) $settings['title_source'] ) : 'dynamic';
$content_source = isset( $settings['content_source'] ) ? sanitize_key( (string) $settings['content_source'] ) : 'dynamic';
$image_source   = isset( $settings['image_source'] ) ? sanitize_key( (string) $settings['image_source'] ) : 'term';
$show_image     = ! empty( $settings['show_image'] ) && '0' !== (string) $settings['show_image'];
$style          = isset( $settings['style'] ) ? sanitize_html_class( (string) $settings['style'] ) : 'default';
$alignment      = isset( $settings['alignment'] ) ? sanitize_html_class( (string) $settings['alignment'] ) : 'left';
$button_label   = isset( $settings['button_label'] ) ? trim( (string) $settings['button_label'] ) : '';
$button_url     = isset( $settings['button_url'] ) ? esc_url_raw( (string) $settings['button_url'] ) : '';
$max_width      = isset( $settings['max_width'] ) ? absint( $settings['max_width'] ) : 960;
$fallback_id    = isset( $settings['fallback_image_id'] ) ? absint( $settings['fallback_image_id'] ) : 0;

$style     = in_array( $style, array( 'default', 'accent' ), true ) ? $style : 'default';
$alignment = in_array( $alignment, array( 'left', 'center' ), true ) ? $alignment : 'left';
$max_width = min( 1280, max( 640, $max_width ) );

$dynamic_title = '';

if ( function_exists( 'woocommerce_page_title' ) ) {
	$dynamic_title = trim( (string) woocommerce_page_title( false ) );
}

if ( '' === $dynamic_title && $queried_object instanceof WP_Term ) {
	$dynamic_title = $queried_object->name;
}

$dynamic_content = '';

if ( $queried_object instanceof WP_Term ) {
	$dynamic_content = trim( (string) term_description( $queried_object->term_id, $queried_object->taxonomy ) );
} elseif ( function_exists( 'wc_get_page_id' ) ) {
	$shop_page_id = (int) wc_get_page_id( 'shop' );

	if ( $shop_page_id > 0 ) {
		$dynamic_content = trim( (string) get_post_field( 'post_excerpt', $shop_page_id ) );
	}
}

$title = '';

if ( 'custom' === $title_source ) {
	$title = $custom_title;
} elseif ( 'hidden' !== $title_source ) {
	$title = '' !== $dynamic_title ? $dynamic_title : $custom_title;
}

$content = '';

if ( 'custom' === $content_source ) {
	$content = $custom_content;
} elseif ( 'hidden' !== $content_source ) {
	$content = '' !== $dynamic_content ? $dynamic_content : $custom_content;
}

$image_id = 0;

if ( $show_image && 'none' !== $image_source ) {
	if ( 'term' === $image_source && $queried_object instanceof WP_Term ) {
		$image_id = absint( get_term_meta( $queried_object->term_id, 'thumbnail_id', true ) );
	}

	if ( ! $image_id || 'custom' === $image_source ) {
		$image_id = $fallback_id;
	}
}

$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'large' ) : '';
$image_alt = $image_id ? get_post_meta( $image_id, '_wp_attachment_image_alt', true ) : '';
$highlights = isset( $settings['highlights'] ) && is_array( $settings['highlights'] ) ? $settings['highlights'] : array();
$classes    = array(
	'starterkit-element-card',
	'starterkit-element-card--' . $style,
	'starterkit-element-category-banner',
	'starterkit-element-category-banner--' . $alignment,
);

if ( $image_url ) {
	$classes[] = 'starterkit-element-category-banner--has-image';
}

if ( '' === $title && '' === $content && ! $image_url && empty( $highlights ) && ( '' === $button_label || '' === $button_url ) ) {
	return;
}
?>
<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" style="--starterkit-element-card-max-width: <?php echo esc_attr( (string) $max_width ); ?>px;">
	<div class="container starterkit-element-card__inner">
		<?php if ( $image_url ) : ?>
			<figure class="starterkit-element-category-banner__media">
				<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $image_alt ? $image_alt : $title ); ?>" loading="lazy" decoding="async">
			</figure>
		<?php endif; ?>

		<div class="starterkit-element-category-banner__body">
			<?php if ( '' !== $title ) : ?>
				<h3 class="starterkit-element-card__title"><?php echo esc_html( $title ); ?></h3>
			<?php endif; ?>

			<?php if ( '' !== $content ) : ?>
				<div class="starterkit-element-card__content"><?php echo wp_kses_post( wpautop( $content ) ); ?></div>
			<?php endif; ?>

			<?php if ( ! empty( $highlights ) ) : ?>
				<ul class="starterkit-element-category-banner__highlights">
					<?php foreach ( $highlights as $highlight ) : ?>
						<?php
						$highlight_text = is_array( $highlight ) && isset( $highlight['text'] ) ? trim( (string) $highlight['text'] ) : '';

						if ( '' === $highlight_text ) {
							continue;
						}
						?>
						<li><?php echo esc_html( $highlight_text ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<?php if ( '' !== $button_label && '' !== $button_url ) : ?>
				<a class="button button-primary starterkit-element-category-banner__button" href="<?php echo esc_url( $button_url ); ?>"><?php echo esc_html( $button_label ); ?></a>
			<?php endif; ?>
		</div>
	</div>
</div>
