<?php
/**
 * Custom cart page layout.
 *
 * @package StarterKit
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="starterkit-cart">
	<?php if ( WC()->cart->is_empty() ) : ?>
		<?php get_template_part( 'template-parts/commerce/cart/cart', 'empty' ); ?>
	<?php else : ?>
		<div class="starterkit-cart__grid">
			<div class="starterkit-cart__main">
				<h1 class="starterkit-cart__title"><?php esc_html_e( 'Shopping Cart', 'starterkit' ); ?></h1>
				<?php get_template_part( 'template-parts/commerce/cart/cart', 'items' ); ?>
			</div>
			<aside class="starterkit-cart__sidebar">
				<?php get_template_part( 'template-parts/commerce/cart/cart', 'summary' ); ?>
			</aside>
		</div>
	<?php endif; ?>
</div>
