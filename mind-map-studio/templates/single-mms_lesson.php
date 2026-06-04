<?php
    get_header();
    wp_enqueue_style( 'mms-frontend' );
    $lesson_id = get_the_ID();
    $course_id = get_post_meta( $lesson_id, '_mms_course_id', true );
?>

<main class="mms-container">
    <div class="breadcrumb-container">
        <?php if ( $course_id ) : ?>
            <div class="breadcrumb-item">
                <a href="<?php echo Mind_Map_Studio::get_mms_permalink( $course_id ); ?>">
                    <?php echo get_the_title( $course_id ); ?>
                </a>
            </div>
        <?php endif; ?>
        <div class="breadcrumb-item active">
            <span><?php the_title(); ?></span>
        </div>
    </div>

    <div class="topic-box">
        <?php
        $topics = get_posts([
            'post_type'  => 'mms_topic',
            'meta_key'   => '_mms_lesson_id',
            'meta_value' => $lesson_id,
            'numberposts' => -1,
            'orderby'    => [ 'menu_order' => 'ASC', 'date' => 'ASC' ],
        ]);

        if ( ! empty( $topics ) ) {
            echo '<ul class="topic-list">';
            foreach ( $topics as $topic ) : ?>
                <li class="topic-list-item">
                    <span><?php echo get_the_title( $topic->ID ); ?></span>
                    <div class="topic-buttons">
                        <a href="<?php echo Mind_Map_Studio::get_mms_permalink( $topic->ID ); ?>" class="btn-topic">
                            <?php esc_html_e( 'مشاهده نقشه‌ها', 'mind-map-studio' ); ?>
                        </a>
                    </div>
                </li>
            <?php endforeach;
            echo '</ul>';
        } else {
            echo '<p style="color:#64748b;font-size:.9rem;text-align:center;padding:20px 0;">هیچ مبحثی برای این درس یافت نشد.</p>';
        }
        ?>
    </div>
</main>

<?php get_footer(); ?>
