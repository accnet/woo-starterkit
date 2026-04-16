<?php
/**
 * Footer preset 1.
 *
 * @package StarterKit
 */
?>
<footer class="site-footer site-footer--preset-1">
	<?php starterkit_render_slot( 'footer_top' ); ?>
	<div class="container footer-grid footer-grid--preset-1">
		<div class="footer-copy">
			<h3><?php bloginfo( 'name' ); ?></h3>
			<p><?php esc_html_e( 'Structured theme builder for WordPress and WooCommerce.', 'starterkit' ); ?></p>
		</div>
		<div class="footer-navigation">
			<?php wp_nav_menu( array( 'theme_location' => 'footer', 'fallback_cb' => false ) ); ?>
		</div>
	</div>
	<div class="container footer-bottom footer-bottom--preset-1">
		<?php starterkit_render_slot( 'footer_bottom' ); ?>
		<p><?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?></p>
	</div>
</footer>
