<?php
/**
 * StarterKit checkout form.
 *
 * @package WooCommerce\Templates
 * @version 9.4.0
 */

defined( 'ABSPATH' ) || exit;

$layout_manager = function_exists( 'starterkit' ) ? starterkit()->checkout_layout_manager() : null;

do_action( 'woocommerce_before_checkout_form', $checkout );

if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'woocommerce' ) ) );
	return;
}

if ( ! $layout_manager ) {
	$default_template = WC()->plugin_path() . '/templates/checkout/form-checkout.php';

	if ( file_exists( $default_template ) ) {
		include $default_template;
	}

	return;
}

$steps              = $layout_manager->get_steps();
$summary_components = $layout_manager->get_summary_components();
$needs_shipping    = $layout_manager->cart_needs_shipping();
?>

<div class="starterkit-checkout starterkit-woocommerce-checkout starterkit-checkout--onepage" data-checkout-root data-checkout-mode="onepage" data-requires-shipping="<?php echo esc_attr( $needs_shipping ? '1' : '0' ); ?>">
	<form name="checkout" method="post" class="starterkit-checkout__form checkout woocommerce-checkout" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data" aria-label="<?php echo esc_attr__( 'Checkout', 'woocommerce' ); ?>" novalidate>
		<div class="starterkit-checkout__grid">
			<div class="starterkit-checkout__main">
				<div class="starterkit-checkout__notice-region">
					<?php wc_print_notices(); ?>
				</div>

				<button type="button" class="starterkit-checkout-mobile-summary" data-mobile-summary-toggle aria-expanded="false">
					<span><?php esc_html_e( 'Show order summary', 'starterkit' ); ?></span>
					<strong><?php echo wp_kses_post( WC()->cart ? WC()->cart->get_total() : '' ); ?></strong>
				</button>

				<?php if ( $checkout->get_checkout_fields() ) : ?>
					<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

					<div class="starterkit-checkout-panels" id="customer_details">
						<?php foreach ( $steps as $step ) : ?>
							<?php
							$step_id  = (string) $step['id'];
							$callback = isset( $step['render_callback'] ) ? $step['render_callback'] : null;
							?>
							<section id="starterkit-checkout-section-<?php echo esc_attr( $step_id ); ?>" class="starterkit-checkout-panel starterkit-checkout-panel--<?php echo esc_attr( sanitize_html_class( $step_id ) ); ?>" data-checkout-step="<?php echo esc_attr( $step_id ); ?>">
								<?php if ( is_callable( $callback ) ) : ?>
									<?php call_user_func( $callback, $checkout ); ?>
								<?php endif; ?>
							</section>
						<?php endforeach; ?>
					</div>

					<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>
				<?php endif; ?>
			</div>

			<?php do_action( 'woocommerce_checkout_before_order_review_heading' ); ?>

			<aside class="starterkit-checkout__sidebar" data-checkout-summary>
				<div id="order_review" class="starterkit-checkout-summary woocommerce-checkout-review-order">
					<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>

					<?php foreach ( $summary_components as $component ) : ?>
						<?php
						$component_id = isset( $component['id'] ) ? (string) $component['id'] : '';
						$callback     = isset( $component['render_callback'] ) ? $component['render_callback'] : null;
						?>
						<section class="starterkit-checkout-summary__component starterkit-checkout-summary__component--<?php echo esc_attr( sanitize_html_class( $component_id ) ); ?>">
							<?php if ( is_callable( $callback ) ) : ?>
								<?php call_user_func( $callback ); ?>
							<?php endif; ?>
						</section>
					<?php endforeach; ?>

					<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>
				</div>
			</aside>
		</div>
	</form>
</div>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
