<?php
    get_header();
    $lesson_id = get_the_ID();
    $course_id = get_post_meta( $lesson_id, '_mcp_course_id', true );
?>

<main class="container">
    <div class="breadcrumb-container">
        <?php if ( $course_id ) : ?>
            <div class="breadcrumb-item">
                <a href="<?php echo mcp_get_permalink($course_id); ?>"><?php echo get_the_title($course_id); ?></a>
            </div>
        <?php endif; ?>
        <div class="breadcrumb-item active">
            <span><?php the_title(); ?></span>
        </div>
    </div>
    <div class="topic-box">
        <?php
        $topics = get_posts([
            'post_type' => 'topic',
            'meta_key' => '_mcp_lesson_id',
            'meta_value' => $lesson_id,
            'numberposts' => -1,
            'orderby' => [ 'menu_order' => 'ASC', 'date' => 'ASC' ]
        ]);

        if ( ! empty( $topics ) ) {
            echo '<ul class="topic-list">';
            foreach ( $topics as $topic ) {
                ?>
                <li class="topic-list-item">
                    <span><?php echo get_the_title( $topic->ID ); ?></span>
                    <?php
                        $course_slug = get_post_meta( $course_id, '_mcp_english_slug', true );
                        $lesson_slug = get_post_meta( $lesson_id, '_mcp_english_slug', true );
                        $topic_slug = get_post_meta( $topic->ID, '_mcp_english_slug', true );
                        $test_url = site_url( "/test/{$course_slug}/{$lesson_slug}/{$topic_slug}/" );
                    ?>
                    <div class="topic-buttons">
                        <a href="<?php echo mcp_get_permalink( $topic->ID ); ?>" class="btn-topic"><?php echo esc_html__( 'درسنامه', 'med-course-plugin' ); ?></a>
                        <a href="<?php echo esc_url( $test_url ); ?>" class="btn-topic-test"><?php echo esc_html__( 'تست', 'med-course-plugin' ); ?></a>
                    </div>
                </li>
                <?php
            }
            echo '</ul>';
        } else {
            echo '<p>هیچ مبحثی برای این درس یافت نشد.</p>';
        }
        ?>
    </div>
</main>

<?php get_footer(); ?>
