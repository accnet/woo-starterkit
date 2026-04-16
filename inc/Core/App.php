<?php
/**
 * Theme application container.
 *
 * @package StarterKit
 */

namespace StarterKit\Core;

use StarterKit\Admin\SectionAdminManager;
use StarterKit\Admin\SectionMetaBoxes;
use StarterKit\Admin\SettingsPage;
use StarterKit\Layouts\LayoutRegistry;
use StarterKit\Layouts\LayoutResolver;
use StarterKit\Rules\DisplayRuleEvaluator;
use StarterKit\Rules\PageContextResolver;
use StarterKit\Sections\SectionInstanceRepository;
use StarterKit\Sections\SectionPostType;
use StarterKit\Sections\SectionRenderer;
use StarterKit\Sections\SectionTypeRegistry;
use StarterKit\Sections\SlotRenderer;
use StarterKit\Settings\CssVariableOutput;
use StarterKit\Settings\FontEmbedManager;
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
		$this->font_embed_manager();
		$this->css_variable_output();
		$this->layout_registry();
		$this->layout_resolver();
		$this->section_type_registry();
		$this->section_post_type();
		$this->settings_page();
		$this->section_meta_boxes();
		$this->section_admin_manager();
		$this->context_resolver();
		$this->display_rule_evaluator();
		$this->section_repository();
		$this->section_renderer();
		$this->slot_renderer();
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
					$this->section_type_registry(),
					$this->section_repository(),
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
	 * Local font embed manager.
	 *
	 * @return FontEmbedManager
	 */
	public function font_embed_manager() {
		return $this->service(
			'font_embed_manager',
			function() {
				return new FontEmbedManager( $this->settings_manager() );
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
	 * Section type registry.
	 *
	 * @return SectionTypeRegistry
	 */
	public function section_type_registry() {
		return $this->service(
			'section_type_registry',
			function() {
				return new SectionTypeRegistry();
			}
		);
	}

	/**
	 * Section CPT registration.
	 *
	 * @return SectionPostType
	 */
	public function section_post_type() {
		return $this->service(
			'section_post_type',
			function() {
				return new SectionPostType();
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
	 * Section meta boxes.
	 *
	 * @return SectionMetaBoxes
	 */
	public function section_meta_boxes() {
		return $this->service(
			'section_meta_boxes',
			function() {
				return new SectionMetaBoxes( $this->section_type_registry() );
			}
		);
	}

	/**
	 * Section admin manager.
	 *
	 * @return SectionAdminManager
	 */
	public function section_admin_manager() {
		return $this->service(
			'section_admin_manager',
			function() {
				return new SectionAdminManager();
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
	 * Section repository.
	 *
	 * @return SectionInstanceRepository
	 */
	public function section_repository() {
		return $this->service(
			'section_repository',
			function() {
				return new SectionInstanceRepository();
			}
		);
	}

	/**
	 * Section renderer.
	 *
	 * @return SectionRenderer
	 */
	public function section_renderer() {
		return $this->service(
			'section_renderer',
			function() {
				return new SectionRenderer( $this->section_type_registry(), $this->settings_manager() );
			}
		);
	}

	/**
	 * Slot renderer.
	 *
	 * @return SlotRenderer
	 */
	public function slot_renderer() {
		return $this->service(
			'slot_renderer',
			function() {
				return new SlotRenderer(
					$this->section_repository(),
					$this->section_renderer(),
					$this->context_resolver(),
					$this->display_rule_evaluator(),
					$this->layout_resolver()
				);
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
