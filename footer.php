<?php
/**
 * Site footer.
 *
 * @package StarterKit
 */

$footer_layout = starterkit()->layout_resolver()->resolve( 'footer' );
?>
	<?php if ( $footer_layout && ! empty( $footer_layout['template'] ) ) : ?>
		<?php include get_template_directory() . '/' . $footer_layout['template']; ?>
	<?php endif; ?>
</div>
<?php wp_footer(); ?>
</body>
</html>
