<?php
/**
 * Product section render template.
 *
 * @package StarterKit
 */

if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'wc_get_product' ) ) {
	return;
}

$limit    = isset( $attributes['limit'] ) ? absint( $attributes['limit'] ) : 8;
$limit    = $limit > 0 ? $limit : 8;
$columns  = isset( $attributes['columns'] ) ? absint( $attributes['columns'] ) : 4;
$columns  = max( 1, min( 6, $columns > 0 ? $columns : 4 ) );
$source   = isset( $attributes['source'] ) ? sanitize_key( (string) $attributes['source'] ) : 'latest';
$source   = in_array( $source, array( 'latest', 'featured', 'sale', 'ids' ), true ) ? $source : 'latest';
$ids      = isset( $attributes['ids'] ) ? array_map( 'absint', explode( ',', (string) $attributes['ids'] ) ) : array();
$ids      = array_values( array_filter( $ids ) );
$category = isset( $attributes['category'] ) ? sanitize_text_field( (string) $attributes['category'] ) : '';
$tag      = isset( $attributes['tag'] ) ? sanitize_text_field( (string) $attributes['tag'] ) : '';
$featured = ! empty( $attributes['featured'] ) && '0' !== (string) $attributes['featured'];
$on_sale  = ! empty( $attributes['on_sale'] ) && '0' !== (string) $attributes['on_sale'];
$args     = array(
	'post_type'           => 'product',
	'post_status'         => 'publish',
	'posts_per_page'      => $limit,
	'ignore_sticky_posts' => true,
	'orderby'             => 'date',
	'order'               => 'DESC',
);
$tax_query = array();

if ( 'ids' === $source ) {
	if ( empty( $ids ) ) {
		return;
	}

	$args['post__in'] = $ids;
	$args['orderby']  = 'post__in';
} elseif ( 'featured' === $source ) {
	$featured = true;
} elseif ( 'sale' === $source ) {
	$on_sale = true;
}

if ( '' !== $category ) {
	$tax_query[] = array(
		'taxonomy' => 'product_cat',
		'field'    => 'slug',
		'terms'    => array_filter( array_map( 'sanitize_title', explode( ',', $category ) ) ),
	);
}

if ( '' !== $tag ) {
	$tax_query[] = array(
		'taxonomy' => 'product_tag',
		'field'    => 'slug',
		'terms'    => array_filter( array_map( 'sanitize_title', explode( ',', $tag ) ) ),
	);
}

if ( $featured ) {
	$tax_query[] = array(
		'taxonomy' => 'product_visibility',
		'field'    => 'name',
		'terms'    => 'featured',
		'operator' => 'IN',
	);
}

if ( ! empty( $tax_query ) ) {
	$args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
}

if ( $on_sale && function_exists( 'wc_get_product_ids_on_sale' ) ) {
	$sale_ids = array_map( 'absint', wc_get_product_ids_on_sale() );

	if ( empty( $sale_ids ) ) {
		return;
	}

	$args['post__in'] = isset( $args['post__in'] ) ? array_values( array_intersect( (array) $args['post__in'], $sale_ids ) ) : $sale_ids;

	if ( empty( $args['post__in'] ) ) {
		return;
	}
}

$products = new \WP_Query( $args );

if ( ! $products->have_posts() ) {
	wp_reset_postdata();
	return;
}

if ( function_exists( 'wc_set_loop_prop' ) ) {
	wc_set_loop_prop( 'columns', $columns );
}
?>
<section class="starterkit-section starterkit-section--product">
	<div class="container">
		<?php woocommerce_product_loop_start(); ?>
		<?php
		while ( $products->have_posts() ) :
			$products->the_post();
			wc_get_template_part( 'content', 'product' );
		endwhile;
		?>
		<?php woocommerce_product_loop_end(); ?>
	</div>
</section>
<?php
wp_reset_postdata();

if ( function_exists( 'wc_reset_loop' ) ) {
	wc_reset_loop();
}
