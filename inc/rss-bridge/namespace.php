<?php
/**
 * Figuren_Theater Data Rss_Bridge.
 *
 * @package figuren-theater/ft-data
 */

namespace Figuren_Theater\Data\Rss_Bridge;

use Figuren_Theater;

use function add_action;
use function is_admin;
use function is_network_admin;
use function is_user_admin;

const BASENAME   = 'rss-bridge/index.php';
const PLUGINPATH = '/rss-bridge/' . BASENAME;

/**
 * Bootstrap module, when enabled.
 *
 * @return void
 */
function bootstrap() :void {

	add_action( 'init', __NAMESPACE__ . '\\load_plugin' );
}
/**
 * Conditionally load the plugin itself and its modifications.
 *
 * @return void
 */
function load_plugin() :void {
	$config = Figuren_Theater\get_config()['modules']['data'];
	if ( ! $config['feed-pull'] ) {
		return;
	}

	// Do only load in "normal" admin view
	// Not for:
	// - public views
	// - network-admin views
	// - user-admin views.
	if ( is_network_admin() || is_user_admin() || ! is_admin() ) {
		return;
	}

	// require_once FT_VENDOR_DIR . PLUGINPATH; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingCustomConstant // @todo #19 When / where to load rss-bridge?
}





