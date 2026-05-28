<?php
    get_header();
    $topic_id = get_the_ID();
    $lesson_id = get_post_meta( $topic_id, '_mms_lesson_id', true );
    $course_id = get_post_meta( $topic_id, '_mms_course_id', true );

    // Enqueue assets
    wp_enqueue_style( 'jsmind' );
    wp_enqueue_script( 'jsmind' );
    wp_enqueue_script( 'mindmap-studio-frontend' );

    // Inline settings
    $inline_settings = array(
        'watermark'   => array(
            'text'    => get_option( 'mind_map_watermark_text', '' ),
            'size'    => (int) get_option( 'mind_map_watermark_size', 14 ),
            'spacing_desktop' => (int) get_option( 'mind_map_watermark_spacing_desktop', 220 ),
            'spacing_mobile'  => (int) get_option( 'mind_map_watermark_spacing_mobile', 110 ),
            'color'   => get_option( 'mind_map_watermark_color', '#94a3b8' ),
            'opacity' => (float) get_option( 'mind_map_watermark_opacity', 0.18 ),
        ),
        'theme_light' => get_option( 'mind_map_theme_light', 'primary' ),
        'theme_dark'  => get_option( 'mind_map_theme_dark', 'dark' ),
        'line_color'  => get_option( 'mind_map_line_color', '#cbd5e1' ),
        'line_style'  => get_option( 'mind_map_line_style', 'bezier' ),
        'line_width'  => get_option( 'mind_map_line_width', 2 ),
        'border_radius' => get_option( 'mind_map_node_border_radius', 5 ),
    );
    wp_add_inline_script(
        'mindmap-studio-frontend',
        'window.mindMapStudioSettings = ' . wp_json_encode( $inline_settings ) . ';',
        'before'
    );
?>

<main class="container">
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

    <!-- Header features placeholder -->
    <div class="mms-header-controls" style="background: var(--bg-card); padding: 10px 15px; border-radius: 10px; margin-bottom: 20px; display: flex; gap: 15px; align-items: center; border: 1px solid #eee;">
        <div style="font-weight: bold; font-size: 0.9rem;"><?php _e('تنظیمات نمایش:', 'mind-map-studio'); ?></div>
        <select id="mms-font-family" style="padding: 5px; border-radius: 5px; border: 1px solid #ddd;">
            <option value="inherit"><?php _e('پیش‌فرض', 'mind-map-studio'); ?></option>
            <option value="Vazirmatn"><?php _e('Vazirmatn', 'mind-map-studio'); ?></option>
            <option value="Tahoma"><?php _e('Tahoma', 'mind-map-studio'); ?></option>
            <option value="Arial"><?php _e('Arial', 'mind-map-studio'); ?></option>
        </select>
        <div style="display: flex; align-items: center; gap: 5px;">
            <button class="button mms-font-size-dec">-</button>
            <span id="mms-font-size-val">100%</span>
            <button class="button mms-font-size-inc">+</button>
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
                                            data-line-color="<?php echo esc_attr( get_option( 'mind_map_line_color', '#cbd5e1' ) ); ?>"
                                            data-line-style="<?php echo esc_attr( get_option( 'mind_map_line_style', 'bezier' ) ); ?>"
                                            data-line-width="<?php echo esc_attr( get_option( 'mind_map_line_width', 2 ) ); ?>">
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
        border-radius: <?php echo (int) get_option( 'mind_map_node_border_radius', 5 ); ?>px !important;
    }
    .mindmap-studio-container { direction: ltr !important; overflow: hidden !important; }
    .mindmap-studio-container jmexpander { display: none !important; }
    .mms-modal jmnode {
        border-radius: <?php echo (int) get_option( 'mind_map_node_border_radius', 5 ); ?>px !important;
    }
</style>

<script>
jQuery(document).ready(function($) {
    var baseFontSize = 100;

    $('#mms-font-family').on('change', function() {
        var font = $(this).val();
        // Add a style tag to override jmnode font-family
        $('#mms-custom-font-style').remove();
        if (font !== 'inherit') {
            $('head').append('<style id="mms-custom-font-style">.mindmap-studio-container jmnode { font-family: ' + font + ' !important; }</style>');
        }
        window.dispatchEvent(new Event('resize'));
    });

    $('.mms-font-size-inc').on('click', function() {
        baseFontSize += 10;
        updateFontSize();
    });

    $('.mms-font-size-dec').on('click', function() {
        if (baseFontSize > 50) {
            baseFontSize -= 10;
            updateFontSize();
        }
    });

    function updateFontSize() {
        $('#mms-font-size-val').text(baseFontSize + '%');
        // Instead of em on container, let's try a CSS variable or direct style
        $('#mms-custom-size-style').remove();
        $('head').append('<style id="mms-custom-size-style">.mindmap-studio-container jmnode { font-size: ' + (baseFontSize / 100 * 12) + 'px !important; }</style>');

        window.dispatchEvent(new Event('resize'));

        // Some jsMind versions might need explicit redraw
        if (window.jsMind && window.jsMind.current) {
             // If we had a global reference, we'd use it.
             // The frontend script handles multiple instances, so resize event is better.
        }
    }
});
</script>

<?php get_footer(); ?>
