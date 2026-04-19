<?php
/**
 * Archive layout preset 2 helper markup.
 *
 * @package StarterKit
 */

defined( 'ABSPATH' ) || exit;

$zone_renderer = starterkit()->zone_renderer();
?>
<div class="starterkit-archive-preset starterkit-archive-preset--2">
	<div class="starterkit-archive-preset__sidebar">
		<?php $zone_renderer->render( 'archive_sidebar_top', array( 'context' => 'archive' ) ); ?>
		<?php $zone_renderer->render( 'archive_sidebar_bottom', array( 'context' => 'archive' ) ); ?>
	</div>
	<div class="starterkit-archive-preset__content">
		<?php $zone_renderer->render( 'archive_before_loop', array( 'context' => 'archive' ) ); ?>
		<?php woocommerce_product_loop_start(); ?>
		<?php while ( have_posts() ) : the_post(); ?>
			<?php wc_get_template_part( 'content', 'product' ); ?>
		<?php endwhile; ?>
		<?php woocommerce_product_loop_end(); ?>
		<?php $zone_renderer->render( 'archive_after_loop', array( 'context' => 'archive' ) ); ?>
	</div>
</div>
