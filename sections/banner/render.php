<?php
/**
 * Banner section render template.
 *
 * @package StarterKit
 */

$title        = isset( $attributes['title'] ) ? sanitize_text_field( (string) $attributes['title'] ) : '';
$image        = isset( $attributes['image'] ) ? esc_url_raw( (string) $attributes['image'] ) : '';
$link         = isset( $attributes['link'] ) ? esc_url_raw( (string) $attributes['link'] ) : '';
$has_overlay  = ! empty( $attributes['overlay'] ) && '0' !== (string) $attributes['overlay'];
$content_html = '';
$classes      = array( 'starterkit-section', 'starterkit-section--banner' );
$style        = '';

if ( '' !== trim( $content ) ) {
	$content_html = wpautop( do_shortcode( $content ) );
}

if ( '' !== $image ) {
	$classes[] = 'starterkit-section--banner-has-image';
	$style     = ' style="background-image: url(' . esc_url( $image ) . ');"';
}

if ( $has_overlay && '' !== $image ) {
	$classes[] = 'starterkit-section--banner-has-overlay';
}

if ( '' === $title && '' === trim( wp_strip_all_tags( $content_html ) ) && '' === $image ) {
	return;
}
?>
<section class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"<?php echo $style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( '' !== $link ) : ?>
		<a class="starterkit-section-banner__link" href="<?php echo esc_url( $link ); ?>" aria-label="<?php echo esc_attr( '' !== $title ? $title : __( 'Banner link', 'starterkit' ) ); ?>"></a>
	<?php endif; ?>
	<div class="starterkit-section__inner starterkit-section-banner">
		<?php if ( '' !== $title ) : ?>
			<h2 class="starterkit-section__title"><?php echo esc_html( $title ); ?></h2>
		<?php endif; ?>
		<?php if ( '' !== $content_html ) : ?>
			<div class="starterkit-section__content"><?php echo wp_kses_post( $content_html ); ?></div>
		<?php endif; ?>
	</div>
</section>
