<?php
/**
 * Figuren_Theater Data Rss_Bridge.
 *
 * @package figuren-theater/data/rss_bridge
 */

namespace Figuren_Theater\Data\Rss_Bridge;

use FT_VENDOR_DIR;

use function add_action;
use function add_query_arg;
use function esc_url_raw;
use function is_admin;
use function is_network_admin;
use function is_user_admin;
use function plugins_url;
use function wp_parse_args;


const BASENAME   = 'rss-bridge/index.php';
const PLUGINPATH = FT_VENDOR_DIR . '/rss-bridge/' . BASENAME;

/**
 * Bootstrap module, when enabled.
 */
function bootstrap() {

	add_action( 'init', __NAMESPACE__ . '\\load_plugin' );
}

function load_plugin() {

	// Do only load in "normal" admin view
	// Not for:
	// - public views
	// - network-admin views
	// - user-admin views
	if ( is_network_admin() || is_user_admin() || ! is_admin() )
		return;
	
	#require_once PLUGINPATH;

    #bootstrap_detector();
    // $detector = new detector();


    $ft_link_pt = Post_Types\Post_Type__ft_link::NAME;

    // 
    add_action( "save_post_$ft_link_pt", [ Detector::get_instance(), 'find_importable_endpoint'], 10, 3 );   
}




/**
 * Generate the URL for the RSS Bridge API.
 *
 * @param  array Parameters to build the rss-bridge query string
 *
 * @return string The URL of the RSS Bridge API. 
 *                Example: https://figuren.test/content/mu-plugins/rss-bridge-master/?action=display&bridge=WordPressBridge&url=https%3A%2F%2Fjuliaraab.de%2F&limit=3&content-selector=&format=Atom
 */
function ft_generate_rss_bridge_url( array $bridge_info ) : string {
    // 
    // $rss_bridge_base_url = get_site_url( null, '/content/mu-plugins/rss-bridge-master/index.php' );
    $rss_bridge_base_url = plugins_url( 'index.php', dirname( PLUGINPATH ) );
    
    // Build the query string parameters for the RSS Bridge API URL.
    $params = wp_parse_args( 
        $bridge_info,
        [
            'action' => 'display',
            'format' => 'Atom',
        ]
    );

    // Combine the query string parameters with the base URL.
    return esc_url_raw( 
        add_query_arg( 
            $params, 
            $rss_bridge_base_url
        )
    );
}




/*
// DEBUG
add_action( 
    'sssadmin_menu',
    function(){
        \do_action( 'qm/debug', ft_generate_rss_bridge_url('https://juliaraab.test/feed') );
    },
    10,
    1
);
*/
