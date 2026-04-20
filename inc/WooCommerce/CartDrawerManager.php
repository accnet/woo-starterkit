<?php
/**
 * AJAX-powered cart drawer for WooCommerce.
 *
 * @package StarterKit
 */

namespace StarterKit\WooCommerce;

use StarterKit\Settings\GlobalSettingsManager;

class CartDrawerManager {
	/**
	 * Settings manager.
	 *
	 * @var GlobalSettingsManager
	 */
	protected $settings;

	/**
	 * Constructor.
	 *
	 * @param GlobalSettingsManager $settings Settings manager.
	 */
	public function __construct( GlobalSettingsManager $settings ) {
		$this->settings = $settings;

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_footer', array( $this, 'render_drawer' ), 30 );
		add_filter( 'woocommerce_add_to_cart_fragments', array( $this, 'register_fragments' ) );
		add_action( 'wp_ajax_starterkit_update_cart_item', array( $this, 'ajax_update_cart_item' ) );
		add_action( 'wp_ajax_nopriv_starterkit_update_cart_item', array( $this, 'ajax_update_cart_item' ) );
		add_action( 'wp_ajax_starterkit_remove_cart_item', array( $this, 'ajax_remove_cart_item' ) );
		add_action( 'wp_ajax_nopriv_starterkit_remove_cart_item', array( $this, 'ajax_remove_cart_item' ) );
		add_filter( 'woocommerce_add_cart_item_data', array( $this, 'fix_wootify_unique_key' ), 99, 3 );
	}

	/**
	 * Fix Wootify's non-deterministic unique_key so same variant merges quantity.
	 *
	 * @param array $cart_item_data Cart item data.
	 * @param int   $product_id    Product ID.
	 * @param int   $variation_id  Variation ID.
	 * @return array
	 */
	public function fix_wootify_unique_key( $cart_item_data, $product_id, $variation_id ) {
		if ( isset( $cart_item_data['wootify_variant_id'] ) && isset( $cart_item_data['unique_key'] ) ) {
			$cart_item_data['unique_key'] = md5(
				'wootify_' . (int) $cart_item_data['wootify_variant_id'] . '_' .
				serialize( $cart_item_data['wootify_customizer_values'] ?? array() )
			);
		}

		return $cart_item_data;
	}

	/**
	 * Enqueue cart drawer assets.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		if ( ! $this->is_available() ) {
			return;
		}

		$css_path = get_template_directory() . '/assets/css/cart-drawer.css';
		$js_path  = get_template_directory() . '/assets/js/cart-drawer.js';

		wp_enqueue_style(
			'starterkit-cart-drawer',
			get_template_directory_uri() . '/assets/css/cart-drawer.css',
			array( 'starterkit-theme' ),
			file_exists( $css_path ) ? (string) filemtime( $css_path ) : wp_get_theme()->get( 'Version' )
		);

		wp_enqueue_script(
			'starterkit-cart-drawer',
			get_template_directory_uri() . '/assets/js/cart-drawer.js',
			array(),
			file_exists( $js_path ) ? (string) filemtime( $js_path ) : wp_get_theme()->get( 'Version' ),
			true
		);

		wp_localize_script(
			'starterkit-cart-drawer',
			'starterkitCartDrawer',
			array(
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'starterkit_cart_drawer' ),
				'cartUrl'      => function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' ),
				'checkoutUrl'  => function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/checkout/' ),
				'wcAjaxUrl'    => class_exists( 'WC_AJAX' ) ? \WC_AJAX::get_endpoint( '%%endpoint%%' ) : '',
				'i18n'         => array(
					'updating'             => __( 'Updating your cart...', 'starterkit' ),
					'error'                => __( 'We could not update your cart. Please try again.', 'starterkit' ),
					'chooseOptions'        => __( 'Choose options', 'starterkit' ),
					'chooseAllOptions'     => __( 'Please choose product options before adding to cart.', 'starterkit' ),
					'unavailableVariation' => __( 'This combination is currently unavailable.', 'starterkit' ),
					'back'                 => __( 'Back', 'starterkit' ),
					'confirmAdd'           => __( 'Add to cart', 'starterkit' ),
				),
			)
		);
	}

	/**
	 * Render drawer markup.
	 *
	 * @return void
	 */
	public function render_drawer() {
		if ( ! $this->is_available() ) {
			return;
		}

		echo '<div id="starterkit-cart-drawer" class="starterkit-cart-drawer" aria-hidden="true">';
		echo '<button type="button" class="starterkit-cart-drawer__overlay" data-cart-drawer-close aria-label="' . esc_attr__( 'Close cart drawer', 'starterkit' ) . '"></button>';
		echo '<div class="starterkit-cart-drawer__toast" aria-live="polite" aria-atomic="true"></div>';
		echo '<aside class="starterkit-cart-drawer__panel" aria-label="' . esc_attr__( 'Shopping cart', 'starterkit' ) . '">';
		echo '<div class="starterkit-cart-drawer__inner">';
		echo $this->get_drawer_inner_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div>';
		echo '<div class="starterkit-cart-drawer__sheet" aria-hidden="true">';
		echo '<button type="button" class="starterkit-cart-drawer__sheet-overlay" data-cart-drawer-sheet-close aria-label="' . esc_attr__( 'Close product options', 'starterkit' ) . '"></button>';
		echo '<div class="starterkit-cart-drawer__sheet-panel">';
		echo '<div class="starterkit-cart-drawer__sheet-content"></div>';
		echo '</div>';
		echo '</div>';
		echo '</aside>';
		echo '</div>';
	}

	/**
	 * Register drawer fragments for WooCommerce AJAX add-to-cart.
	 *
	 * @param array<string, string> $fragments Existing fragments.
	 * @return array<string, string>
	 */
	public function register_fragments( $fragments ) {
		if ( ! is_array( $fragments ) ) {
			$fragments = array();
		}

		return array_merge( $fragments, $this->get_fragments() );
	}

	/**
	 * AJAX update quantity handler.
	 *
	 * @return void
	 */
	public function ajax_update_cart_item() {
		check_ajax_referer( 'starterkit_cart_drawer', 'nonce' );

		if ( ! $this->is_available() ) {
			wp_send_json_error();
		}

		$cart_item_key = isset( $_POST['cart_item_key'] ) ? wc_clean( wp_unslash( $_POST['cart_item_key'] ) ) : '';
		$quantity      = isset( $_POST['quantity'] ) ? max( 0, absint( wp_unslash( $_POST['quantity'] ) ) ) : 0;

		if ( '' === $cart_item_key ) {
			wp_send_json_error();
		}

		$cart = WC()->cart->get_cart();

		if ( ! isset( $cart[ $cart_item_key ] ) ) {
			wp_send_json_success( $this->get_cart_response() );
		}

		if ( 0 === $quantity ) {
			WC()->cart->remove_cart_item( $cart_item_key );
		} else {
			WC()->cart->set_quantity( $cart_item_key, $quantity, true );
		}

		WC()->cart->calculate_totals();
		WC()->cart->maybe_set_cart_cookies();

		wp_send_json_success( $this->get_cart_response() );
	}

	/**
	 * AJAX remove item handler.
	 *
	 * @return void
	 */
	public function ajax_remove_cart_item() {
		check_ajax_referer( 'starterkit_cart_drawer', 'nonce' );

		if ( ! $this->is_available() ) {
			wp_send_json_error();
		}

		$cart_item_key = isset( $_POST['cart_item_key'] ) ? wc_clean( wp_unslash( $_POST['cart_item_key'] ) ) : '';

		if ( '' === $cart_item_key ) {
			wp_send_json_error();
		}

		$cart = WC()->cart->get_cart();

		if ( ! isset( $cart[ $cart_item_key ] ) ) {
			wp_send_json_success( $this->get_cart_response() );
		}

		WC()->cart->remove_cart_item( $cart_item_key );
		WC()->cart->calculate_totals();
		WC()->cart->maybe_set_cart_cookies();

		wp_send_json_success( $this->get_cart_response() );
	}

	/**
	 * Return a normalized AJAX cart payload.
	 *
	 * @return array<string, mixed>
	 */
	protected function get_cart_response() {
		return array(
			'fragments'  => $this->get_fragments(),
			'cart_count' => (int) WC()->cart->get_cart_contents_count(),
			'cart_hash'  => method_exists( WC()->cart, 'get_cart_hash' ) ? WC()->cart->get_cart_hash() : '',
			'is_empty'   => WC()->cart->is_empty(),
		);
	}

	/**
	 * Return fragment map used by drawer sync.
	 *
	 * @return array<string, string>
	 */
	protected function get_fragments() {
		return array(
			'#starterkit-cart-drawer .starterkit-cart-drawer__inner' => '<div class="starterkit-cart-drawer__inner">' . $this->get_drawer_inner_html() . '</div>',
			'.header-cart-count' => $this->get_cart_count_html(),
		);
	}

	/**
	 * Build inner drawer markup.
	 *
	 * @return string
	 */
	protected function get_drawer_inner_html() {
		ob_start();
		?>
		<div class="starterkit-cart-drawer__header">
			<div>
				<h2><?php esc_html_e( 'Your Cart', 'starterkit' ); ?></h2>
				<p class="starterkit-cart-drawer__meta">
					<?php
					printf(
						/* translators: %d: item count */
						esc_html__( '%d item(s)', 'starterkit' ),
						(int) WC()->cart->get_cart_contents_count()
					);
					?>
				</p>
			</div>
			<button type="button" class="starterkit-cart-drawer__close" data-cart-drawer-close aria-label="<?php esc_attr_e( 'Close cart drawer', 'starterkit' ); ?>">
				<span aria-hidden="true">&times;</span>
			</button>
		</div>

		<div class="starterkit-cart-drawer__body">
			<div class="starterkit-cart-drawer__status" aria-live="polite">
				<?php esc_html_e( 'Updating your cart...', 'starterkit' ); ?>
			</div>

			<?php if ( WC()->cart->is_empty() ) : ?>
				<div class="starterkit-cart-drawer__empty">
					<p><?php esc_html_e( 'Your cart is empty.', 'starterkit' ); ?></p>
					<a class="button button-primary" href="<?php echo esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' ) ); ?>">
						<?php esc_html_e( 'Continue Shopping', 'starterkit' ); ?>
					</a>
				</div>
			<?php else : ?>
				<ul class="starterkit-cart-drawer__items">
					<?php foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) : ?>
						<?php
						$product = isset( $cart_item['data'] ) ? $cart_item['data'] : false;

						if ( ! $product || ! $product->exists() ) {
							continue;
						}

						$product_permalink = $product->is_visible() ? $product->get_permalink( $cart_item ) : '';
						$image_html        = $product->get_image( 'woocommerce_thumbnail' );
						$quantity          = isset( $cart_item['quantity'] ) ? (int) $cart_item['quantity'] : 1;
						?>
						<li class="starterkit-cart-drawer__item" data-cart-item-key="<?php echo esc_attr( $cart_item_key ); ?>" data-quantity="<?php echo esc_attr( (string) $quantity ); ?>">
							<div class="starterkit-cart-drawer__item-image">
								<?php if ( $product_permalink ) : ?>
									<a href="<?php echo esc_url( $product_permalink ); ?>"><?php echo wp_kses_post( $image_html ); ?></a>
								<?php else : ?>
									<?php echo wp_kses_post( $image_html ); ?>
								<?php endif; ?>
							</div>

							<div class="starterkit-cart-drawer__item-content">
								<h3 class="starterkit-cart-drawer__item-title">
									<?php if ( $product_permalink ) : ?>
										<a href="<?php echo esc_url( $product_permalink ); ?>"><?php echo esc_html( $product->get_name() ); ?></a>
									<?php else : ?>
										<?php echo esc_html( $product->get_name() ); ?>
									<?php endif; ?>
								</h3>

								<?php if ( function_exists( 'wc_get_formatted_cart_item_data' ) ) : ?>
									<div class="starterkit-cart-drawer__item-variation">
										<?php echo wp_kses_post( wc_get_formatted_cart_item_data( $cart_item ) ); ?>
									</div>
								<?php endif; ?>

								<div class="starterkit-cart-drawer__item-bottom">
									<div class="starterkit-cart-drawer__quantity" aria-label="<?php esc_attr_e( 'Quantity controls', 'starterkit' ); ?>">
										<button type="button" class="starterkit-cart-drawer__qty-button" data-quantity-delta="-1" data-cart-item-key="<?php echo esc_attr( $cart_item_key ); ?>">-</button>
										<span class="starterkit-cart-drawer__qty-value"><?php echo esc_html( (string) $quantity ); ?></span>
										<button type="button" class="starterkit-cart-drawer__qty-button" data-quantity-delta="1" data-cart-item-key="<?php echo esc_attr( $cart_item_key ); ?>">+</button>
									</div>
									<div class="starterkit-cart-drawer__item-price">
										<?php echo wp_kses_post( WC()->cart->get_product_price( $product ) ); ?>
									</div>
								</div>

								<button type="button" class="starterkit-cart-drawer__remove" data-cart-item-key="<?php echo esc_attr( $cart_item_key ); ?>" aria-label="<?php esc_attr_e( 'Remove item', 'starterkit' ); ?>">
									<svg width="16" height="16" viewBox="19 17 18 24" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M36 26v10.997c0 1.659-1.337 3.003-3.009 3.003h-9.981c-1.662 0-3.009-1.342-3.009-3.003v-10.997h16zm-2 0v10.998c0 .554-.456 1.002-1.002 1.002h-9.995c-.554 0-1.002-.456-1.002-1.002v-10.998h12zm-9-5c0-.552.451-1 .991-1h4.018c.547 0 .991.444.991 1 0 .552-.451 1-.991 1h-4.018c-.547 0-.991-.444-.991-1zm0 6.997c0-.551.444-.997 1-.997.552 0 1 .453 1 .997v6.006c0 .551-.444.997-1 .997-.552 0-1-.453-1-.997v-6.006zm4 0c0-.551.444-.997 1-.997.552 0 1 .453 1 .997v6.006c0 .551-.444.997-1 .997-.552 0-1-.453-1-.997v-6.006zm-6-5.997h-4.008c-.536 0-.992.448-.992 1 0 .556.444 1 .992 1h18.016c.536 0 .992-.448.992-1 0-.556-.444-1-.992-1h-4.008v-1c0-1.653-1.343-3-3-3h-3.999c-1.652 0-3 1.343-3 3v1z"/></svg>
								</button>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>

				<?php echo $this->get_upsell_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endif; ?>
		</div>

		<div class="starterkit-cart-drawer__footer">
			<div class="starterkit-cart-drawer__subtotal">
				<span><?php esc_html_e( 'Subtotal', 'starterkit' ); ?></span>
				<strong><?php echo wp_kses_post( WC()->cart->get_cart_subtotal() ); ?></strong>
			</div>
			<div class="starterkit-cart-drawer__actions">
				<a class="button button-secondary" href="<?php echo esc_url( wc_get_cart_url() ); ?>">
					<?php esc_html_e( 'View Cart', 'starterkit' ); ?>
				</a>
				<a class="button button-primary" href="<?php echo esc_url( wc_get_checkout_url() ); ?>">
					<?php esc_html_e( 'Checkout', 'starterkit' ); ?>
				</a>
			</div>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Build optional upsell markup.
	 *
	 * @return string
	 */
	protected function get_upsell_html() {
		$products = $this->get_upsell_products();

		if ( empty( $products ) ) {
			return '';
		}

		ob_start();
		?>
		<div class="starterkit-cart-drawer__upsell">
			<div class="starterkit-cart-drawer__upsell-header">
				<h3><?php esc_html_e( 'You may also like', 'starterkit' ); ?></h3>
			</div>
			<div class="starterkit-cart-drawer__upsell-grid">
				<?php foreach ( $products as $product ) : ?>
					<div class="starterkit-cart-drawer__upsell-card">
						<a class="starterkit-cart-drawer__upsell-image" href="<?php echo esc_url( $product->get_permalink() ); ?>">
							<?php echo wp_kses_post( $product->get_image( 'woocommerce_thumbnail' ) ); ?>
						</a>
						<div class="starterkit-cart-drawer__upsell-copy">
							<a class="starterkit-cart-drawer__upsell-title" href="<?php echo esc_url( $product->get_permalink() ); ?>">
								<?php echo esc_html( $product->get_name() ); ?>
							</a>
							<div class="starterkit-cart-drawer__upsell-price">
								<?php echo wp_kses_post( apply_filters( 'starterkit_cart_drawer_upsell_price_html', $product->get_price_html(), $product ) ); ?>
							</div>
							<?php echo $this->get_upsell_button_html( $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Render upsell button.
	 *
	 * @param \WC_Product $product Product object.
	 * @return string
	 */
	protected function get_upsell_button_html( $product ) {
		$can_open_selector = (bool) apply_filters( 'starterkit_cart_drawer_product_can_open_selector', $product->is_purchasable() && $product->is_in_stock(), $product );

		if ( $this->product_requires_variant_selection( $product ) && $can_open_selector ) {
			$config = $this->get_upsell_selector_config( $product );

			if ( ! empty( $config ) ) {
				$config_id = 'starterkit-cart-drawer-upsell-config-' . (int) $product->get_id();
				$config_json = wp_json_encode( $config, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT );
				$button_text = apply_filters( 'starterkit_cart_drawer_upsell_selector_button_text', __( 'Add to cart', 'starterkit' ), $product, $config );

				if ( ! is_string( $config_json ) ) {
					$config_json = '{}';
				}

				return sprintf(
					'<button type="button" data-product_id="%1$s" data-product_url="%2$s" data-quantity="1" data-config-selector="#%3$s" class="button button-secondary starterkit-cart-drawer__upsell-add starterkit-cart-drawer__upsell-add--selector">%4$s</button><script type="application/json" id="%3$s">%5$s</script>',
					esc_attr( (string) $product->get_id() ),
					esc_url( $product->get_permalink() ),
					esc_attr( $config_id ),
					esc_html( (string) $button_text ),
					$config_json
				);
			}
		}

		if ( $product->is_purchasable() && $product->is_in_stock() && $product->supports( 'ajax_add_to_cart' ) ) {
			return sprintf(
				'<a href="%1$s" data-product_id="%2$s" data-product_sku="%3$s" data-quantity="1" class="button button-secondary starterkit-cart-drawer__upsell-add product_type_%4$s">%5$s</a>',
				esc_url( $product->add_to_cart_url() ),
				esc_attr( (string) $product->get_id() ),
				esc_attr( $product->get_sku() ),
				esc_attr( $product->get_type() ),
				esc_html( $product->add_to_cart_text() )
			);
		}

		return sprintf(
			'<a href="%1$s" class="button button-secondary">%2$s</a>',
			esc_url( $product->get_permalink() ),
			esc_html__( 'View Product', 'starterkit' )
		);
	}

	/**
	 * Determine if an upsell requires a variant picker before add-to-cart.
	 *
	 * @param \WC_Product $product Product object.
	 * @return bool
	 */
	protected function product_requires_variant_selection( $product ) {
		$requires_selection = $product->is_type( 'variable' );

		return (bool) apply_filters( 'starterkit_cart_drawer_product_requires_options', $requires_selection, $product );
	}

	/**
	 * Build the normalized selector payload consumed by the drawer sub-sheet.
	 *
	 * @param \WC_Product $product Product object.
	 * @return array<string, mixed>
	 */
	protected function get_upsell_selector_config( $product ) {
		if ( ! method_exists( $product, 'get_available_variations' ) || ! method_exists( $product, 'get_variation_attributes' ) ) {
			return (array) apply_filters( 'starterkit_cart_drawer_selector_config', array(), $product, $this );
		}

		$available_variations = $product->get_available_variations();
		$attributes           = $this->get_upsell_selector_attributes( $product );

		if ( empty( $available_variations ) || empty( $attributes ) ) {
			return (array) apply_filters( 'starterkit_cart_drawer_selector_config', array(), $product, $this );
		}

		$default_attributes = array();

		if ( method_exists( $product, 'get_default_attributes' ) ) {
			foreach ( (array) $product->get_default_attributes() as $attribute_name => $value ) {
				$normalized_name                         = 0 === strpos( (string) $attribute_name, 'attribute_' ) ? (string) $attribute_name : 'attribute_' . $attribute_name;
				$default_attributes[ $normalized_name ] = (string) $value;
			}
		}

		$variations = array();

		foreach ( $available_variations as $variation ) {
			if ( ! is_array( $variation ) ) {
				continue;
			}

			$variation_id = isset( $variation['variation_id'] ) ? (int) $variation['variation_id'] : 0;

			if ( $variation_id <= 0 ) {
				continue;
			}

			$variation_attributes = array();

			foreach ( (array) ( $variation['attributes'] ?? array() ) as $attribute_name => $value ) {
				$variation_attributes[ (string) $attribute_name ] = (string) $value;
			}

			$variation_image = isset( $variation['image'] ) && is_array( $variation['image'] ) ? $variation['image'] : array();

			$variations[] = array(
				'variationId'       => $variation_id,
				'attributes'        => $variation_attributes,
				'priceHtml'         => (string) ( $variation['price_html'] ?? '' ),
				'availabilityHtml'  => (string) ( $variation['availability_html'] ?? '' ),
				'isInStock'         => ! empty( $variation['is_in_stock'] ),
				'isPurchasable'     => ! empty( $variation['is_purchasable'] ),
				'variationIsActive' => ! empty( $variation['variation_is_active'] ),
				'minQty'            => isset( $variation['min_qty'] ) ? max( 1, (int) $variation['min_qty'] ) : 1,
				'maxQty'            => isset( $variation['max_qty'] ) && '' !== (string) $variation['max_qty'] ? max( 0, (int) $variation['max_qty'] ) : 0,
				'image'             => array(
					'src' => isset( $variation_image['src'] ) ? (string) $variation_image['src'] : '',
					'alt' => isset( $variation_image['alt'] ) ? (string) $variation_image['alt'] : $product->get_name(),
				),
			);
		}

		if ( empty( $variations ) ) {
			return array();
		}

		$config = array(
			'productId'         => (int) $product->get_id(),
			'name'              => (string) $product->get_name(),
			'permalink'         => (string) $product->get_permalink(),
			'priceHtml'         => (string) $product->get_price_html(),
			'buttonText'        => (string) $product->add_to_cart_text(),
			'isWootify'         => false,
			'defaultAttributes' => $default_attributes,
			'image'             => $this->get_upsell_selector_product_image( $product ),
			'attributes'        => $attributes,
			'variations'        => $variations,
		);

		return (array) apply_filters( 'starterkit_cart_drawer_selector_config', $config, $product, $this );
	}

	/**
	 * Build selector attribute metadata.
	 *
	 * @param \WC_Product $product Product object.
	 * @return array<int, array<string, mixed>>
	 */
	protected function get_upsell_selector_attributes( $product ) {
		$variation_attributes = method_exists( $product, 'get_variation_attributes' ) ? (array) $product->get_variation_attributes() : array();

		if ( empty( $variation_attributes ) ) {
			return array();
		}

		$attribute_meta = $this->get_upsell_selector_attribute_meta( $product );
		$attributes     = array();

		foreach ( $variation_attributes as $attribute_name => $values ) {
			if ( empty( $values ) || ! is_array( $values ) ) {
				continue;
			}

			$options = array();

			foreach ( $values as $value ) {
				$value = (string) $value;

				if ( '' === $value ) {
					continue;
				}

				$options[] = array(
					'value' => $value,
					'label' => isset( $attribute_meta['optionLabels'][ $attribute_name ][ $value ] )
						? (string) $attribute_meta['optionLabels'][ $attribute_name ][ $value ]
						: $this->humanize_upsell_selector_option( $value ),
				);
			}

			if ( empty( $options ) ) {
				continue;
			}

			$attributes[] = array(
				'name'    => (string) $attribute_name,
				'label'   => ! empty( $attribute_meta['labels'][ $attribute_name ] )
					? (string) $attribute_meta['labels'][ $attribute_name ]
					: wc_attribute_label( $this->normalize_upsell_selector_attribute_name( $attribute_name ), $product ),
				'options' => $options,
			);
		}

		return $attributes;
	}

	/**
	 * Build labels and option labels for selector attributes.
	 *
	 * @param \WC_Product $product Product object.
	 * @return array<string, array<string, mixed>>
	 */
	protected function get_upsell_selector_attribute_meta( $product ) {
		$labels        = array();
		$option_labels = array();

		$product_attributes = method_exists( $product, 'get_attributes' ) ? (array) $product->get_attributes() : array();

		foreach ( $product_attributes as $taxonomy => $attribute ) {
			if ( ! $attribute instanceof \WC_Product_Attribute ) {
				continue;
			}

			$variation_name = 'attribute_' . $taxonomy;
			$labels[ $variation_name ] = wc_attribute_label( $taxonomy, $product );
			$option_labels[ $variation_name ] = array();

			if ( $attribute->is_taxonomy() && taxonomy_exists( $taxonomy ) ) {
				$terms = wc_get_product_terms( $product->get_id(), $taxonomy, array( 'fields' => 'all' ) );

				foreach ( $terms as $term ) {
					if ( ! $term instanceof \WP_Term ) {
						continue;
					}

					$option_labels[ $variation_name ][ $term->slug ] = $term->name;
				}

				continue;
			}

			foreach ( (array) $attribute->get_options() as $option_value ) {
				$option_value = (string) $option_value;

				if ( '' === $option_value ) {
					continue;
				}

				$normalized_value = sanitize_title( wp_strip_all_tags( $option_value ) );
				$option_labels[ $variation_name ][ $option_value ] = $option_value;

				if ( '' !== $normalized_value && ! isset( $option_labels[ $variation_name ][ $normalized_value ] ) ) {
					$option_labels[ $variation_name ][ $normalized_value ] = $option_value;
				}
			}
		}

		return array(
			'labels'       => $labels,
			'optionLabels' => $option_labels,
		);
	}

	/**
	 * Normalize a selector attribute name by removing the WC prefix.
	 *
	 * @param string $attribute_name Attribute name.
	 * @return string
	 */
	protected function normalize_upsell_selector_attribute_name( $attribute_name ) {
		return 0 === strpos( $attribute_name, 'attribute_' ) ? substr( $attribute_name, 10 ) : $attribute_name;
	}

	/**
	 * Humanize a selector option fallback label.
	 *
	 * @param string $value Attribute value.
	 * @return string
	 */
	protected function humanize_upsell_selector_option( $value ) {
		return ucwords( trim( str_replace( array( '-', '_' ), ' ', wp_strip_all_tags( $value ) ) ) );
	}

	/**
	 * Build a normalized product image payload for the selector sheet.
	 *
	 * @param \WC_Product $product Product object.
	 * @return array<string, string>
	 */
	protected function get_upsell_selector_product_image( $product ) {
		$image_id = $product->get_image_id();
		$image    = array(
			'src' => function_exists( 'wc_placeholder_img_src' ) ? (string) wc_placeholder_img_src( 'woocommerce_thumbnail' ) : '',
			'alt' => (string) $product->get_name(),
		);

		if ( $image_id ) {
			$image['src'] = (string) wp_get_attachment_image_url( $image_id, 'woocommerce_thumbnail' );
			$image['alt'] = (string) get_post_meta( $image_id, '_wp_attachment_image_alt', true );
		}

		if ( '' === $image['alt'] ) {
			$image['alt'] = (string) $product->get_name();
		}

		return $image;
	}

	/**
	 * Return upsell product candidates.
	 *
	 * @return array<int, \WC_Product>
	 */
	protected function get_upsell_products() {
		$exclude = array();
		$products = array();
		$seen     = array();
		$limit    = 2;

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			if ( ! empty( $cart_item['product_id'] ) ) {
				$exclude[] = (int) $cart_item['product_id'];
			}
		}

		$exclude = array_values( array_unique( array_filter( $exclude ) ) );

		$append_products = static function( $items ) use ( &$products, &$seen, $exclude, $limit ) {
			foreach ( (array) $items as $item ) {
				if ( ! $item instanceof \WC_Product ) {
					continue;
				}

				$product_id = (int) $item->get_id();

				if ( $product_id <= 0 || isset( $seen[ $product_id ] ) || in_array( $product_id, $exclude, true ) ) {
					continue;
				}

				$seen[ $product_id ] = true;
				$products[]          = $item;

				if ( count( $products ) >= $limit ) {
					break;
				}
			}
		};

		$cross_sell_ids = array();

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$product = isset( $cart_item['data'] ) ? $cart_item['data'] : null;

			if ( ! $product instanceof \WC_Product || ! method_exists( $product, 'get_cross_sell_ids' ) ) {
				continue;
			}

			$cross_sell_ids = array_merge( $cross_sell_ids, array_map( 'intval', (array) $product->get_cross_sell_ids() ) );
		}

		$cross_sell_ids = array_values( array_diff( array_unique( array_filter( $cross_sell_ids ) ), $exclude ) );

		if ( ! empty( $cross_sell_ids ) && function_exists( 'wc_get_products' ) ) {
			$append_products(
				wc_get_products(
					array(
						'status'  => 'publish',
						'limit'   => $limit,
						'include' => $cross_sell_ids,
						'orderby' => 'include',
					)
				)
			);
		}

		$args = array(
			'status'  => 'publish',
			'limit'   => $limit,
			'orderby' => 'date',
			'order'   => 'DESC',
			'exclude' => $exclude,
		);

		if ( count( $products ) < $limit && function_exists( 'wc_get_products' ) ) {
			$append_products( wc_get_products( array_merge( $args, array( 'featured' => true ) ) ) );
		}

		if ( count( $products ) < $limit && function_exists( 'wc_get_products' ) ) {
			$append_products( wc_get_products( $args ) );
		}

		$products = apply_filters( 'starterkit_cart_drawer_upsell_products', $products, $exclude, $this );

		return is_array( $products ) ? array_slice( $products, 0, $limit ) : array();
	}

	/**
	 * Shared cart count badge HTML.
	 *
	 * @return string
	 */
	protected function get_cart_count_html() {
		return '<span class="header-cart-count">' . esc_html( (string) WC()->cart->get_cart_contents_count() ) . '</span>';
	}

	/**
	 * Determine if WooCommerce cart APIs are available.
	 *
	 * @return bool
	 */
	protected function is_available() {
		return class_exists( 'WooCommerce' ) && function_exists( 'WC' ) && WC()->cart;
	}
}
