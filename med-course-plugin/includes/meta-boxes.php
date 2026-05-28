<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register meta boxes.
 */
function mcp_register_meta_boxes() {
    add_meta_box(
        'mcp_section_type_icon_meta_box',
        __( 'Icon Class', 'med-course-plugin' ),
        'mcp_section_type_icon_meta_box_html',
        'section_type',
        'side'
    );

    add_meta_box(
        'mcp_lesson_parent_meta_box',
        __( 'Course', 'med-course-plugin' ),
        'mcp_lesson_parent_meta_box_html',
        'lesson',
        'side'
    );

    add_meta_box(
        'mcp_topic_course_meta_box',
        __( 'Course', 'med-course-plugin' ),
        'mcp_topic_course_meta_box_html',
        'topic',
        'side'
    );

    add_meta_box(
        'mcp_topic_parent_meta_box',
        __( 'Lesson', 'med-course-plugin' ),
        'mcp_topic_parent_meta_box_html',
        'topic',
        'side'
    );

    add_meta_box(
        'mcp_flashcard_course_meta_box',
        __( 'Course', 'med-course-plugin' ),
        'mcp_topic_course_meta_box_html', // Reusing the same callback
        'flashcard',
        'side'
    );

    add_meta_box(
        'mcp_flashcard_lesson_meta_box',
        __( 'Lesson', 'med-course-plugin' ),
        'mcp_topic_parent_meta_box_html', // Reusing the same callback
        'flashcard',
        'side'
    );

    add_meta_box(
        'mcp_flashcard_parent_meta_box',
        __( 'Topic', 'med-course-plugin' ),
        'mcp_flashcard_parent_meta_box_html',
        'flashcard',
        'side'
    );

    add_meta_box(
        'mcp_test_course_meta_box',
        __( 'Course', 'med-course-plugin' ),
        'mcp_topic_course_meta_box_html', // Reusing the same callback
        'test',
        'side'
    );

    add_meta_box(
        'mcp_test_lesson_meta_box',
        __( 'Lesson', 'med-course-plugin' ),
        'mcp_topic_parent_meta_box_html', // Reusing the same callback
        'test',
        'side'
    );

    add_meta_box(
        'mcp_test_parent_meta_box',
        __( 'Topic', 'med-course-plugin' ),
        'mcp_test_parent_meta_box_html',
        'test',
        'side'
    );

    add_meta_box(
        'mcp_topic_sections_meta_box',
        __( 'Sections', 'med-course-plugin' ),
        'mcp_topic_sections_meta_box_html',
        'topic',
        'normal'
    );

    add_meta_box(
        'mcp_flashcard_details_meta_box',
        __( 'Flashcard Details', 'med-course-plugin' ),
        'mcp_flashcard_details_meta_box_html',
        'flashcard',
        'normal'
    );

    add_meta_box(
        'mcp_test_details_meta_box',
        __( 'Test Details', 'med-course-plugin' ),
        'mcp_test_details_meta_box_html',
        'test',
        'normal'
    );

    add_meta_box(
        'mcp_topic_flashcards_meta_box',
        __( 'Add Flashcards', 'med-course-plugin' ),
        'mcp_topic_flashcards_meta_box_html',
        'topic',
        'normal'
    );

    add_meta_box(
        'mcp_topic_tests_meta_box',
        __( 'Add Tests', 'med-course-plugin' ),
        'mcp_topic_tests_meta_box_html',
        'topic',
        'normal'
    );

    add_meta_box(
        'mcp_course_lessons_order_meta_box',
        __( 'Lessons Order', 'med-course-plugin' ),
        'mcp_course_lessons_order_meta_box_html',
        'course',
        'normal'
    );

    add_meta_box(
        'mcp_lesson_topics_order_meta_box',
        __( 'Topics Order', 'med-course-plugin' ),
        'mcp_lesson_topics_order_meta_box_html',
        'lesson',
        'normal'
    );

    $slug_post_types = [ 'course', 'lesson', 'topic' ];
    foreach ( $slug_post_types as $post_type ) {
        add_meta_box(
            'mcp_english_slug_meta_box',
            __( 'English Slug', 'med-course-plugin' ),
            'mcp_english_slug_meta_box_html',
            $post_type,
            'side'
        );
    }
}
add_action( 'add_meta_boxes', 'mcp_register_meta_boxes' );

/**
 * Meta box display callback for English slug.
 */
function mcp_english_slug_meta_box_html( $post ) {
    $slug = get_post_meta( $post->ID, '_mcp_english_slug', true );
    wp_nonce_field( 'mcp_save_english_slug_meta_box_data', 'mcp_english_slug_meta_box_nonce' );
    ?>
    <label for="mcp_english_slug"><?php _e( 'Enter a URL-friendly slug:', 'med-course-plugin' ); ?></label>
    <input type="text" id="mcp_english_slug" name="mcp_english_slug" value="<?php echo esc_attr( $slug ); ?>" class="widefat">
    <p class="howto"><?php _e( 'Use only lowercase letters, numbers, and hyphens.', 'med-course-plugin' ); ?></p>
    <?php
}

/**
 * Meta box display callback for lesson parent.
 */
function mcp_lesson_parent_meta_box_html( $post ) {
    $parent_id = get_post_meta( $post->ID, '_mcp_course_id', true );
    $courses = get_posts( [ 'post_type' => 'course', 'numberposts' => -1 ] );
    wp_nonce_field( 'mcp_save_lesson_parent_meta_box_data', 'mcp_lesson_parent_meta_box_nonce' );
    ?>
    <label for="mcp_course_id"><?php _e( 'Select Course:', 'med-course-plugin' ); ?></label>
    <select id="mcp_course_id" name="mcp_course_id" class="widefat">
        <option value=""><?php _e( '— Select —', 'med-course-plugin' ); ?></option>
        <?php foreach ( $courses as $course ) : ?>
            <option value="<?php echo esc_attr( $course->ID ); ?>" <?php selected( $parent_id, $course->ID ); ?>>
                <?php echo esc_html( $course->post_title ); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php
}

/**
 * Meta box display callback for topic course.
 */
function mcp_topic_course_meta_box_html( $post ) {
    $course_id = get_post_meta( $post->ID, '_mcp_course_id', true );
    $courses = get_posts( [ 'post_type' => 'course', 'numberposts' => -1 ] );
    wp_nonce_field( 'mcp_save_topic_course_meta_box_data', 'mcp_topic_course_meta_box_nonce' );
    ?>
    <label for="mcp_topic_course_id"><?php _e( 'Select Course:', 'med-course-plugin' ); ?></label>
    <select id="mcp_topic_course_id" name="mcp_topic_course_id" class="widefat">
        <option value=""><?php _e( '— Select —', 'med-course-plugin' ); ?></option>
        <?php foreach ( $courses as $course ) : ?>
            <option value="<?php echo esc_attr( $course->ID ); ?>" <?php selected( $course_id, $course->ID ); ?>>
                <?php echo esc_html( $course->post_title ); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php
}

/**
 * Meta box display callback for topic parent.
 */
function mcp_topic_parent_meta_box_html( $post ) {
    $parent_id = get_post_meta( $post->ID, '_mcp_lesson_id', true );
    $lessons = get_posts( [ 'post_type' => 'lesson', 'numberposts' => -1 ] );
    wp_nonce_field( 'mcp_save_topic_parent_meta_box_data', 'mcp_topic_parent_meta_box_nonce' );
    ?>
    <label for="mcp_lesson_id"><?php _e( 'Select Lesson:', 'med-course-plugin' ); ?></label>
    <select id="mcp_lesson_id" name="mcp_lesson_id" class="widefat">
        <option value=""><?php _e( '— Select —', 'med-course-plugin' ); ?></option>
        <?php foreach ( $lessons as $lesson ) : ?>
            <option value="<?php echo esc_attr( $lesson->ID ); ?>" <?php selected( $parent_id, $lesson->ID ); ?>>
                <?php echo esc_html( $lesson->post_title ); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php
}

/**
 * Meta box display callback for flashcard parent.
 */
function mcp_flashcard_parent_meta_box_html( $post ) {
    $parent_id = get_post_meta( $post->ID, '_mcp_topic_id', true );
    $topics = get_posts( [ 'post_type' => 'topic', 'numberposts' => -1 ] );
    wp_nonce_field( 'mcp_save_flashcard_parent_meta_box_data', 'mcp_flashcard_parent_meta_box_nonce' );
    ?>
    <label for="mcp_topic_id"><?php _e( 'Select Topic:', 'med-course-plugin' ); ?></label>
    <select id="mcp_topic_id" name="mcp_topic_id" class="widefat">
        <option value=""><?php _e( '— Select —', 'med-course-plugin' ); ?></option>
        <?php foreach ( $topics as $topic ) : ?>
            <option value="<?php echo esc_attr( $topic->ID ); ?>" <?php selected( $parent_id, $topic->ID ); ?>>
                <?php echo esc_html( $topic->post_title ); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php
}

/**
 * Meta box display callback for flashcard details.
 */
function mcp_flashcard_details_meta_box_html( $post ) {
    $question = get_post_meta( $post->ID, '_mcp_question', true );
    $answer = get_post_meta( $post->ID, '_mcp_answer', true );
    wp_nonce_field( 'mcp_save_flashcard_details_meta_box_data', 'mcp_flashcard_details_meta_box_nonce' );
    ?>
    <label for="mcp_question"><?php _e( 'Question:', 'med-course-plugin' ); ?></label>
    <textarea id="mcp_question" name="mcp_question" class="widefat" rows="5"><?php echo esc_textarea( $question ); ?></textarea>
    <label for="mcp_answer"><?php _e( 'Answer:', 'med-course-plugin' ); ?></label>
    <textarea id="mcp_answer" name="mcp_answer" class="widefat" rows="5"><?php echo esc_textarea( $answer ); ?></textarea>
    <?php
}

/**
 * Meta box display callback for test details.
 */
function mcp_test_details_meta_box_html( $post ) {
    $identifier = get_post_meta( $post->ID, '_mcp_identifier', true );
    $question = get_post_meta( $post->ID, '_mcp_question', true );
    $option1 = get_post_meta( $post->ID, '_mcp_option1', true );
    $option2 = get_post_meta( $post->ID, '_mcp_option2', true );
    $option3 = get_post_meta( $post->ID, '_mcp_option3', true );
    $option4 = get_post_meta( $post->ID, '_mcp_option4', true );
    $correct_option = get_post_meta( $post->ID, '_mcp_correct_option', true );
    $explanation = get_post_meta( $post->ID, '_mcp_explanation', true );
    wp_nonce_field( 'mcp_save_test_details_meta_box_data', 'mcp_test_details_meta_box_nonce' );
    ?>
    <label for="mcp_identifier"><?php _e( 'Identifier:', 'med-course-plugin' ); ?></label>
    <input type="text" id="mcp_identifier" name="mcp_identifier" value="<?php echo esc_attr( $identifier ); ?>" class="widefat">

    <label for="mcp_question"><?php _e( 'Question:', 'med-course-plugin' ); ?></label>
    <?php wp_editor( $question, 'mcp_question', [ 'textarea_name' => 'mcp_question' ] ); ?>

    <label for="mcp_option1"><?php _e( 'Option 1:', 'med-course-plugin' ); ?></label>
    <input type="text" id="mcp_option1" name="mcp_option1" value="<?php echo esc_attr( $option1 ); ?>" class="widefat">

    <label for="mcp_option2"><?php _e( 'Option 2:', 'med-course-plugin' ); ?></label>
    <input type="text" id="mcp_option2" name="mcp_option2" value="<?php echo esc_attr( $option2 ); ?>" class="widefat">

    <label for="mcp_option3"><?php _e( 'Option 3:', 'med-course-plugin' ); ?></label>
    <input type="text" id="mcp_option3" name="mcp_option3" value="<?php echo esc_attr( $option3 ); ?>" class="widefat">

    <label for="mcp_option4"><?php _e( 'Option 4:', 'med-course-plugin' ); ?></label>
    <input type="text" id="mcp_option4" name="mcp_option4" value="<?php echo esc_attr( $option4 ); ?>" class="widefat">

    <label for="mcp_correct_option"><?php _e( 'Correct Option:', 'med-course-plugin' ); ?></label>
    <select id="mcp_correct_option" name="mcp_correct_option">
        <option value="1" <?php selected( $correct_option, 1 ); ?>><?php _e( 'Option 1', 'med-course-plugin' ); ?></option>
        <option value="2" <?php selected( $correct_option, 2 ); ?>><?php _e( 'Option 2', 'med-course-plugin' ); ?></option>
        <option value="3" <?php selected( $correct_option, 3 ); ?>><?php _e( 'Option 3', 'med-course-plugin' ); ?></option>
        <option value="4" <?php selected( $correct_option, 4 ); ?>><?php _e( 'Option 4', 'med-course-plugin' ); ?></option>
    </select>

    <label for="mcp_explanation"><?php _e( 'Explanation:', 'med-course-plugin' ); ?></label>
    <?php wp_editor( $explanation, 'mcp_explanation', [ 'textarea_name' => 'mcp_explanation' ] ); ?>
    <?php
}

/**
 * Meta box display callback for test parent.
 */
function mcp_test_parent_meta_box_html( $post ) {
    $parent_id = get_post_meta( $post->ID, '_mcp_topic_id', true );
    $topics = get_posts( [ 'post_type' => 'topic', 'numberposts' => -1 ] );
    wp_nonce_field( 'mcp_save_test_parent_meta_box_data', 'mcp_test_parent_meta_box_nonce' );
    ?>
    <label for="mcp_topic_id"><?php _e( 'Select Topic:', 'med-course-plugin' ); ?></label>
    <select id="mcp_topic_id" name="mcp_topic_id" class="widefat">
        <option value=""><?php _e( '— Select —', 'med-course-plugin' ); ?></option>
        <?php foreach ( $topics as $topic ) : ?>
            <option value="<?php echo esc_attr( $topic->ID ); ?>" <?php selected( $parent_id, $topic->ID ); ?>>
                <?php echo esc_html( $topic->post_title ); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php
}

/**
 * Meta box display callback for topic sections.
 */
function mcp_topic_sections_meta_box_html( $post ) {
    $sections = get_post_meta( $post->ID, '_mcp_sections', true );

    $all_section_types = get_posts( [
        'post_type' => 'section_type',
        'numberposts' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
    ] );

    $starred = [];
    $not_starred = [];
    foreach ($all_section_types as $st) {
        if (get_post_meta($st->ID, '_mcp_is_starred', true) == '1') {
            $starred[] = $st;
        } else {
            $not_starred[] = $st;
        }
    }

    $icons = [
        'fa-user-doctor', 'fa-stethoscope', 'fa-pills', 'fa-notes-medical', 'fa-kit-medical', 'fa-hospital', 'fa-heart-pulse', 'fa-file-medical', 'fa-dna', 'fa-capsules', 'fa-brain', 'fa-book-medical', 'fa-band-aid', 'fa-syringe', 'fa-virus', 'fa-lungs', 'fa-microscope', 'fa-prescription-bottle', 'fa-crutch', 'fa-weight-scale',
        'fa-book', 'fa-book-open-reader', 'fa-graduation-cap', 'fa-chalkboard-user', 'fa-school', 'fa-pencil', 'fa-highlighter', 'fa-award', 'fa-atom', 'fa-flask', 'fa-lightbulb', 'fa-question', 'fa-certificate', 'fa-file-lines', 'fa-list-check', 'fa-bullseye', 'fa-clipboard-question', 'fa-magnifying-glass', 'fa-globe', 'fa-calculator',
        'fa-star', 'fa-bookmark', 'fa-flag', 'fa-check', 'fa-circle-info', 'fa-hourglass-half', 'fa-key', 'fa-sitemap', 'fa-tasks', 'fa-lightbulb'
    ];

    wp_nonce_field( 'mcp_save_sections_meta_box_data', 'mcp_sections_meta_box_nonce' );
    ?>
    <div id="mcp-sections-repeater">
        <div class="mcp-section-template" style="display: none;">
            <div class="mcp-section">
                <label><?php _e( 'Section Type:', 'med-course-plugin' ); ?></label>
                <div class="mcp-section-type-wrapper">
                    <select class="mcp-section-type-select" name="mcp_sections[__INDEX__][section_type]" style="width: 80%;">
                        <option value=""><?php _e( '— Select —', 'med-course-plugin' ); ?></option>
                        <?php if ( ! empty( $starred ) ) : ?>
                            <optgroup label="<?php esc_attr_e( 'Starred', 'med-course-plugin' ); ?>">
                                <?php foreach ( $starred as $section_type ) : ?>
                                    <option value="<?php echo esc_attr( $section_type->ID ); ?>"><?php echo esc_html( $section_type->post_title ); ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endif; ?>
                        <optgroup label="<?php esc_attr_e( 'Other', 'med-course-plugin' ); ?>">
                            <?php foreach ( $not_starred as $section_type ) : ?>
                                <option class="non-starred-option" value="<?php echo esc_attr( $section_type->ID ); ?>"><?php echo esc_html( $section_type->post_title ); ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    </select>
                    <button type="button" class="button mcp-add-new-section-type"><?php _e( 'Add New', 'med-course-plugin' ); ?></button>
                    <div class="mcp-inline-form-wrapper" style="display: none;">
                        <h4><?php _e( 'Create New Section Type', 'med-course-plugin' ); ?></h4>
                        <input type="text" class="mcp-new-section-type-title" placeholder="<?php esc_attr_e( 'Title', 'med-course-plugin' ); ?>">
                        <input type="text" class="mcp-new-section-type-icon" placeholder="<?php esc_attr_e( 'Icon Class (e.g., fa-book)', 'med-course-plugin' ); ?>">
                        <label><input type="checkbox" class="mcp-new-section-type-starred"> <?php _e( 'Starred', 'med-course-plugin' ); ?></label>
                        <button type="button" class="button button-primary mcp-save-new-section-type"><?php _e( 'Save', 'med-course-plugin' ); ?></button>
                        <button type="button" class="button mcp-cancel-new-section-type"><?php _e( 'Cancel', 'med-course-plugin' ); ?></button>
                    </div>
                </div>
                <label><?php _e( 'Content:', 'med-course-plugin' ); ?></label>
                <textarea id="mcp_sections___INDEX___content" name="mcp_sections[__INDEX__][content]" class="mcp-editor-area" style="width: 100%;" rows="8"></textarea>
                <button type="button" class="button mcp-remove-section"><?php _e( 'Remove Section', 'med-course-plugin' ); ?></button>
            </div>
        </div>

        <div class="mcp-sections-container">
            <?php if ( ! empty( $sections ) ) : ?>
                <?php foreach ( $sections as $index => $section ) : ?>
                    <div class="mcp-section">
                        <label><?php _e( 'Section Type:', 'med-course-plugin' ); ?></label>
                        <div class="mcp-section-type-wrapper">
                            <select class="mcp-section-type-select" name="mcp_sections[<?php echo $index; ?>][section_type]" style="width: 80%;">
                                <option value=""><?php _e( '— Select —', 'med-course-plugin' ); ?></option>
                                <?php if ( ! empty( $starred ) ) : ?>
                                    <optgroup label="<?php esc_attr_e( 'Starred', 'med-course-plugin' ); ?>">
                                        <?php foreach ( $starred as $section_type ) : ?>
                                            <option value="<?php echo esc_attr( $section_type->ID ); ?>" <?php selected( $section['section_type'], $section_type->ID ); ?>><?php echo esc_html( $section_type->post_title ); ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endif; ?>
                                <optgroup label="<?php esc_attr_e( 'Other', 'med-course-plugin' ); ?>">
                                    <?php foreach ( $not_starred as $section_type ) : ?>
                                        <option class="non-starred-option" value="<?php echo esc_attr( $section_type->ID ); ?>" <?php selected( $section['section_type'], $section_type->ID ); ?>><?php echo esc_html( $section_type->post_title ); ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            </select>
                            <button type="button" class="button mcp-add-new-section-type"><?php _e( 'Add New', 'med-course-plugin' ); ?></button>
                            <div class="mcp-inline-form-wrapper" style="display: none;">
                                <h4><?php _e( 'Create New Section Type', 'med-course-plugin' ); ?></h4>
                                <input type="text" class="mcp-new-section-type-title" placeholder="<?php esc_attr_e( 'Title', 'med-course-plugin' ); ?>">
                                <input type="text" class="mcp-new-section-type-icon" placeholder="<?php esc_attr_e( 'Icon Class (e.g., fa-book)', 'med-course-plugin' ); ?>">
                                <label><input type="checkbox" class="mcp-new-section-type-starred"> <?php _e( 'Starred', 'med-course-plugin' ); ?></label>
                                <button type="button" class="button button-primary mcp-save-new-section-type"><?php _e( 'Save', 'med-course-plugin' ); ?></button>
                                <button type="button" class="button mcp-cancel-new-section-type"><?php _e( 'Cancel', 'med-course-plugin' ); ?></button>
                            </div>
                        </div>
                        <label><?php _e( 'Content:', 'med-course-plugin' ); ?></label>
                        <?php wp_editor( $section['content'], 'mcp_sections_' . $index . '_content', [ 'textarea_name' => 'mcp_sections[' . $index . '][content]' ] ); ?>
                        <button type="button" class="button mcp-remove-section"><?php _e( 'Remove Section', 'med-course-plugin' ); ?></button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <button type="button" id="mcp-add-section" class="button button-primary"><?php _e( 'Add Section', 'med-course-plugin' ); ?></button>
    </div>
    <?php
}

/**
 * Meta box display callback for section type icon.
 */
function mcp_section_type_icon_meta_box_html( $post ) {
    $icon_class = get_post_meta( $post->ID, '_mcp_icon_class', true );
    $is_starred = get_post_meta( $post->ID, '_mcp_is_starred', true );
    wp_nonce_field( 'mcp_save_section_type_icon_meta_box_data', 'mcp_section_type_icon_meta_box_nonce' );

    $icons = [
        'fa-user-doctor', 'fa-stethoscope', 'fa-pills', 'fa-notes-medical', 'fa-kit-medical', 'fa-hospital', 'fa-heart-pulse', 'fa-file-medical', 'fa-dna', 'fa-capsules', 'fa-brain', 'fa-book-medical', 'fa-band-aid', 'fa-syringe', 'fa-virus', 'fa-lungs', 'fa-microscope', 'fa-prescription-bottle', 'fa-crutch', 'fa-weight-scale',
        'fa-book', 'fa-book-open-reader', 'fa-graduation-cap', 'fa-chalkboard-user', 'fa-school', 'fa-pencil', 'fa-highlighter', 'fa-award', 'fa-atom', 'fa-flask', 'fa-lightbulb', 'fa-question', 'fa-certificate', 'fa-file-lines', 'fa-list-check', 'fa-bullseye', 'fa-clipboard-question', 'fa-magnifying-glass', 'fa-globe', 'fa-calculator',
        'fa-star', 'fa-bookmark', 'fa-flag', 'fa-check', 'fa-circle-info', 'fa-hourglass-half', 'fa-key', 'fa-sitemap', 'fa-tasks', 'fa-lightbulb'
    ];
    ?>
    <p>
        <input type="checkbox" id="mcp_is_starred" name="mcp_is_starred" value="1" <?php checked( $is_starred, '1' ); ?>>
        <label for="mcp_is_starred"><?php _e( 'Starred (display on top)', 'med-course-plugin' ); ?></label>
    </p>
    <hr>
    <label for="mcp_icon_class"><?php _e( 'Font Awesome Icon Class:', 'med-course-plugin' ); ?></label>
    <input type="text" id="mcp_icon_class" name="mcp_icon_class" value="<?php echo esc_attr( $icon_class ); ?>" class="widefat">
    <p class="howto"><?php _e( 'Example: fa-book-medical', 'med-course-plugin' ); ?></p>

    <div class="mcp-icon-picker-wrapper">
        <div class="mcp-icon-picker">
            <?php foreach ( $icons as $icon ) : ?>
                <i class="fas <?php echo esc_attr( $icon ); ?>" data-icon="<?php echo esc_attr( $icon ); ?>"></i>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

/**
 * Meta box display callback for topic flashcards.
 */
function mcp_topic_flashcards_meta_box_html( $post ) {
    $flashcards = get_posts([
        'post_type' => 'flashcard',
        'meta_key' => '_mcp_topic_id',
        'meta_value' => $post->ID,
        'numberposts' => -1,
        'orderby' => 'date',
        'order' => 'ASC'
    ]);
    wp_nonce_field( 'mcp_save_topic_flashcards_meta_box_data', 'mcp_topic_flashcards_meta_box_nonce' );
    ?>
    <div id="mcp-flashcards-repeater">
        <div class="mcp-flashcard-template" style="display: none;">
            <div class="mcp-flashcard">
                <label><?php _e( 'Question:', 'med-course-plugin' ); ?></label>
                <textarea name="mcp_flashcards[__INDEX__][question]" class="widefat" rows="3"></textarea>
                <label><?php _e( 'Answer:', 'med-course-plugin' ); ?></label>
                <textarea name="mcp_flashcards[__INDEX__][answer]" class="widefat" rows="3"></textarea>
                <input type="hidden" name="mcp_flashcards[__INDEX__][id]" value="">
                <button type="button" class="button mcp-remove-flashcard"><?php _e( 'Remove Flashcard', 'med-course-plugin' ); ?></button>
            </div>
        </div>

        <div class="mcp-flashcards-container">
            <?php if ( ! empty( $flashcards ) ) : ?>
                <?php foreach ( $flashcards as $index => $flashcard ) : ?>
                    <div class="mcp-flashcard">
                        <label><?php _e( 'Question:', 'med-course-plugin' ); ?></label>
                        <textarea name="mcp_flashcards[<?php echo $index; ?>][question]" class="widefat" rows="3"><?php echo esc_textarea( get_post_meta( $flashcard->ID, '_mcp_question', true ) ); ?></textarea>
                        <label><?php _e( 'Answer:', 'med-course-plugin' ); ?></label>
                        <textarea name="mcp_flashcards[<?php echo $index; ?>][answer]" class="widefat" rows="3"><?php echo esc_textarea( get_post_meta( $flashcard->ID, '_mcp_answer', true ) ); ?></textarea>
                        <input type="hidden" name="mcp_flashcards[<?php echo $index; ?>][id]" value="<?php echo esc_attr( $flashcard->ID ); ?>">
                        <button type="button" class="button mcp-remove-flashcard"><?php _e( 'Remove Flashcard', 'med-course-plugin' ); ?></button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <button type="button" id="mcp-add-flashcard" class="button button-primary"><?php _e( 'Add Flashcard', 'med-course-plugin' ); ?></button>
    </div>
    <?php
}

/**
 * Meta box display callback for topic tests.
 */
/**
 * Meta box display callback for course lessons order.
 */
function mcp_course_lessons_order_meta_box_html( $post ) {
    $lessons = get_posts([
        'post_type' => 'lesson',
        'meta_key' => '_mcp_course_id',
        'meta_value' => $post->ID,
        'numberposts' => -1,
        'orderby' => 'menu_order',
        'order' => 'ASC'
    ]);

    echo '<p>' . __( 'Drag and drop to reorder lessons:', 'med-course-plugin' ) . '</p>';
    echo '<ul id="mcp-sortable-lessons" class="mcp-sortable-list">';
    if ( ! empty( $lessons ) ) {
        foreach ( $lessons as $lesson ) {
            echo '<li class="ui-state-default" data-id="' . esc_attr( $lesson->ID ) . '"><span class="dashicons dashicons-move"></span> ' . esc_html( $lesson->post_title ) . '</li>';
        }
    } else {
        echo '<li>' . __( 'No lessons found for this course.', 'med-course-plugin' ) . '</li>';
    }
    echo '</ul>';
    wp_nonce_field( 'mcp_update_order_nonce', 'mcp_order_nonce' );
}

/**
 * Meta box display callback for lesson topics order.
 */
function mcp_lesson_topics_order_meta_box_html( $post ) {
    $topics = get_posts([
        'post_type' => 'topic',
        'meta_key' => '_mcp_lesson_id',
        'meta_value' => $post->ID,
        'numberposts' => -1,
        'orderby' => 'menu_order',
        'order' => 'ASC'
    ]);

    echo '<p>' . __( 'Drag and drop to reorder topics:', 'med-course-plugin' ) . '</p>';
    echo '<ul id="mcp-sortable-topics" class="mcp-sortable-list">';
    if ( ! empty( $topics ) ) {
        foreach ( $topics as $topic ) {
            echo '<li class="ui-state-default" data-id="' . esc_attr( $topic->ID ) . '"><span class="dashicons dashicons-move"></span> ' . esc_html( $topic->post_title ) . '</li>';
        }
    } else {
        echo '<li>' . __( 'No topics found for this lesson.', 'med-course-plugin' ) . '</li>';
    }
    echo '</ul>';
    wp_nonce_field( 'mcp_update_order_nonce', 'mcp_order_nonce' );
}

function mcp_topic_tests_meta_box_html( $post ) {
    $tests = get_posts([
        'post_type' => 'test',
        'meta_key' => '_mcp_topic_id',
        'meta_value' => $post->ID,
        'numberposts' => -1,
        'orderby' => 'date',
        'order' => 'ASC'
    ]);
    wp_nonce_field( 'mcp_save_topic_tests_meta_box_data', 'mcp_topic_tests_meta_box_nonce' );
    ?>
    <div id="mcp-tests-repeater">
        <div class="mcp-test-template" style="display: none;">
            <div class="mcp-test">
                <label><?php _e( 'Identifier:', 'med-course-plugin' ); ?></label>
                <input type="text" name="mcp_tests[__INDEX__][identifier]" class="widefat">
                <label><?php _e( 'Question:', 'med-course-plugin' ); ?></label>
                <textarea id="mcp_tests___INDEX___question" name="mcp_tests[__INDEX__][question]" class="mcp-test-editor-area" style="width: 100%;" rows="5"></textarea>
                <label><?php _e( 'Option 1:', 'med-course-plugin' ); ?></label>
                <input type="text" name="mcp_tests[__INDEX__][option1]" class="widefat">
                <label><?php _e( 'Option 2:', 'med-course-plugin' ); ?></label>
                <input type="text" name="mcp_tests[__INDEX__][option2]" class="widefat">
                <label><?php _e( 'Option 3:', 'med-course-plugin' ); ?></label>
                <input type="text" name="mcp_tests[__INDEX__][option3]" class="widefat">
                <label><?php _e( 'Option 4:', 'med-course-plugin' ); ?></label>
                <input type="text" name="mcp_tests[__INDEX__][option4]" class="widefat">
                <label><?php _e( 'Correct Option:', 'med-course-plugin' ); ?></label>
                <select name="mcp_tests[__INDEX__][correct_option]">
                    <option value="1"><?php _e( 'Option 1', 'med-course-plugin' ); ?></option>
                    <option value="2"><?php _e( 'Option 2', 'med-course-plugin' ); ?></option>
                    <option value="3"><?php _e( 'Option 3', 'med-course-plugin' ); ?></option>
                    <option value="4"><?php _e( 'Option 4', 'med-course-plugin' ); ?></option>
                </select>
                <label><?php _e( 'Explanation:', 'med-course-plugin' ); ?></label>
                <textarea id="mcp_tests___INDEX___explanation" name="mcp_tests[__INDEX__][explanation]" class="mcp-test-editor-area" style="width: 100%;" rows="5"></textarea>
                <input type="hidden" name="mcp_tests[__INDEX__][id]" value="">
                <button type="button" class="button mcp-remove-test"><?php _e( 'Remove Test', 'med-course-plugin' ); ?></button>
            </div>
        </div>

        <div class="mcp-tests-container">
            <?php if ( ! empty( $tests ) ) : ?>
                <?php foreach ( $tests as $index => $test ) : ?>
                    <div class="mcp-test">
                        <label><?php _e( 'Identifier:', 'med-course-plugin' ); ?></label>
                        <input type="text" name="mcp_tests[<?php echo $index; ?>][identifier]" class="widefat" value="<?php echo esc_attr( get_post_meta( $test->ID, '_mcp_identifier', true ) ); ?>">
                        <label><?php _e( 'Question:', 'med-course-plugin' ); ?></label>
                        <?php wp_editor( get_post_meta( $test->ID, '_mcp_question', true ), 'mcp_tests_' . $index . '_question', [ 'textarea_name' => 'mcp_tests[' . $index . '][question]' ] ); ?>
                        <label><?php _e( 'Option 1:', 'med-course-plugin' ); ?></label>
                        <input type="text" name="mcp_tests[<?php echo $index; ?>][option1]" class="widefat" value="<?php echo esc_attr( get_post_meta( $test->ID, '_mcp_option1', true ) ); ?>">
                        <label><?php _e( 'Option 2:', 'med-course-plugin' ); ?></label>
                        <input type="text" name="mcp_tests[<?php echo $index; ?>][option2]" class="widefat" value="<?php echo esc_attr( get_post_meta( $test->ID, '_mcp_option2', true ) ); ?>">
                        <label><?php _e( 'Option 3:', 'med-course-plugin' ); ?></label>
                        <input type="text" name="mcp_tests[<?php echo $index; ?>][option3]" class="widefat" value="<?php echo esc_attr( get_post_meta( $test->ID, '_mcp_option3', true ) ); ?>">
                        <label><?php _e( 'Option 4:', 'med-course-plugin' ); ?></label>
                        <input type="text" name="mcp_tests[<?php echo $index; ?>][option4]" class="widefat" value="<?php echo esc_attr( get_post_meta( $test->ID, '_mcp_option4', true ) ); ?>">
                        <label><?php _e( 'Correct Option:', 'med-course-plugin' ); ?></label>
                        <select name="mcp_tests[<?php echo $index; ?>][correct_option]">
                            <option value="1" <?php selected( get_post_meta( $test->ID, '_mcp_correct_option', true ), 1 ); ?>><?php _e( 'Option 1', 'med-course-plugin' ); ?></option>
                            <option value="2" <?php selected( get_post_meta( $test->ID, '_mcp_correct_option', true ), 2 ); ?>><?php _e( 'Option 2', 'med-course-plugin' ); ?></option>
                            <option value="3" <?php selected( get_post_meta( $test->ID, '_mcp_correct_option', true ), 3 ); ?>><?php _e( 'Option 3', 'med-course-plugin' ); ?></option>
                            <option value="4" <?php selected( get_post_meta( $test->ID, '_mcp_correct_option', true ), 4 ); ?>><?php _e( 'Option 4', 'med-course-plugin' ); ?></option>
                        </select>
                        <label><?php _e( 'Explanation:', 'med-course-plugin' ); ?></label>
                        <?php wp_editor( get_post_meta( $test->ID, '_mcp_explanation', true ), 'mcp_tests_' . $index . '_explanation', [ 'textarea_name' => 'mcp_tests[' . $index . '][explanation]' ] ); ?>
                        <input type="hidden" name="mcp_tests[<?php echo $index; ?>][id]" value="<?php echo esc_attr( $test->ID ); ?>">
                        <button type="button" class="button mcp-remove-test"><?php _e( 'Remove Test', 'med-course-plugin' ); ?></button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <button type="button" id="mcp-add-test" class="button button-primary"><?php _e( 'Add Test', 'med-course-plugin' ); ?></button>
    </div>
    <?php
}

/**
 * Save meta box data.
 */
function mcp_save_meta_box_data( $post_id ) {
    // Check if this is an autosave.
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // Check the user's permissions.
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    // Check if this is a revision.
    if ( wp_is_post_revision( $post_id ) ) {
        return;
    }

    // Stop post from being published if parent is not selected
    $post_status = get_post_status( $post_id );
    $post_type = get_post_type( $post_id );

    if ( $post_status === 'publish' ) {
        $parent_missing = false;
        $error_message = '';

        if ( $post_type === 'lesson' && isset( $_POST['mcp_course_id'] ) && empty( $_POST['mcp_course_id'] ) ) {
            $parent_missing = true;
            $error_message = 'Please select a Course before publishing.';
        } elseif ( $post_type === 'topic' && isset( $_POST['mcp_lesson_id'] ) && empty( $_POST['mcp_lesson_id'] ) ) {
            $parent_missing = true;
            $error_message = 'Please select a Lesson before publishing.';
        } elseif ( ( $post_type === 'flashcard' || $post_type === 'test' ) && isset( $_POST['mcp_topic_id'] ) && empty( $_POST['mcp_topic_id'] ) ) {
            $parent_missing = true;
            $error_message = 'Please select a Topic before publishing.';
        }

        if ( $parent_missing ) {
            // Unhook this function to prevent infinite loop
            remove_action( 'save_post', 'mcp_save_meta_box_data' );

            // Change status to draft
            wp_update_post( ['ID' => $post_id, 'post_status' => 'draft'] );

            // Re-hook this function
            add_action( 'save_post', 'mcp_save_meta_box_data' );

            // Set a transient to show the admin notice
            set_transient( 'mcp-admin-notice', $error_message, 5 );

            return;
        }
    }


    if ( isset( $_POST['mcp_section_type_icon_meta_box_nonce'] ) && wp_verify_nonce( $_POST['mcp_section_type_icon_meta_box_nonce'], 'mcp_save_section_type_icon_meta_box_data' ) ) {
        if ( array_key_exists( 'mcp_icon_class', $_POST ) ) {
            update_post_meta( $post_id, '_mcp_icon_class', sanitize_text_field( $_POST['mcp_icon_class'] ) );
        }
        if ( isset( $_POST['mcp_is_starred'] ) ) {
            update_post_meta( $post_id, '_mcp_is_starred', '1' );
        } else {
            update_post_meta( $post_id, '_mcp_is_starred', '0' );
        }
    }

    if ( isset( $_POST['mcp_english_slug_meta_box_nonce'] ) && wp_verify_nonce( $_POST['mcp_english_slug_meta_box_nonce'], 'mcp_save_english_slug_meta_box_data' ) ) {
        if ( array_key_exists( 'mcp_english_slug', $_POST ) ) {
            $slug = sanitize_title( $_POST['mcp_english_slug'] );
            update_post_meta( $post_id, '_mcp_english_slug', $slug );

            // Sync with the native WordPress slug
            if ( ! wp_is_post_revision( $post_id ) ) {
                remove_action( 'save_post', 'mcp_save_meta_box_data' );
                wp_update_post( [ 'ID' => $post_id, 'post_name' => $slug ] );
                add_action( 'save_post', 'mcp_save_meta_box_data' );
            }
        }
    }

    if ( isset( $_POST['mcp_lesson_parent_meta_box_nonce'] ) && wp_verify_nonce( $_POST['mcp_lesson_parent_meta_box_nonce'], 'mcp_save_lesson_parent_meta_box_data' ) ) {
        if ( array_key_exists( 'mcp_course_id', $_POST ) ) {
            update_post_meta( $post_id, '_mcp_course_id', absint( $_POST['mcp_course_id'] ) );
        }
    }

    if ( isset( $_POST['mcp_topic_course_meta_box_nonce'] ) && wp_verify_nonce( $_POST['mcp_topic_course_meta_box_nonce'], 'mcp_save_topic_course_meta_box_data' ) ) {
        if ( array_key_exists( 'mcp_topic_course_id', $_POST ) ) {
            update_post_meta( $post_id, '_mcp_course_id', absint( $_POST['mcp_topic_course_id'] ) );
        }
    }

    if ( isset( $_POST['mcp_topic_parent_meta_box_nonce'] ) && wp_verify_nonce( $_POST['mcp_topic_parent_meta_box_nonce'], 'mcp_save_topic_parent_meta_box_data' ) ) {
        if ( array_key_exists( 'mcp_lesson_id', $_POST ) ) {
            update_post_meta( $post_id, '_mcp_lesson_id', absint( $_POST['mcp_lesson_id'] ) );
        }
    }

    if ( isset( $_POST['mcp_flashcard_parent_meta_box_nonce'] ) && wp_verify_nonce( $_POST['mcp_flashcard_parent_meta_box_nonce'], 'mcp_save_flashcard_parent_meta_box_data' ) ) {
        if ( array_key_exists( 'mcp_topic_id', $_POST ) ) {
            update_post_meta( $post_id, '_mcp_topic_id', absint( $_POST['mcp_topic_id'] ) );
        }
    }

    if ( isset( $_POST['mcp_test_parent_meta_box_nonce'] ) && wp_verify_nonce( $_POST['mcp_test_parent_meta_box_nonce'], 'mcp_save_test_parent_meta_box_data' ) ) {
        if ( array_key_exists( 'mcp_topic_id', $_POST ) ) {
            update_post_meta( $post_id, '_mcp_topic_id', absint( $_POST['mcp_topic_id'] ) );
        }
    }

    if ( isset( $_POST['mcp_sections_meta_box_nonce'] ) && wp_verify_nonce( $_POST['mcp_sections_meta_box_nonce'], 'mcp_save_sections_meta_box_data' ) ) {
        if ( array_key_exists( 'mcp_sections', $_POST ) ) {
            $sections = [];
            foreach ( $_POST['mcp_sections'] as $section ) {
                if ( ! empty( $section['section_type'] ) ) {
                    $sections[] = [
                        'section_type' => absint( $section['section_type'] ),
                        'content' => wp_kses_post( $section['content'] ),
                    ];
                }
            }
            update_post_meta( $post_id, '_mcp_sections', $sections );
        }
    }

    if ( isset( $_POST['mcp_flashcard_details_meta_box_nonce'] ) && wp_verify_nonce( $_POST['mcp_flashcard_details_meta_box_nonce'], 'mcp_save_flashcard_details_meta_box_data' ) ) {
        if ( array_key_exists( 'mcp_question', $_POST ) ) {
            update_post_meta( $post_id, '_mcp_question', sanitize_textarea_field( $_POST['mcp_question'] ) );
        }
        if ( array_key_exists( 'mcp_answer', $_POST ) ) {
            update_post_meta( $post_id, '_mcp_answer', sanitize_textarea_field( $_POST['mcp_answer'] ) );
        }
    }

    if ( isset( $_POST['mcp_test_details_meta_box_nonce'] ) && wp_verify_nonce( $_POST['mcp_test_details_meta_box_nonce'], 'mcp_save_test_details_meta_box_data' ) ) {
        if ( array_key_exists( 'mcp_identifier', $_POST ) ) {
            update_post_meta( $post_id, '_mcp_identifier', sanitize_text_field( $_POST['mcp_identifier'] ) );
        }
        if ( array_key_exists( 'mcp_question', $_POST ) ) {
            update_post_meta( $post_id, '_mcp_question', wp_kses_post( $_POST['mcp_question'] ) );
        }
        if ( array_key_exists( 'mcp_option1', $_POST ) ) {
            update_post_meta( $post_id, '_mcp_option1', sanitize_text_field( $_POST['mcp_option1'] ) );
        }
        if ( array_key_exists( 'mcp_option2', $_POST ) ) {
            update_post_meta( $post_id, '_mcp_option2', sanitize_text_field( $_POST['mcp_option2'] ) );
        }
        if ( array_key_exists( 'mcp_option3', $_POST ) ) {
            update_post_meta( $post_id, '_mcp_option3', sanitize_text_field( $_POST['mcp_option3'] ) );
        }
        if ( array_key_exists( 'mcp_option4', $_POST ) ) {
            update_post_meta( $post_id, '_mcp_option4', sanitize_text_field( $_POST['mcp_option4'] ) );
        }
        if ( array_key_exists( 'mcp_correct_option', $_POST ) ) {
            update_post_meta( $post_id, '_mcp_correct_option', absint( $_POST['mcp_correct_option'] ) );
        }
        if ( array_key_exists( 'mcp_explanation', $_POST ) ) {
            update_post_meta( $post_id, '_mcp_explanation', wp_kses_post( $_POST['mcp_explanation'] ) );
        }
    }

    if ( isset( $_POST['mcp_topic_flashcards_meta_box_nonce'] ) && wp_verify_nonce( $_POST['mcp_topic_flashcards_meta_box_nonce'], 'mcp_save_topic_flashcards_meta_box_data' ) ) {
        if ( array_key_exists( 'mcp_flashcards', $_POST ) ) {
            remove_action( 'save_post', 'mcp_save_meta_box_data' );

            $existing_ids = get_posts([
                'post_type' => 'flashcard',
                'meta_key' => '_mcp_topic_id',
                'meta_value' => $post_id,
                'fields' => 'ids'
            ]);
            $submitted_ids = [];

            foreach ( $_POST['mcp_flashcards'] as $flashcard_data ) {
                $flashcard_id = ! empty( $flashcard_data['id'] ) ? absint( $flashcard_data['id'] ) : 0;
                $question = sanitize_textarea_field( $flashcard_data['question'] );
                $answer = sanitize_textarea_field( $flashcard_data['answer'] );

                if ( empty( $question ) && empty( $answer ) ) {
                    if ( $flashcard_id ) {
                        wp_delete_post( $flashcard_id, true );
                    }
                    continue;
                }

                if ( $flashcard_id ) {
                    $post_data = [
                        'ID' => $flashcard_id,
                        'post_title' => wp_trim_words( $question, 10, '...' ),
                    ];
                    wp_update_post( $post_data );
                    update_post_meta( $flashcard_id, '_mcp_question', $question );
                    update_post_meta( $flashcard_id, '_mcp_answer', $answer );
                    $submitted_ids[] = $flashcard_id;
                } else {
                    $post_data = [
                        'post_title' => wp_trim_words( $question, 10, '...' ),
                        'post_type' => 'flashcard',
                        'post_status' => 'publish'
                    ];
                    $new_flashcard_id = wp_insert_post( $post_data );
                    update_post_meta( $new_flashcard_id, '_mcp_topic_id', $post_id );
                    update_post_meta( $new_flashcard_id, '_mcp_question', $question );
                    update_post_meta( $new_flashcard_id, '_mcp_answer', $answer );
                }
            }

            $deleted_ids = array_diff( $existing_ids, $submitted_ids );
            foreach ( $deleted_ids as $deleted_id ) {
                wp_delete_post( $deleted_id, true );
            }

            add_action( 'save_post', 'mcp_save_meta_box_data' );
        }
    }

    if ( isset( $_POST['mcp_topic_tests_meta_box_nonce'] ) && wp_verify_nonce( $_POST['mcp_topic_tests_meta_box_nonce'], 'mcp_save_topic_tests_meta_box_data' ) ) {
        if ( array_key_exists( 'mcp_tests', $_POST ) ) {
            remove_action( 'save_post', 'mcp_save_meta_box_data' );

            $existing_ids = get_posts([
                'post_type' => 'test',
                'meta_key' => '_mcp_topic_id',
                'meta_value' => $post_id,
                'fields' => 'ids'
            ]);
            $submitted_ids = [];

            foreach ( $_POST['mcp_tests'] as $test_data ) {
                $test_id = ! empty( $test_data['id'] ) ? absint( $test_data['id'] ) : 0;
                $question = sanitize_textarea_field( $test_data['question'] );

                if ( empty( $question ) ) {
                    if ( $test_id ) {
                        wp_delete_post( $test_id, true );
                    }
                    continue;
                }

                if ( $test_id ) {
                    $post_data = [
                        'ID' => $test_id,
                        'post_title' => wp_trim_words( $question, 10, '...' ),
                    ];
                    wp_update_post( $post_data );
                    update_post_meta( $test_id, '_mcp_identifier', sanitize_text_field( $test_data['identifier'] ) );
                    update_post_meta( $test_id, '_mcp_question', $question );
                    update_post_meta( $test_id, '_mcp_option1', sanitize_text_field( $test_data['option1'] ) );
                    update_post_meta( $test_id, '_mcp_option2', sanitize_text_field( $test_data['option2'] ) );
                    update_post_meta( $test_id, '_mcp_option3', sanitize_text_field( $test_data['option3'] ) );
                    update_post_meta( $test_id, '_mcp_option4', sanitize_text_field( $test_data['option4'] ) );
                    update_post_meta( $test_id, '_mcp_correct_option', absint( $test_data['correct_option'] ) );
                    update_post_meta( $test_id, '_mcp_explanation', sanitize_textarea_field( $test_data['explanation'] ) );
                    $submitted_ids[] = $test_id;
                } else {
                    $post_data = [
                        'post_title' => wp_trim_words( $question, 10, '...' ),
                        'post_type' => 'test',
                        'post_status' => 'publish'
                    ];
                    $new_test_id = wp_insert_post( $post_data );
                    update_post_meta( $new_test_id, '_mcp_topic_id', $post_id );
                    update_post_meta( $new_test_id, '_mcp_identifier', sanitize_text_field( $test_data['identifier'] ) );
                    update_post_meta( $new_test_id, '_mcp_question', $question );
                    update_post_meta( $new_test_id, '_mcp_option1', sanitize_text_field( $test_data['option1'] ) );
                    update_post_meta( $new_test_id, '_mcp_option2', sanitize_text_field( $test_data['option2'] ) );
                    update_post_meta( $new_test_id, '_mcp_option3', sanitize_text_field( $test_data['option3'] ) );
                    update_post_meta( $new_test_id, '_mcp_option4', sanitize_text_field( $test_data['option4'] ) );
                    update_post_meta( $new_test_id, '_mcp_correct_option', absint( $test_data['correct_option'] ) );
                    update_post_meta( $new_test_id, '_mcp_explanation', sanitize_textarea_field( $test_data['explanation'] ) );
                }
            }

            $deleted_ids = array_diff( $existing_ids, $submitted_ids );
            foreach ( $deleted_ids as $deleted_id ) {
                wp_delete_post( $deleted_id, true );
            }

            add_action( 'save_post', 'mcp_save_meta_box_data' );
        }
    }
}
add_action( 'save_post', 'mcp_save_meta_box_data' );
