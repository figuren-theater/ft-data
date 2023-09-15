<?php
/**
 * Figuren_Theater Data Term_Management_Tools.
 *
 * @package figuren-theater/ft-data
 */

namespace Figuren_Theater\Data\Term_Management_Tools;

use FT_VENDOR_DIR;

use function add_action;
use function is_admin;
use function wp_doing_cron;

const BASENAME   = 'term-management-tools/term-management-tools.php';
const PLUGINPATH = '/wpackagist-plugin/' . BASENAME;

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

	if ( ! is_admin() || wp_doing_cron() ) {
		return;
	}

	require_once FT_VENDOR_DIR . PLUGINPATH; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingCustomConstant
}
