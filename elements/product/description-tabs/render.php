<?php
/**
 * Description tabs element render template.
 *
 * @package StarterKit
 */

$title             = isset( $settings['title'] ) ? trim( (string) $settings['title'] ) : '';
$description_label = isset( $settings['description_label'] ) ? trim( (string) $settings['description_label'] ) : __( 'Description', 'starterkit' );
$rows              = isset( $settings['items'] ) && is_array( $settings['items'] ) ? $settings['items'] : array();
$description_html  = '';
$tabs              = array();

if ( function_exists( 'wc_get_product' ) ) {
	global $product;

	$description_product = $product instanceof \WC_Product ? $product : wc_get_product( get_the_ID() );

	if ( $description_product instanceof \WC_Product ) {
		$raw_description = (string) $description_product->get_description();

		if ( '' !== trim( wp_strip_all_tags( $raw_description ) ) ) {
			$description_html = apply_filters( 'the_content', $raw_description );
		}
	}
}

if ( '' !== $description_html ) {
	$tabs[] = array(
		'label'   => '' !== $description_label ? $description_label : __( 'Description', 'starterkit' ),
		'content' => $description_html,
	);
}

foreach ( $rows as $row ) {
	$row = is_array( $row ) ? $row : array();

	$label   = isset( $row['label'] ) ? trim( (string) $row['label'] ) : '';
	$content = isset( $row['content'] ) ? trim( (string) $row['content'] ) : '';

	if ( '' === $label || '' === $content ) {
		continue;
	}

	$tabs[] = array(
		'label'   => $label,
		'content' => wpautop( $content ),
	);
}

if ( empty( $tabs ) ) {
	return;
}

$instance_id = function_exists( 'wp_unique_id' ) ? wp_unique_id( 'starterkit-description-tabs-' ) : 'starterkit-description-tabs-' . wp_rand();
?>
<div class="starterkit-element-card starterkit-element-card--description-tabs">
	<div class="container starterkit-element-card__inner">
		<?php if ( '' !== $title ) : ?>
			<h3 class="starterkit-element-card__title"><?php echo esc_html( $title ); ?></h3>
		<?php endif; ?>
		<div class="starterkit-description-tabs" data-starterkit-tabs>
			<div class="starterkit-description-tabs__nav" role="tablist" aria-label="<?php echo esc_attr( $title ? $title : __( 'Product details', 'starterkit' ) ); ?>">
				<?php foreach ( $tabs as $index => $tab ) : ?>
					<?php
					$tab_id      = $instance_id . '-tab-' . $index;
					$panel_id    = $instance_id . '-panel-' . $index;
					$is_selected = 0 === $index;
					?>
					<button
						type="button"
						id="<?php echo esc_attr( $tab_id ); ?>"
						class="starterkit-description-tabs__tab<?php echo $is_selected ? ' is-active' : ''; ?>"
						role="tab"
						aria-selected="<?php echo $is_selected ? 'true' : 'false'; ?>"
						aria-controls="<?php echo esc_attr( $panel_id ); ?>"
						tabindex="<?php echo $is_selected ? '0' : '-1'; ?>"
						data-starterkit-tab
					>
						<?php echo esc_html( $tab['label'] ); ?>
					</button>
				<?php endforeach; ?>
			</div>
			<div class="starterkit-description-tabs__panels">
				<?php foreach ( $tabs as $index => $tab ) : ?>
					<?php
					$tab_id      = $instance_id . '-tab-' . $index;
					$panel_id    = $instance_id . '-panel-' . $index;
					$is_selected = 0 === $index;
					?>
					<div
						id="<?php echo esc_attr( $panel_id ); ?>"
						class="starterkit-description-tabs__panel starterkit-element-card__content<?php echo $is_selected ? ' is-active' : ''; ?>"
						role="tabpanel"
						aria-labelledby="<?php echo esc_attr( $tab_id ); ?>"
						<?php echo $is_selected ? '' : 'hidden'; ?>
						data-starterkit-tab-panel
					>
						<?php echo wp_kses_post( $tab['content'] ); ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</div>
