<?php
/**
 * Resolve active layout presets from settings.
 *
 * @package StarterKit
 */

namespace StarterKit\Layouts;

use StarterKit\Settings\GlobalSettingsManager;

class LayoutResolver {
	/**
	 * Layout registry.
	 *
	 * @var LayoutRegistry
	 */
	protected $registry;

	/**
	 * Settings manager.
	 *
	 * @var GlobalSettingsManager
	 */
	protected $settings;

	/**
	 * Constructor.
	 *
	 * @param LayoutRegistry         $registry Layout registry.
	 * @param GlobalSettingsManager $settings Settings manager.
	 */
	public function __construct( LayoutRegistry $registry, GlobalSettingsManager $settings ) {
		$this->registry = $registry;
		$this->settings = $settings;
	}

	/**
	 * Resolve a layout by type.
	 *
	 * @param string $type Layout type.
	 * @return array<string, mixed>|null
	 */
	public function resolve( $type ) {
		$map = array(
			'header'  => 'header_layout',
			'footer'  => 'footer_layout',
			'product' => 'product_layout',
			'archive' => 'archive_layout',
		);

		if ( ! isset( $map[ $type ] ) ) {
			return null;
		}

		return $this->registry->get( $this->settings->get( $map[ $type ] ) );
	}

	/**
	 * Determine whether a slot is supported by the active layouts.
	 *
	 * @param string               $slot_name Slot identifier.
	 * @param array<string, mixed> $context Context.
	 * @return bool
	 */
	public function is_slot_supported( $slot_name, array $context = array() ) {
		$candidates = array();

		if ( 0 === strpos( $slot_name, 'header_' ) ) {
			$candidates = array( 'header' );
		} elseif ( 0 === strpos( $slot_name, 'footer_' ) ) {
			$candidates = array( 'footer' );
		} elseif ( 0 === strpos( $slot_name, 'product_' ) ) {
			$candidates = array( 'product' );
		} elseif ( 0 === strpos( $slot_name, 'archive_' ) ) {
			$candidates = array( 'archive' );
		}

		foreach ( $candidates as $type ) {
			$layout = $this->resolve( $type );

			if ( $layout && ! empty( $layout['slots'] ) && in_array( $slot_name, $layout['slots'], true ) ) {
				return true;
			}
		}

		return in_array( $slot_name, array( 'home_after_header', 'home_before_content', 'home_after_content', 'home_before_footer' ), true );
	}
}
