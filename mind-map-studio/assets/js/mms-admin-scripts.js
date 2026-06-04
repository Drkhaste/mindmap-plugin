jQuery(document).ready(function($) {
    if ($('.mms-sortable-items').length) {
        $('.mms-sortable-items').sortable({
            update: function(event, ui) {
                var order = [];
                $(this).children('li').each(function() {
                    order.push($(this).data('id'));
                });
                $.post(ajaxurl, {
                    action : 'mms_update_order',
                    order  : order,
                    nonce  : $('input[name="mms_order_nonce"]').val()
                });
            }
        });
    }
});
