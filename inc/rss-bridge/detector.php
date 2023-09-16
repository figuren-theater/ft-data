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

const CustomDomainRegex = '/^[a-zA-Z0-9]+([\-\.]{1}[a-zA-Z0-9]+)*\.[a-zA-Z]{2,}$/i';

/**
 * Extracts the custom domain from a given URL.
 *
 * @param string $url The URL to extract the custom domain from.
 *
 * @return string|null The extracted custom domain, or null if not found.
 */
function get_custom_domain( string $url ): ?string {
	// Get the host name from the URL.
	$parts = parse_url( $url );
	if ( ! isset( $parts['host'] ) ) {
		return null;// Invalid URL, no host found.
	}
	$host = $parts['host'];

	// Check if the custom domain is valid.
	$matches = [];
	// $customDomainRegex = '/^[a-zA-Z0-9]+([\-\.]{1}[a-zA-Z0-9]+)*\.[a-zA-Z]{2,}$/i';
	$customDomainRegex = CustomDomainRegex;
	preg_match( $customDomainRegex, $host, $matches );

	return isset( $matches[0] ) ? $matches[0] : null;
}

function get_bridge_for_domain( string $domain ): ?array {
	$bridges = get_bridges();
	foreach ( $bridges as $bridge ) {

		if ( preg_match( $bridge['pattern'], $domain, $matches ) ) {
			return $bridge;
		}
	}
	return null;
}

function get_bridge_for_platform( string $platform ): ?array {
	$bridges = get_bridges();
	if ( isset( $bridges[ $platform ] ) ) {
		return $bridges[ $platform ];
	}
	return null;
}

/**
 * Generate an RSS Bridge URL from the given bridge name and parameters.
 *
 * @param  string $bridge The name of the bridge to use.
 * @param  array  $params The parameters for the bridge.
 *
 * @return string The generated RSS Bridge URL.
 *
 * @example https://figuren.test/content/mu-plugins/rss-bridge-master/?action=display&bridge=WordPressBridge&url=https%3A%2F%2Fjuliaraab.de%2F&limit=3&content-selector=&format=Atom
 */
function generate_rss_bridge_url_from_params( string $bridge, array $params ) : string {
	//
	// $rss_bridge_base_url = get_site_url( null, '/content/mu-plugins/rss-bridge-master/index.php' );
	$rss_bridge_base_url = plugins_url( 'index.php', dirname( PLUGINPATH ) );

	// $query_params = http_build_query( [ 'format' => 'Atom', 'bridge' => $bridge ] + $params );
	// return 'https://..../display/?' . $query_params;

	// Build the query string parameters for the RSS Bridge API URL.
	$params = wp_parse_args(
		$params,
		[
			'action' => 'display',
			'format' => 'Atom',
			'bridge' => $bridge,
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

function get_bridge_url( array $bridge, string $url, ?string $platform = null ) : ?string {
	if ( ! isset( $bridge['bridge_url_data'] ) ) {
		return null;
	}
	// Call the bridge to get the feed URL.
	$bridge_url_data = call_user_func( $bridge['bridge_url_data'], $bridge, $url, $platform );

	// The bridged feed.
	return generate_rss_bridge_url_from_params( $bridge['bridge_name'], $bridge_url_data );
}

function get_custom_feed_url( array $bridge, string $url, ?string $platform = null ) : string {

	if ( $platform ) {
		$domain = get_custom_domain( $url );
	}
	if ( is_string( $domain ) ) {
		$url_parts      = parse_url( $url );
		$feed_url_parts = parse_url( $bridge['feed_url'] );

		// Update the host with the custom domain-name.
		$feed_url_parts['host'] = $url_parts['host'];

		// Re-glue everything together.
		$custom_feed_url = $feed_url_parts['scheme'] . '://' . $feed_url_parts['host'] . $feed_url_parts['path'];

		// Create a pseudo-bridge for the next run of get_feed_url().
		$bridge['feed_url'] = $custom_feed_url;
		$bridge['pattern']  = CustomDomainRegex;
	}

	return get_feed_url( $bridge, $url );

}

function get_feed_url( array $bridge, string $url ) : string {
	if ( ! isset( $bridge['feed_url_data'] ) ) {
		return '';
	}

	// Call the bridge to get the feed URL.
	$feed_url = call_user_func( $bridge['feed_url_data'], $bridge['pattern'], $url );

	return sprintf( $bridge['feed_url'], $feed_url );
}

function is_feed_ok( string $url ) : bool {

	$response = wp_remote_get( set_url_scheme( $url, 'https' ) );

	if ( ! is_wp_error( $response ) ) {
		$status = wp_remote_retrieve_response_code( $response );
		if ( 200 == $status ) {
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

	$bridge = false;

	// Extract the domain from the URL.
	if ( $platform ) {
		$bridge = get_bridge_for_platform( $platform );
	}

	//
	// Check if we have a bridge for the domain
	// if no platform was given or
	// even if a platform was set, but nothing was found.
	if ( ! $bridge ) {
		$bridge = get_bridge_for_domain( $url );
	}

	// Try to find a bridge Adapter.
	if ( $bridge ) {
		$bridged_url = get_bridge_url( $bridge, $url, $platform );
	}

	// WORKING, used as fallback.
	if ( $bridge && ! $bridged_url ) {
		// Get the normal feed.
		$bridged_url = get_feed_url( $bridge, $url );
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
