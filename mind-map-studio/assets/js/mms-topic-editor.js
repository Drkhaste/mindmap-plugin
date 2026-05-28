(function ($) {
    'use strict';

    var jmInstances = {};
    var isUpdating = false;

    function init() {
        // Initialize existing mindmaps
        $('.mms-editor-wrapper').each(function() {
            var containerId = $(this).data('id');
            generateMindMap(containerId);
        });

        // Event delegation for dynamic elements
        $(document).on('input propertychange', '.mms-mindmap-data', function() {
            var containerId = $(this).closest('.mms-editor-wrapper').data('id');
            if (!isUpdating) generateMindMap(containerId);
        });

        $(document).on('click', '.mms-btn-toggle-layout', function() {
            var $wrapper = $(this).closest('.mms-editor-wrapper');
            var $input = $wrapper.find('.mms-layout-input');
            var current = $input.val();
            if (current === 'both') {
                $input.val('single');
                $(this).text('🌲 درختی یک طرفه').removeClass('button-primary');
            } else {
                $input.val('both');
                $(this).text('📏 چیدمان دو طرفه').addClass('button-primary');
            }
            generateMindMap($wrapper.data('id'));
        });

        $(document).on('click', '.mms-btn-insert-template', function() {
            var $textarea = $(this).closest('.mms-editor-wrapper').find('.mms-mindmap-data');
            $textarea.val('مرکز\n  شاخه یک\n    زیرشاخه\n  شاخه دو');
            $textarea.trigger('input');
        });

        $(document).on('click', '.mms-btn-clear-text', function() {
            var $textarea = $(this).closest('.mms-editor-wrapper').find('.mms-mindmap-data');
            $textarea.val('').trigger('input');
        });

        $(document).on('click', '.mms-btn-add-child', function() {
            var containerId = $(this).closest('.mms-editor-wrapper').data('id');
            var jm = jmInstances[containerId];
            if (!jm) return;
            var selected_node = jm.get_selected_node();
            if (!selected_node) return;
            var nodeid = 'n' + Date.now();
            var node = jm.add_node(selected_node, nodeid, 'نود جدید');
            if (node) {
                jm.select_node(nodeid);
                jm.begin_edit(nodeid);
            }
        });

        $(document).on('click', '.mms-btn-add-sibling', function() {
            var containerId = $(this).closest('.mms-editor-wrapper').data('id');
            var jm = jmInstances[containerId];
            if (!jm) return;
            var selected_node = jm.get_selected_node();
            if (!selected_node || selected_node.isroot) return;
            var nodeid = 'n' + Date.now();
            var node = jm.insert_node_after(selected_node, nodeid, 'نود جدید');
            if (node) {
                jm.select_node(nodeid);
                jm.begin_edit(nodeid);
            }
        });

        $(document).on('click', '.mms-btn-delete-node', function() {
            var containerId = $(this).closest('.mms-editor-wrapper').data('id');
            var jm = jmInstances[containerId];
            if (!jm || !jm.get_selected_node() || jm.get_selected_node().isroot) return;
            jm.remove_node(jm.get_selected_node());
            updateTextareaFromMap(containerId);
        });

        // Add Accordion
        $('#mms-add-accordion').on('click', function() {
            var index = $('.mms-accordion-item').length;
            var html = `
                <div class="mms-accordion-item" style="border: 1px solid #ccd0d4; padding: 15px; margin-bottom: 20px; background: #f9f9f9; border-radius: 8px;">
                    <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <input type="text" name="mms_accordions[${index}][title]" value="" placeholder="عنوان آکاردئون" style="width: 80%; font-weight: bold;">
                        <button type="button" class="button button-link-delete mms-remove-accordion" style="color: #d63638;">حذف آکاردئون</button>
                    </div>
                    <div class="mms-mindmaps-container" style="padding-right: 20px; border-right: 2px solid #ddd;"></div>
                    <button type="button" class="button button-secondary mms-add-mindmap">+ افزودن نقشه ذهنی جدید به این آکاردئون</button>
                </div>
            `;
            $('.mms-accordions-container').append(html);
        });

        // Add MindMap
        $(document).on('click', '.mms-add-mindmap', function() {
            var $accordion = $(this).closest('.mms-accordion-item');
            var aIndex = $('.mms-accordion-item').index($accordion);
            var mIndex = $accordion.find('.mms-mindmap-item').length;
            var uniqueId = `mms_editor_${aIndex}_${mIndex}`;

            var html = `
                <div class="mms-mindmap-item" style="margin-bottom: 30px; border: 1px solid #eee; padding: 10px; background: #fff;">
                    <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <input type="text" name="mms_accordions[${aIndex}][mindmaps][${mIndex}][title]" value="" placeholder="عنوان نقشه" style="width: 70%;">
                        <button type="button" class="button button-link-delete mms-remove-mindmap" style="color: #d63638;">حذف نقشه</button>
                    </div>
                    <div class="mms-editor-wrapper" data-id="${uniqueId}">
                        <div class="mindmap-toolbar" style="margin-bottom:10px;display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
                            <button type="button" class="button mms-btn-insert-template">درج قالب</button>
                            <button type="button" class="button mms-btn-clear-text">پاکسازی</button>
                            <button type="button" class="button button-primary mms-btn-toggle-layout">📏 چیدمان دو طرفه</button>
                            <input type="hidden" name="mms_accordions[${aIndex}][mindmaps][${mIndex}][layout]" class="mms-layout-input" value="both">
                            <div class="visual-edit-group" style="margin-right:20px; display:flex; gap:5px; border-right:1px solid #ccc; padding-right:15px;">
                                <button type="button" class="button button-secondary mms-btn-add-child"><span class="dashicons dashicons-plus-alt" style="margin-top:4px;"></span></button>
                                <button type="button" class="button button-secondary mms-btn-add-sibling"><span class="dashicons dashicons-plus" style="margin-top:4px;"></span></button>
                                <button type="button" class="button button-link-delete mms-btn-delete-node" style="color:#d63638;"><span class="dashicons dashicons-trash" style="margin-top:4px;"></span></button>
                            </div>
                        </div>
                        <div style="display:flex;gap:10px;">
                            <textarea name="mms_accordions[${aIndex}][mindmaps][${mIndex}][data]" class="mms-mindmap-data" style="width:30%; height:300px; font-family: monospace; direction: ltr;"></textarea>
                            <div class="mms-jsmind-container" id="${uniqueId}" style="flex:1; height:300px; border:1px solid #ccc; background:#fff;"></div>
                        </div>
                    </div>
                </div>
            `;
            $accordion.find('.mms-mindmaps-container').append(html);
            generateMindMap(uniqueId);
        });

        $(document).on('click', '.mms-remove-accordion', function() {
            if (confirm('آیا از حذف این آکاردئون و تمام نقشه‌های آن مطمئن هستید؟')) {
                $(this).closest('.mms-accordion-item').remove();
            }
        });

        $(document).on('click', '.mms-remove-mindmap', function() {
            if (confirm('آیا از حذف این نقشه مطمئن هستید؟')) {
                $(this).closest('.mms-mindmap-item').remove();
            }
        });
    }

    function generateMindMap(containerId) {
        var $wrapper = $(`.mms-editor-wrapper[data-id="${containerId}"]`);
        var text = $wrapper.find('.mms-mindmap-data').val();
        var layout = $wrapper.find('.mms-layout-input').val() || 'both';
        var container = document.getElementById(containerId);

        if (!container) return;
        container.innerHTML = '';
        if (!text || !text.trim()) return;

        var nodes = parseNodes(text, layout);
        var settings = (typeof mindMapStudioSettings !== 'undefined') ? mindMapStudioSettings : {};

        var opts = {
            container : containerId,
            theme     : settings.theme || 'primary',
            mode      : 'full',
            editable  : true,
            view      : {
                engine: 'svg',
                line_width: settings.line_width || 2,
                line_style: settings.line_style || 'bezier'
            }
        };

        var mind = {
            meta: { name: 'Map' },
            format: 'node_array',
            data: nodes
        };

        var jm = new jsMind(opts);
        jm.show(mind);
        jmInstances[containerId] = jm;

        jm.add_event_listener(function(type, data) {
            if (type === 3 && !isUpdating) {
                updateTextareaFromMap(containerId);
            }
        });
    }

    function parseNodes(text, layout) {
        var lines = text.split('\n');
        var nodes = [];
        var stack = [];
        var rootDone = false;
        var rightTurn = true;

        for (var i = 0; i < lines.length; i++) {
            var raw = lines[i];
            var trimmed = raw.trim();
            if (!trimmed) continue;

            var indent = 0;
            while (indent < raw.length && (raw[indent] === ' ' || raw[indent] === '\t')) indent++;

            var node = { id: 'n' + i, topic: trimmed, indent: indent };

            if (!rootDone) {
                node.isroot = true;
                rootDone = true;
                stack = [node];
                nodes.push(node);
                continue;
            }

            while (stack.length && stack[stack.length - 1].indent >= indent) stack.pop();
            var parent = stack.length ? stack[stack.length - 1] : nodes[0];
            node.parentid = parent.id;

            if (parent.isroot) {
                node.direction = (layout === 'both') ? (rightTurn ? 'right' : 'left') : 'right';
                if (layout === 'both') rightTurn = !rightTurn;
            }

            stack.push(node);
            nodes.push(node);
        }
        return nodes;
    }

    function updateTextareaFromMap(containerId) {
        var jm = jmInstances[containerId];
        if (!jm) return;
        var mind_data = jm.get_data('node_array');
        var text = serializeToText(mind_data.data);
        isUpdating = true;
        $(`.mms-editor-wrapper[data-id="${containerId}"]`).find('.mms-mindmap-data').val(text).trigger('input');
        isUpdating = false;
    }

    function serializeToText(nodes) {
        if (!nodes || nodes.length === 0) return '';
        var root = nodes.find(function(n) { return n.isroot; });
        if (!root) return '';
        var text = root.topic + '\n';
        function walk(parentId, level) {
            var children = nodes.filter(function(n) { return n.parentid === parentId; });
            children.forEach(function(child) {
                text += '  '.repeat(level) + child.topic + '\n';
                walk(child.id, level + 1);
            });
        }
        walk(root.id, 1);
        return text.trim();
    }

    $(document).ready(init);

})(jQuery);
