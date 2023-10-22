<?php
declare(strict_types=1);
/**
 * Figuren_Theater Data Rss_Bridge.
 *
 * @package figuren-theater/ft-data
 */

namespace Figuren_Theater\Data\Rss_Bridge;

use function add_query_arg;
use function esc_url_raw;
use function is_wp_error;
use function plugins_url;
use function set_url_scheme;
use function wp_parse_args;
use function wp_remote_get;
use function wp_remote_retrieve_response_code;

/**
 *
 *
 * @param  string     $domain
 *
 * @return Adapter|null
 */
function get_adapter_for_domain( string $domain ): ?Adapter {
	$bridges = get_bridges();
	foreach ( $bridges as $bridge ) {

		if ( preg_match( $bridge['pattern'], $domain, $matches ) ) {
			return $bridge;
		}
	}
	return null;
}

/**
 *
 *
 * @param  string     $platform
 *
 * @return Adapter|null
 */
function get_adapter_for_platform( string $platform ): ?Adapter {
	$bridges = get_bridges();
	if ( isset( $bridges[ $platform ] ) ) {
		return $bridges[ $platform ];
	}
	return null;
}

/**
 *
 *
 * @param  string $url
 *
 * @return bool
 */
function is_feed_ok( string $url ) : bool {

	$response = wp_remote_get( set_url_scheme( $url, 'https' ) );

	if ( ! is_wp_error( $response ) ) {
		$status = wp_remote_retrieve_response_code( $response );
		if ( 200 === $status ) {
			return true;
		}
	}

	return false;
}

/**
 * Detect which rss-brige to use,
 * based on the given URL and|or
 * the suggested bridge given by the author
 *
 * @param   string    $url       The websites source URL to get data from
 * @param   string    $platform  Suggested bridge given by the author during post_creation.
 *
 * @return  string URL to the feed
 */
function get_bridged_url( string $url, ?string $platform = null ): ?string {
	// Remove any trailing slashes from the URL.
	$url = rtrim( $url, '/' );

	$adapter = false;

	// Extract the domain from the URL.
	if ( $platform ) {
		$adapter = get_adapter_for_platform( $platform );
	}

	//
	// Check if we have an adapter for the domain
	// if no platform was given or
	// even if a platform was set, but nothing was found.
	if ( ! $adapter ) {
		$adapter = get_adapter_for_domain( $url );
	}

	// Try to find an adapter.
	if ( $adapter instanceof Has_Rss_Bridge ) {
		$bridged_url = $adapter->get_bridge_url();
	}

	// WORKING, used as fallback.
	if ( $adapter instanceof Has_Public_Feed && ! $bridged_url ) {
		// Get the normal feed.
		$bridged_url = $adapter->get_feed_url();
	}

	// Make sure we have a valid response,
	// which means status codes 200, 301 or 302.
	if ( is_feed_ok( $bridged_url ) ) {
		return $bridged_url;
	}

	return null;
}

//
// just for debugging ################################
//

/*
echo '<pre>';

echo "<br><br>";
echo "<br><br>";
$given_urls = [
	['https://scubulus.org','webflow'],
	['https://tata.live','blogspot'],
	['https://ololololololololol.com/','tumblr'],
	['https://theaterpaedagpgik.leipzig','jimdo'],
	['https://mein.schickes.figuren.theater/','WordPress'],
	['https://ft123.wordpress.com/','wordpress'],
	['https://ft123.abc/','WordPress'],
	['https://ft123.xyz/abcdefghjik/','WordPress'],
];

$output_urls = $given_urls; // this line just helps debugging
foreach ($output_urls as $given_url) {
	$rss_bridge_url = get_bridged_url($given_url[0], $given_url[1]);
	// $rss_bridge_url = get_bridge_from_url($given_url);
	echo "$given_url[0]\n";
	echo "==> $rss_bridge_url\n\n\n";
}


echo "<br><br>";
echo "<br><br>";
$given_urls = [
	'https://scubulus.webflow.com/',
	'https://tata.blogspot.com/',
	'https://ololololololololol.tumblr.com/',
	'https://theaterpaedagpgik-leipzig.jimdo.com',
	'https://ft123.wordpress.com/cat-or-tag-or-term/',
	'https://www.facebook.com/figuren.theater.dach/',
	'https://www.tiktok.com/@olaf_scholz',
	'https://medium.com/@ololololololololol/',
	'https://twitter.com/figuren_theater',
	'https://twitter.com/OpenAI/status/1104858619893089282',
	'https://www.youtube.com/channel/UCBR8-60-B28hp2BmDPdntcQ',
	'https://www.youtube.com/@paulpanether',
	'https://www.flickr.com/photos/127356892@N06/49741958331/in/album-72157713692908818/',
];

$output_urls = $given_urls; // this line just helps debugging
foreach ($output_urls as $given_url) {
	$rss_bridge_url = get_bridged_url($given_url);
	// $rss_bridge_url = get_bridge_from_url($given_url);
	echo "$given_url\n";
	echo "==> $rss_bridge_url\n\n\n";
}


echo '</pre>';

// var_dump(get_bridges());
exit();
*/
