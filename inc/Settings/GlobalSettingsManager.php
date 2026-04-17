<?php
/**
 * Manage global theme settings.
 *
 * @package StarterKit
 */

namespace StarterKit\Settings;

class GlobalSettingsManager {
	/**
	 * Settings option key.
	 */
	const OPTION_KEY = 'starterkit_global_settings';

	/**
	 * Default settings.
	 *
	 * @return array<string, mixed>
	 */
	public function defaults() {
		return array(
			'logo_id'            => 0,
			'favicon_id'         => 0,
			'header_layout'      => 'header-1',
			'footer_layout'      => 'footer-1',
			'product_layout'     => 'product-layout-1',
			'archive_layout'     => 'archive-layout-1',
			'heading_font'       => 'Poppins',
			'body_font'          => 'Inter',
			'body_font_size'     => '16px',
			'body_font_weight'   => '400',
			'body_line_height'   => '1.6',
			'heading_font_weight' => '700',
			'heading_text_transform' => 'none',
			'heading_letter_spacing' => '0',
			'nav_font_size'      => '16px',
			'nav_font_weight'    => '500',
			'nav_text_transform' => 'none',
			'button_font_size'   => '16px',
			'button_font_weight' => '600',
			'button_text_transform' => 'none',
			'eyebrow_font_size'  => '12px',
			'eyebrow_font_weight' => '700',
			'eyebrow_text_transform' => 'uppercase',
			'eyebrow_letter_spacing' => '0.12em',
			'color_primary'      => '#111827',
			'color_secondary'    => '#334155',
			'color_accent'       => '#f59e0b',
			'color_background'   => '#ffffff',
			'button_style'       => 'solid',
			'container_width'    => '1280px',
			'border_radius'      => '12px',
			'shadow_preset'      => '0 10px 30px rgba(15, 23, 42, 0.08)',
			'element_gap'        => '16px',
			'component_gap'      => '24px',
			'content_gap'        => '32px',
			'spacing_scale'      => '80px',
			'lazy_load_images'   => '1',
			'disable_emojis'     => '1',
			'disable_block_css'  => '1',
			'disable_mediaelement' => '1',
			'disable_jquery_migrate' => '1',
			'preconnect_hints'       => '1',
			'preload_fonts'          => '1',
			'async_images'           => '1',
			'disable_cart_fragments'  => '1',
			'disable_wc_block_css'   => '1',
			'disable_oembed'         => '1',
			'header_scripts'     => '',
			'footer_scripts'     => '',
			'body_scripts_top'   => '',
			'body_scripts_bottom' => '',
		);
	}

	/**
	 * Preset spacing options.
	 *
	 * @return array<string, array<string, string>>
	 */
	public function spacing_options() {
		return array(
			'8px'  => array(
				'id'          => '8px',
				'label'       => __( 'XS', 'starterkit' ),
				'description' => __( 'Very tight spacing.', 'starterkit' ),
			),
			'12px' => array(
				'id'          => '12px',
				'label'       => __( 'SM', 'starterkit' ),
				'description' => __( 'Compact spacing.', 'starterkit' ),
			),
			'16px' => array(
				'id'          => '16px',
				'label'       => __( 'MD', 'starterkit' ),
				'description' => __( 'Balanced default spacing.', 'starterkit' ),
			),
			'24px' => array(
				'id'          => '24px',
				'label'       => __( 'LG', 'starterkit' ),
				'description' => __( 'Relaxed spacing.', 'starterkit' ),
			),
			'32px' => array(
				'id'          => '32px',
				'label'       => __( 'XL', 'starterkit' ),
				'description' => __( 'Large spacing for spacious layouts.', 'starterkit' ),
			),
			'40px' => array(
				'id'          => '40px',
				'label'       => __( '2XL', 'starterkit' ),
				'description' => __( 'Very open spacing.', 'starterkit' ),
			),
		);
	}

	/**
	 * Preset border radius options.
	 *
	 * @return array<string, array<string, string>>
	 */
	public function radius_options() {
		return array(
			'0px'  => array(
				'id'          => '0px',
				'label'       => __( 'None', 'starterkit' ),
				'description' => __( 'Sharp corners for a clean, rigid look.', 'starterkit' ),
			),
			'6px'  => array(
				'id'          => '6px',
				'label'       => __( 'Small', 'starterkit' ),
				'description' => __( 'Subtle rounding with a restrained feel.', 'starterkit' ),
			),
			'12px' => array(
				'id'          => '12px',
				'label'       => __( 'Medium', 'starterkit' ),
				'description' => __( 'Balanced default radius for most storefronts.', 'starterkit' ),
			),
			'18px' => array(
				'id'          => '18px',
				'label'       => __( 'Large', 'starterkit' ),
				'description' => __( 'Softer corners for a friendlier interface.', 'starterkit' ),
			),
			'24px' => array(
				'id'          => '24px',
				'label'       => __( 'Extra Large', 'starterkit' ),
				'description' => __( 'Highly rounded cards and content panels.', 'starterkit' ),
			),
			'999px' => array(
				'id'          => '999px',
				'label'       => __( 'Pill', 'starterkit' ),
				'description' => __( 'Maximum rounding where components allow it.', 'starterkit' ),
			),
		);
	}

	/**
	 * Preset font weight options.
	 *
	 * @return array<string, array<string, string>>
	 */
	public function font_weight_options() {
		return array(
			'400' => array(
				'id'          => '400',
				'label'       => __( '400 - Regular', 'starterkit' ),
				'description' => __( 'Default body weight.', 'starterkit' ),
			),
			'500' => array(
				'id'          => '500',
				'label'       => __( '500 - Medium', 'starterkit' ),
				'description' => __( 'A slightly stronger emphasis.', 'starterkit' ),
			),
			'600' => array(
				'id'          => '600',
				'label'       => __( '600 - Semibold', 'starterkit' ),
				'description' => __( 'Common for navigation and buttons.', 'starterkit' ),
			),
			'700' => array(
				'id'          => '700',
				'label'       => __( '700 - Bold', 'starterkit' ),
				'description' => __( 'Strong visual emphasis.', 'starterkit' ),
			),
			'800' => array(
				'id'          => '800',
				'label'       => __( '800 - Extra Bold', 'starterkit' ),
				'description' => __( 'High-contrast display weight.', 'starterkit' ),
			),
		);
	}

	/**
	 * Preset text transform options.
	 *
	 * @return array<string, array<string, string>>
	 */
	public function text_transform_options() {
		return array(
			'none' => array(
				'id'          => 'none',
				'label'       => __( 'None', 'starterkit' ),
				'description' => __( 'Keep the original casing.', 'starterkit' ),
			),
			'uppercase' => array(
				'id'          => 'uppercase',
				'label'       => __( 'Uppercase', 'starterkit' ),
				'description' => __( 'Convert all letters to uppercase.', 'starterkit' ),
			),
			'lowercase' => array(
				'id'          => 'lowercase',
				'label'       => __( 'Lowercase', 'starterkit' ),
				'description' => __( 'Convert all letters to lowercase.', 'starterkit' ),
			),
			'capitalize' => array(
				'id'          => 'capitalize',
				'label'       => __( 'Capitalize', 'starterkit' ),
				'description' => __( 'Uppercase the first letter of each word.', 'starterkit' ),
			),
		);
	}

	/**
	 * Curated Google Font options.
	 *
	 * @return array<string, array<string, string>>
	 */
	public function google_font_options() {
		return array(
			'Inter'      => array(
				'id'          => 'Inter',
				'label'       => 'Inter',
				'stack'       => '"Inter", sans-serif',
				'query'       => 'Inter:wght@400;500;600;700',
				'description' => __( 'Neutral modern sans-serif for body copy.', 'starterkit' ),
			),
			'Poppins'    => array(
				'id'          => 'Poppins',
				'label'       => 'Poppins',
				'stack'       => '"Poppins", sans-serif',
				'query'       => 'Poppins:wght@400;500;600;700',
				'description' => __( 'Rounded geometric sans-serif for bold headings.', 'starterkit' ),
			),
			'Manrope'    => array(
				'id'          => 'Manrope',
				'label'       => 'Manrope',
				'stack'       => '"Manrope", sans-serif',
				'query'       => 'Manrope:wght@400;500;600;700;800',
				'description' => __( 'Clean high-contrast sans-serif suited to modern commerce UIs.', 'starterkit' ),
			),
			'Outfit'     => array(
				'id'          => 'Outfit',
				'label'       => 'Outfit',
				'stack'       => '"Outfit", sans-serif',
				'query'       => 'Outfit:wght@400;500;600;700',
				'description' => __( 'Friendly display-oriented sans-serif.', 'starterkit' ),
			),
			'DM Sans'    => array(
				'id'          => 'DM Sans',
				'label'       => 'DM Sans',
				'stack'       => '"DM Sans", sans-serif',
				'query'       => 'DM+Sans:wght@400;500;700',
				'description' => __( 'Readable grotesk with a polished editorial feel.', 'starterkit' ),
			),
			'Plus Jakarta Sans' => array(
				'id'          => 'Plus Jakarta Sans',
				'label'       => 'Plus Jakarta Sans',
				'stack'       => '"Plus Jakarta Sans", sans-serif',
				'query'       => 'Plus+Jakarta+Sans:wght@400;500;600;700;800',
				'description' => __( 'Versatile contemporary UI font.', 'starterkit' ),
			),
			'Playfair Display' => array(
				'id'          => 'Playfair Display',
				'label'       => 'Playfair Display',
				'stack'       => '"Playfair Display", serif',
				'query'       => 'Playfair+Display:wght@400;500;600;700',
				'description' => __( 'Elegant serif for premium headline treatment.', 'starterkit' ),
			),
			'Cormorant Garamond' => array(
				'id'          => 'Cormorant Garamond',
				'label'       => 'Cormorant Garamond',
				'stack'       => '"Cormorant Garamond", serif',
				'query'       => 'Cormorant+Garamond:wght@400;500;600;700',
				'description' => __( 'Classic serif with a more editorial personality.', 'starterkit' ),
			),
			'Space Grotesk' => array(
				'id'          => 'Space Grotesk',
				'label'       => 'Space Grotesk',
				'stack'       => '"Space Grotesk", sans-serif',
				'query'       => 'Space+Grotesk:wght@400;500;600;700',
				'description' => __( 'Technical and distinctive sans-serif for modern brands.', 'starterkit' ),
			),
			'Archivo'    => array(
				'id'          => 'Archivo',
				'label'       => 'Archivo',
				'stack'       => '"Archivo", sans-serif',
				'query'       => 'Archivo:wght@400;500;600;700',
				'description' => __( 'Strong grotesk with good legibility in commerce layouts.', 'starterkit' ),
			),
		);
	}

	/**
	 * Resolve a selected font to a CSS font-family stack.
	 *
	 * @param string $font_id Font identifier.
	 * @return string
	 */
	public function font_stack( $font_id ) {
		$options = $this->google_font_options();

		return isset( $options[ $font_id ]['stack'] ) ? $options[ $font_id ]['stack'] : '"Inter", sans-serif';
	}

	/**
	 * Preset container width options.
	 *
	 * @return array<string, array<string, string>>
	 */
	public function container_width_options() {
		return array(
			'960px'  => array(
				'id'          => '960px',
				'label'       => __( 'Narrow', 'starterkit' ),
				'description' => __( 'Best for editorial or focused landing pages.', 'starterkit' ),
			),
			'1140px' => array(
				'id'          => '1140px',
				'label'       => __( 'Default', 'starterkit' ),
				'description' => __( 'Balanced content width for most storefront layouts.', 'starterkit' ),
			),
			'1280px' => array(
				'id'          => '1280px',
				'label'       => __( 'Wide', 'starterkit' ),
				'description' => __( 'More breathing room for catalog and merchandising pages.', 'starterkit' ),
			),
			'1440px' => array(
				'id'          => '1440px',
				'label'       => __( 'Extra Wide', 'starterkit' ),
				'description' => __( 'Useful for large screens and image-heavy layouts.', 'starterkit' ),
			),
			'100%'   => array(
				'id'          => '100%',
				'label'       => __( 'Full Width', 'starterkit' ),
				'description' => __( 'Lets sections span the full viewport width.', 'starterkit' ),
			),
		);
	}

	/**
	 * Return merged settings.
	 *
	 * @return array<string, mixed>
	 */
	public function all() {
		$saved = get_option( self::OPTION_KEY, array() );

		return wp_parse_args( is_array( $saved ) ? $saved : array(), $this->defaults() );
	}

	/**
	 * Get one setting.
	 *
	 * @param string $key Setting key.
	 * @param mixed  $default Optional fallback.
	 * @return mixed
	 */
	public function get( $key, $default = null ) {
		$settings = $this->all();

		return array_key_exists( $key, $settings ) ? $settings[ $key ] : $default;
	}

	/**
	 * Sanitize saved values.
	 *
	 * @param mixed $input Raw settings.
	 * @return array<string, mixed>
	 */
	public function sanitize( $input ) {
		$input    = is_array( $input ) ? $input : array();
		$saved    = get_option( self::OPTION_KEY, array() );
		$saved    = is_array( $saved ) ? $saved : array();
		$merged   = array_merge( $saved, $input );
		$defaults = $this->defaults();
		$output   = array();

		foreach ( $defaults as $key => $value ) {
			$raw = isset( $merged[ $key ] ) ? $merged[ $key ] : $value;

			switch ( $key ) {
				case 'logo_id':
				case 'favicon_id':
					$output[ $key ] = absint( $raw );
					break;
				case 'color_primary':
				case 'color_secondary':
				case 'color_accent':
				case 'color_background':
					$output[ $key ] = sanitize_hex_color( $raw ) ? sanitize_hex_color( $raw ) : $value;
					break;
				case 'container_width':
					$options = $this->container_width_options();
					$raw     = sanitize_text_field( (string) $raw );
					$output[ $key ] = isset( $options[ $raw ] ) ? $raw : $value;
					break;
				case 'border_radius':
					$options = $this->radius_options();
					$raw     = sanitize_text_field( (string) $raw );
					$output[ $key ] = isset( $options[ $raw ] ) ? $raw : $value;
					break;
				case 'element_gap':
				case 'component_gap':
				case 'content_gap':
					$options = $this->spacing_options();
					$raw     = sanitize_text_field( (string) $raw );
					$output[ $key ] = isset( $options[ $raw ] ) ? $raw : $value;
					break;
				case 'heading_font':
				case 'body_font':
					$options = $this->google_font_options();
					$raw     = sanitize_text_field( (string) $raw );
					$output[ $key ] = isset( $options[ $raw ] ) ? $raw : $value;
					break;
				case 'body_font_size':
				case 'body_line_height':
				case 'heading_letter_spacing':
				case 'nav_font_size':
				case 'button_font_size':
				case 'eyebrow_font_size':
				case 'eyebrow_letter_spacing':
					$output[ $key ] = $this->sanitize_css_measurement( $raw, (string) $value );
					break;
				case 'body_font_weight':
				case 'heading_font_weight':
				case 'nav_font_weight':
				case 'button_font_weight':
				case 'eyebrow_font_weight':
					$output[ $key ] = $this->sanitize_font_weight( $raw, (string) $value );
					break;
				case 'heading_text_transform':
				case 'nav_text_transform':
				case 'button_text_transform':
				case 'eyebrow_text_transform':
					$output[ $key ] = $this->sanitize_text_transform( $raw, (string) $value );
					break;
				case 'lazy_load_images':
				case 'disable_emojis':
				case 'disable_block_css':
				case 'disable_mediaelement':
				case 'disable_jquery_migrate':
				case 'preconnect_hints':
				case 'preload_fonts':
				case 'async_images':
				case 'disable_cart_fragments':
				case 'disable_wc_block_css':
				case 'disable_oembed':
					$output[ $key ] = ! empty( $raw ) ? '1' : '0';
					break;
				case 'header_scripts':
				case 'footer_scripts':
				case 'body_scripts_top':
				case 'body_scripts_bottom':
					$output[ $key ] = $this->sanitize_code_snippet( $raw );
					break;
				default:
					$output[ $key ] = sanitize_text_field( (string) $raw );
					break;
			}
		}

		return $output;
	}

	/**
	 * Sanitize script/code snippets saved by administrators.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	protected function sanitize_code_snippet( $value ) {
		$value = is_string( $value ) ? $value : '';
		$value = trim( wp_unslash( $value ) );

		if ( current_user_can( 'unfiltered_html' ) ) {
			return $value;
		}

		return wp_kses_post( $value );
	}

	/**
	 * Sanitize CSS measurement-like values used by design tokens.
	 *
	 * @param mixed  $value Raw value.
	 * @param string $default Fallback value.
	 * @return string
	 */
	protected function sanitize_css_measurement( $value, $default ) {
		$value = sanitize_text_field( (string) $value );

		if ( preg_match( '/^-?(?:\d+|\d*\.\d+)(?:px|rem|em|%|vh|vw)?$/', $value ) ) {
			return $value;
		}

		return $default;
	}

	/**
	 * Sanitize font-weight values.
	 *
	 * @param mixed  $value Raw value.
	 * @param string $default Fallback value.
	 * @return string
	 */
	protected function sanitize_font_weight( $value, $default ) {
		$value   = sanitize_text_field( (string) $value );
		$allowed = array( '100', '200', '300', '400', '500', '600', '700', '800', '900', 'normal', 'bold' );

		return in_array( $value, $allowed, true ) ? $value : $default;
	}

	/**
	 * Sanitize text-transform values.
	 *
	 * @param mixed  $value Raw value.
	 * @param string $default Fallback value.
	 * @return string
	 */
	protected function sanitize_text_transform( $value, $default ) {
		$value   = sanitize_text_field( (string) $value );
		$allowed = array( 'none', 'uppercase', 'lowercase', 'capitalize' );

		return in_array( $value, $allowed, true ) ? $value : $default;
	}
}
