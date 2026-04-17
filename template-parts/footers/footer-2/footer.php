<?php
/**
 * Footer preset 2.
 *
 * @package StarterKit
 */
?>
<footer class="site-footer site-footer--preset-2">
	<div class="container footer-banner footer-banner--preset-2">
		<div>
			<h3><?php esc_html_e( 'Join the newsletter', 'starterkit' ); ?></h3>
			<p><?php esc_html_e( 'Pair layout presets with reusable campaigns and content blocks.', 'starterkit' ); ?></p>
		</div>
		<a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" class="button button-secondary"><?php esc_html_e( 'Contact Us', 'starterkit' ); ?></a>
	</div>
	<div class="container footer-grid footer-grid--preset-2">
		<div>
			<h3><?php bloginfo( 'name' ); ?></h3>
		</div>
		<div class="footer-navigation">
			<?php wp_nav_menu( array( 'theme_location' => 'footer', 'fallback_cb' => false ) ); ?>
		</div>
	</div>
	<div class="container footer-bottom footer-bottom--preset-2">
		<p><?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'description' ); ?></p>
	</div>
</footer>
