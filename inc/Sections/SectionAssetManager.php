<?php
/**
 * Conditional asset loader for shortcode section modules.
 *
 * @package StarterKit
 */

namespace StarterKit\Sections;

use StarterKit\Core\AssetManager;
use StarterKit\Core\AssetVersion;

class SectionAssetManager {
	/**
	 * Section registry.
	 *
	 * @var SectionRegistry
	 */
	protected $section_registry;

	/**
	 * Theme asset manager.
	 *
	 * @var AssetManager
	 */
	protected $asset_manager;

	/**
	 * Constructor.
	 *
	 * @param SectionRegistry $section_registry Section registry.
	 * @param AssetManager    $asset_manager Theme asset manager.
	 */
	public function __construct( SectionRegistry $section_registry, AssetManager $asset_manager ) {
		$this->section_registry = $section_registry;
		$this->asset_manager    = $asset_manager;

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_active_section_assets' ), 30 );
	}

	/**
	 * Enqueue assets for sections present in current post content.
	 *
	 * @return void
	 */
	public function enqueue_active_section_assets() {
		foreach ( $this->get_used_section_types() as $section_type ) {
			$definition = $this->section_registry->get( $section_type );

			if ( ! $definition ) {
				continue;
			}

			$assets = $this->section_registry->get_assets( $definition );

			foreach ( $assets['css'] as $index => $asset ) {
				wp_enqueue_style(
					'starterkit-section-' . sanitize_key( $section_type ) . '-' . $index,
					$asset['uri'],
					array( 'starterkit-theme' ),
					AssetVersion::for_file( $asset['path'] )
				);
			}

			$script_deps = $this->script_dependencies_for_section( $section_type );

			foreach ( $assets['js'] as $index => $asset ) {
				wp_enqueue_script(
					'starterkit-section-' . sanitize_key( $section_type ) . '-' . $index,
					$asset['uri'],
					$script_deps,
					AssetVersion::for_file( $asset['path'] ),
					true
				);
			}
		}
	}

	/**
	 * Return section types present in current queried post content.
	 *
	 * @return string[]
	 */
	protected function get_used_section_types() {
		$contents = $this->get_request_post_contents();

		if ( empty( $contents ) ) {
			return array();
		}

		$types = array();

		foreach ( $contents as $content ) {
			if ( ! has_shortcode( $content, 'section' ) ) {
				continue;
			}

			$types = array_merge( $types, $this->extract_section_types( $content ) );
		}

		return array_values( array_unique( array_filter( $types ) ) );
	}

	/**
	 * Return post contents relevant to the current request.
	 *
	 * @return string[]
	 */
	protected function get_request_post_contents() {
		$post = get_queried_object();

		if ( $post instanceof \WP_Post && ! empty( $post->post_content ) ) {
			return array( (string) $post->post_content );
		}

		global $wp_query;

		if ( ! $wp_query instanceof \WP_Query || empty( $wp_query->posts ) ) {
			return array();
		}

		$contents = array();

		foreach ( $wp_query->posts as $loop_post ) {
			if ( $loop_post instanceof \WP_Post && ! empty( $loop_post->post_content ) ) {
				$contents[] = (string) $loop_post->post_content;
			}
		}

		return $contents;
	}

	/**
	 * Extract section types using WordPress shortcode parsing APIs.
	 *
	 * @param string $content Content to scan.
	 * @return string[]
	 */
	protected function extract_section_types( $content ) {
		if ( false === strpos( $content, '[' ) ) {
			return array();
		}

		$types = array();
		$regex = get_shortcode_regex( array( 'section' ) );

		if ( ! preg_match_all( '/' . $regex . '/', $content, $matches, PREG_SET_ORDER ) ) {
			return $types;
		}

		foreach ( $matches as $shortcode ) {
			if ( ! isset( $shortcode[2] ) || 'section' !== $shortcode[2] ) {
				continue;
			}

			$atts = shortcode_parse_atts( isset( $shortcode[3] ) ? $shortcode[3] : '' );

			if ( is_array( $atts ) && ! empty( $atts['type'] ) ) {
				$types[] = sanitize_key( (string) $atts['type'] );
			}
		}

		return $types;
	}

	/**
	 * Return script dependencies for a section type.
	 *
	 * @param string $section_type Section type.
	 * @return string[]
	 */
	protected function script_dependencies_for_section( $section_type ) {
		if ( 'sliders' !== $section_type ) {
			return array();
		}

		$this->asset_manager->register_swiper_dependency();

		return array( 'starterkit-swiper' );
	}
}
