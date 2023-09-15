<?php
/**
 * Figuren_Theater Data Rss_Bridge.
 *
 * @package figuren-theater/ft-data
 */

namespace Figuren_Theater\Data\Rss_Bridge;

use FT_VENDOR_DIR;

use Figuren_Theater;
use function Figuren_Theater\get_config;

use function add_action;
use function is_admin;
use function is_network_admin;
use function is_user_admin;


const BASENAME   = 'rss-bridge/index.php';
const PLUGINPATH = '/rss-bridge/' . BASENAME;

/**
 * Bootstrap module, when enabled.
 */
function bootstrap() {

	add_action( 'init', __NAMESPACE__ . '\\load_plugin' );
}

function load_plugin() {
    $config = Figuren_Theater\get_config()['modules']['data'];
    if ( ! $config['feed-pull'] )
        return; // early

	// Do only load in "normal" admin view
	// Not for:
	// - public views
	// - network-admin views
	// - user-admin views
	if ( is_network_admin() || is_user_admin() || ! is_admin() )
		return;

	#require_once PLUGINPATH;

}





