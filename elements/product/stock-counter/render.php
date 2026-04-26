<?php
/**
 * Stock counter element render template.
 *
 * @package StarterKit
 */

$title               = isset( $settings['title'] ) ? (string) $settings['title'] : '';
$threshold           = isset( $settings['low_stock_threshold'] ) ? max( 1, absint( $settings['low_stock_threshold'] ) ) : 10;
$show_exact_quantity = ! empty( $settings['show_exact_quantity'] ) && '0' !== (string) $settings['show_exact_quantity'];
$in_stock_text       = isset( $settings['in_stock_text'] ) ? (string) $settings['in_stock_text'] : '';
$low_stock_text      = isset( $settings['low_stock_text'] ) ? (string) $settings['low_stock_text'] : '';
$out_of_stock_text   = isset( $settings['out_of_stock_text'] ) ? (string) $settings['out_of_stock_text'] : '';
$message             = '';
$status              = 'unknown';

if ( function_exists( 'wc_get_product' ) ) {
	global $product;

	$stock_product = $product instanceof \WC_Product ? $product : wc_get_product( get_the_ID() );

	if ( $stock_product instanceof \WC_Product ) {
		$status   = $stock_product->get_stock_status();
		$quantity = $stock_product->managing_stock() ? $stock_product->get_stock_quantity() : null;

		if ( ! $stock_product->is_in_stock() ) {
			$message = $out_of_stock_text;
		} elseif ( null !== $quantity && $quantity <= $threshold ) {
			$message = str_replace( '{quantity}', number_format_i18n( max( 0, (int) $quantity ) ), $low_stock_text );
			$status  = 'low-stock';
		} else {
			$message = $in_stock_text;

			if ( $show_exact_quantity && null !== $quantity ) {
				$message = sprintf(
					/* translators: %s: stock quantity. */
					__( '%s available and ready to ship', 'starterkit' ),
					number_format_i18n( max( 0, (int) $quantity ) )
				);
			}
		}
	}
}

if ( '' === $message ) {
	return;
}
?>
<div class="starterkit-element-card starterkit-element-card--stock starterkit-element-card--stock-<?php echo esc_attr( sanitize_html_class( $status ) ); ?>">
	<div class="container starterkit-element-card__inner">
		<?php if ( '' !== $title ) : ?>
			<strong class="starterkit-element-card__title"><?php echo esc_html( $title ); ?></strong>
		<?php endif; ?>
		<div class="starterkit-element-card__content"><?php echo esc_html( $message ); ?></div>
	</div>
</div>
