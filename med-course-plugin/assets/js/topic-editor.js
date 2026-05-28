jQuery(document).ready(function($) {
    // Note: Most of the repeater logic is in admin-scripts.js. This file handles the new modal functionality.

    let $currentSelect2;

    // Function to initialize Select2
    function initSelect2($element) {
        $element.select2({
            width: 'calc(100% - 100px)', // Adjust width to accommodate the button
            matcher: function(params, data) {
                // If there is no search term, only show starred items (those that do NOT have the non-starred-option class)
                if ($.trim(params.term) === '') {
                    return $(data.element).hasClass('non-starred-option') ? null : data;
                }

                // If there is a search term, search through all options
                // `params.term` should be the term that is used for searching
                // `data.text` is the text that is displayed for the data object
                if (data.text.toLowerCase().indexOf(params.term.toLowerCase()) > -1) {
                    return data;
                }

                // Return `null` if the term should not be displayed
                return null;
            }
        });
    }

    // Initialize Select2 on existing elements
    $('.mcp-section-type-select').each(function() {
        initSelect2($(this));
    });

    // Toggle inline form
    $('#mcp-sections-repeater').on('click', '.mcp-add-new-section-type', function() {
        $(this).siblings('.mcp-inline-form-wrapper').slideToggle();
    });

    $('#mcp-sections-repeater').on('click', '.mcp-cancel-new-section-type', function() {
        $(this).closest('.mcp-inline-form-wrapper').slideUp();
    });


    // Handle saving the new section type from inline form
    $('#mcp-sections-repeater').on('click', '.mcp-save-new-section-type', function() {
        const $wrapper = $(this).closest('.mcp-inline-form-wrapper');
        const title = $wrapper.find('.mcp-new-section-type-title').val();
        const icon_class = $wrapper.find('.mcp-new-section-type-icon').val();
        const is_starred = $wrapper.find('.mcp-new-section-type-starred').is(':checked');
        const $button = $(this);
        $currentSelect2 = $wrapper.siblings('.mcp-section-type-select');

        if (!title) {
            alert('Title is required.');
            return;
        }

        $button.prop('disabled', true);

        $.ajax({
            url: mcp_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'mcp_create_section_type',
                nonce: mcp_ajax.create_section_type_nonce,
                title: title,
                icon_class: icon_class,
                is_starred: is_starred
            },
            success: function(response) {
                if (response.success) {
                    const optgroupLabel = is_starred ? 'Starred' : 'Other';

                    // Add the new option to all Select2 dropdowns on the page
                    $('.mcp-section-type-select').each(function() {
                        const $select = $(this);
                        let $optgroup = $select.find('optgroup[label="' + optgroupLabel + '"]');

                        if (!$optgroup.length) {
                            $optgroup = $('<optgroup label="' + optgroupLabel + '"></optgroup>');
                            if (optgroupLabel === 'Starred' && $select.find('optgroup[label="Other"]').length) {
                                $select.find('optgroup[label="Other"]').before($optgroup);
                            } else {
                                $select.append($optgroup);
                            }
                        }

                        const newOptionHTML = new Option(response.data.title, response.data.id).outerHTML;

                        if (!is_starred){
                           $optgroup.append($(newOptionHTML).addClass('non-starred-option'));
                        } else {
                           $optgroup.append(newOptionHTML);
                        }
                    });

                    // Set the value for the specific select that triggered the action and trigger change
                    $currentSelect2.val(response.data.id).trigger('change');

                    $wrapper.slideUp();
                    $wrapper.find('input[type="text"]').val('');
                    $wrapper.find('input[type="checkbox"]').prop('checked', false);

                } else {
                    alert('Error: ' + response.data.message);
                }
            },
            error: function() {
                alert('An unexpected error occurred.');
            },
            complete: function() {
                $button.prop('disabled', false);
            }
        });
    });

    // Re-initialize Select2 for new sections added by the repeater
    $('#mcp-add-section').on('click', function() {
        setTimeout(function() {
            $('.mcp-sections-container .mcp-section:last-child .mcp-section-type-select').each(function() {
                if (!$(this).data('select2')) {
                    initSelect2($(this));
                }
            });
        }, 100);
    });
});
