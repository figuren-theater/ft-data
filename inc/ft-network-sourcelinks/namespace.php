<?php
/**
 * Figuren_Theater Data FT_Network_Sourcelinks.
 *
 * @package figuren-theater/data/ft_network_sourcelinks
 */

namespace Figuren_Theater\Data\FT_Network_Sourcelinks;

use FT_VENDOR_DIR;

use function add_action;
// use function is_network_admin;
// use function is_user_admin;

const BASENAME   = 'ft-network-sourcelinks/ft-network-sourcelinks.php';
const PLUGINPATH = FT_VENDOR_DIR . '/figuren-theater/' . BASENAME;

/**
 * Bootstrap module, when enabled.
 *
 * @return void
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
