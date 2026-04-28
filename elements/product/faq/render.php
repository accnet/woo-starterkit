<?php
/**
 * FAQ element render template.
 *
 * @package StarterKit
 */

$title = isset( $settings['title'] ) ? (string) $settings['title'] : '';
$rows  = isset( $settings['items'] ) ? $settings['items'] : array();

if ( is_string( $rows ) ) {
	$rows = array_map(
		function( $row ) {
			list( $question, $answer ) = array_pad( array_map( 'trim', explode( '|', (string) $row, 2 ) ), 2, '' );

			return array(
				'question'        => $question,
				'answer'          => $answer,
				'open_by_default' => '0',
			);
		},
		preg_split( '/\r\n|\r|\n/', $rows ) ?: array()
	);
}
?>
	<div class="starterkit-element-card starterkit-element-card--faq">
		<div class="container starterkit-element-card__inner">
			<?php if ( '' !== $title ) : ?>
				<h3 class="starterkit-element-card__title"><?php echo esc_html( $title ); ?></h3>
			<?php endif; ?>
			<div class="starterkit-element-faq">
				<?php foreach ( (array) $rows as $row ) : ?>
					<?php
					$row = is_array( $row ) ? $row : array();

					$question = isset( $row['question'] ) ? trim( (string) $row['question'] ) : '';
					$answer   = isset( $row['answer'] ) ? trim( (string) $row['answer'] ) : '';
					$open     = ! empty( $row['open_by_default'] ) && '0' !== (string) $row['open_by_default'];

					if ( '' === $question && '' === $answer ) {
						continue;
					}
					?>
					<details class="starterkit-element-faq__item"<?php echo $open ? ' open' : ''; ?>>
						<summary><?php echo esc_html( $question ); ?></summary>
						<div><?php echo wp_kses_post( wpautop( $answer ) ); ?></div>
					</details>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
