<?php
/**
 * Zone renderer for preset-controlled builder areas.
 *
 * @package StarterKit
 */

namespace StarterKit\ThemeBuilder;

use StarterKit\Rules\PageContextResolver;

class ZoneRenderer {
	/**
	 * Preset schema registry.
	 *
	 * @var PresetSchemaRegistry
	 */
	protected $preset_schema_registry;

	/**
	 * Builder state repository.
	 *
	 * @var BuilderStateRepository
	 */
	protected $state_repository;

	/**
	 * Element renderer.
	 *
	 * @var ElementRenderer
	 */
	protected $element_renderer;

	/**
	 * Builder mode.
	 *
	 * @var BuilderMode
	 */
	protected $builder_mode;

	/**
	 * Page context resolver.
	 *
	 * @var PageContextResolver
	 */
	protected $context_resolver;

	/**
	 * Request-local zone markup cache.
	 *
	 * @var array<string, string>
	 */
	protected $markup_cache = array();

	/**
	 * Constructor.
	 *
	 * @param PresetSchemaRegistry  $preset_schema_registry Preset schema registry.
	 * @param BuilderStateRepository $state_repository State repository.
	 * @param ElementRenderer        $element_renderer Element renderer.
	 * @param BuilderMode            $builder_mode Builder mode.
	 */
	public function __construct( PresetSchemaRegistry $preset_schema_registry, BuilderStateRepository $state_repository, ElementRenderer $element_renderer, BuilderMode $builder_mode, PageContextResolver $context_resolver ) {
		$this->preset_schema_registry = $preset_schema_registry;
		$this->state_repository       = $state_repository;
		$this->element_renderer       = $element_renderer;
		$this->builder_mode           = $builder_mode;
		$this->context_resolver       = $context_resolver;
	}

	/**
	 * Render a zone by id.
	 *
	 * @param string               $zone_id Zone id.
	 * @param array<string, mixed> $args Optional args.
	 * @return void
	 */
	public function render( $zone_id, array $args = array() ) {
		echo $this->get_markup( $zone_id, $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Build zone markup as a string.
	 *
	 * @param string               $zone_id Zone id.
	 * @param array<string, mixed> $args Optional args.
	 * @return string
	 */
	public function get_markup( $zone_id, array $args = array() ) {
		$context = isset( $args['context'] ) ? (string) $args['context'] : $this->infer_context_from_zone( $zone_id );
		$zone    = $this->preset_schema_registry->find_active_zone( $context, $zone_id );

		if ( ! $zone ) {
			return '';
		}

		$preset_id = isset( $zone['preset_id'] ) ? (string) $zone['preset_id'] : '';
		$items     = isset( $args['items'] ) && is_array( $args['items'] ) ? array_values( $args['items'] ) : $this->state_repository->get_zone_items( $context, $preset_id, $zone_id );
		$is_builder_mode = array_key_exists( 'builder_mode', $args ) ? (bool) $args['builder_mode'] : $this->builder_mode->is_builder_mode();
		$cache_key = $this->get_cache_key( $zone_id, $context, $preset_id, $items, $args, $is_builder_mode );
		$classes   = array( 'slot', 'starterkit-element-zone', 'starterkit-element-zone--' . sanitize_html_class( $zone_id ) );

		if ( empty( $items ) && ! $is_builder_mode ) {
			return '';
		}

		if ( $cache_key && isset( $this->markup_cache[ $cache_key ] ) ) {
			return $this->markup_cache[ $cache_key ];
		}

		if ( $cache_key ) {
			$cached_markup = wp_cache_get( $cache_key, 'starterkit_zone_markup' );

			if ( false !== $cached_markup && is_string( $cached_markup ) ) {
				$this->markup_cache[ $cache_key ] = $cached_markup;

				return $cached_markup;
			}
		}

		if ( $is_builder_mode ) {
			$classes[] = 'starterkit-builder-zone';
			$classes[] = 'starterkit-builder-zone--' . sanitize_html_class( $zone_id );
		}

		if ( empty( $items ) ) {
			$classes[] = 'starterkit-element-zone--empty';

			if ( $is_builder_mode ) {
				$classes[] = 'starterkit-builder-zone--empty';
			}
		}

		ob_start();
		?>
		<div
			class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
			<?php if ( $is_builder_mode ) : ?>
				data-builder-zone="<?php echo esc_attr( $zone_id ); ?>"
				data-builder-zone-label="<?php echo esc_attr( isset( $zone['label'] ) ? (string) $zone['label'] : $zone_id ); ?>"
				data-builder-zone-context="<?php echo esc_attr( $context ); ?>"
				data-builder-zone-preset="<?php echo esc_attr( $preset_id ); ?>"
				data-builder-zone-droppable="<?php echo ! empty( $zone['droppable'] ) ? '1' : '0'; ?>"
				data-builder-zone-sortable="<?php echo ! empty( $zone['sortable'] ) ? '1' : '0'; ?>"
			<?php endif; ?>
		>
			<?php foreach ( $items as $item ) : ?>
				<?php echo $this->element_renderer->render( $item, $zone_id, $context, $is_builder_mode ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endforeach; ?>
			<?php if ( $is_builder_mode && empty( $items ) ) : ?>
				<div class="starterkit-builder-zone__placeholder">
					<strong><?php echo esc_html( isset( $zone['label'] ) ? (string) $zone['label'] : $zone_id ); ?></strong>
					<span><?php esc_html_e( 'Drop or add elements here from the builder panel.', 'starterkit' ); ?></span>
				</div>
			<?php endif; ?>
		</div>
		<?php

		$markup = (string) ob_get_clean();

		if ( $cache_key ) {
			$this->markup_cache[ $cache_key ] = $markup;
			wp_cache_set( $cache_key, $markup, 'starterkit_zone_markup' );
		}

		return $markup;
	}

	/**
	 * Infer context from zone id.
	 *
	 * @param string $zone_id Zone id.
	 * @return string
	 */
	protected function infer_context_from_zone( $zone_id ) {
		if ( 0 === strpos( $zone_id, 'product_' ) ) {
			return BuilderContext::PRODUCT;
		}

		if ( 0 === strpos( $zone_id, 'archive_' ) ) {
			return BuilderContext::ARCHIVE;
		}

		return BuilderContext::MASTER;
	}

	/**
	 * Build a cache key for a zone render.
	 *
	 * @param string               $zone_id Zone id.
	 * @param string               $context Builder context.
	 * @param string               $preset_id Preset id.
	 * @param array<int, mixed>    $items Zone items.
	 * @param array<string, mixed> $args Render args.
	 * @param bool                 $is_builder_mode Whether builder mode is active.
	 * @return string
	 */
	protected function get_cache_key( $zone_id, $context, $preset_id, array $items, array $args, $is_builder_mode ) {
		if ( $is_builder_mode || isset( $args['items'] ) ) {
			return '';
		}

		$page_context = $this->context_resolver->resolve();

		return md5(
			wp_json_encode(
				array(
					'theme_version'  => wp_get_theme()->get( 'Version' ),
					'state_version'  => $this->state_repository->version(),
					'context'        => $context,
					'preset_id'      => $preset_id,
					'zone_id'        => $zone_id,
					'items'          => $items,
					'page_context'   => array(
						'is_homepage'        => ! empty( $page_context['is_homepage'] ),
						'is_shop'            => ! empty( $page_context['is_shop'] ),
						'is_product'         => ! empty( $page_context['is_product'] ),
						'is_product_archive' => ! empty( $page_context['is_product_archive'] ),
						'is_logged_in'       => ! empty( $page_context['is_logged_in'] ),
						'current_page_id'    => isset( $page_context['current_page_id'] ) ? (int) $page_context['current_page_id'] : 0,
						'current_product_id' => isset( $page_context['current_product_id'] ) ? (int) $page_context['current_product_id'] : 0,
						'current_term_id'    => isset( $page_context['current_term_id'] ) ? (int) $page_context['current_term_id'] : 0,
						'current_post_type'  => isset( $page_context['current_post_type'] ) ? (string) $page_context['current_post_type'] : '',
						'current_taxonomy'   => isset( $page_context['current_taxonomy'] ) ? (string) $page_context['current_taxonomy'] : '',
						'device'             => isset( $page_context['device'] ) ? (string) $page_context['device'] : '',
					),
					'locale'         => function_exists( 'determine_locale' ) ? determine_locale() : get_locale(),
				)
			)
		);
	}
}
