<?php
/**
 * Footer preset 2.
 *
 * @package StarterKit
 */
?>
<footer class="site-footer site-footer--preset-2">
	<?php starterkit_render_slot( 'footer_top' ); ?>
	<div class="container footer-banner">
		<h3><?php esc_html_e( 'Join the newsletter', 'starterkit' ); ?></h3>
		<p><?php esc_html_e( 'Pair layout presets with reusable campaigns and content blocks.', 'starterkit' ); ?></p>
	</div>
	<div class="container footer-grid">
		<div>
			<h3><?php bloginfo( 'name' ); ?></h3>
		</div>
		<div>
			<?php wp_nav_menu( array( 'theme_location' => 'footer', 'fallback_cb' => false ) ); ?>
		</div>
	</div>
	<div class="container footer-bottom">
		<?php starterkit_render_slot( 'footer_bottom' ); ?>
		<p><?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'description' ); ?></p>
	</div>
</footer>
