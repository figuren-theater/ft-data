<?php
/**
 * Figuren_Theater Data Distributor_Remote_Quickedit.
 *
 * @package figuren-theater/data/distributor_remote_quickedit
 */

namespace Figuren_Theater\Data\Distributor_Remote_Quickedit;

use FT_VENDOR_DIR;

use function add_action;

const BASENAME   = 'distributor-remote-quickedit/distributor-remote-quickedit.php';
const PLUGINPATH = FT_VENDOR_DIR . '/carstingaxion/' . BASENAME;

/**
 * Bootstrap module, when enabled.
 */
function bootstrap() {

	add_action( 'plugins_loaded', __NAMESPACE__ . '\\load_plugin', 9 );
}

function load_plugin() {

	// Do only load in "normal" admin view
	// and public views
	// Not for:
	// - network-admin views
	// - user-admin views
	#if ( is_network_admin() || is_user_admin() )
	#	return;
	
	require_once PLUGINPATH;
}
