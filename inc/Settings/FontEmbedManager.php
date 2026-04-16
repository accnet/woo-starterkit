<?php
/**
 * Download selected Google Fonts into the theme for local embedding.
 *
 * @package StarterKit
 */

namespace StarterKit\Settings;

class FontEmbedManager {
	/**
	 * Manifest option key.
	 */
	const OPTION_KEY = 'starterkit_embedded_fonts_manifest';

	/**
	 * Settings manager.
	 *
	 * @var GlobalSettingsManager
	 */
	protected $settings;

	/**
	 * Constructor.
	 *
	 * @param GlobalSettingsManager $settings Settings manager.
	 */
	public function __construct( GlobalSettingsManager $settings ) {
		$this->settings = $settings;

		add_action( 'admin_post_starterkit_embed_fonts', array( $this, 'handle_embed_request' ) );
	}

	/**
	 * Handle admin request for embedding fonts locally.
	 *
	 * @return void
	 */
	public function handle_embed_request() {
		if ( ! current_user_can( 'manage_options' ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['starterkit_embed_fonts_nonce'] ?? '' ) ), 'starterkit_embed_fonts' ) ) {
			wp_die( esc_html__( 'Invalid font embed request.', 'starterkit' ) );
		}

		$result = $this->embed_selected_fonts();
		$notice = $result ? 'font_embed_success' : 'font_embed_failed';

		wp_safe_redirect( admin_url( 'admin.php?page=starterkit-theme-builder&tab=branding&starterkit_notice=' . $notice ) );
		exit;
	}

	/**
	 * Embed currently selected fonts into the theme.
	 *
	 * @return bool
	 */
	public function embed_selected_fonts() {
		$font_ids = array_values( array_unique( $this->selected_font_ids() ) );
		$options  = $this->settings->google_font_options();

		if ( empty( $font_ids ) ) {
			return false;
		}

		$fonts_dir = trailingslashit( get_template_directory() ) . 'assets/fonts/generated/';
		$css_dir   = trailingslashit( get_template_directory() ) . 'assets/css/';
		$css_file  = $css_dir . 'generated-fonts.css';

		wp_mkdir_p( $fonts_dir );
		wp_mkdir_p( $css_dir );

		$css_output = "/* Generated local Google Fonts */\n";

		foreach ( $font_ids as $font_id ) {
			if ( empty( $options[ $font_id ]['query'] ) ) {
				continue;
			}

			$remote_css = $this->fetch_google_font_css( (string) $options[ $font_id ]['query'] );

			if ( ! $remote_css ) {
				return false;
			}

			$css_output .= $this->localize_font_css( $remote_css, $fonts_dir );
		}

		if ( empty( trim( $css_output ) ) ) {
			return false;
		}

		$written = file_put_contents( $css_file, $css_output );

		if ( false === $written ) {
			return false;
		}

		update_option(
			self::OPTION_KEY,
			array(
				'fonts'        => $font_ids,
				'css_relative' => 'assets/css/generated-fonts.css',
				'updated_at'   => time(),
			)
		);

		return true;
	}

	/**
	 * Return current embed manifest.
	 *
	 * @return array<string, mixed>
	 */
	public function manifest() {
		$manifest = get_option( self::OPTION_KEY, array() );

		return is_array( $manifest ) ? $manifest : array();
	}

	/**
	 * Determine whether local embedded fonts are usable for current settings.
	 *
	 * @return bool
	 */
	public function has_current_embed() {
		$manifest = $this->manifest();
		$fonts    = isset( $manifest['fonts'] ) && is_array( $manifest['fonts'] ) ? array_values( $manifest['fonts'] ) : array();
		$current  = array_values( array_unique( $this->selected_font_ids() ) );
		$relative = isset( $manifest['css_relative'] ) ? (string) $manifest['css_relative'] : '';

		sort( $fonts );
		sort( $current );

		return ! empty( $relative ) && $fonts === $current && file_exists( trailingslashit( get_template_directory() ) . $relative );
	}

	/**
	 * Get local generated CSS URL when available.
	 *
	 * @return string
	 */
	public function local_css_url() {
		$manifest = $this->manifest();
		$relative = isset( $manifest['css_relative'] ) ? (string) $manifest['css_relative'] : '';

		if ( ! $relative ) {
			return '';
		}

		return trailingslashit( get_template_directory_uri() ) . ltrim( $relative, '/' );
	}

	/**
	 * Fetch CSS from Google Fonts.
	 *
	 * @param string $query Font query string.
	 * @return string
	 */
	protected function fetch_google_font_css( $query ) {
		$url = add_query_arg(
			array(
				'family'  => $query,
				'display' => 'swap',
			),
			'https://fonts.googleapis.com/css2'
		);

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 30,
				'headers' => array(
					// Request woff2 sources from the CSS endpoint.
					'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0 Safari/537.36',
				),
			)
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return '';
		}

		return (string) wp_remote_retrieve_body( $response );
	}

	/**
	 * Convert remote Google Fonts CSS into local @font-face rules.
	 *
	 * @param string $css Remote CSS.
	 * @param string $fonts_dir Local fonts directory.
	 * @return string
	 */
	protected function localize_font_css( $css, $fonts_dir ) {
		$output = '';

		if ( ! preg_match_all( '/@font-face\s*{.*?}/s', $css, $blocks ) ) {
			return $output;
		}

		foreach ( $blocks[0] as $index => $block ) {
			if ( ! preg_match('/font-family:\s*[\'"]([^\'"]+)[\'"];/i', $block, $family_match ) ) {
				continue;
			}

			if ( ! preg_match('/src:\s*url\(([^)]+)\)\s*format\([\'"]woff2[\'"]\)/i', $block, $src_match ) ) {
				continue;
			}

			$remote_url = trim( $src_match[1], '\'"' );
			$family     = sanitize_file_name( strtolower( $family_match[1] ) );
			$style      = $this->extract_css_value( $block, 'font-style', 'normal' );
			$weight     = $this->extract_css_value( $block, 'font-weight', '400' );
			$filename   = $family . '-' . sanitize_file_name( $style ) . '-' . sanitize_file_name( $weight ) . '-' . $index . '.woff2';
			$local_file = $fonts_dir . $filename;

			if ( ! file_exists( $local_file ) ) {
				$font_response = wp_remote_get( $remote_url, array( 'timeout' => 30 ) );

				if ( is_wp_error( $font_response ) || 200 !== (int) wp_remote_retrieve_response_code( $font_response ) ) {
					continue;
				}

				$bytes = file_put_contents( $local_file, wp_remote_retrieve_body( $font_response ) );

				if ( false === $bytes ) {
					continue;
				}
			}

			$local_url = '../fonts/generated/' . $filename;
			$face      = preg_replace(
				'/src:\s*url\(([^)]+)\)\s*format\([\'"]woff2[\'"]\);/i',
				"src: url('" . $local_url . "') format('woff2');",
				$block
			);

			$output .= $face . "\n";
		}

		return $output;
	}

	/**
	 * Extract a CSS property value from a font-face block.
	 *
	 * @param string $block CSS block.
	 * @param string $property CSS property name.
	 * @param string $default Fallback value.
	 * @return string
	 */
	protected function extract_css_value( $block, $property, $default ) {
		if ( preg_match( '/' . preg_quote( $property, '/' ) . ':\s*([^;]+);/i', $block, $match ) ) {
			return trim( $match[1] );
		}

		return $default;
	}

	/**
	 * Return selected heading/body fonts.
	 *
	 * @return array<int, string>
	 */
	protected function selected_font_ids() {
		return array_filter(
			array(
				(string) $this->settings->get( 'heading_font', 'Poppins' ),
				(string) $this->settings->get( 'body_font', 'Inter' ),
			)
		);
	}
}
