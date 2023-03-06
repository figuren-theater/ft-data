<?php
/**
 * Figuren_Theater Data Term_Management_Tools.
 *
 * @package figuren-theater/data/term_management_tools
 */

namespace Figuren_Theater\Data\Term_Management_Tools;

use FT_VENDOR_DIR;

use function add_action;
use function is_admin;
use function wp_doing_cron;

const BASENAME   = 'term-management-tools/term-management-tools.php';
const PLUGINPATH = FT_VENDOR_DIR . '/wpackagist-plugin/' . BASENAME;

/**
 * Bootstrap module, when enabled.
 */
function bootstrap() {

	add_action( 'plugins_loaded', __NAMESPACE__ . '\\load_plugin' );
}

function load_plugin() {

	if ( ! is_admin() || wp_doing_cron() )
		return;

	require_once PLUGINPATH;
}
