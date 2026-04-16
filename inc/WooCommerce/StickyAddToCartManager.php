<?php
/**
 * Sticky add-to-cart bar for WooCommerce single products.
 *
 * @package StarterKit
 */

namespace StarterKit\WooCommerce;

class StickyAddToCartManager {
	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_footer', array( $this, 'render_bar' ), 25 );
	}

	/**
	 * Enqueue sticky bar assets on product pages only.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}

		$css_path = get_template_directory() . '/assets/css/sticky-add-to-cart.css';
		$js_path  = get_template_directory() . '/assets/js/sticky-add-to-cart.js';

		wp_enqueue_style(
			'starterkit-sticky-add-to-cart',
			get_template_directory_uri() . '/assets/css/sticky-add-to-cart.css',
			array( 'starterkit-theme' ),
			file_exists( $css_path ) ? (string) filemtime( $css_path ) : wp_get_theme()->get( 'Version' )
		);

		wp_enqueue_script(
			'starterkit-sticky-add-to-cart',
			get_template_directory_uri() . '/assets/js/sticky-add-to-cart.js',
			array(),
			file_exists( $js_path ) ? (string) filemtime( $js_path ) : wp_get_theme()->get( 'Version' ),
			true
		);
	}

	/**
	 * Render sticky add-to-cart markup.
	 *
	 * @return void
	 */
	public function render_bar() {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}

		global $product;

		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		$image_html     = $product->get_image( 'woocommerce_thumbnail' );
		$product_url    = get_permalink( $product->get_id() );
		$product_type   = $product->get_type();
		$is_simple      = $product->is_type( 'simple' );
		$is_external    = $product->is_type( 'external' );
		$button_label   = $is_simple ? __( 'Add to cart', 'starterkit' ) : __( 'Choose options', 'starterkit' );
		$action_mode    = $is_simple ? 'submit' : 'scroll';
		$quantity_value = 1;
		?>
		<div class="starterkit-sticky-atc" data-sticky-atc data-action-mode="<?php echo esc_attr( $action_mode ); ?>" hidden>
			<div class="starterkit-sticky-atc__inner container">
				<div class="starterkit-sticky-atc__product">
					<div class="starterkit-sticky-atc__image">
						<a href="<?php echo esc_url( $product_url ); ?>"><?php echo wp_kses_post( $image_html ); ?></a>
					</div>
					<div class="starterkit-sticky-atc__copy">
						<p class="starterkit-sticky-atc__title"><?php echo esc_html( $product->get_name() ); ?></p>
						<div class="starterkit-sticky-atc__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></div>
					</div>
				</div>

				<div class="starterkit-sticky-atc__actions">
					<?php if ( $is_simple && ! $is_external ) : ?>
						<label class="starterkit-sticky-atc__qty-label" for="starterkit-sticky-atc-qty">
							<span class="screen-reader-text"><?php esc_html_e( 'Quantity', 'starterkit' ); ?></span>
						</label>
						<input id="starterkit-sticky-atc-qty" class="starterkit-sticky-atc__qty" type="number" min="1" step="1" value="<?php echo esc_attr( (string) $quantity_value ); ?>">
					<?php endif; ?>

					<button type="button" class="button button-primary starterkit-sticky-atc__button" data-sticky-atc-trigger>
						<?php echo esc_html( $button_label ); ?>
					</button>
				</div>
			</div>
		</div>
		<?php
	}
}
