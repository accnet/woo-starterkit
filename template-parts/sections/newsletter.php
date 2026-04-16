<?php
/**
 * Newsletter section template.
 *
 * @var array<string, mixed> $content
 *
 * @package StarterKit
 */
?>
<section class="starterkit-section starterkit-section--newsletter">
	<div class="container newsletter-panel">
		<div>
			<h2><?php echo esc_html( $content['heading'] ?? '' ); ?></h2>
			<p><?php echo esc_html( $content['content'] ?? '' ); ?></p>
		</div>
		<form class="newsletter-form" action="#" method="post">
			<input type="email" placeholder="<?php echo esc_attr( $content['placeholder'] ?? '' ); ?>">
			<button type="submit" class="button button-primary"><?php echo esc_html( $content['button_text'] ?? '' ); ?></button>
		</form>
	</div>
</section>
