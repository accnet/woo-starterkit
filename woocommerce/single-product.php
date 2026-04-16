<?php
/**
 * WooCommerce single product template.
 *
 * @package StarterKit
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );
?>
<main id="primary" class="site-main starterkit-woocommerce-single">
	<div class="container">
		<?php while ( have_posts() ) : ?>
			<?php the_post(); ?>
			<?php wc_get_template_part( 'content', 'single-product' ); ?>
		<?php endwhile; ?>
	</div>
</main>
<?php
get_footer( 'shop' );
