<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Add filters to the admin list tables.
 */
function mcp_add_admin_list_filters() {
    global $typenow;

    if ( in_array( $typenow, [ 'lesson', 'topic' ] ) ) {
        // Course Filter
        $current_course = isset( $_GET['mcp_filter_course'] ) ? absint( $_GET['mcp_filter_course'] ) : 0;
        $courses = get_posts( [ 'post_type' => 'course', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC' ] );

        echo '<select name="mcp_filter_course" id="mcp_filter_course">';
        echo '<option value="">' . __( 'All Courses', 'med-course-plugin' ) . '</option>';
        foreach ( $courses as $course ) {
            echo '<option value="' . esc_attr( $course->ID ) . '" ' . selected( $current_course, $course->ID, false ) . '>' . esc_html( $course->post_title ) . '</option>';
        }
        echo '</select>';

        // Lesson Filter (only for topics)
        if ( $typenow === 'topic' ) {
            $current_lesson = isset( $_GET['mcp_filter_lesson'] ) ? absint( $_GET['mcp_filter_lesson'] ) : 0;

            echo '<select name="mcp_filter_lesson" id="mcp_filter_lesson">';
            echo '<option value="">' . __( 'All Lessons', 'med-course-plugin' ) . '</option>';

            if ( $current_course ) {
                $lessons = get_posts( [
                    'post_type'  => 'lesson',
                    'meta_key'   => '_mcp_course_id',
                    'meta_value' => $current_course,
                    'numberposts' => -1,
                    'orderby'    => 'title',
                    'order'      => 'ASC'
                ] );
                foreach ( $lessons as $lesson ) {
                    echo '<option value="' . esc_attr( $lesson->ID ) . '" ' . selected( $current_lesson, $lesson->ID, false ) . '>' . esc_html( $lesson->post_title ) . '</option>';
                }
            }
            echo '</select>';
        }
    }
}
add_action( 'restrict_manage_posts', 'mcp_add_admin_list_filters' );

/**
 * Filter the query based on selected admin filters.
 */
function mcp_filter_admin_list_query( $query ) {
    global $pagenow;

    if ( ! is_admin() || $pagenow !== 'edit.php' || ! $query->is_main_query() ) {
        return;
    }

    $post_type = $query->get( 'post_type' );

    if ( in_array( $post_type, [ 'lesson', 'topic' ] ) ) {
        $meta_query = (array) $query->get( 'meta_query' );

        if ( ! empty( $_GET['mcp_filter_course'] ) ) {
            $meta_query[] = [
                'key'   => '_mcp_course_id',
                'value' => absint( $_GET['mcp_filter_course'] ),
            ];
        }

        if ( $post_type === 'topic' && ! empty( $_GET['mcp_filter_lesson'] ) ) {
            $meta_query[] = [
                'key'   => '_mcp_lesson_id',
                'value' => absint( $_GET['mcp_filter_lesson'] ),
            ];
        }

        if ( ! empty( $meta_query ) ) {
            $query->set( 'meta_query', $meta_query );
        }
    }
}
add_action( 'pre_get_posts', 'mcp_filter_admin_list_query' );
