<?php
/**
 * Output global custom scripts from theme settings.
 *
 * @package StarterKit
 */

namespace StarterKit\Core;

use StarterKit\Settings\GlobalSettingsManager;

class ScriptInjectionManager {
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

		add_action( 'wp_head', array( $this, 'render_header_scripts' ), 99 );
		add_action( 'wp_body_open', array( $this, 'render_body_top_scripts' ), 1 );
		add_action( 'starterkit_before_footer', array( $this, 'render_body_bottom_scripts' ), 99 );
		add_action( 'wp_footer', array( $this, 'render_footer_scripts' ), 99 );
	}

	/**
	 * Render scripts inside the document head.
	 *
	 * @return void
	 */
	public function render_header_scripts() {
		$this->output( (string) $this->settings->get( 'header_scripts', '' ) );
	}

	/**
	 * Render scripts at the top of the body.
	 *
	 * @return void
	 */
	public function render_body_top_scripts() {
		$this->output( (string) $this->settings->get( 'body_scripts_top', '' ) );
	}

	/**
	 * Render scripts near the bottom of the body content.
	 *
	 * @return void
	 */
	public function render_body_bottom_scripts() {
		$this->output( (string) $this->settings->get( 'body_scripts_bottom', '' ) );
	}

	/**
	 * Render scripts near wp_footer.
	 *
	 * @return void
	 */
	public function render_footer_scripts() {
		$this->output( (string) $this->settings->get( 'footer_scripts', '' ) );
	}

	/**
	 * Echo raw snippet content for trusted administrators.
	 *
	 * @param string $content Script snippet.
	 * @return void
	 */
	protected function output( $content ) {
		if ( '' === trim( $content ) ) {
			return;
		}

		echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
