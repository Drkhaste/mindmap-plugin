(function ($) {
    'use strict';

    var jmInstances  = {};
    var isUpdating   = false;
    var nodeStylesMap = {};  // { containerId: { nodeId: { color, dashed } } }

    var NODE_COLORS = [
        { key: 'default', label: 'پیش‌فرض', swatch: 'linear-gradient(135deg,#f8fafc,#e2e8f0)', border: '#94a3b8', text: '#1e293b' },
        { key: 'teal',    label: 'فیروزه‌ای', swatch: 'linear-gradient(135deg,#ccfbf1,#99f6e4)', border: '#2dd4bf', text: '#134e4a' },
        { key: 'blue',    label: 'آبی',       swatch: 'linear-gradient(135deg,#dbeafe,#bfdbfe)', border: '#60a5fa', text: '#1e3a8a' },
        { key: 'purple',  label: 'بنفش',      swatch: 'linear-gradient(135deg,#ede9fe,#ddd6fe)', border: '#a78bfa', text: '#4c1d95' },
        { key: 'pink',    label: 'صورتی',     swatch: 'linear-gradient(135deg,#fce7f3,#fbcfe8)', border: '#f472b6', text: '#831843' },
        { key: 'red',     label: 'قرمز',      swatch: 'linear-gradient(135deg,#fee2e2,#fecaca)', border: '#f87171', text: '#7f1d1d' },
        { key: 'orange',  label: 'نارنجی',    swatch: 'linear-gradient(135deg,#ffedd5,#fed7aa)', border: '#fb923c', text: '#7c2d12' },
        { key: 'yellow',  label: 'زرد',       swatch: 'linear-gradient(135deg,#fef9c3,#fef08a)', border: '#facc15', text: '#713f12' },
        { key: 'green',   label: 'سبز',       swatch: 'linear-gradient(135deg,#dcfce7,#bbf7d0)', border: '#4ade80', text: '#14532d' },
        { key: 'dark',    label: 'تیره',      swatch: 'linear-gradient(135deg,#334155,#1e293b)', border: '#475569', text: '#f1f5f9' },
    ];

    function init() {
        // Init existing mindmaps and load saved styles
        $('.mms-editor-wrapper').each(function () {
            var containerId = $(this).data('id');
            // Load saved node styles for this editor
            var savedStyles = $(this).find('.mms-node-styles-input').val();
            nodeStylesMap[containerId] = {};
            if (savedStyles) {
                try { nodeStylesMap[containerId] = JSON.parse(savedStyles); } catch (e) {}
            }
            generateMindMap(containerId);
        });

        // Textarea input sync
        $(document).on('input propertychange', '.mms-mindmap-data', function () {
            var containerId = $(this).closest('.mms-editor-wrapper').data('id');
            if (!isUpdating) generateMindMap(containerId);
        });

        // Layout toggle
        $(document).on('click', '.mms-btn-toggle-layout', function () {
            var $wrapper = $(this).closest('.mms-editor-wrapper');
            var $input   = $wrapper.find('.mms-layout-input');
            if ($input.val() === 'both') {
                $input.val('single');
                $(this).text('🌲 درختی یک طرفه').removeClass('button-primary');
            } else {
                $input.val('both');
                $(this).text('📏 چیدمان دو طرفه').addClass('button-primary');
            }
            generateMindMap($wrapper.data('id'));
        });

        // Template / Clear
        $(document).on('click', '.mms-btn-insert-template', function () {
            var $ta = $(this).closest('.mms-editor-wrapper').find('.mms-mindmap-data');
            $ta.val('مرکز\n  شاخه یک\n    زیرشاخه\n  شاخه دو');
            $ta.trigger('input');
        });

        $(document).on('click', '.mms-btn-clear-text', function () {
            var $ta = $(this).closest('.mms-editor-wrapper').find('.mms-mindmap-data');
            $ta.val('').trigger('input');
        });

        // Add child/sibling/delete
        $(document).on('click', '.mms-btn-add-child', function () {
            var containerId = $(this).closest('.mms-editor-wrapper').data('id');
            var jm = jmInstances[containerId];
            if (!jm) return;
            var sel = jm.get_selected_node();
            if (!sel) return;
            var id = 'n' + Date.now();
            var node = jm.add_node(sel, id, 'نود جدید');
            if (node) { jm.select_node(id); jm.begin_edit(id); }
        });

        $(document).on('click', '.mms-btn-add-sibling', function () {
            var containerId = $(this).closest('.mms-editor-wrapper').data('id');
            var jm = jmInstances[containerId];
            if (!jm) return;
            var sel = jm.get_selected_node();
            if (!sel || sel.isroot) return;
            var id = 'n' + Date.now();
            var node = jm.insert_node_after(sel, id, 'نود جدید');
            if (node) { jm.select_node(id); jm.begin_edit(id); }
        });

        $(document).on('click', '.mms-btn-delete-node', function () {
            var containerId = $(this).closest('.mms-editor-wrapper').data('id');
            var jm = jmInstances[containerId];
            if (!jm) return;
            var sel = jm.get_selected_node();
            if (!sel || sel.isroot) return;
            delete nodeStylesMap[containerId][sel.id];
            saveNodeStyles(containerId);
            jm.remove_node(sel);
            updateTextareaFromMap(containerId);
        });

        // Add Accordion
        $('#mms-add-accordion').on('click', function () {
            var index = $('.mms-accordion-item').length;
            var html = `
                <div class="mms-accordion-item" style="border: 1px solid #ccd0d4; padding: 15px; margin-bottom: 20px; background: #f9f9f9; border-radius: 8px;">
                    <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <input type="text" name="mms_accordions[${index}][title]" value="" placeholder="عنوان آکاردئون" style="width: 80%; font-weight: bold;">
                        <button type="button" class="button button-link-delete mms-remove-accordion" style="color: #d63638;">حذف آکاردئون</button>
                    </div>
                    <div class="mms-mindmaps-container" style="padding-right: 20px; border-right: 2px solid #ddd;"></div>
                    <button type="button" class="button button-secondary mms-add-mindmap">+ افزودن نقشه ذهنی جدید به این آکاردئون</button>
                </div>`;
            $('.mms-accordions-container').append(html);
        });

        // Add MindMap to accordion
        $(document).on('click', '.mms-add-mindmap', function () {
            var $accordion = $(this).closest('.mms-accordion-item');
            var aIndex     = $('.mms-accordion-item').index($accordion);
            var mIndex     = $accordion.find('.mms-mindmap-item').length;
            var uniqueId   = `mms_editor_${aIndex}_${mIndex}`;

            nodeStylesMap[uniqueId] = {};

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
                            <input type="hidden" name="mms_accordions[${aIndex}][mindmaps][${mIndex}][node_styles]" class="mms-node-styles-input" value="{}">
                            <div class="visual-edit-group" style="margin-right:20px; display:flex; gap:5px; border-right:1px solid #ccc; padding-right:15px;">
                                <button type="button" class="button button-secondary mms-btn-add-child"><span class="dashicons dashicons-plus-alt" style="margin-top:4px;"></span></button>
                                <button type="button" class="button button-secondary mms-btn-add-sibling"><span class="dashicons dashicons-plus" style="margin-top:4px;"></span></button>
                                <button type="button" class="button button-link-delete mms-btn-delete-node" style="color:#d63638;"><span class="dashicons dashicons-trash" style="margin-top:4px;"></span></button>
                            </div>
                            <span style="font-size:11px;color:#888;margin-right:auto;">کلیک روی نود → تغییر رنگ/خط</span>
                        </div>
                        <div style="display:flex;gap:10px;">
                            <textarea name="mms_accordions[${aIndex}][mindmaps][${mIndex}][data]" class="mms-mindmap-data" style="width:30%; height:300px; font-family: monospace; direction: ltr;"></textarea>
                            <div class="mms-jsmind-container" id="${uniqueId}" style="flex:1; height:300px; border:1px solid #ccc; background:#fff; border-radius:8px;"></div>
                        </div>
                    </div>
                </div>`;
            $accordion.find('.mms-mindmaps-container').append(html);
            generateMindMap(uniqueId);
        });

        $(document).on('click', '.mms-remove-accordion', function () {
            if (confirm('آیا از حذف این آکاردئون و تمام نقشه‌های آن مطمئن هستید؟')) {
                $(this).closest('.mms-accordion-item').remove();
            }
        });

        $(document).on('click', '.mms-remove-mindmap', function () {
            if (confirm('آیا از حذف این نقشه مطمئن هستید؟')) {
                $(this).closest('.mms-mindmap-item').remove();
            }
        });

        // Close toolbar on outside click
        $(document).on('click.mmsTopicToolbar', function (e) {
            if (!$(e.target).closest('.mms-node-toolbar').length &&
                !$(e.target).closest('jmnode').length) {
                hideNodeToolbar();
            }
        });
    }

    /* ── Generate / Re-render map ── */
    function generateMindMap(containerId) {
        var $wrapper = $(`.mms-editor-wrapper[data-id="${containerId}"]`);
        var text     = $wrapper.find('.mms-mindmap-data').val();
        var layout   = $wrapper.find('.mms-layout-input').val() || 'both';
        var container = document.getElementById(containerId);

        if (!container) return;
        container.innerHTML = '';
        if (!text || !text.trim()) return;

        var nodes    = parseNodes(text, layout);
        var settings = (typeof mindMapStudioSettings !== 'undefined') ? mindMapStudioSettings : {};

        var opts = {
            container : containerId,
            theme     : settings.theme || 'primary',
            mode      : 'full',
            editable  : true,
            view      : {
                engine     : 'svg',
                line_width : settings.line_width || 2,
                line_color : '#94a3b8',
                line_style : settings.line_style || 'bezier'
            },
            layout : { hspace: 46, vspace: 16, pspace: 12 }
        };

        var jm = new jsMind(opts);
        jm.show({ meta: { name: 'Map' }, format: 'node_array', data: nodes });
        jmInstances[containerId] = jm;

        // Apply saved styles after render
        setTimeout(function () {
            applyAllNodeStyles(containerId);
            applyAllLineStyles(containerId);
            attachNodeClickHandlers(containerId);
        }, 180);

        jm.add_event_listener(function (type, data) {
            if (type === 3 && !isUpdating) {
                updateTextareaFromMap(containerId);
            }
            if (type === 3) {
                setTimeout(function () {
                    applyAllNodeStyles(containerId);
                    applyAllLineStyles(containerId);
                    attachNodeClickHandlers(containerId);
                }, 200);
            }
            if (type === 4 && data && data.node) {
                setTimeout(function () { showNodeToolbar(containerId, data.node); }, 50);
            }
        });
    }

    /* ── Click handlers on jmnodes ── */
    function attachNodeClickHandlers(containerId) {
        var $container = $('#' + containerId);
        $container.find('jmnode').off('click.mmsStyle').on('click.mmsStyle', function (e) {
            var nodeId = $(this).attr('nodeid');
            if (nodeId) showNodeToolbar(containerId, nodeId);
        });
    }

    /* ── Node toolbar ── */
    function showNodeToolbar(containerId, nodeId) {
        if (!nodeId) return;
        var jm = jmInstances[containerId];
        if (!jm) return;
        var node = jm.get_node(nodeId);
        if (!node) return;

        hideNodeToolbar();

        var styles  = (nodeStylesMap[containerId] || {})[nodeId] || { color: 'default', dashed: false };

        var swatchesHtml = NODE_COLORS.map(function (c) {
            var isActive = (c.key === (styles.color || 'default'));
            return '<div class="mms-color-swatch ' + (isActive ? 'active' : '') + '" ' +
                'data-color="' + c.key + '" title="' + c.label + '" ' +
                'style="background:' + c.swatch + ';border-color:' + c.border + ';"></div>';
        }).join('');

        var isDashed = styles.dashed === 'dashed';
        var isDotted = styles.dashed === 'dotted';

        var html = '<div class="mms-node-toolbar" id="mms-node-toolbar" ' +
            'data-nodeid="' + nodeId + '" data-containerid="' + containerId + '">' +
            '<div class="mms-node-toolbar-title">رنگ نود</div>' +
            '<div style="display:flex;flex-wrap:wrap;gap:5px;align-items:center;">' +
            swatchesHtml +
            '<div class="mms-node-toolbar-sep"></div>' +
            '<div class="mms-toolbar-icon-btn ' + (isDashed ? 'active' : '') + '" id="mms-btn-dashed" title="خط چین">╌</div>' +
            '<div class="mms-toolbar-icon-btn ' + (isDotted ? 'active' : '') + '" id="mms-btn-dotted" title="نقطه‌چین">···</div>' +
            '<div class="mms-toolbar-icon-btn" id="mms-btn-solid" title="خط معمولی">—</div>' +
            '</div>' +
            '</div>';

        $('body').append(html);

        // Position
        var $nodeEl = $('#' + containerId + ' jmnode[nodeid="' + nodeId + '"]');
        if ($nodeEl.length) {
            var rect   = $nodeEl[0].getBoundingClientRect();
            var tbW    = 290;
            var left   = rect.left;
            var top    = rect.bottom + 8;
            if (left + tbW > window.innerWidth - 10) left = window.innerWidth - tbW - 10;
            if (top + 80 > window.innerHeight) top = rect.top - 80;
            $('#mms-node-toolbar').css({ top: top, left: left });
        }

        // Bind
        $('#mms-node-toolbar .mms-color-swatch').on('click', function (e) {
            e.stopPropagation();
            var colorKey = $(this).data('color');
            applyNodeColor(containerId, nodeId, colorKey);
            $('#mms-node-toolbar .mms-color-swatch').removeClass('active');
            $(this).addClass('active');
        });

        $('#mms-btn-dashed').on('click', function (e) {
            e.stopPropagation();
            var cur = (nodeStylesMap[containerId] || {})[nodeId] && nodeStylesMap[containerId][nodeId].dashed;
            applyLineDash(containerId, nodeId, cur === 'dashed' ? false : 'dashed');
            updateLineButtons(containerId, nodeId);
        });

        $('#mms-btn-dotted').on('click', function (e) {
            e.stopPropagation();
            var cur = (nodeStylesMap[containerId] || {})[nodeId] && nodeStylesMap[containerId][nodeId].dashed;
            applyLineDash(containerId, nodeId, cur === 'dotted' ? false : 'dotted');
            updateLineButtons(containerId, nodeId);
        });

        $('#mms-btn-solid').on('click', function (e) {
            e.stopPropagation();
            applyLineDash(containerId, nodeId, false);
            updateLineButtons(containerId, nodeId);
        });
    }

    function updateLineButtons(containerId, nodeId) {
        var styles = (nodeStylesMap[containerId] || {})[nodeId] || {};
        $('#mms-btn-dashed').toggleClass('active', styles.dashed === 'dashed');
        $('#mms-btn-dotted').toggleClass('active', styles.dashed === 'dotted');
    }

    function hideNodeToolbar() {
        $('#mms-node-toolbar').remove();
    }

    /* ── Apply color ── */
    function applyNodeColor(containerId, nodeId, colorKey) {
        if (!nodeStylesMap[containerId]) nodeStylesMap[containerId] = {};
        if (!nodeStylesMap[containerId][nodeId]) nodeStylesMap[containerId][nodeId] = {};
        nodeStylesMap[containerId][nodeId].color = colorKey;
        saveNodeStyles(containerId);

        var c   = NODE_COLORS.find(function (x) { return x.key === colorKey; });
        var $el = $('#' + containerId + ' jmnode[nodeid="' + nodeId + '"]');

        if (!c || colorKey === 'default') {
            $el.css({ background: '', color: '', borderColor: '' });
        } else {
            $el.css({ background: c.swatch, color: c.text, borderColor: c.border });
        }
    }

    /* ── Apply dash style ── */
    function applyLineDash(containerId, nodeId, dashType) {
        if (!nodeStylesMap[containerId]) nodeStylesMap[containerId] = {};
        if (!nodeStylesMap[containerId][nodeId]) nodeStylesMap[containerId][nodeId] = {};
        nodeStylesMap[containerId][nodeId].dashed = dashType;
        saveNodeStyles(containerId);
        applyAllLineStyles(containerId);
    }

    /* ── Apply all saved colours ── */
    function applyAllNodeStyles(containerId) {
        var styles = nodeStylesMap[containerId] || {};
        Object.keys(styles).forEach(function (nodeId) {
            var s = styles[nodeId];
            if (!s || !s.color) return;
            applyNodeColor(containerId, nodeId, s.color);
        });
    }

    /* ── Apply all line dash styles ── */
    function applyAllLineStyles(containerId) {
        var $svg = $('#' + containerId + ' svg.jsmind');
        if (!$svg.length) return;

        $svg.find('path').css('stroke-dasharray', '');

        var jm     = jmInstances[containerId];
        var styles = nodeStylesMap[containerId] || {};
        if (!jm || !jm.mind) return;

        var allNodes = Object.values(jm.mind.nodes).filter(function (n) { return !n.isroot; });
        var $paths   = $svg.find('path');

        allNodes.forEach(function (node, idx) {
            var s = styles[node.id];
            if (!s || !s.dashed) return;
            var $p = $paths.eq(idx);
            if (!$p.length) return;
            $p.css('stroke-dasharray', s.dashed === 'dashed' ? '7 4' : '2 5');
        });
    }

    /* ── Persist to hidden field ── */
    function saveNodeStyles(containerId) {
        var $wrapper = $(`.mms-editor-wrapper[data-id="${containerId}"]`);
        $wrapper.find('.mms-node-styles-input').val(JSON.stringify(nodeStylesMap[containerId] || {}));
    }

    /* ── Parse nodes ── */
    function parseNodes(text, layout) {
        var lines    = text.split('\n');
        var nodes    = [], stack = [], rootDone = false, rightTurn = true;

        for (var i = 0; i < lines.length; i++) {
            var raw     = lines[i];
            var trimmed = raw.trim();
            if (!trimmed) continue;

            var indent = 0;
            while (indent < raw.length && (raw[indent] === ' ' || raw[indent] === '\t')) indent++;
            var node = { id: 'n' + i, topic: trimmed, indent: indent };

            if (!rootDone) { node.isroot = true; rootDone = true; stack = [node]; nodes.push(node); continue; }

            while (stack.length && stack[stack.length - 1].indent >= indent) stack.pop();
            var parent    = stack.length ? stack[stack.length - 1] : nodes[0];
            node.parentid = parent.id;

            if (parent.isroot) {
                node.direction = (layout === 'both') ? (rightTurn ? 'right' : 'left') : 'right';
                if (layout === 'both') rightTurn = !rightTurn;
            }
            stack.push(node); nodes.push(node);
        }
        return nodes;
    }

    /* ── Sync map→textarea ── */
    function updateTextareaFromMap(containerId) {
        var jm = jmInstances[containerId];
        if (!jm) return;
        var text = serializeToText(jm.get_data('node_array').data);
        isUpdating = true;
        $(`.mms-editor-wrapper[data-id="${containerId}"]`).find('.mms-mindmap-data').val(text).trigger('input');
        isUpdating = false;
    }

    function serializeToText(nodes) {
        if (!nodes || !nodes.length) return '';
        var root = nodes.find(function (n) { return n.isroot; });
        if (!root) return '';
        var text = root.topic + '\n';
        function walk(parentId, level) {
            nodes.filter(function (n) { return n.parentid === parentId; })
                .forEach(function (child) {
                    text += '  '.repeat(level) + child.topic + '\n';
                    walk(child.id, level + 1);
                });
        }
        walk(root.id, 1);
        return text.trim();
    }

    $(document).ready(init);

})(jQuery);
