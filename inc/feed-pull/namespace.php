<?php
/**
 * Figuren_Theater Data Feed_Pull.
 *
 * @package figuren-theater/data/feed_pull
 */

namespace Figuren_Theater\Data\Feed_Pull;

use Figuren_Theater\Network\Users;

// use FT_VENDOR_DIR;
use WP_PLUGIN_DIR;

use function add_action;
use function is_admin;
use function is_network_admin;
use function is_user_admin;
use function wp_doing_ajax;
use function wp_doing_cron;

const BASENAME   = 'feed-pull/feed-pull.php';
# const PLUGINPATH = FT_VENDOR_DIR . '/carstingaxion/' . BASENAME;
const PLUGINPATH = WP_PLUGIN_DIR . '/' . BASENAME;

const SOURCE_POSTTYPE      = 'fp_feed';
const DESTINATION_POSTTYPE = 'post';

/**
 * Bootstrap module, when enabled.
 */
function bootstrap() {

	add_action( 'init', __NAMESPACE__ . '\\load_plugin', 0 );
}

function load_plugin() {

	// Do only load in "normal" admin view
	// Not for:
	// - public views
	// - network-admin views
	// - user-admin views
	if ( is_network_admin() || is_user_admin() || ( ! is_admin() && ! wp_doing_cron() && ! wp_doing_ajax() ) )
		return;


	// $this->required_plugins = [
	//	'bulk-block-converter/bulk-block-converter.php',
	// ];

	require_once PLUGINPATH;

	// create new 'fp_feed' posts, when a new 'ft_link' post is created
	// which has an importable endpoint
	bootstrap_auto_setup();

	// everything related to importing normal posts from feeds
	bootstrap_import();



	// add_action( 'admin_menu', __NAMESPACE__ . '\\DEBUG__setup_feed_pull' );

}




######## DEV & DEBUG #############################

/*
function DEBUG__setup_feed_pull($value='') {


	// $url = 'https://juliaraab.de';
	$url = 'http://juliaraab.de';
	// $url = 'http://carsten-bach.de';
	$url = 'http://maxigrehl.de';
	// $url = 'http://buehnen-halle.de/start';
	$url = 'http://hakre.wordpress.com/';
	// $url = 'http://kommaklar-ey.de';
	$parsed_url = parse_url($url);


	$html = file_get_contents($url);

	$unslashed_url = \untrailingslashit( $url );

	$debug_return = var_export(
		array(
		#	__FILE__,
		#	
			get_meta_tags($url),

			// post_title
			'post_title' => $parsed_url['host'],

			// META fp_feed_url
			'getRSSLocation' => getRSSLocation($url, $html), # http://hakre.wordpress.com/feed/
	#			getFeedUrl($url), // returns: 'https://vimeo.com/juliaraab'

			// is WP
			'is WP' => isProbablyWordPress($url),

			'has /wp-admin' => webItemExists( "$unslashed_url/wp-admin" ),
			'has /wp-json' => json_validator( @file_get_contents( "$url/wp-json/wp/v2" )),

		),
		true
	);
	

	\do_action( 'qm/debug', $debug_return );

	// \wp_die('<pre>'.$debug_return.'</pre>');
}*/



