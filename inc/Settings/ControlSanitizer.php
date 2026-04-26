<?php
/**
 * Shared sanitizer for schema-driven controls.
 *
 * @package StarterKit
 */

namespace StarterKit\Settings;

class ControlSanitizer {
	/**
	 * Supported control types.
	 *
	 * @var string[]
	 */
	protected $allowed_types = array( 'text', 'textarea', 'toggle', 'checkbox', 'range', 'number', 'color', 'image', 'url', 'select', 'repeater' );

	/**
	 * Control meta keys that should remain strings.
	 *
	 * @var string[]
	 */
	protected $string_meta_keys = array( 'label', 'placeholder', 'help', 'min', 'max', 'step', 'rows', 'accept', 'item_label', 'unit', 'target', 'options_source', 'preview_strategy' );

	/**
	 * Extract defaults from a control schema list.
	 *
	 * @param array<int, array<string, mixed>> $schema Control schema.
	 * @return array<string, mixed>
	 */
	public function defaults_from_schema( array $schema ) {
		$defaults = array();

		foreach ( $this->normalize_schema( $schema ) as $control ) {
			$id = isset( $control['id'] ) ? sanitize_key( (string) $control['id'] ) : '';

			if ( '' === $id ) {
				continue;
			}

			$defaults[ $id ] = isset( $control['default'] ) ? $control['default'] : '';
		}

		return $defaults;
	}

	/**
	 * Normalize a list-based control schema.
	 *
	 * @param array<int, mixed> $schema Raw control schema.
	 * @return array<int, array<string, mixed>>
	 */
	public function normalize_schema( array $schema ) {
		$normalized = array();

		foreach ( $schema as $control ) {
			if ( ! is_array( $control ) ) {
				continue;
			}

			$normalized_control = $this->normalize_control( $control );

			if ( ! $normalized_control ) {
				continue;
			}

			$normalized[] = $normalized_control;
		}

		return $normalized;
	}

	/**
	 * Normalize a manifest settings object keyed by control id.
	 *
	 * @param array<string, mixed> $settings Raw settings object.
	 * @return array<int, array<string, mixed>>
	 */
	public function normalize_manifest_settings( array $settings ) {
		$schema = array();

		foreach ( $settings as $setting_id => $control ) {
			if ( ! is_array( $control ) ) {
				continue;
			}

			$control['id'] = $setting_id;
			$normalized    = $this->normalize_control( $control, (string) $setting_id );

			if ( ! $normalized ) {
				continue;
			}

			$schema[] = $normalized;
		}

		return $schema;
	}

	/**
	 * Normalize one control definition.
	 *
	 * @param array<string, mixed> $control Raw control.
	 * @param string               $fallback_id Optional fallback id.
	 * @return array<string, mixed>|null
	 */
	public function normalize_control( array $control, $fallback_id = '' ) {
		$id = isset( $control['id'] ) ? sanitize_key( (string) $control['id'] ) : sanitize_key( (string) $fallback_id );

		if ( '' === $id ) {
			return null;
		}

		$type = isset( $control['type'] ) ? sanitize_key( (string) $control['type'] ) : 'text';

		if ( ! in_array( $type, $this->allowed_types, true ) ) {
			$type = 'text';
		}

		$normalized = array(
			'id'      => $id,
			'type'    => $type,
			'label'   => isset( $control['label'] ) ? sanitize_text_field( (string) $control['label'] ) : ucwords( str_replace( '_', ' ', $id ) ),
			'default' => isset( $control['default'] ) ? $control['default'] : '',
			'options' => isset( $control['options'] ) && is_array( $control['options'] ) ? $this->normalize_control_options( $control['options'] ) : array(),
		);

		foreach ( $this->string_meta_keys as $meta_key ) {
			if ( isset( $control[ $meta_key ] ) ) {
				$normalized[ $meta_key ] = sanitize_text_field( (string) $control[ $meta_key ] );
			}
		}

		if ( isset( $control['multiple'] ) ) {
			$normalized['multiple'] = ! empty( $control['multiple'] );
		}

		if ( 'repeater' === $type ) {
			$normalized['fields']  = isset( $control['fields'] ) && is_array( $control['fields'] ) ? $this->normalize_schema( $control['fields'] ) : array();
			$normalized['default'] = $this->normalize_repeater_default( $normalized['default'], $normalized );
		} else {
			$normalized['default'] = $this->sanitize_control_value( $normalized, $normalized['default'] );
		}

		return $normalized;
	}

	/**
	 * Sanitize a map of settings against a schema.
	 *
	 * @param array<int, array<string, mixed>> $schema Control schema.
	 * @param array<string, mixed>             $raw Raw values.
	 * @return array<string, mixed>
	 */
	public function sanitize_settings( array $schema, array $raw ) {
		$schema   = $this->normalize_schema( $schema );
		$defaults = $this->defaults_from_schema( $schema );
		$output   = array();

		foreach ( $schema as $control ) {
			$id = isset( $control['id'] ) ? sanitize_key( (string) $control['id'] ) : '';

			if ( '' === $id ) {
				continue;
			}

			$value = array_key_exists( $id, $raw ) ? $raw[ $id ] : ( isset( $defaults[ $id ] ) ? $defaults[ $id ] : '' );

			if ( 'repeater' === $control['type'] ) {
				$output[ $id ] = $this->normalize_repeater_default( $value, $control );
				continue;
			}

			$output[ $id ] = $this->sanitize_control_value( $control, $value );
		}

		return $output;
	}

	/**
	 * Sanitize one control value.
	 *
	 * @param array<string, mixed> $control Control schema.
	 * @param mixed                $value Raw value.
	 * @return mixed
	 */
	public function sanitize_control_value( array $control, $value ) {
		$type    = isset( $control['type'] ) ? sanitize_key( (string) $control['type'] ) : 'text';
		$default = isset( $control['default'] ) ? $control['default'] : '';

		switch ( $type ) {
			case 'textarea':
				return sanitize_textarea_field( (string) $value );
			case 'toggle':
			case 'checkbox':
				return ! empty( $value ) && 'false' !== (string) $value ? '1' : '0';
			case 'range':
			case 'number':
				return $this->sanitize_number( $value, $control, $default );
			case 'color':
				$color = sanitize_hex_color( (string) $value );

				return $color ? $color : ( sanitize_hex_color( (string) $default ) ? sanitize_hex_color( (string) $default ) : '' );
			case 'image':
				return (string) absint( $value );
			case 'url':
				return esc_url_raw( (string) $value );
			case 'select':
				return $this->sanitize_select( $value, $control, $default );
			default:
				return sanitize_text_field( (string) $value );
		}
	}

	/**
	 * Normalize select-like control options.
	 *
	 * @param array<int, mixed> $options Raw options.
	 * @return array<int, array<string, string>>
	 */
	public function normalize_control_options( array $options ) {
		$sanitized = array();

		foreach ( $options as $option ) {
			if ( ! is_array( $option ) || ! isset( $option['value'] ) ) {
				continue;
			}

			$sanitized[] = array(
				'value' => sanitize_text_field( (string) $option['value'] ),
				'label' => isset( $option['label'] ) ? sanitize_text_field( (string) $option['label'] ) : sanitize_text_field( (string) $option['value'] ),
			);
		}

		return $sanitized;
	}

	/**
	 * Normalize a repeater default payload.
	 *
	 * @param mixed                $value Raw default.
	 * @param array<string, mixed> $control Repeater control schema.
	 * @return array<int, array<string, mixed>>
	 */
	public function normalize_repeater_default( $value, array $control ) {
		$rows   = is_array( $value ) ? array_values( $value ) : array();
		$fields = isset( $control['fields'] ) && is_array( $control['fields'] ) ? $this->normalize_schema( $control['fields'] ) : array();
		$output = array();

		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$normalized_row = array();

			foreach ( $fields as $field ) {
				$field_id = isset( $field['id'] ) ? (string) $field['id'] : '';

				if ( '' === $field_id ) {
					continue;
				}

				$field_value = isset( $row[ $field_id ] ) ? $row[ $field_id ] : ( isset( $field['default'] ) ? $field['default'] : '' );
				$normalized_row[ $field_id ] = $this->sanitize_control_value( $field, $field_value );
			}

			if ( ! empty( array_filter( $normalized_row, array( $this, 'row_value_is_not_empty' ) ) ) ) {
				$output[] = $normalized_row;
			}
		}

		return $output;
	}

	/**
	 * Determine whether a repeater row value should be considered non-empty.
	 *
	 * @param mixed $value Row value.
	 * @return bool
	 */
	protected function row_value_is_not_empty( $value ) {
		return '' !== (string) $value;
	}

	/**
	 * Sanitize a number/range value with optional bounds.
	 *
	 * @param mixed                $value Raw value.
	 * @param array<string, mixed> $control Control schema.
	 * @param mixed                $default Default value.
	 * @return string
	 */
	protected function sanitize_number( $value, array $control, $default ) {
		$number = is_numeric( $value ) ? (float) $value : ( is_numeric( $default ) ? (float) $default : 0.0 );

		if ( isset( $control['min'] ) && is_numeric( $control['min'] ) ) {
			$number = max( (float) $control['min'], $number );
		}

		if ( isset( $control['max'] ) && is_numeric( $control['max'] ) ) {
			$number = min( (float) $control['max'], $number );
		}

		if ( 0.0 === fmod( $number, 1.0 ) ) {
			return (string) (int) $number;
		}

		return rtrim( rtrim( sprintf( '%.4F', $number ), '0' ), '.' );
	}

	/**
	 * Sanitize a select value.
	 *
	 * @param mixed                $value Raw value.
	 * @param array<string, mixed> $control Control schema.
	 * @param mixed                $default Default value.
	 * @return string
	 */
	protected function sanitize_select( $value, array $control, $default ) {
		$allowed = array();

		foreach ( (array) ( isset( $control['options'] ) ? $control['options'] : array() ) as $option ) {
			if ( isset( $option['value'] ) ) {
				$allowed[] = (string) $option['value'];
			}
		}

		$value = sanitize_text_field( (string) $value );

		if ( in_array( $value, $allowed, true ) ) {
			return $value;
		}

		$default = sanitize_text_field( (string) $default );

		return in_array( $default, $allowed, true ) ? $default : ( isset( $allowed[0] ) ? $allowed[0] : '' );
	}
}
