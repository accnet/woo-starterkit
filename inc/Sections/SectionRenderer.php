<?php
/**
 * Render a section template.
 *
 * @package StarterKit
 */

namespace StarterKit\Sections;

use StarterKit\Settings\GlobalSettingsManager;

class SectionRenderer {
	/**
	 * Section type registry.
	 *
	 * @var SectionTypeRegistry
	 */
	protected $registry;

	/**
	 * Settings manager.
	 *
	 * @var GlobalSettingsManager
	 */
	protected $settings;

	/**
	 * Constructor.
	 *
	 * @param SectionTypeRegistry   $registry Section registry.
	 * @param GlobalSettingsManager $settings Settings manager.
	 */
	public function __construct( SectionTypeRegistry $registry, GlobalSettingsManager $settings ) {
		$this->registry = $registry;
		$this->settings = $settings;
	}

	/**
	 * Render one section.
	 *
	 * @param array<string, mixed> $section Section instance.
	 * @param array<string, mixed> $context Page context.
	 * @return void
	 */
	public function render( array $section, array $context ) {
		$type = $this->registry->get( $section['type'] );

		if ( ! $type || empty( $type['template'] ) ) {
			return;
		}

		$data = array(
			'section'         => $section,
			'section_type'    => $type,
			'content'         => wp_parse_args( (array) $section['content'], isset( $type['default_content'] ) ? $type['default_content'] : array() ),
			'style'           => is_array( $section['style'] ) ? $section['style'] : array(),
			'global_settings' => $this->settings->all(),
			'page_context'    => $context,
		);

		$template = get_template_directory() . '/' . $type['template'];

		if ( file_exists( $template ) ) {
			include $template;
		}
	}
}
