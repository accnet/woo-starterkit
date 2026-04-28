<?php
/**
 * Schema-driven layout settings manager.
 *
 * @package StarterKit
 */

namespace StarterKit\Layouts;

use StarterKit\Settings\ControlSanitizer;
use StarterKit\Settings\CssVariableBuilder;
use StarterKit\Settings\GlobalSettingsManager;

class LayoutSettingsManager {
	/**
	 * Layout registry.
	 *
	 * @var LayoutRegistry
	 */
	protected $registry;

	/**
	 * Layout resolver.
	 *
	 * @var LayoutResolver
	 */
	protected $resolver;

	/**
	 * Global settings manager.
	 *
	 * @var GlobalSettingsManager
	 */
	protected $settings;

	/**
	 * Control sanitizer.
	 *
	 * @var ControlSanitizer
	 */
	protected $sanitizer;

	/**
	 * Layout settings cache.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	protected $cache = array();

	/**
	 * Nav menu options cache.
	 *
	 * @var array<int, array<string, string>>|null
	 */
	protected $nav_menu_options = null;

	/**
	 * Constructor.
	 *
	 * @param LayoutRegistry        $registry Layout registry.
	 * @param LayoutResolver        $resolver Layout resolver.
	 * @param GlobalSettingsManager $settings Global settings manager.
	 * @param ControlSanitizer      $sanitizer Control sanitizer.
	 */
	public function __construct( LayoutRegistry $registry, LayoutResolver $resolver, GlobalSettingsManager $settings, ControlSanitizer $sanitizer ) {
		$this->registry  = $registry;
		$this->resolver  = $resolver;
		$this->settings  = $settings;
		$this->sanitizer = $sanitizer;
	}

	/**
	 * Return sanitized settings for one layout.
	 *
	 * @param string                    $layout_id Layout id.
	 * @param array<string, mixed>|null $draft Optional draft settings.
	 * @return array<string, mixed>
	 */
	public function get_layout_settings( $layout_id, array $draft = null ) {
		$layout_id = sanitize_key( (string) $layout_id );
		$cache_key = null === $draft ? $layout_id : '';

		if ( $cache_key && isset( $this->cache[ $cache_key ] ) ) {
			return $this->cache[ $cache_key ];
		}

		$schema = $this->get_layout_settings_schema( $layout_id );

		if ( empty( $schema ) ) {
			return array();
		}

		$source = null === $draft ? $this->settings->all() : array_merge( $this->settings->all(), $draft );
		$values = $this->sanitizer->sanitize_settings( $schema, $source );

		if ( $cache_key ) {
			$this->cache[ $cache_key ] = $values;
		}

		return $values;
	}

	/**
	 * Return active layout settings as a flat map.
	 *
	 * @param array<string, mixed>|null $draft Optional draft settings.
	 * @return array<string, mixed>
	 */
	public function get_active_settings( array $draft = null ) {
		$output = array();

		foreach ( $this->get_active_layouts() as $resolved ) {
			$layout = isset( $resolved['layout'] ) && is_array( $resolved['layout'] ) ? $resolved['layout'] : array();

			if ( empty( $layout['id'] ) ) {
				continue;
			}

			$output = array_merge( $output, $this->get_layout_settings( (string) $layout['id'], $draft ) );
		}

		return $output;
	}

	/**
	 * Return active layout schemas for the builder.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_active_schemas() {
		$output = array();

		foreach ( $this->get_active_layouts() as $resolved ) {
			$part    = isset( $resolved['part'] ) ? (string) $resolved['part'] : '';
			$context = isset( $resolved['context'] ) ? (string) $resolved['context'] : 'master';
			$layout  = isset( $resolved['layout'] ) && is_array( $resolved['layout'] ) ? $resolved['layout'] : array();

			if ( empty( $layout['id'] ) ) {
				continue;
			}

			$layout_id = (string) $layout['id'];
			$schema    = $this->get_layout_settings_schema( $layout_id );

			if ( empty( $schema ) ) {
				continue;
			}

			$output[ $layout_id ] = array(
				'id'               => $layout_id,
				'part'             => $part,
				'context'          => $context,
				'label'            => isset( $layout['label'] ) ? (string) $layout['label'] : $layout_id,
				'settings_version' => isset( $layout['settings_version'] ) ? (int) $layout['settings_version'] : 1,
				'settings_schema'  => $schema,
			);
		}

		return $output;
	}

	/**
	 * Return a version hash for active layout settings.
	 *
	 * @return string
	 */
	public function version() {
		return md5( wp_json_encode( $this->get_active_settings() ) );
	}

	/**
	 * Save active layout settings from a flat map.
	 *
	 * @param array<string, mixed> $raw_settings Raw layout settings.
	 * @return array<string, mixed>
	 */
	public function save_active_settings( array $raw_settings ) {
		$sanitized = $this->get_active_settings( $raw_settings );
		$saved     = get_option( GlobalSettingsManager::OPTION_KEY, array() );
		$saved     = is_array( $saved ) ? $saved : array();

		foreach ( $sanitized as $key => $value ) {
			$saved[ $key ] = $value;
		}

		update_option( GlobalSettingsManager::OPTION_KEY, $this->settings->sanitize( $saved ), false );
		$this->settings->reset_cache();
		$this->cache = array();

		return $this->get_active_settings();
	}

	/**
	 * Backward-compatible alias for active Master layout settings.
	 *
	 * @param array<string, mixed>|null $draft Optional draft settings.
	 * @return array<string, mixed>
	 */
	public function get_active_master_settings( array $draft = null ) {
		return $this->get_active_settings( $draft );
	}

	/**
	 * Backward-compatible alias for active Master layout schemas.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_active_master_schemas() {
		return $this->get_active_schemas();
	}

	/**
	 * Backward-compatible alias for saving active Master layout settings.
	 *
	 * @param array<string, mixed> $raw_settings Raw layout settings.
	 * @return array<string, mixed>
	 */
	public function save_active_master_settings( array $raw_settings ) {
		return $this->save_active_settings( $raw_settings );
	}

	/**
	 * Return nav menu options for layout controls.
	 *
	 * @return array<int, array<string, string>>
	 */
	public function get_nav_menu_options() {
		if ( null !== $this->nav_menu_options ) {
			return $this->nav_menu_options;
		}

		$options = array(
			array(
				'value' => '0',
				'label' => __( 'Use Primary Menu', 'starterkit' ),
			),
		);

		foreach ( wp_get_nav_menus() as $menu ) {
			$options[] = array(
				'value' => (string) $menu->term_id,
				'label' => (string) $menu->name,
			);
		}

		$this->nav_menu_options = $options;

		return $this->nav_menu_options;
	}

	/**
	 * Return menu arguments for header-1.
	 *
	 * @param array<string, mixed>|null $settings Optional settings.
	 * @return array<string, mixed>
	 */
	public function header_1_menu_args( array $settings = null ) {
		$settings = is_array( $settings ) ? $settings : $this->get_layout_settings( 'header-1' );
		$menu_id  = isset( $settings['header_1_main_menu_id'] ) ? absint( $settings['header_1_main_menu_id'] ) : 0;
		$args     = array( 'fallback_cb' => false );

		if ( $menu_id && wp_get_nav_menu_object( $menu_id ) ) {
			$args['menu'] = $menu_id;
		} else {
			$args['theme_location'] = 'primary';
		}

		return $args;
	}

	/**
	 * Render header-1 navigation.
	 *
	 * @param array<string, mixed>|null $settings Optional settings.
	 * @return string
	 */
	public function render_header_1_navigation( array $settings = null ) {
		ob_start();
		?>
		<nav class="site-navigation">
			<?php wp_nav_menu( $this->header_1_menu_args( $settings ) ); ?>
		</nav>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Build navigation args for header-2.
	 *
	 * @param array<string, mixed>|null $settings Optional settings.
	 * @return array<string, mixed>
	 */
	protected function header_2_menu_args( array $settings = null ) {
		$settings = is_array( $settings ) ? $settings : $this->get_layout_settings( 'header-2' );
		$menu_id  = isset( $settings['header_2_main_menu_id'] ) ? absint( $settings['header_2_main_menu_id'] ) : 0;
		$args     = array( 'fallback_cb' => false );

		if ( $menu_id && wp_get_nav_menu_object( $menu_id ) ) {
			$args['menu'] = $menu_id;
		} else {
			$args['theme_location'] = 'primary';
		}

		return $args;
	}

	/**
	 * Render header-2 navigation.
	 *
	 * @param array<string, mixed>|null $settings Optional settings.
	 * @return string
	 */
	public function render_header_2_navigation( array $settings = null ) {
		ob_start();
		?>
		<nav class="site-navigation">
			<?php wp_nav_menu( $this->header_2_menu_args( $settings ) ); ?>
		</nav>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Return inline CSS variables for header-1.
	 *
	 * @param array<string, mixed>|null $settings Optional settings.
	 * @return string
	 */
	public function header_1_inline_style( array $settings = null ) {
		$settings = is_array( $settings ) ? array_merge( $this->get_layout_settings( 'header-1' ), $settings ) : $this->get_layout_settings( 'header-1' );

		return CssVariableBuilder::build(
			array(
				'--header-1-logo-max-height' => array(
					'value' => $settings['header_1_logo_max_height'],
					'unit'  => 'px',
				),
				'--header-1-min-height' => array(
					'value' => $settings['header_1_header_min_height'],
					'unit'  => 'px',
				),
				'--header-1-bg' => $settings['header_1_background_color'],
				'--header-1-nav-gap' => array(
					'value' => $settings['header_1_navigation_gap'],
					'unit'  => 'px',
				),
			)
		);
	}

	/**
	 * Return inline CSS variables for header-2.
	 *
	 * @param array<string, mixed>|null $settings Optional settings.
	 * @return string
	 */
	public function header_2_inline_style( array $settings = null ) {
		$settings = is_array( $settings ) ? array_merge( $this->get_layout_settings( 'header-2' ), $settings ) : $this->get_layout_settings( 'header-2' );

		return CssVariableBuilder::build(
			array(
				'--header-2-logo-max-height' => array(
					'value' => $settings['header_2_logo_max_height'],
					'unit'  => 'px',
				),
				'--header-2-nav-gap' => array(
					'value' => $settings['header_2_navigation_gap'],
					'unit'  => 'px',
				),
				'--header-2-bg' => $settings['header_2_background_color'],
				'--header-2-nav-bg' => $settings['header_2_navigation_background_color'],
			)
		);
	}

	/**
	 * Return the configured gallery width percentage for split product layouts.
	 *
	 * @param array<string, mixed>|null $settings Optional settings.
	 * @return int
	 */
	public function product_gallery_width_percent( array $settings = null ) {
		$settings = is_array( $settings ) ? $settings : $this->get_layout_settings( 'product-layout-1' );
		$gallery  = isset( $settings['product_gallery_column_ratio'] ) ? (int) $settings['product_gallery_column_ratio'] : 60;

		return max( 40, min( 70, $gallery ) );
	}

	/**
	 * Return inline CSS variables for split product layouts.
	 *
	 * @param array<string, mixed>|null $settings Optional settings.
	 * @return string
	 */
	public function product_split_layout_inline_style( array $settings = null ) {
		$gallery = $this->product_gallery_width_percent( $settings );
		$summary = 100 - $gallery;

		return '--starterkit-product-gallery-col:' . $gallery . '%;--starterkit-product-summary-col:' . $summary . '%;';
	}

	/**
	 * Return related products settings for product-layout-1.
	 *
	 * @param array<string, mixed>|null $settings Optional settings.
	 * @return array<string, int|string>
	 */
	public function product_layout_1_related_products_settings( array $settings = null ) {
		$settings = is_array( $settings ) ? $settings : $this->get_layout_settings( 'product-layout-1' );

		return array(
			'show'    => isset( $settings['product_layout_1_show_related_products'] ) ? (string) $settings['product_layout_1_show_related_products'] : '1',
			'limit'   => isset( $settings['product_layout_1_related_products_count'] ) ? max( 1, min( 12, (int) $settings['product_layout_1_related_products_count'] ) ) : 5,
			'columns' => isset( $settings['product_layout_1_related_products_columns'] ) ? max( 1, min( 6, (int) $settings['product_layout_1_related_products_columns'] ) ) : 5,
		);
	}

	/**
	 * Return visible footer-1 columns for sanitized settings.
	 *
	 * @param array<string, mixed>|null $settings Optional settings.
	 * @return array<int, array<string, mixed>>
	 */
	public function footer_1_visible_columns( array $settings = null ) {
		$settings     = is_array( $settings ) ? $settings : $this->get_layout_settings( 'footer-1' );
		$column_count = isset( $settings['footer_1_column_count'] ) ? max( 1, min( 4, (int) $settings['footer_1_column_count'] ) ) : 4;
		$columns      = $this->footer_1_columns();

		$visible_columns = array_values(
			array_filter(
				$columns,
				function( $column ) use ( $settings, $column_count ) {
					$index = (int) $column['index'];
					$key   = 'footer_1_show_column_' . $index;

					return $index <= $column_count && '1' === (string) ( isset( $settings[ $key ] ) ? $settings[ $key ] : '1' );
				}
			)
		);

		if ( empty( $visible_columns ) ) {
			$visible_columns = array( $columns[0] );
		}

		return $visible_columns;
	}

	/**
	 * Render the footer-1 grid.
	 *
	 * @param array<string, mixed>|null $settings Optional settings.
	 * @return string
	 */
	public function render_footer_1_grid( array $settings = null ) {
		$visible_columns = $this->footer_1_visible_columns( $settings );
		$grid_style      = CssVariableBuilder::build(
			array(
				'--footer-1-columns' => count( $visible_columns ),
			)
		);

		ob_start();
		?>
		<div class="container footer-grid footer-grid--preset-1" style="<?php echo esc_attr( $grid_style ); ?>">
			<?php foreach ( $visible_columns as $column ) : ?>
				<div class="footer-col" data-footer-column-index="<?php echo esc_attr( (string) $column['index'] ); ?>">
					<?php if ( is_active_sidebar( $column['sidebar'] ) ) : ?>
						<?php dynamic_sidebar( $column['sidebar'] ); ?>
					<?php else : ?>
						<h3 class="footer-col__title"><?php echo esc_html( (string) $column['title'] ); ?></h3>
						<?php if ( 'footer-menu' === $column['content'] ) : ?>
							<?php wp_nav_menu( array( 'theme_location' => 'footer', 'fallback_cb' => false ) ); ?>
						<?php else : ?>
							<?php echo $column['content']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php endif; ?>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Return settings schema for a layout.
	 *
	 * @param string $layout_id Layout id.
	 * @return array<int, array<string, mixed>>
	 */
	protected function get_layout_settings_schema( $layout_id ) {
		$layout = $this->registry->get( $layout_id );

		if ( empty( $layout['settings_schema'] ) || ! is_array( $layout['settings_schema'] ) ) {
			return array();
		}

		return $this->sanitizer->normalize_schema( $this->resolve_dynamic_schema_options( (array) $layout['settings_schema'] ) );
	}

	/**
	 * Return footer-1 column definitions.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	protected function footer_1_columns() {
		return array(
			array(
				'index'   => 1,
				'sidebar' => 'footer-1',
				'title'   => get_bloginfo( 'name' ),
				'content' => '<p class="footer-col__desc">' . esc_html__( 'Structured theme builder for WordPress and WooCommerce.', 'starterkit' ) . '</p>',
			),
			array(
				'index'   => 2,
				'sidebar' => 'footer-2',
				'title'   => __( 'Quick Links', 'starterkit' ),
				'content' => 'footer-menu',
			),
			array(
				'index'   => 3,
				'sidebar' => 'footer-3',
				'title'   => __( 'Support', 'starterkit' ),
				'content' => '',
			),
			array(
				'index'   => 4,
				'sidebar' => 'footer-4',
				'title'   => __( 'Contact', 'starterkit' ),
				'content' => '',
			),
		);
	}

	/**
	 * Return active Master header/footer layouts.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	protected function get_active_master_layouts() {
		return array_filter(
			array(
				'header' => $this->resolver->resolve( 'header' ),
				'footer' => $this->resolver->resolve( 'footer' ),
			)
		);
	}

	/**
	 * Return active layouts across builder contexts.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	protected function get_active_layouts() {
		$layouts = array();

		foreach ( $this->get_active_master_layouts() as $part => $layout ) {
			$layouts[] = array(
				'context' => 'master',
				'part'    => $part,
				'layout'  => $layout,
			);
		}

		$product = $this->resolver->resolve( 'product' );

		if ( ! empty( $product['id'] ) ) {
			$layouts[] = array(
				'context' => 'product',
				'part'    => 'product',
				'layout'  => $product,
			);
		}

		$archive = $this->resolver->resolve( 'archive' );

		if ( ! empty( $archive['id'] ) ) {
			$layouts[] = array(
				'context' => 'archive',
				'part'    => 'archive',
				'layout'  => $archive,
			);
		}

		return $layouts;
	}

	/**
	 * Resolve dynamic options in public schemas.
	 *
	 * @param array<int, array<string, mixed>> $schema Settings schema.
	 * @return array<int, array<string, mixed>>
	 */
	protected function resolve_dynamic_schema_options( array $schema ) {
		foreach ( $schema as &$control ) {
			if ( isset( $control['options_source'] ) && 'nav_menus' === $control['options_source'] ) {
				$control['options'] = $this->get_nav_menu_options();

				if ( count( $control['options'] ) <= 1 ) {
					$control['help'] = __( 'No menus found. Create a menu in Appearance > Menus to select it here.', 'starterkit' );
				}
			}
		}
		unset( $control );

		return $schema;
	}
}
