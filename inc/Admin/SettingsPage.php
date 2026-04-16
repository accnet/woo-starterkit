<?php
/**
 * Theme settings, layout selection, and import/export tools.
 *
 * @package StarterKit
 */

namespace StarterKit\Admin;

use StarterKit\Layouts\LayoutRegistry;
use StarterKit\Settings\GlobalSettingsManager;

class SettingsPage {
	/**
	 * Settings manager.
	 *
	 * @var GlobalSettingsManager
	 */
	protected $settings;

	/**
	 * Layout registry.
	 *
	 * @var LayoutRegistry
	 */
	protected $layout_registry;

	/**
	 * Constructor.
	 *
	 * @param GlobalSettingsManager $settings Settings manager.
	 * @param LayoutRegistry        $layout_registry Layout registry.
	 */
	public function __construct( GlobalSettingsManager $settings, LayoutRegistry $layout_registry ) {
		$this->settings        = $settings;
		$this->layout_registry = $layout_registry;

		add_action( 'admin_menu', array( $this, 'register_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_post_starterkit_export_config', array( $this, 'export_config' ) );
		add_action( 'admin_post_starterkit_import_config', array( $this, 'import_config' ) );
		add_action( 'admin_notices', array( $this, 'render_admin_notice' ) );
	}

	/**
	 * Add the menu page.
	 *
	 * @return void
	 */
	public function register_page() {
		add_menu_page(
			__( 'Theme Builder Settings', 'starterkit' ),
			__( 'Theme Builder', 'starterkit' ),
			'manage_options',
			'starterkit-theme-builder',
			array( $this, 'render_page' ),
			'dashicons-admin-customizer',
			58
		);

		add_submenu_page(
			'starterkit-theme-builder',
			__( 'Settings', 'starterkit' ),
			__( 'Settings', 'starterkit' ),
			'manage_options',
			'starterkit-theme-builder',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Register option group.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'starterkit_theme_builder',
			GlobalSettingsManager::OPTION_KEY,
			array( $this->settings, 'sanitize' )
		);
	}

	/**
	 * Render settings UI.
	 *
	 * @return void
	 */
	public function render_page() {
		$settings = $this->settings->all();
		$layouts  = $this->layout_registry->all();
		$tab      = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'branding';
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Theme Builder Settings', 'starterkit' ); ?></h1>
			<h2 class="nav-tab-wrapper">
				<?php $this->render_tab( 'branding', __( 'Branding', 'starterkit' ), $tab ); ?>
				<?php $this->render_tab( 'design', __( 'Design Tokens', 'starterkit' ), $tab ); ?>
				<?php $this->render_tab( 'layouts', __( 'Layouts', 'starterkit' ), $tab ); ?>
				<?php $this->render_tab( 'tools', __( 'Tools', 'starterkit' ), $tab ); ?>
			</h2>

			<form method="post" action="options.php">
				<?php settings_fields( 'starterkit_theme_builder' ); ?>

				<?php if ( 'branding' === $tab ) : ?>
					<table class="form-table" role="presentation">
						<?php $this->render_select_row( 'heading_font', __( 'Heading Font', 'starterkit' ), $settings['heading_font'], $this->settings->google_font_options() ); ?>
						<?php $this->render_select_row( 'body_font', __( 'Body Font', 'starterkit' ), $settings['body_font'], $this->settings->google_font_options() ); ?>
						<?php $this->render_input_row( 'button_style', __( 'Button Style', 'starterkit' ), $settings['button_style'] ); ?>
						<?php $this->render_input_row( 'shadow_preset', __( 'Shadow Preset', 'starterkit' ), $settings['shadow_preset'] ); ?>
					</table>
				<?php elseif ( 'design' === $tab ) : ?>
					<table class="form-table" role="presentation">
						<?php $this->render_input_row( 'color_primary', __( 'Primary Color', 'starterkit' ), $settings['color_primary'] ); ?>
						<?php $this->render_input_row( 'color_secondary', __( 'Secondary Color', 'starterkit' ), $settings['color_secondary'] ); ?>
						<?php $this->render_input_row( 'color_accent', __( 'Accent Color', 'starterkit' ), $settings['color_accent'] ); ?>
						<?php $this->render_input_row( 'color_background', __( 'Background Color', 'starterkit' ), $settings['color_background'] ); ?>
						<?php $this->render_input_row( 'border_radius', __( 'Border Radius', 'starterkit' ), $settings['border_radius'] ); ?>
						<?php $this->render_select_row( 'element_gap', __( 'Element Gap', 'starterkit' ), $settings['element_gap'], $this->settings->spacing_options() ); ?>
						<?php $this->render_select_row( 'component_gap', __( 'Component Gap', 'starterkit' ), $settings['component_gap'], $this->settings->spacing_options() ); ?>
						<?php $this->render_select_row( 'content_gap', __( 'Content Gap', 'starterkit' ), $settings['content_gap'], $this->settings->spacing_options() ); ?>
						<?php $this->render_select_row( 'spacing_scale', __( 'Section Gap', 'starterkit' ), $settings['spacing_scale'], $this->settings->spacing_options() ); ?>
					</table>
				<?php elseif ( 'layouts' === $tab ) : ?>
					<table class="form-table" role="presentation">
						<?php $this->render_select_row( 'container_width', __( 'Container Size', 'starterkit' ), $settings['container_width'], $this->settings->container_width_options() ); ?>
						<?php $this->render_select_row( 'master_layout', __( 'Master Layout', 'starterkit' ), $settings['master_layout'], $layouts['masters'] ); ?>
						<?php $this->render_select_row( 'header_layout', __( 'Header Layout', 'starterkit' ), $settings['header_layout'], $layouts['headers'] ); ?>
						<?php $this->render_select_row( 'footer_layout', __( 'Footer Layout', 'starterkit' ), $settings['footer_layout'], $layouts['footers'] ); ?>
						<?php $this->render_select_row( 'product_layout', __( 'Product Layout', 'starterkit' ), $settings['product_layout'], $layouts['products'] ); ?>
						<?php $this->render_select_row( 'archive_layout', __( 'Archive Layout', 'starterkit' ), $settings['archive_layout'], $layouts['archives'] ); ?>
					</table>
				<?php endif; ?>

				<?php if ( 'tools' !== $tab ) : ?>
					<?php submit_button(); ?>
				<?php endif; ?>
			</form>

			<?php if ( 'branding' === $tab ) : ?>
				<p class="description"><?php esc_html_e( 'After saving your font choices, you can embed them locally into the theme to avoid relying on Google Fonts at runtime.', 'starterkit' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'starterkit_embed_fonts', 'starterkit_embed_fonts_nonce' ); ?>
					<input type="hidden" name="action" value="starterkit_embed_fonts">
					<p><button type="submit" class="button"><?php esc_html_e( 'Embed Selected Fonts Locally', 'starterkit' ); ?></button></p>
				</form>
			<?php endif; ?>

			<?php if ( 'tools' === $tab ) : ?>
				<?php $this->render_tools_panel(); ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render a text row.
	 *
	 * @param string $name Field key.
	 * @param string $label Field label.
	 * @param string $value Field value.
	 * @return void
	 */
	protected function render_input_row( $name, $label, $value ) {
		?>
		<tr>
			<th scope="row"><label for="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?></label></th>
			<td><input name="<?php echo esc_attr( GlobalSettingsManager::OPTION_KEY ); ?>[<?php echo esc_attr( $name ); ?>]" id="<?php echo esc_attr( $name ); ?>" class="regular-text" value="<?php echo esc_attr( $value ); ?>"></td>
		</tr>
		<?php
	}

	/**
	 * Render a select field row.
	 *
	 * @param string                               $name Field key.
	 * @param string                               $label Field label.
	 * @param string                               $selected Selected value.
	 * @param array<string, array<string, mixed>>  $options Options.
	 * @return void
	 */
	protected function render_select_row( $name, $label, $selected, array $options ) {
		?>
		<tr>
			<th scope="row"><label for="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?></label></th>
			<td>
				<select id="<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( GlobalSettingsManager::OPTION_KEY ); ?>[<?php echo esc_attr( $name ); ?>]">
					<?php foreach ( $options as $option ) : ?>
						<option value="<?php echo esc_attr( $option['id'] ); ?>" <?php selected( $selected, $option['id'] ); ?>>
							<?php echo esc_html( $option['label'] ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<?php if ( isset( $options[ $selected ]['description'] ) && ! empty( $options[ $selected ]['description'] ) ) : ?>
					<p class="description"><?php echo esc_html( $options[ $selected ]['description'] ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render a nav tab.
	 *
	 * @param string $slug Tab slug.
	 * @param string $label Label.
	 * @param string $current Current tab.
	 * @return void
	 */
	protected function render_tab( $slug, $label, $current ) {
		$url   = admin_url( 'admin.php?page=starterkit-theme-builder&tab=' . $slug );
		$class = 'nav-tab' . ( $slug === $current ? ' nav-tab-active' : '' );

		echo '<a class="' . esc_attr( $class ) . '" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
	}

	/**
	 * Render export and import tools.
	 *
	 * @return void
	 */
	protected function render_tools_panel() {
		$payload = wp_json_encode( $this->build_export_payload(), JSON_PRETTY_PRINT );
		?>
		<h2><?php esc_html_e( 'Export', 'starterkit' ); ?></h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'starterkit_export_config', 'starterkit_export_nonce' ); ?>
			<input type="hidden" name="action" value="starterkit_export_config">
			<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Download Configuration JSON', 'starterkit' ); ?></button></p>
		</form>
		<p><textarea readonly rows="14" class="large-text code"><?php echo esc_textarea( (string) $payload ); ?></textarea></p>

		<h2><?php esc_html_e( 'Import', 'starterkit' ); ?></h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'starterkit_import_config', 'starterkit_import_nonce' ); ?>
			<input type="hidden" name="action" value="starterkit_import_config">
			<p><textarea name="starterkit_import_payload" rows="14" class="large-text code" placeholder="<?php esc_attr_e( 'Paste exported configuration JSON here', 'starterkit' ); ?>"></textarea></p>
			<?php submit_button( __( 'Import Configuration', 'starterkit' ), 'secondary' ); ?>
		</form>
		<?php
	}

	/**
	 * Build export payload.
	 *
	 * @return array<string, mixed>
	 */
	protected function build_export_payload() {
		$sections = get_posts(
			array(
				'post_type'      => 'theme_section',
				'post_status'    => array( 'publish', 'draft' ),
				'posts_per_page' => -1,
			)
		);

		return array(
			'version'  => '1.0',
			'settings' => $this->settings->all(),
			'sections' => array_map(
				function( $post ) {
					$meta = array();

					foreach ( get_post_meta( $post->ID ) as $key => $values ) {
						$meta[ $key ] = count( $values ) > 1 ? array_map( 'maybe_unserialize', $values ) : maybe_unserialize( $values[0] );
					}

					return array(
						'title'  => $post->post_title,
						'status' => $post->post_status,
						'meta'   => $meta,
					);
				},
				$sections
			),
		);
	}

	/**
	 * Export configuration JSON.
	 *
	 * @return void
	 */
	public function export_config() {
		if ( ! current_user_can( 'manage_options' ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['starterkit_export_nonce'] ?? '' ) ), 'starterkit_export_config' ) ) {
			wp_die( esc_html__( 'Invalid export request.', 'starterkit' ) );
		}

		$payload = wp_json_encode( $this->build_export_payload(), JSON_PRETTY_PRINT );

		header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
		header( 'Content-Disposition: attachment; filename=starterkit-config.json' );
		echo $payload; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Import configuration JSON.
	 *
	 * @return void
	 */
	public function import_config() {
		if ( ! current_user_can( 'manage_options' ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['starterkit_import_nonce'] ?? '' ) ), 'starterkit_import_config' ) ) {
			wp_die( esc_html__( 'Invalid import request.', 'starterkit' ) );
		}

		$payload = isset( $_POST['starterkit_import_payload'] ) ? wp_unslash( $_POST['starterkit_import_payload'] ) : '';
		$data    = json_decode( is_string( $payload ) ? $payload : '', true );

		if ( ! is_array( $data ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=starterkit-theme-builder&tab=tools&starterkit_notice=import_failed' ) );
			exit;
		}

		if ( isset( $data['settings'] ) && is_array( $data['settings'] ) ) {
			update_option( GlobalSettingsManager::OPTION_KEY, $this->settings->sanitize( $data['settings'] ) );
		}

		if ( isset( $data['sections'] ) && is_array( $data['sections'] ) ) {
			foreach ( $data['sections'] as $section ) {
				if ( empty( $section['title'] ) ) {
					continue;
				}

				$post_id = wp_insert_post(
					array(
						'post_type'   => 'theme_section',
						'post_status' => isset( $section['status'] ) ? sanitize_key( (string) $section['status'] ) : 'draft',
						'post_title'  => sanitize_text_field( (string) $section['title'] ),
					)
				);

				if ( ! $post_id || is_wp_error( $post_id ) || empty( $section['meta'] ) || ! is_array( $section['meta'] ) ) {
					continue;
				}

				foreach ( $section['meta'] as $key => $value ) {
					update_post_meta( $post_id, sanitize_key( (string) $key ), is_array( $value ) ? wp_json_encode( $value ) : maybe_serialize( $value ) );
				}
			}
		}

		$this->flush_section_caches();

		wp_safe_redirect( admin_url( 'admin.php?page=starterkit-theme-builder&tab=tools&starterkit_notice=import_success' ) );
		exit;
	}

	/**
	 * Render import feedback notice.
	 *
	 * @return void
	 */
	public function render_admin_notice() {
		if ( empty( $_GET['starterkit_notice'] ) || empty( $_GET['page'] ) || 'starterkit-theme-builder' !== $_GET['page'] ) {
			return;
		}

		$notice = sanitize_key( wp_unslash( $_GET['starterkit_notice'] ) );

		if ( 'import_success' === $notice ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Configuration imported successfully.', 'starterkit' ) . '</p></div>';
		}

		if ( 'import_failed' === $notice ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Unable to import configuration. Check the JSON payload and try again.', 'starterkit' ) . '</p></div>';
		}

		if ( 'font_embed_success' === $notice ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Selected fonts were embedded locally into the theme.', 'starterkit' ) . '</p></div>';
		}

		if ( 'font_embed_failed' === $notice ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Unable to embed the selected fonts. Check network access and theme write permissions, then try again.', 'starterkit' ) . '</p></div>';
		}
	}

	/**
	 * Flush cached section collections.
	 *
	 * @return void
	 */
	protected function flush_section_caches() {
		$slots = array( '' );

		foreach ( $this->layout_registry->all() as $group ) {
			foreach ( $group as $layout ) {
				if ( empty( $layout['slots'] ) || ! is_array( $layout['slots'] ) ) {
					continue;
				}

				$slots = array_merge( $slots, $layout['slots'] );
			}
		}

		foreach ( array_unique( $slots ) as $slot ) {
			wp_cache_delete( 'starterkit_sections_' . md5( (string) $slot ), 'starterkit' );
		}
	}
}
