(function ($) {
    'use strict';

    var jm          = null;
    var mapDirection = 'both';
    var isUpdating   = false;

    // Track per-node custom styles: { nodeId: { color, dashed } }
    var nodeStyles   = {};

    // Color palette
    var NODE_COLORS = [
        { key: 'default', label: 'پیش‌فرض', bg: '#ffffff', border: '#94a3b8', text: '#1e293b', swatch: 'linear-gradient(135deg,#f8fafc,#e2e8f0)' },
        { key: 'teal',    label: 'فیروزه‌ای',  bg: '#ccfbf1', border: '#2dd4bf', text: '#134e4a', swatch: 'linear-gradient(135deg,#ccfbf1,#99f6e4)' },
        { key: 'blue',    label: 'آبی',       bg: '#dbeafe', border: '#60a5fa', text: '#1e3a8a', swatch: 'linear-gradient(135deg,#dbeafe,#bfdbfe)' },
        { key: 'purple',  label: 'بنفش',      bg: '#ede9fe', border: '#a78bfa', text: '#4c1d95', swatch: 'linear-gradient(135deg,#ede9fe,#ddd6fe)' },
        { key: 'pink',    label: 'صورتی',     bg: '#fce7f3', border: '#f472b6', text: '#831843', swatch: 'linear-gradient(135deg,#fce7f3,#fbcfe8)' },
        { key: 'red',     label: 'قرمز',      bg: '#fee2e2', border: '#f87171', text: '#7f1d1d', swatch: 'linear-gradient(135deg,#fee2e2,#fecaca)' },
        { key: 'orange',  label: 'نارنجی',    bg: '#ffedd5', border: '#fb923c', text: '#7c2d12', swatch: 'linear-gradient(135deg,#ffedd5,#fed7aa)' },
        { key: 'yellow',  label: 'زرد',       bg: '#fef9c3', border: '#facc15', text: '#713f12', swatch: 'linear-gradient(135deg,#fef9c3,#fef08a)' },
        { key: 'green',   label: 'سبز',       bg: '#dcfce7', border: '#4ade80', text: '#14532d', swatch: 'linear-gradient(135deg,#dcfce7,#bbf7d0)' },
        { key: 'dark',    label: 'تیره',      bg: '#1e293b', border: '#475569', text: '#f1f5f9', swatch: 'linear-gradient(135deg,#334155,#1e293b)' },
    ];

    /* ── init ── */
    function init() {
        if (!$('#mind_map_data').length) return;

        mapDirection = $('#mind_map_layout').val() || 'both';

        // Load saved node styles
        var savedStyles = $('#mind_map_node_styles').val();
        if (savedStyles) {
            try { nodeStyles = JSON.parse(savedStyles); } catch(e) {}
        }

        setTimeout(function () {
            generateMindMap();
            updateWatermark();
        }, 400);

        $('#mind_map_data').on('input propertychange', function () {
            if (!isUpdating) generateMindMap();
        });
        $('#mind_map_data').on('keydown', handleTab);

        $('#btn-insert-template').on('click', function () {
            insertTemplate();
            $('#mind_map_data').trigger('input');
        });
        $('#btn-clear-text').on('click', function () {
            $('#mind_map_data').val('');
            $('#mind_map_data').trigger('input');
        });
        $('#btn-toggle-layout').on('click', toggleLayout);
        $('#zoom-in').on('click',  function () { if (jm) jm.view.zoomIn();  });
        $('#zoom-out').on('click', function () { if (jm) jm.view.zoomOut(); });

        $('#btn-add-child').on('click', addChild);
        $('#btn-add-sibling').on('click', addSibling);
        $('#btn-delete-node').on('click', deleteNode);

        // Close toolbar on outside click
        $(document).on('click.mmsToolbar', function(e) {
            if (!$(e.target).closest('.mms-node-toolbar').length && !$(e.target).closest('jmnode').length) {
                hideNodeToolbar();
            }
        });
    }

    /* ── Tab key handler ── */
    function handleTab(e) {
        if (e.key !== 'Tab') return;
        e.preventDefault();
        var el    = this;
        var start = el.selectionStart;
        var end   = el.selectionEnd;
        var val   = $(el).val();
        if (!e.shiftKey) {
            $(el).val(val.substring(0, start) + '  ' + val.substring(end));
            el.selectionStart = el.selectionEnd = start + 2;
        } else {
            var before = val.substring(0, start);
            if (before.endsWith('  ')) {
                $(el).val(before.slice(0, -2) + val.substring(end));
                el.selectionStart = el.selectionEnd = start - 2;
            }
        }
        $(el).trigger('input');
    }

    /* ── layout toggle ── */
    function toggleLayout() {
        var $btn = $('#btn-toggle-layout');
        if (mapDirection === 'both') {
            mapDirection = 'single';
            $btn.text('🌲 درختی یک طرفه').removeClass('button-primary');
        } else {
            mapDirection = 'both';
            $btn.text('📏 چیدمان دو طرفه').addClass('button-primary');
        }
        $('#mind_map_layout').val(mapDirection);
        generateMindMap();
    }

    /* ── parse text → nodes ── */
    function parseNodes(text) {
        var lines     = text.split('\n');
        var nodes     = [];
        var stack     = [];
        var rootDone  = false;
        var rightTurn = true;

        for (var i = 0; i < lines.length; i++) {
            var raw     = lines[i];
            var trimmed = raw.trim();
            if (!trimmed) continue;

            var indent = 0;
            while (indent < raw.length && (raw[indent] === ' ' || raw[indent] === '\t')) indent++;

            var node = { id: 'n' + i, topic: trimmed, indent: indent };

            if (!rootDone) {
                node.isroot = true;
                rootDone    = true;
                stack       = [node];
                nodes.push(node);
                continue;
            }

            while (stack.length && stack[stack.length - 1].indent >= indent) stack.pop();
            var parent    = stack.length ? stack[stack.length - 1] : nodes[0];
            node.parentid = parent.id;

            if (parent.isroot) {
                node.direction = (mapDirection === 'both')
                    ? (rightTurn ? 'right' : 'left')
                    : 'right';
                if (mapDirection === 'both') rightTurn = !rightTurn;
            }

            stack.push(node);
            nodes.push(node);
        }
        return nodes;
    }

    /* ── render ── */
    function generateMindMap() {
        var text      = $('#mind_map_data').val();
        var container = document.getElementById('jsmind_container');
        if (!container) return;

        container.innerHTML = '';
        if (!text || !text.trim()) return;

        if (typeof jsMind === 'undefined') {
            console.error('[MindMapStudio] jsMind not loaded');
            return;
        }

        var nodes = parseNodes(text);
        if (!nodes.length) return;

        var settings = (typeof mindMapStudioSettings !== 'undefined') ? mindMapStudioSettings : {};
        var theme = settings.theme || 'primary';

        var opts = {
            container : 'jsmind_container',
            theme     : theme,
            mode      : 'full',
            editable  : true,
            view      : {
                engine    : 'svg',
                line_width : settings.line_width || 2,
                line_color : '#94a3b8',
                line_style : settings.line_style || 'bezier'
            },
            layout    : { hspace: 46, vspace: 16, pspace: 12 }
        };

        var mind = {
            meta   : { name: 'Map', author: 'Mind Map Studio', version: '1.0' },
            format : 'node_array',
            data   : nodes
        };

        try {
            jm = new jsMind(opts);
            jm.show(mind);

            applyLineStyleOverrides();

            // Apply saved node styles after render
            setTimeout(function() {
                applyAllNodeStyles();
                applyAllLineStyles();
                attachNodeClickHandlers();
            }, 150);

            jm.add_event_listener(function(type, data) {
                if (type === 3 && !isUpdating) {
                    updateTextareaFromMap();
                }
                // Re-attach handlers after edit events
                if (type === 3) {
                    setTimeout(function() {
                        applyAllNodeStyles();
                        applyAllLineStyles();
                        attachNodeClickHandlers();
                    }, 200);
                }
                // On select, show toolbar
                if (type === 4 && data && data.node) {
                    setTimeout(function() {
                        showNodeToolbar(data.node);
                    }, 50);
                }
            });

        } catch (e) {
            console.error('[MindMapStudio] jsMind error:', e);
        }
    }

    /* ── Attach click handlers to nodes ── */
    function attachNodeClickHandlers() {
        $('#jsmind_container jmnode').off('click.mmsStyle').on('click.mmsStyle', function(e) {
            var nodeId = $(this).attr('nodeid');
            if (nodeId) {
                showNodeToolbar(nodeId);
            }
        });
    }

    /* ══════════════════════════════════════════
       NODE TOOLBAR
    ══════════════════════════════════════════ */
    function showNodeToolbar(nodeId) {
        if (!nodeId || !jm) return;
        var node = jm.get_node(nodeId);
        if (!node) return;

        hideNodeToolbar();

        var style = nodeStyles[nodeId] || { color: 'default', dashed: false };

        // Build toolbar HTML
        var swatchesHtml = NODE_COLORS.map(function(c) {
            var isActive = (c.key === (style.color || 'default'));
            return '<div class="mms-color-swatch ' + (isActive ? 'active' : '') + '" ' +
                   'data-color="' + c.key + '" title="' + c.label + '" ' +
                   'style="background:' + c.swatch + '; border-color:' + c.border + ';"></div>';
        }).join('');

        var isDashed = style.dashed === true || style.dashed === 'dashed';
        var isDotted = style.dashed === 'dotted';

        var html = '<div class="mms-node-toolbar" id="mms-node-toolbar" data-nodeid="' + nodeId + '">' +
            '<div class="mms-node-toolbar-title">رنگ نود</div>' +
            '<div style="display:flex;flex-wrap:wrap;gap:5px;align-items:center;">' +
            swatchesHtml +
            '<div class="mms-node-toolbar-sep"></div>' +
            '<div class="mms-toolbar-icon-btn ' + (isDashed ? 'active' : '') + '" id="mms-btn-dashed" title="خط چین">╌</div>' +
            '<div class="mms-toolbar-icon-btn ' + (isDotted ? 'active' : '') + '" id="mms-btn-dotted" title="نقطه‌چین">···</div>' +
            '<div class="mms-toolbar-icon-btn" id="mms-btn-solid" title="خط ساده">—</div>' +
            '<div class="mms-node-toolbar-sep"></div>' +
            '<div class="mms-toolbar-icon-btn" id="mms-btn-delete-node-tb" title="حذف نود" style="color:#f87171;">✕</div>' +
            '</div>' +
            '</div>';

        $('body').append(html);

        // Position near the selected node element
        var $nodeEl = $('#jsmind_container jmnode[nodeid="' + nodeId + '"]');
        positionToolbar($nodeEl);

        // Bind events
        $('#mms-node-toolbar .mms-color-swatch').on('click', function(e) {
            e.stopPropagation();
            var colorKey = $(this).data('color');
            applyNodeColor(nodeId, colorKey);
            $('#mms-node-toolbar .mms-color-swatch').removeClass('active');
            $(this).addClass('active');
        });

        $('#mms-btn-dashed').on('click', function(e) {
            e.stopPropagation();
            var current = nodeStyles[nodeId] && nodeStyles[nodeId].dashed;
            applyNodeLineDash(nodeId, current === 'dashed' ? false : 'dashed');
            updateToolbarLineButtons(nodeId);
        });

        $('#mms-btn-dotted').on('click', function(e) {
            e.stopPropagation();
            var current = nodeStyles[nodeId] && nodeStyles[nodeId].dashed;
            applyNodeLineDash(nodeId, current === 'dotted' ? false : 'dotted');
            updateToolbarLineButtons(nodeId);
        });

        $('#mms-btn-solid').on('click', function(e) {
            e.stopPropagation();
            applyNodeLineDash(nodeId, false);
            updateToolbarLineButtons(nodeId);
        });

        $('#mms-btn-delete-node-tb').on('click', function(e) {
            e.stopPropagation();
            deleteNode();
            hideNodeToolbar();
        });
    }

    function positionToolbar($nodeEl) {
        var $toolbar = $('#mms-node-toolbar');
        if (!$toolbar.length) return;

        var containerRect = document.getElementById('jsmind_container').getBoundingClientRect();

        if ($nodeEl && $nodeEl.length) {
            var nodeRect = $nodeEl[0].getBoundingClientRect();
            var top  = nodeRect.bottom + 8;
            var left = nodeRect.left;

            // Clamp to viewport
            var tbW = $toolbar.outerWidth() || 280;
            if (left + tbW > window.innerWidth - 10) {
                left = window.innerWidth - tbW - 10;
            }
            if (top + 80 > window.innerHeight) {
                top = nodeRect.top - 80;
            }

            $toolbar.css({ top: top + 'px', left: left + 'px' });
        } else {
            $toolbar.css({ top: '50%', left: '50%', transform: 'translate(-50%,-50%)' });
        }
    }

    function updateToolbarLineButtons(nodeId) {
        var style = nodeStyles[nodeId] || {};
        $('#mms-btn-dashed').toggleClass('active', style.dashed === 'dashed');
        $('#mms-btn-dotted').toggleClass('active', style.dashed === 'dotted');
    }

    function hideNodeToolbar() {
        $('#mms-node-toolbar').remove();
    }

    /* ── Apply color to a node ── */
    function applyNodeColor(nodeId, colorKey) {
        if (!nodeStyles[nodeId]) nodeStyles[nodeId] = {};
        nodeStyles[nodeId].color = colorKey;
        saveNodeStyles();

        var colorData = NODE_COLORS.find(function(c){ return c.key === colorKey; });
        var $el = $('#jsmind_container jmnode[nodeid="' + nodeId + '"]');

        if (colorKey === 'default' || !colorData) {
            $el.removeAttr('data-node-color');
            $el.css({ 'background': '', 'color': '', 'border-color': '' });
        } else {
            $el.attr('data-node-color', colorKey);
            $el.css({
                'background': colorData.swatch,
                'color': colorData.text,
                'border-color': colorData.border
            });
        }
    }

    /* ── Apply dashed line to a node's connecting line ── */
    function applyNodeLineDash(nodeId, dashType) {
        if (!nodeStyles[nodeId]) nodeStyles[nodeId] = {};
        nodeStyles[nodeId].dashed = dashType;
        saveNodeStyles();
        applyAllLineStyles();
    }

    /* ── Apply all saved node styles to the DOM ── */
    function applyAllNodeStyles() {
        Object.keys(nodeStyles).forEach(function(nodeId) {
            var style = nodeStyles[nodeId];
            if (!style || !style.color) return;
            applyNodeColor(nodeId, style.color);
        });
    }

    /* ── Apply all dashed-line styles to SVG paths ── */
    function applyAllLineStyles() {
        var $svg = $('#jsmind_container svg.jsmind');
        if (!$svg.length) return;

        // Reset all paths first
        $svg.find('path').removeClass('mms-dashed mms-dotted').css({
            'stroke-dasharray': '',
            'opacity': ''
        });

        Object.keys(nodeStyles).forEach(function(nodeId) {
            var style = nodeStyles[nodeId];
            if (!style || !style.dashed) return;

            // Find the SVG path that connects to this node
            var $path = findPathForNode(nodeId, $svg);
            if ($path && $path.length) {
                $path.removeClass('mms-dashed mms-dotted');
                if (style.dashed === 'dashed') {
                    $path.css('stroke-dasharray', '7 4');
                } else if (style.dashed === 'dotted') {
                    $path.css('stroke-dasharray', '2 5');
                }
            }
        });
    }

    /* ── Find the SVG path corresponding to a node ── */
    function findPathForNode(nodeId, $svg) {
        if (!jm) return null;
        var node = jm.get_node(nodeId);
        if (!node || node.isroot) return null;

        var nodeEl = document.querySelector('#jsmind_container jmnode[nodeid="' + nodeId + '"]');
        if (!nodeEl) return null;

        // jsMind draws lines in order — find which path index corresponds to this node
        var allNodes = Object.values(jm.mind.nodes);
        var nonRootNodes = allNodes.filter(function(n){ return !n.isroot; }).sort(function(a,b){ return a.index - b.index; });

        var $paths = $svg.find('path');
        var idx = -1;
        for (var i = 0; i < nonRootNodes.length; i++) {
            if (nonRootNodes[i].id === nodeId) { idx = i; break; }
        }

        if (idx >= 0 && idx < $paths.length) {
            return $paths.eq(idx);
        }
        return null;
    }

    /* ── Save styles to hidden field ── */
    function saveNodeStyles() {
        var $field = $('#mind_map_node_styles');
        if (!$field.length) {
            $('<input type="hidden" name="mind_map_node_styles" id="mind_map_node_styles">').appendTo('#post');
        }
        $('#mind_map_node_styles').val(JSON.stringify(nodeStyles));
    }

    /* ── Bi-directional Sync ── */
    function updateTextareaFromMap() {
        if (!jm) return;
        var mind_data = jm.get_data('node_array');
        var text = serializeToText(mind_data.data);
        isUpdating = true;
        $('#mind_map_data').val(text).trigger('input');
        isUpdating = false;
    }

    function serializeToText(nodes) {
        if (!nodes || nodes.length === 0) return '';
        var root = nodes.find(function(n) { return n.isroot; });
        if (!root) return '';

        var text = root.topic + '\n';

        function getChildren(parentId) {
            return nodes.filter(function(n) { return n.parentid === parentId; });
        }

        function walk(parentId, level) {
            var children = getChildren(parentId);
            children.sort(function(a, b) { return (a.index || 0) - (b.index || 0); });
            children.forEach(function(child) {
                text += '  '.repeat(level) + child.topic + '\n';
                walk(child.id, level + 1);
            });
        }

        walk(root.id, 1);
        return text.trim();
    }

    /* ── Visual Edit Actions ── */
    function addChild() {
        if (!jm) return;
        var selected_node = jm.get_selected_node();
        if (!selected_node) {
            alert('لطفاً ابتدا یک نود را انتخاب کنید.');
            return;
        }
        var nodeid = 'n' + Date.now();
        var node = jm.add_node(selected_node, nodeid, 'نود جدید');
        if (node) {
            jm.select_node(nodeid);
            jm.begin_edit(nodeid);
        }
    }

    function addSibling() {
        if (!jm) return;
        var selected_node = jm.get_selected_node();
        if (!selected_node || selected_node.isroot) {
            alert('لطفاً یک نود (غیر از ریشه) را انتخاب کنید.');
            return;
        }
        var nodeid = 'n' + Date.now();
        var node = jm.insert_node_after(selected_node, nodeid, 'نود جدید');
        if (node) {
            jm.select_node(nodeid);
            jm.begin_edit(nodeid);
        }
    }

    function deleteNode() {
        if (!jm) return;
        var selected_node = jm.get_selected_node();
        if (!selected_node || selected_node.isroot) {
            alert('نود ریشه را نمی‌توان حذف کرد.');
            return;
        }
        if (confirm('آیا از حذف این نود و تمام فرزندان آن مطمئن هستید؟')) {
            // Clean up stored styles for this node
            delete nodeStyles[selected_node.id];
            saveNodeStyles();
            jm.remove_node(selected_node);
            updateTextareaFromMap();
        }
    }

    /* ── watermark ── */
    function updateWatermark() {
        if (typeof mindMapStudioSettings === 'undefined') return;
        var wm  = mindMapStudioSettings.watermark || {};
        var $ca = $('#capture_area');
        if (!wm.text) { $ca.css('background-image', 'none'); return; }

        var spacing = wm.spacing_desktop || 220;
        var height  = spacing * 0.72;

        var svg =
            '<svg xmlns="http://www.w3.org/2000/svg" width="' + spacing + '" height="' + height + '">' +
            '<text x="' + (spacing/2) + '" y="' + (height/2) + '" dominant-baseline="middle" text-anchor="middle"' +
            ' fill="'      + (wm.color   || '#94a3b8') + '"' +
            ' opacity="'   + (wm.opacity || 0.18)      + '"' +
            ' font-size="' + (wm.size    || 14)        + '"' +
            ' font-weight="600"' +
            ' font-family="Vazirmatn,Tahoma,sans-serif"' +
            ' transform="rotate(-30,' + (spacing/2) + ',' + (height/2) + ')">' +
            wm.text + '</text></svg>';

        $ca.css({
            'background-image'  : 'url("data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svg) + '")',
            'background-repeat' : 'repeat',
            'background-size'   : spacing + 'px ' + height + 'px'
        });
    }

    /* ── template ── */
    function insertTemplate() {
        $('#mind_map_data').val(
            'دیجیتال مارکتینگ\n' +
            '  سئو (SEO)\n' +
            '    کلمات کلیدی\n' +
            '    سئو داخلی\n' +
            '  تبلیغات\n' +
            '    گوگل ادز\n' +
            '    اجتماعی\n' +
            '  داده کاوی'
        );
    }

    /* ── Line Style Overrides (bezier / straight / rounded) ── */
    function applyLineStyleOverrides() {
        if (typeof jsMind === 'undefined') return;

        if (jsMind.graph_svg && !jsMind.graph_svg.prototype._bezier_to_orig) {
            jsMind.graph_svg.prototype._bezier_to_orig = jsMind.graph_svg.prototype._bezier_to;
            jsMind.graph_svg.prototype._bezier_to = function (path, x1, y1, x2, y2) {
                var style = (this.opts.line_style || 'bezier');
                if (style === 'straight') {
                    path.setAttribute('d', 'M' + x1 + ' ' + y1 + ' L' + x2 + ' ' + y2);
                } else if (style === 'rounded') {
                    var midX = x1 + (x2 - x1) * 0.5;
                    path.setAttribute('d', 'M' + x1 + ' ' + y1 + ' L' + midX + ' ' + y1 + ' L' + midX + ' ' + y2 + ' L' + x2 + ' ' + y2);
                } else {
                    this._bezier_to_orig(path, x1, y1, x2, y2);
                }
                // Make lines more beautiful: round caps
                path.setAttribute('stroke-linecap', 'round');
                path.setAttribute('stroke-linejoin', 'round');
            };
        }

        if (jsMind.graph_canvas && !jsMind.graph_canvas.prototype._bezier_to_orig) {
            jsMind.graph_canvas.prototype._bezier_to_orig = jsMind.graph_canvas.prototype._bezier_to;
            jsMind.graph_canvas.prototype._bezier_to = function (ctx, x1, y1, x2, y2) {
                var style = (this.opts.line_style || 'bezier');
                ctx.beginPath();
                ctx.moveTo(x1, y1);
                if (style === 'straight') {
                    ctx.lineTo(x2, y2);
                } else if (style === 'rounded') {
                    var midX = x1 + (x2 - x1) * 0.5;
                    ctx.lineTo(midX, y1);
                    ctx.lineTo(midX, y2);
                    ctx.lineTo(x2, y2);
                } else {
                    ctx.bezierCurveTo(x1 + (x2 - x1) * 2 / 3, y1, x1, y2, x2, y2);
                }
                ctx.lineCap = 'round';
                ctx.stroke();
            };
        }
    }
    applyLineStyleOverrides();

    $(document).ready(init);

}(jQuery));

if (!String.prototype.repeat) {
    String.prototype.repeat = function(count) {
        if (count < 0) throw new RangeError('repeat count must be non-negative');
        if (count == Infinity) throw new RangeError('repeat count must be less than infinity');
        count = Math.floor(count);
        if (this.length == 0 || count == 0) return '';
        if (this.length * count >= 1 << 28) throw new RangeError('repeat count must not overflow maximum string size');
        var str = '' + this;
        var res = '';
        while (count > 0) {
            if (count & 1) res += str;
            count >>= 1;
            str += str;
        }
        return res;
    };
}
