<?php
/**
 * Figuren_Theater Data.
 *
 * @package figuren-theater/data
 */

namespace Figuren_Theater\Data;

use Altis;
use function Altis\register_module;

/**
 * Register module.
 */
function register() {

	$default_settings = [
		'enabled'                      => true, // needs to be set
		'distributor-remote-quickedit' => false,
		'feed-pull'                    => false,
	];
	$options = [
		'defaults' => $default_settings,
	];

	Altis\register_module(
		'data',
		DIRECTORY,
		'Data',
		$options,
		__NAMESPACE__ . '\\bootstrap'
	);
}

/**
 * Bootstrap module, when enabled.
 */
function bootstrap() {

	Distributor\bootstrap();
	Distributor_Remote_Quickedit\bootstrap();
	Feed_Pull\bootstrap();
	FT_Network_Sourcelinks\bootstrap();
	Rss_Bridge\bootstrap();
	Term_Management_Tools\bootstrap();
	Utility_Taxonomy\bootstrap();
}
