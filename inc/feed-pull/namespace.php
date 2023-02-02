<?php
/**
 * Figuren_Theater Data Feed_Pull.
 *
 * @package figuren-theater/data/feed_pull
 */

namespace Figuren_Theater\Data\Feed_Pull;

use Figuren_Theater\Network\Users;

use FT_VENDOR_DIR;

use function add_action;
use function add_filter;
use function delete_option;
use function do_blocks;
use function is_admin;
use function is_network_admin;
use function is_user_admin;

const BASENAME   = 'feed-pull/feed-pull.php';
const PLUGINPATH = FT_VENDOR_DIR . '/carstingaxion/' . BASENAME;

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
	if ( ! is_admin() || is_network_admin() || is_user_admin() )
		return;


	// $this->required_plugins = [
	//	'bulk-block-converter/bulk-block-converter.php',
	// ];

	require_once PLUGINPATH;

	add_action( 'init', __NAMESPACE__ . '\\load_plugin', 5 );

	// add_action( 'init', __NAMESPACE__ . '\\DEBUG__setup_feed_pull' );

}

function init() {

	// for debugging only
	delete_option( 'fp_deleted_syndicated' );




	// https://github.com/tlovett1/feed-pull/blob/45d667c1275cca0256bd03ed6fa1655cdf26f064/includes/class-fp-pull.php#L274
	add_filter( 'fp_pre_post_insert_value', __NAMESPACE__ . '\\fp_pre_post_insert_value', 10, 4 );



	add_filter( 'fp_post_args', __NAMESPACE__ . '\\fp_post_args', 10, 3 );
}


/**
 * [fp_pre_post_insert_value description]
 * @param  [mixed] $pre_filter_post_value [description]
 * @param  [array] $field                 [description]
 *
 *   array (
 *     'source_field' => 'guid',
 *     'destination_field' => 'guid',
 *     'mapping_type' => 'post_field', 
 *   ), 
 *   
 * @param  [WP_Post] $post                  [description]
 * @param  [int] $source_feed_id        [description]
 * 
 * @return [type]                        [description]
 */
function fp_pre_post_insert_value( $pre_filter_post_value, $field, $post, $source_feed_id  ) {
	#error_log(var_export(array( $pre_filter_post_value, $field, '$post', $source_feed_id ),true));


	if ( 'post_title' == $field['destination_field'] )
		return \sanitize_text_field( $pre_filter_post_value );



	if ( 'post_excerpt' == $field['destination_field'] )
		return \sanitize_textarea_field( $pre_filter_post_value );



	if ( 'post_content' == $field['destination_field'] ) {
	/*
		$tags_to_strip = Array("figure","font" );
		foreach ($tags_to_strip as $tag)
		{
		    $pre_filter_post_value = preg_replace("/<\\/?" . $tag . "(.|\\s)*?>/",'',$pre_filter_post_value);

		}		serialize_block( $block );*/
		// return \wpautop( \wp_kses_post( $pre_filter_post_value ), true );
		// return \do_blocks( \wp_kses_post( $pre_filter_post_value ), true );
		return do_blocks( $pre_filter_post_value );
	}


	// all other fields
	return $pre_filter_post_value;
}	



function fp_post_args( $new_post_args, $post, $source_feed_id ) : array {

	// set author to machine user
	$new_post_args['post_author']           = Users\ft_bot::id();

	// TODO
	// add (yet non-existent) 'imported_from' taxonomy-term

	// strip (maybe) filled excerpt
	// if we can auto-generate it 
	if ( !empty($new_post_args['post_content']) && !empty($new_post_args['post_excerpt']) )
		unset($new_post_args['post_excerpt']);

	return $new_post_args;
}


######## DEV & DEBUG #############################


function DEBUG__setup_feed_pull($value='') {

	#do_action( 'qm/debug', $arg );

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

	\wp_die(
		'<pre>'.
		var_export(
			array(
			#	__FILE__,
			#	
				get_meta_tags($url),

				// post_title
				'post_title' => $parsed_url['host'],

				// META fp_feed_url
				'fp_feed_url' => getRSSLocation($url, $html), # http://hakre.wordpress.com/feed/
	#			getFeedUrl($url), // returns: 'https://vimeo.com/juliaraab'

				// is WP
				'is WP' => isProbablyWordPress($url),

				'has /wp-admin' => webItemExists( "$unslashed_url/wp-admin" ),
				'has /wp-json' => json_validator( @file_get_contents( "$url/wp-json/wp/v2" )),

			),
			true
		).
		'</pre>'
	);
}

function isProbablyWordPress($url) {
	if (strpos($url, 'wordpress.com'))
		return true;

	$meta_tags = get_meta_tags($url);
	if (!empty($meta_tags['generator']) && stripos( $meta_tags['generator'], 'wordpress' ) )
		return true;


	$url = \untrailingslashit( $url );
	if (
		( webItemExists( "$url/wp-admin" ) ) 
		&&
		( json_validator( @file_get_contents( "$url/wp-json" )) )
	) {
		return true;
	} else {
		return false;
	}
}

//JSON Validator function
function json_validator($data=NULL) : bool {
  if (!empty($data)) {
                @json_decode($data);
                return (json_last_error() === JSON_ERROR_NONE);
        }
        return false;
}


/**
 * Check if an item exists out there in the "ether".
 *
 * @param string $url - preferably a fully qualified URL
 * @return boolean - true if it is out there somewhere
 */
function webItemExists($url) : bool {
    if (($url == '') || ($url == null)) { return false; }
   // $response = \wp_remote_head( $url, array( 'timeout' => 5 ) );
    $response = \wp_safe_remote_head( $url, array( 'timeout' => 5 ) );
    $accepted_status_codes = array( 200, 301, 302 );
    // $accepted_status_codes = array( 200 );
    $response_code = \wp_remote_retrieve_response_code( $response );
    if ( ! \is_wp_error( $response ) && in_array( $response_code, $accepted_status_codes ) )
    {
        return true;
    }
    return false;
}



/**
 * @link https://stackoverflow.com/questions/6968107/how-to-fetch-rss-feed-url-of-a-website-using-php
 * @link http://keithdevens.com/weblog/archive/2002/Jun/03/RSSAuto-DiscoveryPHP
 */
function getRSSLocation( $location, $html ){
    if(!$html or !$location){
        return false;
    }else{
        #search through the HTML, save all <link> tags
        # and store each link's attributes in an associative array
        preg_match_all('/<link\s+(.*?)\s*\/?>/si', $html, $matches);
        $links = $matches[1];
        $final_links = array();
        $link_count = count($links);
        for($n=0; $n<$link_count; $n++){
            $attributes = preg_split('/\s+/s', $links[$n]);
            foreach($attributes as $attribute){
                $att = preg_split('/\s*=\s*/s', $attribute, 2);
                if(isset($att[1])){
                    $att[1] = preg_replace('/([\'"]?)(.*)\1/', '$2', $att[1]);
                    $final_link[strtolower($att[0])] = $att[1];
                }
            }
            $final_links[$n] = $final_link;
        }
        #now figure out which one points to the RSS file
        for($n=0; $n<$link_count; $n++){
            if(strtolower($final_links[$n]['rel']) == 'alternate'){
                if(strtolower($final_links[$n]['type']) == 'application/rss+xml'){
                    $href = $final_links[$n]['href'];
                }
                if(!$href and strtolower($final_links[$n]['type']) == 'text/xml'){
                    #kludge to make the first version of this still work
                    $href = $final_links[$n]['href'];
                }
                if($href){
                    if(strstr($href, "://") !== false){ #if it's absolute
                        $full_url = $href;
                    }else{ #otherwise, 'absolutize' it
                        $url_parts = parse_url($location);
                        #only made it work for http:// links. Any problem with this?
                        $full_url = "$url_parts[scheme]://$url_parts[host]";
                        if(isset($url_parts['port'])){
                            $full_url .= ":$url_parts[port]";
                        }
                        if($href{0} != '/'){ #it's a relative link on the domain
                            $full_url .= dirname($url_parts['path']);
                            if(substr($full_url, -1) != '/'){
                                #if the last character isn't a '/', add it
                                $full_url .= '/';
                            }
                        }
                        $full_url .= $href;
                    }
                    return $full_url;
                }
            }
        }
        return false;
    }
}


function getFeedUrl($url){
    if(@file_get_contents($url)){
        preg_match_all('/<link\srel\=\"alternate\"\stype\=\"application\/(?:rss|atom)\+xml\"\stitle\=\".*href\=\"(.*)\"\s\/\>/', file_get_contents($url), $matches);
        return $matches[1][0];
    }
    return false;
}
