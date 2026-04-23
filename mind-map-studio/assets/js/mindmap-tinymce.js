(function () {
    tinymce.create('tinymce.plugins.mind_map', {
        init: function (ed) {
            ed.addButton('mind_map', {
                title: 'درج نقشه ذهنی',
                onclick: function () {
                    var nonce = (typeof window.mind_map_vars !== 'undefined') ? window.mind_map_vars.nonce : '';
                    jQuery.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: { action: 'mind_map_get_list', security: nonce },
                        success: function (response) {
                            if (response.success && response.data.length > 0) {
                                var values = response.data.map(function (item) {
                                    return { text: item.title, value: String(item.id) };
                                });
                                ed.windowManager.open({
                                    title: 'انتخاب نقشه ذهنی',
                                    body: [{ type: 'listbox', name: 'mindmap_id', label: 'نام نقشه', values: values }],
                                    onsubmit: function (e) {
                                        if (e.data.mindmap_id) {
                                            ed.insertContent('[mindmap id="' + e.data.mindmap_id + '"]');
                                        }
                                    }
                                });
                            } else {
                                alert('نقشه‌ای یافت نشد. ابتدا یک نقشه در بخش نقشه‌ساز ذهنی بسازید.');
                            }
                        }
                    });
                }
            });
        }
    });
    tinymce.PluginManager.add('mind_map', tinymce.plugins.mind_map);
}());
