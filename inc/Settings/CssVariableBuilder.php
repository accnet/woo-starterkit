<?php
/**
 * Helper for safe inline CSS custom properties.
 *
 * @package StarterKit
 */

namespace StarterKit\Settings;

class CssVariableBuilder {
	/**
	 * Build an inline style string from CSS variable definitions.
	 *
	 * @param array<string, mixed> $variables CSS variables.
	 * @return string
	 */
	public static function build( array $variables ) {
		$output = array();

		foreach ( $variables as $name => $definition ) {
			$name = (string) $name;

			if ( ! preg_match( '/^--[a-zA-Z0-9_-]+$/', $name ) ) {
				continue;
			}

			$value = is_array( $definition ) && array_key_exists( 'value', $definition ) ? $definition['value'] : $definition;
			$unit  = is_array( $definition ) && isset( $definition['unit'] ) ? sanitize_key( (string) $definition['unit'] ) : '';

			if ( '' !== $unit && is_numeric( $value ) ) {
				$value = (string) $value . $unit;
			}

			$value = self::sanitize_css_value( (string) $value );

			if ( '' === $value ) {
				continue;
			}

			$output[] = $name . ':' . $value;
		}

		return implode( ';', $output );
	}

	/**
	 * Sanitize a CSS value for inline custom property output.
	 *
	 * @param string $value Raw CSS value.
	 * @return string
	 */
	protected static function sanitize_css_value( $value ) {
		$value = html_entity_decode( $value, ENT_QUOTES, 'UTF-8' );

		return trim( preg_replace( '/[^a-zA-Z0-9#%(),.\'"\\-\\s]/', '', $value ) );
	}
}
