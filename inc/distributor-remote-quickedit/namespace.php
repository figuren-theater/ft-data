<?php
/**
 * Figuren_Theater Data Distributor_Remote_Quickedit.
 *
 * @package figuren-theater/data/distributor_remote_quickedit
 */

namespace Figuren_Theater\Data\Distributor_Remote_Quickedit;

use FT_VENDOR_DIR;

use Figuren_Theater;
use function Figuren_Theater\get_config;

use function add_action;
use function is_admin;
use function is_network_admin;
use function is_user_admin;

const BASENAME   = 'distributor-remote-quickedit/distributor-remote-quickedit.php';
const PLUGINPATH = FT_VENDOR_DIR . '/wpackagist-plugin/' . BASENAME;

/**
 * Bootstrap module, when enabled.
 */
function bootstrap() {

	add_action( 'plugins_loaded', __NAMESPACE__ . '\\load_plugin', 9 );
}

function load_plugin() {

	$config = Figuren_Theater\get_config()['modules']['data'];
	if ( ! $config['distributor-remote-quickedit'] )
		return; // early

	// Do only load in "normal" admin view
	// Not for:
	// - and public views
	// - network-admin views
	// - user-admin views
	if ( ! is_admin() || is_network_admin() || is_user_admin() )
		return;
	
	require_once PLUGINPATH;
}
