<?php
/**
 * WooCommerce cart page template override.
 *
 * @package StarterKit
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );
?>
<main id="primary" class="site-main starterkit-woocommerce-cart">
	<div class="container">
		<?php
		do_action( 'woocommerce_before_cart' );
		get_template_part( 'template-parts/commerce/cart/cart', 'page' );
		do_action( 'woocommerce_after_cart' );
		?>
	</div>
</main>
<?php
get_footer( 'shop' );
