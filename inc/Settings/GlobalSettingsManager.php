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
			'master_layout'      => 'master-default',
			'product_layout'     => 'product-layout-1',
			'archive_layout'     => 'archive-layout-1',
			'heading_font'       => 'Poppins',
			'body_font'          => 'Inter',
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
		$defaults = $this->defaults();
		$output   = array();

		foreach ( $defaults as $key => $value ) {
			$raw = isset( $input[ $key ] ) ? $input[ $key ] : $value;

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
				default:
					$output[ $key ] = sanitize_text_field( (string) $raw );
					break;
			}
		}

		return $output;
	}
}
