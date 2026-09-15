/* ================================================
   blueprint.js — 2D top-down build blueprint viewer
   ------------------------------------------------
   One square = one Minecraft block. Renders a Y-layer at a time from
   a blueprint object shaped like lib/data/blueprints.php's
   blueprintGet() output (footprint, legend, layers[]). Reused by
   Build Ideas and Farm guides — one viewer, not a copy per page.

   This is deliberately NOT a Minecraft engine: it shows shape, layer
   order and material, nothing more. See the PR description for why
   there is no 3D preview.
   ================================================ */
(function (global) {
  'use strict';
  var MC = global.MC;
  if (!MC) return;

  function esc(s) { return MC.escapeHtml(s); }

  /**
   * Mounts a blueprint viewer into `root` (a container element).
   * `bp` = { footprint:{x,z}, legend:{char:{id,name,hex}}, layers:[{label,y,rows[]}], materials:[{id,name,hex,count}] }
   */
  function mount(root, bp) {
    if (!root || !bp || !bp.layers || !bp.layers.length) return;

    var state = { layerIndex: 0, zoom: 22, highlight: null, showCoords: true };

    root.innerHTML =
      '<div class="bp-toolbar">' +
        '<div class="bp-layers" data-bp-layers></div>' +
        '<div class="bp-controls">' +
          '<button type="button" class="bp-btn" data-bp-zoom="-1" title="Zoom out">−</button>' +
          '<button type="button" class="bp-btn" data-bp-zoom="1" title="Zoom in">+</button>' +
          '<button type="button" class="bp-btn" data-bp-reset title="Reset view">⤢</button>' +
        '</div>' +
      '</div>' +
      '<div class="bp-body">' +
        '<div class="bp-viewport" data-bp-viewport>' +
          '<div class="bp-compass">N<span>↑</span></div>' +
          '<div class="bp-grid" data-bp-grid></div>' +
        '</div>' +
        '<div class="bp-side">' +
          '<div class="bp-hover" data-bp-hover>Hover a block to inspect it.</div>' +
          '<div class="bp-legend" data-bp-legend></div>' +
        '</div>' +
      '</div>';

    var layersEl = root.querySelector('[data-bp-layers]');
    var gridEl = root.querySelector('[data-bp-grid]');
    var legendEl = root.querySelector('[data-bp-legend]');
    var hoverEl = root.querySelector('[data-bp-hover]');
    var viewportEl = root.querySelector('[data-bp-viewport]');

    layersEl.innerHTML = bp.layers.map(function (layer, i) {
      return '<button type="button" class="bp-layer-btn' + (i === 0 ? ' active' : '') + '" data-bp-layer="' + i + '">' +
        'Y=' + esc(layer.y) + ' <span>' + esc(layer.label) + '</span></button>';
    }).join('');

    function cellsForLayer(i) {
      var layer = bp.layers[i];
      var out = [];
      layer.rows.forEach(function (row, z) {
        row.split('').forEach(function (ch, x) {
          out.push({ x: x, z: z, ch: ch });
        });
      });
      return out;
    }

    function renderGrid() {
      var layer = bp.layers[state.layerIndex];
      var w = layer.rows[0].length, h = layer.rows.length;
      gridEl.style.gridTemplateColumns = 'repeat(' + w + ', ' + state.zoom + 'px)';
      gridEl.style.gridTemplateRows = 'repeat(' + h + ', ' + state.zoom + 'px)';
      gridEl.innerHTML = cellsForLayer(state.layerIndex).map(function (c) {
        if (c.ch === '.') return '<div class="bp-cell bp-cell-air"></div>';
        var meta = bp.legend[c.ch] || { name: c.ch, hex: '#5a6478' };
        var hl = state.highlight && state.highlight === c.ch ? ' bp-cell-hl' : (state.highlight ? ' bp-cell-dim' : '');
        return '<div class="bp-cell' + hl + '" style="background:' + esc(meta.hex) + '" ' +
          'data-x="' + c.x + '" data-z="' + c.z + '" data-name="' + esc(meta.name) + '"></div>';
      }).join('');
    }

    function renderLegend() {
      var layer = bp.layers[state.layerIndex];
      var counts = {};
      layer.rows.forEach(function (row) {
        row.split('').forEach(function (ch) {
          if (ch === '.') return;
          counts[ch] = (counts[ch] || 0) + 1;
        });
      });
      var chars = Object.keys(counts);
      if (!chars.length) {
        legendEl.innerHTML = '<div class="bp-legend-empty">Nothing on this layer.</div>';
        return;
      }
      legendEl.innerHTML = '<div class="bp-legend-title">This layer</div>' + chars.map(function (ch) {
        var meta = bp.legend[ch] || { name: ch, hex: '#5a6478' };
        var on = state.highlight === ch;
        return '<button type="button" class="bp-legend-row' + (on ? ' on' : '') + '" data-bp-hl="' + ch + '">' +
          '<i style="background:' + esc(meta.hex) + '"></i>' +
          '<span>' + esc(meta.name) + '</span><b>' + counts[ch] + '</b></button>';
      }).join('');
    }

    function selectLayer(i) {
      state.layerIndex = Math.max(0, Math.min(bp.layers.length - 1, i));
      state.highlight = null;
      layersEl.querySelectorAll('[data-bp-layer]').forEach(function (b) {
        b.classList.toggle('active', +b.dataset.bpLayer === state.layerIndex);
      });
      renderGrid();
      renderLegend();
    }

    layersEl.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-bp-layer]');
      if (btn) selectLayer(+btn.dataset.bpLayer);
    });

    root.querySelectorAll('[data-bp-zoom]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        state.zoom = Math.max(10, Math.min(48, state.zoom + (+btn.dataset.bpZoom) * 4));
        renderGrid();
      });
    });
    var resetBtn = root.querySelector('[data-bp-reset]');
    if (resetBtn) resetBtn.addEventListener('click', function () {
      state.zoom = 22;
      viewportEl.scrollLeft = 0; viewportEl.scrollTop = 0;
      renderGrid();
    });

    legendEl.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-bp-hl]');
      if (!btn) return;
      var ch = btn.dataset.bpHl;
      state.highlight = state.highlight === ch ? null : ch;
      renderGrid();
      renderLegend();
    });

    gridEl.addEventListener('mouseover', function (e) {
      var cell = e.target.closest('.bp-cell:not(.bp-cell-air)');
      if (!cell) return;
      var layer = bp.layers[state.layerIndex];
      hoverEl.innerHTML = '<b>' + esc(cell.dataset.name) + '</b><br>X: ' + esc(cell.dataset.x) +
        ' &nbsp; Z: ' + esc(cell.dataset.z) + ' &nbsp; Y: ' + esc(layer.y);
    });
    gridEl.addEventListener('mouseleave', function () {
      hoverEl.textContent = 'Hover a block to inspect it.';
    });

    // Drag-to-pan, since the grid can be wider than its viewport at
    // higher zoom levels.
    var dragging = false, startX, startY, scrollX, scrollY;
    viewportEl.addEventListener('mousedown', function (e) {
      if (e.target.closest('.bp-cell')) return; // let hover/click through on cells
      dragging = true; startX = e.clientX; startY = e.clientY;
      scrollX = viewportEl.scrollLeft; scrollY = viewportEl.scrollTop;
    });
    window.addEventListener('mousemove', function (e) {
      if (!dragging) return;
      viewportEl.scrollLeft = scrollX - (e.clientX - startX);
      viewportEl.scrollTop = scrollY - (e.clientY - startY);
    });
    window.addEventListener('mouseup', function () { dragging = false; });

    selectLayer(0);

    if (bp.materials && bp.materials.length) {
      var matBox = document.createElement('div');
      matBox.className = 'bp-materials';
      matBox.innerHTML = '<div class="bp-legend-title">Whole build</div>' + bp.materials.map(function (m) {
        return '<div class="bp-mat-row"><i style="background:' + esc(m.hex) + '"></i>' +
          '<span>' + esc(m.name) + '</span><b>' + m.count + '</b></div>';
      }).join('');
      root.querySelector('[data-bp-legend]').appendChild(matBox);
    }
  }

  MC.blueprint = { mount: mount };
})(window);
