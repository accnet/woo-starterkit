<?php
/**
 * Theme Builder admin page.
 *
 * @package StarterKit
 */

namespace StarterKit\Admin;

use StarterKit\Core\AssetVersion;
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
		add_filter( 'admin_body_class', array( $this, 'add_admin_body_class' ) );
		add_action( 'admin_head', array( $this, 'print_fullscreen_admin_css' ) );
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
		if ( ! $this->is_builder_hook( $hook_suffix ) ) {
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
			AssetVersion::for_file( $coloris_css, '0.25.0' )
		);

		wp_enqueue_style(
			'starterkit-theme-builder',
			get_template_directory_uri() . '/assets/css/theme-builder.css',
			array( 'starterkit-coloris' ),
			AssetVersion::for_file( get_template_directory() . '/assets/css/theme-builder.css' )
		);

		wp_enqueue_script(
			'starterkit-coloris',
			$coloris_uri . 'coloris.min.js',
			array(),
			AssetVersion::for_file( $coloris_js, '0.25.0' ),
			true
		);

		wp_enqueue_script(
			'starterkit-theme-builder-app',
			get_template_directory_uri() . '/assets/js/theme-builder-app.js',
			array( 'starterkit-coloris' ),
			AssetVersion::for_file( get_template_directory() . '/assets/js/theme-builder-app.js' ),
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
				'exitUrl'     => admin_url( 'admin.php?page=starterkit-theme-builder' ),
				'bootstrap'   => $this->api_controller->get_bootstrap_payload(),
			)
		);
	}

	/**
	 * Add a body class for fullscreen admin chrome overrides.
	 *
	 * @param string $classes Admin body class string.
	 * @return string
	 */
	public function add_admin_body_class( $classes ) {
		if ( ! $this->is_builder_request() ) {
			return $classes;
		}

		return trim( $classes . ' starterkit-theme-builder-admin-fullscreen' );
	}

	/**
	 * Remove the WordPress admin toolbar offset for the fullscreen builder page.
	 *
	 * @return void
	 */
	public function print_fullscreen_admin_css() {
		if ( ! $this->is_builder_request() ) {
			return;
		}

		echo '<style id="starterkit-theme-builder-admin-fullscreen-css">html.wp-toolbar{padding-top:0!important;}</style>' . "\n";
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

	/**
	 * Determine whether the current admin hook belongs to the live builder.
	 *
	 * @param string $hook_suffix Hook suffix.
	 * @return bool
	 */
	protected function is_builder_hook( $hook_suffix ) {
		return in_array(
			$hook_suffix,
			array(
				'theme-settings_page_starterkit-live-theme-builder',
				'starterkit-theme-builder_page_starterkit-live-theme-builder',
			),
			true
		);
	}

	/**
	 * Determine whether the current admin request is the live builder page.
	 *
	 * @return bool
	 */
	protected function is_builder_request() {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( (string) $_GET['page'] ) ) : '';

		return 'starterkit-live-theme-builder' === $page;
	}
}
