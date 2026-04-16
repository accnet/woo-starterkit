<?php
/**
 * Meta boxes for theme sections.
 *
 * @package StarterKit
 */

namespace StarterKit\Admin;

use StarterKit\Sections\SectionTypeRegistry;

class SectionMetaBoxes {
	/**
	 * Section type registry.
	 *
	 * @var SectionTypeRegistry
	 */
	protected $registry;

	/**
	 * Constructor.
	 *
	 * @param SectionTypeRegistry $registry Registry.
	 */
	public function __construct( SectionTypeRegistry $registry ) {
		$this->registry = $registry;

		add_action( 'add_meta_boxes', array( $this, 'register' ) );
		add_action( 'save_post_theme_section', array( $this, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Register meta box.
	 *
	 * @return void
	 */
	public function register() {
		add_meta_box(
			'starterkit-section-config',
			__( 'Section Configuration', 'starterkit' ),
			array( $this, 'render' ),
			'theme_section',
			'normal',
			'high'
		);
	}

	/**
	 * Render meta box.
	 *
	 * @param \WP_Post $post Current post.
	 * @return void
	 */
	public function render( $post ) {
		$types         = $this->registry->all();
		$current_type  = get_post_meta( $post->ID, '_section_type', true );
		$current_type  = $current_type ? $current_type : key( $types );
		$current_slot  = get_post_meta( $post->ID, '_section_slot', true );
		$current_rules = $this->decode_json_meta( $post->ID, '_section_display_rules_json' );
		$saved_content = $this->decode_json_meta( $post->ID, '_section_content_json' );
		$current_style = $this->decode_json_meta( $post->ID, '_section_style_json' );
		$priority      = (int) get_post_meta( $post->ID, '_section_priority', true );
		$status        = get_post_meta( $post->ID, '_section_status', true );
		$allowed_slots = $this->registry->allowed_slots( $current_type );
		$include_rules = isset( $current_rules['include'] ) && is_array( $current_rules['include'] ) ? $current_rules['include'] : array();
		$exclude_rules = isset( $current_rules['exclude'] ) && is_array( $current_rules['exclude'] ) ? $current_rules['exclude'] : array();
		$entire_site   = ! empty( $current_rules['entire_site'] );
		$include_rel   = isset( $current_rules['include_relation'] ) ? (string) $current_rules['include_relation'] : 'OR';
		$exclude_rel   = isset( $current_rules['exclude_relation'] ) ? (string) $current_rules['exclude_relation'] : 'OR';
		$device        = isset( $current_rules['device'] ) ? (string) $current_rules['device'] : 'all';
		$start_date    = isset( $current_rules['start_date'] ) ? (string) $current_rules['start_date'] : '';
		$end_date      = isset( $current_rules['end_date'] ) ? (string) $current_rules['end_date'] : '';
		$is_valid_slot = empty( $current_slot ) || in_array( $current_slot, $allowed_slots, true );

		wp_nonce_field( 'starterkit_save_section', 'starterkit_section_nonce' );
		?>
		<div class="starterkit-admin-grid">
			<div class="starterkit-admin-main">
				<p>
					<label for="starterkit_section_type"><?php esc_html_e( 'Section Type', 'starterkit' ); ?></label><br>
					<select name="starterkit_section_type" id="starterkit_section_type">
						<?php foreach ( $types as $type ) : ?>
							<option value="<?php echo esc_attr( $type['id'] ); ?>" <?php selected( $current_type, $type['id'] ); ?>>
								<?php echo esc_html( $type['label'] ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</p>
				<div class="starterkit-field-note">
					<?php esc_html_e( 'Changing section type updates the available fields and recommended slots.', 'starterkit' ); ?>
				</div>
				<?php if ( ! $is_valid_slot ) : ?>
					<div class="notice notice-warning inline"><p><?php esc_html_e( 'The saved slot no longer matches this section type. Choose a compatible slot before publishing.', 'starterkit' ); ?></p></div>
				<?php endif; ?>
				<p>
					<label for="starterkit_section_slot"><?php esc_html_e( 'Slot', 'starterkit' ); ?></label><br>
					<select name="starterkit_section_slot" id="starterkit_section_slot">
						<?php foreach ( $allowed_slots as $slot ) : ?>
							<option value="<?php echo esc_attr( $slot ); ?>" <?php selected( $current_slot, $slot ); ?>><?php echo esc_html( $slot ); ?></option>
						<?php endforeach; ?>
						<?php if ( $current_slot && ! in_array( $current_slot, $allowed_slots, true ) ) : ?>
							<option value="<?php echo esc_attr( $current_slot ); ?>" selected><?php echo esc_html( $current_slot ); ?></option>
						<?php endif; ?>
					</select>
				</p>
				<p>
					<label for="starterkit_section_priority"><?php esc_html_e( 'Priority', 'starterkit' ); ?></label><br>
					<input type="number" name="starterkit_section_priority" id="starterkit_section_priority" value="<?php echo esc_attr( $priority ); ?>">
				</p>
				<p>
					<label for="starterkit_section_status"><?php esc_html_e( 'Status', 'starterkit' ); ?></label><br>
					<select name="starterkit_section_status" id="starterkit_section_status">
						<option value="active" <?php selected( $status, 'active' ); ?>><?php esc_html_e( 'Active', 'starterkit' ); ?></option>
						<option value="inactive" <?php selected( $status, 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'starterkit' ); ?></option>
					</select>
				</p>
				<h3><?php esc_html_e( 'Content', 'starterkit' ); ?></h3>
				<div class="starterkit-type-panels">
					<?php foreach ( $types as $type_id => $type ) : ?>
						<div class="starterkit-type-panel" data-section-type="<?php echo esc_attr( $type_id ); ?>" <?php if ( $type_id !== $current_type ) : ?>hidden<?php endif; ?>>
							<?php $this->render_schema_fields( $type, $this->merge_with_defaults( $type_id, $type_id === $current_type ? $saved_content : array() ) ); ?>
							<?php $this->render_style_fields( $type, $current_style ); ?>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
			<div class="starterkit-admin-sidebar">
				<h3><?php esc_html_e( 'Display Rules', 'starterkit' ); ?></h3>
				<p>
					<label for="starterkit_rule_device"><?php esc_html_e( 'Device Visibility', 'starterkit' ); ?></label><br>
					<select name="starterkit_rule_device" id="starterkit_rule_device">
						<option value="all" <?php selected( $device, 'all' ); ?>><?php esc_html_e( 'All Devices', 'starterkit' ); ?></option>
						<option value="desktop" <?php selected( $device, 'desktop' ); ?>><?php esc_html_e( 'Desktop Only', 'starterkit' ); ?></option>
						<option value="mobile" <?php selected( $device, 'mobile' ); ?>><?php esc_html_e( 'Mobile Only', 'starterkit' ); ?></option>
					</select>
				</p>
				<p>
					<label for="starterkit_rule_start_date"><?php esc_html_e( 'Start Date', 'starterkit' ); ?></label><br>
					<input type="date" id="starterkit_rule_start_date" name="starterkit_rule_start_date" value="<?php echo esc_attr( $start_date ); ?>">
				</p>
				<p>
					<label for="starterkit_rule_end_date"><?php esc_html_e( 'End Date', 'starterkit' ); ?></label><br>
					<input type="date" id="starterkit_rule_end_date" name="starterkit_rule_end_date" value="<?php echo esc_attr( $end_date ); ?>">
				</p>
				<p>
					<label>
						<input type="checkbox" name="starterkit_rules_entire_site" value="1" <?php checked( $entire_site ); ?>>
						<?php esc_html_e( 'Render on entire site when no include rules are specified', 'starterkit' ); ?>
					</label>
				</p>
				<p>
					<label for="starterkit_rule_include_relation"><?php esc_html_e( 'Include Relation', 'starterkit' ); ?></label><br>
					<select name="starterkit_rule_include_relation" id="starterkit_rule_include_relation">
						<option value="OR" <?php selected( strtoupper( $include_rel ), 'OR' ); ?>><?php esc_html_e( 'Match Any Include Rule', 'starterkit' ); ?></option>
						<option value="AND" <?php selected( strtoupper( $include_rel ), 'AND' ); ?>><?php esc_html_e( 'Match All Include Rules', 'starterkit' ); ?></option>
					</select>
				</p>
				<p>
					<label for="starterkit_rule_include"><?php esc_html_e( 'Include Rules', 'starterkit' ); ?></label>
					<textarea class="large-text code" rows="8" name="starterkit_rule_include" id="starterkit_rule_include" placeholder="homepage&#10;page_id:42&#10;product_category:18"><?php echo esc_textarea( $this->rules_to_lines( $include_rules ) ); ?></textarea>
				</p>
				<p>
					<label for="starterkit_rule_exclude_relation"><?php esc_html_e( 'Exclude Relation', 'starterkit' ); ?></label><br>
					<select name="starterkit_rule_exclude_relation" id="starterkit_rule_exclude_relation">
						<option value="OR" <?php selected( strtoupper( $exclude_rel ), 'OR' ); ?>><?php esc_html_e( 'Exclude If Any Rule Matches', 'starterkit' ); ?></option>
						<option value="AND" <?php selected( strtoupper( $exclude_rel ), 'AND' ); ?>><?php esc_html_e( 'Exclude Only If All Rules Match', 'starterkit' ); ?></option>
					</select>
				</p>
				<p>
					<label for="starterkit_rule_exclude"><?php esc_html_e( 'Exclude Rules', 'starterkit' ); ?></label>
					<textarea class="large-text code" rows="8" name="starterkit_rule_exclude" id="starterkit_rule_exclude" placeholder="product_id:99"><?php echo esc_textarea( $this->rules_to_lines( $exclude_rules ) ); ?></textarea>
				</p>
				<div class="starterkit-field-note">
					<?php esc_html_e( 'One rule per line. Examples: homepage, page_id:42, post_type:page, page_template:template-special.php, all_products, product_id:88, product_category:14, product_tag:12, product_archive, shop_page, logged_in, guest, device:mobile.', 'starterkit' ); ?>
				</div>
			</div>
		</div>
		<script type="application/json" id="starterkit-section-types-data"><?php echo wp_json_encode( $this->build_editor_config( $types ) ); ?></script>
		<?php
	}

	/**
	 * Save section meta.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function save( $post_id ) {
		if ( ! isset( $_POST['starterkit_section_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['starterkit_section_nonce'] ) ), 'starterkit_save_section' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$type = sanitize_text_field( wp_unslash( $_POST['starterkit_section_type'] ?? '' ) );

		update_post_meta( $post_id, '_section_type', $type );
		update_post_meta( $post_id, '_section_slot', sanitize_text_field( wp_unslash( $_POST['starterkit_section_slot'] ?? '' ) ) );
		update_post_meta( $post_id, '_section_priority', absint( wp_unslash( $_POST['starterkit_section_priority'] ?? 0 ) ) );
		update_post_meta( $post_id, '_section_status', sanitize_text_field( wp_unslash( $_POST['starterkit_section_status'] ?? 'inactive' ) ) );
		update_post_meta( $post_id, '_section_display_rules_json', wp_json_encode( $this->collect_rules_from_request() ) );
		update_post_meta( $post_id, '_section_content_json', wp_json_encode( $this->collect_content_from_request( $type ) ) );
		update_post_meta( $post_id, '_section_style_json', wp_json_encode( $this->collect_style_from_request( $type ) ) );
		$this->flush_section_caches();
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( $hook ) {
		global $post_type;

		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}

		if ( 'theme_section' !== $post_type ) {
			return;
		}

		wp_enqueue_script(
			'starterkit-section-editor',
			get_template_directory_uri() . '/assets/js/admin-section-editor.js',
			array(),
			wp_get_theme()->get( 'Version' ),
			true
		);

		wp_enqueue_style(
			'starterkit-admin',
			get_template_directory_uri() . '/assets/css/admin.css',
			array(),
			wp_get_theme()->get( 'Version' )
		);

		wp_enqueue_media();
	}

	/**
	 * Render fields from schema.
	 *
	 * @param array<string, mixed> $type Section type.
	 * @param array<string, mixed> $values Values.
	 * @return void
	 */
	protected function render_schema_fields( array $type, array $values ) {
		$fields = isset( $type['fields'] ) && is_array( $type['fields'] ) ? $type['fields'] : array();

		foreach ( $fields as $field ) {
			$name  = $field['name'];
			$value = isset( $values[ $name ] ) ? $values[ $name ] : '';
			$this->render_field_input( 'starterkit_content', $field, $value );
		}
	}

	/**
	 * Render style fields from schema.
	 *
	 * @param array<string, mixed> $type Section type.
	 * @param array<string, mixed> $values Values.
	 * @return void
	 */
	protected function render_style_fields( array $type, array $values ) {
		$fields = isset( $type['style_fields'] ) && is_array( $type['style_fields'] ) ? $type['style_fields'] : array();

		if ( empty( $fields ) ) {
			return;
		}

		echo '<h3>' . esc_html__( 'Style Overrides', 'starterkit' ) . '</h3>';

		foreach ( $fields as $field ) {
			$name  = $field['name'];
			$value = isset( $values[ $name ] ) ? $values[ $name ] : '';
			$this->render_field_input( 'starterkit_style', $field, $value );
		}
	}

	/**
	 * Render one field input.
	 *
	 * @param string               $prefix Input prefix.
	 * @param array<string, mixed> $field Field schema.
	 * @param mixed                $value Value.
	 * @return void
	 */
	protected function render_field_input( $prefix, array $field, $value ) {
		$field_id = $prefix . '_' . $field['name'];

		echo '<p class="starterkit-field">';
		echo '<label for="' . esc_attr( $field_id ) . '"><strong>' . esc_html( $field['label'] ) . '</strong></label><br>';

		switch ( $field['type'] ) {
			case 'textarea':
				echo '<textarea class="large-text" rows="4" id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $prefix ) . '[' . $field['name'] . ']">' . esc_textarea( (string) $value ) . '</textarea>';
				break;
			case 'select':
				echo '<select id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $prefix ) . '[' . $field['name'] . ']">';
				foreach ( $field['options'] as $option_value => $option_label ) {
					echo '<option value="' . esc_attr( $option_value ) . '" ' . selected( (string) $value, (string) $option_value, false ) . '>' . esc_html( $option_label ) . '</option>';
				}
				echo '</select>';
				break;
			case 'media':
				$this->render_media_input( $prefix, $field, (int) $value, $field_id );
				break;
			case 'list':
				$this->render_list_input( $prefix, $field, is_array( $value ) ? $value : array() );
				break;
			case 'url':
				echo '<input class="regular-text" type="url" id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $prefix ) . '[' . $field['name'] . ']" value="' . esc_attr( (string) $value ) . '">';
				break;
			default:
				echo '<input class="regular-text" type="text" id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $prefix ) . '[' . $field['name'] . ']" value="' . esc_attr( (string) $value ) . '">';
				break;
		}

		echo '</p>';
	}

	/**
	 * Render repeatable list input.
	 *
	 * @param string               $prefix Input prefix.
	 * @param array<string, mixed> $field Field schema.
	 * @param array<int, mixed>    $items Items.
	 * @return void
	 */
	protected function render_list_input( $prefix, array $field, array $items ) {
		$item_fields = isset( $field['item_fields'] ) && is_array( $field['item_fields'] ) ? $field['item_fields'] : array();
		$min_rows    = isset( $field['min_rows'] ) ? max( 1, (int) $field['min_rows'] ) : 1;
		$items       = array_values( $items );

		while ( count( $items ) < $min_rows ) {
			$items[] = array();
		}

		echo '<div class="starterkit-list-group" data-list-name="' . esc_attr( $field['name'] ) . '">';
		echo '<div class="starterkit-list-items" data-next-index="' . esc_attr( (string) count( $items ) ) . '">';

		foreach ( $items as $index => $item ) {
			$this->render_list_row( $prefix, $field, $item_fields, $index, $item );
		}

		echo '</div>';
		echo '<template class="starterkit-list-template">';
		$this->render_list_row( $prefix, $field, $item_fields, '__INDEX__', array() );
		echo '</template>';
		echo '<div class="starterkit-list-actions"><button type="button" class="button button-secondary starterkit-add-row">' . esc_html__( 'Add Item', 'starterkit' ) . '</button></div>';
		echo '</div>';
	}

	/**
	 * Render one row inside repeatable list.
	 *
	 * @param string                              $prefix Input prefix.
	 * @param array<string, mixed>                $field Field schema.
	 * @param array<int, array<string, mixed>>    $item_fields Item fields.
	 * @param int|string                          $index Index.
	 * @param array<string, mixed>                $item Item values.
	 * @return void
	 */
	protected function render_list_row( $prefix, array $field, array $item_fields, $index, array $item ) {
		echo '<div class="starterkit-list-item">';
		echo '<div class="starterkit-list-item-header">';
		echo '<p><strong>' . esc_html( $field['label'] ) . ' <span class="starterkit-list-row-number">' . esc_html( is_numeric( $index ) ? (string) ( (int) $index + 1 ) : 'X' ) . '</span></strong></p>';
		echo '<div class="starterkit-list-toolbar"><button type="button" class="button-link starterkit-move-up">' . esc_html__( 'Up', 'starterkit' ) . '</button><button type="button" class="button-link starterkit-move-down">' . esc_html__( 'Down', 'starterkit' ) . '</button><button type="button" class="button-link-delete starterkit-remove-row">' . esc_html__( 'Remove', 'starterkit' ) . '</button></div>';
		echo '</div>';

		foreach ( $item_fields as $item_field ) {
			$name     = $item_field['name'];
			$value    = isset( $item[ $name ] ) ? $item[ $name ] : '';
			$input_id = $prefix . '_' . $field['name'] . '_' . $index . '_' . $name;
			$input    = $prefix . '[' . $field['name'] . '][' . $index . '][' . $name . ']';

			echo '<p>';
			echo '<label for="' . esc_attr( $input_id ) . '">' . esc_html( $item_field['label'] ) . '</label><br>';

			if ( 'textarea' === $item_field['type'] ) {
				echo '<textarea class="large-text" rows="3" id="' . esc_attr( $input_id ) . '" name="' . esc_attr( $input ) . '">' . esc_textarea( (string) $value ) . '</textarea>';
			} else {
				echo '<input class="regular-text" type="text" id="' . esc_attr( $input_id ) . '" name="' . esc_attr( $input ) . '" value="' . esc_attr( (string) $value ) . '">';
			}

			echo '</p>';
		}

		echo '</div>';
	}

	/**
	 * Render WordPress media selector.
	 *
	 * @param string               $prefix Input prefix.
	 * @param array<string, mixed> $field Field schema.
	 * @param int                  $value Attachment ID.
	 * @param string               $field_id HTML field id.
	 * @return void
	 */
	protected function render_media_input( $prefix, array $field, $value, $field_id ) {
		$image_url = $value ? wp_get_attachment_image_url( $value, 'medium' ) : '';

		echo '<div class="starterkit-media-field">';
		echo '<input type="hidden" id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $prefix ) . '[' . $field['name'] . ']" value="' . esc_attr( (string) $value ) . '">';
		echo '<div class="starterkit-media-preview">';
		if ( $image_url ) {
			echo '<img src="' . esc_url( $image_url ) . '" alt="">';
		} else {
			echo '<span>' . esc_html__( 'No image selected', 'starterkit' ) . '</span>';
		}
		echo '</div>';
		echo '<div class="starterkit-media-actions"><button type="button" class="button starterkit-media-select" data-target="' . esc_attr( $field_id ) . '">' . esc_html__( 'Choose Image', 'starterkit' ) . '</button><button type="button" class="button-link starterkit-media-clear" data-target="' . esc_attr( $field_id ) . '">' . esc_html__( 'Clear', 'starterkit' ) . '</button></div>';
		echo '</div>';
	}

	/**
	 * Decode JSON meta to array.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $meta_key Meta key.
	 * @return array<string, mixed>
	 */
	protected function decode_json_meta( $post_id, $meta_key ) {
		$raw = get_post_meta( $post_id, $meta_key, true );
		$val = is_string( $raw ) ? json_decode( $raw, true ) : array();

		return is_array( $val ) ? $val : array();
	}

	/**
	 * Merge saved values with defaults.
	 *
	 * @param string               $type_id Type ID.
	 * @param array<string, mixed> $values Saved values.
	 * @return array<string, mixed>
	 */
	protected function merge_with_defaults( $type_id, array $values ) {
		$type     = $this->registry->get( $type_id );
		$defaults = isset( $type['default_content'] ) && is_array( $type['default_content'] ) ? $type['default_content'] : array();

		return wp_parse_args( $values, $defaults );
	}

	/**
	 * Build client-side config.
	 *
	 * @param array<string, array<string, mixed>> $types Types.
	 * @return array<string, mixed>
	 */
	protected function build_editor_config( array $types ) {
		$config = array();

		foreach ( $types as $type_id => $type ) {
			$config[ $type_id ] = array(
				'allowed_slots' => isset( $type['allowed_slots'] ) ? array_values( $type['allowed_slots'] ) : array(),
			);
		}

		return $config;
	}

	/**
	 * Convert rules to textarea lines.
	 *
	 * @param array<int, array<string, mixed>> $rules Rules.
	 * @return string
	 */
	protected function rules_to_lines( array $rules ) {
		$lines = array();

		foreach ( $rules as $rule ) {
			$type  = isset( $rule['type'] ) ? (string) $rule['type'] : '';
			$value = isset( $rule['value'] ) ? (string) $rule['value'] : '';

			if ( '' === $type ) {
				continue;
			}

			$lines[] = '' !== $value ? $type . ':' . $value : $type;
		}

		return implode( "\n", $lines );
	}

	/**
	 * Collect content from request.
	 *
	 * @param string $type_id Type id.
	 * @return array<string, mixed>
	 */
	protected function collect_content_from_request( $type_id ) {
		$type   = $this->registry->get( $type_id );
		$fields = isset( $type['fields'] ) && is_array( $type['fields'] ) ? $type['fields'] : array();
		$input  = isset( $_POST['starterkit_content'] ) && is_array( $_POST['starterkit_content'] ) ? wp_unslash( $_POST['starterkit_content'] ) : array();

		return $this->sanitize_fields_from_schema( $fields, $input );
	}

	/**
	 * Collect style from request.
	 *
	 * @param string $type_id Type id.
	 * @return array<string, mixed>
	 */
	protected function collect_style_from_request( $type_id ) {
		$type   = $this->registry->get( $type_id );
		$fields = isset( $type['style_fields'] ) && is_array( $type['style_fields'] ) ? $type['style_fields'] : array();
		$input  = isset( $_POST['starterkit_style'] ) && is_array( $_POST['starterkit_style'] ) ? wp_unslash( $_POST['starterkit_style'] ) : array();

		return $this->sanitize_fields_from_schema( $fields, $input );
	}

	/**
	 * Sanitize fields from schema.
	 *
	 * @param array<int, array<string, mixed>> $fields Field schema.
	 * @param array<string, mixed>             $input Input.
	 * @return array<string, mixed>
	 */
	protected function sanitize_fields_from_schema( array $fields, array $input ) {
		$output = array();

		foreach ( $fields as $field ) {
			$name = $field['name'];

			if ( 'list' === $field['type'] ) {
				$items          = isset( $input[ $name ] ) && is_array( $input[ $name ] ) ? $input[ $name ] : array();
				$output[ $name ] = $this->sanitize_list_items( $field, $items );
				continue;
			}

			$value = isset( $input[ $name ] ) ? $input[ $name ] : '';

			switch ( $field['type'] ) {
				case 'url':
					$output[ $name ] = esc_url_raw( (string) $value );
					break;
				case 'media':
					$output[ $name ] = absint( $value );
					break;
				case 'textarea':
					$output[ $name ] = sanitize_textarea_field( (string) $value );
					break;
				default:
					$output[ $name ] = sanitize_text_field( (string) $value );
					break;
			}
		}

		return $output;
	}

	/**
	 * Sanitize list items.
	 *
	 * @param array<string, mixed> $field List field schema.
	 * @param array<int, mixed>    $items Items.
	 * @return array<int, array<string, string>>
	 */
	protected function sanitize_list_items( array $field, array $items ) {
		$item_fields = isset( $field['item_fields'] ) && is_array( $field['item_fields'] ) ? $field['item_fields'] : array();
		$output      = array();

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$row = array();

			foreach ( $item_fields as $item_field ) {
				$name         = $item_field['name'];
				$value        = isset( $item[ $name ] ) ? $item[ $name ] : '';
				$row[ $name ] = 'textarea' === $item_field['type'] ? sanitize_textarea_field( (string) $value ) : sanitize_text_field( (string) $value );
			}

			if ( array_filter( $row ) ) {
				$output[] = $row;
			}
		}

		return $output;
	}

	/**
	 * Collect display rules from request.
	 *
	 * @return array<string, mixed>
	 */
	protected function collect_rules_from_request() {
		return array(
			'entire_site'      => ! empty( $_POST['starterkit_rules_entire_site'] ),
			'device'           => sanitize_key( wp_unslash( $_POST['starterkit_rule_device'] ?? 'all' ) ),
			'start_date'       => sanitize_text_field( wp_unslash( $_POST['starterkit_rule_start_date'] ?? '' ) ),
			'end_date'         => sanitize_text_field( wp_unslash( $_POST['starterkit_rule_end_date'] ?? '' ) ),
			'include_relation' => sanitize_key( wp_unslash( $_POST['starterkit_rule_include_relation'] ?? 'OR' ) ),
			'exclude_relation' => sanitize_key( wp_unslash( $_POST['starterkit_rule_exclude_relation'] ?? 'OR' ) ),
			'include'          => $this->parse_rule_lines( isset( $_POST['starterkit_rule_include'] ) ? wp_unslash( $_POST['starterkit_rule_include'] ) : '' ),
			'exclude'          => $this->parse_rule_lines( isset( $_POST['starterkit_rule_exclude'] ) ? wp_unslash( $_POST['starterkit_rule_exclude'] ) : '' ),
		);
	}

	/**
	 * Parse rule textarea.
	 *
	 * @param string $raw Raw textarea content.
	 * @return array<int, array<string, string>>
	 */
	protected function parse_rule_lines( $raw ) {
		$raw   = is_string( $raw ) ? $raw : '';
		$lines = preg_split( '/\r\n|\r|\n/', $raw );
		$rules = array();

		foreach ( $lines as $line ) {
			$line = trim( (string) $line );

			if ( '' === $line ) {
				continue;
			}

			$parts = array_map( 'trim', explode( ':', $line, 2 ) );
			$type  = sanitize_key( $parts[0] );
			$value = isset( $parts[1] ) ? sanitize_text_field( $parts[1] ) : '';

			if ( '' === $type ) {
				continue;
			}

			$rules[] = array(
				'type'  => $type,
				'value' => $value,
			);
		}

		return $rules;
	}

	/**
	 * Flush cached section collections.
	 *
	 * @return void
	 */
	protected function flush_section_caches() {
		$slots = array( '' );

		foreach ( $this->registry->all() as $type ) {
			if ( empty( $type['allowed_slots'] ) || ! is_array( $type['allowed_slots'] ) ) {
				continue;
			}

			$slots = array_merge( $slots, $type['allowed_slots'] );
		}

		foreach ( array_unique( $slots ) as $slot ) {
			wp_cache_delete( 'starterkit_sections_' . md5( (string) $slot ), 'starterkit' );
		}
	}
}
