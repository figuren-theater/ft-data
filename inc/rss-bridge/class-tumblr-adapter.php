<?php
/**
 * Figuren_Theater Data Rss_Bridge.
 *
 * @package figuren-theater/ft-data
 */

namespace Figuren_Theater\Data\Rss_Bridge;

/**
 * Adapter to connect Twitter via RSS-Bridge with WordPress
 */
class Tumblr_Adapter extends Adapter {
	use Has_Public_Feed, Maybe_Custom_Domain, Has_Rss_Bridge;

	/**
	 * RegEx Pattern to match a Full URL of an importable website.
	 *
	 * @var string
	 */
	protected static string $pattern = '#^(https?://)?([a-z0-9-]+)\.tumblr\.com(/.*)?$#i';

	/**
	 * Schematic URL of this platform incl. one %s variable for the 'user', 'channel' or whatever.
	 *
	 * @var string
	 */
	protected static string $feed_url_scheme = 'https://%s.tumblr.com/rss';

	/**
	 * RegEx group within the $pattern, that matches %s in $feed_url_scheme.
	 *
	 * @var int
	 */
	protected static int $pattern_for_feed_match_position = 2;

	function get_bridge_url_data() : array {
		if ( $this->platform_suggestion ) {
			$url = $this->get_custom_feed_url();
		} else {
			$url = $this->get_feed_url();
		}
		return [
			'url' => \rawurlencode( $url ),
		];
	}
}
