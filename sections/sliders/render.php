<?php
/**
 * Sliders section render template.
 *
 * @package StarterKit
 */

$autoplay        = isset( $attributes['autoplay'] ) ? absint( $attributes['autoplay'] ) : 5000;
$speed           = isset( $attributes['speed'] ) ? absint( $attributes['speed'] ) : 600;
$speed           = $speed > 0 ? $speed : 600;
$show_pagination = ! empty( $attributes['show_pagination'] ) && '0' !== (string) $attributes['show_pagination'];
$show_navigation = ! empty( $attributes['show_navigation'] ) && '0' !== (string) $attributes['show_navigation'];
$lines           = preg_split( '/\r\n|\r|\n/', (string) $content );
$slides          = array();

foreach ( is_array( $lines ) ? $lines : array() as $line ) {
	$line = trim( (string) $line );

	if ( '' === $line ) {
		continue;
	}

	$parts     = array_map( 'trim', explode( '|', $line, 3 ) );
	$image_url = isset( $parts[0] ) ? esc_url_raw( $parts[0] ) : '';

	if ( '' === $image_url ) {
		continue;
	}

	$slides[] = array(
		'image_url' => $image_url,
		'heading'   => isset( $parts[1] ) ? sanitize_text_field( $parts[1] ) : '',
		'link_url'  => isset( $parts[2] ) ? esc_url_raw( $parts[2] ) : '',
	);
}

if ( empty( $slides ) ) {
	return;
}

$has_slider = count( $slides ) > 1;
?>
<section class="starterkit-section starterkit-section--sliders">
	<div
		class="starterkit-section-sliders js-starterkit-section-slider<?php echo $has_slider ? ' swiper' : ' is-static'; ?>"
		data-autoplay="<?php echo esc_attr( (string) $autoplay ); ?>"
		data-speed="<?php echo esc_attr( (string) $speed ); ?>"
	>
		<div class="<?php echo $has_slider ? 'swiper-wrapper' : 'starterkit-section-sliders__static'; ?>">
			<?php foreach ( $slides as $slide ) : ?>
				<?php
				$tag      = '' !== $slide['link_url'] ? 'a' : 'div';
				$href     = '' !== $slide['link_url'] ? ' href="' . esc_url( $slide['link_url'] ) . '"' : '';
				$tag_attr = 'a' === $tag ? $href : '';
				?>
				<<?php echo tag_escape( $tag ); ?> class="starterkit-section-sliders__slide<?php echo $has_slider ? ' swiper-slide' : ''; ?>"<?php echo $tag_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
					<img src="<?php echo esc_url( $slide['image_url'] ); ?>" alt="<?php echo esc_attr( $slide['heading'] ); ?>" loading="lazy">
					<?php if ( '' !== $slide['heading'] ) : ?>
						<span class="starterkit-section-sliders__caption"><?php echo esc_html( $slide['heading'] ); ?></span>
					<?php endif; ?>
				</<?php echo tag_escape( $tag ); ?>>
			<?php endforeach; ?>
		</div>
		<?php if ( $has_slider && $show_pagination ) : ?>
			<div class="starterkit-section-sliders__pagination swiper-pagination"></div>
		<?php endif; ?>
		<?php if ( $has_slider && $show_navigation ) : ?>
			<button class="starterkit-section-sliders__nav starterkit-section-sliders__nav--prev swiper-button-prev" type="button" aria-label="<?php esc_attr_e( 'Previous slide', 'starterkit' ); ?>"></button>
			<button class="starterkit-section-sliders__nav starterkit-section-sliders__nav--next swiper-button-next" type="button" aria-label="<?php esc_attr_e( 'Next slide', 'starterkit' ); ?>"></button>
		<?php endif; ?>
	</div>
</section>
