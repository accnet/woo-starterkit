<?php
/**
 * Filesystem-backed theme builder element registry.
 *
 * @package StarterKit
 */

namespace StarterKit\ThemeBuilder;

class ElementRegistry {
	/**
	 * Base elements directory.
	 *
	 * @var string
	 */
	protected $base_path;

	/**
	 * Base elements URI.
	 *
	 * @var string
	 */
	protected $base_uri;

	/**
	 * Loaded definitions cache.
	 *
	 * @var array<string, array<string, mixed>>|null
	 */
	protected $definitions;

	/**
	 * Constructor.
	 *
	 * @param string $base_path Elements base path.
	 * @param string $base_uri Elements base URI.
	 */
	public function __construct( $base_path = '', $base_uri = '' ) {
		$this->base_path = $base_path ? untrailingslashit( $base_path ) : untrailingslashit( get_template_directory() . '/elements' );
		$this->base_uri  = $base_uri ? untrailingslashit( $base_uri ) : untrailingslashit( get_template_directory_uri() . '/elements' );
	}

	/**
	 * Return all registered builder elements.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function all() {
		if ( null !== $this->definitions ) {
			return $this->definitions;
		}

		$this->definitions = array();

		if ( ! is_dir( $this->base_path ) ) {
			return $this->definitions;
		}

		foreach ( glob( $this->base_path . '/*/*/element.json' ) ?: array() as $manifest_path ) {
			$definition = $this->load_manifest( $manifest_path );

			if ( ! $definition ) {
				continue;
			}

			$this->definitions[ $definition['id'] ] = $definition;
		}

		ksort( $this->definitions );

		return $this->definitions;
	}

	/**
	 * Return element definitions safe for builder client payloads.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function public_all() {
		$public = array();

		foreach ( $this->all() as $element_id => $definition ) {
			$public[ $element_id ] = array_diff_key(
				$definition,
				array(
					'module_path' => true,
					'module_uri'  => true,
					'render_path'  => true,
				)
			);
		}

		return $public;
	}

	/**
	 * Get one element definition.
	 *
	 * @param string $element_id Element id.
	 * @return array<string, mixed>|null
	 */
	public function get( $element_id ) {
		$all = $this->all();

		return isset( $all[ $element_id ] ) ? $all[ $element_id ] : null;
	}

	/**
	 * Return element definitions filtered by builder context.
	 *
	 * @param string $context Builder context.
	 * @return array<string, array<string, mixed>>
	 */
	public function get_by_context( $context ) {
		$output = array();

		foreach ( $this->all() as $element_id => $element ) {
			if ( in_array( $context, (array) $element['contexts'], true ) ) {
				$output[ $element_id ] = $element;
			}
		}

		return $output;
	}

	/**
	 * Return element definitions supported by a specific zone.
	 *
	 * @param string $context Builder context.
	 * @param string $zone_id Zone id.
	 * @return array<string, array<string, mixed>>
	 */
	public function get_for_zone( $context, $zone_id ) {
		$output = array();

		foreach ( $this->get_by_context( $context ) as $element_id => $element ) {
			if ( $this->supports_zone( $element_id, $zone_id ) ) {
				$output[ $element_id ] = $element;
			}
		}

		return $output;
	}

	/**
	 * Determine whether an element supports a specific zone.
	 *
	 * @param string $element_id Element id.
	 * @param string $zone_id Zone id.
	 * @return bool
	 */
	public function supports_zone( $element_id, $zone_id ) {
		$element = $this->get( $element_id );

		return ! empty( $element['allowed_zones'] ) && in_array( $zone_id, (array) $element['allowed_zones'], true );
	}

	/**
	 * Render one element instance using its module render.php.
	 *
	 * @param array<string, mixed> $instance Element instance.
	 * @param string               $zone_id Zone id.
	 * @param string               $context Builder context.
	 * @return string
	 */
	public function render( array $instance, $zone_id, $context ) {
		$definition = $this->get( isset( $instance['type'] ) ? (string) $instance['type'] : '' );

		if ( empty( $definition['render_path'] ) || ! is_readable( (string) $definition['render_path'] ) ) {
			return '';
		}

		$settings = isset( $instance['settings'] ) && is_array( $instance['settings'] ) ? $instance['settings'] : array();
		$settings = array_merge( (array) $definition['default_settings'], $settings );
		$element  = $definition;
		$instance = $instance;
		$zone_id  = (string) $zone_id;
		$context  = (string) $context;

		ob_start();
		include (string) $definition['render_path'];

		return (string) ob_get_clean();
	}

	/**
	 * Return element asset descriptors for a definition.
	 *
	 * @param array<string, mixed> $definition Element definition.
	 * @return array<string, array<string, string>>
	 */
	public function get_assets( array $definition ) {
		$assets = array(
			'css' => array(),
			'js'  => array(),
		);

		foreach ( array( 'css', 'js' ) as $type ) {
			if ( empty( $definition['assets'][ $type ] ) ) {
				continue;
			}

			$files = is_array( $definition['assets'][ $type ] ) ? $definition['assets'][ $type ] : array( $definition['assets'][ $type ] );

			foreach ( $files as $file ) {
				$file = ltrim( (string) $file, '/' );
				$path = trailingslashit( (string) $definition['module_path'] ) . $file;

				if ( ! file_exists( $path ) ) {
					continue;
				}

				$assets[ $type ][] = array(
					'path' => $path,
					'uri'  => trailingslashit( (string) $definition['module_uri'] ) . $file,
				);
			}
		}

		return $assets;
	}

	/**
	 * Load and normalize one element manifest.
	 *
	 * @param string $manifest_path Manifest path.
	 * @return array<string, mixed>|null
	 */
	protected function load_manifest( $manifest_path ) {
		$raw = json_decode( (string) file_get_contents( $manifest_path ), true );

		if ( ! is_array( $raw ) || empty( $raw['id'] ) || empty( $raw['label'] ) ) {
			return null;
		}

		$module_path = dirname( $manifest_path );
		$relative    = ltrim( str_replace( $this->base_path, '', $module_path ), '/\\' );
		$module_uri  = trailingslashit( $this->base_uri ) . str_replace( DIRECTORY_SEPARATOR, '/', $relative );
		$settings    = isset( $raw['settings'] ) && is_array( $raw['settings'] ) ? $raw['settings'] : array();

		return array(
			'id'               => sanitize_key( (string) $raw['id'] ),
			'label'            => sanitize_text_field( (string) $raw['label'] ),
			'context'          => isset( $raw['context'] ) ? sanitize_key( (string) $raw['context'] ) : '',
			'contexts'         => $this->normalize_contexts( $raw ),
			'category'         => isset( $raw['category'] ) ? sanitize_key( (string) $raw['category'] ) : '',
			'allowed_zones'    => $this->sanitize_string_list( isset( $raw['allowed_zones'] ) ? $raw['allowed_zones'] : array() ),
			'max_instances'    => isset( $raw['max_instances'] ) ? absint( $raw['max_instances'] ) : 0,
			'assets'           => isset( $raw['assets'] ) && is_array( $raw['assets'] ) ? $raw['assets'] : array(),
			'default_settings' => $this->extract_default_settings( $settings ),
			'settings_schema'  => $this->extract_settings_schema( $settings ),
			'module_path'      => $module_path,
			'module_uri'       => $module_uri,
			'render_path'      => file_exists( $module_path . '/render.php' ) ? $module_path . '/render.php' : '',
			'thumbnail'        => $this->resolve_optional_uri( $module_path, $module_uri, isset( $raw['thumbnail'] ) ? (string) $raw['thumbnail'] : 'thumbnail.jpg' ),
		);
	}

	/**
	 * Normalize context metadata to current builder contexts.
	 *
	 * @param array<string, mixed> $raw Manifest data.
	 * @return string[]
	 */
	protected function normalize_contexts( array $raw ) {
		if ( ! empty( $raw['contexts'] ) ) {
			return $this->sanitize_string_list( $raw['contexts'] );
		}

		$context = isset( $raw['context'] ) ? sanitize_key( (string) $raw['context'] ) : '';

		if ( in_array( $context, array( 'header', 'footer', 'master' ), true ) ) {
			return array( BuilderContext::MASTER );
		}

		if ( in_array( $context, array( BuilderContext::PRODUCT, BuilderContext::ARCHIVE ), true ) ) {
			return array( $context );
		}

		return array();
	}

	/**
	 * Extract default settings from manifest controls.
	 *
	 * @param array<string, mixed> $settings Settings schema object.
	 * @return array<string, mixed>
	 */
	protected function extract_default_settings( array $settings ) {
		$defaults = array();

		foreach ( $settings as $setting_id => $control ) {
			if ( ! is_array( $control ) ) {
				continue;
			}

			$defaults[ sanitize_key( (string) $setting_id ) ] = isset( $control['default'] ) ? $control['default'] : '';
		}

		return $defaults;
	}

	/**
	 * Convert manifest settings object to the existing builder controls schema.
	 *
	 * @param array<string, mixed> $settings Settings schema object.
	 * @return array<int, array<string, mixed>>
	 */
	protected function extract_settings_schema( array $settings ) {
		$schema = array();

		foreach ( $settings as $setting_id => $control ) {
			if ( ! is_array( $control ) ) {
				continue;
			}

			$control_schema = array(
				'id'      => sanitize_key( (string) $setting_id ),
				'type'    => isset( $control['type'] ) ? sanitize_key( (string) $control['type'] ) : 'text',
				'label'   => isset( $control['label'] ) ? sanitize_text_field( (string) $control['label'] ) : ucwords( str_replace( '_', ' ', (string) $setting_id ) ),
				'default' => isset( $control['default'] ) ? $control['default'] : '',
				'options' => isset( $control['options'] ) && is_array( $control['options'] ) ? $this->sanitize_control_options( $control['options'] ) : array(),
			);

			foreach ( array( 'placeholder', 'help', 'min', 'max', 'step', 'rows', 'accept', 'item_label' ) as $meta_key ) {
				if ( isset( $control[ $meta_key ] ) ) {
					$control_schema[ $meta_key ] = sanitize_text_field( (string) $control[ $meta_key ] );
				}
			}

			if ( isset( $control['multiple'] ) ) {
				$control_schema['multiple'] = ! empty( $control['multiple'] );
			}

			if ( isset( $control['fields'] ) && is_array( $control['fields'] ) ) {
				$control_schema['fields'] = $this->sanitize_control_fields( $control['fields'] );
			}

			$schema[] = $control_schema;
		}

		return $schema;
	}

	/**
	 * Sanitize select-like control options.
	 *
	 * @param array<int, mixed> $options Raw options.
	 * @return array<int, array<string, string>>
	 */
	protected function sanitize_control_options( array $options ) {
		$sanitized = array();

		foreach ( $options as $option ) {
			if ( ! is_array( $option ) || ! isset( $option['value'] ) ) {
				continue;
			}

			$sanitized[] = array(
				'value' => sanitize_text_field( (string) $option['value'] ),
				'label' => isset( $option['label'] ) ? sanitize_text_field( (string) $option['label'] ) : sanitize_text_field( (string) $option['value'] ),
			);
		}

		return $sanitized;
	}

	/**
	 * Sanitize repeater field schema.
	 *
	 * @param array<int, mixed> $fields Raw repeater fields.
	 * @return array<int, array<string, mixed>>
	 */
	protected function sanitize_control_fields( array $fields ) {
		$sanitized = array();

		foreach ( $fields as $field ) {
			if ( ! is_array( $field ) || empty( $field['id'] ) ) {
				continue;
			}

			$field_schema = array(
				'id'      => sanitize_key( (string) $field['id'] ),
				'type'    => isset( $field['type'] ) ? sanitize_key( (string) $field['type'] ) : 'text',
				'label'   => isset( $field['label'] ) ? sanitize_text_field( (string) $field['label'] ) : ucwords( str_replace( '_', ' ', (string) $field['id'] ) ),
				'default' => isset( $field['default'] ) ? $field['default'] : '',
				'options' => isset( $field['options'] ) && is_array( $field['options'] ) ? $this->sanitize_control_options( $field['options'] ) : array(),
			);

			foreach ( array( 'placeholder', 'help', 'min', 'max', 'step', 'rows', 'accept' ) as $meta_key ) {
				if ( isset( $field[ $meta_key ] ) ) {
					$field_schema[ $meta_key ] = sanitize_text_field( (string) $field[ $meta_key ] );
				}
			}

			$sanitized[] = $field_schema;
		}

		return $sanitized;
	}

	/**
	 * Sanitize a list of strings.
	 *
	 * @param mixed $values Raw values.
	 * @return string[]
	 */
	protected function sanitize_string_list( $values ) {
		$values = is_array( $values ) ? $values : array( $values );

		return array_values(
			array_filter(
				array_map(
					function( $value ) {
						return sanitize_key( (string) $value );
					},
					$values
				)
			)
		);
	}

	/**
	 * Resolve an optional module file URI.
	 *
	 * @param string $module_path Module path.
	 * @param string $module_uri Module URI.
	 * @param string $file Relative file.
	 * @return string
	 */
	protected function resolve_optional_uri( $module_path, $module_uri, $file ) {
		$file = ltrim( $file, '/' );

		return file_exists( trailingslashit( $module_path ) . $file ) ? trailingslashit( $module_uri ) . $file : '';
	}
}
