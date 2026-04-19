<?php
/**
 * Topbar 1 element render template.
 *
 * @package StarterKit
 */

$left_text   = isset( $settings['left_text'] ) ? (string) $settings['left_text'] : '';
$center_text = isset( $settings['center_text'] ) ? (string) $settings['center_text'] : '';
$right_text  = isset( $settings['right_text'] ) ? (string) $settings['right_text'] : '';
$background  = isset( $settings['background'] ) ? sanitize_hex_color( (string) $settings['background'] ) : '#111111';
$text_color  = isset( $settings['text_color'] ) ? sanitize_hex_color( (string) $settings['text_color'] ) : '#ffffff';
$style       = sprintf( 'background:%s;color:%s;', $background ? $background : '#111111', $text_color ? $text_color : '#ffffff' );
?>
<div class="starterkit-element-topbar-1" style="<?php echo esc_attr( $style ); ?>">
	<div class="container starterkit-element-topbar-1__inner">
		<span><?php echo esc_html( $left_text ); ?></span>
		<strong><?php echo esc_html( $center_text ); ?></strong>
		<span><?php echo esc_html( $right_text ); ?></span>
	</div>
</div>
