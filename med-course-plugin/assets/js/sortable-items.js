jQuery(document).ready(function($) {
    var sortableLists = $('#mcp-sortable-lessons, #mcp-sortable-topics');

    if (sortableLists.length) {
        sortableLists.sortable({
            placeholder: 'ui-state-highlight',
            update: function(event, ui) {
                var order = $(this).sortable('toArray', { attribute: 'data-id' });
                var nonce = mcp_sort_ajax.nonce;

                $.ajax({
                    url: mcp_sort_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'mcp_update_items_order',
                        order: order,
                        nonce: nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            console.log('Order updated successfully.');
                        } else {
                            alert('Error updating order: ' + response.data.message);
                        }
                    },
                    error: function() {
                        alert('Error updating order. Please try again.');
                    }
                });
            }
        });
        sortableLists.disableSelection();
    }
});
