<?php
/**
 * Figuren_Theater Data Feed_Pull.
 *
 * @package figuren-theater/data/feed_pull
 */

namespace Figuren_Theater\Data\Feed_Pull;

use Figuren_Theater\Network\Users;

use function add_action;
use function add_filter;

function bootstrap_auto_setup() {

	// add_action( 'init', __NAMESPACE__ . '\\init', 5 );
}

function init() {

	// for debugging only
	// delete_option( 'fp_deleted_syndicated' );

}



// ####
// 
/**
 * Saves a "feed-pull" source programmatically.
 *
 * @param string $feed_url The URL of the feed to import.
 * @param array $options An array of options to use when importing the feed.
 *
 * @return int|WP_Error The ID of the created post, or a WP_Error object if something goes wrong.

function save_feed_pull_source( string $feed_url, array $options = array() ) {
    // Define default options and merge them with user-supplied options.
    $defaults = array(
        'post_type'           => 'post',
        'status'              => 'publish',
        'author'              => \get_current_user_id(),
        'categories'          => array( 'uncategorized' ),
        'tags'                => array(),
        'excerpt_length'      => 55,
        'featured_image'      => true,
        'update_interval'     => 3600,
        'import_limit'        => 0,
        'allow_comments'      => false,
        'comment_status'      => 'closed',
        'ping_status'         => 'closed',
        'meta_input'          => array(),
    );
    $options = \wp_parse_args( $options, $defaults );

    // Create the post data array.
    $post_data = array(
        'post_type'   => 'fp_feed',
        'post_title'  => 'Feed Source: ' . $feed_url,
        'post_status' => 'publish',
        // Combine meta keys and values to create the meta input array.
        // This is more efficient because 
        // it doesn't require looping over the keys and values separately.
        'meta_input'  => array_combine( $meta_keys, $meta_values ), 
    );

    // Insert the post and return the post ID or an error message.
    $post_id = \wp_insert_post( $post_data );
    if ( \is_wp_error( $post_id ) ) {
        return $post_id->get_error_message();
    } else {
        return $post_id;
    }
}
 */
