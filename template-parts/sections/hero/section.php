<?php
/**
 * Hero section template.
 *
 * @var array<string, mixed> $content
 * @var array<string, mixed> $style
 *
 * @package StarterKit
 */

$image_url = ! empty( $content['background_image_id'] ) ? wp_get_attachment_image_url( (int) $content['background_image_id'], 'full' ) : '';
$panel_style = array();

if ( ! empty( $style['background_color'] ) ) {
	$panel_style[] = 'background-color:' . sanitize_text_field( (string) $style['background_color'] );
}

if ( $image_url ) {
	$panel_style[] = 'background-image:linear-gradient(135deg, rgba(255, 249, 239, 0.92), rgba(255, 255, 255, 0.72)),url(' . esc_url_raw( $image_url ) . ')';
}
?>
<section class="starterkit-section starterkit-section--hero align-<?php echo esc_attr( $content['alignment'] ?? 'left' ); ?>">
	<div class="container hero-panel" <?php if ( ! empty( $panel_style ) ) : ?>style="<?php echo esc_attr( implode( ';', $panel_style ) ); ?>"<?php endif; ?>>
		<?php if ( ! empty( $content['eyebrow'] ) ) : ?>
			<p class="section-eyebrow"><?php echo esc_html( $content['eyebrow'] ); ?></p>
		<?php endif; ?>
		<h2><?php echo esc_html( $content['heading'] ?? '' ); ?></h2>
		<p><?php echo esc_html( $content['subheading'] ?? '' ); ?></p>
		<div class="hero-actions">
			<?php if ( ! empty( $content['primary_button_text'] ) ) : ?>
				<a class="button button-primary" href="<?php echo esc_url( $content['primary_button_url'] ?? '#' ); ?>"><?php echo esc_html( $content['primary_button_text'] ); ?></a>
			<?php endif; ?>
			<?php if ( ! empty( $content['secondary_button_text'] ) ) : ?>
				<a class="button button-secondary" href="<?php echo esc_url( $content['secondary_button_url'] ?? '#' ); ?>"><?php echo esc_html( $content['secondary_button_text'] ); ?></a>
			<?php endif; ?>
		</div>
	</div>
</section>
