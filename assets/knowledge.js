/* ================================================
   knowledge.js — materials, palettes, tips, ideas
   ================================================ */
(function () {
  'use strict';

  var ROLES = ['main', 'secondary', 'accent', 'detail', 'lighting'];
  var byId = {};
  var current = { style: 'medieval', blocks: {} };

  function el(id) { return document.getElementById(id); }
  var esc = function (s) { return MC.escapeHtml(s); };

  BLOCKS.forEach(function (b) { byId[b.id] = b; });

  /* ═══════════ MATERIALS ═══════════ */
  function renderMaterials() {
    var grid = el('mat-grid');
    if (!grid) return;
    var q = el('mat-search').value.trim().toLowerCase();
    var cat = el('mat-cat').value;
    var style = el('mat-style').value;

    var rows = BLOCKS.filter(function (b) {
      if (cat && b.category !== cat) return false;
      if (style && b.styles.indexOf(style) === -1) return false;
      if (!q) return true;
      return b.id.indexOf(q) !== -1 || b.name.toLowerCase().indexOf(q) !== -1 ||
             (b.note || '').toLowerCase().indexOf(q) !== -1;
    });

    el('mat-count').textContent = rows.length + ' of ' + BLOCKS.length + ' blocks';
    el('mat-empty').innerHTML = rows.length ? '' :
      '<div class="empty-state"><div class="empty-icon">🔍</div><div class="empty-title">No blocks match</div>' +
      '<div class="empty-sub">Try a shorter word, or clear the category and style filters.</div></div>';

    grid.innerHTML = rows.map(function (b) {
      return '<div class="mat-card">' +
        '<div class="mat-swatch" style="background:' + esc(b.hex) + '"></div>' +
        '<div style="min-width:0">' +
        '<div class="mat-name">' + esc(b.name) + '</div>' +
        '<div class="mat-id" data-copy="' + esc(b.id) + '" title="Click to copy the id">' + esc(b.id) + '</div>' +
        (b.note ? '<div class="mat-note">' + esc(b.note) + '</div>' : '') +
        '<div class="mat-styles">' + b.styles.map(function (s) {
          return '<span class="mat-style">' + esc(s) + '</span>';
        }).join('') + '</div>' +
        '</div></div>';
    }).join('');
  }

  /* ═══════════ PALETTE ═══════════ */
  function renderRoles() {
    var box = el('pal-roles');
    if (!box) return;
    box.innerHTML = ROLES.map(function (role) {
      var id = current.blocks[role];
      var b = byId[id] || { name: id || '—', hex: '#3a3a3a' };
      return '<div class="pal-role">' +
        '<div class="pal-role-label">' + role + '</div>' +
        '<div class="pal-role-swatch" style="background:' + esc(b.hex) + '"></div>' +
        '<select data-role="' + role + '">' + BLOCKS.map(function (o) {
          return '<option value="' + esc(o.id) + '"' + (o.id === id ? ' selected' : '') + '>' + esc(o.name) + '</option>';
        }).join('') + '</select>' +
        '</div>';
    }).join('');

    box.querySelectorAll('select').forEach(function (sel) {
      sel.addEventListener('change', function () {
        current.blocks[sel.dataset.role] = sel.value;
        renderRoles();
        renderWall();
      });
    });

    var desc = el('pal-style-desc');
    if (desc) desc.textContent = (PALETTE_STYLES[current.style] || {}).desc || '';
  }

  /**
   * A rough wall mock in the palette's own proportions: mostly main,
   * a third secondary, accents and detail sparingly, one light.
   */
  function renderWall() {
    var wall = el('pal-wall');
    if (!wall) return;
    var cells = 26 * 8;                                    // matches the CSS column count
    var weights = [['main', 0.56], ['secondary', 0.26], ['accent', 0.1], ['detail', 0.06], ['lighting', 0.02]];
    var out = [];
    weights.forEach(function (w) {
      var b = byId[current.blocks[w[0]]] || { hex: '#3a3a3a' };
      var n = Math.round(cells * w[1]);
      for (var i = 0; i < n; i++) out.push(b.hex);
    });
    while (out.length < cells) out.push((byId[current.blocks.main] || { hex: '#3a3a3a' }).hex);

    // Deterministic shuffle so the mock does not flicker on every keystroke
    var seed = 7;
    for (var i = out.length - 1; i > 0; i--) {
      seed = (seed * 1103515245 + 12345) & 0x7fffffff;
      var j = seed % (i + 1);
      var t = out[i]; out[i] = out[j]; out[j] = t;
    }
    wall.innerHTML = out.slice(0, cells).map(function (hex) {
      return '<i style="background:' + esc(hex) + '"></i>';
    }).join('');
  }

  window.randomise = function () {
    var pool = PALETTE_POOLS[current.style] || PALETTE_POOLS.medieval;
    ROLES.forEach(function (role) {
      var list = pool[role];
      if (list && list.length) current.blocks[role] = list[Math.floor(Math.random() * list.length)];
    });
    renderRoles();
    renderWall();
    if (window.playSelect) playSelect();
  };

  window.applyStyle = function () {
    current.style = el('pal-style').value;
    randomise();
  };

  function loadPreset(id) {
    var p = PALETTE_PRESETS[id];
    if (!p) return;
    current.style = p.style;
    current.blocks = Object.assign({}, p.blocks);
    if (el('pal-style')) el('pal-style').value = p.style;
    renderRoles();
    renderWall();
  }

  window.copyPalette = function () {
    var lines = ROLES.map(function (r) {
      var b = byId[current.blocks[r]] || { name: current.blocks[r], id: current.blocks[r] };
      return r.charAt(0).toUpperCase() + r.slice(1) + ': ' + b.name + ' (' + b.id + ')';
    });
    MC.copy((PALETTE_STYLES[current.style] || {}).label + ' palette\n' + lines.join('\n'));
  };

  window.savePalette = function () {
    var name = prompt('Name this palette:', (PALETTE_STYLES[current.style] || {}).label + ' palette');
    if (!name) return;
    MC.api('palette_save', { name: name, style: current.style, data: JSON.stringify(current.blocks) })
      .then(function (r) {
        if (!r.ok) { MC.toast(r.error || 'Could not save', 'var(--red)'); return; }
        MC.toast('Palette saved');
        renderSaved(r.rows);
      });
  };

  function renderSaved(rows) {
    var box = el('saved-list');
    if (!box) return;
    if (!rows || !rows.length) {
      box.innerHTML = '<div class="empty-state"><div class="empty-icon">🎨</div>' +
        '<div class="empty-title">Nothing saved yet</div>' +
        '<div class="empty-sub">Build a palette and hit Save to keep it.</div></div>';
      return;
    }
    box.innerHTML = rows.map(function (p) {
      var blocks = p.palette_data || {};
      return '<div class="saved-pal">' +
        '<div class="saved-pal-swatches">' + ROLES.map(function (r) {
          var b = byId[blocks[r]] || { hex: '#3a3a3a' };
          return '<i style="background:' + esc(b.hex) + '"></i>';
        }).join('') + '</div>' +
        '<div style="flex:1;min-width:0"><div style="font-size:13px;font-weight:600">' + esc(p.palette_name) + '</div>' +
        '<div style="font-size:11px;color:var(--text3)">' + esc((PALETTE_STYLES[p.style] || {}).label || p.style || '') + '</div></div>' +
        '<button class="btn btn-ghost btn-sm" data-load-pal=\'' + esc(JSON.stringify({ style: p.style, blocks: blocks })) + '\'>Load</button>' +
        '<button class="btn btn-ghost btn-sm" data-del-pal="' + p.id + '">✕</button>' +
        '</div>';
    }).join('');
  }

  /* ═══════════ INIT ═══════════ */
  document.addEventListener('DOMContentLoaded', function () {
    if (KN_TAB === 'materials') {
      ['mat-search', 'mat-cat', 'mat-style'].forEach(function (id) {
        var e = el(id);
        if (e) e.addEventListener('input', renderMaterials);
        if (e) e.addEventListener('change', renderMaterials);
      });
      renderMaterials();
    }

    if (KN_TAB === 'palette') {
      if (KN_PRESET && PALETTE_PRESETS[KN_PRESET]) {
        loadPreset(KN_PRESET);
      } else {
        current.style = (KN_STYLE && PALETTE_POOLS[KN_STYLE]) ? KN_STYLE : 'medieval';
        if (el('pal-style')) el('pal-style').value = current.style;
        randomise();
      }
      renderSaved(SAVED_PALETTES);

      document.querySelectorAll('[data-preset]').forEach(function (b) {
        b.addEventListener('click', function () { loadPreset(b.dataset.preset); });
      });

      document.addEventListener('click', function (e) {
        var load = e.target.closest('[data-load-pal]');
        if (load) {
          try {
            var d = JSON.parse(load.dataset.loadPal);
            current.style = d.style || current.style;
            current.blocks = Object.assign({}, d.blocks);
            if (el('pal-style') && PALETTE_POOLS[current.style]) el('pal-style').value = current.style;
            renderRoles(); renderWall();
          } catch (err) {}
        }
        var del = e.target.closest('[data-del-pal]');
        if (del) {
          MC.api('palette_delete', { id: del.dataset.delPal }).then(function (r) {
            if (r.ok) renderSaved(r.rows);
          });
        }
      });
    }

    // Copy a block id from anywhere on the page
    document.addEventListener('click', function (e) {
      var c = e.target.closest('[data-copy]');
      if (c) MC.copy(c.dataset.copy);
    });

    // Open the tip named in the URL fragment
    if (location.hash.indexOf('#tip-') === 0) {
      var t = document.querySelector(location.hash);
      if (t) { t.open = true; t.scrollIntoView({ block: 'center' }); }
    }

    MC.remember({ type: 'guide', icon: '📚', title: 'Knowledge — ' + KN_TAB, href: 'knowledge.php?t=' + KN_TAB });
  });
})();
