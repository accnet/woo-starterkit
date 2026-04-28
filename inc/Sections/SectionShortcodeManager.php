<?php
/**
 * Registers the public section shortcode.
 *
 * @package StarterKit
 */

namespace StarterKit\Sections;

class SectionShortcodeManager {
	/**
	 * Section renderer.
	 *
	 * @var SectionRenderer
	 */
	protected $section_renderer;

	/**
	 * Constructor.
	 *
	 * @param SectionRenderer $section_renderer Section renderer.
	 */
	public function __construct( SectionRenderer $section_renderer ) {
		$this->section_renderer = $section_renderer;

		add_action( 'init', array( $this, 'register_shortcode' ) );
	}

	/**
	 * Register shortcode.
	 *
	 * @return void
	 */
	public function register_shortcode() {
		add_shortcode( 'section', array( $this, 'render_shortcode' ) );
	}

	/**
	 * Render section shortcode.
	 *
	 * @param array<string, mixed>|string $atts Shortcode attributes.
	 * @param string|null                 $content Shortcode content.
	 * @return string
	 */
	public function render_shortcode( $atts, $content = null ) {
		$atts = is_array( $atts ) ? $atts : array();
		$type = isset( $atts['type'] ) ? sanitize_key( (string) $atts['type'] ) : '';

		if ( '' === $type ) {
			return '';
		}

		unset( $atts['type'] );

		return $this->section_renderer->render( $type, $atts, null === $content ? '' : (string) $content );
	}
}
