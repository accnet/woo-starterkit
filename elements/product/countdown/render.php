<?php
/**
 * Countdown element render template.
 *
 * @package StarterKit
 */

$title    = isset( $settings['title'] ) ? (string) $settings['title'] : '';
$end_time = isset( $settings['end_time'] ) ? (string) $settings['end_time'] : '';
?>
<div class="starterkit-builder-card starterkit-builder-card--countdown starterkit-element-countdown" data-builder-countdown="<?php echo esc_attr( $end_time ); ?>">
	<div class="container starterkit-builder-card__inner">
		<?php if ( '' !== $title ) : ?>
			<strong class="starterkit-builder-card__title"><?php echo esc_html( $title ); ?></strong>
		<?php endif; ?>
		<div class="starterkit-builder-countdown__value"><?php echo esc_html( $end_time ? $end_time : __( 'Set an end time', 'starterkit' ) ); ?></div>
	</div>
</div>
