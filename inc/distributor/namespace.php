<?php
/**
 * Figuren_Theater Data Distributor.
 *
 * Plugin Bridge for the glorious 'Distributor'-Plugin by 10up
 *
 * @see https://10up.github.io/distributor/
 *
 * @package figuren-theater/ft-data
 */

namespace Figuren_Theater\Data\Distributor;

use FT_VENDOR_DIR;

use WP_DEBUG;
use WP_ENVIRONMENT_TYPE;

use WP_Post;

use Figuren_Theater;
use Figuren_Theater\FeaturesRepo;
use Figuren_Theater\Network\Users;
use Figuren_Theater\Options;
use function Figuren_Theater\get_config;

use function add_action;
use function add_filter;
use function current_user_can;
use function remove_menu_page;
use function remove_action;

const BASENAME   = 'distributor/distributor.php';
const PLUGINPATH = '/10up/' . BASENAME;

/**
 * Bootstrap module, when enabled.
 */
function bootstrap() {

	add_action( 'Figuren_Theater\loaded', __NAMESPACE__ . '\\filter_options', 11 );

	add_action( 'plugins_loaded', __NAMESPACE__ . '\\load_plugin', 0 );
}

function load_plugin() {

	// because this makes things visible
	// to normal 'administrator' users
	if ( ! defined( 'DISTRIBUTOR_DEBUG' ) && 'local' === WP_ENVIRONMENT_TYPE )
		define( 'DISTRIBUTOR_DEBUG', WP_DEBUG );

	// the plugin checks for option 'active_sitewide_plugins'
	// so we need to filter 'active_sitewide_plugins'
	add_filter( 'site_option_active_sitewide_plugins', __NAMESPACE__ . '\\filter_site_option', 0 );

	require_once FT_VENDOR_DIR . PLUGINPATH; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingCustomConstant

	// Filters the arguments for registering a post type.
	add_filter( 'register_post_type_args', __NAMESPACE__ . '\\register_post_type_args', 20, 2 );

	// Remove plugins menu
	add_action( 'network_admin_menu', __NAMESPACE__ . '\\remove_menu', 11 );
	add_action( 'admin_menu', __NAMESPACE__ . '\\remove_menu', 11 );

	add_action( 'admin_init', __NAMESPACE__ . '\\admin_init', 0 );
}

function admin_init() {

	add_action( 'admin_init', __NAMESPACE__ . '\\remove_columns_from_lists', 9 );

	// puc_check_now-$slug
	// 'puc_check_now-distributor' => '__return_false',
	//

	// Allow bypassing of all media processing.
	add_filter( 'dt_push_post_media', __NAMESPACE__ . '\\dt_push_post_media' );

	//
	add_filter( 'dt_push_post_args', __NAMESPACE__ . '\\dt_push_post_args', 9, 4 );
	add_filter( 'dt_pull_post_args', __NAMESPACE__ . '\\dt_pull_post_args', 9, 4 );

	// Filter Distributor capabilities allowed to syndicate content.
	add_filter( 'dt_syndicatable_capabilities', __NAMESPACE__ . '\\dt_syndicatable_capabilities' );

	//
	// \add_filter( 'pre_site_option_external_updates-distributor', [ $this, 'pre_disable_updatecheck' ] );
}



function filter_site_option( $active_sitewide_plugins ) {

	// prevents the default admin-notice for missing plugin files,
	// which gets triggered by the FT_VENDOR_DIR path construct
	global $pagenow;
	if ( 'plugins.php' === $pagenow )
		return $active_sitewide_plugins;

	$active_sitewide_plugins[ BASENAME ] = BASENAME;
	return $active_sitewide_plugins;
}

function filter_options() : void {

	$_option_name = 'dt_settings';
	$_options     = [
		'override_author_byline' => false,
		'media_handling'         => 'featured',
		'email'                  => getenv( 'FT_DATA_DISTRIBUTOR_EMAIL' ),
		'license_key'            => getenv( 'FT_DATA_DISTRIBUTOR_KEY' ),
		'valid_license'          => false, // Distributor: "Enable updates if we have a valid license" --> f.t ;)
	];

	// gets added to the 'OptionsCollection'
	// from within itself on creation
	new Options\Option(
		$_option_name,
		$_options,
		BASENAME,
		'site_option'
	);

	// gets added to the 'OptionsCollection'
	// from within itself on creation
	new Options\Option(
		$_option_name,
		$_options,
		BASENAME
	);


}



function remove_menu() : void {
	remove_menu_page( 'distributor' );
}

function remove_columns_from_lists() : void {
	// unclutter the UI for "normal" users
	// if ( 'this-site-is-not-a-network-hub' && ! \is_main_site( null, 1 ) )
	if ( ! Figuren_Theater\FT::site()->has_feature( [ FeaturesRepo\Feature__core__contenthub::SLUG ] ) )
		remove_action( 'admin_init', 'Distributor\\SyndicatedPostUI\\setup_columns' );
}


/**
 * [pre_disable_updatecheck description]
 *
 * Because this is a pre_option_ filter, do not return FALSE.
 *
 * @subpackage [subpackage]
 * @version    2022-10-21
 * @author Carsten Bach
 *
 * @return     [type]       [description]
function pre_disable_updatecheck() {
	// return false;
	// return 'null';
	return ! ( 'local' === \WP_ENVIRONMENT_TYPE );
}
 */


/**
 * Filters the arguments for registering a post type.
 *
 * @see https://developer.wordpress.org/reference/hooks/register_post_type_args/
 *
 * @since 4.4.0
 *
 * @param array  $args      Array of arguments for registering a post type.
 *                          See the register_post_type() function for accepted arguments.
 * @param string $post_type Post type key.
 */
function register_post_type_args( array $args, String $post_type ) : array {
	if ( in_array( $post_type, ['dt_ext_connection','dt_subscription']) ) {
		$args['can_export'] = current_user_can( 'manage_sites' );
		$args['show_ui'] = false; // disable this anoying 'dt_subscription'-menu, as it is only needed for ext. connections
	}

	return $args;
}


/**
 * Filter Distributor capabilities allowed to syndicate content.
 *
 * At the moment this is done only automatically or by hand by an site-admin.
 * In the future we can go on and allow personal distribution.
 * This is the place to start with.
 *
 * @see https://10up.github.io/distributor/dt_syndicatable_capabilities.html
 *
 * @param  String $capabilities default: edit_posts The capability allowed to syndicate content.
 * @return [type]               [description]
 */
function dt_syndicatable_capabilities( String $capabilities ) : string
{
	return 'manage_sites';
}

/**
 * Allow bypassing of all media processing.
 *
 * @see https://10up.github.io/distributor/dt_push_post_media.html
 *
 * @hook dt_push_post_media
 *
 * @param {bool}       true           If Distributor should push the post media.
 * @param {int}        $new_post_id   The newly created post ID.
 * @param {array}      $media         List of media items attached to the post, formatted by {@link \Distributor\Utils\prepare_media()}.
 * @param {int}        $post_id       The original post ID.
 * @param {array}      $args          The arguments passed into wp_insert_post.
 * @param {Connection} $this          The distributor connection being pushed to.
 *
 * @return {bool} If Distributor should push the post media.
 */
function dt_push_post_media($value)
{
	return false;
}

function dt_push_post_args($new_post_args, $post, $connection_args, $connection) : array {
	return push_pull_default_args( $new_post_args, $post );
}

function dt_pull_post_args($new_post_args, $remote_post_id, $remote_post, $connection) : array {
	return push_pull_default_args( $new_post_args, $remote_post );
}

function push_pull_default_args( array $new_post_args, WP_Post $original_post ) : array {
	// set author to machine user
	$new_post_args['post_author']       = Users\ft_bot::id();

	// by default 'Distributor' sets the current date as new published_date
	$new_post_args['post_date']         = $original_post->post_date;
	// ..and all related dates ...
	$new_post_args['post_date_gmt']     = $original_post->post_date_gmt;
	$new_post_args['post_modified']     = $original_post->post_modified;
	$new_post_args['post_modified_gmt'] = $original_post->post_modified_gmt;

	return $new_post_args;
}
