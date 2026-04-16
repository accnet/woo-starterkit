<?php
/**
 * CTA section template.
 *
 * @var array<string, mixed> $content
 * @var array<string, mixed> $style
 *
 * @package StarterKit
 */

$image_url = ! empty( $content['background_image_id'] ) ? wp_get_attachment_image_url( (int) $content['background_image_id'], 'full' ) : '';
$panel_style = $image_url ? 'background-image:linear-gradient(135deg, rgba(15, 23, 42, 0.85), rgba(15, 23, 42, 0.68)),url(' . esc_url_raw( $image_url ) . ');' : '';
$tone = ! empty( $style['tone'] ) ? $style['tone'] : 'dark';
?>
<section class="starterkit-section starterkit-section--cta starterkit-section--cta-<?php echo esc_attr( $tone ); ?>">
	<div class="container cta-panel" <?php if ( $panel_style ) : ?>style="<?php echo esc_attr( $panel_style ); ?>"<?php endif; ?>>
		<div>
			<h2><?php echo esc_html( $content['heading'] ?? '' ); ?></h2>
			<p><?php echo esc_html( $content['content'] ?? '' ); ?></p>
		</div>
		<?php if ( ! empty( $content['button_text'] ) ) : ?>
			<a class="button button-primary" href="<?php echo esc_url( $content['button_url'] ?? '#' ); ?>"><?php echo esc_html( $content['button_text'] ); ?></a>
		<?php endif; ?>
	</div>
</section>
