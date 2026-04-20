<?php
/**
 * Resolve preview URLs for builder contexts.
 *
 * @package StarterKit
 */

namespace StarterKit\ThemeBuilder;

class PreviewContextResolver {
	/**
	 * Builder mode.
	 *
	 * @var BuilderMode
	 */
	protected $builder_mode;

	/**
	 * Constructor.
	 *
	 * @param BuilderMode $builder_mode Builder mode.
	 */
	public function __construct( BuilderMode $builder_mode ) {
		$this->builder_mode = $builder_mode;
	}

	/**
	 * Return preview URLs for all builder contexts.
	 *
	 * @return array<string, string>
	 */
	public function all() {
		return array(
			BuilderContext::MASTER  => $this->build_url( $this->master_preview_url(), BuilderContext::MASTER ),
			BuilderContext::PRODUCT => $this->build_url( $this->product_preview_url(), BuilderContext::PRODUCT ),
			BuilderContext::ARCHIVE => $this->build_url( $this->archive_preview_url(), BuilderContext::ARCHIVE ),
		);
	}

	/**
	 * Return one preview URL.
	 *
	 * @param string $context Builder context.
	 * @return string
	 */
	public function get( $context ) {
		$urls = $this->all();

		return isset( $urls[ $context ] ) ? $urls[ $context ] : $urls[ BuilderContext::MASTER ];
	}

	/**
	 * Build a preview URL with builder params.
	 *
	 * @param string $base_url Base URL.
	 * @param string $context Builder context.
	 * @return string
	 */
	protected function build_url( $base_url, $context ) {
		return add_query_arg(
			array(
				'starterkit_builder'         => '1',
				'starterkit_builder_context' => $context,
				'starterkit_builder_device'  => $this->builder_mode->get_device_mode(),
				'starterkit_builder_token'   => wp_create_nonce( 'starterkit_theme_builder_preview' ),
			),
			$base_url
		);
	}

	/**
	 * Resolve the master preview URL.
	 *
	 * @return string
	 */
	protected function master_preview_url() {
		return home_url( '/' );
	}

	/**
	 * Resolve the product preview URL.
	 *
	 * @return string
	 */
	protected function product_preview_url() {
		$product_posts = get_posts(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);

		if ( ! empty( $product_posts ) ) {
			return (string) get_permalink( (int) $product_posts[0] );
		}

		return home_url( '/' );
	}

	/**
	 * Resolve the archive preview URL.
	 *
	 * @return string
	 */
	protected function archive_preview_url() {
		if ( function_exists( 'wc_get_page_permalink' ) ) {
			$url = wc_get_page_permalink( 'shop' );

			if ( $url ) {
				return $url;
			}
		}

		return home_url( '/' );
	}
}
