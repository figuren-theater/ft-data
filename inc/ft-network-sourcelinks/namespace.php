<?php
/**
 * Figuren_Theater Data FT_Network_Sourcelinks.
 *
 * @package figuren-theater/ft-data
 */

namespace Figuren_Theater\Data\FT_Network_Sourcelinks;

use FT_VENDOR_DIR;
use function add_action;

const BASENAME   = 'ft-network-sourcelinks/ft-network-sourcelinks.php';
const PLUGINPATH = '/figuren-theater/' . BASENAME;

/**
 * Bootstrap module, when enabled.
 *
 * @return void
 */
function bootstrap() {

	add_action( 'plugins_loaded', __NAMESPACE__ . '\\load_plugin', 9 );
}

/**
 * Conditionally load the plugin itself and its modifications.
 *
 * @return void
 */
function load_plugin() :void {

	require_once FT_VENDOR_DIR . PLUGINPATH; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingCustomConstant
}
