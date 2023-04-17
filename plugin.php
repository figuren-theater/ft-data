<?php
/**
 * Plugin Name:     figuren.theater | Data
 * Plugin URI:      https://github.com/figuren-theater/ft-data
 * Description:     Data structures, posttypes & taxonomies together with the tools to handle this data for a WordPress multisite network like figuren.theater
 * Author:          figuren.theater
 * Author URI:      https://figuren.theater
 * Text Domain:     figurentheater
 * Domain Path:     /languages
 * Version:         1.0.24
 *
 * @package         Figuren_Theater\Data
 */

namespace Figuren_Theater\Data;

const DIRECTORY = __DIR__;

add_action( 'altis.modules.init', __NAMESPACE__ . '\\register' );
