<?php
/**
 * Header preset 2.
 *
 * @package StarterKit
 */
?>
<header class="site-header site-header--preset-2">
	<div class="header-topbar">
		<div class="container">
			<?php starterkit_render_slot( 'header_top' ); ?>
			<span><?php esc_html_e( 'Preset-driven commerce theme', 'starterkit' ); ?></span>
		</div>
	</div>
	<div class="container header-shell">
		<div class="site-branding">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-title"><?php bloginfo( 'name' ); ?></a>
		</div>
		<nav class="site-navigation">
			<?php wp_nav_menu( array( 'theme_location' => 'primary', 'fallback_cb' => false ) ); ?>
		</nav>
	</div>
	<?php starterkit_render_slot( 'header_bottom' ); ?>
</header>
