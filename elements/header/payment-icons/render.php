<?php
/**
 * Payment icons element render template.
 *
 * @package StarterKit
 */

$title = isset( $settings['title'] ) ? (string) $settings['title'] : '';
$items = isset( $settings['items'] ) ? $settings['items'] : array();

if ( is_string( $items ) ) {
	$items = preg_split( '/\r\n|\r|\n/', $items );
}

$items = array_values(
	array_filter(
		array_map(
			function( $item ) {
				return is_array( $item ) && isset( $item['text'] ) ? trim( (string) $item['text'] ) : trim( (string) $item );
			},
			(array) $items
		)
	)
);
?>
<div class="starterkit-element-card starterkit-element-card--list">
	<div class="container starterkit-element-card__inner">
		<?php if ( '' !== $title ) : ?>
			<strong class="starterkit-element-card__title"><?php echo esc_html( $title ); ?></strong>
		<?php endif; ?>
		<?php if ( ! empty( $items ) ) : ?>
			<ul class="starterkit-element-list">
				<?php foreach ( $items as $item ) : ?>
					<li><?php echo esc_html( $item ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
</div>
