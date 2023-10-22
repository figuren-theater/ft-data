<?php
/**
 * Figuren_Theater Data Rss_Bridge.
 *
 * @package figuren-theater/data/rss_bridge
 */

namespace Figuren_Theater\Data\Rss_Bridge;

const CUSTOM_DOMAIN_REGEX = '/^[a-zA-Z0-9]+([\-\.]{1}[a-zA-Z0-9]+)*\.[a-zA-Z]{2,}$/i';

trait Maybe_Custom_Domain {

	/**
	 * Extracts the custom domain from a given URL.
	 *
	 * @return string|null The extracted custom domain, or null if not found.
	 */
	function get_custom_domain(): ?string {
		// Get the host name from the URL.
		$parts = parse_url( $this->lookup_url );
		if ( ! isset( $parts['host'] ) ) {
			return null;// Invalid URL, no host found.
		}
		$host = $parts['host'];

		// Check if the custom domain is valid.
		preg_match( CUSTOM_DOMAIN_REGEX, $host, $matches );
		return isset( $matches[0] ) ? $matches[0] : null;
	}

	/**
	 *
	 *
	 * @param  array       $bridge
	 * @param  string      $url
	 * @param  string|null $platform
	 *
	 * @return string
	 */
	function get_custom_feed_url() : string {

		$adapter = $this;

		if ( $this->platform_suggestion ) {
			$domain = get_custom_domain( $this->lookup_url );
		}
		if ( is_string( $domain ) ) {
			$url_parts      = parse_url( $this->lookup_url );
			$feed_url_parts = parse_url( $this->get_feed_url_scheme() );

			// Update the host with the custom domain-name.
			$feed_url_parts['host'] = $url_parts['host'];

			// Re-glue everything together.
			$custom_feed_url = $feed_url_parts['scheme'] . '://' . $feed_url_parts['host'] . $feed_url_parts['path'];

			// Create a pseudo-bridge for the next run of get_feed_url().
#??!+*##			$this->feed_url = $custom_feed_url;
#??!+*##			$this->pattern  = CustomDomainRegex;
		}

#??!+*##		return $this->get_feed_url( $adapter, $this->lookup_url );

	}

}
