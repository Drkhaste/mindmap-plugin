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
                view   : { engine: 'svg', line_width: 1.5, line_color: lineColor },
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

        var minX = 99999, maxX = -99999, minY = 99999, maxY = -99999;
        for (var j = 0; j < nodes.length; j++) {
            var node = nodes[j];
            var x = parseInt(node.style.left);
            var y = parseInt(node.style.top);
            var w = node.offsetWidth;
            var h = node.offsetHeight;
            if (x < minX) minX = x;
            if (x + w > maxX) maxX = x + w;
            if (y < minY) minY = y;
            if (y + h > maxY) maxY = y + h;
        }

        var mW = maxX - minX;
        var mH = maxY - minY;
        var cW = el.offsetWidth;

        var scale = 1;
        if (mW > cW && cW > 0) {
            scale = cW / (mW + 40); // 40px padding
        }

        // Ensure scale is at most 1 as per user's preference that it's already fit
        if (scale > 1) scale = 1;

        var offsetX = -minX * scale + (cW - mW * scale) / 2;
        var offsetY = -minY * scale;

        var transformVal = 'translate(' + offsetX + 'px, ' + offsetY + 'px) scale(' + scale + ')';

        wrap.style.transformOrigin = '0 0';
        wrap.style.transform       = transformVal;

        var svg = el.querySelector('svg');
        if (svg) {
            svg.style.transformOrigin = '0 0';
            svg.style.transform       = transformVal;
            svg.style.overflow        = 'visible';
        }

        el.style.height   = Math.ceil(mH * scale + 20) + 'px';
        el.style.overflow = 'hidden';

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

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { setTimeout(boot, 150); });
    } else {
        setTimeout(boot, 150);
    }

}());
