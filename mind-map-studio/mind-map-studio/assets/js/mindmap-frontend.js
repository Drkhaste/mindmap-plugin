(function () {
    'use strict';

    var RETRY_MAX  = 30;
    var retryCount = 0;

    function boot() {
        if (typeof jsMind === 'undefined') {
            if (retryCount < RETRY_MAX) { retryCount++; setTimeout(boot, 300); }
            else { console.error('[MindMapStudio] jsMind not found.'); }
            return;
        }
        var els = document.querySelectorAll('.mindmap-studio-container');
        for (var i = 0; i < els.length; i++) {
            initMap(els[i]);
        }
    }

    function initMap(el) {
        var rawData   = el.getAttribute('data-mindmap-data');
        var layout    = el.getAttribute('data-mindmap-layout') || 'both';
        var lineColor = el.getAttribute('data-line-color') || '#cbd5e1';

        if (!rawData || !rawData.trim()) return;

        var nodes = parseNodes(rawData, layout);
        if (!nodes.length) return;

        var isDark = document.body.classList.contains('dark-mode');
        var theme  = isDark
            ? (getS('theme_dark')  || 'dark')
            : (getS('theme_light') || 'primary');

        var settings = (typeof mindMapStudioSettings !== 'undefined') ? mindMapStudioSettings : {};

        if (!el.id) el.id = 'mms_' + Math.random().toString(36).slice(2, 9);

        el.style.width      = '100%';
        el.style.height     = '500px';
        el.style.background = 'transparent';
        el.style.display    = 'block';

        var jm;
        try {
            jm = new jsMind({
                container : el.id,
                theme     : theme,
                mode      : 'full',
                editable  : false,
                view   : {
                    engine: 'svg',
                    line_width: settings.line_width || 1.5,
                    line_color: lineColor,
                    line_style: settings.line_style || 'bezier'
                },
                layout : { hspace: 40, vspace: 14, pspace: 10 }
            });
            jm.show({
                meta   : { name: 'Map', author: 'MMS', version: '1.0' },
                format : 'node_array',
                data   : nodes
            });
        } catch (e) {
            console.error('[MindMapStudio] render error:', e);
            return;
        }

        function update() {
            scaleAndCenter(el);
            applyWatermark(el);
        }

        setTimeout(update, 400);
        setTimeout(update, 1000);

        if (window.ResizeObserver) {
            new ResizeObserver(function () { setTimeout(update, 100); }).observe(el.parentElement || el);
        } else {
            window.addEventListener('resize', function () { setTimeout(update, 100); });
        }
    }

    function scaleAndCenter(el) {
        var wrap = el.querySelector('jmnodes');
        if (!wrap) return;

        var nodes = el.querySelectorAll('jmnode');
        if (!nodes.length) return;

        /* Reset transform before measuring */
        wrap.style.transform = 'none';
        var svg = el.querySelector('svg');
        if (svg) svg.style.transform = 'none';

        var minX = 99999, maxX = -99999, minY = 99999, maxY = -99999;
        for (var j = 0; j < nodes.length; j++) {
            var node = nodes[j];
            var x = node.offsetLeft;
            var y = node.offsetTop;
            var w = node.offsetWidth;
            var h = node.offsetHeight;
            if (x < minX) minX = x;
            if (x + w > maxX) maxX = x + w;
            if (y < minY) minY = y;
            if (y + h > maxY) maxY = y + h;
        }

        var mW = maxX - minX;
        var mH = maxY - minY;
        var cW = el.getBoundingClientRect().width;

        var scale = 1;
        if (mW > (cW - 20) && cW > 0) {
            scale = (cW - 20) / mW;
        }
        if (scale > 1) scale = 1;

        var offsetX = -minX * scale + (cW - mW * scale) / 2;
        var offsetY = -minY * scale + 10;

        var transformVal = 'translate(' + Math.round(offsetX) + 'px, ' + Math.round(offsetY) + 'px) scale(' + scale + ')';

        wrap.style.transformOrigin = '0 0';
        wrap.style.transform       = transformVal;

        if (svg) {
            svg.style.transformOrigin = '0 0';
            svg.style.transform       = transformVal;
            svg.style.overflow        = 'visible';
        }

        el.style.height   = Math.ceil(mH * scale + 20) + 'px';
        el.style.overflow = 'hidden';
        el.style.direction = 'ltr';

        var cap = findCapture(el);
        if (cap) {
            cap.style.height    = 'auto';
            cap.style.minHeight = el.style.height;
        }
    }

    function applyWatermark(el) {
        var cap = findCapture(el);
        if (!cap) return;

        var s = getS('watermark');

        if (!s || !s.text) {
            cap.style.backgroundImage = 'none';
            return;
        }

        var wmSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="220" height="160">'
            + '<text x="110" y="80" dominant-baseline="middle" text-anchor="middle"'
            + ' fill="'      + (s.color   || '#94a3b8') + '"'
            + ' opacity="'   + (s.opacity || 0.18)      + '"'
            + ' font-size="' + (s.size    || 14)        + '"'
            + ' font-weight="600"'
            + ' font-family="Vazirmatn,Tahoma,sans-serif"'
            + ' transform="rotate(-30,110,80)">'
            + s.text
            + '</text></svg>';

        cap.style.backgroundColor  = 'transparent';
        cap.style.backgroundImage  = 'url("data:image/svg+xml;charset=utf-8,' + encodeURIComponent(wmSvg) + '")';
        cap.style.backgroundRepeat = 'repeat';
        cap.style.backgroundSize   = '220px 160px';
    }

    function parseNodes(text, dir) {
        var lines = text.split(/\r?\n/);
        var nodes = [], stack = [], rootDone = false, right = true;
        for (var i = 0; i < lines.length; i++) {
            var raw = lines[i], t = raw.trim();
            if (!t) continue;
            var ind = 0;
            while (ind < raw.length && (raw[ind] === ' ' || raw[ind] === '\t')) ind++;
            var node = { id: 'n' + i, topic: t, indent: ind };
            if (!rootDone) {
                node.isroot = true; rootDone = true; stack = [node]; nodes.push(node); continue;
            }
            while (stack.length && stack[stack.length - 1].indent >= ind) stack.pop();
            var par = stack.length ? stack[stack.length - 1] : nodes[0];
            node.parentid = par.id;
            if (par.isroot) {
                node.direction = (dir === 'both') ? (right ? 'right' : 'left') : 'right';
                if (dir === 'both') right = !right;
            }
            stack.push(node); nodes.push(node);
        }
        return nodes;
    }

    function findCapture(el) {
        var p = el.parentElement;
        while (p) {
            if (p.classList && p.classList.contains('mindmap-studio-capture')) return p;
            p = p.parentElement;
        }
        return null;
    }

    function getS(key) {
        return (typeof mindMapStudioSettings !== 'undefined') ? mindMapStudioSettings[key] : null;
    }

    /* ── Line Style Overrides ── */
    function applyLineStyleOverrides() {
        if (typeof jsMind === 'undefined') return;

        // SVG Engine
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
            };
        }

        // Canvas Engine
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
                ctx.stroke();
            };
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            applyLineStyleOverrides();
            setTimeout(boot, 150);
        });
    } else {
        applyLineStyleOverrides();
        setTimeout(boot, 150);
    }

}());
