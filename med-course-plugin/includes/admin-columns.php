<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Add custom columns to the section_type post type list.
 */
function mcp_add_section_type_columns( $columns ) {
    $columns['icon_class'] = __( 'Icon Class', 'med-course-plugin' );
    return $columns;
}
add_filter( 'manage_section_type_posts_columns', 'mcp_add_section_type_columns' );

/**
 * Display the content for the custom columns.
 */
function mcp_section_type_column_content( $column, $post_id ) {
    if ( $column == 'icon_class' ) {
        $icon_class = get_post_meta( $post_id, '_mcp_icon_class', true );
        echo esc_html( $icon_class );
    }
}
add_action( 'manage_section_type_posts_custom_column', 'mcp_section_type_column_content', 10, 2 );
