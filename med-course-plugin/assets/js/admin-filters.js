jQuery(document).ready(function($) {
    // Auto-submit on change for Course and Lesson filters
    $('#mcp_filter_course, #mcp_filter_lesson').on('change', function() {
        $(this).closest('form').submit();
    });
});
