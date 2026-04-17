<?php
/**
 * Homepage template.
 *
 * @package StarterKit
 */

get_header();
?>
<main id="primary" class="site-main starterkit-homepage">
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
</main>
<?php
get_footer();
