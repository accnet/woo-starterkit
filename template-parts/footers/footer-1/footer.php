<?php
/**
 * Footer preset 1.
 *
 * @package StarterKit
 */
$layout_settings_manager = starterkit()->layout_settings_manager();
$layout_settings         = $layout_settings_manager->get_layout_settings( 'footer-1' );
?>
<footer class="site-footer site-footer--preset-1">
	<?php starterkit()->zone_renderer()->render( 'footer_top', array( 'context' => 'master' ) ); ?>
	<?php echo $layout_settings_manager->render_footer_1_grid( $layout_settings ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<div class="container footer-bottom footer-bottom--preset-1">
		<p>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?></p>
	</div>
	<?php starterkit()->zone_renderer()->render( 'footer_bottom', array( 'context' => 'master' ) ); ?>
</footer>
