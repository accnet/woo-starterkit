<?php
/**
 * Guarantee element render template.
 *
 * @package StarterKit
 */

$title   = isset( $settings['title'] ) ? (string) $settings['title'] : '';
$content = isset( $settings['content'] ) ? (string) $settings['content'] : '';
$style   = isset( $settings['style'] ) ? sanitize_html_class( (string) $settings['style'] ) : 'default';
?>
<div class="starterkit-builder-card starterkit-builder-card--<?php echo esc_attr( $style ); ?>">
	<div class="container starterkit-builder-card__inner">
		<?php if ( '' !== $title ) : ?>
			<strong class="starterkit-builder-card__title"><?php echo esc_html( $title ); ?></strong>
		<?php endif; ?>
		<?php if ( '' !== $content ) : ?>
			<div class="starterkit-builder-card__content"><?php echo wp_kses_post( wpautop( $content ) ); ?></div>
		<?php endif; ?>
	</div>
</div>
