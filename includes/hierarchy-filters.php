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

		add_action( 'jet-smart-filters/admin/register-dynamic-query', array( $this, 'helper_dynamic_query' ) );
	}

	/**
	 * Admin dynamic query for JSF query variable
	 */
	public function helper_dynamic_query( $dynamic_query_manager ) {
		$relations = jet_engine()->relations->get_active_relations();

		if ( ! $relations ) {
			return;
		}

		$relations_list = array(
			'related_grandchildren' => __( 'filters grandchildren items list by grandparents IDs', 'jet-engine' ),
			'related_grandparents'  => __( 'filters grandparents items list by grandchildren IDs', 'jet-engine' )
		);

		$relations_options = array();
		foreach ( $relations as $relation_item ) {
			if ( ! empty( $relation_item->get_args('parent_rel') ) ) {
				$relations_options[$relation_item->get_id()] = $relation_item->get_relation_name();
			}
		}

		foreach ( $relations_list as $relation_key => $relation_label ) {
			$relation_dynamic_query_item = new class( $relation_key, $relation_label, $relations_options ) {

				public $key;
				public $label;
				public $options;

				public function __construct( $key, $label, $options ) {
					$this->key     = $key;
					$this->label   = $label;
					$this->options = $options;
				}

				public function get_name() {
					return $this->key;
				}

				public function get_label() {
					return 'JetEngine: ' . $this->label;
				}

				public function get_extra_args() {
					return array(
						'relation' => array(
							'type'        => 'select',
							'title'       => __( 'Relation', 'jet-engine' ),
							'placeholder' => __( 'Select relation...', 'jet-engine' ),
							'options'     => $this->options,
						),
					);
				}

				public function get_delimiter() {
					return '*';
				}
			};

			$dynamic_query_manager->register_item( $relation_dynamic_query_item );
		}
	}

	public function add_relation_filter_keys( $relation_keys ) {
		$relation_keys[] = 'related_grandchildren';
		$relation_keys[] = 'related_grandparents';
		return $relation_keys;
	}

	public function register_custom_relation_args( $args_data, $type, $relation, $data ) {
		// Cache active relations
		$active_relations = jet_engine()->relations->get_active_relations();

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
					$rel_ids = $relation->get_children( $data['value'], 'ids' );

					// Retrieve the grandchildren relation by its ID
					$child_relation = isset( $active_relations[ $relation_item->get_id() ] )
						? $active_relations[ $relation_item->get_id() ]
						: false;

					// If relation is missing, stop processing
					if ( ! $child_relation ) {
						break;
					}

					// Set the child object and related IDs for the grandchildren relation
					$args_data['object']  = $child_relation->get_args( 'child_object' );
					$args_data['rel_ids'] = $child_relation->get_children( $rel_ids, 'ids' );

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
				$rel_ids = $relation->get_parents( $data['value'], 'ids' );

				// Retrieve the parent relation by its ID
				$parent_relation = isset( $active_relations[ $parent_rel_id ] )
					? $active_relations[ $parent_rel_id ]
					: false;

				// If relation is missing, stop processing
				if ( ! $parent_relation ) {
					break;
				}

				// Set the parent object and related IDs for the parent relation
				$args_data['object']  = $parent_relation->get_args( 'parent_object' );
				$args_data['rel_ids'] = $parent_relation->get_parents( $rel_ids, 'ids' );
				break;
		}

		return $args_data;
	}

}