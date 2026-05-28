<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register Custom Post Types.
 */
function mcp_register_post_types() {
    /**
     * Post Type: Courses.
     */
    $labels = [
        "name" => __( "Courses", "med-course-plugin" ),
        "singular_name" => __( "Course", "med-course-plugin" ),
    ];
    $args = [
        "label" => __( "Courses", "med-course-plugin" ),
        "labels" => $labels,
        "description" => "",
        "public" => true,
        "publicly_queryable" => true,
        "show_ui" => true,
        "show_in_rest" => true,
        "rest_base" => "",
        "rest_controller_class" => "WP_REST_Posts_Controller",
        "has_archive" => false,
        "show_in_menu" => "course_builder_page",
        "show_in_nav_menus" => true,
        "delete_with_user" => false,
        "exclude_from_search" => false,
        "capability_type" => "post",
        "map_meta_cap" => true,
        "hierarchical" => false,
        "rewrite" => false,
        "query_var" => true,
        "menu_icon" => "dashicons-welcome-learn-more",
        "supports" => [ "title", "editor", "thumbnail" ],
    ];
    register_post_type( "course", $args );

    /**
     * Post Type: Lessons.
     */
    $labels = [
        "name" => __( "Lessons", "med-course-plugin" ),
        "singular_name" => __( "Lesson", "med-course-plugin" ),
    ];
    $args = [
        "label" => __( "Lessons", "med-course-plugin" ),
        "labels" => $labels,
        "description" => "",
        "public" => true,
        "publicly_queryable" => true,
        "show_ui" => true,
        "show_in_rest" => true,
        "rest_base" => "",
        "rest_controller_class" => "WP_REST_Posts_Controller",
        "has_archive" => false,
        "show_in_menu" => "course_builder_page",
        "show_in_nav_menus" => true,
        "delete_with_user" => false,
        "exclude_from_search" => false,
        "capability_type" => "post",
        "map_meta_cap" => true,
        "hierarchical" => false,
        "rewrite" => false,
        "query_var" => true,
        "menu_icon" => "dashicons-book",
        "supports" => [ "title", "editor", "thumbnail", "page-attributes" ],
    ];
    register_post_type( "lesson", $args );

    /**
     * Post Type: Topics.
     */
    $labels = [
        "name" => __( "Topics", "med-course-plugin" ),
        "singular_name" => __( "Topic", "med-course-plugin" ),
    ];
    $args = [
        "label" => __( "Topics", "med-course-plugin" ),
        "labels" => $labels,
        "description" => "",
        "public" => true,
        "publicly_queryable" => true,
        "show_ui" => true,
        "show_in_rest" => true,
        "rest_base" => "",
        "rest_controller_class" => "WP_REST_Posts_Controller",
        "has_archive" => false,
        "show_in_menu" => "course_builder_page",
        "show_in_nav_menus" => true,
        "delete_with_user" => false,
        "exclude_from_search" => false,
        "capability_type" => "post",
        "map_meta_cap" => true,
        "hierarchical" => false,
        "rewrite" => false,
        "query_var" => true,
        "menu_icon" => "dashicons-analytics",
        "supports" => [ "title", "editor", "thumbnail", "page-attributes" ],
    ];
    register_post_type( "topic", $args );

    /**
     * Post Type: Flashcards.
     */
    $labels = [
        "name" => __( "Flashcards", "med-course-plugin" ),
        "singular_name" => __( "Flashcard", "med-course-plugin" ),
    ];
    $args = [
        "label" => __( "Flashcards", "med-course-plugin" ),
        "labels" => $labels,
        "description" => "",
        "public" => true,
        "publicly_queryable" => true,
        "show_ui" => true,
        "show_in_rest" => true,
        "rest_base" => "",
        "rest_controller_class" => "WP_REST_Posts_Controller",
        "has_archive" => false,
        "show_in_menu" => "course_builder_page",
        "show_in_nav_menus" => true,
        "delete_with_user" => false,
        "exclude_from_search" => false,
        "capability_type" => "post",
        "map_meta_cap" => true,
        "hierarchical" => false,
        "rewrite" => [ "slug" => "flashcard", "with_front" => true ],
        "query_var" => true,
        "menu_icon" => "dashicons-slides",
        "supports" => [ "title" ],
    ];
    register_post_type( "flashcard", $args );

    /**
     * Post Type: Tests.
     */
    $labels = [
        "name" => __( "Tests", "med-course-plugin" ),
        "singular_name" => __( "Test", "med-course-plugin" ),
    ];
    $args = [
        "label" => __( "Tests", "med-course-plugin" ),
        "labels" => $labels,
        "description" => "",
        "public" => true,
        "publicly_queryable" => true,
        "show_ui" => true,
        "show_in_rest" => true,
        "rest_base" => "",
        "rest_controller_class" => "WP_REST_Posts_Controller",
        "has_archive" => false,
        "show_in_menu" => "course_builder_page",
        "show_in_nav_menus" => true,
        "delete_with_user" => false,
        "exclude_from_search" => false,
        "capability_type" => "post",
        "map_meta_cap" => true,
        "hierarchical" => false,
        "rewrite" => [ "slug" => "test", "with_front" => true ],
        "query_var" => true,
        "menu_icon" => "dashicons-text-page",
        "supports" => [ "title" ],
    ];
    register_post_type( "test", $args );

    /**
     * Post Type: Section Types.
     */
    $labels = [
        "name" => __( "Section Types", "med-course-plugin" ),
        "singular_name" => __( "Section Type", "med-course-plugin" ),
    ];
    $args = [
        "label" => __( "Section Types", "med-course-plugin" ),
        "labels" => $labels,
        "description" => "",
        "public" => false,
        "publicly_queryable" => false,
        "show_ui" => true,
        "show_in_rest" => false,
        "has_archive" => false,
        "show_in_menu" => "course_builder_page",
        "show_in_nav_menus" => false,
        "delete_with_user" => false,
        "exclude_from_search" => true,
        "capability_type" => "post",
        "map_meta_cap" => true,
        "hierarchical" => false,
        "rewrite" => [ "slug" => "section-type", "with_front" => true ],
        "query_var" => true,
        "menu_icon" => "dashicons-tag",
        "supports" => [ "title" ],
    ];
    register_post_type( "section_type", $args );
}
add_action( 'init', 'mcp_register_post_types' );

/**
 * Setup custom admin menu.
 */
function mcp_admin_menu_setup() {
    add_menu_page(
        'کورس ساز',                 // page_title
        'کورس ساز',                 // menu_title
        'manage_options',             // capability
        'course_builder_page',        // menu_slug
        null,                         // function
        'dashicons-welcome-learn-more', // icon_url
        1                             // position
    );
}
add_action( 'admin_menu', 'mcp_admin_menu_setup' );
