<?php
    get_header();
    wp_enqueue_style( 'mms-frontend' );
    $course_id = get_the_ID();
?>

<main class="mms-container">
    <div class="breadcrumb-container">
        <div class="breadcrumb-item active">
            <span><?php the_title(); ?></span>
        </div>
    </div>

    <?php
    $lessons = get_posts([
        'post_type'  => 'mms_lesson',
        'meta_key'   => '_mms_course_id',
        'meta_value' => $course_id,
        'numberposts' => -1,
        'orderby'    => [ 'menu_order' => 'ASC', 'date' => 'ASC' ],
    ]);

    if ( ! empty( $lessons ) ) {
        foreach ( $lessons as $index => $lesson ) :
        ?>
        <details class="section-card" style="animation-delay: <?php echo $index * 0.05; ?>s">
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
                    'post_type'  => 'mms_topic',
                    'meta_key'   => '_mms_lesson_id',
                    'meta_value' => $lesson->ID,
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
                    echo '<p style="color:#64748b;font-size:.9rem;">هیچ مبحثی برای این درس یافت نشد.</p>';
                }
                ?>
            </div>
        </details>
        <?php endforeach;
    } else {
        echo '<p style="text-align:center;color:#64748b;">هیچ درسی برای این کورس یافت نشد.</p>';
    }
    ?>
</main>

<?php get_footer(); ?>
