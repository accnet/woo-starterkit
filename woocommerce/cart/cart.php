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
	<?php get_template_part( 'template-parts/commerce/cart/cart', 'page' ); ?>
</main>
<?php
get_footer( 'shop' );
