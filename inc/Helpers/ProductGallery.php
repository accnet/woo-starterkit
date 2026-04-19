<?php
/**
 * WooCommerce-native product gallery helper.
 *
 * @package StarterKit
 */

namespace StarterKit\Helpers;

use WC_Product;
use WC_Product_Variable;

class ProductGallery {
	/**
	 * Build normalized gallery items for the product layout template.
	 *
	 * @param WC_Product $product Product instance.
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_items( WC_Product $product ) {
		$image_ids = self::get_base_image_ids( $product );

		if ( empty( $image_ids ) ) {
			return array();
		}

		$variation_image_map = self::get_variation_image_map( $product );
		$items               = array();

		foreach ( $image_ids as $image_id ) {
			$src       = wp_get_attachment_image_url( $image_id, 'full' );
			$thumb_src = wp_get_attachment_image_url( $image_id, 'woocommerce_thumbnail' );

			if ( ! $src ) {
				continue;
			}

			$variant_ids = isset( $variation_image_map[ $image_id ] ) ? $variation_image_map[ $image_id ] : array();

			$items[] = array(
				'id'                   => $image_id,
				'src'                  => $src,
				'thumb_src'            => $thumb_src ? $thumb_src : $src,
				'alt'                  => get_post_meta( $image_id, '_wp_attachment_image_alt', true ),
				'variant_ids'          => $variant_ids,
				'featured_variant_ids' => $variant_ids,
			);
		}

		return $items;
	}

	/**
	 * Get the ordered base image ids for the product.
	 *
	 * @param WC_Product $product Product instance.
	 * @return array<int, int>
	 */
	protected static function get_base_image_ids( WC_Product $product ) {
		$image_ids = array();

		if ( $product->get_image_id() ) {
			$image_ids[] = (int) $product->get_image_id();
		}

		foreach ( $product->get_gallery_image_ids() as $image_id ) {
			$image_ids[] = (int) $image_id;
		}

		$image_ids = array_values( array_unique( array_filter( $image_ids ) ) );

		if ( ! empty( $image_ids ) ) {
			return $image_ids;
		}

		$placeholder_id = (int) get_post_thumbnail_id( $product->get_id() );

		return $placeholder_id ? array( $placeholder_id ) : array();
	}

	/**
	 * Map variation image ids to their variation ids.
	 *
	 * @param WC_Product $product Product instance.
	 * @return array<int, array<int, int>>
	 */
	protected static function get_variation_image_map( WC_Product $product ) {
		if ( ! $product instanceof WC_Product_Variable ) {
			return array();
		}

		$map = array();

		foreach ( $product->get_children() as $variation_id ) {
			$variation = wc_get_product( $variation_id );

			if ( ! $variation instanceof WC_Product ) {
				continue;
			}

			$image_id = (int) $variation->get_image_id();

			if ( ! $image_id ) {
				continue;
			}

			if ( ! isset( $map[ $image_id ] ) ) {
				$map[ $image_id ] = array();
			}

			$map[ $image_id ][] = (int) $variation_id;
		}

		foreach ( $map as $image_id => $variation_ids ) {
			$map[ $image_id ] = array_values( array_unique( array_filter( array_map( 'intval', $variation_ids ) ) ) );
		}

		return $map;
	}
}