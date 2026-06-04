<?php
    get_header();
    $topic_id  = get_the_ID();
    $lesson_id = get_post_meta( $topic_id, '_mms_lesson_id', true );
    $course_id = get_post_meta( $topic_id, '_mms_course_id', true );

    wp_enqueue_style( 'jsmind' );
    wp_enqueue_style( 'mms-frontend' );
    wp_enqueue_script( 'jsmind' );
    wp_enqueue_script( 'mindmap-studio-frontend' );

    $inline_settings = array(
        'watermark'   => array(
            'text'            => get_option( 'mind_map_watermark_text',    '' ),
            'size'            => (float) get_option( 'mind_map_watermark_size',    14 ),
            'spacing_desktop' => (int)   get_option( 'mind_map_watermark_spacing', 220 ),
            'spacing_mobile'  => (int)   round( get_option( 'mind_map_watermark_spacing', 220 ) / 2 ),
            'color'           => get_option( 'mind_map_watermark_color',   '#94a3b8' ),
            'opacity'         => (float) get_option( 'mind_map_watermark_opacity', 0.18 ),
        ),
        'theme_light'   => get_option( 'mind_map_theme_light',        'primary' ),
        'theme_dark'    => get_option( 'mind_map_theme_dark',         'primary' ),
        'line_color'    => get_option( 'mind_map_line_color',         '#94a3b8' ),
        'line_style'    => get_option( 'mind_map_line_style',         'bezier' ),
        'line_width'    => (float) get_option( 'mind_map_line_width', 2 ),
        'border_radius' => (int)   get_option( 'mind_map_node_border_radius', 12 ),
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
                <a href="<?php echo Mind_Map_Studio::get_mms_permalink( $course_id ); ?>"><?php echo get_the_title( $course_id ); ?></a>
            </div>
        <?php endif; ?>
        <?php if ( $lesson_id ) : ?>
            <div class="breadcrumb-item">
                <a href="<?php echo Mind_Map_Studio::get_mms_permalink( $lesson_id ); ?>"><?php echo get_the_title( $lesson_id ); ?></a>
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
            <details class="section-card" <?php echo ( $a_index === 0 ) ? 'open' : ''; ?>>
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
                            $unique_id   = "mms_front_{$a_index}_{$m_index}";
                            $node_styles = isset( $mindmap['node_styles'] ) ? $mindmap['node_styles'] : '{}';
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
                                            data-node-styles="<?php echo esc_attr( $node_styles ); ?>"
                                            data-line-color="<?php echo esc_attr( $inline_settings['line_color'] ); ?>"
                                            data-line-style="<?php echo esc_attr( $inline_settings['line_style'] ); ?>"
                                            data-line-width="<?php echo esc_attr( $inline_settings['line_width'] ); ?>">
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
        border-radius: <?php echo (int) $inline_settings['border_radius']; ?>px !important;
    }
    .mindmap-studio-container { direction: ltr !important; overflow: hidden !important; }
    .mindmap-studio-container jmexpander { display: none !important; }
    .mms-modal jmnode { border-radius: <?php echo (int) $inline_settings['border_radius']; ?>px !important; }
</style>

<?php get_footer(); ?>
