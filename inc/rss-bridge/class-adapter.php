<?php
/**
 * Figuren_Theater Data Rss_Bridge.
 *
 * @package figuren-theater/data/rss_bridge
 */

namespace Figuren_Theater\Data\Rss_Bridge;

/**
 * Adapter to connect RSS-Bridge with WordPress
 *
 * In particular to combine forces between
 * 1. RSS-Bridge library
 * 2. feed-pull WordPress plugin
 * 3. ft-network-source-links WordPress plugin
 */
class Adapter {
	
	/**
	 * RSS-Bridge Definition class
	 * @var BridgeAbstract
	 */
	protected $bridge;

	/**
	 * Full URL of a website, which content to import
	 * @var string
	 */
	protected $url;

	/**
	 * [__construct description]
	 *
	 * @param   BridgeAbstract $bridge [description]
	 * @param   string         $url    [description]
	 */
	function __construct( BridgeAbstract $bridge, string $url ) {
		$this->bridge = $bridge;
		$this->url    = $url;
	}

	/**
	 * Get parameters to build a query URL for the RSS-Bridge API.
	 *
	 * Returns all individual parameters needed for that particular bridge.
	 * This could include 'url' or 'u', maybe 'limit', and many more, 
	 * but NOT 'format' and 'action' as theese are assigned globally.
	 *
	 * @return array List of escaped URL parameters to query the RSS-Bridge API.
	 */
	public function get_API_url_params() : array {
		return [];
	}

	public function get_formality_block() {}
	public function get_ft_link_edit_ui() {}
	

	public function get_insert_imports_args() {
		
	}
}
