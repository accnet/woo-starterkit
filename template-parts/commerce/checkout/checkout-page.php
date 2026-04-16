<?php
/**
 * Custom checkout page layout — Shopify-style.
 *
 * @package StarterKit
 */

defined( 'ABSPATH' ) || exit;

$logo_id   = (int) starterkit()->settings_manager()->get( 'logo_id', 0 );
$logo_html = '';

if ( $logo_id ) {
	$logo_html = wp_get_attachment_image( $logo_id, 'medium', false, array( 'class' => 'starterkit-checkout__brand-logo' ) );
}
?>
<div class="starterkit-checkout-app">
	<header class="starterkit-checkout-app__header">
		<div class="starterkit-checkout-app__header-shell">
			<div class="starterkit-checkout-app__header-main">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="starterkit-checkout-app__brand" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
					<?php if ( $logo_html ) : ?>
						<?php echo wp_kses_post( $logo_html ); ?>
					<?php else : ?>
						<span class="starterkit-checkout-app__brand-text"><?php bloginfo( 'name' ); ?></span>
					<?php endif; ?>
				</a>

				<nav class="starterkit-checkout-app__steps" aria-label="<?php esc_attr_e( 'Checkout progress', 'starterkit' ); ?>">
					<a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="starterkit-checkout-app__step"><?php esc_html_e( 'Cart', 'starterkit' ); ?></a>
					<span class="starterkit-checkout-app__separator">/</span>
					<span class="starterkit-checkout-app__step starterkit-checkout-app__step--active"><?php esc_html_e( 'Checkout', 'starterkit' ); ?></span>
				</nav>
			</div>
		</div>
	</header>

	<div class="starterkit-checkout-app__shell">
		<div class="starterkit-checkout-app__grid">
			<section class="starterkit-checkout-app__main">
				<div class="starterkit-checkout-app__main-inner">
					<div class="starterkit-checkout-app__notice" data-checkout-notice></div>
					<div id="starterkit-checkout-form-root" class="starterkit-checkout-app__form-root">
						<div class="starterkit-checkout-app__loading"><?php esc_html_e( 'Preparing checkout...', 'starterkit' ); ?></div>
					</div>
				</div>
			</section>

			<aside class="starterkit-checkout-app__sidebar">
				<div class="starterkit-checkout-app__sidebar-inner" id="starterkit-checkout-summary-root">
					<div class="starterkit-checkout-app__loading"><?php esc_html_e( 'Preparing checkout...', 'starterkit' ); ?></div>
				</div>
			</aside>
		</div>
	</div>
</div>
