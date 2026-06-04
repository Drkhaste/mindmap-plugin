<?php
    get_header();
    $topic_id = get_the_ID();
    $lesson_id = get_post_meta( $topic_id, '_mms_lesson_id', true );
    $course_id = get_post_meta( $topic_id, '_mms_course_id', true );

    // Enqueue assets
    wp_enqueue_style( 'jsmind' );
    wp_enqueue_style( 'mms-frontend' );
    wp_enqueue_script( 'jsmind' );
    wp_enqueue_script( 'mindmap-studio-frontend' );

    // Inline settings
    $inline_settings = array(
        'watermark'   => array(
            'text'    => '',
            'size'    => 14,
            'spacing_desktop' => 220,
            'spacing_mobile'  => 110,
            'color'   => '#94a3b8',
            'opacity' => 0.18,
        ),
        'theme_light' => 'primary',
        'theme_dark'  => 'dark',
        'line_color'  => '#cbd5e1',
        'line_style'  => 'bezier',
        'line_width'  => 2,
        'border_radius' => 5,
    );
    wp_add_inline_script(
        'mindmap-studio-frontend',
        'window.mindMapStudioSettings = ' . wp_json_encode( $inline_settings ) . ';',
        'before'
    );
?>

<main class="mms-container">
    <div class="breadcrumb-container">
        <?php if ( $course_id ) : ?>
            <div class="breadcrumb-item">
                <a href="<?php echo Mind_Map_Studio::get_mms_permalink($course_id); ?>"><?php echo get_the_title($course_id); ?></a>
            </div>
        <?php endif; ?>
        <?php if ( $lesson_id ) : ?>
            <div class="breadcrumb-item">
                <a href="<?php echo Mind_Map_Studio::get_mms_permalink($lesson_id); ?>"><?php echo get_the_title($lesson_id); ?></a>
            </div>
        <?php endif; ?>
        <div class="breadcrumb-item active">
            <span><?php the_title(); ?></span>
        </div>
    </div>


    <?php
    $accordions = get_post_meta( $topic_id, '_mms_accordions', true );
    if ( ! empty( $accordions ) ) {
        foreach ( $accordions as $a_index => $accordion ) {
            ?>
            <details class="section-card" <?php echo ($a_index === 0) ? 'open' : ''; ?>>
                <summary>
                    <div class="sec-title">
                        <i class="fa-solid fa-sitemap"></i>
                        <?php echo esc_html( $accordion['title'] ); ?>
                    </div>
                    <i class="fa-solid fa-chevron-down"></i>
                </summary>
                <div class="card-content">
                    <?php
                    if ( ! empty( $accordion['mindmaps'] ) ) {
                        foreach ( $accordion['mindmaps'] as $m_index => $mindmap ) {
                            $unique_id = "mms_front_{$a_index}_{$m_index}";
                            ?>
                            <div class="mms-front-mindmap-item" style="margin-bottom: 30px;">
                                <h4 style="margin-bottom: 10px; border-right: 3px solid var(--primary); padding-right: 10px;"><?php echo esc_html( $mindmap['title'] ); ?></h4>
                                <div class="mindmap-studio-wrapper" style="width:100%; position:relative;">
                                    <div class="mindmap-studio-capture" id="capture_<?php echo $unique_id; ?>" style="width:100%; position:relative;">
                                        <div
                                            id="<?php echo $unique_id; ?>"
                                            class="mindmap-studio-container"
                                            data-mindmap-data="<?php echo esc_attr( $mindmap['data'] ); ?>"
                                            data-mindmap-layout="<?php echo esc_attr( $mindmap['layout'] ); ?>"
                                            data-line-color="#cbd5e1"
                                            data-line-style="bezier"
                                            data-line-width="2">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                    }
                    ?>
                </div>
            </details>
            <?php
        }
    } else {
        echo '<p style="text-align:center;">هنوز نقشه‌ای برای این مبحث اضافه نشده است.</p>';
    }
    ?>
</main>

<style>
    .mindmap-studio-container jmnode {
        font-family: inherit !important;
        border-radius: 5px !important;
    }
    .mindmap-studio-container { direction: ltr !important; overflow: hidden !important; }
    .mindmap-studio-container jmexpander { display: none !important; }
    .mms-modal jmnode {
        border-radius: 5px !important;
    }
</style>


<?php get_footer(); ?>
