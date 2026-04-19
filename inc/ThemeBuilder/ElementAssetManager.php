<?php
/**
 * Conditional asset loader for filesystem element modules.
 *
 * @package StarterKit
 */

namespace StarterKit\ThemeBuilder;

class ElementAssetManager {
	/**
	 * Element registry.
	 *
	 * @var ElementRegistry
	 */
	protected $element_registry;

	/**
	 * Builder state repository.
	 *
	 * @var BuilderStateRepository
	 */
	protected $state_repository;

	/**
	 * Constructor.
	 *
	 * @param ElementRegistry        $element_registry Element registry.
	 * @param BuilderStateRepository $state_repository State repository.
	 */
	public function __construct( ElementRegistry $element_registry, BuilderStateRepository $state_repository ) {
		$this->element_registry = $element_registry;
		$this->state_repository = $state_repository;

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_active_element_assets' ), 30 );
	}

	/**
	 * Enqueue assets for element types present in normalized builder state.
	 *
	 * @return void
	 */
	public function enqueue_active_element_assets() {
		foreach ( $this->get_used_element_types() as $element_type ) {
			$definition = $this->element_registry->get( $element_type );

			if ( ! $definition ) {
				continue;
			}

			$assets = $this->element_registry->get_assets( $definition );

			foreach ( $assets['css'] as $index => $asset ) {
				wp_enqueue_style(
					'starterkit-element-' . sanitize_key( $element_type ) . '-' . $index,
					$asset['uri'],
					array( 'starterkit-theme' ),
					(string) filemtime( $asset['path'] )
				);
			}

			foreach ( $assets['js'] as $index => $asset ) {
				wp_enqueue_script(
					'starterkit-element-' . sanitize_key( $element_type ) . '-' . $index,
					$asset['uri'],
					array(),
					(string) filemtime( $asset['path'] ),
					true
				);
			}
		}
	}

	/**
	 * Return unique element types used by current builder state.
	 *
	 * @return string[]
	 */
	protected function get_used_element_types() {
		$types = array();

		foreach ( $this->state_repository->all() as $context_state ) {
			if ( ! is_array( $context_state ) ) {
				continue;
			}

			foreach ( $context_state as $preset_state ) {
				if ( ! is_array( $preset_state ) ) {
					continue;
				}

				foreach ( $preset_state as $zone_items ) {
					if ( ! is_array( $zone_items ) ) {
						continue;
					}

					foreach ( $zone_items as $item ) {
						if ( ! empty( $item['type'] ) ) {
							$types[] = sanitize_key( (string) $item['type'] );
						}
					}
				}
			}
		}

		return array_values( array_unique( array_filter( $types ) ) );
	}
}
