<?php
/**
 * Testimonials section template.
 *
 * @var array<string, mixed> $content
 *
 * @package StarterKit
 */

$items = isset( $content['items'] ) && is_array( $content['items'] ) ? $content['items'] : array();
?>
<section class="starterkit-section starterkit-section--testimonials">
	<div class="container">
		<h2><?php echo esc_html( $content['heading'] ?? '' ); ?></h2>
		<div class="testimonial-grid">
			<?php foreach ( $items as $item ) : ?>
				<article class="testimonial-card">
					<p class="testimonial-quote">"<?php echo esc_html( $item['quote'] ?? '' ); ?>"</p>
					<p class="testimonial-author"><?php echo esc_html( $item['author'] ?? '' ); ?></p>
					<?php if ( ! empty( $item['role'] ) ) : ?>
						<p class="testimonial-role"><?php echo esc_html( $item['role'] ); ?></p>
					<?php endif; ?>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
