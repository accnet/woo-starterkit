<?php
/**
 * Header preset 3.
 *
 * @package StarterKit
 */

$cart_count = function_exists( 'WC' ) && WC()->cart ? (int) WC()->cart->get_cart_contents_count() : 0;
$cart_url   = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' );
$logo_id   = (int) starterkit()->settings_manager()->get( 'logo_id', 0 );
?>
<header class="site-header site-header--preset-3" data-header-behavior="menu search">
	<?php starterkit_render_slot( 'header_top' ); ?>
	<div class="container header-shell header-shell--preset-3">
		<div class="site-branding">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-title">
				<?php if ( $logo_id && ( $logo_url = wp_get_attachment_image_url( $logo_id, 'medium' ) ) ) : ?>
					<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php bloginfo( 'name' ); ?>" class="site-logo">
				<?php else : ?>
					<?php bloginfo( 'name' ); ?>
				<?php endif; ?>
			</a>
		</div>
		<button class="site-header__toggle" type="button" aria-expanded="false" aria-controls="site-header-panel-3">
			<span><?php esc_html_e( 'Menu', 'starterkit' ); ?></span>
		</button>
		<div id="site-header-panel-3" class="site-header__panel">
			<nav class="site-navigation">
				<?php wp_nav_menu( array( 'theme_location' => 'primary', 'fallback_cb' => false ) ); ?>
			</nav>
			<div class="header-actions">
				<button class="header-search-toggle" type="button" aria-expanded="false" aria-controls="site-header-search-3">
					<span><?php esc_html_e( 'Search', 'starterkit' ); ?></span>
				</button>
				<a href="<?php echo esc_url( $cart_url ); ?>" class="header-cart-button">
					<span class="screen-reader-text"><?php esc_html_e( 'View cart', 'starterkit' ); ?></span>
					<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
						<path d="M3 4h2l1.3 8.2a2 2 0 0 0 2 1.8h7.9a2 2 0 0 0 2-1.6L20 7H7"></path>
						<circle cx="10" cy="19" r="1.5"></circle>
						<circle cx="17" cy="19" r="1.5"></circle>
					</svg>
					<span class="header-cart-count"><?php echo esc_html( (string) $cart_count ); ?></span>
				</a>
				<a href="<?php echo esc_url( home_url( '/shop/' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Shop', 'starterkit' ); ?></a>
			</div>
		</div>
	</div>
	<div id="site-header-search-3" class="header-search-panel" hidden>
		<div class="container">
			<?php get_search_form(); ?>
		</div>
	</div>
	<?php starterkit_render_slot( 'header_bottom' ); ?>
</header>
