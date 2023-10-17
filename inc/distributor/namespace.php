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

use Distributor\Connection;

use Figuren_Theater;
use Figuren_Theater\FeaturesRepo;
use Figuren_Theater\Network\Users;

use Figuren_Theater\Options;

use FT_VENDOR_DIR;
use function add_action;
use function add_filter;
use function current_user_can;
use function remove_action;
use function remove_menu_page;
use function wp_get_environment_type;
use WP_DEBUG;
use WP_Post;

const BASENAME   = 'distributor/distributor.php';
const PLUGINPATH = '/10up/' . BASENAME;

/**
 * Bootstrap module, when enabled.
 *
 * @return void
 */
function bootstrap() :void {

	add_action( 'Figuren_Theater\loaded', __NAMESPACE__ . '\\filter_options', 11 );

	add_action( 'plugins_loaded', __NAMESPACE__ . '\\load_plugin', 0 );
}

/**
 * Conditionally load the plugin itself and its modifications.
 *
 * @return void
 */
function load_plugin() :void {

	// Because this makes things visible
	// to normal 'administrator' users.
	if ( ! defined( 'DISTRIBUTOR_DEBUG' ) && 'local' === wp_get_environment_type() ) {
		define( 'DISTRIBUTOR_DEBUG', WP_DEBUG );
	}

	// The plugin checks for option 'active_sitewide_plugins'
	// so we need to filter 'active_sitewide_plugins'.
	add_filter( 'site_option_active_sitewide_plugins', __NAMESPACE__ . '\\filter_site_option', 0 );

	require_once FT_VENDOR_DIR . PLUGINPATH; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingCustomConstant

	// Filters the arguments for registering a post type.
	add_filter( 'register_post_type_args', __NAMESPACE__ . '\\register_post_type_args', 20, 2 );

	// Remove plugins menu.
	add_action( 'network_admin_menu', __NAMESPACE__ . '\\remove_menu', 11 );
	add_action( 'admin_menu', __NAMESPACE__ . '\\remove_menu', 11 );

	add_action( 'admin_init', __NAMESPACE__ . '\\admin_init', 0 );
}

/**
 * Fires as an admin screen or script is being initialized.
 *
 * @return void
 */
function admin_init() : void {

	add_action( 'admin_init', __NAMESPACE__ . '\\remove_columns_from_lists', 9 );

	// Allow bypassing of all media processing.
	add_filter( 'dt_push_post_media', __NAMESPACE__ . '\\dt_push_post_media', 10, 6 );

	add_filter( 'dt_push_post_args', __NAMESPACE__ . '\\dt_push_post_args', 9, 4 );
	add_filter( 'dt_pull_post_args', __NAMESPACE__ . '\\dt_pull_post_args', 9, 4 );

	// Filter Distributor capabilities allowed to syndicate content.
	add_filter( 'dt_syndicatable_capabilities', __NAMESPACE__ . '\\dt_syndicatable_capabilities' );
}

/**
 * Add 'Distributor' to the site-wide active plugins on-the-fly.
 *
 * Prevents the default admin-notice for missing plugin files,
 * which gets triggered by the FT_VENDOR_DIR path construct.
 *
 * @param  array<string, string> $active_sitewide_plugins    WordPress' default 'active_sitewide_plugins' site-option.
 *
 * @return array<string, string>
 */
function filter_site_option( array $active_sitewide_plugins ) : array {

	global $pagenow;
	if ( 'plugins.php' === $pagenow ) {
		return $active_sitewide_plugins;
	}

	$active_sitewide_plugins[ BASENAME ] = BASENAME;
	return $active_sitewide_plugins;
}

/**
 * Handle options
 *
 * @return void
 */
function filter_options() :void {

	$_option_name = 'dt_settings';

	$_options = [
		'override_author_byline' => false,
		'media_handling'         => 'featured',
		'email'                  => getenv( 'FT_DATA_DISTRIBUTOR_EMAIL' ),
		'license_key'            => getenv( 'FT_DATA_DISTRIBUTOR_KEY' ),
		'valid_license'          => false, // Distributor would like to "Enable updates if we have a valid license" --> But no, f.t ;) !
	];

	/*
	 * Gets added to the 'OptionsCollection'
	 * from within itself on creation.
	 */
	new Options\Option(
		$_option_name,
		$_options,
		BASENAME,
		'site_option'
	);

	/*
	 * Gets added to the 'OptionsCollection'
	 * from within itself on creation.
	 */
	new Options\Option(
		$_option_name,
		$_options,
		BASENAME
	);

}

/**
 * Remove the plugins admin-menu.
 *
 * @return void
 */
function remove_menu() : void {
	remove_menu_page( 'distributor' );
}

/**
 * Unclutter the UI for "normal" users.
 *
 * @todo https://github.com/figuren-theater/ft-data/issues/21 Refactor hard dependency on 'deprecated_figuren_theater_v2'
 *
 * @return void
 */
function remove_columns_from_lists() : void {

	// @phpstan-ignore-next-line
	if ( ! Figuren_Theater\FT::site()->has_feature( [ FeaturesRepo\Feature__core__contenthub::SLUG ] ) ) {
		remove_action( 'admin_init', 'Distributor\\SyndicatedPostUI\\setup_columns' );
	}
}

/**
 * Filters the arguments for registering a post type.
 *
 * @see https://developer.wordpress.org/reference/hooks/register_post_type_args/
 *
 * @since WP 4.4.0
 *
 * @param array<string, mixed>  $args      Array of arguments for registering a post type.
 *                                         See the register_post_type() function for accepted arguments.
 * @param string                $post_type Post type key.
 *
 * @return array<string, mixed>
 */
function register_post_type_args( array $args, string $post_type ) : array {
	if ( ! in_array( $post_type, [ 'dt_ext_connection', 'dt_subscription' ], true ) ) {
		return $args;
	}

	// Allow exports only to site-admins.
	$args['can_export'] = current_user_can( 'manage_sites' );
	// Disable this anoying 'dt_subscription'-menu, as it is only needed for ext. connections.
	$args['show_ui'] = false;

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
 * @param  string $capabilities default: edit_posts The capability allowed to syndicate content.
 *
 * @return string               [description]
 */
function dt_syndicatable_capabilities( string $capabilities ) : string {
	return 'manage_sites';
}

/**
 * Allow bypassing of all media processing.
 *
 * @see https://10up.github.io/distributor/dt_push_post_media.html
 *
 * @hook dt_push_post_media
 *
 * @param  bool                                               $value           If Distributor should push the post media.
 * @param  int                                                $new_post_id     The newly created post ID.
 * @param  array<int, array<string, mixed>>                   $media           List of media items attached to the post, formatted by {@link \Distributor\Utils\prepare_media()}.
 * @param  int                                                $post_id         The original post ID.
 * @param  array<string, array<int|string, array<int, int>>>  $args            The post data to be inserted. List of 'wp_insert_post()' combatible data.
 * @param  Connection                                         $connection      The distributor connection being pushed to.
 *
 * @return bool                                                                If Distributor should push the post media.
 */
function dt_push_post_media( bool $value, int $new_post_id, array $media, int $post_id, array $args, Connection $connection ) : bool {
	return false;
}

/**
 * Filter the arguments sent to the remote server during a push.
 *
 * @see    https://10up.github.io/distributor/dt_push_post_args.html
 *
 * @param  array<string, array<int|string, array<int, int>>>  $new_post_args   Weirdly, it says: 'The request body to send.', but usually this is: 'The post data to be inserted. List of 'wp_insert_post()' combatible data.'
 * @param  WP_Post                                            $post            The WP_Post that is being pushed.
 * @param  mixed                                              $connection_args Connection args to push.
 * @param  Connection                                         $connection      The distributor connection being pushed to.
 *
 * @return array<string, array<int|string, array<int, int>>>                   The post data to be inserted. List of 'wp_insert_post()' combatible data.
 */
function dt_push_post_args( array $new_post_args, WP_Post $post, mixed $connection_args, Connection $connection ) : array {
	return push_pull_default_args( $new_post_args, $post );
}

/**
 * Filter the arguments passed into wp_insert_post during a pull.
 *
 * @see    https://10up.github.io/distributor/dt_pull_post_args.html
 *
 * @param  array<string, array<int|string, array<int, int>>>  $new_post_args   The post data to be inserted. List of 'wp_insert_post()' combatible data.
 * @param  int                                                $remote_post_id  The remote post ID.
 * @param  WP_Post                                            $remote_post     The request that got the post.
 * @param  Connection                                         $connection      The Distributor connection pulling the post.
 *
 * @return array<string, array<int|string, array<int, int>>>                   The post data to be inserted. List of 'wp_insert_post()' combatible data.
 */
function dt_pull_post_args( array $new_post_args, int $remote_post_id, WP_Post $remote_post, Connection $connection ) : array {
	return push_pull_default_args( $new_post_args, $remote_post );
}

/**
 * Streamline changes to new posts for both directions, pull & push.
 *
 * @todo #20 Refactor hard dependency on 'deprecated_figuren_theater_v2'
 *
 * @param  array<string, array<int|string, array<int, int>>>  $new_post_args   The post data to be inserted. List of 'wp_insert_post()' combatible data.
 * @param  WP_Post                                            $original_post   The original WP_post.
 *
 * @return array<string, array<int|string, array<int, int>>>                   The post data to be inserted. List of 'wp_insert_post()' combatible data.
 */
function push_pull_default_args( array $new_post_args, WP_Post $original_post ) : array {
	// Set author to machine user.
	$new_post_args['post_author'] = Users\ft_bot::id(); // @phpstan-ignore-line

	// By default 'Distributor' sets the current date as new published_date.
	$new_post_args['post_date'] = $original_post->post_date;
	// ..and all related dates ...
	$new_post_args['post_date_gmt']     = $original_post->post_date_gmt;
	$new_post_args['post_modified']     = $original_post->post_modified;
	$new_post_args['post_modified_gmt'] = $original_post->post_modified_gmt;

	return $new_post_args;
}
