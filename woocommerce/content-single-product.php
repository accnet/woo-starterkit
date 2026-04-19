<?php
/**
 * Single product content template override.
 *
 * @package StarterKit
 */

defined( 'ABSPATH' ) || exit;

global $product;

$layout = function_exists( 'starterkit' ) ? starterkit()->layout_resolver()->resolve( 'product' ) : null;

do_action( 'woocommerce_before_single_product' );

if ( post_password_required() ) {
	echo get_the_password_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}
?>
<div id="product-<?php the_ID(); ?>" <?php wc_product_class( '', $product ); ?>>
	<?php if ( ! empty( $layout['template'] ) ) : ?>
		<?php
		$template = get_template_directory() . '/' . $layout['template'];

		if ( file_exists( $template ) ) {
			include $template;
		}
		?>
	<?php else : ?>
		<?php do_action( 'woocommerce_before_single_product_summary' ); ?>

		<div class="summary entry-summary">
			<?php do_action( 'woocommerce_single_product_summary' ); ?>
		</div>

		<?php do_action( 'woocommerce_after_single_product_summary' ); ?>
	<?php endif; ?>
</div>

<?php do_action( 'woocommerce_after_single_product' ); ?>
