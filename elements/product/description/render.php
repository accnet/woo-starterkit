<?php
/**
 * Product description element render template.
 *
 * @package StarterKit
 */

$title            = isset( $settings['title'] ) ? (string) $settings['title'] : '';
$enable_collapse  = ! empty( $settings['enable_collapse'] ) && '0' !== (string) $settings['enable_collapse'];
$collapsed_lines  = isset( $settings['collapsed_lines'] ) ? max( 2, min( 20, absint( $settings['collapsed_lines'] ) ) ) : 6;
$expand_label     = isset( $settings['expand_label'] ) ? trim( (string) $settings['expand_label'] ) : __( 'Read more', 'starterkit' );
$collapse_label   = isset( $settings['collapse_label'] ) ? trim( (string) $settings['collapse_label'] ) : __( 'Show less', 'starterkit' );
$description_html = '';

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

if ( '' === $description_html ) {
	return;
}

$plain_description = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $description_html ) ) );
$word_count        = count( preg_split( '/\s+/', $plain_description ) ?: array() );
$has_toggle        = $enable_collapse && $word_count > 40;
$element_id        = function_exists( 'wp_unique_id' ) ? wp_unique_id( 'starterkit-product-description-' ) : 'starterkit-product-description-' . wp_rand();
?>
<div class="starterkit-element-card starterkit-element-card--description">
	<div class="container starterkit-element-card__inner">
		<?php if ( '' !== $title ) : ?>
			<strong class="starterkit-element-card__title"><?php echo esc_html( $title ); ?></strong>
		<?php endif; ?>
		<div class="starterkit-element-description<?php echo $has_toggle ? ' is-collapsible' : ''; ?>" data-starterkit-description>
			<div
				id="<?php echo esc_attr( $element_id ); ?>"
				class="starterkit-element-description__body starterkit-element-card__content<?php echo $has_toggle ? ' is-collapsed' : ''; ?>"
				style="--starterkit-description-lines: <?php echo esc_attr( (string) $collapsed_lines ); ?>;"
			>
				<?php echo wp_kses_post( $description_html ); ?>
			</div>
			<?php if ( $has_toggle ) : ?>
				<button
					type="button"
					class="starterkit-element-description__toggle"
					data-starterkit-description-toggle
					data-collapsed-label="<?php echo esc_attr( $expand_label ); ?>"
					data-expanded-label="<?php echo esc_attr( $collapse_label ); ?>"
					aria-expanded="false"
					aria-controls="<?php echo esc_attr( $element_id ); ?>"
				>
					<span class="starterkit-element-description__toggle-label"><?php echo esc_html( $expand_label ); ?></span>
				</button>
			<?php endif; ?>
		</div>
	</div>
</div>