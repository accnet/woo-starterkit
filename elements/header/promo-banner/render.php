<?php
/**
 * Promo banner element render template.
 *
 * @package StarterKit
 */

$title   = isset( $settings['title'] ) ? (string) $settings['title'] : '';
$content = isset( $settings['content'] ) ? (string) $settings['content'] : '';
$style   = isset( $settings['style'] ) ? sanitize_html_class( (string) $settings['style'] ) : 'accent';
?>
<div class="starterkit-element-card starterkit-element-card--<?php echo esc_attr( $style ); ?> starterkit-element-promo-banner">
	<div class="container starterkit-element-card__inner">
		<?php if ( '' !== $title ) : ?>
			<h3 class="starterkit-element-card__title"><?php echo esc_html( $title ); ?></h3>
		<?php endif; ?>
		<?php if ( '' !== $content ) : ?>
			<div class="starterkit-element-card__content"><?php echo wp_kses_post( wpautop( $content ) ); ?></div>
		<?php endif; ?>
	</div>
</div>
