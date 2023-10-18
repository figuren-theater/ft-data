<?php
/**
 * Figuren_Theater Data Rss_Bridge.
 *
 * @package figuren-theater/data/rss_bridge
 */

namespace Figuren_Theater\Data\Rss_Bridge;

use BridgeAbstract;

/**
 * Adapter to connect RSS-Bridge with WordPress
 *
 * In particular to combine forces between
 * 1. RSS-Bridge library
 * 2. feed-pull WordPress plugin
 * 3. ft-network-source-links WordPress plugin
 */
trait Has_Rss_Bridge {

	/**
	 * RSS-Bridge Definition class
	 *
	 * @var BridgeAbstract
	 */
	protected BridgeAbstract $bridge;

	public function get_bridge_name() : string {
		return $this->bridge::NAME;
	}

	protected abstract function get_bridge_url_data() : array;


	/**
	 * Generate an RSS Bridge URL from the given bridge name and parameters.
	 *
	 * @param  array  $params The parameters for the bridge.
	 *
	 * @return string         The generated RSS Bridge URL.
	 *
	 * @example https://figuren.test/content/mu-plugins/rss-bridge-master/?action=display&bridge=WordPressBridge&url=https%3A%2F%2Fjuliaraab.de%2F&limit=3&content-selector=&format=Atom
	 */
	protected function generate_rss_bridge_url_from_params( array $params ) : string {
		// Build the query string parameters for the RSS Bridge API URL.
		$params = wp_parse_args(
			$params,
			[
				'action' => 'display',
				'format' => 'Atom',
				'bridge' => $this->get_bridge_name(),
			]
		);

		//
		// $rss_bridge_base_url = get_site_url( null, '/content/mu-plugins/rss-bridge-master/index.php' );
		$rss_bridge_base_url = plugins_url( 'index.php', dirname( PLUGINPATH ) );

		// Combine the query string parameters with the base URL.
		return esc_url_raw(
			add_query_arg(
				$params,
				$rss_bridge_base_url
			)
		);
	}

	/**
	 *
	 *
	 *
	 * @return string
	 */
	function get_bridge_url() : string {
		// The bridged feed.
		return $this->generate_rss_bridge_url_from_params(
			// Call the bridge to get the feed URL.
			$this->get_bridge_url_data()
		);
	}

}
