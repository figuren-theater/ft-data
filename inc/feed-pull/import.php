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
use function delete_option;
use function do_blocks;
use function get_post;
use function get_post_type;
use function sanitize_text_field;
use function sanitize_textarea_field;
use function wp_parse_args;
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
		
		// post_meta of a normal 'fp_feed' post

		// 'fp_feed_url', // that should not treated by our filters
		// 'fp_guid',     // that should not treated by our filters

		'fp_posts_xpath',
		'fp_field_map',
		'fp_post_status',
		'fp_post_type',
		'fp_allow_updates',
		'fp_new_post_categories',
		// 'fp_custom_namespaces',
		// 'fp_namespace_prefix',
		// 'fp_namespace_url',
		

		// post_meta of an imported DESTINATION_POSTTYPE post
		
		'fp_syndicated_post', // 1 // should not be saved, as it's never used by the plugin itself
		// TODO // 'fp_source_feed_id', // int post_ID 
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
 * @param string $meta_type Type of object metadata is for. Accepts DESTINATION_POSTTYPE, 'comment', 'term', 'user',
 *                          or any other object type with an associated meta table.
 */
function default_post_metadata( mixed $value, int $object_id, string $meta_key ) : mixed {

	// Go out for all other post_meta
	if ( ! in_array( $meta_key, get_default_static_metas() ) ) {
		return $value;
	}

	switch ($meta_key) {
		
		case 'fp_posts_xpath':
			return 'feed/entry'; // Atom
		
		case 'fp_field_map':
			return get_fp_field_map();

		case 'fp_post_status':
			return 'pending';
		
		case 'fp_post_type':
			return DESTINATION_POSTTYPE;
		
		case 'fp_allow_updates':
			return true;
		
		case 'fp_new_post_categories':
			return [];
		
		case 'fp_source_feed_id':
			return get_fp_source_feed_id( $object_id );
		
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
 */
function get_fp_source_feed_id( int $post_id ) : string {

	$post = get_post( $post_id );

	if ( DESTINATION_POSTTYPE !== get_post_type( $post ) ) {
		return '';
	}

#	$_has_link_shadow_term = has_term( $term = '', $taxonomy = '', $post = null )
#
#	if () {
#		# code...
#	}

	return '1';
}



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
function dont_update_post_metadata( $check, int $object_id, string $meta_key ) : mixed {

	// all other post_meta
	if ( ! in_array( $meta_key, get_default_static_metas() ) ) {
		return $check;
	}

	// one special-operation 
	if ( 'fp_source_feed_id' === $meta_key ) {
		// 
		// relate_import_with_ft_link_shadow_tax()
	}
	
	// Send non-null, falsy return to prevent the current post_meta from being updated
	return false;
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
	#error_log(var_export(array( $pre_filter_post_value, $field, '$post', $source_feed_id ),true));


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
 * @param   int       $source_feed_id This is the Post, at least its ID
 * 
 * @return  array                     List of 'wp_insert_post()' combatible data.
 */
function fp_post_args( array $new_post_args, $post, int $source_feed_id ) : array {
	
	$import_args = get_import_args_from_source( $source_feed_id );

	// Set some defaults
	$import_args['comment_status'] = 'closed';
	$import_args['ping_status']    = 'closed';

	// TODO
	// add (yet non-existent) 'imported_from' taxonomy-term

	// 
	// $new_post_args['post_status'] = 

	// 

	// set author to machine user, if non set
	$new_post_args['post_author'] ?: Users\ft_bot::id();

	// strip (maybe) filled excerpt
	// if we can auto-generate it 
	if ( ! empty( $new_post_args['post_content'] ) && ! empty( $new_post_args['post_excerpt'] ) ) {
		unset($new_post_args['post_excerpt']);
	}

	$new_post_args = wp_parse_args( $import_args, $new_post_args )

	return wp_slash( $new_post_args );
}


function get_import_args_from_source( int $source_feed_id ) : array {
	

	$args = [
		'post_status' => 
	];

	return $args;
}
