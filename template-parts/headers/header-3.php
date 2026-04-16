<?php
/**
 * Header preset 3.
 *
 * @package StarterKit
 */
?>
<header class="site-header site-header--preset-3">
	<?php starterkit_render_slot( 'header_top' ); ?>
	<div class="container header-shell header-shell--split">
		<nav class="site-navigation">
			<?php wp_nav_menu( array( 'theme_location' => 'primary', 'fallback_cb' => false ) ); ?>
		</nav>
		<div class="site-branding">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-title"><?php bloginfo( 'name' ); ?></a>
		</div>
		<div class="header-actions">
			<a href="<?php echo esc_url( home_url( '/shop/' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Shop', 'starterkit' ); ?></a>
		</div>
	</div>
	<?php starterkit_render_slot( 'header_bottom' ); ?>
</header>
