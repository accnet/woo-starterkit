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
$starterkit_use_archive_layout_1 = 'archive-layout-1' === $starterkit_archive_layout;
?>
<main id="primary" class="site-main starterkit-woocommerce-archive">
	<div class="container">
		<?php do_action( 'woocommerce_before_main_content' ); ?>
		<header class="woocommerce-products-header">
			<?php starterkit_render_slot( 'archive_before_title' ); ?>
			<?php if ( apply_filters( 'woocommerce_show_page_title', true ) ) : ?>
				<h1 class="woocommerce-products-header__title page-title"><?php woocommerce_page_title(); ?></h1>
			<?php endif; ?>
			<?php starterkit_render_slot( 'archive_after_title' ); ?>
		</header>
		<?php if ( woocommerce_product_loop() ) : ?>
			<?php do_action( 'woocommerce_before_shop_loop' ); ?>
			<?php if ( $starterkit_use_archive_layout_1 ) : ?>
				<?php get_template_part( 'template-parts/archive/archive-layout-1/archive' ); ?>
			<?php else : ?>
				<?php woocommerce_product_loop_start(); ?>
				<?php while ( have_posts() ) : the_post(); ?>
					<?php wc_get_template_part( 'content', 'product' ); ?>
				<?php endwhile; ?>
				<?php woocommerce_product_loop_end(); ?>
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
