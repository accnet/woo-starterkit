<?php
/**
 * Main fallback template.
 *
 * @package StarterKit
 */

$is_checkout_page = function_exists( 'is_checkout' ) && is_checkout();

if ( $is_checkout_page ) :
	?>
	<!doctype html>
	<html <?php language_attributes(); ?>>
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<?php wp_head(); ?>
	</head>
	<body <?php body_class( 'starterkit-checkout-chrome-less' ); ?>>
	<?php wp_body_open(); ?>
	<main id="primary" class="site-main starterkit-woocommerce-checkout">
		<?php if ( have_posts() ) : ?>
			<?php while ( have_posts() ) : the_post(); ?>
				<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
					<div class="entry-content">
						<?php the_content(); ?>
					</div>
				</article>
			<?php endwhile; ?>
		<?php endif; ?>
	</main>
	<?php wp_footer(); ?>
	</body>
	</html>
	<?php
	return;
endif;

get_header();
?>
<main id="primary" class="site-main container">
	<?php if ( have_posts() ) : ?>
		<?php while ( have_posts() ) : the_post(); ?>
			<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
				<?php if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) : ?>
					<?php the_title( '<h1>', '</h1>' ); ?>
				<?php endif; ?>
				<div class="entry-content">
					<?php the_content(); ?>
				</div>
			</article>
		<?php endwhile; ?>
	<?php endif; ?>
</main>
<?php
get_footer();
