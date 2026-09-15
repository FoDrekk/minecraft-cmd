/* ================================================
   blueprint.js — 2D top-down build blueprint viewer
   ------------------------------------------------
   One square = one Minecraft block. Renders a Y-layer at a time from
   a blueprint object shaped like lib/data/blueprints.php's
   blueprintGet() output (footprint, legend, layers[]). Reused by
   Build Ideas and Farm guides — one viewer, not a copy per page.

   Optional per-layer fields a blueprint can supply:
     facing     'N'|'E'|'S'|'W' — which way this layer's construction
                faces (piston/observer direction, water flow, etc.)
     highlight  [char,...] — legend characters to auto-highlight when
                this layer is selected (e.g. "what's new this step")
     step       1-based step number. Any layer carrying it joins the
                Step Navigator; layers without it stay plain Y-layers.
     stepTitle / stepText — short label shown in the navigator. Kept
                out of blueprints.php itself — callers (farms.php,
                the Build Guide) attach these from their own prose
                steps so there is exactly one place that text lives.

   This is deliberately NOT a Minecraft engine: it shows shape, layer
   order and material, nothing more. See the PR description for why
   there is no 3D preview.
   ================================================ */
(function (global) {
  'use strict';
  var MC = global.MC;
  if (!MC) return;

  function esc(s) { return MC.escapeHtml(s); }

  /* Cells and swatches paint through the shared resolver
     (assets/mcvisual.js), so a texture dropped into assets/textures/
     shows up here with no blueprint-specific mapping of its own. With no
     asset for the id it stays the Material Library's approximate colour,
     exactly as before. */
  function swatchStyle(meta) {
    var r = (meta && meta.id && MC.visual && MC.visual.resolve) ? MC.visual.resolve(meta.id) : null;
    if (r && r.src) {
      // Real quotes here: the whole attribute is HTML-escaped below, and
      // the parser turns &quot; back into " before the CSS is read.
      return 'background-image:url("' + String(r.src).replace(/["\\]/g, '\\$&') + '")';
    }
    return 'background:' + ((meta && meta.hex) || '#5a6478');
  }

  var FACING_ARROW = { N: '↑', E: '→', S: '↓', W: '←' };
  var FACING_LABEL = { N: 'North', E: 'East', S: 'South', W: 'West' };

  /**
   * Mounts a blueprint viewer into `root` (a container element).
   * `bp` = { footprint:{x,z}, legend:{char:{id,name,hex}},
   *          layers:[{label,y,rows[],facing?,highlight?,step?,stepTitle?,stepText?}],
   *          materials:[{id,name,hex,count}] }
   * Returns { gotoStep(n), gotoLayer(i) } so a caller (e.g. a prose
   * step list) can drive the same viewer instead of duplicating it.
   */
  function mount(root, bp) {
    if (!root || !bp || !bp.layers || !bp.layers.length) return null;

    var state = { layerIndex: 0, zoom: 22, highlight: null };

    // Layers that opted into the step navigator, in step order.
    var stepLayers = [];
    bp.layers.forEach(function (layer, i) { if (typeof layer.step === 'number') stepLayers.push(i); });
    stepLayers.sort(function (a, b) { return bp.layers[a].step - bp.layers[b].step; });
    var hasSteps = stepLayers.length > 0;

    root.innerHTML =
      '<div class="bp-toolbar">' +
        '<div class="bp-layers" data-bp-layers></div>' +
        '<div class="bp-toolbar-right">' +
          '<div class="bp-facing" data-bp-facing hidden></div>' +
          '<div class="bp-controls">' +
            '<button type="button" class="bp-btn" data-bp-zoom="-1" title="Zoom out">−</button>' +
            '<button type="button" class="bp-btn" data-bp-zoom="1" title="Zoom in">+</button>' +
            '<button type="button" class="bp-btn" data-bp-reset title="Reset view">⤢</button>' +
          '</div>' +
        '</div>' +
      '</div>' +
      (hasSteps ?
      '<div class="bp-stepnav" data-bp-stepnav>' +
        '<div class="bp-stepnav-head">' +
          '<button type="button" class="bp-btn" data-bp-step="-1" aria-label="Previous step">‹</button>' +
          '<div class="bp-stepnav-label" data-bp-stepnav-label></div>' +
          '<button type="button" class="bp-btn" data-bp-step="1" aria-label="Next step">›</button>' +
        '</div>' +
        '<div class="bp-stepnav-bar"><div class="bp-stepnav-fill" data-bp-stepnav-fill></div></div>' +
        '<div class="bp-stepnav-text" data-bp-stepnav-text></div>' +
      '</div>' : '') +
      '<div class="bp-body">' +
        '<div class="bp-viewport" data-bp-viewport>' +
          '<div class="bp-compass">N<span>↑</span></div>' +
          '<div class="bp-grid" data-bp-grid></div>' +
        '</div>' +
        '<div class="bp-side">' +
          '<div class="bp-hover" data-bp-hover>Hover a block to inspect it.</div>' +
          '<div class="bp-legend" data-bp-legend></div>' +
          '<div class="bp-materials" data-bp-materials hidden></div>' +
        '</div>' +
      '</div>';

    var layersEl = root.querySelector('[data-bp-layers]');
    var gridEl = root.querySelector('[data-bp-grid]');
    var legendEl = root.querySelector('[data-bp-legend]');
    var hoverEl = root.querySelector('[data-bp-hover]');
    var viewportEl = root.querySelector('[data-bp-viewport]');
    var facingEl = root.querySelector('[data-bp-facing]');
    var stepNavEl = root.querySelector('[data-bp-stepnav]');

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

    function isHighlighted(ch) { return !!state.highlight && state.highlight.indexOf(ch) !== -1; }

    function renderGrid() {
      var layer = bp.layers[state.layerIndex];
      var w = layer.rows[0].length, h = layer.rows.length;
      gridEl.style.gridTemplateColumns = 'repeat(' + w + ', ' + state.zoom + 'px)';
      gridEl.style.gridTemplateRows = 'repeat(' + h + ', ' + state.zoom + 'px)';
      gridEl.innerHTML = cellsForLayer(state.layerIndex).map(function (c) {
        if (c.ch === '.') return '<div class="bp-cell bp-cell-air"></div>';
        var meta = bp.legend[c.ch] || { name: c.ch, hex: '#5a6478' };
        var hl = isHighlighted(c.ch) ? ' bp-cell-hl' : (state.highlight ? ' bp-cell-dim' : '');
        return '<div class="bp-cell' + hl + '" style="' + esc(swatchStyle(meta)) + '" ' +
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
        var on = isHighlighted(ch);
        return '<button type="button" class="bp-legend-row' + (on ? ' on' : '') + '" data-bp-hl="' + ch + '">' +
          '<i style="' + esc(swatchStyle(meta)) + '"></i>' +
          '<span>' + esc(meta.name) + '</span><b>' + counts[ch] + '</b></button>';
      }).join('');
    }

    function renderFacing() {
      if (!facingEl) return;
      var layer = bp.layers[state.layerIndex];
      var dir = layer.facing && FACING_ARROW[layer.facing] ? layer.facing : null;
      if (!dir) { facingEl.hidden = true; return; }
      facingEl.hidden = false;
      facingEl.innerHTML = '<span class="bp-facing-arrow">' + FACING_ARROW[dir] + '</span> Facing ' + FACING_LABEL[dir];
    }

    function renderStepNav() {
      if (!stepNavEl) return;
      var labelEl = stepNavEl.querySelector('[data-bp-stepnav-label]');
      var textEl = stepNavEl.querySelector('[data-bp-stepnav-text]');
      var fillEl = stepNavEl.querySelector('[data-bp-stepnav-fill]');
      var prevBtn = stepNavEl.querySelector('[data-bp-step="-1"]');
      var nextBtn = stepNavEl.querySelector('[data-bp-step="1"]');
      var pos = stepLayers.indexOf(state.layerIndex);
      var onStep = pos !== -1;
      var total = bp.layers[stepLayers[stepLayers.length - 1]].step;
      if (onStep) {
        var layer = bp.layers[state.layerIndex];
        labelEl.textContent = 'Step ' + layer.step + ' of ' + total;
        textEl.innerHTML = (layer.stepTitle ? '<b>' + esc(layer.stepTitle) + '</b>' : '') +
          (layer.stepText ? (layer.stepTitle ? ' — ' : '') + esc(layer.stepText) : '');
        fillEl.style.width = Math.round((layer.step / total) * 100) + '%';
      } else {
        labelEl.textContent = 'Not part of the step sequence';
        textEl.textContent = 'Use the layer tabs above, or Prev/Next to return to the steps.';
        fillEl.style.width = '0%';
      }
      if (prevBtn) prevBtn.disabled = !onStep || pos === 0;
      if (nextBtn) nextBtn.disabled = !onStep || pos === stepLayers.length - 1;
    }

    function selectLayer(i) {
      state.layerIndex = Math.max(0, Math.min(bp.layers.length - 1, i));
      var layer = bp.layers[state.layerIndex];
      state.highlight = (layer.highlight && layer.highlight.length) ? layer.highlight.slice() : null;
      layersEl.querySelectorAll('[data-bp-layer]').forEach(function (b) {
        b.classList.toggle('active', +b.dataset.bpLayer === state.layerIndex);
      });
      renderGrid();
      renderLegend();
      renderFacing();
      renderStepNav();
    }

    layersEl.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-bp-layer]');
      if (btn) selectLayer(+btn.dataset.bpLayer);
    });

    if (stepNavEl) {
      stepNavEl.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-bp-step]');
        if (!btn || btn.disabled) return;
        var pos = stepLayers.indexOf(state.layerIndex);
        if (pos === -1) pos = 0;
        var next = Math.max(0, Math.min(stepLayers.length - 1, pos + (+btn.dataset.bpStep)));
        selectLayer(stepLayers[next]);
      });
    }

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
      // A manual pick always narrows to exactly that material, whether
      // or not the layer arrived with its own auto-highlight set.
      state.highlight = (state.highlight && state.highlight.length === 1 && state.highlight[0] === ch)
        ? null : [ch];
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

    selectLayer(hasSteps ? stepLayers[0] : 0);

    // A dedicated, standalone element — never touched by renderLegend()'s
    // innerHTML rewrite, so the whole-build total survives every layer
    // switch and legend click instead of vanishing after the first one.
    var materialsEl = root.querySelector('[data-bp-materials]');
    if (materialsEl && bp.materials && bp.materials.length) {
      materialsEl.hidden = false;
      materialsEl.innerHTML = '<div class="bp-legend-title">Whole build</div>' + bp.materials.map(function (m) {
        return '<div class="bp-mat-row"><i style="' + esc(swatchStyle(m)) + '"></i>' +
          '<span>' + esc(m.name) + '</span><b>' + m.count + '</b></div>';
      }).join('');
    }

    function gotoStep(n) {
      var idx = -1;
      bp.layers.forEach(function (l, i) { if (l.step === n) idx = i; });
      if (idx === -1) return false;
      selectLayer(idx);
      if (typeof root.scrollIntoView === 'function') root.scrollIntoView({ behavior: 'smooth', block: 'center' });
      return true;
    }

    return { gotoStep: gotoStep, gotoLayer: selectLayer };
  }

  MC.blueprint = { mount: mount };
})(window);
