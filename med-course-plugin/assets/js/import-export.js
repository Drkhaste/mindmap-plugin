jQuery(document).ready(function($) {
    // Handle course selection change
    $('#mcp-course').on('change', function() {
        var courseId = $(this).val();
        var lessonDropdown = $('#mcp-lesson');
        var topicDropdown = $('#mcp-topic');

        lessonDropdown.prop('disabled', true).html('<option value="">— Select Lesson —</option>');
        topicDropdown.prop('disabled', true).html('<option value="">— Select Topic —</option>');

        if (courseId) {
            $.post(mcp_ajax.ajax_url, {
                action: 'mcp_get_lessons_by_course',
                course_id: courseId
            }, function(response) {
                if (response.success) {
                    lessonDropdown.prop('disabled', false);
                    $.each(response.data, function(i, lesson) {
                        lessonDropdown.append($('<option>', {
                            value: lesson.id,
                            text: lesson.title
                        }));
                    });
                }
            });
        }
    });

    // Handle lesson selection change
    $('#mcp-lesson').on('change', function() {
        var lessonId = $(this).val();
        var topicDropdown = $('#mcp-topic');

        topicDropdown.prop('disabled', true).html('<option value="">— Select Topic —</option>');

        if (lessonId) {
            $.post(mcp_ajax.ajax_url, {
                action: 'mcp_get_topics_by_lesson',
                lesson_id: lessonId
            }, function(response) {
                if (response.success) {
                    topicDropdown.prop('disabled', false);
                    $.each(response.data, function(i, topic) {
                        topicDropdown.append($('<option>', {
                            value: topic.id,
                            text: topic.title
                        }));
                    });
                }
            });
        }
    });

    // Handle content type selection change on import page
    if ($('#mcp-import-form').length) {
        $('#mcp-content-type').on('change', function() {
            var contentType = $(this).val();
            var instructionsContainer = $('#mcp-instructions-container');
            var instructions = $('.mcp-instructions');
            var replaceSectionsRow = $('#mcp-replace-sections-row');

            instructionsContainer.hide();
            instructions.hide();
            replaceSectionsRow.hide();

            if (contentType) {
                instructionsContainer.show();
                $('#mcp-instructions-' + contentType).show();

                if (contentType === 'sections') {
                    replaceSectionsRow.show();
                }
            }
        });
    }

    // Handle content type selection change on export page
    if ($('#mcp-export-form').length) {
        $('#mcp-content-type').on('change', function() {
            var contentType = $(this).val();
            var exportContainer = $('#mcp-export-container');

            exportContainer.hide();

            if (contentType) {
                exportContainer.show();
            }
        });
    }
});
