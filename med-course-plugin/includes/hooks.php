<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Helper function to recursively get all descendant post IDs.
 *
 * @param int $parent_id The ID of the parent post.
 * @return array A flat array of all descendant post IDs.
 */
function mcp_get_all_descendant_ids( $parent_id ) {
    $descendants = [];
    $post_type = get_post_type( $parent_id );
    $child_post_type = '';
    $meta_key = '';

    if ( $post_type === 'course' ) {
        $child_post_type = 'lesson';
        $meta_key = '_mcp_course_id';
    } elseif ( $post_type === 'lesson' ) {
        $child_post_type = 'topic';
        $meta_key = '_mcp_lesson_id';
    } elseif ( $post_type === 'topic' ) {
        // Topics have two types of children, handle them separately
        $flashcard_ids = mcp_get_all_descendant_ids_by_meta( $parent_id, 'flashcard', '_mcp_topic_id' );
        $test_ids = mcp_get_all_descendant_ids_by_meta( $parent_id, 'test', '_mcp_topic_id' );
        return array_merge( $flashcard_ids, $test_ids );
    } else {
        return [];
    }

    return mcp_get_all_descendant_ids_by_meta( $parent_id, $child_post_type, $meta_key );
}

/**
 * Helper function to get descendants by a specific meta key.
 */
function mcp_get_all_descendant_ids_by_meta( $parent_id, $child_post_type, $meta_key ) {
    $child_ids = get_posts([
        'post_type' => $child_post_type,
        'meta_key' => $meta_key,
        'meta_value' => $parent_id,
        'fields' => 'ids',
        'numberposts' => -1,
    ]);

    $descendants = $child_ids;
    foreach ( $child_ids as $child_id ) {
        $descendants = array_merge( $descendants, mcp_get_all_descendant_ids( $child_id ) );
    }
    return $descendants;
}

/**
 * Handle cascading post trashing.
 * When a parent is trashed, all its children are also trashed.
 */
function mcp_handle_cascading_trash( $post_id ) {
    static $trashing = false;
    if ( $trashing ) return;

    $descendant_ids = mcp_get_all_descendant_ids( $post_id );
    if ( ! empty( $descendant_ids ) ) {
        $trashing = true;
        foreach ( $descendant_ids as $descendant_id ) {
            wp_trash_post( $descendant_id );
        }
        $trashing = false;
    }
}
add_action( 'wp_trash_post', 'mcp_handle_cascading_trash' );

/**
 * Modify admin query to sort lessons and topics by menu_order.
 */
function mcp_modify_admin_query( $query ) {
    if ( ! is_admin() || ! $query->is_main_query() ) {
        return;
    }

    $post_type = $query->get( 'post_type' );

    if ( in_array( $post_type, [ 'lesson', 'topic' ] ) ) {
        // Only set the default order if the user hasn't manually sorted the list
        if ( ! isset( $_GET['orderby'] ) ) {
            $query->set( 'orderby', 'menu_order' );
            $query->set( 'order', 'ASC' );
        }
    }
}
add_action( 'pre_get_posts', 'mcp_modify_admin_query' );

/**
 * Handle cascading post deletion.
 * When a parent is permanently deleted, all its children are also deleted.
 */
function mcp_handle_cascading_delete( $post_id ) {
    static $deleting = false;
    if ( $deleting ) return;

    $descendant_ids = mcp_get_all_descendant_ids( $post_id );
    if ( ! empty( $descendant_ids ) ) {
        $deleting = true;
        foreach ( $descendant_ids as $descendant_id ) {
            wp_delete_post( $descendant_id, true ); // Force delete
        }
        $deleting = false;
    }
}
add_action( 'before_delete_post', 'mcp_handle_cascading_delete' );
