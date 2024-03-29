<?php
/**
 * Figuren_Theater Data Feed_Pull.
 *
 * @package figuren-theater/ft-data
 */

namespace Figuren_Theater\Data\Feed_Pull;

use Figuren_Theater;
use Figuren_Theater\Network\Features;
use Figuren_Theater\Options;
use function add_action;
use function add_filter;
use function current_user_can;
use function get_current_screen;
use function is_admin;
use function is_network_admin;
use function is_user_admin;
use function remove_meta_box;
use function wp_doing_ajax;
use function wp_doing_cron;

const FP_OPTION_NAME = 'fp_feed_pull';

const BASENAME   = 'feed-pull/feed-pull.php';
const PLUGINPATH = '/carstingaxion/' . BASENAME;
// const PLUGINPATH = '/tlovett1/' . BASENAME; // phpcs:ignore !

const FEED_POSTTYPE        = 'fp_feed';
const DESTINATION_POSTTYPE = 'post';
const ADAPTER_POSTMETA     = '_ft_bridge_adapter';

/**
 * Bootstrap module, when enabled.
 *
 * @return void
 */
function bootstrap() :void {

	add_action( 'Figuren_Theater\loaded', __NAMESPACE__ . '\\filter_options', 11 );

	add_action( 'init', __NAMESPACE__ . '\\load_plugin', 0 );
}

/**
 * Conditionally load the plugin itself and its modifications.
 *
 * @return void
 */
function load_plugin() :void {
	$config = Figuren_Theater\get_config()['modules']['data'];
	if ( ! $config['feed-pull'] ) {
		return;
	}

	// Do only load in "normal" admin view
	// Not for:
	// - public views
	// - network-admin views
	// - user-admin views.
	if ( is_network_admin() || is_user_admin() || ( ! is_admin() && ! wp_doing_cron() && ! wp_doing_ajax() ) ) {
		return;
	}

	require_once FT_VENDOR_DIR . PLUGINPATH; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingCustomConstant

	// Create new 'fp_feed' posts, when a new 'ft_link' post is created
	// which has an importable endpoint.
	Auto_Setup\bootstrap();

	// Everything related to importing normal posts from feeds.
	Import\bootstrap();

	add_action( 'admin_menu', __NAMESPACE__ . '\\remove_menu', 11 );

	add_filter( 'register_' . FEED_POSTTYPE . '_post_type_args', __NAMESPACE__ . '\\register_post_type_args' );

	add_action( 'admin_print_footer_scripts', __NAMESPACE__ . '\\custom_icons' );

	add_action( 'add_meta_boxes_' . FEED_POSTTYPE, __NAMESPACE__ . '\\modify_metaboxes' );
}

/**
 * Handle options
 *
 * @return void
 */
function filter_options() :void {
	/**
	 * Defaults:
	 *  'pull_interval'    => 3600,
	 *  'enable_feed_pull' => 1
	 */
	$_options = [
		'pull_interval'    => 3607,
		'enable_feed_pull' => 1,
	];

	/*
	 * Gets added to the 'OptionsCollection'
	 * from within itself on creation.
	 */
	new Options\Option(
		FP_OPTION_NAME,
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
	remove_submenu_page( 'options-general.php', 'feed-pull' );
}

/**
 * Modify 'fp_feed' post_type
 *
 * @todo https://github.com/figuren-theater/ft-data/issues/21 Remove hard dependency on 'deprecated__Figuren_Theater__v2' using Taxonomies\...
 *
 * @see  https://github.com/tlovett1/feed-pull/blob/45d667c1275cca0256bd03ed6fa1655cdf26f064/includes/class-fp-source-feed-cpt.php#L136
 *
 * @param   array<string, mixed> $args Arguments for registering a post type. See the register_post_type() function for accepted arguments.
 *
 * @return  array<string, mixed>
 */
function register_post_type_args( array $args ) : array {

	$cuc = current_user_can( 'manage_sites' );

	$args['public']        = false; // WHY is this 'true' by default?
	$args['supports']      = [ 'title', 'post-formats' ];

	$args['show_ui']       = $cuc;
	$args['show_in_menu']  = $cuc;

	$args['menu_icon']     = 'dashicons-rss';
	$args['menu_position'] = 100;

	$args['taxonomies']    = $args['taxonomies'] ?? [];
	$args['taxonomies'][]  = Features\UtilityFeaturesManager::TAX; // @phpstan-ignore-line

	return $args;
}

/**
 * Removes 'post slug' metabox for all users and
 * removes 'custom fields' metabox for all, but super-admin, users .
 *
 * @return void
 */
function modify_metaboxes() : void {

	$screen = get_current_screen();
	if ( \is_null( $screen ) ) {
		return;
	}

	remove_meta_box( 'slugdiv', $screen, 'normal' );

	if ( ! current_user_can( 'manage_sites' ) ) {
		remove_meta_box( 'postcustom', $screen, 'normal' );
	}
}

/**
 * Enqueue a script in the WordPress admin on post.php.
 *
 * @return void
 */
function custom_icons() : void {
	global $pagenow, $typenow;

	if ( ( 'post.php' !== $pagenow && 'post-new.php' !== $pagenow ) || FEED_POSTTYPE !== $typenow ) {
		return;
	}
	?>
	<style type="text/css">
	.misc-pub-section.misc-pub-fp-last-pulled label {
		background: 0;
		padding-left: 0;
	}
	.misc-pub-section.misc-pub-fp-last-pulled label::before {
		content: "\f303";
		position: relative;
		font: normal 20px/1 dashicons;
		speak: never;
		display: inline-block;
		margin-left: -1px;
		padding-right: 3px;
		vertical-align: top;
	}
	</style>
	<?php
}
