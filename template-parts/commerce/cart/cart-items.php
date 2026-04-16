<?php
/**
 * Cart items table.
 *
 * @package StarterKit
 */

defined( 'ABSPATH' ) || exit;
?>
<form class="starterkit-cart-items" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">
	<?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>

	<div class="starterkit-cart-items__header">
		<span class="starterkit-cart-items__col-product"><?php esc_html_e( 'Product', 'starterkit' ); ?></span>
		<span class="starterkit-cart-items__col-price"><?php esc_html_e( 'Price', 'starterkit' ); ?></span>
		<span class="starterkit-cart-items__col-qty"><?php esc_html_e( 'Quantity', 'starterkit' ); ?></span>
		<span class="starterkit-cart-items__col-total"><?php esc_html_e( 'Subtotal', 'starterkit' ); ?></span>
	</div>

	<?php foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) : ?>
		<?php
		$product   = $cart_item['data'];
		$product_id = $cart_item['product_id'];

		if ( ! $product || ! $product->exists() || $cart_item['quantity'] <= 0 ) {
			continue;
		}

		$permalink = $product->is_visible() ? $product->get_permalink( $cart_item ) : '';
		$quantity  = (int) $cart_item['quantity'];
		?>
		<div class="starterkit-cart-items__row <?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>">
			<div class="starterkit-cart-items__product">
				<div class="starterkit-cart-items__image">
					<?php
					$thumbnail = apply_filters( 'woocommerce_cart_item_thumbnail', $product->get_image( 'woocommerce_thumbnail' ), $cart_item, $cart_item_key );
					echo $permalink ? '<a href="' . esc_url( $permalink ) . '">' . wp_kses_post( $thumbnail ) . '</a>' : wp_kses_post( $thumbnail ); // phpcs:ignore
					?>
				</div>
				<div class="starterkit-cart-items__info">
					<h3 class="starterkit-cart-items__name">
						<?php
						$name = apply_filters( 'woocommerce_cart_item_name', $product->get_name(), $cart_item, $cart_item_key );
						echo $permalink ? '<a href="' . esc_url( $permalink ) . '">' . esc_html( $name ) . '</a>' : esc_html( $name );
						?>
					</h3>
					<?php if ( function_exists( 'wc_get_formatted_cart_item_data' ) ) : ?>
						<div class="starterkit-cart-items__meta">
							<?php echo wp_kses_post( wc_get_formatted_cart_item_data( $cart_item ) ); ?>
						</div>
					<?php endif; ?>
					<button type="submit" name="remove_item" value="<?php echo esc_attr( $cart_item_key ); ?>" class="starterkit-cart-items__remove" aria-label="<?php esc_attr_e( 'Remove item', 'starterkit' ); ?>">
						<?php esc_html_e( 'Remove', 'starterkit' ); ?>
					</button>
				</div>
			</div>

			<div class="starterkit-cart-items__price" data-label="<?php esc_attr_e( 'Price', 'starterkit' ); ?>">
				<?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $product ), $cart_item, $cart_item_key ) ); ?>
			</div>

			<div class="starterkit-cart-items__qty" data-label="<?php esc_attr_e( 'Quantity', 'starterkit' ); ?>">
				<?php
				$product_quantity = woocommerce_quantity_input(
					array(
						'input_name'   => "cart[{$cart_item_key}][qty]",
						'input_value'  => $quantity,
						'max_value'    => $product->get_max_purchase_quantity(),
						'min_value'    => 0,
						'product_name' => $product->get_name(),
					),
					$product,
					false
				);
				echo apply_filters( 'woocommerce_cart_item_quantity', $product_quantity, $cart_item_key, $cart_item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
			</div>

			<div class="starterkit-cart-items__total" data-label="<?php esc_attr_e( 'Subtotal', 'starterkit' ); ?>">
				<?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $product, $quantity ), $cart_item, $cart_item_key ) ); ?>
			</div>
		</div>
	<?php endforeach; ?>

	<div class="starterkit-cart-items__actions">
		<?php if ( wc_coupons_enabled() ) : ?>
			<div class="starterkit-cart-items__coupon">
				<input type="text" name="coupon_code" class="input-text" id="coupon_code" value="" placeholder="<?php esc_attr_e( 'Coupon code', 'starterkit' ); ?>">
				<button type="submit" class="button button-secondary" name="apply_coupon" value="<?php esc_attr_e( 'Apply coupon', 'starterkit' ); ?>"><?php esc_html_e( 'Apply', 'starterkit' ); ?></button>
			</div>
		<?php endif; ?>
		<button type="submit" class="button button-secondary" name="update_cart" value="<?php esc_attr_e( 'Update cart', 'starterkit' ); ?>"><?php esc_html_e( 'Update Cart', 'starterkit' ); ?></button>
		<?php wp_nonce_field( 'woocommerce-cart', '_wpnonce', false ); ?>
		<input type="hidden" name="wc-ajax" value="update_cart">
	</div>
</form>
