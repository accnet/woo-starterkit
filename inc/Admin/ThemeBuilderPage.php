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

		wp_enqueue_style(
			'starterkit-theme-builder',
			get_template_directory_uri() . '/assets/css/theme-builder.css',
			array(),
			filemtime( get_template_directory() . '/assets/css/theme-builder.css' )
		);

		wp_enqueue_script(
			'starterkit-theme-builder-app',
			get_template_directory_uri() . '/assets/js/theme-builder-app.js',
			array(),
			filemtime( get_template_directory() . '/assets/js/theme-builder-app.js' ),
			true
		);

		wp_localize_script(
			'starterkit-theme-builder-app',
			'starterkitThemeBuilder',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'starterkit_theme_builder' ),
				'bootstrap' => $this->api_controller->get_bootstrap_payload(),
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
