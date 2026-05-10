(function ($) {
    'use strict';

    var jm          = null;
    var mapDirection = 'both';
    var isUpdating   = false;

    /* ── init ── */
    function init() {
        if (!$('#mind_map_data').length) return;

        mapDirection = $('#mind_map_layout').val() || 'both';

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
            console.error('[MindMapStudio] jsMind yüklenmedi');
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
                engine: 'svg',
                line_width: settings.line_width || 2,
                line_color: '#cbd5e1',
                line_style: settings.line_style || 'bezier'
            },
            layout    : { hspace: 40, vspace: 14, pspace: 10 }
        };

        var mind = {
            meta   : { name: 'Map', author: 'Mind Map Studio', version: '1.0' },
            format : 'node_array',
            data   : nodes
        };

        try {
            jm = new jsMind(opts);
            jm.show(mind);
            jm.add_event_listener(function(type, data) {
                if (type === 3 && !isUpdating) {
                    updateTextareaFromMap();
                }
            });
        } catch (e) {
            console.error('[MindMapStudio] jsMind error:', e);
        }
    }

    /* ── Bi-directional Sync ── */
    function updateTextareaFromMap() {
        if (!jm) return;
        var mind_data = jm.get_data('node_array');
        var text = serializeToText(mind_data.data);
        isUpdating = true;
        $('#mind_map_data').val(text).trigger('change');
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
        jm.add_node(selected_node, nodeid, 'نود جدید');
    }

    function addSibling() {
        if (!jm) return;
        var selected_node = jm.get_selected_node();
        if (!selected_node || selected_node.isroot) {
            alert('لطفاً یک نود (غیر از ریشه) را انتخاب کنید.');
            return;
        }
        var nodeid = 'n' + Date.now();
        jm.insert_node_after(selected_node, nodeid, 'نود جدید');
    }

    function deleteNode() {
        if (!jm) return;
        var selected_node = jm.get_selected_node();
        if (!selected_node || selected_node.isroot) {
            alert('نود ریشه را نمی‌توان حذف کرد.');
            return;
        }
        if (confirm('آیا از حذف این نود و تمام فرزندان آن مطمئن هستید؟')) {
            jm.remove_node(selected_node);
        }
    }

    /* ── watermark ── */
    function updateWatermark() {
        if (typeof mindMapStudioSettings === 'undefined') return;
        var wm  = mindMapStudioSettings.watermark || {};
        var $ca = $('#capture_area');
        if (!wm.text) { $ca.css('background-image', 'none'); return; }

        var svg =
            '<svg xmlns="http://www.w3.org/2000/svg" width="220" height="160">' +
            '<text x="110" y="80" dominant-baseline="middle" text-anchor="middle"' +
            ' fill="'      + (wm.color   || '#94a3b8') + '"' +
            ' opacity="'   + (wm.opacity || 0.18)      + '"' +
            ' font-size="' + (wm.size    || 14)        + '"' +
            ' font-weight="600"' +
            ' font-family="Vazirmatn,Tahoma,sans-serif"' +
            ' transform="rotate(-30,110,80)">' +
            wm.text + '</text></svg>';

        $ca.css({ 'background-image': 'url("data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svg) + '")', 'background-repeat': 'repeat' });
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

    /* ── Line Style Overrides ── */
    if (typeof jsMind !== 'undefined' && jsMind.graph_svg) {
        var origBezierTo = jsMind.graph_svg.prototype._bezier_to;

        jsMind.graph_svg.prototype._bezier_to = function (path, x1, y1, x2, y2) {
            var style = (this.opts.line_style || 'bezier');

            if (style === 'straight') {
                path.setAttribute('d', 'M' + x1 + ' ' + y1 + ' L' + x2 + ' ' + y2);
            } else if (style === 'rounded') {
                var midX = x1 + (x2 - x1) * 0.5;
                path.setAttribute('d', 'M' + x1 + ' ' + y1 + ' L' + midX + ' ' + y1 + ' L' + midX + ' ' + y2 + ' L' + x2 + ' ' + y2);
            } else {
                origBezierTo.call(this, path, x1, y1, x2, y2);
            }
        };
    }

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
