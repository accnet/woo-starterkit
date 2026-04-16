<?php
/**
 * Features section template.
 *
 * @var array<string, mixed> $content
 * @var array<string, mixed> $style
 *
 * @package StarterKit
 */

$items = isset( $content['items'] ) && is_array( $content['items'] ) ? $content['items'] : array();
$columns = ! empty( $style['columns'] ) ? (int) $style['columns'] : 2;
?>
<section class="starterkit-section starterkit-section--features">
	<div class="container">
		<h2><?php echo esc_html( $content['heading'] ?? '' ); ?></h2>
		<p class="section-intro"><?php echo esc_html( $content['intro'] ?? '' ); ?></p>
		<div class="feature-grid columns-<?php echo esc_attr( (string) $columns ); ?>">
			<?php foreach ( $items as $item ) : ?>
				<article class="feature-card">
					<?php if ( ! empty( $item['icon'] ) ) : ?>
						<p class="feature-icon"><?php echo esc_html( $item['icon'] ); ?></p>
					<?php endif; ?>
					<h3><?php echo esc_html( $item['title'] ?? '' ); ?></h3>
					<p><?php echo esc_html( $item['description'] ?? '' ); ?></p>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
