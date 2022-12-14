<?php
/**
 * Figuren_Theater Data Utility_Taxonomy.
 *
 * @package figuren-theater/data/utility_taxonomy
 */

namespace Figuren_Theater\Data\Utility_Taxonomy;

use FT_VENDOR_DIR;

use function add_action;

const BASENAME   = 'utility-taxonomy/plugin.php';
const PLUGINPATH = FT_VENDOR_DIR . '/humanmade/' . BASENAME;

/**
 * Bootstrap module, when enabled.
 */
function bootstrap() {

	add_action( 'plugins_loaded', __NAMESPACE__ . '\\load_plugin' );
}

function load_plugin() {

	require_once PLUGINPATH;
}
