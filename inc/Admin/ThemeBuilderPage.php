<?php
/**
 * Theme Builder admin page.
 *
 * @package StarterKit
 */

namespace StarterKit\Admin;

use StarterKit\ThemeBuilder\ApiController;

class ThemeBuilderPage {
	/**
	 * AJAX controller.
	 *
	 * @var ApiController
	 */
	protected $api_controller;

	/**
	 * Constructor.
	 *
	 * @param ApiController $api_controller Builder API controller.
	 */
	public function __construct( ApiController $api_controller ) {
		$this->api_controller = $api_controller;

		add_action( 'admin_menu', array( $this, 'register_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Register the builder page.
	 *
	 * @return void
	 */
	public function register_page() {
		add_submenu_page(
			'starterkit-theme-builder',
			__( 'Theme Builder', 'starterkit' ),
			__( 'Theme Builder', 'starterkit' ),
			'manage_options',
			'starterkit-live-theme-builder',
			array( $this, 'render_page' ),
			1
		);
	}

	/**
	 * Enqueue builder assets.
	 *
	 * @param string $hook_suffix Hook suffix.
	 * @return void
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( 'theme-settings_page_starterkit-live-theme-builder' !== $hook_suffix && 'starterkit-theme-builder_page_starterkit-live-theme-builder' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_media();

		$coloris_path = get_template_directory() . '/assets/vendor/coloris/';
		$coloris_uri  = get_template_directory_uri() . '/assets/vendor/coloris/';
		$coloris_css  = $coloris_path . 'coloris.min.css';
		$coloris_js   = $coloris_path . 'coloris.min.js';

		wp_enqueue_style(
			'starterkit-coloris',
			$coloris_uri . 'coloris.min.css',
			array(),
			file_exists( $coloris_css ) ? filemtime( $coloris_css ) : '0.25.0'
		);

		wp_enqueue_style(
			'starterkit-theme-builder',
			get_template_directory_uri() . '/assets/css/theme-builder.css',
			array( 'starterkit-coloris' ),
			filemtime( get_template_directory() . '/assets/css/theme-builder.css' )
		);

		wp_enqueue_script(
			'starterkit-coloris',
			$coloris_uri . 'coloris.min.js',
			array(),
			file_exists( $coloris_js ) ? filemtime( $coloris_js ) : '0.25.0',
			true
		);

		wp_enqueue_script(
			'starterkit-theme-builder-app',
			get_template_directory_uri() . '/assets/js/theme-builder-app.js',
			array( 'starterkit-coloris' ),
			filemtime( get_template_directory() . '/assets/js/theme-builder-app.js' ),
			true
		);

		$admin_url_parts = wp_parse_url( admin_url() );
		$admin_origin    = isset( $admin_url_parts['scheme'], $admin_url_parts['host'] ) ? $admin_url_parts['scheme'] . '://' . $admin_url_parts['host'] : '';

		if ( $admin_origin && ! empty( $admin_url_parts['port'] ) ) {
			$admin_origin .= ':' . (int) $admin_url_parts['port'];
		}

		wp_localize_script(
			'starterkit-theme-builder-app',
			'starterkitThemeBuilder',
			array(
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( 'starterkit_theme_builder' ),
				'adminOrigin' => $admin_origin,
				'bootstrap'   => $this->api_controller->get_bootstrap_payload(),
			)
		);
	}

	/**
	 * Render the builder shell.
	 *
	 * @return void
	 */
	public function render_page() {
		?>
		<div class="wrap starterkit-theme-builder-page">
			<h1><?php esc_html_e( 'Theme Builder', 'starterkit' ); ?></h1>
			<div id="starterkit-theme-builder-app" class="starterkit-theme-builder-app"></div>
		</div>
		<?php
	}
}
