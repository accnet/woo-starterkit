<?php
/**
 * Header preset 1.
 *
 * @package StarterKit
 */

$cart_count = function_exists( 'WC' ) && WC()->cart ? (int) WC()->cart->get_cart_contents_count() : 0;
$cart_url   = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' );
$logo_id   = (int) starterkit()->settings_manager()->get( 'logo_id', 0 );
$zone_renderer = starterkit()->zone_renderer();
$layout_settings_manager = starterkit()->layout_settings_manager();
$layout_settings         = $layout_settings_manager->get_layout_settings( 'header-1' );
$header_style            = $layout_settings_manager->header_1_inline_style( $layout_settings );
?>
<header class="site-header site-header--preset-1" data-header-behavior="menu search"<?php echo $header_style ? ' style="' . esc_attr( $header_style ) . '"' : ''; ?>>
	<?php $zone_renderer->render( 'header_top', array( 'context' => 'master' ) ); ?>
	<button class="site-header__backdrop" type="button" data-header-close aria-label="<?php esc_attr_e( 'Close menu', 'starterkit' ); ?>"></button>
	<div class="container header-shell header-shell--preset-1">
		<div class="site-branding">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-title">
				<?php if ( $logo_id && ( $logo_url = wp_get_attachment_image_url( $logo_id, 'medium' ) ) ) : ?>
					<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php bloginfo( 'name' ); ?>" class="site-logo">
				<?php else : ?>
					<?php bloginfo( 'name' ); ?>
				<?php endif; ?>
			</a>
		</div>

		<div id="site-header-panel-1" class="site-header__panel">
			<div class="site-header__panel-header">
				<strong><?php bloginfo( 'name' ); ?></strong>
				<button class="site-header__close" type="button" aria-label="<?php esc_attr_e( 'Close menu', 'starterkit' ); ?>">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<?php echo $layout_settings_manager->render_header_1_navigation( $layout_settings ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<div class="site-header__panel-actions">
				<button class="header-search-button" type="button" aria-expanded="false" aria-controls="site-header-search-1">
					<?php esc_html_e( 'Search', 'starterkit' ); ?>
				</button>
				<a class="header-cart-button" href="<?php echo esc_url( $cart_url ); ?>">
					<?php esc_html_e( 'Cart', 'starterkit' ); ?>
					<span class="header-cart-count"><?php echo esc_html( (string) $cart_count ); ?></span>
				</a>
			</div>
		</div>

		<div class="site-header__controls">
			<button class="site-header__toggle" type="button" aria-expanded="false" aria-controls="site-header-panel-1">
				<span class="site-header__toggle-icon" aria-hidden="true"></span>
				<span class="screen-reader-text"><?php esc_html_e( 'Menu', 'starterkit' ); ?></span>
			</button>
			<a class="header-icon-button header-cart-link" href="<?php echo esc_url( $cart_url ); ?>" aria-label="<?php esc_attr_e( 'Cart', 'starterkit' ); ?>">
				<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
					<path d="M19.77,4.71H17.3l-.07-.19C16.14,1.59,14.36,0,12.08,0S8,1.59,6.9,4.52c0,.06,0,.12-.07.19H4.39a3,3,0,0,0-3,2.94V21.06a3,3,0,0,0,3,2.94H19.77a3,3,0,0,0,3-2.94V7.65a3,3,0,0,0-3-2.94Zm-7.7-3.59c2.12,0,3.36,1.75,4.07,3.59H8c.73-1.84,2-3.59,4.1-3.59Zm9.59,19.94a1.88,1.88,0,0,1-1.88,1.87H4.39a1.88,1.88,0,0,1-1.88-1.87V7.65A1.88,1.88,0,0,1,4.39,5.78H6.49A18,18,0,0,0,6,8.12a1.13,1.13,0,1,0,1.08.09,17.14,17.14,0,0,1,.53-2.43h8.9A17.13,17.13,0,0,1,17,8.2a1.13,1.13,0,1,0,1.08-.07,18,18,0,0,0-.46-2.36h2.14a1.88,1.88,0,0,1,1.88,1.87Z"></path>
				</svg>
				<span class="header-cart-count"><?php echo esc_html( (string) $cart_count ); ?></span>
			</a>
			<button class="header-icon-button header-search-toggle" type="button" aria-expanded="false" aria-controls="site-header-search-1" aria-label="<?php esc_attr_e( 'Search', 'starterkit' ); ?>">
				<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
					<circle cx="11" cy="11" r="6.5"></circle>
					<path d="M16 16l5 5"></path>
				</svg>
			</button>
		</div>
	</div>
	<div id="site-header-search-1" class="header-search-panel" hidden>
		<div class="container">
			<?php get_search_form(); ?>
		</div>
	</div>
	<?php $zone_renderer->render( 'header_bottom', array( 'context' => 'master' ) ); ?>
</header>
