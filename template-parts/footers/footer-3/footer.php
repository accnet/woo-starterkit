<?php
/**
 * Footer preset 3.
 *
 * @package StarterKit
 */
?>
<footer class="site-footer site-footer--preset-3">
	<?php starterkit()->zone_renderer()->render( 'footer_top', array( 'context' => 'master' ) ); ?>
	<div class="container footer-bottom footer-bottom--compact footer-bottom--preset-3">
		<p><?php bloginfo( 'name' ); ?>. <?php esc_html_e( 'Built for structured commerce pages.', 'starterkit' ); ?></p>
	</div>
	<?php starterkit()->zone_renderer()->render( 'footer_bottom', array( 'context' => 'master' ) ); ?>
</footer>
