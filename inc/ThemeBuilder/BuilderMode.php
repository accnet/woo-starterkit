<?php
/**
 * Detect whether a request is running in theme builder preview mode.
 *
 * @package StarterKit
 */

namespace StarterKit\ThemeBuilder;

class BuilderMode {
	/**
	 * Builder context helper.
	 *
	 * @var BuilderContext
	 */
	protected $builder_context;

	/**
	 * Constructor.
	 *
	 * @param BuilderContext $builder_context Builder context helper.
	 */
	public function __construct( BuilderContext $builder_context ) {
		$this->builder_context = $builder_context;
	}

	/**
	 * Determine whether preview builder mode is active.
	 *
	 * @return bool
	 */
	public function is_builder_mode() {
		return isset( $_GET['starterkit_builder'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['starterkit_builder'] ) );
	}

	/**
	 * Resolve the requested builder context.
	 *
	 * @return string
	 */
	public function get_context() {
		$context = isset( $_GET['starterkit_builder_context'] ) ? sanitize_key( wp_unslash( $_GET['starterkit_builder_context'] ) ) : BuilderContext::MASTER;

		return $this->builder_context->is_valid( $context ) ? $context : BuilderContext::MASTER;
	}

	/**
	 * Resolve the preview device mode.
	 *
	 * @return string
	 */
	public function get_device_mode() {
		$mode = isset( $_GET['starterkit_builder_device'] ) ? sanitize_key( wp_unslash( $_GET['starterkit_builder_device'] ) ) : 'desktop';

		return in_array( $mode, array( 'desktop', 'tablet', 'mobile' ), true ) ? $mode : 'desktop';
	}
}
