<?php
/**
 * Wootify Core integration for the AJAX cart drawer.
 *
 * @package StarterKit
 */

namespace StarterKit\Compatibility\WootifyCore;

use StarterKit\WooCommerce\CartDrawerManager;

class CartDrawerIntegration {
	/**
	 * Cached Wootify product data by Woo product id.
	 *
	 * @var array<int, array<string, mixed>|null>
	 */
	protected $product_data_cache = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'starterkit_cart_drawer_product_requires_options', array( $this, 'product_requires_options' ), 10, 2 );
		add_filter( 'starterkit_cart_drawer_product_can_open_selector', array( $this, 'product_can_open_selector' ), 10, 2 );
		add_filter( 'starterkit_cart_drawer_selector_config', array( $this, 'filter_selector_config' ), 10, 3 );
		add_filter( 'starterkit_cart_drawer_upsell_price_html', array( $this, 'filter_upsell_price_html' ), 10, 2 );
		add_filter( 'starterkit_cart_drawer_upsell_selector_button_text', array( $this, 'filter_selector_button_text' ), 10, 3 );
	}

	/**
	 * Mark Wootify matrix products as requiring the drawer selector.
	 *
	 * @param bool        $requires_selection Existing decision.
	 * @param \WC_Product $product Product object.
	 * @return bool
	 */
	public function product_requires_options( $requires_selection, $product ) {
		if ( $requires_selection || ! $product instanceof \WC_Product ) {
			return (bool) $requires_selection;
		}

		$data = $this->get_product_data( $product );

		return is_array( $data ) && count( (array) ( $data['variants'] ?? array() ) ) > 1;
	}

	/**
	 * Allow selector opening when the Woo shell is stockless but Wootify variants are available.
	 *
	 * @param bool        $can_open_selector Existing decision.
	 * @param \WC_Product $product Product object.
	 * @return bool
	 */
	public function product_can_open_selector( $can_open_selector, $product ) {
		if ( $can_open_selector || ! $product instanceof \WC_Product || ! $product->is_purchasable() ) {
			return (bool) $can_open_selector;
		}

		$data = $this->get_product_data( $product );

		return is_array( $data ) && $this->has_available_variant( $data );
	}

	/**
	 * Build the drawer selector config for Wootify matrix products.
	 *
	 * @param array<string, mixed> $config Existing selector config.
	 * @param \WC_Product         $product Product object.
	 * @param CartDrawerManager   $drawer  Cart drawer manager.
	 * @return array<string, mixed>
	 */
	public function filter_selector_config( $config, $product, $drawer ) {
		unset( $drawer );

		if ( ! empty( $config ) || ! $product instanceof \WC_Product ) {
			return (array) $config;
		}

		$data = $this->get_product_data( $product );

		if ( ! is_array( $data ) || empty( $data['variants'] ) ) {
			return (array) $config;
		}

		$attributes = $this->build_attributes( $data );
		$variations = $this->build_variations( $data, $attributes, $product );

		if ( empty( $attributes ) || empty( $variations ) ) {
			return (array) $config;
		}

		$selector_config = array(
			'productId'         => (int) $product->get_id(),
			'name'              => (string) $product->get_name(),
			'permalink'         => (string) $product->get_permalink(),
			'priceHtml'         => (string) $this->get_price_html( $data, $product ),
			'buttonText'        => (string) $product->add_to_cart_text(),
			'isWootify'         => true,
			'defaultAttributes' => array(),
			'image'             => $this->get_product_image( $data, $product ),
			'attributes'        => $attributes,
			'variations'        => $variations,
		);

		return (array) apply_filters( 'starterkit_cart_drawer_wootify_selector_config', $selector_config, $product, $data );
	}

	/**
	 * Display a Wootify variant price/range in the recommendation card.
	 *
	 * @param string      $price_html Existing price HTML.
	 * @param \WC_Product $product Product object.
	 * @return string
	 */
	public function filter_upsell_price_html( $price_html, $product ) {
		if ( ! $product instanceof \WC_Product ) {
			return (string) $price_html;
		}

		$data = $this->get_product_data( $product );

		if ( ! is_array( $data ) || empty( $data['variants'] ) ) {
			return (string) $price_html;
		}

		return (string) $this->get_price_html( $data, $product );
	}

	/**
	 * Keep Wootify upsell CTA copy aligned with regular add-to-cart actions.
	 *
	 * @param string      $button_text Existing button text.
	 * @param \WC_Product $product Product object.
	 * @param array       $config Selector config.
	 * @return string
	 */
	public function filter_selector_button_text( $button_text, $product, $config ) {
		if ( ! $product instanceof \WC_Product || empty( $config['isWootify'] ) ) {
			return (string) $button_text;
		}

		return __( 'Add to cart', 'starterkit' );
	}

	/**
	 * Fetch Wootify product data through the plugin service.
	 *
	 * @param \WC_Product $product Product object.
	 * @return array<string, mixed>|null
	 */
	protected function get_product_data( $product ) {
		$product_id = (int) $product->get_id();

		if ( $product_id <= 0 ) {
			return null;
		}

		if ( array_key_exists( $product_id, $this->product_data_cache ) ) {
			return $this->product_data_cache[ $product_id ];
		}

		if ( ! class_exists( 'WootifyCore\\Services\\ProductService' ) ) {
			$this->product_data_cache[ $product_id ] = null;
			return null;
		}

		try {
			$service = new \WootifyCore\Services\ProductService();
			$data    = $service->get_product_data( $product_id );
		} catch ( \Throwable $throwable ) {
			$data = null;
		}

		$this->product_data_cache[ $product_id ] = is_array( $data ) ? $data : null;

		return $this->product_data_cache[ $product_id ];
	}

	/**
	 * Build selector attributes from Wootify option metadata.
	 *
	 * @param array<string, mixed> $data Wootify product data.
	 * @return array<int, array<string, mixed>>
	 */
	protected function build_attributes( array $data ) {
		$attributes           = array();
		$variation_attributes = ! empty( $data['variation_attributes'] ) && is_array( $data['variation_attributes'] )
			? array_values( $data['variation_attributes'] )
			: array();

		if ( empty( $variation_attributes ) && ! empty( $data['options'] ) && is_array( $data['options'] ) ) {
			foreach ( array_values( $data['options'] ) as $index => $option ) {
				$name = trim( (string) ( $option['name'] ?? '' ) );

				if ( '' === $name ) {
					continue;
				}

				$variation_attributes[] = array(
					'name'     => $name,
					'key'      => 'attribute_' . sanitize_title( $name ),
					'position' => $index,
				);
			}
		}

		foreach ( $variation_attributes as $index => $attribute ) {
			$label = trim( (string) ( $attribute['name'] ?? '' ) );
			$key   = trim( (string) ( $attribute['key'] ?? '' ) );

			if ( '' === $label ) {
				continue;
			}

			if ( '' === $key ) {
				$key = 'attribute_' . sanitize_title( $label );
			}

			$position = isset( $attribute['position'] ) ? (int) $attribute['position'] : (int) $index;
			$options  = array();
			$seen     = array();

			foreach ( (array) ( $data['variants'] ?? array() ) as $variant ) {
				if ( ! is_array( $variant ) ) {
					continue;
				}

				$value = $this->get_variant_attribute_value( $variant, $key, $position );

				if ( '' === $value || isset( $seen[ $value ] ) ) {
					continue;
				}

				$seen[ $value ] = true;
				$options[]      = array(
					'value' => $value,
					'label' => $value,
				);
			}

			if ( empty( $options ) ) {
				continue;
			}

			$attributes[] = array(
				'name'     => $key,
				'label'    => $label,
				'position' => $position,
				'options'  => $options,
			);
		}

		return $attributes;
	}

	/**
	 * Build normalized variation payloads consumed by cart-drawer.js.
	 *
	 * @param array<string, mixed> $data       Wootify product data.
	 * @param array<int, array>    $attributes Selector attributes.
	 * @param \WC_Product          $product    Product object.
	 * @return array<int, array<string, mixed>>
	 */
	protected function build_variations( array $data, array $attributes, $product ) {
		$variations = array();

		foreach ( (array) ( $data['variants'] ?? array() ) as $variant ) {
			if ( ! is_array( $variant ) ) {
				continue;
			}

			$variant_id = isset( $variant['id'] ) ? (int) $variant['id'] : (int) ( $variant['variation_id'] ?? 0 );

			if ( $variant_id <= 0 ) {
				continue;
			}

			$variant_attributes = array();

			foreach ( $attributes as $attribute ) {
				$key      = (string) ( $attribute['name'] ?? '' );
				$position = isset( $attribute['position'] ) ? (int) $attribute['position'] : 0;

				if ( '' === $key ) {
					continue;
				}

				$variant_attributes[ $key ] = $this->get_variant_attribute_value( $variant, $key, $position );
			}

			$stock       = $variant['stock'] ?? null;
			$is_in_stock = null === $stock || '' === (string) $stock || (int) $stock > 0;
			$image_src   = $this->get_variant_image_src( $variant );

			$variations[] = array(
				'variationId'       => $variant_id,
				'attributes'        => $variant_attributes,
				'priceHtml'         => $this->get_variant_price_html( $variant ),
				'availabilityHtml'  => $this->get_availability_html( $is_in_stock ),
				'isInStock'         => $is_in_stock,
				'isPurchasable'     => $is_in_stock && $product->is_purchasable(),
				'variationIsActive' => true,
				'minQty'            => 1,
				'maxQty'            => $is_in_stock && is_numeric( $stock ) && (int) $stock > 0 ? (int) $stock : 0,
				'image'             => array(
					'src' => $image_src,
					'alt' => (string) $product->get_name(),
				),
			);
		}

		return $variations;
	}

	/**
	 * Determine if at least one Wootify variant can be sold.
	 *
	 * @param array<string, mixed> $data Wootify product data.
	 * @return bool
	 */
	protected function has_available_variant( array $data ) {
		foreach ( (array) ( $data['variants'] ?? array() ) as $variant ) {
			if ( ! is_array( $variant ) ) {
				continue;
			}

			$stock = $variant['stock'] ?? null;

			if ( null === $stock || '' === (string) $stock || (int) $stock > 0 ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get a variant attribute value by facade key or combination index.
	 *
	 * @param array<string, mixed> $variant  Variant payload.
	 * @param string               $key      Attribute key.
	 * @param int                  $position Combination position.
	 * @return string
	 */
	protected function get_variant_attribute_value( array $variant, $key, $position ) {
		if ( ! empty( $variant['attributes'] ) && is_array( $variant['attributes'] ) && isset( $variant['attributes'][ $key ] ) ) {
			return trim( (string) $variant['attributes'][ $key ] );
		}

		if ( ! empty( $variant['facade']['attributes'] ) && is_array( $variant['facade']['attributes'] ) && isset( $variant['facade']['attributes'][ $key ] ) ) {
			return trim( (string) $variant['facade']['attributes'][ $key ] );
		}

		if ( isset( $variant['combination'] ) && is_array( $variant['combination'] ) && isset( $variant['combination'][ $position ] ) ) {
			return trim( (string) $variant['combination'][ $position ] );
		}

		return '';
	}

	/**
	 * Build product price HTML from Wootify variants.
	 *
	 * @param array<string, mixed> $data    Wootify product data.
	 * @param \WC_Product         $product Product object.
	 * @return string
	 */
	protected function get_price_html( array $data, $product ) {
		$prices = array();

		foreach ( (array) ( $data['variants'] ?? array() ) as $variant ) {
			if ( ! is_array( $variant ) || ! isset( $variant['price'] ) || ! is_numeric( $variant['price'] ) ) {
				continue;
			}

			$prices[] = (float) $variant['price'];
		}

		if ( empty( $prices ) ) {
			return (string) $product->get_price_html();
		}

		$min = min( $prices );
		$max = max( $prices );

		if ( $min === $max ) {
			return function_exists( 'wc_price' ) ? wc_price( $min ) : (string) $min;
		}

		if ( function_exists( 'wc_format_price_range' ) ) {
			return wc_format_price_range( $min, $max );
		}

		return ( function_exists( 'wc_price' ) ? wc_price( $min ) : (string) $min ) . ' - ' . ( function_exists( 'wc_price' ) ? wc_price( $max ) : (string) $max );
	}

	/**
	 * Build variant price HTML.
	 *
	 * @param array<string, mixed> $variant Variant payload.
	 * @return string
	 */
	protected function get_variant_price_html( array $variant ) {
		if ( ! empty( $variant['price_html'] ) ) {
			return (string) $variant['price_html'];
		}

		if ( isset( $variant['facade']['price_html'] ) && '' !== (string) $variant['facade']['price_html'] ) {
			return (string) $variant['facade']['price_html'];
		}

		if ( isset( $variant['price'] ) && is_numeric( $variant['price'] ) ) {
			return function_exists( 'wc_price' ) ? wc_price( (float) $variant['price'] ) : (string) $variant['price'];
		}

		return '';
	}

	/**
	 * Get the product image used in the selector sheet.
	 *
	 * @param array<string, mixed> $data    Wootify product data.
	 * @param \WC_Product         $product Product object.
	 * @return array<string, string>
	 */
	protected function get_product_image( array $data, $product ) {
		$src = '';

		if ( ! empty( $data['gallery_items'] ) && is_array( $data['gallery_items'] ) ) {
			$first = reset( $data['gallery_items'] );
			$src   = is_array( $first ) ? trim( (string) ( $first['src'] ?? '' ) ) : '';
		}

		if ( '' === $src && ! empty( $data['gallery'] ) && is_array( $data['gallery'] ) ) {
			$src = trim( (string) reset( $data['gallery'] ) );
		}

		if ( '' === $src && $product->get_image_id() ) {
			$src = (string) wp_get_attachment_image_url( $product->get_image_id(), 'woocommerce_thumbnail' );
		}

		if ( '' === $src && function_exists( 'wc_placeholder_img_src' ) ) {
			$src = (string) wc_placeholder_img_src( 'woocommerce_thumbnail' );
		}

		return array(
			'src' => $src,
			'alt' => (string) $product->get_name(),
		);
	}

	/**
	 * Get the image URL for a Wootify variant.
	 *
	 * @param array<string, mixed> $variant Variant payload.
	 * @return string
	 */
	protected function get_variant_image_src( array $variant ) {
		if ( ! empty( $variant['image_url'] ) ) {
			return trim( (string) $variant['image_url'] );
		}

		if ( ! empty( $variant['featured_image']['src'] ) ) {
			return trim( (string) $variant['featured_image']['src'] );
		}

		if ( ! empty( $variant['facade']['image']['src'] ) ) {
			return trim( (string) $variant['facade']['image']['src'] );
		}

		if ( ! empty( $variant['gallery_selection'] ) && is_array( $variant['gallery_selection'] ) ) {
			return trim( (string) reset( $variant['gallery_selection'] ) );
		}

		return '';
	}

	/**
	 * Build small stock status HTML for the selector sheet.
	 *
	 * @param bool $is_in_stock Whether variant is in stock.
	 * @return string
	 */
	protected function get_availability_html( $is_in_stock ) {
		if ( $is_in_stock ) {
			return '<p class="stock in-stock">' . esc_html__( 'In stock', 'starterkit' ) . '</p>';
		}

		return '<p class="stock out-of-stock">' . esc_html__( 'Out of stock', 'starterkit' ) . '</p>';
	}
}
