<?php
/**
 * Theme builder context constants and helpers.
 *
 * @package StarterKit
 */

namespace StarterKit\ThemeBuilder;

class BuilderContext {
	const MASTER  = 'master';
	const PRODUCT = 'product';
	const ARCHIVE = 'archive';

	/**
	 * Return supported contexts.
	 *
	 * @return string[]
	 */
	public function all() {
		return array(
			self::MASTER,
			self::PRODUCT,
			self::ARCHIVE,
		);
	}

	/**
	 * Determine whether a context is valid.
	 *
	 * @param string $context Context id.
	 * @return bool
	 */
	public function is_valid( $context ) {
		return in_array( (string) $context, $this->all(), true );
	}

	/**
	 * Return labels for UI.
	 *
	 * @return array<string, string>
	 */
	public function labels() {
		return array(
			self::MASTER  => __( 'Master Layout', 'starterkit' ),
			self::PRODUCT => __( 'Product Layout', 'starterkit' ),
			self::ARCHIVE => __( 'Archive Layout', 'starterkit' ),
		);
	}
}
