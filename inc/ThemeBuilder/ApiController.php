<?php
/**
 * Admin AJAX endpoints for the theme builder.
 *
 * @package StarterKit
 */

namespace StarterKit\ThemeBuilder;

use StarterKit\Layouts\LayoutSettingsManager;

class ApiController {
	/**
	 * Builder context helper.
	 *
	 * @var BuilderContext
	 */
	protected $builder_context;

	/**
	 * Preset schema registry.
	 *
	 * @var PresetSchemaRegistry
	 */
	protected $preset_schema_registry;

	/**
	 * Element registry.
	 *
	 * @var ElementRegistry
	 */
	protected $element_registry;

	/**
	 * State repository.
	 *
	 * @var BuilderStateRepository
	 */
	protected $state_repository;

	/**
	 * Preview resolver.
	 *
	 * @var PreviewContextResolver
	 */
	protected $preview_context_resolver;

	/**
	 * Zone renderer.
	 *
	 * @var ZoneRenderer
	 */
	protected $zone_renderer;

	/**
	 * Layout settings manager.
	 *
	 * @var LayoutSettingsManager
	 */
	protected $layout_settings_manager;

	/**
	 * Constructor.
	 *
	 * @param BuilderContext         $builder_context Builder context helper.
	 * @param PresetSchemaRegistry   $preset_schema_registry Preset schema registry.
	 * @param ElementRegistry        $element_registry Element registry.
	 * @param BuilderStateRepository $state_repository State repository.
	 * @param PreviewContextResolver $preview_context_resolver Preview resolver.
	 * @param ZoneRenderer           $zone_renderer Zone renderer.
	 * @param LayoutSettingsManager  $layout_settings_manager Layout settings manager.
	 */
	public function __construct( BuilderContext $builder_context, PresetSchemaRegistry $preset_schema_registry, ElementRegistry $element_registry, BuilderStateRepository $state_repository, PreviewContextResolver $preview_context_resolver, ZoneRenderer $zone_renderer, LayoutSettingsManager $layout_settings_manager ) {
		$this->builder_context          = $builder_context;
		$this->preset_schema_registry   = $preset_schema_registry;
		$this->element_registry         = $element_registry;
		$this->state_repository         = $state_repository;
		$this->preview_context_resolver = $preview_context_resolver;
		$this->zone_renderer            = $zone_renderer;
		$this->layout_settings_manager  = $layout_settings_manager;

		add_action( 'wp_ajax_starterkit_theme_builder_bootstrap', array( $this, 'bootstrap' ) );
		add_action( 'wp_ajax_starterkit_theme_builder_save_state', array( $this, 'save_state' ) );
		add_action( 'wp_ajax_starterkit_theme_builder_render_zone', array( $this, 'render_zone' ) );
		add_action( 'wp_ajax_starterkit_theme_builder_render_layout_partial', array( $this, 'render_layout_partial' ) );
	}

	/**
	 * Return the builder bootstrap payload.
	 *
	 * @return void
	 */
	public function bootstrap() {
		$this->authorize();

		wp_send_json_success( $this->get_bootstrap_payload() );
	}

	/**
	 * Save builder state.
	 *
	 * @return void
	 */
	public function save_state() {
		$this->authorize();

		$raw_state = $this->read_json_array_param( 'state' );
		$version   = isset( $_POST['version'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['version'] ) ) : '';
		$current   = $this->state_repository->version();
		$raw_layout_settings = $this->read_json_array_param( 'layoutSettings' );
		$layout_version      = isset( $_POST['layoutSettingsVersion'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['layoutSettingsVersion'] ) ) : '';
		$current_layout      = $this->layout_settings_manager->version();
		$save_state          = ! isset( $_POST['saveBuilderState'] ) || '1' === sanitize_text_field( wp_unslash( (string) $_POST['saveBuilderState'] ) );
		$save_layout         = ! isset( $_POST['saveLayoutSettings'] ) || '1' === sanitize_text_field( wp_unslash( (string) $_POST['saveLayoutSettings'] ) );
		$state_conflict      = $save_state && '' !== $version && $version !== $current;
		$layout_conflict     = $save_layout && '' !== $layout_version && $layout_version !== $current_layout;
		$state               = $this->state_repository->all();
		$layout_settings     = $this->layout_settings_manager->get_active_settings();
		$saved_state         = false;
		$saved_layout        = false;

		if ( $save_state && ! $state_conflict ) {
			$state       = $this->state_repository->save_state( $raw_state );
			$saved_state = true;
		}

		if ( $save_layout && ! $layout_conflict ) {
			$layout_settings = is_array( $raw_layout_settings ) ? $this->layout_settings_manager->save_active_settings( $raw_layout_settings ) : $layout_settings;
			$saved_layout    = true;
		}

		if ( $state_conflict || $layout_conflict ) {
			$code    = $state_conflict && $layout_conflict ? 'save_version_conflict' : ( $state_conflict ? 'state_version_conflict' : 'layout_settings_version_conflict' );
			$message = __( 'Builder state and layout settings changed in another session. Reload the latest data before saving again.', 'starterkit' );

			if ( $state_conflict && ! $layout_conflict ) {
				$message = $saved_layout
					? __( 'Builder state changed in another session. Layout settings were saved; reload the latest state before saving builder changes again.', 'starterkit' )
					: __( 'Builder state changed in another session. Reload the latest state before saving builder changes again.', 'starterkit' );
			} elseif ( $layout_conflict && ! $state_conflict ) {
				$message = $saved_state
					? __( 'Layout settings changed in another session. Builder state was saved; reload the latest settings before saving layout changes again.', 'starterkit' )
					: __( 'Layout settings changed in another session. Reload the latest settings before saving layout changes again.', 'starterkit' );
			}

			wp_send_json_error(
				array(
					'message'                => $message,
					'code'                   => $code,
					'stateConflict'          => $state_conflict,
					'layoutSettingsConflict' => $layout_conflict,
					'savedBuilderState'      => $saved_state,
					'savedLayoutSettings'    => $saved_layout,
					'state'                 => $state,
					'version'               => $this->state_repository->version(),
					'stateVersion'          => $this->state_repository->version(),
					'serverVersion'         => $state_conflict ? $current : $current_layout,
					'stateServerVersion'    => $current,
					'layoutServerVersion'   => $current_layout,
					'layoutSettingsVersion' => $this->layout_settings_manager->version(),
					'layoutSettings'        => $this->layout_settings_manager->get_active_settings(),
				),
				409
			);
		}

		wp_send_json_success(
			array(
				'state'                 => $state,
				'version'               => $this->state_repository->version(),
				'layoutSettings'        => $layout_settings,
				'layoutSettingsVersion' => $this->layout_settings_manager->version(),
				'savedBuilderState'     => $saved_state,
				'savedLayoutSettings'   => $saved_layout,
			)
		);
	}

	/**
	 * Render one zone from a draft builder state.
	 *
	 * @return void
	 */
	public function render_zone() {
		$this->authorize();

		$raw_state = $this->read_json_array_param( 'state' );
		$context   = isset( $_POST['context'] ) ? sanitize_key( wp_unslash( (string) $_POST['context'] ) ) : BuilderContext::MASTER;
		$zone_id   = isset( $_POST['zoneId'] ) ? sanitize_key( wp_unslash( (string) $_POST['zoneId'] ) ) : '';

		if ( ! $this->builder_context->is_valid( $context ) || '' === $zone_id ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid zone preview request.', 'starterkit' ),
				),
				400
			);
		}

		$zone = $this->preset_schema_registry->find_active_zone( $context, $zone_id );

		if ( ! $zone ) {
			wp_send_json_error(
				array(
					'message' => __( 'Zone not found in the active preset.', 'starterkit' ),
				),
				404
			);
		}

		$normalized = $this->state_repository->normalize_state( $raw_state );
		$preset_id  = isset( $zone['preset_id'] ) ? (string) $zone['preset_id'] : '';
		$items      = isset( $normalized[ $context ][ $preset_id ][ $zone_id ] ) && is_array( $normalized[ $context ][ $preset_id ][ $zone_id ] ) ? $normalized[ $context ][ $preset_id ][ $zone_id ] : array();

		wp_send_json_success(
			array(
				'zoneId'  => $zone_id,
				'context' => $context,
				'html'    => $this->zone_renderer->get_markup(
					$zone_id,
					array(
						'context'      => $context,
						'items'        => $items,
						'builder_mode' => true,
					)
				),
			)
		);
	}

	/**
	 * Render a layout partial from draft settings.
	 *
	 * @return void
	 */
	public function render_layout_partial() {
		$this->authorize();

		$partial = isset( $_POST['partial'] ) ? sanitize_key( wp_unslash( (string) $_POST['partial'] ) ) : '';
		$raw_settings = $this->read_json_array_param( 'layoutSettings' );

		if ( ! in_array( $partial, array( 'header_1_navigation', 'header_2_navigation', 'footer_1_grid' ), true ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid layout partial request.', 'starterkit' ),
				),
				400
			);
		}

		if ( 'header_2_navigation' === $partial ) {
			$settings = $this->layout_settings_manager->get_layout_settings( 'header-2', $raw_settings );

			wp_send_json_success(
				array(
					'partial' => $partial,
					'target'  => '.site-header--preset-2 .site-navigation',
					'html'    => $this->layout_settings_manager->render_header_2_navigation( $settings ),
				)
			);
		}

		if ( 'footer_1_grid' === $partial ) {
			$settings = $this->layout_settings_manager->get_layout_settings( 'footer-1', $raw_settings );

			wp_send_json_success(
				array(
					'partial' => $partial,
					'target'  => '.site-footer--preset-1 .footer-grid--preset-1',
					'html'    => $this->layout_settings_manager->render_footer_1_grid( $settings ),
				)
			);
		}

		$settings = $this->layout_settings_manager->get_layout_settings( 'header-1', $raw_settings );

		wp_send_json_success(
			array(
				'partial' => $partial,
				'target'  => '.site-header--preset-1 .site-navigation',
				'html'    => $this->layout_settings_manager->render_header_1_navigation( $settings ),
			)
		);
	}

	/**
	 * Return the bootstrap payload.
	 *
	 * @return array<string, mixed>
	 */
	public function get_bootstrap_payload() {
		$contexts = array();

		foreach ( $this->builder_context->all() as $context ) {
			$contexts[] = array(
				'id'    => $context,
				'label' => $this->builder_context->labels()[ $context ],
			);
		}

		return array(
			'contexts'      => $contexts,
			'activePresets' => $this->preset_schema_registry->resolve_active_preset_ids(),
			'activeSchemas' => array(
				BuilderContext::MASTER  => $this->preset_schema_registry->get_active_schemas( BuilderContext::MASTER ),
				BuilderContext::PRODUCT => $this->preset_schema_registry->get_active_schemas( BuilderContext::PRODUCT ),
				BuilderContext::ARCHIVE => $this->preset_schema_registry->get_active_schemas( BuilderContext::ARCHIVE ),
			),
			'elements'      => $this->element_registry->public_all(),
			'previewUrls'   => $this->preview_context_resolver->all(),
			'state'         => $this->state_repository->all(),
			'version'       => $this->state_repository->version(),
			'layoutSettings' => $this->layout_settings_manager->get_active_settings(),
			'layoutSettingsVersion' => $this->layout_settings_manager->version(),
			'layoutSettingsSchemas' => $this->layout_settings_manager->get_active_schemas(),
			'navMenus'      => $this->layout_settings_manager->get_nav_menu_options(),
		);
	}

	/**
	 * Ensure the current user is authorized for builder actions.
	 *
	 * @return void
	 */
	protected function authorize() {
		if ( 'POST' !== strtoupper( isset( $_SERVER['REQUEST_METHOD'] ) ? (string) $_SERVER['REQUEST_METHOD'] : '' ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid request method.', 'starterkit' ) ), 405 );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to use the theme builder.', 'starterkit' ) ), 403 );
		}

		if ( ! check_ajax_referer( 'starterkit_theme_builder', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid security token.', 'starterkit' ) ), 403 );
		}
	}

	/**
	 * Read a JSON object/array parameter from the request.
	 *
	 * @param string $key Request parameter key.
	 * @return array<string, mixed>
	 */
	protected function read_json_array_param( $key ) {
		if ( ! isset( $_POST[ $key ] ) || '' === (string) $_POST[ $key ] ) {
			return array();
		}

		$decoded = json_decode( wp_unslash( (string) $_POST[ $key ] ), true );

		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
			wp_send_json_error(
				array(
					'message' => sprintf(
						/* translators: %s: request field name */
						__( 'Invalid JSON payload for %s.', 'starterkit' ),
						sanitize_key( $key )
					),
				),
				400
			);
		}

		return $decoded;
	}
}
