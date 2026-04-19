<?php
/**
 * Zone renderer for preset-controlled builder areas.
 *
 * @package StarterKit
 */

namespace StarterKit\ThemeBuilder;

class ZoneRenderer {
	/**
	 * Preset schema registry.
	 *
	 * @var PresetSchemaRegistry
	 */
	protected $preset_schema_registry;

	/**
	 * Builder state repository.
	 *
	 * @var BuilderStateRepository
	 */
	protected $state_repository;

	/**
	 * Element renderer.
	 *
	 * @var ElementRenderer
	 */
	protected $element_renderer;

	/**
	 * Builder mode.
	 *
	 * @var BuilderMode
	 */
	protected $builder_mode;

	/**
	 * Constructor.
	 *
	 * @param PresetSchemaRegistry  $preset_schema_registry Preset schema registry.
	 * @param BuilderStateRepository $state_repository State repository.
	 * @param ElementRenderer        $element_renderer Element renderer.
	 * @param BuilderMode            $builder_mode Builder mode.
	 */
	public function __construct( PresetSchemaRegistry $preset_schema_registry, BuilderStateRepository $state_repository, ElementRenderer $element_renderer, BuilderMode $builder_mode ) {
		$this->preset_schema_registry = $preset_schema_registry;
		$this->state_repository       = $state_repository;
		$this->element_renderer       = $element_renderer;
		$this->builder_mode           = $builder_mode;
	}

	/**
	 * Render a zone by id.
	 *
	 * @param string               $zone_id Zone id.
	 * @param array<string, mixed> $args Optional args.
	 * @return void
	 */
	public function render( $zone_id, array $args = array() ) {
		echo $this->get_markup( $zone_id, $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Build zone markup as a string.
	 *
	 * @param string               $zone_id Zone id.
	 * @param array<string, mixed> $args Optional args.
	 * @return string
	 */
	public function get_markup( $zone_id, array $args = array() ) {
		$context = isset( $args['context'] ) ? (string) $args['context'] : $this->infer_context_from_zone( $zone_id );
		$zone    = $this->preset_schema_registry->find_active_zone( $context, $zone_id );

		if ( ! $zone ) {
			return '';
		}

		$preset_id = isset( $zone['preset_id'] ) ? (string) $zone['preset_id'] : '';
		$items     = isset( $args['items'] ) && is_array( $args['items'] ) ? array_values( $args['items'] ) : $this->state_repository->get_zone_items( $context, $preset_id, $zone_id );
		$is_builder_mode = array_key_exists( 'builder_mode', $args ) ? (bool) $args['builder_mode'] : $this->builder_mode->is_builder_mode();
		$classes   = array( 'slot', 'starterkit-builder-zone', 'starterkit-builder-zone--' . sanitize_html_class( $zone_id ) );

		if ( empty( $items ) ) {
			$classes[] = 'starterkit-builder-zone--empty';
		}

		ob_start();
		?>
		<div
			class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
			data-builder-zone="<?php echo esc_attr( $zone_id ); ?>"
			data-builder-zone-label="<?php echo esc_attr( isset( $zone['label'] ) ? (string) $zone['label'] : $zone_id ); ?>"
			data-builder-zone-context="<?php echo esc_attr( $context ); ?>"
			data-builder-zone-preset="<?php echo esc_attr( $preset_id ); ?>"
			data-builder-zone-droppable="<?php echo ! empty( $zone['droppable'] ) ? '1' : '0'; ?>"
			data-builder-zone-sortable="<?php echo ! empty( $zone['sortable'] ) ? '1' : '0'; ?>"
		>
			<?php foreach ( $items as $item ) : ?>
				<?php echo $this->element_renderer->render( $item, $zone_id, $context, $is_builder_mode ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endforeach; ?>
			<?php if ( $is_builder_mode && empty( $items ) ) : ?>
				<div class="starterkit-builder-zone__placeholder">
					<strong><?php echo esc_html( isset( $zone['label'] ) ? (string) $zone['label'] : $zone_id ); ?></strong>
					<span><?php esc_html_e( 'Drop or add elements here from the builder panel.', 'starterkit' ); ?></span>
				</div>
			<?php endif; ?>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Infer context from zone id.
	 *
	 * @param string $zone_id Zone id.
	 * @return string
	 */
	protected function infer_context_from_zone( $zone_id ) {
		if ( 0 === strpos( $zone_id, 'product_' ) ) {
			return BuilderContext::PRODUCT;
		}

		if ( 0 === strpos( $zone_id, 'archive_' ) ) {
			return BuilderContext::ARCHIVE;
		}

		return BuilderContext::MASTER;
	}
}
