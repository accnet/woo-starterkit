<?php
/**
 * Renderer for shortcode section modules.
 *
 * @package StarterKit
 */

namespace StarterKit\Sections;

class SectionRenderer {
	/**
	 * Section registry.
	 *
	 * @var SectionRegistry
	 */
	protected $section_registry;

	/**
	 * Constructor.
	 *
	 * @param SectionRegistry $section_registry Section registry.
	 */
	public function __construct( SectionRegistry $section_registry ) {
		$this->section_registry = $section_registry;
	}

	/**
	 * Render one section instance.
	 *
	 * @param string               $type Section type.
	 * @param array<string, mixed> $attributes Shortcode attributes.
	 * @param string               $content Inner shortcode content.
	 * @return string
	 */
	public function render( $type, array $attributes = array(), $content = '' ) {
		$type    = sanitize_key( (string) $type );
		$section = $this->section_registry->get( $type );

		if ( empty( $section['render_path'] ) || ! is_readable( (string) $section['render_path'] ) ) {
			return '';
		}

		$attributes = array_merge( (array) $section['defaults'], $attributes );
		$content    = (string) $content;

		ob_start();
		include (string) $section['render_path'];

		return (string) ob_get_clean();
	}
}
