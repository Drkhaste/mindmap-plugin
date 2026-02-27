(function ($) {
    'use strict';

    var jm          = null;
    var mapDirection = 'both';

    /* ── init ── */
    function init() {
        if (!$('#mind_map_data').length) return;

        mapDirection = $('#mind_map_layout').val() || 'both';

        setTimeout(function () {
            generateMindMap();
            updateWatermark();
        }, 400);

        $('#mind_map_data').on('input propertychange', function () { generateMindMap(); });
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

        var theme = (typeof mindMapStudioSettings !== 'undefined')
            ? (mindMapStudioSettings.theme || 'primary')
            : 'primary';

        var opts = {
            container : 'jsmind_container',
            theme     : theme,
            mode      : 'full',
            editable  : false,
            view      : { engine: 'svg', line_width: 0.8, line_color: '#cbd5e1' },
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
        } catch (e) {
            console.error('[MindMapStudio] jsMind error:', e);
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

    $(document).ready(init);

}(jQuery));
