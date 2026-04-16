<?php
/**
 * Product grid section template.
 *
 * @var array<string, mixed> $content
 *
 * @package StarterKit
 */

$limit = ! empty( $content['limit'] ) ? max( 1, (int) $content['limit'] ) : 4;
$products = function_exists( 'wc_get_products' ) ? wc_get_products(
	array(
		'limit'   => $limit,
		'status'  => 'publish',
		'orderby' => 'date',
		'order'   => 'DESC',
	)
) : array();
?>
<section class="starterkit-section starterkit-section--product-grid">
	<div class="container">
		<h2><?php echo esc_html( $content['heading'] ?? '' ); ?></h2>
		<div class="starterkit-product-grid">
			<?php foreach ( $products as $product ) : ?>
				<article class="starterkit-product-card">
					<a href="<?php echo esc_url( get_permalink( $product->get_id() ) ); ?>">
						<?php echo wp_kses_post( $product->get_image() ); ?>
						<h3><?php echo esc_html( $product->get_name() ); ?></h3>
						<p><?php echo wp_kses_post( $product->get_price_html() ); ?></p>
					</a>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
