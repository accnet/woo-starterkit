<?php
/**
 * Filesystem-backed shortcode section registry.
 *
 * @package StarterKit
 */

namespace StarterKit\Sections;

class SectionRegistry {
	/**
	 * Base sections directory.
	 *
	 * @var string
	 */
	protected $base_path;

	/**
	 * Base sections URI.
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
	 * @param string $base_path Sections base path.
	 * @param string $base_uri Sections base URI.
	 */
	public function __construct( $base_path = '', $base_uri = '' ) {
		$this->base_path = $base_path ? untrailingslashit( $base_path ) : untrailingslashit( get_template_directory() . '/sections' );
		$this->base_uri  = $base_uri ? untrailingslashit( $base_uri ) : untrailingslashit( get_template_directory_uri() . '/sections' );
	}

	/**
	 * Return all registered sections.
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

		foreach ( glob( $this->base_path . '/*/section.json' ) ?: array() as $manifest_path ) {
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
	 * Get one section definition.
	 *
	 * @param string $section_id Section id.
	 * @return array<string, mixed>|null
	 */
	public function get( $section_id ) {
		$section_id = sanitize_key( (string) $section_id );
		$all        = $this->all();

		return isset( $all[ $section_id ] ) ? $all[ $section_id ] : null;
	}

	/**
	 * Return section asset descriptors for a definition.
	 *
	 * @param array<string, mixed> $definition Section definition.
	 * @return array<string, array<int, array<string, string>>>
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
	 * Load and normalize one section manifest.
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
		$render_path = trailingslashit( $module_path ) . 'render.php';

		return array(
			'id'          => sanitize_key( (string) $raw['id'] ),
			'label'       => sanitize_text_field( (string) $raw['label'] ),
			'assets'      => isset( $raw['assets'] ) && is_array( $raw['assets'] ) ? $raw['assets'] : array(),
			'defaults'    => isset( $raw['defaults'] ) && is_array( $raw['defaults'] ) ? $raw['defaults'] : array(),
			'module_path' => $module_path,
			'module_uri'  => $module_uri,
			'render_path' => $render_path,
		);
	}
}
