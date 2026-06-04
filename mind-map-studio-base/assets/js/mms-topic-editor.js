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

        $(document).on('click', '.mms-color-opt', function() {
            var containerId = $(this).closest('.mms-editor-wrapper').data('id');
            var color = $(this).data('color');
            applyNodeColor(containerId, color);
        });

        $(document).on('click', '.mms-btn-toggle-dashed', function() {
            var containerId = $(this).closest('.mms-editor-wrapper').data('id');
            toggleDashed(containerId);
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
                            <div class="node-custom-group" style="margin-right:20px; display:flex; gap:8px; border-right:1px solid #ccc; padding-right:15px; align-items:center;">
                                <div class="mms-color-palette" style="display:flex; gap:4px;">
                                    <div class="mms-color-opt" data-color="#ffffff" style="width:20px; height:20px; border-radius:4px; cursor:pointer; background:#ffffff; border:1px solid #ddd;"></div>
                                    <div class="mms-color-opt" data-color="#f8d7da" style="width:20px; height:20px; border-radius:4px; cursor:pointer; background:#f8d7da; border:1px solid #ddd;"></div>
                                    <div class="mms-color-opt" data-color="#d1ecf1" style="width:20px; height:20px; border-radius:4px; cursor:pointer; background:#d1ecf1; border:1px solid #ddd;"></div>
                                    <div class="mms-color-opt" data-color="#d4edda" style="width:20px; height:20px; border-radius:4px; cursor:pointer; background:#d4edda; border:1px solid #ddd;"></div>
                                    <div class="mms-color-opt" data-color="#fff3cd" style="width:20px; height:20px; border-radius:4px; cursor:pointer; background:#fff3cd; border:1px solid #ddd;"></div>
                                    <div class="mms-color-opt" data-color="#e2e3e5" style="width:20px; height:20px; border-radius:4px; cursor:pointer; background:#e2e3e5; border:1px solid #ddd;"></div>
                                    <div class="mms-color-opt" data-color="#3b82f6" style="width:20px; height:20px; border-radius:4px; cursor:pointer; background:#3b82f6; border:1px solid #ddd;"></div>
                                </div>
                                <button type="button" class="button button-secondary mms-btn-toggle-dashed" title="خط‌چین"><span class="dashicons dashicons-ellipsis" style="margin-top:4px;"></span></button>
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
                line_color: '#cbd5e1',
                line_style: settings.line_style || 'bezier'
            },
            layout    : { hspace: 60, vspace: 20, pspace: 10 }
        };

        var mind = {
            meta: { name: 'Map' },
            format: 'node_array',
            data: nodes
        };

        var jm = new jsMind(opts);
        jm.show(mind);
        jmInstances[containerId] = jm;

        applyLineStyleOverrides();

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
        function walk(parentId, level) {
            var children = nodes.filter(function(n) { return n.parentid === parentId; });
            children.forEach(function(child) {
                text += '  '.repeat(level) + getNodeText(child) + '\n';
                walk(child.id, level + 1);
            });
        }
        walk(root.id, 1);
        return text.trim();
    }

    function applyNodeColor(containerId, color) {
        var jm = jmInstances[containerId];
        if (!jm) return;
        var selected_node = jm.get_selected_node();
        if (!selected_node) return;

        if (!selected_node.data) selected_node.data = {};
        selected_node.data['background-color'] = color;

        var el = document.querySelector('#' + containerId + ' jmnode[nodeid="'+selected_node.id+'"]');
        if (el) {
            el.style.backgroundColor = color;
            if (jm.view && jm.view.reset_node_custom_style) {
                jm.view.reset_node_custom_style(selected_node);
            }
        }

        updateTextareaFromMap(containerId);
    }

    function toggleDashed(containerId) {
        var jm = jmInstances[containerId];
        if (!jm) return;
        var selected_node = jm.get_selected_node();
        if (!selected_node || selected_node.isroot) return;

        if (!selected_node.data) selected_node.data = {};
        selected_node.data.dashed = !selected_node.data.dashed;

        if (jm.view && jm.view.show_lines) {
            jm.view.show_lines();
        }
        updateTextareaFromMap(containerId);
    }

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

    $(document).ready(init);

})(jQuery);
