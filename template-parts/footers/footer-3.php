<?php
/**
 * Footer preset 3.
 *
 * @package StarterKit
 */
?>
<footer class="site-footer site-footer--preset-3">
	<?php starterkit_render_slot( 'footer_top' ); ?>
	<div class="container footer-bottom footer-bottom--compact">
		<p><?php bloginfo( 'name' ); ?>. <?php esc_html_e( 'Built for structured commerce pages.', 'starterkit' ); ?></p>
		<?php starterkit_render_slot( 'footer_bottom' ); ?>
	</div>
</footer>
