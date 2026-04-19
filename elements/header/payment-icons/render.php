<?php
/**
 * Payment icons element render template.
 *
 * @package StarterKit
 */

$title = isset( $settings['title'] ) ? (string) $settings['title'] : '';
$items = isset( $settings['items'] ) ? preg_split( '/\r\n|\r|\n/', (string) $settings['items'] ) : array();
$items = array_filter( array_map( 'trim', (array) $items ) );
?>
<div class="starterkit-builder-card starterkit-builder-card--list">
	<div class="container starterkit-builder-card__inner">
		<?php if ( '' !== $title ) : ?>
			<strong class="starterkit-builder-card__title"><?php echo esc_html( $title ); ?></strong>
		<?php endif; ?>
		<?php if ( ! empty( $items ) ) : ?>
			<ul class="starterkit-builder-list">
				<?php foreach ( $items as $item ) : ?>
					<li><?php echo esc_html( $item ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
</div>
