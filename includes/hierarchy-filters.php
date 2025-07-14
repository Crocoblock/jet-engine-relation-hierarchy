<?php

namespace Jet_Engine\Relations;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Hierarchy_Filters {
	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function __construct() {
		add_filter( 'jet-engine/relations/relation-filter-keys', array( $this, 'add_relation_filter_keys' ) );
		add_filter( 'jet-engine/relations/custom-relation-args', array( $this, 'register_custom_relation_args' ), 10, 4 );

		// Indexer common
		add_filter( 'jet-engine/relations/custom-indexer-rel-ids', array( $this, 'get_custom_indexer_rel_ids' ), 10, 5 );

		add_filter( 'jet-engine/relations/dynamic-queries', array( $this, 'add_dynamic_queries' ) );
	}

	function add_dynamic_queries( $queries ) {
		$queries['related_grandchildren'] = __( 'filters grandchildren items list by grandparents IDs', 'my-addon' );
		$queries['related_grandparents'] = __( 'filters grandparents items list by grandchildren IDs', 'my-addon' );
		return $queries;
	}

	public function add_relation_filter_keys( $relation_keys ) {
		$relation_keys[] = 'related_grandchildren';
		$relation_keys[] = 'related_grandparents';
		return $relation_keys;
	}

	public function register_custom_relation_args( $args_data, $type, $relation, $data ) {
		$data_result = $this->get_custom_relation_data( $type, $relation, $data['value'] );

		if ( ! empty( $data_result['object'] ) && ! empty( $data_result['rel_ids'] ) ) {
			$args_data['object']  = $data_result['object'];
			$args_data['rel_ids'] = $data_result['rel_ids'];
		}

		return $args_data;
	}

	public function get_custom_indexer_rel_ids( $rel_ids, $rel_type, $relation, $value, $type ) {
		$data_result = $this->get_custom_relation_data( $rel_type, $relation, $value );

		if ( ! empty( $data_result['rel_ids'] ) ) {
			$rel_ids = $data_result['rel_ids'];
		}

		return $rel_ids;
	}

	public function get_custom_relation_data( $type, $relation, $value ) {
		// Cache active relations
		$active_relations = jet_engine()->relations->get_active_relations();

		$result = array(
			'object'  => false,
			'rel_ids' => array(),
		);

		switch ( $type ) {
			case 'related_grandchildren':
				foreach ( $active_relations as $relation_item ) {
					// Skip the same relation
					if ( $relation_item->get_id() === $relation->get_id() ) {
						continue;
					}

					// Check if this relation is a child of the current one
					$child_rel_id = $relation_item->get_args( 'parent_rel' );
					if ( $child_rel_id !== $relation->get_id() ) {
						continue;
					}

					// Get IDs of children for the current value
					$rel_ids = $relation->get_children( $value, 'ids' );

					// Retrieve the grandchildren relation by its ID
					$child_relation = isset( $active_relations[ $relation_item->get_id() ] )
						? $active_relations[ $relation_item->get_id() ]
						: false;

					// If relation is missing, stop processing
					if ( ! $child_relation ) {
						break;
					}

					// Set the child object and related IDs for the grandchildren relation
					$result['object']  = $child_relation->get_args( 'child_object' );
					$result['rel_ids'] = $child_relation->get_children( $rel_ids, 'ids' );

					// Only process the first matching grandchildren relation
					break;
				}
				break;

			case 'related_grandparents':
				// Get the parent relation ID for the current relation
				$parent_rel_id = $relation->get_args( 'parent_rel' );
				if ( empty( $parent_rel_id ) ) {
					break;
				}

				// Get IDs of parents for the current value
				$rel_ids = $relation->get_parents( $value, 'ids' );

				// Retrieve the parent relation by its ID
				$parent_relation = isset( $active_relations[ $parent_rel_id ] )
					? $active_relations[ $parent_rel_id ]
					: false;

				// If relation is missing, stop processing
				if ( ! $parent_relation ) {
					break;
				}

				// Set the parent object and related IDs for the parent relation
				$result['object']  = $parent_relation->get_args( 'parent_object' );
				$result['rel_ids'] = $parent_relation->get_parents( $rel_ids, 'ids' );
				break;
		}

		$result['rel_ids'] = array_unique( $result['rel_ids'] );

		return $result;
	}

}