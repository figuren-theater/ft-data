<?php
/**
 * Figuren_Theater Data.
 *
 * @package figuren-theater/data
 */

namespace Figuren_Theater\Data;

use function Altis\register_module;

/**
 * Register module.
 */
function register() {
	Altis\register_module(
		'data',
		DIRECTORY,
		'Data',
		[
			'defaults' => [
				'enabled' => true,
			],
		],
		__NAMESPACE__ . '\\bootstrap'
	);
}

/**
 * Bootstrap module, when enabled.
 */
function bootstrap() {

	// ...\bootstrap();
}
