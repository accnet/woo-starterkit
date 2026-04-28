<?php
/**
 * Review summary element render template.
 *
 * @package StarterKit
 */

$title      = isset( $settings['title'] ) ? (string) $settings['title'] : '';
$source     = isset( $settings['source'] ) ? sanitize_key( (string) $settings['source'] ) : 'woocommerce';
$content    = isset( $settings['content'] ) ? (string) $settings['content'] : '';
$show_stars = ! empty( $settings['show_stars'] ) && '0' !== (string) $settings['show_stars'];
$style      = isset( $settings['style'] ) ? sanitize_html_class( (string) $settings['style'] ) : 'default';
$rating     = 0.0;
$count      = 0;

if ( 'woocommerce' === $source && function_exists( 'wc_get_product' ) ) {
	global $product;

	$review_product = $product instanceof \WC_Product ? $product : wc_get_product( get_the_ID() );

	if ( $review_product instanceof \WC_Product ) {
		$rating = (float) $review_product->get_average_rating();
		$count  = (int) $review_product->get_review_count();

		if ( $count > 0 && $rating > 0 ) {
			$content = sprintf(
				/* translators: 1: rating, 2: review count. */
				_n( 'Rated %1$s/5 by %2$s customer', 'Rated %1$s/5 by %2$s customers', $count, 'starterkit' ),
				number_format_i18n( $rating, 1 ),
				number_format_i18n( $count )
			);
		}
	}
}
?>
<div class="starterkit-element-card starterkit-element-card--<?php echo esc_attr( $style ); ?>">
	<div class="container starterkit-element-card__inner">
		<?php if ( '' !== $title ) : ?>
			<h3 class="starterkit-element-card__title"><?php echo esc_html( $title ); ?></h3>
		<?php endif; ?>
		<?php if ( $show_stars && $rating > 0 ) : ?>
			<div class="starterkit-element-card__rating" aria-label="<?php echo esc_attr( sprintf( __( 'Rated %s out of 5', 'starterkit' ), number_format_i18n( $rating, 1 ) ) ); ?>">
				<?php for ( $star_index = 0; $star_index < max( 1, min( 5, (int) round( $rating ) ) ); $star_index++ ) : ?>
					<span aria-hidden="true">&#9733;</span>
				<?php endfor; ?>
			</div>
		<?php endif; ?>
		<?php if ( '' !== $content ) : ?>
			<div class="starterkit-element-card__content"><?php echo wp_kses_post( wpautop( $content ) ); ?></div>
		<?php endif; ?>
	</div>
</div>
