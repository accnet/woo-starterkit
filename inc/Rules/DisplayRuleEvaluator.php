<?php
/**
 * Evaluate include and exclude display rules.
 *
 * @package StarterKit
 */

namespace StarterKit\Rules;

class DisplayRuleEvaluator {
	/**
	 * Determine whether a section should render.
	 *
	 * @param array<string, mixed> $rules Display rules.
	 * @param array<string, mixed> $context Resolved page context.
	 * @return bool
	 */
	public function matches( array $rules, array $context ) {
		$include          = isset( $rules['include'] ) && is_array( $rules['include'] ) ? $rules['include'] : array();
		$exclude          = isset( $rules['exclude'] ) && is_array( $rules['exclude'] ) ? $rules['exclude'] : array();
		$include_relation = isset( $rules['include_relation'] ) ? strtoupper( (string) $rules['include_relation'] ) : 'OR';
		$exclude_relation = isset( $rules['exclude_relation'] ) ? strtoupper( (string) $rules['exclude_relation'] ) : 'OR';

		if ( ! $this->match_schedule( $rules ) || ! $this->match_device( $rules, $context ) ) {
			return false;
		}

		if ( $this->match_rules( $exclude, $exclude_relation, $context ) ) {
			return false;
		}

		if ( empty( $include ) ) {
			return ! empty( $rules['entire_site'] );
		}

		return $this->match_rules( $include, $include_relation, $context );
	}

	/**
	 * Evaluate a rules list with a relation.
	 *
	 * @param array<int, array<string, mixed>> $rules Rules.
	 * @param string                           $relation Relation.
	 * @param array<string, mixed>             $context Context.
	 * @return bool
	 */
	protected function match_rules( array $rules, $relation, array $context ) {
		if ( empty( $rules ) ) {
			return false;
		}

		if ( 'AND' === $relation ) {
			foreach ( $rules as $rule ) {
				if ( ! $this->match_rule( $rule, $context ) ) {
					return false;
				}
			}

			return true;
		}

		foreach ( $rules as $rule ) {
			if ( $this->match_rule( $rule, $context ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Match one rule.
	 *
	 * @param array<string, mixed> $rule Rule data.
	 * @param array<string, mixed> $context Page context.
	 * @return bool
	 */
	protected function match_rule( array $rule, array $context ) {
		$type  = isset( $rule['type'] ) ? $rule['type'] : '';
		$value = isset( $rule['value'] ) ? $rule['value'] : '';

		switch ( $type ) {
			case 'entire_site':
				return true;
			case 'homepage':
				return ! empty( $context['is_homepage'] );
			case 'page_id':
				return (int) $value === (int) $context['current_page_id'];
			case 'post_type':
				return (string) $value === (string) $context['current_post_type'];
			case 'page_template':
				return (string) $value === (string) $context['page_template'];
			case 'all_products':
			case 'single_product':
				return ! empty( $context['is_product'] );
			case 'product_id':
				return (int) $value === (int) $context['current_product_id'];
			case 'product_category':
				return in_array( (int) $value, array_map( 'intval', (array) $context['product_cat_ids'] ), true ) || (int) $value === (int) $context['current_term_id'];
			case 'product_tag':
				return in_array( (int) $value, array_map( 'intval', (array) $context['product_tag_ids'] ), true );
			case 'product_archive':
			case 'all_product_archives':
				return ! empty( $context['is_product_archive'] );
			case 'shop_page':
				return ! empty( $context['is_shop'] );
			case 'logged_in':
				return ! empty( $context['is_logged_in'] );
			case 'guest':
				return empty( $context['is_logged_in'] );
			case 'device':
				return (string) $value === (string) $context['device'];
			case 'taxonomy_term':
				return (int) $value === (int) $context['current_term_id'];
			default:
				return false;
		}
	}

	/**
	 * Match date schedule.
	 *
	 * @param array<string, mixed> $rules Ruleset.
	 * @return bool
	 */
	protected function match_schedule( array $rules ) {
		$today = current_time( 'Y-m-d' );
		$start = isset( $rules['start_date'] ) ? sanitize_text_field( (string) $rules['start_date'] ) : '';
		$end   = isset( $rules['end_date'] ) ? sanitize_text_field( (string) $rules['end_date'] ) : '';

		if ( $start && $today < $start ) {
			return false;
		}

		if ( $end && $today > $end ) {
			return false;
		}

		return true;
	}

	/**
	 * Match device visibility if defined.
	 *
	 * @param array<string, mixed> $rules Ruleset.
	 * @param array<string, mixed> $context Context.
	 * @return bool
	 */
	protected function match_device( array $rules, array $context ) {
		$device = isset( $rules['device'] ) ? (string) $rules['device'] : 'all';

		if ( 'all' === $device || '' === $device ) {
			return true;
		}

		return $device === (string) $context['device'];
	}
}
