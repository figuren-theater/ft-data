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
use function add_action;

// use FP_DELETED_OPTION_NAME; // 'fp_deleted_syndicated' // Debugging only.

use function get_post;
use function get_post_meta;
use function get_term_by;
use function wp_delete_post;
use function wp_insert_post;
use function wp_slash;
use WP_Error;

use WP_Post;
use WP_Query;
use WP_Term;

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
 * @param WP_Post $link_post    The post object being saved or updated.
 */
function create_feed_post( WP_Post $link_post ) : void {

	// Bail if post type is not a Link.
	if ( $link_post->post_type !== LINK_PT ) {
		return;
	}

	/**
	 * Look for a platform suggestion
	 * which use user may have given during registration.
	 *
	 * @see Figuren_Theater\src\FeaturesAssets\core-my-registration\wp_core.php
	 */
	$suggestion = get_post_meta( $link_post->ID, '_ft_platform', true );
	$suggestion = ( \is_string( $suggestion ) ) ? $suggestion : null;
	$bridged_url = Rss_Bridge\get_bridged_url( $link_post->post_content, $suggestion );
	if ( empty( $bridged_url ) ) {
		return;
	}

	// Get bridged URL.
	$fp_feed_url = esc_url( $bridged_url, [ 'https' ], 'db' );

	// Bail, if not importable.
	if ( ! $fp_feed_url ) {
		return;
	}

	// Prepare the insert arguments.
	$insert_args = wp_slash( [
		'post_author' => (int) $link_post->post_author,
		'post_type'   => Feed_Pull\FEED_POSTTYPE,
		'post_title'  => 'Feed: ' . $link_post->post_content,
		'post_parent' => (int) $link_post->ID,
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
	$feed_post_id = wp_insert_post( $insert_args, true );

	if ( ! $feed_post_id instanceof WP_Error ) {
		return;
	}

	// Something went wrong.
	// Log an error if the feed post could not be created.
	error_log(
		sprintf(
			'Error creating feed post for link post with ID %d: %s',
			$link_post->ID,
			$feed_post_id->get_error_message()
		)
	);
}

/**
 * Delete feed post when the "import" term is removed from a link post.
 *
 * Fires after an object's terms have been set.
 *
 * @param int    $link_post_id  Object ID.
 * @param string[]|int[]  $terms      An array of object term IDs or slugs.
 * @param int[]  $new_terms  An array of term taxonomy IDs.
 * @param string $taxonomy   Taxonomy slug.
 * @param bool   $append     Whether to append new terms to the old terms.
 * @param int[]  $old_terms  Old array of term taxonomy IDs.
 */
function add_or_delete_feed_post( int $link_post_id, array $terms, array $new_terms, string $taxonomy, bool $append, array $old_terms ) : void {
	$import_term_id = get_import_term_id();

	$is_in_new = in_array( $import_term_id, $new_terms, true );
	$is_in_old = in_array( $import_term_id, $old_terms, true );

	// Return early if not the utility taxonomy.
	if ( $taxonomy !== UTILITY_TAX ) {
		return;
	}

	// Return early if not the 'import' term being added or removed.
	if ( ! $is_in_new && ! $is_in_old ) {
		return;
	}

	// Return early if the 'import' term already existed and is not removed.
	if ( $is_in_new && $is_in_old ) {
		return;
	}

	$link_post = get_post( $link_post_id );

	// Return early if not a link post.
	if ( ( ! $link_post instanceof WP_Post ) || $link_post->post_type !== LINK_PT ) {
		return;
	}

	// Add or remove feed posts
	// depending on 'import' term being
	// added or removed from Link posts.
	//
	// Term is new and not yet assigned.
	if ( $is_in_new && ! $is_in_old ) {
		create_feed_post( $link_post );

		// Term is not assigned, but was previously.
	} elseif ( ! $is_in_new && $is_in_old ) {

		$feed_post_id = get_feed_from_link( $link_post_id );
		if ( $feed_post_id ) {
			// Delete without trash bin.
			wp_delete_post( $feed_post_id, true );
		}
	}
}

/**
 * Delete feed post when a link post is trashed.
 *
 * @param int $link_post_id The ID of the post being trashed.
 */
function delete_feed_post_on_trash( int $link_post_id ) : void {
	$link_post = get_post( $link_post_id );

	// Bail if post type is not a Link.
	if ( \is_null( $link_post ) || $link_post->post_type !== LINK_PT ) {
		return;
	}
	// Delete & trash the feed post.
	wp_delete_post( get_feed_from_link( $link_post_id ), true );
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

	if ( empty( $feed_query->posts ) || ! $feed_query->posts[0] instanceof WP_Post ) {
		return 0;
	}

	return $feed_query->posts[0]->ID;
}

/**
 * Get Term_ID of 'import' term within the Utility Taxonomy.
 *
 * @return int
 */
function get_import_term_id() : int {
	// Get the "import" term ID.
	$term = get_term_by( 'slug', UTILITY_TERM, UTILITY_TAX );

	if ( ! $term instanceof WP_Term ) {
		return 0;
	}

	return $term->term_id;
}
