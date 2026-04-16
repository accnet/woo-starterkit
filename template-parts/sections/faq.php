<?php
/**
 * FAQ section template.
 *
 * @var array<string, mixed> $content
 *
 * @package StarterKit
 */

$items = isset( $content['items'] ) && is_array( $content['items'] ) ? $content['items'] : array();
?>
<section class="starterkit-section starterkit-section--faq">
	<div class="container">
		<h2><?php echo esc_html( $content['heading'] ?? '' ); ?></h2>
		<div class="faq-list">
			<?php foreach ( $items as $item ) : ?>
				<details class="faq-item">
					<summary><?php echo esc_html( $item['question'] ?? '' ); ?></summary>
					<p><?php echo esc_html( $item['answer'] ?? '' ); ?></p>
				</details>
			<?php endforeach; ?>
		</div>
	</div>
</section>
