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
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'admin_post_starterkit_export_config', array( $this, 'export_config' ) );
		add_action( 'admin_post_starterkit_import_config', array( $this, 'import_config' ) );
		add_action( 'admin_notices', array( $this, 'render_admin_notice' ) );
		add_action( 'wp_head', array( $this, 'render_favicon' ) );
	}

	/**
	 * Enqueue media uploader on settings page.
	 *
	 * @param string $hook_suffix Admin page hook.
	 * @return void
	 */
	public function enqueue_admin_assets( $hook_suffix ) {
		if ( 'toplevel_page_starterkit-theme-builder' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_media();
	}

	/**
	 * Output favicon link tag.
	 *
	 * @return void
	 */
	public function render_favicon() {
		$favicon_id = (int) $this->settings->get( 'favicon_id', 0 );

		if ( ! $favicon_id ) {
			return;
		}

		$url = wp_get_attachment_image_url( $favicon_id, 'full' );

		if ( $url ) {
			echo '<link rel="icon" href="' . esc_url( $url ) . '" />' . "\n";
		}
	}

	/**
	 * Add the menu page.
	 *
	 * @return void
	 */
	public function register_page() {
		add_menu_page(
			__( 'Theme Settings', 'starterkit' ),
			__( 'Theme Settings', 'starterkit' ),
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
			array( 'sanitize_callback' => array( $this->settings, 'sanitize' ) )
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
			<h1><?php esc_html_e( 'Theme Settings', 'starterkit' ); ?></h1>
			<h2 class="nav-tab-wrapper">
				<?php $this->render_tab( 'branding', __( 'Branding', 'starterkit' ), $tab ); ?>
				<?php $this->render_tab( 'global', __( 'Global Settings', 'starterkit' ), $tab ); ?>
				<?php $this->render_tab( 'design', __( 'Design Tokens', 'starterkit' ), $tab ); ?>
				<?php $this->render_tab( 'layouts', __( 'Layouts', 'starterkit' ), $tab ); ?>
				<?php $this->render_tab( 'performance', __( 'Performance', 'starterkit' ), $tab ); ?>
				<?php $this->render_tab( 'tools', __( 'Tools', 'starterkit' ), $tab ); ?>
			</h2>

			<form method="post" action="options.php">
				<?php settings_fields( 'starterkit_theme_builder' ); ?>

				<?php if ( 'branding' === $tab ) : ?>
					<table class="form-table" role="presentation">
						<?php $this->render_media_row( 'logo_id', __( 'Logo', 'starterkit' ), (int) $settings['logo_id'] ); ?>
						<?php $this->render_media_row( 'favicon_id', __( 'Favicon', 'starterkit' ), (int) $settings['favicon_id'] ); ?>
						<?php $this->render_select_row( 'heading_font', __( 'Heading Font', 'starterkit' ), $settings['heading_font'], $this->settings->google_font_options() ); ?>
						<?php $this->render_select_row( 'body_font', __( 'Body Font', 'starterkit' ), $settings['body_font'], $this->settings->google_font_options() ); ?>
						<?php $this->render_select_row( 'border_radius', __( 'Border Radius', 'starterkit' ), $settings['border_radius'], $this->settings->radius_options() ); ?>
						<?php $this->render_input_row( 'button_style', __( 'Button Style', 'starterkit' ), $settings['button_style'] ); ?>
						<?php $this->render_input_row( 'shadow_preset', __( 'Shadow Preset', 'starterkit' ), $settings['shadow_preset'] ); ?>
					</table>
				<?php elseif ( 'global' === $tab ) : ?>
					<table class="form-table" role="presentation">
						<?php $this->render_textarea_row( 'header_scripts', __( 'Header Scripts', 'starterkit' ), $settings['header_scripts'], __( 'Injected inside <head>. Useful for verification tags, analytics bootstrap, or third-party head snippets.', 'starterkit' ) ); ?>
						<?php $this->render_textarea_row( 'body_scripts_top', __( 'Body Scripts - Top', 'starterkit' ), $settings['body_scripts_top'], __( 'Injected immediately after <body>. Common for GTM noscript or tag manager body snippets.', 'starterkit' ) ); ?>
						<?php $this->render_textarea_row( 'body_scripts_bottom', __( 'Body Scripts - Bottom', 'starterkit' ), $settings['body_scripts_bottom'], __( 'Injected before the site footer template. Useful for global widgets or deferred embeds.', 'starterkit' ) ); ?>
						<?php $this->render_textarea_row( 'footer_scripts', __( 'Footer Scripts', 'starterkit' ), $settings['footer_scripts'], __( 'Injected near wp_footer() before </body>. Best for tracking and non-critical scripts.', 'starterkit' ) ); ?>
					</table>
				<?php elseif ( 'design' === $tab ) : ?>
					<table class="form-table" role="presentation">
						<?php $this->render_input_row( 'color_primary', __( 'Primary Color', 'starterkit' ), $settings['color_primary'] ); ?>
						<?php $this->render_input_row( 'color_secondary', __( 'Secondary Color', 'starterkit' ), $settings['color_secondary'] ); ?>
						<?php $this->render_input_row( 'color_accent', __( 'Accent Color', 'starterkit' ), $settings['color_accent'] ); ?>
						<?php $this->render_input_row( 'color_background', __( 'Background Color', 'starterkit' ), $settings['color_background'] ); ?>
						<?php $this->render_select_row( 'element_gap', __( 'Element Gap', 'starterkit' ), $settings['element_gap'], $this->settings->spacing_options() ); ?>
						<?php $this->render_select_row( 'component_gap', __( 'Component Gap', 'starterkit' ), $settings['component_gap'], $this->settings->spacing_options() ); ?>
						<?php $this->render_select_row( 'content_gap', __( 'Content Gap', 'starterkit' ), $settings['content_gap'], $this->settings->spacing_options() ); ?>
						<?php $this->render_select_row( 'spacing_scale', __( 'Section Gap', 'starterkit' ), $settings['spacing_scale'], $this->settings->spacing_options() ); ?>
						<?php $this->render_section_label_row( __( 'Typography', 'starterkit' ) ); ?>
						<?php $this->render_input_row( 'body_font_size', __( 'Body Font Size', 'starterkit' ), $settings['body_font_size'] ); ?>
						<?php $this->render_select_row( 'body_font_weight', __( 'Body Font Weight', 'starterkit' ), $settings['body_font_weight'], $this->settings->font_weight_options() ); ?>
						<?php $this->render_input_row( 'body_line_height', __( 'Body Line Height', 'starterkit' ), $settings['body_line_height'] ); ?>
						<?php $this->render_select_row( 'heading_font_weight', __( 'Heading Font Weight', 'starterkit' ), $settings['heading_font_weight'], $this->settings->font_weight_options() ); ?>
						<?php $this->render_select_row( 'heading_text_transform', __( 'Heading Text Transform', 'starterkit' ), $settings['heading_text_transform'], $this->settings->text_transform_options() ); ?>
						<?php $this->render_input_row( 'heading_letter_spacing', __( 'Heading Letter Spacing', 'starterkit' ), $settings['heading_letter_spacing'] ); ?>
						<?php $this->render_input_row( 'nav_font_size', __( 'Navigation Font Size', 'starterkit' ), $settings['nav_font_size'] ); ?>
						<?php $this->render_select_row( 'nav_font_weight', __( 'Navigation Font Weight', 'starterkit' ), $settings['nav_font_weight'], $this->settings->font_weight_options() ); ?>
						<?php $this->render_select_row( 'nav_text_transform', __( 'Navigation Text Transform', 'starterkit' ), $settings['nav_text_transform'], $this->settings->text_transform_options() ); ?>
						<?php $this->render_input_row( 'button_font_size', __( 'Button Font Size', 'starterkit' ), $settings['button_font_size'] ); ?>
						<?php $this->render_select_row( 'button_font_weight', __( 'Button Font Weight', 'starterkit' ), $settings['button_font_weight'], $this->settings->font_weight_options() ); ?>
						<?php $this->render_select_row( 'button_text_transform', __( 'Button Text Transform', 'starterkit' ), $settings['button_text_transform'], $this->settings->text_transform_options() ); ?>
						<?php $this->render_input_row( 'eyebrow_font_size', __( 'Eyebrow Font Size', 'starterkit' ), $settings['eyebrow_font_size'] ); ?>
						<?php $this->render_select_row( 'eyebrow_font_weight', __( 'Eyebrow Font Weight', 'starterkit' ), $settings['eyebrow_font_weight'], $this->settings->font_weight_options() ); ?>
						<?php $this->render_select_row( 'eyebrow_text_transform', __( 'Eyebrow Text Transform', 'starterkit' ), $settings['eyebrow_text_transform'], $this->settings->text_transform_options() ); ?>
						<?php $this->render_input_row( 'eyebrow_letter_spacing', __( 'Eyebrow Letter Spacing', 'starterkit' ), $settings['eyebrow_letter_spacing'] ); ?>
					</table>
				<?php elseif ( 'layouts' === $tab ) : ?>
					<table class="form-table" role="presentation">
						<?php $this->render_select_row( 'container_width', __( 'Container Size', 'starterkit' ), $settings['container_width'], $this->settings->container_width_options() ); ?>
						<?php $this->render_select_row( 'header_layout', __( 'Header Layout', 'starterkit' ), $settings['header_layout'], $layouts['headers'] ); ?>
						<?php $this->render_select_row( 'footer_layout', __( 'Footer Layout', 'starterkit' ), $settings['footer_layout'], $layouts['footers'] ); ?>
						<?php $this->render_select_row( 'product_layout', __( 'Product Layout', 'starterkit' ), $settings['product_layout'], $layouts['products'] ); ?>
						<?php $this->render_select_row( 'archive_layout', __( 'Archive Layout', 'starterkit' ), $settings['archive_layout'], $layouts['archives'] ); ?>
					</table>
				<?php elseif ( 'performance' === $tab ) : ?>
					<table class="form-table" role="presentation">
						<?php $this->render_checkbox_row( 'lazy_load_images', __( 'Lazy Load Images', 'starterkit' ), $settings['lazy_load_images'], __( 'Use native browser lazy-loading on WordPress images and thumbnails.', 'starterkit' ) ); ?>
						<?php $this->render_checkbox_row( 'disable_emojis', __( 'Disable Emoji Script', 'starterkit' ), $settings['disable_emojis'], __( 'Remove emoji detection scripts, styles, and related TinyMCE conversions.', 'starterkit' ) ); ?>
						<?php $this->render_checkbox_row( 'disable_block_css', __( 'Disable Block Library CSS', 'starterkit' ), $settings['disable_block_css'], __( 'Skip frontend block library styles when this theme is not using Gutenberg block styling.', 'starterkit' ) ); ?>
						<?php $this->render_checkbox_row( 'disable_mediaelement', __( 'Disable MediaElement Script and CSS', 'starterkit' ), $settings['disable_mediaelement'], __( 'Prevent loading MediaElement assets on the frontend unless you rely on core audio or video players.', 'starterkit' ) ); ?>
						<?php $this->render_checkbox_row( 'disable_jquery_migrate', __( 'Disable jQuery Migrate', 'starterkit' ), $settings['disable_jquery_migrate'], __( 'Remove jQuery Migrate from frontend requests for a lighter legacy script payload.', 'starterkit' ) ); ?>
						<?php $this->render_checkbox_row( 'preconnect_hints', __( 'Resource Hints (Preconnect)', 'starterkit' ), $settings['preconnect_hints'], __( 'Add dns-prefetch and preconnect for Google Fonts and other external domains to reduce DNS/TLS latency.', 'starterkit' ) ); ?>
						<?php $this->render_checkbox_row( 'preload_fonts', __( 'Preload Fonts', 'starterkit' ), $settings['preload_fonts'], __( 'Preload locally embedded WOFF2 font files so the browser fetches them before parsing CSS.', 'starterkit' ) ); ?>
						<?php $this->render_checkbox_row( 'async_images', __( 'Async Image Decoding', 'starterkit' ), $settings['async_images'], __( 'Add decoding="async" and fetchpriority hints on images to improve rendering performance.', 'starterkit' ) ); ?>
						<?php $this->render_checkbox_row( 'disable_cart_fragments', __( 'Disable WC Cart Fragments', 'starterkit' ), $settings['disable_cart_fragments'], __( 'Disable the heavy wc-cart-fragments script on pages where the cart drawer handles updates via AJAX.', 'starterkit' ) ); ?>
						<?php $this->render_checkbox_row( 'disable_wc_block_css', __( 'Disable WooCommerce Block CSS', 'starterkit' ), $settings['disable_wc_block_css'], __( 'Remove WooCommerce Blocks stylesheets when the theme does not use WooCommerce block components.', 'starterkit' ) ); ?>
						<?php $this->render_checkbox_row( 'disable_oembed', __( 'Disable oEmbed', 'starterkit' ), $settings['disable_oembed'], __( 'Remove oEmbed discovery links and scripts if your site does not embed external content.', 'starterkit' ) ); ?>
					</table>
				<?php endif; ?>

				<?php if ( 'tools' !== $tab ) : ?>
					<?php submit_button(); ?>
				<?php endif; ?>
			</form>

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
	 * Render a simple section label row inside a settings table.
	 *
	 * @param string $label Section label.
	 * @return void
	 */
	protected function render_section_label_row( $label ) {
		?>
		<tr>
			<th colspan="2" style="padding-top:24px;">
				<h2 style="margin:0;font-size:22px;"><?php echo esc_html( $label ); ?></h2>
			</th>
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
	 * Render a media upload field row.
	 *
	 * @param string $name Field key.
	 * @param string $label Field label.
	 * @param int    $attachment_id Attachment ID.
	 * @return void
	 */
	protected function render_media_row( $name, $label, $attachment_id ) {
		$preview = $attachment_id ? wp_get_attachment_image_url( $attachment_id, 'medium' ) : '';
		$field_name = GlobalSettingsManager::OPTION_KEY . '[' . $name . ']';
		$field_class = 'starterkit-media-field';

		if ( 'logo_id' === $name ) {
			$field_class .= ' starterkit-media-field--logo';
		}
		?>
		<tr>
			<th scope="row"><?php echo esc_html( $label ); ?></th>
			<td>
				<div class="<?php echo esc_attr( $field_class ); ?>" id="starterkit-media-<?php echo esc_attr( $name ); ?>">
					<div class="starterkit-media-preview">
						<?php if ( $preview ) : ?>
							<img src="<?php echo esc_url( $preview ); ?>" alt="">
						<?php endif; ?>
					</div>
					<input type="hidden" name="<?php echo esc_attr( $field_name ); ?>" value="<?php echo esc_attr( (string) $attachment_id ); ?>" class="starterkit-media-value">
					<div class="starterkit-media-actions">
						<button type="button" class="button starterkit-media-select"><?php esc_html_e( 'Select Image', 'starterkit' ); ?></button>
						<button type="button" class="button starterkit-media-remove" <?php echo ! $attachment_id ? 'style="display:none"' : ''; ?>><?php esc_html_e( 'Remove', 'starterkit' ); ?></button>
					</div>
				</div>
				<script>
				(function(){
					var wrap = document.getElementById('starterkit-media-<?php echo esc_js( $name ); ?>');
					if (!wrap) return;
					var input = wrap.querySelector('.starterkit-media-value');
					var preview = wrap.querySelector('.starterkit-media-preview');
					var removeBtn = wrap.querySelector('.starterkit-media-remove');

					wrap.querySelector('.starterkit-media-select').addEventListener('click', function(){
						var frame = wp.media({ title: '<?php echo esc_js( $label ); ?>', multiple: false, library: { type: 'image' } });
						frame.on('select', function(){
							var attachment = frame.state().get('selection').first().toJSON();
							var url = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;
							input.value = attachment.id;
							preview.innerHTML = '<img src="' + url + '" alt="">';
							removeBtn.style.display = '';
						});
						frame.open();
					});

					removeBtn.addEventListener('click', function(){
						input.value = '0';
						preview.innerHTML = '';
						this.style.display = 'none';
					});
				})();
				</script>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render a checkbox field row.
	 *
	 * @param string $name Field key.
	 * @param string $label Field label.
	 * @param string $checked Checked value.
	 * @param string $description Description text.
	 * @return void
	 */
	protected function render_checkbox_row( $name, $label, $checked, $description = '' ) {
		?>
		<tr>
			<th scope="row"><?php echo esc_html( $label ); ?></th>
			<td>
				<label for="<?php echo esc_attr( $name ); ?>">
					<input type="hidden" name="<?php echo esc_attr( GlobalSettingsManager::OPTION_KEY ); ?>[<?php echo esc_attr( $name ); ?>]" value="0">
					<input type="checkbox" id="<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( GlobalSettingsManager::OPTION_KEY ); ?>[<?php echo esc_attr( $name ); ?>]" value="1" <?php checked( '1', (string) $checked ); ?>>
					<?php esc_html_e( 'Enabled', 'starterkit' ); ?>
				</label>
				<?php if ( ! empty( $description ) ) : ?>
					<p class="description"><?php echo esc_html( $description ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render a textarea field row.
	 *
	 * @param string $name Field key.
	 * @param string $label Field label.
	 * @param string $value Field value.
	 * @param string $description Description text.
	 * @return void
	 */
	protected function render_textarea_row( $name, $label, $value, $description = '' ) {
		?>
		<tr>
			<th scope="row"><label for="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?></label></th>
			<td>
				<textarea name="<?php echo esc_attr( GlobalSettingsManager::OPTION_KEY ); ?>[<?php echo esc_attr( $name ); ?>]" id="<?php echo esc_attr( $name ); ?>" rows="8" class="large-text code"><?php echo esc_textarea( $value ); ?></textarea>
				<?php if ( ! empty( $description ) ) : ?>
					<p class="description"><?php echo esc_html( $description ); ?></p>
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
