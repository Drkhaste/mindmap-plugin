<?php
    get_header();
    $topic_id = get_the_ID();
    $lesson_id = get_post_meta( $topic_id, '_mcp_lesson_id', true );
    $course_id = $lesson_id ? get_post_meta( $lesson_id, '_mcp_course_id', true ) : null;
?>

<main class="container">
    <div class="breadcrumb-container">
        <?php if ( $course_id ) : ?>
            <div class="breadcrumb-item">
                <a href="<?php echo mcp_get_permalink($course_id); ?>"><?php echo get_the_title($course_id); ?></a>
            </div>
        <?php endif; ?>
        <?php if ( $lesson_id ) : ?>
            <div class="breadcrumb-item">
                <a href="<?php echo mcp_get_permalink($lesson_id); ?>"><?php echo get_the_title($lesson_id); ?></a>
            </div>
        <?php endif; ?>
        <div class="breadcrumb-item active">
            <span><?php the_title(); ?></span>
        </div>
    </div>

    <?php
    $sections = get_post_meta( $topic_id, '_mcp_sections', true );
    if ( ! empty( $sections ) ) {
        foreach ( $sections as $section ) {
            $section_type_id = $section['section_type'];
            $section_type_title = get_the_title( $section_type_id );
            $icon_class = get_post_meta( $section_type_id, '_mcp_icon_class', true );
            ?>
            <?php
            // Ensure icon has proper FA6 classes
            $final_icon_class = $icon_class;
            if ( ! empty( $icon_class ) && strpos( $icon_class, 'fa-' ) === 0 ) {
                if ( strpos( $icon_class, 'fa-solid' ) === false && strpos( $icon_class, 'fa-brands' ) === false && strpos( $icon_class, 'fab ' ) === false ) {
                    $final_icon_class = 'fa-solid ' . $icon_class;
                }
            } else if ( empty( $icon_class ) ) {
                $final_icon_class = 'fa-solid fa-book'; // Fallback
            }
            ?>
            <details class="section-card" <?php echo (is_array($sections) && count($sections) === 1) ? 'open' : ''; ?>>
                <summary>
                    <div class="sec-title">
                        <i class="<?php echo esc_attr( $final_icon_class ); ?>"></i> <?php echo esc_html( $section_type_title ); ?>
                    </div>
                    <i class="fa-solid fa-chevron-down"></i>
                </summary>
                <div class="card-content">
                    <?php echo apply_filters( 'the_content', $section['content'] ); ?>
                </div>
            </details>
            <?php
        }
    }

    $flashcards = get_posts([
        'post_type' => 'flashcard',
        'meta_key' => '_mcp_topic_id',
        'meta_value' => $topic_id,
        'numberposts' => -1,
        'orderby' => 'date',
        'order' => 'ASC'
    ]);
    ?>
    <div class="flashcard-box">
        <h3 style="text-align: center;">مرور با فلش کارت</h3>
        <?php if ( ! empty( $flashcards ) ) : ?>
            <?php foreach ( $flashcards as $flashcard ) : ?>
                <div class="fc-item">
                    <div style="font-weight: bold;"><?php echo apply_filters( 'the_content', get_post_meta( $flashcard->ID, '_mcp_question', true ) ); ?></div>
                    <button class="fc-btn" onclick="toggleAnswer(this)">نمایش پاسخ</button>
                    <div class="fc-ans"><?php echo apply_filters( 'the_content', get_post_meta( $flashcard->ID, '_mcp_answer', true ) ); ?></div>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <p style="text-align:center; margin-top: 20px;">هنوز فلش‌کارتی برای این مبحث اضافه نشده است.</p>
        <?php endif; ?>
    </div>
</main>

<script>
    function toggleAnswer(btn) {
        const ans = btn.nextElementSibling;
        if(ans.style.display === 'block'){
            ans.style.display = 'none';
            btn.textContent = 'نمایش پاسخ';
        } else {
            ans.style.display = 'block';
            btn.textContent = 'بستن پاسخ';
        }
    }
</script>

<?php get_footer(); ?>
