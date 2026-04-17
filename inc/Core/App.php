<?php
/**
 * Theme application container.
 *
 * @package StarterKit
 */

namespace StarterKit\Core;

use StarterKit\Admin\SettingsPage;
use StarterKit\Layouts\LayoutRegistry;
use StarterKit\Layouts\LayoutResolver;
use StarterKit\Rules\DisplayRuleEvaluator;
use StarterKit\Rules\PageContextResolver;
use StarterKit\Settings\CssVariableOutput;
use StarterKit\Settings\GlobalSettingsManager;
use StarterKit\WooCommerce\ArchiveLayoutManager;
use StarterKit\WooCommerce\CartDrawerManager;
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
		$this->context_resolver();
		$this->display_rule_evaluator();
		$this->asset_manager();
		$this->performance_manager();
		$this->script_injection_manager();
		$this->product_layout_manager();
		$this->archive_layout_manager();
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
				return new SettingsPage( $this->settings_manager(), $this->layout_registry() );
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
