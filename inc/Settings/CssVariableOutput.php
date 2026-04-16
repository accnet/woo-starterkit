<?php
/**
 * Print global CSS variables in the frontend head.
 *
 * @package StarterKit
 */

namespace StarterKit\Settings;

class CssVariableOutput {
	/**
	 * Settings manager.
	 *
	 * @var GlobalSettingsManager
	 */
	protected $settings;

	/**
	 * Constructor.
	 *
	 * @param GlobalSettingsManager $settings Settings manager.
	 */
	public function __construct( GlobalSettingsManager $settings ) {
		$this->settings = $settings;

		add_action( 'wp_head', array( $this, 'render' ), 20 );
	}

	/**
	 * Output CSS variables.
	 *
	 * @return void
	 */
	public function render() {
		$settings = $this->settings->all();
		$vars     = array(
			'--site-font-heading' => $this->settings->font_stack( (string) $settings['heading_font'] ),
			'--site-font-body'    => $this->settings->font_stack( (string) $settings['body_font'] ),
			'--color-primary'     => $settings['color_primary'],
			'--color-secondary'   => $settings['color_secondary'],
			'--color-accent'      => $settings['color_accent'],
			'--color-bg'          => $settings['color_background'],
			'--container-width'   => $settings['container_width'],
			'--element-gap'       => $settings['element_gap'],
			'--component-gap'     => $settings['component_gap'],
			'--content-gap'       => $settings['content_gap'],
			'--section-gap'       => $settings['spacing_scale'],
			'--radius-md'         => $settings['border_radius'],
			'--shadow-preset'     => $settings['shadow_preset'],
		);

		echo "<style id='starterkit-theme-tokens'>:root{";

		foreach ( $vars as $key => $value ) {
			echo wp_strip_all_tags( (string) $key ) . ':' . $this->sanitize_css_value( (string) $value ) . ';'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		echo '}</style>';
	}

	/**
	 * Sanitize a CSS custom property value while preserving quotes and commas.
	 *
	 * @param string $value Raw CSS value.
	 * @return string
	 */
	protected function sanitize_css_value( $value ) {
		$value = html_entity_decode( $value, ENT_QUOTES, 'UTF-8' );

		return trim( preg_replace( '/[^a-zA-Z0-9#%(),.\'"\\-\\s]/', '', $value ) );
	}
}
