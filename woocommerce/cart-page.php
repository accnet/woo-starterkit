<?php
/**
 * Theme-owned cart page shell.
 *
 * @package StarterKit
 */

defined( 'ABSPATH' ) || exit;

if ( have_posts() ) {
	the_post();
}

get_header( 'shop' );
?>
<main id="primary" class="site-main starterkit-woocommerce-shell starterkit-woocommerce-shell--cart">
	<div class="container">
		<?php echo do_shortcode( '[woocommerce_cart]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
</main>
<?php
get_footer( 'shop' );
