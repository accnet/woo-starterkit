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
		<div class="footer-col">
			<?php if ( is_active_sidebar( 'footer-1' ) ) : ?>
				<?php dynamic_sidebar( 'footer-1' ); ?>
			<?php else : ?>
				<h3 class="footer-col__title"><?php bloginfo( 'name' ); ?></h3>
				<p class="footer-col__desc"><?php esc_html_e( 'Structured theme builder for WordPress and WooCommerce.', 'starterkit' ); ?></p>
			<?php endif; ?>
		</div>
		<div class="footer-col">
			<?php if ( is_active_sidebar( 'footer-2' ) ) : ?>
				<?php dynamic_sidebar( 'footer-2' ); ?>
			<?php else : ?>
				<h3 class="footer-col__title"><?php esc_html_e( 'Quick Links', 'starterkit' ); ?></h3>
				<?php wp_nav_menu( array( 'theme_location' => 'footer', 'fallback_cb' => false ) ); ?>
			<?php endif; ?>
		</div>
		<div class="footer-col">
			<?php if ( is_active_sidebar( 'footer-3' ) ) : ?>
				<?php dynamic_sidebar( 'footer-3' ); ?>
			<?php else : ?>
				<h3 class="footer-col__title"><?php esc_html_e( 'Support', 'starterkit' ); ?></h3>
			<?php endif; ?>
		</div>
		<div class="footer-col">
			<?php if ( is_active_sidebar( 'footer-4' ) ) : ?>
				<?php dynamic_sidebar( 'footer-4' ); ?>
			<?php else : ?>
				<h3 class="footer-col__title"><?php esc_html_e( 'Contact', 'starterkit' ); ?></h3>
			<?php endif; ?>
		</div>
	</div>
	<div class="container footer-bottom footer-bottom--preset-1">
		<?php starterkit_render_slot( 'footer_bottom' ); ?>
		<p>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?></p>
	</div>
</footer>
