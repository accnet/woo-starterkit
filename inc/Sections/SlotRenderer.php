<?php
/**
 * Resolve and render sections assigned to a slot.
 *
 * @package StarterKit
 */

namespace StarterKit\Sections;

use StarterKit\Layouts\LayoutResolver;
use StarterKit\Rules\DisplayRuleEvaluator;
use StarterKit\Rules\PageContextResolver;

class SlotRenderer {
	/**
	 * Section repository.
	 *
	 * @var SectionInstanceRepository
	 */
	protected $repository;

	/**
	 * Section renderer.
	 *
	 * @var SectionRenderer
	 */
	protected $renderer;

	/**
	 * Context resolver.
	 *
	 * @var PageContextResolver
	 */
	protected $context_resolver;

	/**
	 * Rule evaluator.
	 *
	 * @var DisplayRuleEvaluator
	 */
	protected $rule_evaluator;

	/**
	 * Layout resolver.
	 *
	 * @var LayoutResolver
	 */
	protected $layout_resolver;

	/**
	 * Constructor.
	 *
	 * @param SectionInstanceRepository $repository Repository.
	 * @param SectionRenderer           $renderer Renderer.
	 * @param PageContextResolver       $context_resolver Context resolver.
	 * @param DisplayRuleEvaluator      $rule_evaluator Rule evaluator.
	 */
	public function __construct( SectionInstanceRepository $repository, SectionRenderer $renderer, PageContextResolver $context_resolver, DisplayRuleEvaluator $rule_evaluator, LayoutResolver $layout_resolver ) {
		$this->repository       = $repository;
		$this->renderer         = $renderer;
		$this->context_resolver = $context_resolver;
		$this->rule_evaluator   = $rule_evaluator;
		$this->layout_resolver  = $layout_resolver;
	}

	/**
	 * Render all matching sections for a slot.
	 *
	 * @param string               $slot_name Slot identifier.
	 * @param array<string, mixed> $context Optional context override.
	 * @return void
	 */
	public function render( $slot_name, array $context = array() ) {
		$context  = wp_parse_args( $context, $this->context_resolver->resolve() );

		if ( ! $this->layout_resolver->is_slot_supported( $slot_name, $context ) ) {
			return;
		}

		$sections = $this->repository->get_active_sections( $slot_name );

		if ( empty( $sections ) ) {
			return;
		}

		echo '<div class="slot slot-' . esc_attr( $slot_name ) . '">';

		foreach ( $sections as $section ) {
			if ( $slot_name !== $section['slot'] ) {
				continue;
			}

			if ( ! $this->rule_evaluator->matches( (array) $section['display_rules'], $context ) ) {
				continue;
			}

			$this->renderer->render( $section, $context );
		}

		echo '</div>';
	}
}
