<?php
/**
 * Icon manifest and filesystem registry.
 *
 * @package StarterKit
 */

namespace StarterKit\Icons;

class IconRegistry {
	/**
	 * Manifest relative path from theme root.
	 *
	 * @var string
	 */
	protected $manifest_relative_path;

	/**
	 * Cached manifest payload.
	 *
	 * @var array<string, mixed>|null
	 */
	protected $manifest = null;

	/**
	 * Constructor.
	 *
	 * @param string $manifest_relative_path Relative manifest path.
	 */
	public function __construct( $manifest_relative_path = 'assets/icons/manifest.json' ) {
		$this->manifest_relative_path = ltrim( (string) $manifest_relative_path, '/' );
	}

	/**
	 * Return normalized manifest payload.
	 *
	 * @return array<string, mixed>
	 */
	public function manifest() {
		if ( null !== $this->manifest ) {
			return $this->manifest;
		}

		$manifest = $this->read_manifest_file();
		$groups   = $this->normalize_groups( isset( $manifest['groups'] ) && is_array( $manifest['groups'] ) ? $manifest['groups'] : array() );
		$icons    = $this->normalize_icons( isset( $manifest['icons'] ) && is_array( $manifest['icons'] ) ? $manifest['icons'] : array(), $groups );
		$icons    = $this->merge_discovered_icons( $icons, $groups );

		$this->manifest = array(
			'version'  => isset( $manifest['version'] ) ? (int) $manifest['version'] : 1,
			'basePath' => isset( $manifest['basePath'] ) ? $this->sanitize_relative_path( (string) $manifest['basePath'] ) : 'assets/icons',
			'picker'   => $this->normalize_picker( isset( $manifest['picker'] ) && is_array( $manifest['picker'] ) ? $manifest['picker'] : array(), $groups ),
			'groups'   => array_values( $groups ),
			'icons'    => array_values( $icons ),
		);

		return $this->manifest;
	}

	/**
	 * Return all icon groups.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function groups() {
		$manifest = $this->manifest();

		return isset( $manifest['groups'] ) && is_array( $manifest['groups'] ) ? $manifest['groups'] : array();
	}

	/**
	 * Return a single group definition.
	 *
	 * @param string $group_id Group id.
	 * @return array<string, mixed>|null
	 */
	public function group( $group_id ) {
		$group_id = sanitize_key( (string) $group_id );

		foreach ( $this->groups() as $group ) {
			if ( $group_id === ( isset( $group['id'] ) ? $group['id'] : '' ) ) {
				return $group;
			}
		}

		return null;
	}

	/**
	 * Return all icons.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function icons() {
		$manifest = $this->manifest();

		return isset( $manifest['icons'] ) && is_array( $manifest['icons'] ) ? $manifest['icons'] : array();
	}

	/**
	 * Return icons for one group.
	 *
	 * @param string $group_id Group id.
	 * @return array<int, array<string, mixed>>
	 */
	public function icons_for_group( $group_id ) {
		$group_id = sanitize_key( (string) $group_id );

		return array_values(
			array_filter(
				$this->icons(),
				function( $icon ) use ( $group_id ) {
					return $group_id === ( isset( $icon['group'] ) ? $icon['group'] : '' );
				}
			)
		);
	}

	/**
	 * Return one icon definition by id.
	 *
	 * @param string $icon_id Icon id in group:slug format.
	 * @return array<string, mixed>|null
	 */
	public function icon( $icon_id ) {
		$icon_id = sanitize_text_field( (string) $icon_id );

		foreach ( $this->icons() as $icon ) {
			if ( $icon_id === ( isset( $icon['id'] ) ? $icon['id'] : '' ) ) {
				return $icon;
			}
		}

		return null;
	}

	/**
	 * Render inline SVG markup for one icon.
	 *
	 * @param string               $icon_id Icon id in group:slug format.
	 * @param array<string, mixed> $args Optional render args.
	 * @return string
	 */
	public function render( $icon_id, array $args = array() ) {
		$icon = $this->icon( $icon_id );

		if ( ! $icon || empty( $icon['filePath'] ) || ! file_exists( (string) $icon['filePath'] ) ) {
			return '';
		}

		$svg = file_get_contents( (string) $icon['filePath'] );

		if ( ! is_string( $svg ) || '' === trim( $svg ) ) {
			return '';
		}

		$classes = array(
			'starterkit-icon',
			'starterkit-icon--' . sanitize_html_class( str_replace( ':', '-', (string) $icon['id'] ) ),
		);

		if ( ! empty( $args['class'] ) ) {
			$extra_classes = preg_split( '/\s+/', (string) $args['class'] );

			if ( is_array( $extra_classes ) ) {
				foreach ( $extra_classes as $extra_class ) {
					$extra_class = sanitize_html_class( (string) $extra_class );

					if ( '' !== $extra_class ) {
						$classes[] = $extra_class;
					}
				}
			}
		}

		$attributes = array(
			'class' => implode( ' ', array_unique( $classes ) ),
		);

		if ( ! empty( $args['label'] ) ) {
			$attributes['role'] = 'img';
			$attributes['aria-label'] = sanitize_text_field( (string) $args['label'] );
		} else {
			$attributes['aria-hidden'] = 'true';
			$attributes['focusable'] = 'false';
		}

		$svg = preg_replace( '/<svg\b[^>]*>/i', '<svg ' . $this->build_svg_attributes( $attributes ) . '>', $svg, 1 );

		return is_string( $svg ) ? $svg : '';
	}

	/**
	 * Return picker configuration merged with live counts.
	 *
	 * @return array<string, mixed>
	 */
	public function picker_config() {
		$manifest = $this->manifest();
		$picker   = isset( $manifest['picker'] ) && is_array( $manifest['picker'] ) ? $manifest['picker'] : array();
		$groups   = $this->groups();

		$picker['groups'] = array_map(
			function( $group ) {
				$group['iconCount'] = count( $this->icons_for_group( isset( $group['id'] ) ? (string) $group['id'] : '' ) );

				return $group;
			},
			$groups
		);

		return $picker;
	}

	/**
	 * Read manifest JSON file.
	 *
	 * @return array<string, mixed>
	 */
	protected function read_manifest_file() {
		$path = get_template_directory() . '/' . $this->manifest_relative_path;

		if ( ! file_exists( $path ) ) {
			return array();
		}

		$contents = file_get_contents( $path );
		$data     = json_decode( is_string( $contents ) ? $contents : '', true );

		return is_array( $data ) ? $data : array();
	}

	/**
	 * Normalize picker config.
	 *
	 * @param array<string, mixed>               $picker Raw picker config.
	 * @param array<string, array<string, mixed>> $groups Normalized groups.
	 * @return array<string, mixed>
	 */
	protected function normalize_picker( array $picker, array $groups ) {
		$group_ids = array_keys( $groups );
		$default   = isset( $picker['defaultGroup'] ) ? sanitize_key( (string) $picker['defaultGroup'] ) : 'ui';

		if ( ! in_array( $default, $group_ids, true ) ) {
			$default = isset( $group_ids[0] ) ? (string) $group_ids[0] : 'ui';
		}

		$order = isset( $picker['groupOrder'] ) && is_array( $picker['groupOrder'] ) ? $picker['groupOrder'] : array();
		$order = array_values(
			array_filter(
				array_map( 'sanitize_key', $order ),
				function( $group_id ) use ( $group_ids ) {
					return in_array( $group_id, $group_ids, true );
				}
			)
		);

		foreach ( $group_ids as $group_id ) {
			if ( ! in_array( $group_id, $order, true ) ) {
				$order[] = $group_id;
			}
		}

		$search_keys = isset( $picker['searchKeys'] ) && is_array( $picker['searchKeys'] ) ? $picker['searchKeys'] : array( 'label', 'keywords', 'group' );
		$search_keys = array_values(
			array_filter(
				array_map( 'sanitize_key', $search_keys )
			)
		);

		return array(
			'defaultGroup' => $default,
			'allowSearch'  => ! isset( $picker['allowSearch'] ) || ! empty( $picker['allowSearch'] ),
			'allowClear'   => ! isset( $picker['allowClear'] ) || ! empty( $picker['allowClear'] ),
			'groupOrder'   => $order,
			'searchKeys'   => $search_keys,
		);
	}

	/**
	 * Normalize group definitions.
	 *
	 * @param array<int, mixed> $groups Raw groups.
	 * @return array<string, array<string, mixed>>
	 */
	protected function normalize_groups( array $groups ) {
		$normalized = array();

		foreach ( $groups as $group ) {
			if ( ! is_array( $group ) ) {
				continue;
			}

			$id = isset( $group['id'] ) ? sanitize_key( (string) $group['id'] ) : '';

			if ( '' === $id ) {
				continue;
			}

			$normalized[ $id ] = array(
				'id'          => $id,
				'label'       => isset( $group['label'] ) ? sanitize_text_field( (string) $group['label'] ) : ucwords( str_replace( '-', ' ', $id ) ),
				'path'        => isset( $group['path'] ) ? $this->sanitize_relative_path( (string) $group['path'] ) : $id,
				'description' => isset( $group['description'] ) ? sanitize_text_field( (string) $group['description'] ) : '',
			);
		}

		return $normalized;
	}

	/**
	 * Normalize manifest icons.
	 *
	 * @param array<int, mixed>                  $icons Raw icons.
	 * @param array<string, array<string, mixed>> $groups Normalized groups.
	 * @return array<string, array<string, mixed>>
	 */
	protected function normalize_icons( array $icons, array $groups ) {
		$normalized = array();

		foreach ( $icons as $icon ) {
			if ( ! is_array( $icon ) ) {
				continue;
			}

			$group_id = isset( $icon['group'] ) ? sanitize_key( (string) $icon['group'] ) : '';
			$slug     = isset( $icon['slug'] ) ? sanitize_title( (string) $icon['slug'] ) : '';

			if ( '' === $group_id || '' === $slug || ! isset( $groups[ $group_id ] ) ) {
				continue;
			}

			$relative_path = isset( $icon['path'] ) ? $this->sanitize_relative_path( (string) $icon['path'] ) : $groups[ $group_id ]['path'] . '/' . $slug . '.svg';
			$key           = $group_id . ':' . $slug;

			$normalized[ $key ] = array(
				'id'          => $key,
				'group'       => $group_id,
				'slug'        => $slug,
				'label'       => isset( $icon['label'] ) ? sanitize_text_field( (string) $icon['label'] ) : $this->humanize_slug( $slug ),
				'keywords'    => $this->normalize_keywords( isset( $icon['keywords'] ) && is_array( $icon['keywords'] ) ? $icon['keywords'] : array() ),
				'path'        => $relative_path,
				'filePath'    => get_template_directory() . '/' . ltrim( $relative_path, '/' ),
				'url'         => get_template_directory_uri() . '/' . ltrim( $relative_path, '/' ),
				'groupLabel'  => $groups[ $group_id ]['label'],
				'isAvailable' => file_exists( get_template_directory() . '/' . ltrim( $relative_path, '/' ) ),
			);
		}

		return $normalized;
	}

	/**
	 * Merge filesystem-discovered icons not explicitly listed in manifest.
	 *
	 * @param array<string, array<string, mixed>> $icons Normalized manifest icons.
	 * @param array<string, array<string, mixed>> $groups Normalized groups.
	 * @return array<string, array<string, mixed>>
	 */
	protected function merge_discovered_icons( array $icons, array $groups ) {
		foreach ( $groups as $group_id => $group ) {
			$directory = trailingslashit( get_template_directory() . '/assets/icons/' . $group['path'] );

			if ( ! is_dir( $directory ) ) {
				continue;
			}

			$files = glob( $directory . '*.svg' );

			if ( ! is_array( $files ) ) {
				continue;
			}

			sort( $files );

			foreach ( $files as $file ) {
				$slug = sanitize_title( basename( $file, '.svg' ) );
				$key  = $group_id . ':' . $slug;

				if ( isset( $icons[ $key ] ) ) {
					$icons[ $key ]['isAvailable'] = true;
					continue;
				}

				$relative_path = 'assets/icons/' . trim( $group['path'], '/' ) . '/' . basename( $file );

				$icons[ $key ] = array(
					'id'          => $key,
					'group'       => $group_id,
					'slug'        => $slug,
					'label'       => $this->humanize_slug( $slug ),
					'keywords'    => array(),
					'path'        => $relative_path,
					'filePath'    => $file,
					'url'         => get_template_directory_uri() . '/' . ltrim( $relative_path, '/' ),
					'groupLabel'  => $group['label'],
					'isAvailable' => true,
				);
			}
		}

		return $icons;
	}

	/**
	 * Normalize keywords to a clean list.
	 *
	 * @param array<int, mixed> $keywords Raw keywords.
	 * @return array<int, string>
	 */
	protected function normalize_keywords( array $keywords ) {
		return array_values(
			array_filter(
				array_map(
					function( $keyword ) {
						return sanitize_text_field( (string) $keyword );
					},
					$keywords
				)
			)
		);
	}

	/**
	 * Sanitize a relative path.
	 *
	 * @param string $path Raw path.
	 * @return string
	 */
	protected function sanitize_relative_path( $path ) {
		$path = wp_normalize_path( $path );
		$path = ltrim( $path, '/' );

		return trim( preg_replace( '#\.\.+#', '', $path ), '/' );
	}

	/**
	 * Humanize an icon slug.
	 *
	 * @param string $slug Icon slug.
	 * @return string
	 */
	protected function humanize_slug( $slug ) {
		return ucwords( str_replace( array( '-', '_' ), ' ', (string) $slug ) );
	}

	/**
	 * Build safe SVG attribute markup.
	 *
	 * @param array<string, string> $attributes Attribute map.
	 * @return string
	 */
	protected function build_svg_attributes( array $attributes ) {
		$parts = array();

		foreach ( $attributes as $name => $value ) {
			$name  = preg_replace( '/[^a-zA-Z0-9:_-]/', '', (string) $name );
			$value = esc_attr( (string) $value );

			if ( '' === $name ) {
				continue;
			}

			$parts[] = $name . '="' . $value . '"';
		}

		return implode( ' ', $parts );
	}
}
