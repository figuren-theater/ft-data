<?php
/**
 * Figuren_Theater Data Feed_Pull.
 *
 * Whenever a new link post is created with the "import" term in the "utility" taxonomy, 
 * a new feed post should be automatically created with the link post as the parent.
 * This should also happen if the "import" term is added to an existing link post. 
 * 
 * If the "import" term is removed from a link post, the corresponding feed child-post should be deleted. 
 * If the link post is updated but still has the "import" term assigned, 
 * the feed child-post should not be updated.
 * 
 * @package figuren-theater/data/feed_pull
 */



namespace Figuren_Theater\Data\Feed_Pull;

use Figuren_Theater\Network\Features;
use Figuren_Theater\Network\Post_Types;

// use FP_DELETED_OPTION_NAME; // 'fp_deleted_syndicated'

use function add_action;
use function add_filter;
use function get_post;
use function get_posts;
use function get_term_by;
use function has_term;
use function is_wp_error;
use function wp_delete_post;
use function wp_insert_post;

const LINK_PT      = Post_Types\Post_Type__ft_link::NAME;
const UTILITY_TAX  = Features\UtilityFeaturesManager::TAX;
const UTILITY_TERM = 'feedpull-import';

function bootstrap_auto_setup() {

	add_action( 'admin_init', __NAMESPACE__ . '\\admin_init', 5 );

	add_action(  
		// 'Figuren_Theater\\Network\\Post_Types\\Post_Type__ft_link\\found_importable_endpoint',
		'Figuren_Theater\\Data\\Rss_Bridge\\found_importable_endpoint',
		__NAMESPACE__ . '\\save_importable_endpoint' );

}

function admin_init() {

	// for debugging only
	// delete_option( FP_DELETED_OPTION_NAME );

	// Hook into save_post to create/update feed post
	// add_action( 'save_post_'.LINK_PT, __NAMESPACE__ . '\\create_feed_post', 10, 2 );

	// Hook into wp_set_object_terms to add or delete feed post
	add_filter( 'wp_set_object_terms', __NAMESPACE__ . '\\add_or_delete_feed_post', 10, 5 );

	// Hook into wp_trash_post to delete feed post
	// add_action( 'wp_trash_post', __NAMESPACE__ . '\\delete_feed_post_on_trash' );
	add_action( 'before_delete_post', __NAMESPACE__ . '\\delete_feed_post_on_trash' );
}

function save_importable_endpoint( string $importable_endpoint ) : void {

	// \do_action( 'qm/info', $importable_endpoint );
	// error_log(var_export([__FILE__,'save_importable_endpoint', $importable_endpoint],true));
}


/**
 * Create or update a feed post 
 * when a link post with the "import" term 
 * is saved or updated.
 *
 * @param int     $post_id The ID of the post being saved or updated.
 * @param WP_Post $post    The post object being saved or updated.
 */
function create_feed_post( int $post_id, WP_Post $post ) : void {
	#//
	#$import_term_id = get_import_term_id();
    #
    #// Check if the "import" term is assigned to the link post.
    #if (!has_term( $import_term_id, UTILITY_TAX, $post_id)) {
    #    return; // Early return if the "import" term is not assigned to the link post.
    #}
    
    // Bail if post type is not a Link
    if ( $post->post_type !== LINK_PT ) {
        return;
    }

    // get bridged URL
    $fp_feed_url = esc_url(
    	RssBridge\get_bridged_url( $post->post_content ),
    	'https',
    	'db'
    );

    // Bail, if not importable
    if ( ! $fp_feed_url ) {
    	return;
    }

    // Create the feed post with the link post as parent.
    $feed_post_id = wp_insert_post(array(
        'post_author' => $post->post_author,
        'post_type'   => FEED_POSTTYPE,
        'post_title'  => 'Feed: ' . $post->post_content ),
        'post_parent' => $post_id,
        'post_status' => 'publish',
		
		'menu_order'     => 0,
		'comment_status' => 'closed',
		'ping_status'    => 'closed',

        'meta_input'   => [
        	'fp_feed_url' => $fp_feed_url,
			// 'fp_guid' => ,    
        ],
        'taxonomies'  => [
        	UTILITY_TAX => [
        		
        	],
        ],
    ));

    if (is_wp_error($feed_post_id)) {
        // Log an error if the feed post could not be created.
        error_log(
        	sprintf(
        		'Error creating feed post for link post with ID %d: %s',
        		$post_id,
        		$feed_post_id->get_error_message()
        	)
        );
    }
}

/**
 * Delete feed post when the "import" term is removed from a link post.
 *
 * @param array  $terms      Array of term IDs.
 * @param array  $object_ids Array of object IDs.
 * @param string $taxonomy   Taxonomy slug.
 * @param bool   $append     Whether to append terms to the object.
 * @param array  $old_terms  Array of term IDs before the update.
 *
 * @return array Array of term IDs.
 */
function add_or_delete_feed_post( array $new_terms, array $object_ids, string $taxonomy, bool $append, array $old_terms ) : array {
	//
	$import_term_id = get_import_term_id();

	// Return early if not the utility taxonomy or not the 'import' term being added or removed.
	if ( $taxonomy !== UTILITY_TAX || ! in_array( $import_term_id, $new_terms ) && ! in_array( $import_term_id, $old_terms ) ) {
		return $new_terms;
	}

	foreach ( $object_ids as $object_id ) {
		$post = get_post( $object_id );

		// Return early if not a link post.
		if ( $post->post_type !== LINK_PT ) {
			continue;
		}

		// Add or remove feed posts 
		// depending on 'import' term being 
		// added or removed from Link posts.
		// 
		// Term is new and not yet assigned
		if ( in_array( $import_term_id, $new_terms ) && ! in_array( $import_term_id, $old_terms ) ) {
			//
			$feed_post_id = create_feed_post( $object_id, $post );
		
		// Term is not assigned, but was previously
		} elseif ( ! in_array( $import_term_id, $new_terms ) && in_array( $import_term_id, $old_terms ) ) {
			//
			$feed_post_id = get_feed_from_link( $object_id );
			if ( $feed_post_id ) {
			    // Delete without trash bin
			    wp_delete_post( $feed_post_id, true );
			}
		}

	}

	return $new_terms;
}

/**
 * Delete feed post when a link post is trashed.
 *
 * @param int $post_id The ID of the post being trashed.
 */
function delete_feed_post_on_trash( int $post_id ) : void {
    $post = get_post( $post_id );
    
    // Bail if post type is not a Link
    if ( $post->post_type !== LINK_PT ) {
        return;
    }
    // Delete feed post
    wp_delete_post( get_feed_from_link( $post_id ), true );
}


function get_feed_from_link( int $link_post_id ) : int {
	$feed_post = get_posts( array(
	    'post_type'   => FEED_POSTTYPE,
	    'post_parent' => $link_post_id,
	    'numberposts' => 1,
	) );
	
	return ( empty($feed_post) ) ? 0 : $feed_post[0]->ID;
}

function get_import_term_id() : int {
	$term = get_term_by('name', UTILITY_TERM, UTILITY_TAX);
    // Get the "import" term ID.
    return (is_wp_error( $term )) ? 0 : $term->term_id;
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
 */
