<?php
/**
 * Promo banner section template.
 *
 * @var array<string, mixed> $content
 * @var array<string, mixed> $style
 *
 * @package StarterKit
 */

$inline_style = ! empty( $style['background_color'] ) ? 'style="background:' . esc_attr( (string) $style['background_color'] ) . '"' : '';
?>
<section class="starterkit-section starterkit-section--banner">
	<div class="container banner-panel" <?php echo $inline_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<div>
			<h2><?php echo esc_html( $content['heading'] ?? '' ); ?></h2>
			<p><?php echo esc_html( $content['content'] ?? '' ); ?></p>
		</div>
		<?php if ( ! empty( $content['button_text'] ) ) : ?>
			<a class="button button-primary" href="<?php echo esc_url( $content['button_url'] ?? '#' ); ?>"><?php echo esc_html( $content['button_text'] ); ?></a>
		<?php endif; ?>
	</div>
</section>
