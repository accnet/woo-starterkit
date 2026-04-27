<?php
/**
 * Description accordion element render template.
 *
 * @package StarterKit
 */

$title             = isset( $settings['title'] ) ? trim( (string) $settings['title'] ) : '';
$description_label = isset( $settings['description_label'] ) ? trim( (string) $settings['description_label'] ) : __( 'Description', 'starterkit' );
$open_first_item   = ! empty( $settings['open_first_item'] ) && '0' !== (string) $settings['open_first_item'];
$rows              = isset( $settings['items'] ) && is_array( $settings['items'] ) ? $settings['items'] : array();
$description_html  = '';
$items             = array();

if ( function_exists( 'wc_get_product' ) ) {
	global $product;

	$description_product = $product instanceof \WC_Product ? $product : wc_get_product( get_the_ID() );

	if ( $description_product instanceof \WC_Product ) {
		$raw_description = (string) $description_product->get_description();

		if ( '' !== trim( wp_strip_all_tags( $raw_description ) ) ) {
			$description_html = apply_filters( 'the_content', $raw_description );
		}
	}
}

if ( '' !== $description_html ) {
	$items[] = array(
		'label'   => '' !== $description_label ? $description_label : __( 'Description', 'starterkit' ),
		'content' => $description_html,
	);
}

foreach ( $rows as $row ) {
	$row = is_array( $row ) ? $row : array();

	$label   = isset( $row['label'] ) ? trim( (string) $row['label'] ) : '';
	$content = isset( $row['content'] ) ? trim( (string) $row['content'] ) : '';

	if ( '' === $label || '' === $content ) {
		continue;
	}

	$items[] = array(
		'label'   => $label,
		'content' => wpautop( $content ),
	);
}

if ( empty( $items ) ) {
	return;
}
?>
<div class="starterkit-element-card starterkit-element-card--description-accordion">
	<div class="container starterkit-element-card__inner">
		<?php if ( '' !== $title ) : ?>
			<strong class="starterkit-element-card__title"><?php echo esc_html( $title ); ?></strong>
		<?php endif; ?>
		<div class="starterkit-description-accordion">
			<?php foreach ( $items as $index => $item ) : ?>
				<details class="starterkit-description-accordion__item"<?php echo ( $open_first_item && 0 === $index ) ? ' open' : ''; ?>>
					<summary class="starterkit-description-accordion__summary"><?php echo esc_html( $item['label'] ); ?></summary>
					<div class="starterkit-description-accordion__content starterkit-element-card__content">
						<?php echo wp_kses_post( $item['content'] ); ?>
					</div>
				</details>
			<?php endforeach; ?>
		</div>
	</div>
</div>
