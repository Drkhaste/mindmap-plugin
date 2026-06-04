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

        $('.mms-color-opt').on('click', function() {
            var color = $(this).data('color');
            applyNodeColor(color);
        });
        $('#btn-toggle-dashed').on('click', toggleDashed);
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

            // Parse metadata: {c:#fff, d:1}
            var topic = trimmed;
            var data = {};
            var metaMatch = topic.match(/\{([^}]+)\}$/);
            if (metaMatch) {
                topic = topic.substring(0, metaMatch.index).trim();
                var metaStr = metaMatch[1];
                var parts = metaStr.split(',');
                parts.forEach(function(p) {
                    var kv = p.split(':');
                    if (kv.length === 2) {
                        var k = kv[0].trim();
                        var v = kv[1].trim();
                        if (k === 'c') data['background-color'] = v;
                        if (k === 'd') data['dashed'] = (v === '1' || v === 'true');
                    }
                });
            }

            var node = { id: 'n' + i, topic: topic, indent: indent, data: data };

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
            layout    : { hspace: 60, vspace: 20, pspace: 10 }
        };

        var mind = {
            meta   : { name: 'Map', author: 'Mind Map Studio', version: '1.0' },
            format : 'node_array',
            data   : nodes
        };

        try {
            jm = new jsMind(opts);
            jm.show(mind);

            // Re-apply overrides in case jsMind was re-instantiated
            applyLineStyleOverrides();

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
        $('#mind_map_data').val(text).trigger('input'); // Use input event
        isUpdating = false;
    }

    function serializeToText(nodes) {
        if (!nodes || nodes.length === 0) return '';
        var root = nodes.find(function(n) { return n.isroot; });
        if (!root) return '';

        function getNodeText(node) {
            var text = node.topic;
            var meta = [];
            if (node.data) {
                if (node.data['background-color']) meta.push('c:' + node.data['background-color']);
                if (node.data['dashed']) meta.push('d:1');
            }
            if (meta.length > 0) {
                text += ' {' + meta.join(',') + '}';
            }
            return text;
        }

        var text = getNodeText(root) + '\n';

        function getChildren(parentId) {
            return nodes.filter(function(n) { return n.parentid === parentId; });
        }

        function walk(parentId, level) {
            var children = getChildren(parentId);
            children.sort(function(a, b) { return (a.index || 0) - (b.index || 0); });
            children.forEach(function(child) {
                text += '  '.repeat(level) + getNodeText(child) + '\n';
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
            jm.remove_node(selected_node);
            updateTextareaFromMap(); // Ensure sync after deletion
        }
    }

    function applyNodeColor(color) {
        if (!jm) return;
        var selected_node = jm.get_selected_node();
        if (!selected_node) return;

        if (!selected_node.data) selected_node.data = {};
        selected_node.data['background-color'] = color;

        // Update visual
        var el = document.querySelector('jmnode[nodeid="'+selected_node.id+'"]');
        if (el) {
            el.style.backgroundColor = color;
            // Also ensure it stays when unselected
            if (jm.view && jm.view.reset_node_custom_style) {
                jm.view.reset_node_custom_style(selected_node);
            }
        }

        updateTextareaFromMap();
    }

    function toggleDashed() {
        if (!jm) return;
        var selected_node = jm.get_selected_node();
        if (!selected_node || selected_node.isroot) return;

        if (!selected_node.data) selected_node.data = {};
        selected_node.data.dashed = !selected_node.data.dashed;

        if (jm.view && jm.view.show_lines) {
            jm.view.show_lines();
        }
        updateTextareaFromMap();
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
            'background-image': 'url("data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svg) + '")',
            'background-repeat': 'repeat',
            'background-size': spacing + 'px ' + height + 'px'
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

    /* ── Line Style Overrides ── */
    function applyLineStyleOverrides() {
        if (typeof jsMind === 'undefined') return;

        // SVG Engine
        if (jsMind.graph_svg && !jsMind.graph_svg.prototype.draw_line_orig) {
            jsMind.graph_svg.prototype.draw_line_orig = jsMind.graph_svg.prototype.draw_line;
            jsMind.graph_svg.prototype.draw_line = function (pout, pin, offset, node) {
                this.draw_line_orig(pout, pin, offset);
                var lastLine = this.lines[this.lines.length - 1];
                if (lastLine && node && node.data && node.data.dashed) {
                    lastLine.setAttribute('stroke-dasharray', '5,5');
                }
            };

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
            };
        }

        // Canvas Engine
        if (jsMind.graph_canvas && !jsMind.graph_canvas.prototype.draw_line_orig) {
            jsMind.graph_canvas.prototype.draw_line_orig = jsMind.graph_canvas.prototype.draw_line;
            jsMind.graph_canvas.prototype.draw_line = function (pout, pin, offset, node) {
                var ctx = this.canvas_ctx;
                ctx.save();
                if (node && node.data && node.data.dashed) {
                    ctx.setLineDash([5, 5]);
                } else {
                    ctx.setLineDash([]);
                }
                this.draw_line_orig(pout, pin, offset);
                ctx.restore();
            };

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
                ctx.stroke();
            };
        }

        // Wrap show_lines to pass node to draw_line
        if (jsMind.view_provider && !jsMind.view_provider.prototype.show_lines_orig) {
            jsMind.view_provider.prototype.show_lines_orig = jsMind.view_provider.prototype.show_lines;
            jsMind.view_provider.prototype.show_lines = function() {
                this.clear_lines();
                var nodes = this.jm.mind.nodes;
                var node = null;
                var pin = null;
                var pout = null;
                var _offset = this.get_view_offset();
                for (var nodeid in nodes) {
                    node = nodes[nodeid];
                    if (!!node.isroot) { continue; }
                    if (('visible' in node._data.layout) && !node._data.layout.visible) { continue; }
                    pin = this.layout.get_node_point_in(node);
                    pout = this.layout.get_node_point_out(node.parent);
                    this.graph.draw_line(pout, pin, _offset, node);
                }
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
