<?php
/**
 * Archive layout 1.
 *
 * @package StarterKit
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="starterkit-archive-preset starterkit-archive-preset--1">
	<?php woocommerce_product_loop_start(); ?>
	<?php while ( have_posts() ) : the_post(); ?>
		<?php wc_get_template_part( 'content', 'product' ); ?>
	<?php endwhile; ?>
	<?php woocommerce_product_loop_end(); ?>
</div>
