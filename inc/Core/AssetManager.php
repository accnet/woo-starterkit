<?php
/**
 * Frontend asset registration.
 *
 * @package StarterKit
 */

namespace StarterKit\Core;

use StarterKit\Rules\DisplayRuleEvaluator;
use StarterKit\Rules\PageContextResolver;
use StarterKit\Settings\FontEmbedManager;
use StarterKit\Settings\GlobalSettingsManager;
use StarterKit\Layouts\LayoutRegistry;
use StarterKit\Layouts\LayoutResolver;
use StarterKit\Sections\SectionInstanceRepository;
use StarterKit\Sections\SectionTypeRegistry;

class AssetManager {
	/**
	 * Settings manager.
	 *
	 * @var GlobalSettingsManager
	 */
	protected $settings;

	/**
	 * Layout registry.
	 *
	 * @var LayoutRegistry
	 */
	protected $layout_registry;

	/**
	 * Section type registry.
	 *
	 * @var SectionTypeRegistry
	 */
	protected $section_type_registry;

	/**
	 * Section repository.
	 *
	 * @var SectionInstanceRepository
	 */
	protected $section_repository;

	/**
	 * Context resolver.
	 *
	 * @var PageContextResolver
	 */
	protected $context_resolver;

	/**
	 * Rule evaluator.
	 *
	 * @var DisplayRuleEvaluator
	 */
	protected $rule_evaluator;

	/**
	 * Layout resolver.
	 *
	 * @var LayoutResolver
	 */
	protected $layout_resolver;

	/**
	 * Local font embed manager.
	 *
	 * @var FontEmbedManager|null
	 */
	protected $font_embed_manager;

	/**
	 * Hook registration.
	 *
	 * @param GlobalSettingsManager $settings Settings manager.
	 */
	public function __construct( GlobalSettingsManager $settings, LayoutRegistry $layout_registry, SectionTypeRegistry $section_type_registry, SectionInstanceRepository $section_repository, PageContextResolver $context_resolver, DisplayRuleEvaluator $rule_evaluator, LayoutResolver $layout_resolver ) {
		$this->settings              = $settings;
		$this->layout_registry       = $layout_registry;
		$this->section_type_registry = $section_type_registry;
		$this->section_repository    = $section_repository;
		$this->context_resolver      = $context_resolver;
		$this->rule_evaluator        = $rule_evaluator;
		$this->layout_resolver       = $layout_resolver;

		if ( function_exists( 'starterkit' ) ) {
			$this->font_embed_manager = starterkit()->font_embed_manager();
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Enqueue theme assets.
	 *
	 * @return void
	 */
	public function enqueue() {
		$version  = wp_get_theme()->get( 'Version' );
		$context  = $this->context_resolver->resolve();
		$font_url = $this->local_font_url();

		if ( ! $font_url ) {
			$font_url = $this->google_fonts_url();
		}

		if ( $font_url ) {
			wp_enqueue_style(
				'starterkit-theme-fonts',
				$font_url,
				array(),
				null
			);
		}

		wp_enqueue_style(
			'starterkit-theme',
			get_template_directory_uri() . '/assets/css/theme.css',
			array(),
			$version
		);

		$this->enqueue_active_layout_assets( $context );
		$this->enqueue_active_section_assets( $context );
		$this->enqueue_commerce_assets();
	}

	/**
	 * Enqueue CSS and JS for layouts actually used on the current request.
	 *
	 * @param array<string, mixed> $context Request context.
	 * @return void
	 */
	protected function enqueue_active_layout_assets( array $context ) {
		$layout_ids = array_filter(
			array_unique(
				array(
					(string) $this->settings->get( 'header_layout', 'header-1' ),
					(string) $this->settings->get( 'footer_layout', 'footer-1' ),
					! empty( $context['is_homepage'] ) ? (string) $this->settings->get( 'master_layout', 'master-default' ) : '',
					! empty( $context['is_product'] ) ? (string) $this->settings->get( 'product_layout', 'product-layout-1' ) : '',
					! empty( $context['is_product_archive'] ) ? (string) $this->settings->get( 'archive_layout', 'archive-layout-1' ) : '',
				)
			)
		);

		foreach ( $layout_ids as $layout_id ) {
			$layout = $this->layout_registry->get( $layout_id );

			if ( empty( $layout['asset_base'] ) ) {
				continue;
			}

			$this->enqueue_asset_bundle( 'starterkit-layout-', $layout_id, (string) $layout['asset_base'] );
		}
	}

	/**
	 * Enqueue assets only for section types that will actually render on this request.
	 *
	 * @param array<string, mixed> $context Request context.
	 * @return void
	 */
	protected function enqueue_active_section_assets( array $context ) {
		$type_ids = array();
		$slots    = $this->resolve_request_slots( $context );

		foreach ( $slots as $slot_name ) {
			$sections = $this->section_repository->get_active_sections( $slot_name );

			foreach ( $sections as $section ) {
				if ( $slot_name !== $section['slot'] ) {
					continue;
				}

				if ( ! $this->layout_resolver->is_slot_supported( $slot_name, $context ) ) {
					continue;
				}

				if ( ! $this->rule_evaluator->matches( (array) $section['display_rules'], $context ) ) {
					continue;
				}

				if ( ! empty( $section['type'] ) ) {
					$type_ids[] = (string) $section['type'];
				}
			}
		}

		$type_ids = array_unique( array_filter( $type_ids ) );

		foreach ( $type_ids as $type_id ) {
			$type = $this->section_type_registry->get( $type_id );

			if ( empty( $type['asset_base'] ) ) {
				continue;
			}

			$this->enqueue_asset_bundle( 'starterkit-section-', $type_id, (string) $type['asset_base'] );
		}
	}

	/**
	 * Resolve all slots that can render on the current request.
	 *
	 * @param array<string, mixed> $context Request context.
	 * @return array<int, string>
	 */
	protected function resolve_request_slots( array $context ) {
		$slots = array( 'header_top', 'header_bottom', 'footer_top', 'footer_bottom' );

		if ( ! empty( $context['is_homepage'] ) ) {
			$master = $this->layout_registry->get( (string) $this->settings->get( 'master_layout', 'master-default' ) );
			$slots  = array_merge( $slots, isset( $master['slots'] ) && is_array( $master['slots'] ) ? $master['slots'] : array() );
		}

		if ( ! empty( $context['is_product'] ) ) {
			$product = $this->layout_registry->get( (string) $this->settings->get( 'product_layout', 'product-layout-1' ) );
			$slots   = array_merge( $slots, isset( $product['slots'] ) && is_array( $product['slots'] ) ? $product['slots'] : array() );
		}

		if ( ! empty( $context['is_product_archive'] ) ) {
			$archive = $this->layout_registry->get( (string) $this->settings->get( 'archive_layout', 'archive-layout-1' ) );
			$slots   = array_merge( $slots, isset( $archive['slots'] ) && is_array( $archive['slots'] ) ? $archive['slots'] : array() );
		}

		return array_values( array_unique( array_filter( $slots ) ) );
	}

	/**
	 * Conditionally enqueue cart / checkout page assets.
	 *
	 * @return void
	 */
	protected function enqueue_commerce_assets() {
		if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'is_cart' ) ) {
			return;
		}

		$base = get_template_directory();
		$uri  = get_template_directory_uri();
		$core_js = $base . '/assets/js/commerce-store.js';

		if ( file_exists( $core_js ) ) {
			wp_enqueue_script(
				'starterkit-commerce-store',
				$uri . '/assets/js/commerce-store.js',
				array(),
				(string) filemtime( $core_js ),
				true
			);

			wp_localize_script(
				'starterkit-commerce-store',
				'starterkitCommerce',
				$this->build_commerce_config()
			);
		}

		if ( is_cart() ) {
			$css = $base . '/assets/css/cart.css';
			$js  = $base . '/assets/js/cart.js';

			if ( file_exists( $css ) ) {
				wp_enqueue_style( 'starterkit-cart', $uri . '/assets/css/cart.css', array( 'starterkit-theme' ), (string) filemtime( $css ) );
			}
			if ( file_exists( $js ) ) {
				wp_enqueue_script( 'starterkit-cart', $uri . '/assets/js/cart.js', array( 'starterkit-commerce-store' ), (string) filemtime( $js ), true );
			}
		}

		if ( is_checkout() ) {
			$css = $base . '/assets/css/checkout.css';
			$js  = $base . '/assets/js/checkout.js';

			if ( file_exists( $css ) ) {
				wp_enqueue_style( 'starterkit-checkout', $uri . '/assets/css/checkout.css', array( 'starterkit-theme' ), (string) filemtime( $css ) );
			}
			if ( file_exists( $js ) ) {
				wp_enqueue_script( 'starterkit-checkout', $uri . '/assets/js/checkout.js', array( 'starterkit-commerce-store' ), (string) filemtime( $js ), true );
			}
		}
	}

	/**
	 * Build front-end config used by custom cart and checkout UIs.
	 *
	 * @return array<string, mixed>
	 */
	protected function build_commerce_config() {
		$countries       = function_exists( 'WC' ) ? WC()->countries : null;
		$allowed         = $countries ? $countries->get_allowed_countries() : array();
		$states          = $countries ? $countries->get_states() : array();
		$payment_methods = array();
		$free_shipping_threshold = (float) starterkit()->settings_manager()->get( 'free_shipping_threshold', '0' );

		if ( function_exists( 'WC' ) && null !== WC()->payment_gateways() ) {
			foreach ( WC()->payment_gateways()->get_available_payment_gateways() as $gateway ) {
				$payment_methods[] = array(
					'id'                => (string) $gateway->id,
					'title'             => wp_strip_all_tags( (string) $gateway->get_title() ),
					'description'       => wp_strip_all_tags( (string) $gateway->get_description() ),
					'order_button_text' => wp_strip_all_tags( (string) $gateway->order_button_text ),
					'icon_html'         => wp_kses_post( $gateway->get_icon() ),
					'supports'          => array_values( array_map( 'strval', (array) $gateway->supports ) ),
					'has_fields'        => ! empty( $gateway->has_fields ),
				);
			}
		}

		return array(
			'apiBase'        => esc_url_raw( rest_url( 'wc/store/v1' ) ),
			'nonce'          => wp_create_nonce( 'wc_store_api' ),
			'cartUrl'        => function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' ),
			'checkoutUrl'    => function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/checkout/' ),
			'orderReceivedBase' => function_exists( 'wc_get_checkout_url' ) ? trailingslashit( wc_get_checkout_url() ) . 'order-received/' : trailingslashit( home_url( '/checkout/' ) ) . 'order-received/',
			'shopUrl'        => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' ),
			'countries'      => $allowed,
			'states'         => $states,
			'paymentMethods' => $payment_methods,
			'freeShippingThreshold' => $free_shipping_threshold,
			'i18n'           => array(
				'loadingCart'       => __( 'Loading your cart...', 'starterkit' ),
				'loadingCheckout'   => __( 'Preparing checkout...', 'starterkit' ),
				'updateError'       => __( 'We could not update your cart. Please try again.', 'starterkit' ),
				'checkoutError'     => __( 'We could not process checkout. Please review your details and try again.', 'starterkit' ),
				'emptyCart'         => __( 'Your cart is empty.', 'starterkit' ),
				'emptyCartBody'     => __( 'Add products before continuing to checkout.', 'starterkit' ),
				'backToShop'        => __( 'Continue shopping', 'starterkit' ),
				'applyingCoupon'    => __( 'Applying…', 'starterkit' ),
				'placeOrder'        => __( 'Place order', 'starterkit' ),
				'processingOrder'   => __( 'Processing…', 'starterkit' ),
				'remove'            => __( 'Remove', 'starterkit' ),
				'subtotal'          => __( 'Subtotal', 'starterkit' ),
				'shipping'          => __( 'Shipping', 'starterkit' ),
				'total'             => __( 'Total', 'starterkit' ),
				'cartTitle'         => __( 'Your cart', 'starterkit' ),
				'checkoutTitle'     => __( 'Checkout', 'starterkit' ),
				'contact'           => __( 'Contact', 'starterkit' ),
				'delivery'          => __( 'Delivery', 'starterkit' ),
				'payment'           => __( 'Payment', 'starterkit' ),
				'orderSummary'      => __( 'Order summary', 'starterkit' ),
				'discountCode'      => __( 'Discount code', 'starterkit' ),
				'apply'             => __( 'Apply', 'starterkit' ),
				'selectOption'      => __( 'Select', 'starterkit' ),
				'shippingPending'   => __( 'Enter shipping address', 'starterkit' ),
				'paymentUnavailable'=> __( 'No payment methods are currently available for this order.', 'starterkit' ),
				'itemCountSingular' => __( '%d item', 'starterkit' ),
				'itemCountPlural'   => __( '%d items', 'starterkit' ),
				'freeShippingUnlocked' => __( 'You unlocked free shipping.', 'starterkit' ),
				'freeShippingRemaining' => __( 'Add %s more for free shipping', 'starterkit' ),
				'cartDrawerTitle'   => __( 'Your cart', 'starterkit' ),
				'viewCart'          => __( 'View cart', 'starterkit' ),
				'checkout'          => __( 'Checkout', 'starterkit' ),
				'continueShopping'  => __( 'Continue shopping', 'starterkit' ),
				'emptyDrawerBody'   => __( 'Add something good and your bag will appear here.', 'starterkit' ),
				'orderNotes'        => __( 'Order notes', 'starterkit' ),
				'shippingMethod'    => __( 'Shipping method', 'starterkit' ),
				'billingAddress'    => __( 'Billing address', 'starterkit' ),
				'shippingAddress'   => __( 'Shipping address', 'starterkit' ),
				'sameAsShipping'    => __( 'Billing address is the same as shipping', 'starterkit' ),
				'email'             => __( 'Email', 'starterkit' ),
				'phone'             => __( 'Phone', 'starterkit' ),
				'firstName'         => __( 'First name', 'starterkit' ),
				'lastName'          => __( 'Last name', 'starterkit' ),
				'company'           => __( 'Company', 'starterkit' ),
				'address1'          => __( 'Address line 1', 'starterkit' ),
				'address2'          => __( 'Address line 2', 'starterkit' ),
				'city'              => __( 'City', 'starterkit' ),
				'postcode'          => __( 'Postcode', 'starterkit' ),
				'country'           => __( 'Country / Region', 'starterkit' ),
				'state'             => __( 'State / Province', 'starterkit' ),
				'orderPlaced'       => __( 'Order created. Redirecting…', 'starterkit' ),
				'paymentUnsupported'=> __( 'This gateway needs extra fields that are not exposed in the custom checkout yet.', 'starterkit' ),
				'discount'          => __( 'Discount', 'starterkit' ),
			),
		);
	}

	/**
	 * Enqueue an asset bundle when local preset files are present.
	 *
	 * @param string $handle_prefix Handle prefix.
	 * @param string $asset_id Asset identifier.
	 * @param string $asset_base Asset base path.
	 * @return void
	 */
	protected function enqueue_asset_bundle( $handle_prefix, $asset_id, $asset_base ) {
		$asset_base  = trim( $asset_base, '/' );
		$style_path  = get_template_directory() . '/' . $asset_base . '/style.css';
		$script_path = get_template_directory() . '/' . $asset_base . '/script.js';

		if ( file_exists( $style_path ) ) {
			wp_enqueue_style(
				$handle_prefix . sanitize_key( $asset_id ),
				get_template_directory_uri() . '/' . $asset_base . '/style.css',
				array( 'starterkit-theme' ),
				(string) filemtime( $style_path )
			);
		}

		if ( file_exists( $script_path ) ) {
			wp_enqueue_script(
				$handle_prefix . sanitize_key( $asset_id ),
				get_template_directory_uri() . '/' . $asset_base . '/script.js',
				array(),
				(string) filemtime( $script_path ),
				true
			);
		}
	}

	/**
	 * Build Google Fonts URL from selected font families.
	 *
	 * @return string
	 */
	protected function google_fonts_url() {
		$families = array_unique(
			array_filter(
				array(
					(string) $this->settings->get( 'heading_font', 'Poppins' ),
					(string) $this->settings->get( 'body_font', 'Inter' ),
				)
			)
		);

		$options = $this->settings->google_font_options();
		$query_families = array();

		foreach ( $families as $family ) {
			if ( isset( $options[ $family ]['query'] ) ) {
				$query_families[] = $options[ $family ]['query'];
			}
		}

		if ( empty( $query_families ) ) {
			return '';
		}

		return add_query_arg(
			array(
				'family'  => implode( '&family=', $query_families ),
				'display' => 'swap',
			),
			'https://fonts.googleapis.com/css2'
		);
	}

	/**
	 * Return local generated font CSS URL when available.
	 *
	 * @return string
	 */
	protected function local_font_url() {
		if ( ! $this->font_embed_manager || ! $this->font_embed_manager->has_current_embed() ) {
			return '';
		}

		return $this->font_embed_manager->local_css_url();
	}
}
