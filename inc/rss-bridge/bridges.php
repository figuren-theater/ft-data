<?php
declare(strict_types=1);
/**
 * Figuren_Theater Data Rss_Bridge.
 *
 * @package figuren-theater/ft-data
 */

namespace Figuren_Theater\Data\Rss_Bridge;

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

function get_bridges() : array {
	// 1&1
	//

	// !!
	// '.jimdo.com'     => '%s/rss/blog/',
	// '.jimdofree.com' => '%s/rss/blog/',

	//
	// 'vimeo.com'      => '%s/videos/rss',

	//
	// 'wix.com'        => '%s/blog-feed.xml',  https://wix.com/{site}/blog-feed.xml'

	// Telegram

	// Mastodon

	// NO WAY
	// - other than a sarcastic blog post -
	//
	// facebook.com
	// weebly.com

	return [

		'twitter' => [
			'pattern' => '#^(https?://)?(www\.)?twitter\.com/([a-zA-Z0-9_]+)(/.*)?(\\?.*)?(\\#.*?)?$#i',
			'bridge_name' => 'Twitter',
			'bridge_url_data' => function( $bridge, $url ) {
				preg_match( $bridge['pattern'], $url, $matches );
				return [
					'u'     => $matches[3],
				];
			},
			// "feed_url" => "https://twitter.com/%s.rss",
			// "feed_url_data" => function($pattern, $url) {
			// preg_match($pattern, $url, $matches);
			// return $matches[3];
			// }
		],

		'tumblr' => [
			'pattern' => '#^(https?://)?([a-z0-9-]+)\.tumblr\.com(/.*)?$#i',
			'bridge_name' => 'FeedMerger',
			'bridge_url_data' => function( $bridge, $url, $platform = null ) {
				if ( $platform ) {
					$url = get_custom_feed_url( $bridge, $url, $platform );
				} else {
					$url = get_feed_url( $bridge, $url );
				}
				return [
					'url' => urlencode( $url ),
				];
			},
			'feed_url' => 'https://%s.tumblr.com/rss',
			'feed_url_data' => function( $pattern, $url ) {
				preg_match( $pattern, $url, $matches );
				return $matches[2];
			},
		],
		'medium' => [
			'pattern' => '#^(https?://)?([a-z0-9-]+\.)?medium\.com/@([a-zA-Z0-9_]+)(/.*)?$#i',
			'bridge_name' => 'FeedMerger',
			'bridge_url_data' => function( $bridge, $url, $platform = null ) {
				$url = get_feed_url( $bridge, $url );
				return [
					'url'    => urlencode( $url ),
				];
			},
			'feed_url' => 'https://medium.com/feed/@%s',
			'feed_url_data' => function( $pattern, $url ) {
				preg_match( $pattern, $url, $matches );
				return $matches[3];
			},
		],

		'webflow' => [
			'pattern' => '#^(https?://)?([a-zA-Z0-9_-]+)\.webflow\.com(/.*)?$#i',
			'bridge_name' => 'FeedMerger',
			'bridge_url_data' => function( $bridge, $url, $platform = null ) {
				if ( $platform ) {
					$url = get_custom_feed_url( $bridge, $url, $platform );
				} else {
					$url = get_feed_url( $bridge, $url );
				}
				return [
					'url' => urlencode( $url ),
				];
			},
			'feed_url' => 'https://%s.webflow.com/blog/rss.xml',
			'feed_url_data' => function( $pattern, $url ) {
				preg_match( $pattern, $url, $matches );
				return $matches[2];
			},
		],

		'blogspot' => [
			'pattern' => '#^(https?://)?([a-zA-Z0-9_-]+)\.blogspot\.com(/.*)?$#i',
			'bridge_name' => 'FeedMerger',
			'bridge_url_data' => function( $bridge, $url, $platform = null ) {
				if ( $platform ) {
					$url = get_custom_feed_url( $bridge, $url, $platform );
				} else {
					$url = get_feed_url( $bridge, $url );
				}
				return [
					'url' => urlencode( $url ),
				];
			},
			'feed_url' => 'https://%s.blogspot.com/feeds/posts/default',
			'feed_url_data' => function( $pattern, $url ) {
				preg_match( $pattern, $url, $matches );
				return $matches[2];
			},
		],

		'jimdo' => [
			'pattern' => '#^(https?://)?([a-zA-Z0-9_-]+\.)*jimdo\.com(/.*)?$#i',
			'feed_url' => 'https://%sjimdo.com/rss.xml',
			'feed_url_data' => function( $pattern, $url ) {
				preg_match( $pattern, $url, $matches );
				return $matches[2];
			},
		],

		'wordpress' => [
			'pattern' => '#^(https?://)?([a-zA-Z0-9_-]+)\.wordpress\.com(/.*)?$#i',
			'bridge_name' => 'WordPress',
			'bridge_url_data' => function( $bridge, $url, $platform = null ) {
				return [
					'url' => urlencode( $url ),
				];
			},
			'feed_url' => 'https://%s.wordpress.com/feed/',
			'feed_url_data' => function( $pattern, $url ) {
				preg_match( $pattern, $url, $matches );
				return $matches[2];
			},
		],

		'youtube' => [
			'pattern' => '#^(https?://)?(www\.)?youtube\.com/(channel/|@)([a-zA-Z0-9_-]+)$#i',
			// "bridge_url" => "https://www.youtube.com/feeds/videos.xml?%s",
			'bridge_name' => 'YouTube',
			'bridge_url_data' => function( $bridge, $url ) {
				preg_match( $bridge['pattern'], $url, $matches );
				$id = ( $matches[3] == 'channel' ) ? 'channel_id' : 'user';
				return [
					$id      => $matches[4],
					'min'    => '', // needed default
					'max'    => '', // needed default
				];
			},
			// "feed_url" => "https://www.youtube.com/feeds/videos.xml?%s",
			// "feed_url_data" => function($pattern, $url) {
			// preg_match($pattern, $url, $matches);
			// $id = ($matches[3] == 'channel') ? 'channel_id' : 'user';
			// return "$id=$matches[4]";
			// }
		],
		/*
		 WORKING BACKUP
		"youtube" => array(
			"pattern" => '#^(https?://)?(www\.)?youtube\.com/(channel/|@)([a-zA-Z0-9_-]+)$#i',
			"feed_url" => "https://www.youtube.com/feeds/videos.xml?%s",
			"feed_url_data" => function($pattern, $url) {
				preg_match($pattern, $url, $matches);
				$id = ($matches[3] == 'channel') ? 'channel_id' : 'user';
				return "$id=$matches[4]";
			}
		),

		"youtube" => array(
			"pattern" => '#^(https?://)?(www\.)?youtube\.com/channel/([a-zA-Z0-9_-]+)$#i',
			"feed_url" => "https://www.youtube.com/feeds/videos.xml?channel_id=%s",
			"feed_url_data" => function($pattern, $url) {
				preg_match($pattern, $url, $matches);
				return $matches[3];
			}
		),*/

		'vimeo' => [
			'pattern' => '#^(https?://)?(www\.)?vimeo\.com/([a-zA-Z0-9_-]+)$#i',
			'feed_url' => 'https://vimeo.com/%s/videos/rss',
			'feed_url_data' => function( $pattern, $url ) {
				preg_match( $pattern, $url, $matches );
				return $matches[3];
			},
		],

		'flickr' => [
			'pattern' => '#^(https?://)?(www\.)?flickr\.com/photos/([a-zA-Z0-9@]+)(/albums/([a-zA-Z0-9_-]+))?/?#i',
			'bridge_name' => 'Flickr',
			'bridge_url_data' => function( $bridge, $url ) {
				preg_match( $bridge['pattern'], $url, $matches );
				return [
					'id'     => $matches[3],
				];
			},
			// "feed_url" => "https://www.flickr.com/photos/%s/rss",
			// "feed_url_data" => function($pattern, $url) {
			// preg_match($pattern, $url, $matches);
			// return $matches[3];
			// }
		],

		'tiktok' => [
			'pattern' => '#^(https?://)?(www\.)?tiktok\.com/@([a-zA-Z0-9_]+)(/.*)?(\\?.*)?(\\#.*?)?$#i',
			'bridge_name' => 'FeedMerger',
			'bridge_url_data' => function( $bridge, $url ) {
				$url = get_feed_url( $bridge, $url );
				return [
					'url'    => urlencode( $url ),
				];
			},
			'feed_url' => 'https://www.tiktok.com/@%s/rss',
			'feed_url_data' => function( $pattern, $url ) {
				preg_match( $pattern, $url, $matches );
				return $matches[3];
			},
		],

		// "facebook" => array(
		// "pattern" => '#^(https?://)?(www\.)?facebook\.com/([a-zA-Z0-9.-]+)/?$#i',
		// "feed_url" => "https://www.facebook.com/%s/posts/rss",
		// "feed_url_data" => function($pattern, $url) {
		// preg_match($pattern, $url, $matches);
		// return $matches[3];
		// }
		// ),

	];
}
