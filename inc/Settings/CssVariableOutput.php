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
			'--font-size-body'    => $settings['body_font_size'],
			'--font-weight-body'  => $settings['body_font_weight'],
			'--line-height-body'  => $settings['body_line_height'],
			'--font-weight-heading' => $settings['heading_font_weight'],
			'--text-transform-heading' => $settings['heading_text_transform'],
			'--letter-spacing-heading' => $settings['heading_letter_spacing'],
			'--font-size-nav'     => $settings['nav_font_size'],
			'--font-weight-nav'   => $settings['nav_font_weight'],
			'--text-transform-nav' => $settings['nav_text_transform'],
			'--font-size-button'  => $settings['button_font_size'],
			'--font-weight-button' => $settings['button_font_weight'],
			'--text-transform-button' => $settings['button_text_transform'],
			'--font-size-eyebrow' => $settings['eyebrow_font_size'],
			'--font-weight-eyebrow' => $settings['eyebrow_font_weight'],
			'--text-transform-eyebrow' => $settings['eyebrow_text_transform'],
			'--letter-spacing-eyebrow' => $settings['eyebrow_letter_spacing'],
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
