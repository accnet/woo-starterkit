<?php
/**
 * Main fallback template.
 *
 * @package StarterKit
 */

get_header();
?>
<main id="primary" class="site-main container">
	<?php if ( have_posts() ) : ?>
		<?php while ( have_posts() ) : the_post(); ?>
			<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
				<?php the_title( '<h1>', '</h1>' ); ?>
				<div class="entry-content">
					<?php the_content(); ?>
				</div>
			</article>
		<?php endwhile; ?>
	<?php endif; ?>
</main>
<?php
get_footer();
