<?php
/**
 * Element rendering gateway.
 *
 * @package StarterKit
 */

namespace StarterKit\ThemeBuilder;

class ElementRenderer {
	/**
	 * Element registry.
	 *
	 * @var ElementRegistry
	 */
	protected $element_registry;

	/**
	 * Builder mode.
	 *
	 * @var BuilderMode
	 */
	protected $builder_mode;

	/**
	 * Constructor.
	 *
	 * @param ElementRegistry $element_registry Element registry.
	 * @param BuilderMode     $builder_mode Builder mode.
	 */
	public function __construct( ElementRegistry $element_registry, BuilderMode $builder_mode ) {
		$this->element_registry = $element_registry;
		$this->builder_mode     = $builder_mode;
	}

	/**
	 * Render a builder element instance.
	 *
	 * @param array<string, mixed> $instance Element instance.
	 * @param string               $zone_id Zone id.
	 * @param string               $context Builder context.
	 * @param bool|null            $is_builder_mode Whether to force builder mode rendering.
	 * @return string
	 */
	public function render( array $instance, $zone_id, $context, $is_builder_mode = null ) {
		if ( empty( $instance['enabled'] ) ) {
			return '';
		}

		$definition = $this->element_registry->get( isset( $instance['type'] ) ? (string) $instance['type'] : '' );

		if ( ! $definition || ! $this->element_registry->supports_zone( (string) $instance['type'], $zone_id ) ) {
			return '';
		}

		$is_builder_mode = is_bool( $is_builder_mode ) ? $is_builder_mode : $this->builder_mode->is_builder_mode();
		$instance['__builder_mode'] = $is_builder_mode ? '1' : '0';
		$content = $this->element_registry->render( $instance, $zone_id, $context );

		if ( '' === $content ) {
			return '';
		}

		$classes         = array(
			'starterkit-element-wrapper',
			'starterkit-element-wrapper--' . sanitize_html_class( (string) $instance['type'] ),
		);

		if ( $is_builder_mode ) {
			$classes[] = 'starterkit-builder-element';
		}

		ob_start();
		?>
		<div
			class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
			<?php if ( $is_builder_mode ) : ?>
				data-builder-element-id="<?php echo esc_attr( (string) $instance['id'] ); ?>"
				data-builder-element-type="<?php echo esc_attr( (string) $instance['type'] ); ?>"
			<?php endif; ?>
		>
			<?php if ( $is_builder_mode ) : ?>
				<div class="starterkit-builder-element__toolbar" aria-hidden="false">
					<button
						type="button"
						class="starterkit-builder-element__move"
						draggable="true"
						data-builder-move-element="<?php echo esc_attr( (string) $instance['id'] ); ?>"
						aria-label="<?php esc_attr_e( 'Move element', 'starterkit' ); ?>"
					>
						↕
					</button>
					<button
						type="button"
						class="starterkit-builder-element__delete"
						data-builder-delete-element="<?php echo esc_attr( (string) $instance['id'] ); ?>"
						aria-label="<?php esc_attr_e( 'Remove element', 'starterkit' ); ?>"
					>
						&times;
					</button>
				</div>
			<?php endif; ?>
			<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
		<?php

		return (string) ob_get_clean();
	}
}
