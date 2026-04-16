<?php
/**
 * Benefits section template.
 *
 * @var array<string, mixed> $content
 * @var array<string, mixed> $style
 *
 * @package StarterKit
 */

$items = isset( $content['items'] ) && is_array( $content['items'] ) ? $content['items'] : array();
$emphasis = ! empty( $style['emphasis'] ) ? $style['emphasis'] : 'soft';
?>
<section class="starterkit-section starterkit-section--benefits emphasis-<?php echo esc_attr( $emphasis ); ?>">
	<div class="container">
		<h2><?php echo esc_html( $content['heading'] ?? '' ); ?></h2>
		<div class="benefit-list">
			<?php foreach ( $items as $item ) : ?>
				<article class="benefit-item">
					<h3><?php echo esc_html( $item['title'] ?? '' ); ?></h3>
					<p><?php echo esc_html( $item['description'] ?? '' ); ?></p>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
