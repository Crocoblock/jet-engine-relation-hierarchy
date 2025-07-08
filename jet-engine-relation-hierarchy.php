<?php
/**
 * Plugin Name: JetEngine - Relation Hierarchy Filters Addon
 * Plugin URI: #
 * Description: Description: Extends JetEngine and JetSmartFilters functionality by adding support for multi-level relation filtering (grandparent, grandchild, etc.) within listing queries.
 * Version:     1.0.0
 * Author:      Crocoblock
 * Author URI:  https://crocoblock.com/
 * License:     GPL-3.0+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 * Domain Path: /languages
 */

use Jet_Engine\Relations\Hierarchy_Filters;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Jet_Engine_Relation_Hierarchy {

	public function __construct() {
		require_once __DIR__ . '/includes/hierarchy-filters.php';

		Hierarchy_Filters::instance();
	}
}

new Jet_Engine_Relation_Hierarchy();