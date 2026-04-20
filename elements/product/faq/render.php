<?php
/**
 * FAQ element render template.
 *
 * @package StarterKit
 */

$title = isset( $settings['title'] ) ? (string) $settings['title'] : '';
$rows  = isset( $settings['items'] ) ? preg_split( '/\r\n|\r|\n/', (string) $settings['items'] ) : array();
?>
<div class="starterkit-element-card starterkit-element-card--faq">
	<div class="container starterkit-element-card__inner">
		<?php if ( '' !== $title ) : ?>
			<strong class="starterkit-element-card__title"><?php echo esc_html( $title ); ?></strong>
		<?php endif; ?>
		<div class="starterkit-element-faq">
			<?php foreach ( (array) $rows as $row ) : ?>
				<?php
				$row = trim( (string) $row );

				if ( '' === $row ) {
					continue;
				}

				list( $question, $answer ) = array_pad( array_map( 'trim', explode( '|', $row, 2 ) ), 2, '' );
				?>
				<details class="starterkit-element-faq__item">
					<summary><?php echo esc_html( $question ); ?></summary>
					<div><?php echo esc_html( $answer ); ?></div>
				</details>
			<?php endforeach; ?>
		</div>
	</div>
</div>
