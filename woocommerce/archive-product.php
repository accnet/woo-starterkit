<?php
/**
 * WooCommerce product archive template.
 *
 * @package StarterKit
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

$starterkit_settings       = get_option( 'starterkit_global_settings', array() );
$starterkit_archive_layout = isset( $starterkit_settings['archive_layout'] ) ? (string) $starterkit_settings['archive_layout'] : 'archive-layout-1';
$zone_renderer             = function_exists( 'starterkit' ) ? starterkit()->zone_renderer() : null;
?>
<main id="primary" class="site-main starterkit-woocommerce-archive">
	<div class="container">
		<?php do_action( 'woocommerce_before_main_content' ); ?>
		<?php if ( $zone_renderer ) : ?>
			<?php $zone_renderer->render( 'archive_before_title', array( 'context' => 'archive' ) ); ?>
		<?php endif; ?>
		<header class="woocommerce-products-header">
			<?php if ( apply_filters( 'woocommerce_show_page_title', true ) ) : ?>
				<h1 class="woocommerce-products-header__title page-title"><?php woocommerce_page_title(); ?></h1>
			<?php endif; ?>
		</header>
		<?php if ( $zone_renderer ) : ?>
			<?php $zone_renderer->render( 'archive_after_title', array( 'context' => 'archive' ) ); ?>
		<?php endif; ?>
		<?php if ( woocommerce_product_loop() ) : ?>
			<?php do_action( 'woocommerce_before_shop_loop' ); ?>
			<?php if ( 'archive-layout-1' === $starterkit_archive_layout ) : ?>
				<?php get_template_part( 'template-parts/archive/archive-layout-1/archive' ); ?>
			<?php else : ?>
				<?php include get_template_directory() . '/template-parts/archive/archive-layout-2.php'; ?>
			<?php endif; ?>
			<?php do_action( 'woocommerce_after_shop_loop' ); ?>
		<?php else : ?>
			<?php do_action( 'woocommerce_no_products_found' ); ?>
		<?php endif; ?>
		<?php do_action( 'woocommerce_after_main_content' ); ?>
	</div>
</main>
<?php
get_footer( 'shop' );
