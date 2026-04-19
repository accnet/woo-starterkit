<?php
/**
 * Admin AJAX endpoints for the theme builder.
 *
 * @package StarterKit
 */

namespace StarterKit\ThemeBuilder;

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
	 * Constructor.
	 *
	 * @param BuilderContext         $builder_context Builder context helper.
	 * @param PresetSchemaRegistry   $preset_schema_registry Preset schema registry.
	 * @param ElementRegistry        $element_registry Element registry.
	 * @param BuilderStateRepository $state_repository State repository.
	 * @param PreviewContextResolver $preview_context_resolver Preview resolver.
	 * @param ZoneRenderer           $zone_renderer Zone renderer.
	 */
	public function __construct( BuilderContext $builder_context, PresetSchemaRegistry $preset_schema_registry, ElementRegistry $element_registry, BuilderStateRepository $state_repository, PreviewContextResolver $preview_context_resolver, ZoneRenderer $zone_renderer ) {
		$this->builder_context          = $builder_context;
		$this->preset_schema_registry   = $preset_schema_registry;
		$this->element_registry         = $element_registry;
		$this->state_repository         = $state_repository;
		$this->preview_context_resolver = $preview_context_resolver;
		$this->zone_renderer            = $zone_renderer;

		add_action( 'wp_ajax_starterkit_theme_builder_bootstrap', array( $this, 'bootstrap' ) );
		add_action( 'wp_ajax_starterkit_theme_builder_save_state', array( $this, 'save_state' ) );
		add_action( 'wp_ajax_starterkit_theme_builder_render_zone', array( $this, 'render_zone' ) );
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

		$raw_state = isset( $_POST['state'] ) ? json_decode( wp_unslash( (string) $_POST['state'] ), true ) : array();
		$version   = isset( $_POST['version'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['version'] ) ) : '';
		$current   = $this->state_repository->version();

		if ( '' !== $version && $version !== $current ) {
			wp_send_json_error(
				array(
					'message' => __( 'Builder state changed in another session. Reload the latest state before saving again.', 'starterkit' ),
					'code'    => 'state_version_conflict',
					'serverVersion' => $current,
					'state'   => $this->state_repository->all(),
				),
				409
			);
		}

		$state = $this->state_repository->save_state( $raw_state );

		wp_send_json_success(
			array(
				'state'   => $state,
				'version' => $this->state_repository->version(),
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

		$raw_state = isset( $_POST['state'] ) ? json_decode( wp_unslash( (string) $_POST['state'] ), true ) : array();
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
		);
	}

	/**
	 * Ensure the current user is authorized for builder actions.
	 *
	 * @return void
	 */
	protected function authorize() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to use the theme builder.', 'starterkit' ) ), 403 );
		}

		check_ajax_referer( 'starterkit_theme_builder', 'nonce' );
	}
}
