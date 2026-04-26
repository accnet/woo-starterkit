<?php
/**
 * Conditional asset loader for filesystem element modules.
 *
 * @package StarterKit
 */

namespace StarterKit\ThemeBuilder;

use StarterKit\Core\AssetVersion;

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
	 * Preset schema registry.
	 *
	 * @var PresetSchemaRegistry
	 */
	protected $preset_schema_registry;

	/**
	 * Page context resolver.
	 *
	 * @var \StarterKit\Rules\PageContextResolver
	 */
	protected $context_resolver;

	/**
	 * Request-local used element types cache.
	 *
	 * @var array<string, string[]>
	 */
	protected $used_types_cache = array();

	/**
	 * Constructor.
	 *
	 * @param ElementRegistry                         $element_registry Element registry.
	 * @param BuilderStateRepository                  $state_repository State repository.
	 * @param PresetSchemaRegistry                    $preset_schema_registry Preset schema registry.
	 * @param \StarterKit\Rules\PageContextResolver   $context_resolver Page context resolver.
	 */
	public function __construct( ElementRegistry $element_registry, BuilderStateRepository $state_repository, PresetSchemaRegistry $preset_schema_registry, \StarterKit\Rules\PageContextResolver $context_resolver ) {
		$this->element_registry       = $element_registry;
		$this->state_repository       = $state_repository;
		$this->preset_schema_registry = $preset_schema_registry;
		$this->context_resolver       = $context_resolver;

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
					AssetVersion::for_file( $asset['path'] )
				);
			}

			foreach ( $assets['js'] as $index => $asset ) {
				wp_enqueue_script(
					'starterkit-element-' . sanitize_key( $element_type ) . '-' . $index,
					$asset['uri'],
					array(),
					AssetVersion::for_file( $asset['path'] ),
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
		$cache_key = $this->get_used_types_cache_key();

		if ( isset( $this->used_types_cache[ $cache_key ] ) ) {
			return $this->used_types_cache[ $cache_key ];
		}

		$cached = wp_cache_get( $cache_key, 'starterkit_active_element_types' );

		if ( false !== $cached && is_array( $cached ) ) {
			$this->used_types_cache[ $cache_key ] = $cached;

			return $cached;
		}

		$types = array();
		$state = $this->state_repository->all();

		foreach ( $this->get_relevant_builder_contexts() as $context ) {
			foreach ( $this->preset_schema_registry->get_active_schemas( $context ) as $preset_id => $schema ) {
				if ( empty( $state[ $context ][ $preset_id ] ) || ! is_array( $state[ $context ][ $preset_id ] ) ) {
					continue;
				}

				$preset_state = $state[ $context ][ $preset_id ];

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

		$types = array_values( array_unique( array_filter( $types ) ) );

		$this->used_types_cache[ $cache_key ] = $types;
		wp_cache_set( $cache_key, $types, 'starterkit_active_element_types' );

		return $types;
	}

	/**
	 * Return builder contexts relevant to the current frontend request.
	 *
	 * @return string[]
	 */
	protected function get_relevant_builder_contexts() {
		$page_context = $this->context_resolver->resolve();
		$contexts     = array( BuilderContext::MASTER );

		if ( ! empty( $page_context['is_product'] ) ) {
			$contexts[] = BuilderContext::PRODUCT;
		}

		if ( ! empty( $page_context['is_product_archive'] ) ) {
			$contexts[] = BuilderContext::ARCHIVE;
		}

		return array_values( array_unique( $contexts ) );
	}

	/**
	 * Build a cache key for active element type resolution.
	 *
	 * @return string
	 */
	protected function get_used_types_cache_key() {
		$page_context = $this->context_resolver->resolve();
		$contexts     = $this->get_relevant_builder_contexts();
		$presets      = array();

		foreach ( $contexts as $context ) {
			$presets[ $context ] = array_keys( $this->preset_schema_registry->get_active_schemas( $context ) );
		}

		return md5(
			wp_json_encode(
				array(
					'theme_version'      => wp_get_theme()->get( 'Version' ),
					'state_version'      => $this->state_repository->version(),
					'contexts'           => $contexts,
					'active_preset_ids'  => $presets,
					'current_page_id'    => isset( $page_context['current_page_id'] ) ? (int) $page_context['current_page_id'] : 0,
					'current_product_id' => isset( $page_context['current_product_id'] ) ? (int) $page_context['current_product_id'] : 0,
					'current_term_id'    => isset( $page_context['current_term_id'] ) ? (int) $page_context['current_term_id'] : 0,
				)
			)
		);
	}
}
