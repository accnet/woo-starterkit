<?php
/**
 * Theme builder state repository.
 *
 * @package StarterKit
 */

namespace StarterKit\ThemeBuilder;

class BuilderStateRepository {
	const OPTION_KEY = 'starterkit_builder_state';

	/**
	 * Preset schema registry.
	 *
	 * @var PresetSchemaRegistry
	 */
	protected $preset_schema_registry;

	/**
	 * Element registry.
	 *
	 * @var ElementRegistry
	 */
	protected $element_registry;

	/**
	 * Constructor.
	 *
	 * @param PresetSchemaRegistry $preset_schema_registry Preset schema registry.
	 * @param ElementRegistry      $element_registry Element registry.
	 */
	public function __construct( PresetSchemaRegistry $preset_schema_registry, ElementRegistry $element_registry ) {
		$this->preset_schema_registry = $preset_schema_registry;
		$this->element_registry       = $element_registry;
	}

	/**
	 * Return normalized full state.
	 *
	 * @return array<string, mixed>
	 */
	public function all() {
		return $this->normalize_state( get_option( self::OPTION_KEY, array() ) );
	}

	/**
	 * Return a version hash for the current normalized state.
	 *
	 * @return string
	 */
	public function version() {
		return md5( wp_json_encode( $this->all() ) );
	}

	/**
	 * Return the preset state for a builder context and preset id.
	 *
	 * @param string $context Builder context.
	 * @param string $preset_id Preset id.
	 * @return array<string, array<int, array<string, mixed>>>
	 */
	public function get_preset_state( $context, $preset_id ) {
		$state = $this->all();

		return isset( $state[ $context ][ $preset_id ] ) && is_array( $state[ $context ][ $preset_id ] ) ? $state[ $context ][ $preset_id ] : array();
	}

	/**
	 * Return zone items for a preset.
	 *
	 * @param string $context Builder context.
	 * @param string $preset_id Preset id.
	 * @param string $zone_id Zone id.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_zone_items( $context, $preset_id, $zone_id ) {
		$preset_state = $this->get_preset_state( $context, $preset_id );

		return isset( $preset_state[ $zone_id ] ) && is_array( $preset_state[ $zone_id ] ) ? array_values( $preset_state[ $zone_id ] ) : array();
	}

	/**
	 * Save builder state.
	 *
	 * @param mixed $state Raw state.
	 * @return array<string, mixed>
	 */
	public function save_state( $state ) {
		$normalized = $this->normalize_state( $state );

		update_option( self::OPTION_KEY, $normalized, false );

		return $normalized;
	}

	/**
	 * Normalize state against schemas and element definitions.
	 *
	 * @param mixed $state Raw state.
	 * @return array<string, mixed>
	 */
	public function normalize_state( $state ) {
		$state  = is_array( $state ) ? $state : array();
		$output = array(
			BuilderContext::MASTER  => array(),
			BuilderContext::PRODUCT => array(),
			BuilderContext::ARCHIVE => array(),
		);

		foreach ( $this->preset_schema_registry->all() as $preset_id => $schema ) {
			$context = isset( $schema['context'] ) ? (string) $schema['context'] : '';
			$element_counts = array();

			if ( ! isset( $output[ $context ] ) ) {
				continue;
			}

			$raw_preset_state = isset( $state[ $context ][ $preset_id ] ) && is_array( $state[ $context ][ $preset_id ] ) ? $state[ $context ][ $preset_id ] : array();
			$output[ $context ][ $preset_id ] = array();

			foreach ( (array) $schema['zones'] as $zone ) {
				$zone_id    = isset( $zone['id'] ) ? (string) $zone['id'] : '';
				$raw_items  = isset( $raw_preset_state[ $zone_id ] ) && is_array( $raw_preset_state[ $zone_id ] ) ? $raw_preset_state[ $zone_id ] : array();
				$max_items  = isset( $zone['constraints']['max_items'] ) ? absint( $zone['constraints']['max_items'] ) : 12;
				$zone_items = array();

				foreach ( $raw_items as $item ) {
					$normalized_item = $this->normalize_item( $item, $context, $zone_id );

					if ( $normalized_item ) {
						$type          = isset( $normalized_item['type'] ) ? (string) $normalized_item['type'] : '';
						$max_instances = $this->get_element_max_instances( $type );

						if ( $max_instances > 0 ) {
							$current_count = isset( $element_counts[ $type ] ) ? (int) $element_counts[ $type ] : 0;

							if ( $current_count >= $max_instances ) {
								continue;
							}

							$element_counts[ $type ] = $current_count + 1;
						}

						$zone_items[] = $normalized_item;
					}

					if ( count( $zone_items ) >= $max_items ) {
						break;
					}
				}

				$output[ $context ][ $preset_id ][ $zone_id ] = $zone_items;
			}
		}

		return $output;
	}

	/**
	 * Normalize a single element instance.
	 *
	 * @param mixed  $item Raw item.
	 * @param string $context Builder context.
	 * @param string $zone_id Zone id.
	 * @return array<string, mixed>|null
	 */
	protected function normalize_item( $item, $context, $zone_id ) {
		if ( ! is_array( $item ) ) {
			return null;
		}

		$type       = isset( $item['type'] ) ? sanitize_key( (string) $item['type'] ) : '';
		$definition = $this->element_registry->get( $type );

		if ( ! $definition || ! in_array( $context, (array) $definition['contexts'], true ) || ! $this->element_registry->supports_zone( $type, $zone_id ) ) {
			return null;
		}

		$settings = isset( $item['settings'] ) && is_array( $item['settings'] ) ? $item['settings'] : array();
		$settings = $this->normalize_settings( $definition, $settings );

		return array(
			'id'       => isset( $item['id'] ) && '' !== (string) $item['id'] ? sanitize_key( (string) $item['id'] ) : uniqid( 'tb_', false ),
			'type'     => $type,
			'enabled'  => empty( $item['enabled'] ) ? false : true,
			'settings' => $settings,
		);
	}

	/**
	 * Normalize element settings using definition defaults and schema.
	 *
	 * @param array<string, mixed> $definition Element definition.
	 * @param array<string, mixed> $settings Raw settings.
	 * @return array<string, mixed>
	 */
	protected function normalize_settings( array $definition, array $settings ) {
		$normalized = isset( $definition['default_settings'] ) && is_array( $definition['default_settings'] ) ? $definition['default_settings'] : array();

		foreach ( (array) $definition['settings_schema'] as $control ) {
			$control_id = isset( $control['id'] ) ? (string) $control['id'] : '';

			if ( '' === $control_id ) {
				continue;
			}

			$value = isset( $settings[ $control_id ] ) ? $settings[ $control_id ] : ( isset( $normalized[ $control_id ] ) ? $normalized[ $control_id ] : '' );
			$type  = isset( $control['type'] ) ? (string) $control['type'] : 'text';

			switch ( $type ) {
				case 'textarea':
					$normalized[ $control_id ] = sanitize_textarea_field( (string) $value );
					break;
				case 'select':
					$allowed = array();

					foreach ( (array) $control['options'] as $option ) {
						if ( isset( $option['value'] ) ) {
							$allowed[] = (string) $option['value'];
						}
					}

					$value = sanitize_text_field( (string) $value );
					$normalized[ $control_id ] = in_array( $value, $allowed, true ) ? $value : ( isset( $normalized[ $control_id ] ) ? $normalized[ $control_id ] : '' );
					break;
				case 'datetime-local':
					$normalized[ $control_id ] = sanitize_text_field( (string) $value );
					break;
				default:
					$normalized[ $control_id ] = sanitize_text_field( (string) $value );
					break;
			}
		}

		return $normalized;
	}

	/**
	 * Return the max allowed instances for an element within a preset state.
	 *
	 * @param string $element_id Element id.
	 * @return int
	 */
	protected function get_element_max_instances( $element_id ) {
		$definition = $this->element_registry->get( $element_id );

		if ( empty( $definition['max_instances'] ) ) {
			return 0;
		}

		return max( 0, absint( $definition['max_instances'] ) );
	}
}
