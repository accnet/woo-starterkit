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
$icon_registry = starterkit()->icon_registry();
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
					<?php echo $icon_registry->render( 'ui:close', array( 'class' => 'site-header__close-icon' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
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
				<?php echo $icon_registry->render( 'ui:menu', array( 'class' => 'site-header__toggle-icon' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span class="screen-reader-text"><?php esc_html_e( 'Menu', 'starterkit' ); ?></span>
			</button>
			<a class="header-icon-button header-cart-link" href="<?php echo esc_url( $cart_url ); ?>" aria-label="<?php esc_attr_e( 'Cart', 'starterkit' ); ?>">
				<?php echo $icon_registry->render( 'ecommerce:shopping-bag', array( 'class' => 'header-icon-svg' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span class="header-cart-count"><?php echo esc_html( (string) $cart_count ); ?></span>
			</a>
			<button class="header-icon-button header-search-toggle" type="button" aria-expanded="false" aria-controls="site-header-search-1" aria-label="<?php esc_attr_e( 'Search', 'starterkit' ); ?>">
				<?php echo $icon_registry->render( 'ui:search', array( 'class' => 'header-icon-svg' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
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
