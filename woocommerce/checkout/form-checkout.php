<?php
/**
 * WooCommerce checkout form template override.
 *
 * @package StarterKit
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );
?>
<main id="primary" class="site-main starterkit-woocommerce-checkout">
	<?php
	$checkout = WC()->checkout();
	get_template_part( 'template-parts/commerce/checkout/checkout', 'page' );
	?>
</main>
<?php
get_footer( 'shop' );
