<?php
/**
 * Newsletter element render template.
 *
 * @package StarterKit
 */

$title       = isset( $settings['title'] ) ? (string) $settings['title'] : '';
$description = isset( $settings['description'] ) ? (string) $settings['description'] : '';
$button_text = isset( $settings['button_text'] ) ? (string) $settings['button_text'] : '';
?>
<div class="starterkit-element-card starterkit-element-card--newsletter starterkit-element-newsletter">
	<div class="container starterkit-element-card__inner">
		<div>
			<?php if ( '' !== $title ) : ?>
				<strong class="starterkit-element-card__title"><?php echo esc_html( $title ); ?></strong>
			<?php endif; ?>
			<?php if ( '' !== $description ) : ?>
				<div class="starterkit-element-card__content"><?php echo wp_kses_post( wpautop( $description ) ); ?></div>
			<?php endif; ?>
		</div>
		<div class="starterkit-element-newsletter__form">
			<input type="email" value="" placeholder="<?php esc_attr_e( 'Email address', 'starterkit' ); ?>" readonly>
			<button type="button" class="button button-primary"><?php echo esc_html( $button_text ); ?></button>
		</div>
	</div>
</div>
