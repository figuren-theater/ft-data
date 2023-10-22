<?php
/**
 * Figuren_Theater Data Utility_Taxonomy.
 *
 * @package figuren-theater/ft-data
 */

namespace Figuren_Theater\Data\Utility_Taxonomy;

use FT_VENDOR_DIR;

use function add_action;

const BASENAME   = 'utility-taxonomy/plugin.php';
const PLUGINPATH = '/humanmade/' . BASENAME;

/**
 * Bootstrap module, when enabled.
 *
 * @return void
 */
function bootstrap() :void {

	add_action( 'plugins_loaded', __NAMESPACE__ . '\\load_plugin' );
}

/**
 * Conditionally load the plugin itself and its modifications.
 *
 * @return void
 */
function load_plugin() :void {

	require_once FT_VENDOR_DIR . PLUGINPATH; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingCustomConstant
}
