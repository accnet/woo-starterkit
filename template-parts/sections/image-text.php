<?php
/**
 * Image and text section template.
 *
 * @var array<string, mixed> $content
 *
 * @package StarterKit
 */

$layout = ! empty( $content['layout'] ) ? (string) $content['layout'] : 'image-left';
$image  = ! empty( $content['image_id'] ) ? wp_get_attachment_image( (int) $content['image_id'], 'large' ) : '';
?>
<section class="starterkit-section starterkit-section--image-text <?php echo esc_attr( $layout ); ?>">
	<div class="container image-text-shell">
		<div class="image-text-media"><?php echo wp_kses_post( $image ); ?></div>
		<div class="image-text-copy">
			<h2><?php echo esc_html( $content['heading'] ?? '' ); ?></h2>
			<p><?php echo esc_html( $content['content'] ?? '' ); ?></p>
		</div>
	</div>
</section>
