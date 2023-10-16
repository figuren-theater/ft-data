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
 * @package figuren-theater/ft-data
 */

namespace Figuren_Theater\Data\Feed_Pull\Auto_Setup;

use Figuren_Theater\Data\Feed_Pull;

use Figuren_Theater\Data\Rss_Bridge;

// use FP_DELETED_OPTION_NAME; // 'fp_deleted_syndicated' // Debugging only.

use function add_action;
use function get_post;
use function get_post_meta;
use function get_term_by;
use function is_wp_error;
use function wp_delete_post;
use function wp_insert_post;
use function wp_slash;

use WP_Post;
use WP_Query;

// Normally defined in Post_Types\Post_Type__ft_link::NAME .
const LINK_PT = 'ft_link';
// Normally defined in Features\UtilityFeaturesManager::TAX .
const UTILITY_TAX = 'hm-utility';
// Normally defined in UtilityFeaturesRepo\UtilityFeature__ft_link__feedpull_import::SLUG .
const UTILITY_TERM = 'feedpull-import';

/**
 * Bootstrap module, when enabled.
 *
 * @return void
 */
function bootstrap() :void {
	add_action( 'admin_init', __NAMESPACE__ . '\\admin_init', 5 );
}

/**
 * Define hooks for automated 'CRUD' of FEED_POSTTYPE posts.
 *
 * @return void
 */
function admin_init() {

	// Debugging only.
	// phpcs:ignore
	// delete_option( FP_DELETED_OPTION_NAME );

	// Hook into save_post to create/update feed post.
	// add_action( 'save_post_'.LINK_PT, __NAMESPACE__ . '\\create_feed_post', 10, 2 ); // DEBUG !

	// Hook into set_object_terms to add or delete a feed post.
	add_action( 'set_object_terms', __NAMESPACE__ . '\\add_or_delete_feed_post', 10, 6 );

	// Hook into wp_trash_post to delete feed post.
	// add_action( 'wp_trash_post', __NAMESPACE__ . '\\delete_feed_post_on_trash' ); // MAYBE REMOVE, if really not needed anymore !
	add_action( 'before_delete_post', __NAMESPACE__ . '\\delete_feed_post_on_trash' );
}

/**
 * Create or update a feed post
 * when a link post with the "import" term
 * is saved or updated.
 *
 * @param WP_Post $post    The post object being saved or updated.
 */
function create_feed_post( WP_Post $post ) : void {

	// Bail if post type is not a Link.
	if ( $post->post_type !== LINK_PT ) {
		return;
	}

	/**
	 * Look for a platform suggestion
	 * which use user may have given during registration.
	 *
	 * @see Figuren_Theater\src\FeaturesAssets\core-my-registration\wp_core.php
	 */
	$suggestion = get_post_meta( $post->ID, '_ft_platform', true ) ?? null;

	// Get bridged URL.
	$fp_feed_url = esc_url(
		Rss_Bridge\get_bridged_url( $post->post_content, $suggestion ),
		'https',
		'db'
	);

	// Bail, if not importable.
	if ( ! $fp_feed_url ) {
		return;
	}

	// Prepare the insert arguments.
	$insert_args = wp_slash( [
		'post_author' => $post->post_author,
		'post_type'   => Feed_Pull\FEED_POSTTYPE,
		'post_title'  => 'Feed: ' . $post->post_content,
		'post_parent' => $post->ID,
		'post_status' => 'publish',

		'menu_order'     => 0,
		'comment_status' => 'closed',
		'ping_status'    => 'closed',

		'meta_input'   => [
			'fp_feed_url'    => $fp_feed_url,
			Feed_Pull\ADAPTER_POSTMETA => '', // @todo #16 // array_key of one of the get_bridges() array.
		],
		'tax_input'  => [
			UTILITY_TAX => [],
		],
	]);

	// Create the feed post with the link post as parent.
	$feed_post_id = wp_insert_post( $insert_args );

	if ( is_wp_error( $feed_post_id ) ) {
		// Log an error if the feed post could not be created.
		error_log(
			sprintf(
				'Error creating feed post for link post with ID %d: %s',
				$post->ID,
				$feed_post_id->get_error_message()
			)
		);
	}
}

/**
 * Delete feed post when the "import" term is removed from a link post.
 *
 * Fires after an object's terms have been set.
 *
 * @param int    $object_id  Object ID.
 * @param array  $terms      An array of object term IDs or slugs.
 * @param array  $new_terms  An array of term taxonomy IDs.
 * @param string $taxonomy   Taxonomy slug.
 * @param bool   $append     Whether to append new terms to the old terms.
 * @param array  $old_terms  Old array of term taxonomy IDs.
 */
function add_or_delete_feed_post( int $object_id, array $terms, array $new_terms, string $taxonomy, bool $append, array $old_terms ) : void {
		$import_term_id = get_import_term_id();

	// Return early if not the utility taxonomy or not the 'import' term being added or removed.
	if ( $taxonomy !== UTILITY_TAX || ! in_array( $import_term_id, $new_terms, true ) && ! in_array( $import_term_id, $old_terms, true ) ) {
		return;
	}

	$post = get_post( $object_id );

	// Return early if not a link post.
	if ( ! is_a( $post, 'WP_Post' ) || $post->post_type !== LINK_PT ) {
		return;
	}

	// Add or remove feed posts
	// depending on 'import' term being
	// added or removed from Link posts.
	//
	// Term is new and not yet assigned.
	if ( in_array( $import_term_id, $new_terms, true ) && ! in_array( $import_term_id, $old_terms, true ) ) {
				create_feed_post( $post );

		// Term is not assigned, but was previously.
	} elseif ( ! in_array( $import_term_id, $new_terms, true ) && in_array( $import_term_id, $old_terms, true ) ) {
				$feed_post_id = get_feed_from_link( $object_id );
		if ( $feed_post_id ) {
			// Delete without trash bin.
			wp_delete_post( $feed_post_id, true );
		}
	}
}

/**
 * Delete feed post when a link post is trashed.
 *
 * @param int $post_id The ID of the post being trashed.
 */
function delete_feed_post_on_trash( int $post_id ) : void {
	$post = get_post( $post_id );

	// Bail if post type is not a Link.
	if ( $post->post_type !== LINK_PT ) {
		return;
	}
	// Delete & trash the feed post.
	wp_delete_post( get_feed_from_link( $post_id ), true );
}

/**
 * Get the FEED post, by its post_parent ID.
 *
 * @param  int $link_post_id A LINK_PT post_ID.
 *
 * @return int A Feed_Pull\FEED_POSTTYPE post_ID.
 */
function get_feed_from_link( int $link_post_id ) : int {

	$feed_query = new WP_Query( [
		'post_type'   => Feed_Pull\FEED_POSTTYPE,
		'post_parent' => $link_post_id,
		'numberposts' => 1,
	] );

	return ( empty( $feed_query->posts ) ) ? 0 : $feed_query->posts[0]->ID;
}

/**
 * Get Term_ID of 'import' term within the Utility Taxonomy.
 *
 * @return int
 */
function get_import_term_id() : int {
	$term = get_term_by( 'slug', UTILITY_TERM, UTILITY_TAX );
	// Get the "import" term ID.
	return ( is_wp_error( $term ) ) ? 0 : $term->term_id;
}
