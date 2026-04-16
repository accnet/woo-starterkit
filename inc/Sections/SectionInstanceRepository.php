<?php
/**
 * Query section instances.
 *
 * @package StarterKit
 */

namespace StarterKit\Sections;

class SectionInstanceRepository {
	/**
	 * Fetch active sections, optionally by slot.
	 *
	 * @param string $slot Slot identifier.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_active_sections( $slot = '' ) {
		$cache_key = 'starterkit_sections_' . md5( (string) $slot );
		$cached    = wp_cache_get( $cache_key, 'starterkit' );

		if ( false !== $cached ) {
			return is_array( $cached ) ? $cached : array();
		}

		$meta_query = array(
			array(
				'key'   => '_section_status',
				'value' => 'active',
			),
		);

		if ( $slot ) {
			$meta_query[] = array(
				'key'   => '_section_slot',
				'value' => $slot,
			);
		}

		$posts = get_posts(
			array(
				'post_type'      => 'theme_section',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'meta_value_num',
				'meta_key'       => '_section_priority',
				'order'          => 'ASC',
				'meta_query'     => $meta_query,
			)
		);

		if ( ! empty( $posts ) ) {
			update_meta_cache( 'post', wp_list_pluck( $posts, 'ID' ) );
		}

		$mapped = array_map( array( $this, 'map_post' ), $posts );

		wp_cache_set( $cache_key, $mapped, 'starterkit', MINUTE_IN_SECONDS * 10 );

		return $mapped;
	}

	/**
	 * Map a WP_Post into render-friendly data.
	 *
	 * @param \WP_Post $post Section post.
	 * @return array<string, mixed>
	 */
	protected function map_post( $post ) {
		return array(
			'id'            => $post->ID,
			'title'         => $post->post_title,
			'type'          => get_post_meta( $post->ID, '_section_type', true ),
			'content'       => $this->decode_json_meta( $post->ID, '_section_content_json' ),
			'style'         => $this->decode_json_meta( $post->ID, '_section_style_json' ),
			'slot'          => get_post_meta( $post->ID, '_section_slot', true ),
			'display_rules' => $this->decode_json_meta( $post->ID, '_section_display_rules_json' ),
			'priority'      => (int) get_post_meta( $post->ID, '_section_priority', true ),
			'status'        => get_post_meta( $post->ID, '_section_status', true ),
		);
	}

	/**
	 * Decode JSON meta into arrays.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key Meta key.
	 * @return array<string, mixed>
	 */
	protected function decode_json_meta( $post_id, $key ) {
		$raw = get_post_meta( $post_id, $key, true );
		$raw = is_string( $raw ) ? $raw : '';
		$val = json_decode( $raw, true );

		return is_array( $val ) ? $val : array();
	}
}
