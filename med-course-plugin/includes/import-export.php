<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Add the import/export pages to the admin menu.
 */
function mcp_add_import_export_menu() {
    add_submenu_page(
        'course_builder_page',
        __( 'CSV Import', 'med-course-plugin' ),
        __( 'ایمپورت CSV', 'med-course-plugin' ),
        'manage_options',
        'mcp-csv-import',
        'mcp_render_import_page'
    );

    add_submenu_page(
        'course_builder_page',
        __( 'CSV Export', 'med-course-plugin' ),
        __( 'اکسپورت CSV', 'med-course-plugin' ),
        'manage_options',
        'mcp-csv-export',
        'mcp_render_export_page'
    );
}
add_action( 'admin_menu', 'mcp_add_import_export_menu' );

/**
 * Render the import page.
 */
function mcp_render_import_page() {
    ?>
    <div class="wrap">
        <h1><?php _e( 'CSV Import', 'med-course-plugin' ); ?></h1>
        <?php
        if ( isset( $_GET['mcp_import_status'] ) ) {
            $status = sanitize_text_field( $_GET['mcp_import_status'] );
            $imported_count = isset( $_GET['mcp_imported_count'] ) ? absint( $_GET['mcp_imported_count'] ) : 0;
            $skipped_count = isset( $_GET['mcp_skipped_count'] ) ? absint( $_GET['mcp_skipped_count'] ) : 0;
            $errors = get_transient( 'mcp_import_errors' );
            delete_transient( 'mcp_import_errors' );

            if ( $status === 'success' ) {
                echo '<div class="notice notice-success is-dismissible"><p>' . sprintf( __( '%d rows imported successfully.', 'med-course-plugin' ), $imported_count ) . '</p></div>';
                if ( $skipped_count > 0 ) {
                    echo '<div class="notice notice-warning is-dismissible"><p>' . sprintf( __( '%d rows were skipped.', 'med-course-plugin' ), $skipped_count ) . '</p></div>';
                    if ( ! empty( $errors ) ) {
                        echo '<h4>' . __( 'Specific Errors:', 'med-course-plugin' ) . '</h4><ul style="list-style-type: disc; padding-left: 20px;">';
                        foreach ( $errors as $error ) {
                            echo '<li>' . esc_html( $error ) . '</li>';
                        }
                        echo '</ul>';
                    }
                }
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>' . __( 'An error occurred during import. Please check your file and try again.', 'med-course-plugin' ) . '</p></div>';
                 if ( ! empty( $errors ) ) {
                    echo '<h4>' . __( 'Specific Errors:', 'med-course-plugin' ) . '</h4><ul style="list-style-type: disc; padding-left: 20px;">';
                    foreach ( $errors as $error ) {
                        echo '<li>' . esc_html( $error ) . '</li>';
                    }
                    echo '</ul>';
                }
            }
        }
        ?>
        <p><?php _e( 'Use this tool to import tests, flashcards, and sections from a CSV file.', 'med-course-plugin' ); ?></p>

        <div id="mcp-import-export-container">
            <form id="mcp-import-form" method="post" enctype="multipart/form-data">
                <?php wp_nonce_field( 'mcp_import_export_action', 'mcp_import_export_nonce' ); ?>

                <h2><?php _e( '1. Selection', 'med-course-plugin' ); ?></h2>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="mcp-course"><?php _e( 'Course', 'med-course-plugin' ); ?></label></th>
                            <td>
                                <select id="mcp-course" name="mcp_course_id" class="widefat" required>
                                    <option value=""><?php _e( '— Select Course —', 'med-course-plugin' ); ?></option>
                                    <?php
                                    $courses = get_posts( [ 'post_type' => 'course', 'numberposts' => -1 ] );
                                    foreach ( $courses as $course ) {
                                        echo '<option value="' . esc_attr( $course->ID ) . '">' . esc_html( $course->post_title ) . '</option>';
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="mcp-lesson"><?php _e( 'Lesson', 'med-course-plugin' ); ?></label></th>
                            <td>
                                <select id="mcp-lesson" name="mcp_lesson_id" class="widefat" disabled required>
                                    <option value=""><?php _e( '— Select Lesson —', 'med-course-plugin' ); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="mcp-topic"><?php _e( 'Topic', 'med-course-plugin' ); ?></label></th>
                            <td>
                                <select id="mcp-topic" name="mcp_topic_id" class="widefat" disabled required>
                                    <option value=""><?php _e( '— Select Topic —', 'med-course-plugin' ); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="mcp-content-type"><?php _e( 'Content Type', 'med-course-plugin' ); ?></label></th>
                            <td>
                                <select id="mcp-content-type" name="mcp_content_type" class="widefat" required>
                                    <option value=""><?php _e( '— Select Content Type —', 'med-course-plugin' ); ?></option>
                                    <option value="tests"><?php _e( 'Tests', 'med-course-plugin' ); ?></option>
                                    <option value="flashcards"><?php _e( 'Flashcards', 'med-course-plugin' ); ?></option>
                                    <option value="sections"><?php _e( 'Sections', 'med-course-plugin' ); ?></option>
                                </select>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div id="mcp-import-container">
                    <h2><?php _e( '2. Import', 'med-course-plugin' ); ?></h2>
                    <table class="form-table">
                        <tbody>
                            <tr>
                                <th scope="row"><label for="mcp-csv-file"><?php _e( 'CSV File', 'med-course-plugin' ); ?></label></th>
                                <td><input type="file" id="mcp-csv-file" name="mcp_csv_file" accept=".csv" required></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="mcp-import-status"><?php _e( 'Status', 'med-course-plugin' ); ?></label></th>
                                <td>
                                    <select id="mcp-import-status" name="mcp_import_status">
                                        <option value="publish"><?php _e( 'Published', 'med-course-plugin' ); ?></option>
                                        <option value="draft"><?php _e( 'Draft', 'med-course-plugin' ); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr id="mcp-replace-sections-row">
                                <th scope="row"><?php _e( 'Import Option', 'med-course-plugin' ); ?></th>
                                <td>
                                    <label><input type="checkbox" name="mcp_replace_sections" value="1"> <?php _e( 'Replace existing sections', 'med-course-plugin' ); ?></label>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p><input type="submit" name="mcp_import_submit" class="button button-primary" value="<?php _e( 'Import from CSV', 'med-course-plugin' ); ?>"></p>
                </div>
            </form>
        </div>

        <div id="mcp-instructions-container">
            <h2><?php _e( 'CSV File Instructions', 'med-course-plugin' ); ?></h2>
            <p><?php _e( 'Please ensure your CSV file is saved with UTF-8 encoding to prevent issues with special characters.', 'med-course-plugin' ); ?></p>
            <div id="mcp-instructions-tests" class="mcp-instructions">
                <h3><?php _e( 'Tests CSV Format', 'med-course-plugin' ); ?></h3>
                <p><?php _e( 'For tests, your CSV file must have the following columns in this order:', 'med-course-plugin' ); ?></p>
                <ul>
                    <li><strong>identifier:</strong> <?php _e( 'A unique identifier for the test (e.g., test-001).', 'med-course-plugin' ); ?></li>
                    <li><strong>question:</strong> <?php _e( 'The test question. HTML is supported.', 'med-course-plugin' ); ?></li>
                    <li><strong>option1:</strong> <?php _e( 'The first answer option.', 'med-course-plugin' ); ?></li>
                    <li><strong>option2:</strong> <?php _e( 'The second answer option.', 'med-course-plugin' ); ?></li>
                    <li><strong>option3:</strong> <?php _e( 'The third answer option.', 'med-course-plugin' ); ?></li>
                    <li><strong>option4:</strong> <?php _e( 'The fourth answer option.', 'med-course-plugin' ); ?></li>
                    <li><strong>correct_option:</strong> <?php _e( 'The number of the correct option (e.g., 1 for option1, 2 for option2, etc.).', 'med-course-plugin' ); ?></li>
                    <li><strong>explanation:</strong> <?php _e( 'An explanation for the correct answer. HTML is supported.', 'med-course-plugin' ); ?></li>
                </ul>
                <h4><?php _e( 'Example:', 'med-course-plugin' ); ?></h4>
                <pre><code>identifier,question,option1,option2,option3,option4,correct_option,explanation\n"cardio-01","What is the main function of the heart?","Pumping blood","Filtering waste","Producing hormones","Digesting food",1,"The heart is the primary organ responsible for pumping blood throughout the circulatory system."</code></pre>
            </div>
            <div id="mcp-instructions-flashcards" class="mcp-instructions">
                <h3><?php _e( 'Flashcards CSV Format', 'med-course-plugin' ); ?></h3>
                <p><?php _e( 'For flashcards, your CSV file must have the following columns in this order:', 'med-course-plugin' ); ?></p>
                <ul>
                    <li><strong>question:</strong> <?php _e( 'The front side of the flashcard (the question).', 'med-course-plugin' ); ?></li>
                    <li><strong>answer:</strong> <?php _e( 'The back side of the flashcard (the answer).', 'med-course-plugin' ); ?></li>
                </ul>
                <h4><?php _e( 'Example:', 'med-course-plugin' ); ?></h4>
                <pre><code>question,answer\n"What does 'CPR' stand for?","Cardiopulmonary Resuscitation"</code></pre>
            </div>
            <div id="mcp-instructions-sections" class="mcp-instructions">
                <h3><?php _e( 'Sections CSV Format', 'med-course-plugin' ); ?></h3>
                <p><?php _e( 'For sections, your CSV file must have the following columns in this order:', 'med-course-plugin' ); ?></p>
                <ul>
                    <li><strong>section_type:</strong> <?php _e( 'The name of the section type. This must exactly match an existing Section Type name in your system.', 'med-course-plugin' ); ?></li>
                    <li><strong>content:</strong> <?php _e( 'The content for the section. HTML is supported.', 'med-course-plugin' ); ?></li>
                </ul>
                <h4><?php _e( 'Example:', 'med-course-plugin' ); ?></h4>
                <pre><code>section_type,content\n"Introduction","&lt;p&gt;This is an introductory paragraph about the topic.&lt;/p&gt;"</code></pre>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Render the export page.
 */
function mcp_render_export_page() {
    ?>
    <div class="wrap">
        <h1><?php _e( 'CSV Export', 'med-course-plugin' ); ?></h1>
        <p><?php _e( 'Use this tool to export tests, flashcards, and sections to a CSV file.', 'med-course-plugin' ); ?></p>

        <div id="mcp-import-export-container">
            <form id="mcp-export-form" method="post">
                <?php wp_nonce_field( 'mcp_import_export_action', 'mcp_import_export_nonce' ); ?>

                <h2><?php _e( '1. Selection', 'med-course-plugin' ); ?></h2>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="mcp-course"><?php _e( 'Course', 'med-course-plugin' ); ?></label></th>
                            <td>
                                <select id="mcp-course" name="mcp_course_id" class="widefat" required>
                                    <option value=""><?php _e( '— Select Course —', 'med-course-plugin' ); ?></option>
                                    <?php
                                    $courses = get_posts( [ 'post_type' => 'course', 'numberposts' => -1 ] );
                                    foreach ( $courses as $course ) {
                                        echo '<option value="' . esc_attr( $course->ID ) . '">' . esc_html( $course->post_title ) . '</option>';
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="mcp-lesson"><?php _e( 'Lesson', 'med-course-plugin' ); ?></label></th>
                            <td>
                                <select id="mcp-lesson" name="mcp_lesson_id" class="widefat" disabled required>
                                    <option value=""><?php _e( '— Select Lesson —', 'med-course-plugin' ); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="mcp-topic"><?php _e( 'Topic', 'med-course-plugin' ); ?></label></th>
                            <td>
                                <select id="mcp-topic" name="mcp_topic_id" class="widefat" disabled required>
                                    <option value=""><?php _e( '— Select Topic —', 'med-course-plugin' ); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="mcp-content-type"><?php _e( 'Content Type', 'med-course-plugin' ); ?></label></th>
                            <td>
                                <select id="mcp-content-type" name="mcp_content_type" class="widefat" required>
                                    <option value=""><?php _e( '— Select Content Type —', 'med-course-plugin' ); ?></option>
                                    <option value="tests"><?php _e( 'Tests', 'med-course-plugin' ); ?></option>
                                    <option value="flashcards"><?php _e( 'Flashcards', 'med-course-plugin' ); ?></option>
                                    <option value="sections"><?php _e( 'Sections', 'med-course-plugin' ); ?></option>
                                </select>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div id="mcp-export-container">
                    <h2><?php _e( '2. Export', 'med-course-plugin' ); ?></h2>
                    <p><?php _e( 'Click the button below to export the selected content to a CSV file.', 'med-course-plugin' ); ?></p>
                    <p><input type="submit" name="mcp_export_submit" class="button button-primary" value="<?php _e( 'Export to CSV', 'med-course-plugin' ); ?>"></p>
                </div>
            </form>
        </div>
    </div>
    <?php
}

/**
 * Handle the export request.
 */
function mcp_handle_export_request() {
    if ( ! isset( $_POST['mcp_export_submit'] ) || ! isset( $_POST['mcp_import_export_nonce'] ) || ! wp_verify_nonce( $_POST['mcp_import_export_nonce'], 'mcp_import_export_action' ) ) {
        return;
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $topic_id = isset( $_POST['mcp_topic_id'] ) ? absint( $_POST['mcp_topic_id'] ) : 0;
    $content_type = isset( $_POST['mcp_content_type'] ) ? sanitize_text_field( $_POST['mcp_content_type'] ) : '';

    if ( empty( $topic_id ) || empty( $content_type ) ) {
        return;
    }

    $filename = $content_type . '-export-' . date( 'Y-m-d' ) . '.csv';

    header( 'Content-Type: text/csv; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename=' . $filename );

    $output = fopen( 'php://output', 'w' );
    fprintf( $output, chr(0xEF) . chr(0xBB) . chr(0xBF) );

    switch ( $content_type ) {
        case 'tests':
            fputcsv( $output, [ 'identifier', 'question', 'option1', 'option2', 'option3', 'option4', 'correct_option', 'explanation' ] );
            $items = get_posts([
                'post_type' => 'test',
                'meta_key' => '_mcp_topic_id',
                'meta_value' => $topic_id,
                'numberposts' => -1,
                'orderby' => 'date',
                'order' => 'ASC'
            ]);
            foreach ( $items as $item ) {
                fputcsv( $output, [
                    get_post_meta( $item->ID, '_mcp_identifier', true ),
                    get_post_meta( $item->ID, '_mcp_question', true ),
                    get_post_meta( $item->ID, '_mcp_option1', true ),
                    get_post_meta( $item->ID, '_mcp_option2', true ),
                    get_post_meta( $item->ID, '_mcp_option3', true ),
                    get_post_meta( $item->ID, '_mcp_option4', true ),
                    get_post_meta( $item->ID, '_mcp_correct_option', true ),
                    get_post_meta( $item->ID, '_mcp_explanation', true )
                ]);
            }
            break;
        case 'flashcards':
            fputcsv( $output, [ 'question', 'answer' ] );
            $items = get_posts([
                'post_type' => 'flashcard',
                'meta_key' => '_mcp_topic_id',
                'meta_value' => $topic_id,
                'numberposts' => -1,
                'orderby' => 'date',
                'order' => 'ASC'
            ]);
            foreach ( $items as $item ) {
                fputcsv( $output, [
                    get_post_meta( $item->ID, '_mcp_question', true ),
                    get_post_meta( $item->ID, '_mcp_answer', true )
                ]);
            }
            break;
        case 'sections':
            fputcsv( $output, [ 'section_type', 'content' ] );
            $sections = get_post_meta( $topic_id, '_mcp_sections', true );
            if ( ! empty( $sections ) ) {
                foreach ( $sections as $section ) {
                    $section_type = get_post( $section['section_type'] );
                    fputcsv( $output, [
                        $section_type ? $section_type->post_title : '',
                        $section['content']
                    ]);
                }
            }
            break;
    }

    fclose( $output );
    exit;
}
add_action( 'init', 'mcp_handle_export_request' );

/**
 * Handle the import request.
 */
function mcp_handle_import_request() {
    if ( ! isset( $_POST['mcp_import_submit'] ) || ! isset( $_POST['mcp_import_export_nonce'] ) || ! wp_verify_nonce( $_POST['mcp_import_export_nonce'], 'mcp_import_export_action' ) ) {
        return;
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $redirect_url = admin_url( 'admin.php?page=mcp-csv-import' );
    $errors = [];

    if ( ! isset( $_FILES['mcp_csv_file'] ) || $_FILES['mcp_csv_file']['error'] !== UPLOAD_ERR_OK ) {
        $errors[] = __( 'File upload error.', 'med-course-plugin' );
        set_transient( 'mcp_import_errors', $errors, 60 );
        wp_redirect( add_query_arg( [ 'mcp_import_status' => 'error' ], $redirect_url ) );
        exit;
    }

    $file = $_FILES['mcp_csv_file'];
    $file_ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
    if ( $file_ext !== 'csv' ) {
        $errors[] = __( 'Invalid file type. Please upload a .csv file.', 'med-course-plugin' );
        set_transient( 'mcp_import_errors', $errors, 60 );
        wp_redirect( add_query_arg( [ 'mcp_import_status' => 'error' ], $redirect_url ) );
        exit;
    }

    $topic_id = isset( $_POST['mcp_topic_id'] ) ? absint( $_POST['mcp_topic_id'] ) : 0;
    $content_type = isset( $_POST['mcp_content_type'] ) ? sanitize_text_field( $_POST['mcp_content_type'] ) : '';
    $import_status = isset( $_POST['mcp_import_status'] ) ? sanitize_text_field( $_POST['mcp_import_status'] ) : 'publish';
    $replace_sections = isset( $_POST['mcp_replace_sections'] ) && $_POST['mcp_replace_sections'] == 1;
    $file_path = $_FILES['mcp_csv_file']['tmp_name'];

    if ( empty( $topic_id ) || empty( $content_type ) ) {
        $errors[] = __( 'Please select a course, lesson, topic, and content type.', 'med-course-plugin' );
        set_transient( 'mcp_import_errors', $errors, 60 );
        wp_redirect( add_query_arg( [ 'mcp_import_status' => 'error' ], $redirect_url ) );
        exit;
    }

    $imported_count = 0;
    $skipped_count = 0;
    $row_number = 1;

    if ( ( $handle = fopen( $file_path, 'r' ) ) !== false ) {
        $header = fgetcsv( $handle );
        if ( ! $header ) {
            $errors[] = __( 'Could not read CSV header.', 'med-course-plugin' );
            set_transient( 'mcp_import_errors', $errors, 60 );
            wp_redirect( add_query_arg( [ 'mcp_import_status' => 'error' ], $redirect_url ) );
            exit;
        }

        $new_sections = [];

        while ( ( $row = fgetcsv( $handle ) ) !== false ) {
            $row_number++;
            if ( count( $row ) !== count( $header ) ) {
                $errors[] = sprintf( __( 'Row %d: Column count does not match header.', 'med-course-plugin' ), $row_number );
                $skipped_count++;
                continue;
            }

            $data = array_combine( $header, $row );

            switch ( $content_type ) {
                case 'tests':
                    if ( empty( trim( $data['question'] ) ) ) {
                        $errors[] = sprintf( __( 'Row %d: "question" column is empty.', 'med-course-plugin' ), $row_number );
                        $skipped_count++;
                        continue 2;
                    }
                    $post_id = wp_insert_post([
                        'post_title' => wp_trim_words( $data['question'], 10, '...' ),
                        'post_type' => 'test',
                        'post_status' => $import_status,
                    ]);
                    if ( $post_id ) {
                        update_post_meta( $post_id, '_mcp_topic_id', $topic_id );
                        update_post_meta( $post_id, '_mcp_identifier', sanitize_text_field( $data['identifier'] ) );
                        update_post_meta( $post_id, '_mcp_question', wp_kses_post( $data['question'] ) );
                        update_post_meta( $post_id, '_mcp_option1', sanitize_text_field( $data['option1'] ) );
                        update_post_meta( $post_id, '_mcp_option2', sanitize_text_field( $data['option2'] ) );
                        update_post_meta( $post_id, '_mcp_option3', sanitize_text_field( $data['option3'] ) );
                        update_post_meta( $post_id, '_mcp_option4', sanitize_text_field( $data['option4'] ) );
                        update_post_meta( $post_id, '_mcp_correct_option', absint( $data['correct_option'] ) );
                        update_post_meta( $post_id, '_mcp_explanation', wp_kses_post( $data['explanation'] ) );
                        $imported_count++;
                    }
                    break;
                case 'flashcards':
                    if ( empty( trim( $data['question'] ) ) ) {
                        $errors[] = sprintf( __( 'Row %d: "question" column is empty.', 'med-course-plugin' ), $row_number );
                        $skipped_count++;
                        continue 2;
                    }
                    $post_id = wp_insert_post([
                        'post_title' => wp_trim_words( $data['question'], 10, '...' ),
                        'post_type' => 'flashcard',
                        'post_status' => $import_status,
                    ]);
                    if ( $post_id ) {
                        update_post_meta( $post_id, '_mcp_topic_id', $topic_id );
                        update_post_meta( $post_id, '_mcp_question', sanitize_textarea_field( $data['question'] ) );
                        update_post_meta( $post_id, '_mcp_answer', sanitize_textarea_field( $data['answer'] ) );
                        $imported_count++;
                    }
                    break;
                case 'sections':
                    $section_type_name = trim( $data['section_type'] );
                    $section_type = get_page_by_title( $section_type_name, OBJECT, 'section_type' );
                    if ( ! $section_type ) {
                        // Case-insensitive fallback
                        $all_section_types = get_posts(['post_type' => 'section_type', 'numberposts' => -1]);
                        foreach ( $all_section_types as $st ) {
                            if ( strtolower( $st->post_title ) === strtolower( $section_type_name ) ) {
                                $section_type = $st;
                                break;
                            }
                        }
                    }

                    if ( $section_type ) {
                        $new_sections[] = [
                            'section_type' => $section_type->ID,
                            'content' => wp_kses_post( $data['content'] ),
                        ];
                        $imported_count++;
                    } else {
                        $errors[] = sprintf( __( 'Row %d: Section type "%s" not found.', 'med-course-plugin' ), $row_number, $section_type_name );
                        $skipped_count++;
                    }
                    break;
            }
        }
        fclose( $handle );

        if ( $content_type === 'sections' ) {
            $existing_sections = $replace_sections ? [] : ( get_post_meta( $topic_id, '_mcp_sections', true ) ?: [] );
            $all_sections = array_merge( $existing_sections, $new_sections );
            update_post_meta( $topic_id, '_mcp_sections', $all_sections );
        }
    }

    set_transient( 'mcp_import_errors', $errors, 60 );
    wp_redirect( add_query_arg( [
        'mcp_import_status' => 'success',
        'mcp_imported_count' => $imported_count,
        'mcp_skipped_count' => $skipped_count
    ], $redirect_url ) );
    exit;
}
add_action( 'init', 'mcp_handle_import_request' );

/**
 * AJAX handler for getting lessons by course.
 */
function mcp_get_lessons_by_course() {
    if ( ! isset( $_POST['course_id'] ) || ! is_numeric( $_POST['course_id'] ) ) {
        wp_send_json_error( 'Invalid course ID' );
    }

    $course_id = absint( $_POST['course_id'] );
    $lessons = get_posts([
        'post_type' => 'lesson',
        'meta_key' => '_mcp_course_id',
        'meta_value' => $course_id,
        'numberposts' => -1,
        'orderby' => 'date',
        'order' => 'ASC'
    ]);

    $response = [];
    foreach ( $lessons as $lesson ) {
        $response[] = [
            'id' => $lesson->ID,
            'title' => $lesson->post_title
        ];
    }

    wp_send_json_success( $response );
}
add_action( 'wp_ajax_mcp_get_lessons_by_course', 'mcp_get_lessons_by_course' );

/**
 * AJAX handler for getting topics by lesson.
 */
function mcp_get_topics_by_lesson() {
    if ( ! isset( $_POST['lesson_id'] ) || ! is_numeric( $_POST['lesson_id'] ) ) {
        wp_send_json_error( 'Invalid lesson ID' );
    }

    $lesson_id = absint( $_POST['lesson_id'] );
    $topics = get_posts([
        'post_type' => 'topic',
        'meta_key' => '_mcp_lesson_id',
        'meta_value' => $lesson_id,
        'numberposts' => -1,
        'orderby' => 'date',
        'order' => 'ASC'
    ]);

    $response = [];
    foreach ( $topics as $topic ) {
        $response[] = [
            'id' => $topic->ID,
            'title' => $topic->post_title
        ];
    }

    wp_send_json_success( $response );
}
add_action( 'wp_ajax_mcp_get_topics_by_lesson', 'mcp_get_topics_by_lesson' );
