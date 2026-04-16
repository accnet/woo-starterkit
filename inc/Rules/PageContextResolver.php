<?php
/**
 * Resolve request context for display rules.
 *
 * @package StarterKit
 */

namespace StarterKit\Rules;

class PageContextResolver {
	/**
	 * Build a normalized context array.
	 *
	 * @return array<string, mixed>
	 */
	public function resolve() {
		$product_id = function_exists( 'is_product' ) && is_product() ? get_the_ID() : 0;
		$page_id    = is_singular() ? get_queried_object_id() : 0;
		$term_id    = is_tax() || is_category() ? get_queried_object_id() : 0;
		$template   = $page_id ? get_page_template_slug( $page_id ) : '';
		$post_type  = $page_id ? get_post_type( $page_id ) : get_post_type();
		$queried    = get_queried_object();

		return array(
			'is_homepage'        => is_front_page(),
			'is_shop'            => function_exists( 'is_shop' ) ? is_shop() : false,
			'is_product'         => function_exists( 'is_product' ) ? is_product() : false,
			'is_logged_in'       => is_user_logged_in(),
			'device'             => wp_is_mobile() ? 'mobile' : 'desktop',
			'current_product_id' => $product_id,
			'current_page_id'    => $page_id,
			'current_post_type'  => $post_type ? $post_type : '',
			'page_template'      => $template ? $template : '',
			'is_product_archive' => function_exists( 'is_product_taxonomy' ) ? ( is_post_type_archive( 'product' ) || is_product_taxonomy() ) : is_post_type_archive( 'product' ),
			'is_product_category'=> function_exists( 'is_product_category' ) ? is_product_category() : false,
			'current_taxonomy'   => isset( $queried->taxonomy ) ? (string) $queried->taxonomy : '',
			'current_term_id'    => $term_id,
			'product_cat_ids'    => $product_id && function_exists( 'wc_get_product_term_ids' ) ? wc_get_product_term_ids( $product_id, 'product_cat' ) : array(),
			'product_tag_ids'    => $product_id && function_exists( 'wc_get_product_term_ids' ) ? wc_get_product_term_ids( $product_id, 'product_tag' ) : array(),
		);
	}
}
