<?php
    get_header();
    $course_id = get_the_ID();
?>

<main class="container">
    <div class="breadcrumb-container">
        <div class="breadcrumb-item active">
            <span><?php the_title(); ?></span>
        </div>
    </div>
    <?php
    $lessons = get_posts([
        'post_type' => 'mms_lesson',
        'meta_key' => '_mms_course_id',
        'meta_value' => $course_id,
        'numberposts' => -1,
        'orderby' => [ 'menu_order' => 'ASC', 'date' => 'ASC' ]
    ]);

    if ( ! empty( $lessons ) ) {
        foreach ( $lessons as $lesson ) {
            ?>
            <details class="section-card">
                <summary>
                    <div class="sec-title">
                        <i class="fa-solid fa-chalkboard-teacher"></i>
                        <?php echo get_the_title( $lesson->ID ); ?>
                    </div>
                    <i class="fa-solid fa-chevron-down"></i>
                </summary>
                <div class="card-content">
                    <?php
                    $topics = get_posts([
                        'post_type' => 'mms_topic',
                        'meta_key' => '_mms_lesson_id',
                        'meta_value' => $lesson->ID,
                        'numberposts' => -1,
                        'orderby' => [ 'menu_order' => 'ASC', 'date' => 'ASC' ]
                    ]);

                    if ( ! empty( $topics ) ) {
                        echo '<ul class="topic-list">';
                        foreach ( $topics as $topic ) {
                            ?>
                            <li class="topic-list-item">
                                <span><?php echo get_the_title( $topic->ID ); ?></span>
                                <div class="topic-buttons">
                                    <a href="<?php echo Mind_Map_Studio::get_mms_permalink( $topic->ID ); ?>" class="btn-topic"><?php echo esc_html__( 'مشاهده نقشه‌ها', 'mind-map-studio' ); ?></a>
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
            </details>
            <?php
        }
    } else {
        echo '<p>هیچ درسی برای این کورس یافت نشد.</p>';
    }
    ?>
</main>

<?php get_footer(); ?>
