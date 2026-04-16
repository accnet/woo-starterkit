<?php
/**
 * Section list-table workflow enhancements.
 *
 * @package StarterKit
 */

namespace StarterKit\Admin;

class SectionAdminManager {
	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'manage_theme_section_posts_columns', array( $this, 'register_columns' ) );
		add_action( 'manage_theme_section_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
		add_filter( 'post_row_actions', array( $this, 'register_row_actions' ), 10, 2 );
		add_action( 'admin_action_starterkit_duplicate_section', array( $this, 'duplicate_section' ) );
	}

	/**
	 * Register custom columns.
	 *
	 * @param array<string, string> $columns List table columns.
	 * @return array<string, string>
	 */
	public function register_columns( array $columns ) {
		$columns['section_type'] = __( 'Type', 'starterkit' );
		$columns['section_slot'] = __( 'Slot', 'starterkit' );
		$columns['priority']     = __( 'Priority', 'starterkit' );
		$columns['status_meta']  = __( 'Status', 'starterkit' );

		return $columns;
	}

	/**
	 * Render custom column.
	 *
	 * @param string $column_name Column key.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public function render_column( $column_name, $post_id ) {
		switch ( $column_name ) {
			case 'section_type':
				echo esc_html( (string) get_post_meta( $post_id, '_section_type', true ) );
				break;
			case 'section_slot':
				echo esc_html( (string) get_post_meta( $post_id, '_section_slot', true ) );
				break;
			case 'priority':
				echo esc_html( (string) (int) get_post_meta( $post_id, '_section_priority', true ) );
				break;
			case 'status_meta':
				echo esc_html( (string) get_post_meta( $post_id, '_section_status', true ) );
				break;
		}
	}

	/**
	 * Add duplicate action to section rows.
	 *
	 * @param array<string, string> $actions Existing row actions.
	 * @param \WP_Post              $post Current post.
	 * @return array<string, string>
	 */
	public function register_row_actions( array $actions, $post ) {
		if ( 'theme_section' !== $post->post_type || ! current_user_can( 'edit_post', $post->ID ) ) {
			return $actions;
		}

		$url = wp_nonce_url(
			admin_url( 'admin.php?action=starterkit_duplicate_section&post=' . $post->ID ),
			'starterkit_duplicate_section_' . $post->ID
		);

		$actions['starterkit_duplicate'] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Duplicate', 'starterkit' ) . '</a>';

		return $actions;
	}

	/**
	 * Duplicate a section post and its meta.
	 *
	 * @return void
	 */
	public function duplicate_section() {
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;

		if ( ! $post_id || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'starterkit_duplicate_section_' . $post_id ) ) {
			wp_die( esc_html__( 'Invalid section duplication request.', 'starterkit' ) );
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( esc_html__( 'You are not allowed to duplicate this section.', 'starterkit' ) );
		}

		$original = get_post( $post_id );

		if ( ! $original || 'theme_section' !== $original->post_type ) {
			wp_die( esc_html__( 'Section not found.', 'starterkit' ) );
		}

		$new_id = wp_insert_post(
			array(
				'post_type'   => 'theme_section',
				'post_status' => 'draft',
				'post_title'  => $original->post_title . ' ' . __( '(Copy)', 'starterkit' ),
			)
		);

		if ( ! $new_id || is_wp_error( $new_id ) ) {
			wp_die( esc_html__( 'Unable to duplicate the section.', 'starterkit' ) );
		}

		foreach ( get_post_meta( $post_id ) as $key => $values ) {
			foreach ( $values as $value ) {
				add_post_meta( $new_id, $key, maybe_unserialize( $value ) );
			}
		}

		wp_safe_redirect( admin_url( 'post.php?post=' . $new_id . '&action=edit' ) );
		exit;
	}
}
