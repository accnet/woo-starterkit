<?php
/**
 * Trust badges section render template.
 *
 * @package StarterKit
 */

$items = preg_split( '/\r\n|\r|\n/', (string) $content );
$items = array_values(
	array_filter(
		array_map(
			function( $item ) {
				return trim( (string) $item );
			},
			is_array( $items ) ? $items : array()
		)
	)
);

if ( empty( $items ) ) {
	return;
}
?>
<section class="starterkit-section starterkit-section--trust-badges">
	<div class="container">
		<ul class="starterkit-section-trust-badges">
			<?php foreach ( $items as $item ) : ?>
				<li><?php echo esc_html( $item ); ?></li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
