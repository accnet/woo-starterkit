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
?>
<header class="site-header site-header--preset-2" data-header-behavior="sticky menu">
	<?php $zone_renderer->render( 'header_top', array( 'context' => 'master' ) ); ?>
	<div class="header-topbar">
		<div class="container header-topbar__inner">
			<span><?php esc_html_e( 'Preset-driven commerce theme', 'starterkit' ); ?></span>
		</div>
	</div>
	<div class="container header-shell header-shell--preset-2">
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
			<a class="header-cart-link" href="<?php echo esc_url( $cart_url ); ?>">
				<span class="screen-reader-text"><?php esc_html_e( 'View cart', 'starterkit' ); ?></span>
				<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
					<path d="M3 4h2l1.3 8.2a2 2 0 0 0 2 1.8h7.9a2 2 0 0 0 2-1.6L20 7H7"></path>
					<circle cx="10" cy="19" r="1.5"></circle>
					<circle cx="17" cy="19" r="1.5"></circle>
				</svg>
				<span class="header-cart-count"><?php echo esc_html( (string) $cart_count ); ?></span>
			</a>
		</div>
		<button class="site-header__toggle" type="button" aria-expanded="false" aria-controls="site-header-panel-2">
			<span><?php esc_html_e( 'Menu', 'starterkit' ); ?></span>
		</button>
		<div id="site-header-panel-2" class="site-header__panel">
			<nav class="site-navigation">
				<?php wp_nav_menu( array( 'theme_location' => 'primary', 'fallback_cb' => false ) ); ?>
			</nav>
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
	<?php $zone_renderer->render( 'header_bottom', array( 'context' => 'master' ) ); ?>
</header>
