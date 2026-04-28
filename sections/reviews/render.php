<?php
/**
 * Reviews section render template.
 *
 * @package StarterKit
 */

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

$limit = isset( $attributes['limit'] ) ? absint( $attributes['limit'] ) : 3;
$limit = $limit > 0 ? $limit : 3;

$comments = get_comments(
	array(
		'status'     => 'approve',
		'post_type'  => 'product',
		'number'     => $limit * 4,
		'orderby'    => 'comment_date_gmt',
		'order'      => 'DESC',
		'meta_key'   => 'rating', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			array(
				'key'     => 'rating',
				'value'   => 0,
				'compare' => '>',
				'type'    => 'NUMERIC',
			),
		),
	)
);

if ( empty( $comments ) ) {
	return;
}

$reviews = array();

foreach ( $comments as $comment ) {
	$rating  = absint( get_comment_meta( $comment->comment_ID, 'rating', true ) );
	$product = wc_get_product( $comment->comment_post_ID );

	if ( $rating <= 0 || ! $product ) {
		continue;
	}

	$reviews[] = array(
		'author'        => get_comment_author( $comment ),
		'rating'        => min( 5, $rating ),
		'content'       => wp_trim_words( wp_strip_all_tags( $comment->comment_content ), 30 ),
		'date'          => get_comment_date( '', $comment ),
		'product_title' => $product->get_name(),
		'product_url'   => get_permalink( $product->get_id() ),
	);

	if ( count( $reviews ) >= $limit ) {
		break;
	}
}

if ( empty( $reviews ) ) {
	return;
}
?>
<section class="starterkit-section starterkit-section--reviews">
	<div class="container">
		<div class="starterkit-section-reviews">
			<?php foreach ( $reviews as $review ) : ?>
				<article class="starterkit-section-reviews__item">
					<div class="starterkit-section-reviews__rating" aria-label="<?php echo esc_attr( sprintf( __( 'Rated %s out of 5', 'starterkit' ), $review['rating'] ) ); ?>">
						<?php for ( $star = 0; $star < $review['rating']; $star++ ) : ?>
							<span aria-hidden="true">&#9733;</span>
						<?php endfor; ?>
					</div>
					<p class="starterkit-section-reviews__content"><?php echo esc_html( $review['content'] ); ?></p>
					<div class="starterkit-section-reviews__meta">
						<strong><?php echo esc_html( $review['author'] ); ?></strong>
						<span><?php echo esc_html( $review['date'] ); ?></span>
					</div>
					<a class="starterkit-section-reviews__product" href="<?php echo esc_url( $review['product_url'] ); ?>"><?php echo esc_html( $review['product_title'] ); ?></a>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
