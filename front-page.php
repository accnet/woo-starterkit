<?php
/**
 * Homepage template.
 *
 * @package StarterKit
 */

get_header();
?>
<main id="primary" class="site-main starterkit-homepage">
	<?php starterkit_render_slot( 'home_after_header' ); ?>
	<?php starterkit_render_slot( 'home_before_content' ); ?>
	<div class="starterkit-home-content">
		<div class="container">
			<?php
			while ( have_posts() ) :
				the_post();
				the_content();
			endwhile;
			?>
		</div>
	</div>
	<?php starterkit_render_slot( 'home_after_content' ); ?>
	<?php starterkit_render_slot( 'home_before_footer' ); ?>
</main>
<?php
get_footer();
