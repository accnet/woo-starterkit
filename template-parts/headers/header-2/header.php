<?php
/**
 * Header preset 2.
 *
 * @package StarterKit
 */

$cart_count = function_exists( 'WC' ) && WC()->cart ? (int) WC()->cart->get_cart_contents_count() : 0;
$cart_url   = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' );
$logo_id   = (int) starterkit()->settings_manager()->get( 'logo_id', 0 );
$zone_renderer = starterkit()->zone_renderer();
$layout_settings_manager = starterkit()->layout_settings_manager();
$layout_settings         = $layout_settings_manager->get_layout_settings( 'header-2' );
$header_style            = $layout_settings_manager->header_2_inline_style( $layout_settings );
$header_classes          = array( 'site-header', 'site-header--preset-2' );

if ( '1' !== (string) ( isset( $layout_settings['header_2_enable_sticky'] ) ? $layout_settings['header_2_enable_sticky'] : '1' ) ) {
	$header_classes[] = 'site-header--not-sticky';
}
?>
<header class="<?php echo esc_attr( implode( ' ', $header_classes ) ); ?>" data-header-behavior="sticky menu search"<?php echo $header_style ? ' style="' . esc_attr( $header_style ) . '"' : ''; ?>>
	<?php $zone_renderer->render( 'header_top', array( 'context' => 'master' ) ); ?>
	<div class="container header-shell header-shell--preset-2">
		<div class="site-header__top-row">
			<div class="site-header__toggle-wrap">
				<?php $zone_renderer->render( 'header_2_top_left', array( 'context' => 'master' ) ); ?>
				<button class="site-header__toggle" type="button" aria-expanded="false" aria-controls="site-header-panel-2">
					<span><?php esc_html_e( 'Menu', 'starterkit' ); ?></span>
				</button>
			</div>
			<div class="site-branding">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-title">
					<?php if ( $logo_id && ( $logo_url = wp_get_attachment_image_url( $logo_id, 'medium' ) ) ) : ?>
						<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php bloginfo( 'name' ); ?>" class="site-logo">
					<?php else : ?>
						<?php bloginfo( 'name' ); ?>
					<?php endif; ?>
				</a>
			</div>
			<div class="site-header__panel-actions">
				<button class="header-search-toggle" type="button" aria-expanded="false" aria-controls="site-header-search-2" aria-label="<?php esc_attr_e( 'Search', 'starterkit' ); ?>">
					<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
						<circle cx="11" cy="11" r="6.5"></circle>
						<path d="M16 16l5 5"></path>
					</svg>
				</button>
				<a class="header-cart-link" href="<?php echo esc_url( $cart_url ); ?>" aria-label="<?php esc_attr_e( 'Cart', 'starterkit' ); ?>">
					<span class="screen-reader-text"><?php esc_html_e( 'View cart', 'starterkit' ); ?></span>
					<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
						<path d="M3 4h2l1.3 8.2a2 2 0 0 0 2 1.8h7.9a2 2 0 0 0 2-1.6L20 7H7"></path>
						<circle cx="10" cy="19" r="1.5"></circle>
						<circle cx="17" cy="19" r="1.5"></circle>
					</svg>
					<span class="header-cart-count"><?php echo esc_html( (string) $cart_count ); ?></span>
				</a>
			</div>
		</div>
	</div>
	<div id="site-header-panel-2" class="site-header__panel">
		<div class="container site-header__panel-inner">
			<?php $zone_renderer->render( 'header_2_before_navigation', array( 'context' => 'master' ) ); ?>
			<?php echo $layout_settings_manager->render_header_2_navigation( $layout_settings ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php $zone_renderer->render( 'header_2_after_navigation', array( 'context' => 'master' ) ); ?>
			<div class="site-header__panel-cart">
				<a class="header-cart-button" href="<?php echo esc_url( $cart_url ); ?>">
					<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
						<path d="M3 4h2l1.3 8.2a2 2 0 0 0 2 1.8h7.9a2 2 0 0 0 2-1.6L20 7H7"></path>
						<circle cx="10" cy="19" r="1.5"></circle>
						<circle cx="17" cy="19" r="1.5"></circle>
					</svg>
					<?php esc_html_e( 'Cart', 'starterkit' ); ?>
					<span class="header-cart-count"><?php echo esc_html( (string) $cart_count ); ?></span>
				</a>
			</div>
		</div>
	</div>
	<div id="site-header-search-2" class="header-search-panel" hidden>
		<div class="container">
			<?php get_search_form(); ?>
		</div>
	</div>
	<?php $zone_renderer->render( 'header_bottom', array( 'context' => 'master' ) ); ?>
</header>
