<?php
/**
 * Product badges element render template.
 *
 * @package StarterKit
 */

if ( ! function_exists( 'wc_get_product' ) ) {
	return;
}

global $product;

$badge_product = $product instanceof \WC_Product ? $product : wc_get_product( get_the_ID() );

if ( ! ( $badge_product instanceof \WC_Product ) ) {
	return;
}

$badges   = array();
$new_days = isset( $settings['new_days'] ) ? max( 1, absint( $settings['new_days'] ) ) : 14;

if ( ! empty( $settings['show_sale'] ) && '0' !== (string) $settings['show_sale'] && $badge_product->is_on_sale() ) {
	$badges[] = array(
		'type'  => 'sale',
		'label' => isset( $settings['sale_label'] ) ? (string) $settings['sale_label'] : __( 'Sale', 'starterkit' ),
	);
}

if ( ! empty( $settings['show_featured'] ) && '0' !== (string) $settings['show_featured'] && $badge_product->is_featured() ) {
	$badges[] = array(
		'type'  => 'featured',
		'label' => isset( $settings['featured_label'] ) ? (string) $settings['featured_label'] : __( 'Featured', 'starterkit' ),
	);
}

if ( ! empty( $settings['show_stock'] ) && '0' !== (string) $settings['show_stock'] && ! $badge_product->is_in_stock() ) {
	$badges[] = array(
		'type'  => 'out-of-stock',
		'label' => isset( $settings['out_of_stock_label'] ) ? (string) $settings['out_of_stock_label'] : __( 'Out of stock', 'starterkit' ),
	);
}

if ( ! empty( $settings['show_new'] ) && '0' !== (string) $settings['show_new'] ) {
	$created = $badge_product->get_date_created();

	if ( $created && $created->getTimestamp() >= strtotime( '-' . $new_days . ' days' ) ) {
		$badges[] = array(
			'type'  => 'new',
			'label' => isset( $settings['new_label'] ) ? (string) $settings['new_label'] : __( 'New', 'starterkit' ),
		);
	}
}

$badges = array_values(
	array_filter(
		$badges,
		function( $badge ) {
			return is_array( $badge ) && ! empty( $badge['label'] );
		}
	)
);

if ( empty( $badges ) ) {
	return;
}
?>
<div class="starterkit-element-card starterkit-element-card--badges">
	<div class="container starterkit-element-card__inner">
		<ul class="starterkit-element-list starterkit-element-list--badges">
			<?php foreach ( $badges as $badge ) : ?>
				<li class="starterkit-element-badge starterkit-element-badge--<?php echo esc_attr( sanitize_html_class( (string) $badge['type'] ) ); ?>"><?php echo esc_html( (string) $badge['label'] ); ?></li>
			<?php endforeach; ?>
		</ul>
	</div>
</div>
