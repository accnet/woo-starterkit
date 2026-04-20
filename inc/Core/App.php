<?php
/**
 * Theme application container.
 *
 * @package StarterKit
 */

namespace StarterKit\Core;

use StarterKit\Admin\SettingsPage;
use StarterKit\Admin\ThemeBuilderPage;
use StarterKit\Layouts\LayoutRegistry;
use StarterKit\Layouts\LayoutResolver;
use StarterKit\Rules\DisplayRuleEvaluator;
use StarterKit\Rules\PageContextResolver;
use StarterKit\Settings\CssVariableOutput;
use StarterKit\Settings\GlobalSettingsManager;
use StarterKit\ThemeBuilder\ApiController;
use StarterKit\ThemeBuilder\BuilderContext;
use StarterKit\ThemeBuilder\BuilderMode;
use StarterKit\ThemeBuilder\BuilderStateRepository;
use StarterKit\ThemeBuilder\ElementAssetManager;
use StarterKit\ThemeBuilder\ElementRegistry;
use StarterKit\ThemeBuilder\ElementRenderer;
use StarterKit\ThemeBuilder\PresetSchemaRegistry;
use StarterKit\ThemeBuilder\PreviewAssetManager;
use StarterKit\ThemeBuilder\PreviewContextResolver;
use StarterKit\ThemeBuilder\ZoneRenderer;
use StarterKit\WooCommerce\ArchiveLayoutManager;
use StarterKit\WooCommerce\CartDrawerManager;
use StarterKit\WooCommerce\CheckoutLayoutManager;
use StarterKit\WooCommerce\CheckoutRuntimeManager;
use StarterKit\WooCommerce\CommerceTemplateManager;
use StarterKit\WooCommerce\HookRegistrar;
use StarterKit\WooCommerce\ProductLayoutManager;
use StarterKit\Core\PerformanceManager;

class App {
	/**
	 * Singleton instance.
	 *
	 * @var App|null
	 */
	protected static $instance;

	/**
	 * Registered services.
	 *
	 * @var array<string, object>
	 */
	protected $services = array();

	/**
	 * Get singleton instance.
	 *
	 * @return App
	 */
	public static function instance() {
		if ( null === static::$instance ) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * Boot theme services.
	 *
	 * @return void
	 */
	public function boot() {
		$this->theme_setup();
		$this->settings_manager();
		$this->css_variable_output();
		$this->layout_registry();
			$this->layout_resolver();
			$this->settings_page();
			$this->builder_context();
			$this->builder_mode();
			$this->preset_schema_registry();
			$this->element_registry();
			$this->builder_state_repository();
			$this->preview_context_resolver();
			$this->element_renderer();
			$this->theme_builder_element_asset_manager();
			$this->zone_renderer();
			$this->theme_builder_api_controller();
			$this->theme_builder_page();
			$this->theme_builder_preview_asset_manager();
			$this->context_resolver();
			$this->display_rule_evaluator();
			$this->asset_manager();
		$this->performance_manager();
		$this->script_injection_manager();
		$this->product_layout_manager();
		$this->archive_layout_manager();
		$this->commerce_template_manager();
		$this->checkout_layout_manager();
		$this->checkout_runtime_manager();
		$this->hook_registrar();
		$this->cart_drawer_manager();
	}

	/**
	 * Access a shared service.
	 *
	 * @param string   $key     Service key.
	 * @param callable $factory Service factory.
	 * @return object
	 */
	protected function service( $key, callable $factory ) {
		if ( ! isset( $this->services[ $key ] ) ) {
			$this->services[ $key ] = $factory();
		}

		return $this->services[ $key ];
	}

	/**
	 * Theme setup service.
	 *
	 * @return ThemeSetup
	 */
	public function theme_setup() {
		return $this->service(
			'theme_setup',
			function() {
				return new ThemeSetup();
			}
		);
	}

	/**
	 * Asset manager service.
	 *
	 * @return AssetManager
	 */
	public function asset_manager() {
		return $this->service(
			'asset_manager',
			function() {
				return new AssetManager(
					$this->settings_manager(),
					$this->layout_registry(),
					$this->context_resolver(),
					$this->display_rule_evaluator(),
					$this->layout_resolver()
				);
			}
		);
	}

	/**
	 * Performance manager service.
	 *
	 * @return PerformanceManager
	 */
	public function performance_manager() {
		return $this->service(
			'performance_manager',
			function() {
				return new PerformanceManager( $this->settings_manager() );
			}
		);
	}

	/**
	 * Script injection manager service.
	 *
	 * @return ScriptInjectionManager
	 */
	public function script_injection_manager() {
		return $this->service(
			'script_injection_manager',
			function() {
				return new ScriptInjectionManager( $this->settings_manager() );
			}
		);
	}

	/**
	 * Global settings manager.
	 *
	 * @return GlobalSettingsManager
	 */
	public function settings_manager() {
		return $this->service(
			'settings_manager',
			function() {
				return new GlobalSettingsManager();
			}
		);
	}

	/**
	 * CSS variable output service.
	 *
	 * @return CssVariableOutput
	 */
	public function css_variable_output() {
		return $this->service(
			'css_variable_output',
			function() {
				return new CssVariableOutput( $this->settings_manager() );
			}
		);
	}

	/**
	 * Layout registry.
	 *
	 * @return LayoutRegistry
	 */
	public function layout_registry() {
		return $this->service(
			'layout_registry',
			function() {
				return new LayoutRegistry();
			}
		);
	}

	/**
	 * Layout resolver.
	 *
	 * @return LayoutResolver
	 */
	public function layout_resolver() {
		return $this->service(
			'layout_resolver',
			function() {
				return new LayoutResolver( $this->layout_registry(), $this->settings_manager() );
			}
		);
	}

	/**
	 * Settings page.
	 *
	 * @return SettingsPage
	 */
	public function settings_page() {
		return $this->service(
			'settings_page',
			function() {
				return new SettingsPage( $this->settings_manager(), $this->layout_registry(), $this->builder_state_repository() );
			}
		);
	}

	/**
	 * Builder context service.
	 *
	 * @return BuilderContext
	 */
	public function builder_context() {
		return $this->service(
			'builder_context',
			function() {
				return new BuilderContext();
			}
		);
	}

	/**
	 * Builder mode service.
	 *
	 * @return BuilderMode
	 */
	public function builder_mode() {
		return $this->service(
			'builder_mode',
			function() {
				return new BuilderMode( $this->builder_context() );
			}
		);
	}

	/**
	 * Preset schema registry service.
	 *
	 * @return PresetSchemaRegistry
	 */
	public function preset_schema_registry() {
		return $this->service(
			'preset_schema_registry',
			function() {
				return new PresetSchemaRegistry( $this->layout_resolver() );
			}
		);
	}

	/**
	 * Element registry service.
	 *
	 * @return ElementRegistry
	 */
	public function element_registry() {
		return $this->service(
			'element_registry',
			function() {
				return new ElementRegistry( get_template_directory() . '/elements', get_template_directory_uri() . '/elements' );
			}
		);
	}

	/**
	 * Element module asset manager.
	 *
	 * @return ElementAssetManager
	 */
	public function theme_builder_element_asset_manager() {
		return $this->service(
			'theme_builder_element_asset_manager',
			function() {
				return new ElementAssetManager( $this->element_registry(), $this->builder_state_repository() );
			}
		);
	}

	/**
	 * Builder state repository service.
	 *
	 * @return BuilderStateRepository
	 */
	public function builder_state_repository() {
		return $this->service(
			'builder_state_repository',
			function() {
				return new BuilderStateRepository( $this->preset_schema_registry(), $this->element_registry() );
			}
		);
	}

	/**
	 * Preview context resolver service.
	 *
	 * @return PreviewContextResolver
	 */
	public function preview_context_resolver() {
		return $this->service(
			'preview_context_resolver',
			function() {
				return new PreviewContextResolver( $this->builder_mode() );
			}
		);
	}

	/**
	 * Element renderer service.
	 *
	 * @return ElementRenderer
	 */
	public function element_renderer() {
		return $this->service(
			'element_renderer',
			function() {
				return new ElementRenderer( $this->element_registry(), $this->builder_mode() );
			}
		);
	}

	/**
	 * Zone renderer service.
	 *
	 * @return ZoneRenderer
	 */
	public function zone_renderer() {
		return $this->service(
			'zone_renderer',
			function() {
				return new ZoneRenderer( $this->preset_schema_registry(), $this->builder_state_repository(), $this->element_renderer(), $this->builder_mode() );
			}
		);
	}

	/**
	 * Theme builder API controller.
	 *
	 * @return ApiController
	 */
	public function theme_builder_api_controller() {
		return $this->service(
			'theme_builder_api_controller',
			function() {
				return new ApiController(
					$this->builder_context(),
					$this->preset_schema_registry(),
					$this->element_registry(),
					$this->builder_state_repository(),
					$this->preview_context_resolver(),
					$this->zone_renderer()
				);
			}
		);
	}

	/**
	 * Theme builder page.
	 *
	 * @return ThemeBuilderPage
	 */
	public function theme_builder_page() {
		return $this->service(
			'theme_builder_page',
			function() {
				return new ThemeBuilderPage( $this->theme_builder_api_controller() );
			}
		);
	}

	/**
	 * Preview asset manager service.
	 *
	 * @return PreviewAssetManager
	 */
	public function theme_builder_preview_asset_manager() {
		return $this->service(
			'theme_builder_preview_asset_manager',
			function() {
				return new PreviewAssetManager( $this->builder_mode() );
			}
		);
	}

	/**
	 * Context resolver.
	 *
	 * @return PageContextResolver
	 */
	public function context_resolver() {
		return $this->service(
			'context_resolver',
			function() {
				return new PageContextResolver();
			}
		);
	}

	/**
	 * Rule evaluator.
	 *
	 * @return DisplayRuleEvaluator
	 */
	public function display_rule_evaluator() {
		return $this->service(
			'display_rule_evaluator',
			function() {
				return new DisplayRuleEvaluator();
			}
		);
	}

	/**
	 * Product layout manager.
	 *
	 * @return ProductLayoutManager
	 */
	public function product_layout_manager() {
		return $this->service(
			'product_layout_manager',
			function() {
				return new ProductLayoutManager( $this->layout_resolver() );
			}
		);
	}

	/**
	 * Archive layout manager.
	 *
	 * @return ArchiveLayoutManager
	 */
	public function archive_layout_manager() {
		return $this->service(
			'archive_layout_manager',
			function() {
				return new ArchiveLayoutManager( $this->layout_resolver() );
			}
		);
	}

	/**
	 * WooCommerce hook registrar.
	 *
	 * @return HookRegistrar
	 */
	public function hook_registrar() {
		return $this->service(
			'hook_registrar',
			function() {
				return new HookRegistrar( $this->product_layout_manager(), $this->archive_layout_manager() );
			}
		);
	}

	/**
	 * WooCommerce cart/checkout template manager.
	 *
	 * @return CommerceTemplateManager
	 */
	public function commerce_template_manager() {
		return $this->service(
			'commerce_template_manager',
			function() {
				return new CommerceTemplateManager( $this->settings_manager() );
			}
		);
	}

	/**
	 * WooCommerce checkout layout manager.
	 *
	 * @return CheckoutLayoutManager
	 */
	public function checkout_layout_manager() {
		return $this->service(
			'checkout_layout_manager',
			function() {
				return new CheckoutLayoutManager( $this->settings_manager() );
			}
		);
	}

	/**
	 * WooCommerce checkout runtime manager.
	 *
	 * @return CheckoutRuntimeManager
	 */
	public function checkout_runtime_manager() {
		return $this->service(
			'checkout_runtime_manager',
			function() {
				return new CheckoutRuntimeManager( $this->settings_manager() );
			}
		);
	}

	/**
	 * WooCommerce cart drawer manager.
	 *
	 * @return CartDrawerManager
	 */
	public function cart_drawer_manager() {
		return $this->service(
			'cart_drawer_manager',
			function() {
				return new CartDrawerManager( $this->settings_manager() );
			}
		);
	}
}
