<?php
/**
 * Figuren_Theater Data Feed_Pull.
 *
 * @package figuren-theater/data/feed_pull
 */

namespace Figuren_Theater\Data\Feed_Pull;

use Figuren_Theater\Data\Rss_Bridge;

use Figuren_Theater\Network\Features;
use Figuren_Theater\Network\Taxonomies;
use Figuren_Theater\Network\Users;

use function add_action;
use function add_filter;
use function delete_option;
use function do_blocks;
use function get_post;
use function get_post_meta;
use function get_post_parent;
use function get_post_type;
use function get_the_terms;
use function sanitize_text_field;
use function sanitize_textarea_field;
use function wp_list_pluck;
use function wp_parse_args;
use function wp_set_object_terms;
use function wp_slash;

function bootstrap_import() {

	add_action( 'init', __NAMESPACE__ . '\\init', 5 );
}

function init() {

	// for debugging only
	// delete_option( 'fp_deleted_syndicated' );




	// https://github.com/tlovett1/feed-pull/blob/45d667c1275cca0256bd03ed6fa1655cdf26f064/includes/class-fp-pull.php#L274
	add_filter( 'fp_pre_post_insert_value', __NAMESPACE__ . '\\fp_pre_post_insert_value', 10, 4 );



	add_filter( 'fp_post_args', __NAMESPACE__ . '\\fp_post_args', 10, 3 );


	add_filter( 'default_post_metadata', __NAMESPACE__ . '\\default_post_metadata', 10, 3 );
	add_filter( 'update_post_metadata', __NAMESPACE__ . '\\dont_update_post_metadata', 1000, 3 );
}

/**
 * List of all post_meta keys, that are used by the 'feed-pull' plugin
 * and which should not be saved to the DB, for performance and storage reasons, 
 * but without compromising functionality.
 * 
 * @see https://github.com/tlovett1/feed-pull/blob/45d667c1275cca0256bd03ed6fa1655cdf26f064/includes/class-fp-pull.php#L174-L181
 *
 * @return  array  post_meta keys we want to act on or with.
 */
function get_default_static_metas() : array {
	return [
		
		// ////////////////////////////////////////////////////
		// post_meta of a normal 'fp_feed' post
		// ////////////////////////////////////////////////////

		// 'fp_feed_url', // that should not treated by our filters

		'fp_posts_xpath',					// this should come from a defined BridgeAdapter
		'fp_field_map',						// this should come from a defined BridgeAdapter
		#'fp_post_status',					// this should NOT come from a defined BridgeAdapter
		#'fp_post_type',					// this should NOT come from a defined BridgeAdapter
		'fp_allow_updates',					// this should come from a defined BridgeAdapter
		'fp_new_post_categories',			// this should come from a defined BridgeAdapter
		// 'fp_custom_namespaces',			// this should come from a defined BridgeAdapter
		// 'fp_namespace_prefix',			// this should come from a defined BridgeAdapter
		// 'fp_namespace_url',				// this should come from a defined BridgeAdapter
		

		// ////////////////////////////////////////////////////
		// post_meta of an imported DESTINATION_POSTTYPE post
		// ////////////////////////////////////////////////////
		
		// 'fp_source_feed_id', // source post_ID
		'fp_syndicated_post', // 1 // should not be saved, as it's never used by the plugin itself
		// 'fp_guid',     // that should not treated by our filters
	];
}

/**
 * Filters the default metadata value for a specified meta key and object.
 *
 * The dynamic portion of the hook name, `$meta_type`, refers to the meta object type
 * (post, comment, term, user, or any other type with an associated meta table).
 *
 * Possible filter names include:
 *
 *  - `default_post_metadata`
 *  - `default_comment_metadata`
 *  - `default_term_metadata`
 *  - `default_user_metadata`
 *
 * @see   https://github.com/WordPress/wordpress-develop/blob/6.1/src/wp-includes/meta.php#L714-L714
 *
 * @param mixed  $value     The value to return, either a single metadata value or an array
 *                          of values depending on the value of `$single`.
 * @param int    $object_id ID of the object metadata is for.
 * @param string $meta_key  Metadata key.
 * @param bool   $single    Whether to return only the first value of the specified `$meta_key`.
 * @param string $meta_type Type of object metadata is for. Can be 'post', 'comment', 'term', 'user',
 *                          or any other object type with an associated meta table.
 */
function default_post_metadata( mixed $value, int $object_id, string $meta_key ) : mixed {

	// Go out for all other post_meta
	if ( ! in_array( $meta_key, get_default_static_metas() ) ) {
		return $value;
	}

	$adapter = get_post_meta( $object_id, ADAPTER_POSTMETA, true );
	$bridges = Rss_Bridge\get_bridges();

	if ( ! isset($bridges[$adapter])) {
		return $value;
	}

	$adapter = $bridges[$adapter];

	switch ($meta_key) {

		case 'fp_posts_xpath':
			return $adapter['fp_posts_xpath'] ?? 'feed/entry'; // Atom

		case 'fp_field_map':
			return $adapter['fp_field_map'] ?? get_fp_field_map();

		#case 'fp_post_status':
		#	return 'pending';

		// case 'fp_post_type':
			// return $adapter['fp_post_type'] ?? DESTINATION_POSTTYPE;

		case 'fp_allow_updates':
			return $adapter['fp_allow_updates'] ?? true;

		case 'fp_new_post_categories':
			return [];

		#case 'fp_source_feed_id':
		#	return get_fp_source_feed_id( $object_id );

		default:
			return $value;
	}
}

/**
 * Default static post_meta that doesn't need to be saved into DB
 * because its the same (per bridge)
 *
 * @return  array List of feed-fields and their mappings within WordPress, following the 'feed-pull'-plugin conventions.
 */
function get_fp_field_map() : array {
	return array (
		array (
			'source_field'      => 'title', // Atom
			'destination_field' => 'post_title',
			'mapping_type'      => 'post_field',
		),
		array (
			'source_field'      => 'id', // Atom
			'destination_field' => 'guid',
			'mapping_type'      => 'post_field',
		),
	);
}


/**
 * Normally the 'fp_source_feed_id' post_meta holds an post_ID.
 * 
 * This post_ID belongs to the 'feed-pull'-post, where the feed is defined, 
 * that this post is imported from.
 * 
 * The post_meta is later on, only used within a simple empty() check,
 *
 *
 * @return  string

function get_fp_source_feed_id( int $post_id ) : int|false {

	$post = get_post( $post_id );

	if ( DESTINATION_POSTTYPE !== get_post_type( $post ) ) {
		return false;
	}

	$ft_link_shadows = get_the_terms( $post, Taxonomies\Taxonomy__ft_link_shadow::NAME );

	if ($ft_link_shadows) {
		$TAX_Shadow = Taxonomies\TAX_Shadow::init();
		return $TAX_Shadow->get_associated_post_id( $ft_link_shadows );
	}

	return false;
}
 */


/**
 * Short-circuits updating metadata of a specific type.
 *
 * The dynamic portion of the hook name, `$meta_type`, refers to the meta object type
 * (post, comment, term, user, or any other type with an associated meta table).
 * Returning a non-null value will effectively short-circuit the function.
 *
 * Possible hook names include:
 *
 *  - `update_post_metadata`      <--- !!!
 *  - `update_comment_metadata`
 *  - `update_term_metadata`
 *  - `update_user_metadata`
 *
 * @since 3.1.0
 *
 * @param null|bool $check      Whether to allow updating metadata for the given type.
 * @param int       $object_id  ID of the object metadata is for.
 * @param string    $meta_key   Metadata key.
 * @param mixed     $meta_value Metadata value. Must be serializable if non-scalar.
 * @param mixed     $prev_value Optional. Previous value to check before updating.
 *                              If specified, only update existing metadata entries with
 *                              this value. Otherwise, update all entries.
 */
function dont_update_post_metadata( $check, int $object_id, string $meta_key, mixed $meta_value ) : mixed {
/*
	// one special-operation 
	// but instead of writing to post_meta
	// we are creating a taxonomy relation
	// which can be queried much faster ... later on
	if ( 'fp_source_feed_id' === $meta_key && 0 < intval($meta_value) ) {
		// 0. prepare for readability
		$taxonomy = Taxonomies\Taxonomy__ft_link_shadow::NAME;
		// 1. get sourced 'ft_link' post
		$ft_link = get_post( intval( $meta_value ) );
		// 2. get sourced 'ft_link_shadow'-term-id
		$TAX_Shadow = Taxonomies\TAX_Shadow::init();
		$ft_link_term = $TAX_Shadow->get_associated_term( 
			$ft_link, 
			$taxonomy
		);

		if (false !== $ft_link_term) {
			// relate import with ft_link_shadow tax
			wp_set_object_terms( 
				$object_id,
				$ft_link_term->term_id,
				$taxonomy
			);
		}
	}
*/
	// Send non-null, falsy return to prevent feed-pull post_meta from being written|updated
	if ( in_array( $meta_key, get_default_static_metas() ) ) {
		return false;
	}	

	// all other post_meta
	return $check;
}


/**
 * [fp_pre_post_insert_value description]
 * @param  [mixed] $pre_filter_post_value [description]
 * @param  [array] $field                 [description]
 *
 *   array (
 *     'source_field' => 'guid',
 *     'destination_field' => 'guid',
 *     'mapping_type' => 'post_field', 
 *   ), 
 *   
 * @param  [WP_Post] $post                  [description]
 * @param  [int] $source_feed_id        [description]
 * 
 * @return [type]                        [description]
 */
function fp_pre_post_insert_value( $pre_filter_post_value, $field, $post, $source_feed_id  ): string {

	if ( 'post_title' == $field['destination_field'] )
		return sanitize_text_field( $pre_filter_post_value );

	if ( 'post_excerpt' == $field['destination_field'] )
		return sanitize_textarea_field( $pre_filter_post_value );

	if ( 'post_content' == $field['destination_field'] ) {
	/*
		$tags_to_strip = Array("figure","font" );
		foreach ($tags_to_strip as $tag)
		{
		    $pre_filter_post_value = preg_replace("/<\\/?" . $tag . "(.|\\s)*?>/",'',$pre_filter_post_value);

		}		serialize_block( $block );*/
		// return \wpautop( \wp_kses_post( $pre_filter_post_value ), true );
		// return \do_blocks( \wp_kses_post( $pre_filter_post_value ), true );
		return do_blocks( $pre_filter_post_value );
	}

	// all other fields
	return $pre_filter_post_value;
}	



/**
 * Change the arguments for the new or to-update 'post' post
 * that gets inserted|updated directly after this filter.
 *
 * Last chance to change anything
 * and: anything available ;)
 *
 * @param   array     $new_post_args  [description]
 * @param   ??????    $post           This is not a WP_Post.
 * @param   int       $source_feed_id This is the fp_feed Post, at least its ID, which is sourcing the new post
 * 
 * @return  array                     List of 'wp_insert_post()' combatible data.
 */
function fp_post_args( array $new_post_args, $post, int $source_feed_id ) : array {
	
	$import_args = get_import_args_from_source( $source_feed_id );

	// Set some defaults
	$import_args['comment_status'] = 'closed';
	$import_args['ping_status']    = 'closed';

	// set author to machine user, if non set
	$new_post_args['post_author'] ?: Users\ft_bot::id();

	// strip (maybe) filled excerpt
	// if we can auto-generate it 
	if ( ! empty( $new_post_args['post_content'] ) && ! empty( $new_post_args['post_excerpt'] ) ) {
		unset($new_post_args['post_excerpt']);
	}

	$new_post_args = wp_parse_args( $import_args, $new_post_args );

	return wp_slash( $new_post_args );
}


function get_import_args_from_source( int $source_feed_id ) : array {
	// 1. get sourced 'ft_link' post, 
	// which is the parent of the 'fp_feed' that is sourcing this post
	$ft_link =  get_post_parent( get_post( $source_feed_id ) );
	
	// 2. get sourced 'ft_link_shadow'-term-id
	$TAX_Shadow = Taxonomies\TAX_Shadow::init();
	$ft_link_term = $TAX_Shadow->get_associated_term( 
		$ft_link, 
		$taxonomy
	);

	// 3. translate 'utility-tax' terms at the source
	//    into post-fields of the import
	// $args = get_post_fields_from_utility_terms( $source_feed_id ) + [
	$args = [
		'tax_input'  => [
			Taxonomies\Taxonomy__ft_link_shadow::NAME => [ $ft_link_term->term_id ],
		],
	];

	return $args;
}

/*
function get_post_fields_from_utility_terms( int $source_feed_id ) : array {
	$utility_terms = get_the_terms( 
		get_post( $source_feed_id ),
		Features\UtilityFeaturesManager::TAX
	);

	if (empty($utility_terms)) {
		return [];
	}

	$utility_terms = wp_list_pluck( $utility_terms, 'slug' );

	return array_map(
		__NAMESPACE__ . '\\__get_post_field_from_term_slug',
		$utility_terms
	);
}

function __get_post_field_from_term_slug( string $term_slug ) : array {
	$parts = explode('-', $term_slug);
	$field = str_replace('post', 'post_', $parts[1]);
	return [ $field => $parts[2] ];
}
*/
