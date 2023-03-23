<?php
/**
 * Figuren_Theater Data Rss_Bridge.
 *
 * @package figuren-theater/data/rss_bridge
 */

namespace Figuren_Theater\Data\Rss_Bridge;

use WP_Post;

use function do_action;
use function esc_url;
use function untrailingslashit;
use function wp_set_object_terms;

/**
 * Bootstrap module, when enabled.

function bootstrap_detector() {

	add_action( 'plugins_loaded', __NAMESPACE__ . '\\load_plugin', 9 );
}

function load_plugin() {

	// Do only load in "normal" admin view
	// and public views
	// Not for:
	// - network-admin views
	// - user-admin views
	#if ( is_network_admin() || is_user_admin() )
	#	return;
	
	require_once PLUGINPATH;
}
 */



/**
 * Detect which rss-brige to use,
 * based on the given URL and|or 
 * the suggested bridge given by the author
 *
 * @param   string    $url              The websites source URL to get data from
 * @param   string    $suggested_bridge Suggested bridge given by the author during post_creation.
 * 
 * @return  array List of parameters for ft_generate_rss_bridge_url()
 *                'bridge' => Required. Name of the bridge to use, following the naming-conventions from rss-bridge
 *                '...'    => Required|Optional. Other parameters depend on the bridge, see definition for available options.
 */
function ft_detect_rss_bridge( string $url='', string $suggested_bridge='' ) : array {
    $bridge_info = [];

    // defaults
    $limit = 3;
    $url   = rawurlencode( $url );

    if ( // Bail, if no data to work with
        ! is_string( $url ) ||
        empty( $url ) ||
        ! is_string( $suggested_bridge ) ||
        empty( $suggested_bridge )
    ) {
        return $bridge_info;
    }

	// Does NOT work
	// 
	// Youtube
	// https://figuren.test/content/mu-plugins/rss-bridge-master/?action=detect&url=https://www.youtube.com/channel/UCpGlwdRlimIXuPMEg7mw5ew&format=html
	// https://figuren.test/content/mu-plugins/rss-bridge-master/?action=detect&url=https://www.youtube.com/@juliaraab4423&format=html
	// 
	// Flickr
	// https://figuren.test/content/mu-plugins/rss-bridge-master/?action=detect&url=https://www.flickr.com/photos/carstingaxion&format=html
	// 
	// Twitch
	// https://figuren.test/content/mu-plugins/rss-bridge-master/?action=detect&url=https://www.twitch.tv/ryanwelchercodes&format=html
	// 
	// 
	// 
	// Works very well
	// 
	// Yotube (17. Versuch)
	// https://figuren.test/content/mu-plugins/rss-bridge-master/?action=display&bridge=YoutubeBridge&context=By+custom+name&custom=%40juliaraab4423&duration_min=&duration_max=&format=Html
	// https://figuren.test/content/mu-plugins/rss-bridge-master/?action=display&bridge=YoutubeBridge&context=By+channel+id&c=UCpGlwdRlimIXuPMEg7mw5ew&duration_min=&duration_max=&format=Html
	// 
	// Flickr
	// https://figuren.test/content/mu-plugins/rss-bridge-master/?action=display&bridge=FlickrBridge&context=By+username&u=carstingaxion&content=uploads&media=all&sort=date-posted-desc&format=Html
	// 
	// Twitch
	// https://figuren.test/content/mu-plugins/rss-bridge-master/?action=display&bridge=TwitchBridge&channel=ryanwelchercodes&type=archive&format=Html
	// 
	// 
	// Twitter
	// https://figuren.test/content/mu-plugins/rss-bridge-master/?action=display&bridge=TwitterBridge&context=By+username&u=juliaraab&format=Html
	// https://figuren.test/content/mu-plugins/rss-bridge-master/?action=detect&url=https://twitter.com/juliaraab&format=html

    $bridge = ($suggested_bridge)?: 'WordPressBridge';

    switch ($bridge) {
        case 'WordPressBridge':
            $bridge_info = [
                'limit'   => $limit,
                'url'     => $url, // full url
            ];
            break;
        
    #    case 'YoutubeBridge':
    #        $bridge_info = [
    #            'duration_min' => 0,
    #            'duration_max' => 240,
    #            'u'            => $url,
    #        ];
    #        break;
        
        default:
            # code...
            break;
    }
    
    $bridge_info['bridge'] = $bridge;

    return $bridge_info;
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


/**
 * Detect connectable Rss-Bridges by a given URL
 */
class Detector
{

	/**
	 * The Class Object
	 */
	static private $instance = null;
	
	function __construct() {}


	public static function get_importable_services() : array {
		
		return [
			// Example
			// do not add any protocoll
			// 
			// 'url-to-search.domain' => '%s/importable/endpoint/',
			
			// 
			'.blogspot.com'  => '%s/feeds/posts/default',
			
			// !!
			'.jimdo.com'     => '%s/rss/blog/',
			'.jimdofree.com' => '%s/rss/blog/',
			
			// 
			'.tumblr.com'    => '%s/rss',
			
			// 
			'vimeo.com'      => '%s/videos/rss',
			
			// 
			'wix.com'        => '%s/blog-feed.xml',
			
			// 
			'wordpress.com'  => '%s/feed/',
			
			// 
			'youtube.com'    => '%s',
			
			// 
			// 'medium.com/example-site' => 'https://medium.com/feed/example-site',
			// 
			// 'twitter.com/example-site' => 'https://nitter.com/...???.../example-site/feed/',
			// 
			// 'flickr.com/example-site' => 'https://flickr.com/...???.../some-cryptic-flickr-id',


			// NO WAY
			// - other than a sarcastic blog post - 
			// 
			// facebook.com
			// weebly.com

		];

	}

	public static function find_importable_endpoint( int $post_ID, WP_Post $post, bool $update ) : void {
		
		// run only on the first run
		if ( $update )
			return;

		// make sure we have anything to work with
		if ( empty( $post->post_content ) )
			return;

		// make sure it is a well formed URL
		$new_url = esc_url( 
			$post->post_content,
			[
				'http',
				'https'
			],
			'db'
		);
		if ( empty( $new_url ) )
			return;

		$new_url = untrailingslashit( $new_url );

		// well prepared,
		// let's go
		// 
		// hand the URL to our RSS-detective
		if ( static::has_importable_endpoint( $new_url ) ){
			// we found something ...
		}
		#	wp_set_object_terms( 
		#		$post_ID,
		#		[
		#			'is-importable',
		#		],
		#		'link_category',
		#		true
		#	);

	}



	public static function has_importable_endpoint( string $new_url ) : bool {

		$found = false;
		foreach ( static::get_importable_services() as $url_to_search => $pattern ) {
	
			if ( $found )
				return $found;

			if ( false !== strpos( $new_url, $url_to_search ) ) {

				#\do_action( 'qm/info', sprintf( $pattern, $new_url ) . ' can be imported.' );
				$found = true;
				do_action( 
					__NAMESPACE__ . '\\found_importable_endpoint',
					sprintf( $pattern, $new_url )
				);
			} 
		}

		if ( $found )
			return $found;

		do_action( 'qm/warning', '{new_url} kann nicht importiert werden.', [
			'new_url' => $new_url,
		] );

		return $found;
	}



	public static function get_instance() {
		if ( null === self::$instance )
			self::$instance = new self;
		return self::$instance;
	}
}
