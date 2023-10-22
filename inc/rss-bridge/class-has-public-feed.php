<?php
/**
 * Figuren_Theater Data Rss_Bridge.
 *
 * @package figuren-theater/data/rss_bridge
 */

namespace Figuren_Theater\Data\Rss_Bridge;

trait Has_Public_Feed {

	/**
	 * Schematic URL of this platform incl. one %s variable for the 'user', 'channel' or whatever.
	 *
	 * @var string
	 */
	protected static string $feed_url_scheme;

	/**
	 * RegEx group within the $pattern, that matches %s in $feed_url_scheme.
	 *
	 * @var int
	 */
	protected static int $pattern_for_feed_match_position = 0;

	/**
	 *
	 *
	 * @return string
	 */
	protected function get_feed_url_scheme() : string {
		return $this->feed_url_scheme;
	}

	/**
	 * Extract RSS-Bridge-cosumable parameters from a given URL, based on the Adapters Pattern.
	 *
	 * @return string|null
	 */
	protected function get_feed_url_data() : string {
		preg_match( $this->pattern, $this->lookup_url, $matches );
		return isset( $matches[ $this->pattern_for_feed_match_position ] ) ? $matches[ $this->pattern_for_feed_match_position ] : '';

	}

	/**
	 *
	 *
	 * @return string
	 */
	public function get_feed_url() : string {
		return sprintf(
			$this->get_feed_url_scheme(),
			$this->get_feed_url_data()
		);
	}

}
