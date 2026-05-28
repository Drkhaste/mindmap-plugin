<?php
/**
 * Template Name: Topic Tests
 */

get_header();

$course_slug = get_query_var( 'course_slug' );
$lesson_slug = get_query_var( 'lesson_slug' );
$topic_slug  = get_query_var( 'topic_slug' );

$topic = get_posts( [
    'name'           => $topic_slug,
    'post_type'      => 'topic',
    'posts_per_page' => 1,
] );

if ( ! $topic ) {
    wp_die( 'مبحث مورد نظر یافت نشد.' );
}

$topic_id  = $topic[0]->ID;
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
        <div class="breadcrumb-item">
            <a href="<?php echo mcp_get_permalink($topic_id); ?>"><?php echo get_the_title($topic_id); ?></a>
        </div>
        <div class="breadcrumb-item active">
            <span>تست‌ها</span>
        </div>
    </div>

    <?php
    $tests = get_posts([
        'post_type' => 'test',
        'meta_key' => '_mcp_topic_id',
        'meta_value' => $topic_id,
        'numberposts' => -1,
        'orderby' => 'date',
        'order' => 'ASC'
    ]);

    if ( ! empty( $tests ) ) {
        foreach ( $tests as $index => $test ) {
            $test_id = $test->ID;
            $question = get_post_meta( $test_id, '_mcp_question', true );
            $options = [
                '1' => get_post_meta( $test_id, '_mcp_option1', true ),
                '2' => get_post_meta( $test_id, '_mcp_option2', true ),
                '3' => get_post_meta( $test_id, '_mcp_option3', true ),
                '4' => get_post_meta( $test_id, '_mcp_option4', true ),
            ];
            $correct_answer = get_post_meta( $test_id, '_mcp_correct_option', true );
            $correct_answer_feedback = get_post_meta( $test_id, '_mcp_explanation', true );
            ?>
            <div class="test-card" id="test-<?php echo $index; ?>">
                <div class="test-question"><?php echo ( $index + 1 ) . '. ' . apply_filters( 'the_content', $question ); ?></div>
                <div class="test-options">
                    <?php if ( ! empty( $options ) ) : ?>
                        <?php foreach ( $options as $key => $option ) : ?>
                            <?php if ( ! empty( $option ) ) : ?>
                                <div>
                                    <input type="radio" name="option-<?php echo $index; ?>" id="option-<?php echo $index . '-' . $key; ?>" value="<?php echo $key; ?>">
                                    <label for="option-<?php echo $index . '-' . $key; ?>"><?php echo esc_html( $option ); ?></label>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <button class="check-answer-btn" onclick="checkAnswer(<?php echo $index; ?>, '<?php echo esc_js( $correct_answer ); ?>')">بررسی پاسخ</button>
                <div class="correct-answer-feedback" id="feedback-<?php echo $index; ?>" style="display:none;">
                    <?php echo apply_filters( 'the_content', $correct_answer_feedback ); ?>
                </div>
            </div>
            <?php
        }
    } else {
        echo '<p>هنوز تستی برای این مبحث اضافه نشده است.</p>';
    }
    ?>
</main>

<script>
    function checkAnswer(testIndex, correctAnswer) {
        const selectedOption = document.querySelector(`input[name="option-${testIndex}"]:checked`);
        const feedbackDiv = document.getElementById(`feedback-${testIndex}`);
        const labels = document.querySelectorAll(`#test-${testIndex} .test-options label`);

        if (!selectedOption) {
            alert('لطفا یک گزینه را انتخاب کنید.');
            return;
        }

        labels.forEach(label => {
            const inputId = label.getAttribute('for');
            const input = document.getElementById(inputId);
            if (input.value === correctAnswer) {
                label.style.backgroundColor = 'var(--correct-ans)';
                label.style.borderColor = 'var(--correct-ans)';
                label.style.color = 'white';
            }
        });

        if (selectedOption.value === correctAnswer) {
            feedbackDiv.className = 'correct-answer-feedback correct';
        } else {
            feedbackDiv.className = 'correct-answer-feedback wrong';
            selectedOption.nextElementSibling.style.backgroundColor = 'var(--wrong-ans)';
            selectedOption.nextElementSibling.style.borderColor = 'var(--wrong-ans)';
            selectedOption.nextElementSibling.style.color = 'white';
        }
        feedbackDiv.style.display = 'block';
    }
</script>

<?php get_footer(); ?>
