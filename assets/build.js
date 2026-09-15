/* ================================================
   build.js — build tools
   ------------------------------------------------
   Region maths, the block-placing commands, and the
   planning calculators.
   ================================================ */
(function () {
  'use strict';

  // The default block limit for one /fill or /clone. It is the
  // commandModificationBlockLimit gamerule, so it can be raised.
  var BLOCK_LIMIT = 32768;
  var LARGE = 8000;

  var state = { clearMode: 'all', clearRegion: 'corners' };

  function el(id) { return document.getElementById(id); }
  function v(id) { var e = el(id); return e ? e.value.trim() : ''; }
  function chk(id) { var e = el(id); return e ? e.checked : false; }
  function num(id, d) { var n = parseFloat(v(id)); return isNaN(n) ? d : n; }
  function fmt(n) { return n.toLocaleString('en-GB'); }

  /* ── REGION MATHS ────────────────────────────
     Coordinates may be absolute, relative (~) or local (^).
     Size is only knowable when both ends of an axis use the
     same form, so anything else reports as unknown rather
     than guessing. */
  function axisSize(a, b) {
    var ra = a.charAt(0) === '~', rb = b.charAt(0) === '~';
    if (a.charAt(0) === '^' || b.charAt(0) === '^') return null;
    if (ra !== rb) return null;
    var na = parseFloat(ra ? a.slice(1) || '0' : a);
    var nb = parseFloat(rb ? b.slice(1) || '0' : b);
    if (isNaN(na) || isNaN(nb)) return null;
    return Math.abs(Math.floor(nb) - Math.floor(na)) + 1;   // both corners are included
  }

  function region(p) {
    var from = [v(p + '-x1'), v(p + '-y1'), v(p + '-z1')];
    var to   = [v(p + '-x2'), v(p + '-y2'), v(p + '-z2')];
    var size = [axisSize(from[0], to[0]), axisSize(from[1], to[1]), axisSize(from[2], to[2])];
    var known = size.every(function (s) { return s !== null; });
    return {
      from: from, to: to, size: size, known: known,
      volume: known ? size[0] * size[1] * size[2] : null
    };
  }

  function shellVolume(s) {
    // Blocks in the outer shell only (hollow / outline modes).
    if (s[0] < 3 || s[1] < 3 || s[2] < 3) return s[0] * s[1] * s[2];
    return s[0] * s[1] * s[2] - (s[0] - 2) * (s[1] - 2) * (s[2] - 2);
  }

  function renderVolume(p, r, affected) {
    var box = el(p + '-volume');
    if (!box) return;
    box.classList.remove('is-warn', 'is-error');
    if (!r.known) {
      box.innerHTML = '<div class="volume-dims">Size unknown</div>' +
        '<div class="volume-count">Mixing ~ with plain numbers on the same axis, or using ^, means the size cannot be worked out here. The command still works in game.</div>';
      return;
    }
    var n = affected === undefined ? r.volume : affected;
    var msg = '';
    if (n > BLOCK_LIMIT) {
      box.classList.add('is-error');
      msg = 'Over the ' + fmt(BLOCK_LIMIT) + '-block limit for a single command. Split it into sections, or raise the commandModificationBlockLimit gamerule first.';
    } else if (n > LARGE) {
      box.classList.add('is-warn');
      msg = 'A large operation. Expect a pause, and make sure you are standing outside the region.';
    }
    box.innerHTML =
      '<div class="volume-dims">' + r.size[0] + ' × ' + r.size[1] + ' × ' + r.size[2] + '</div>' +
      '<div class="volume-count">' + fmt(n) + ' block' + (n === 1 ? '' : 's') +
      (affected !== undefined && affected !== r.volume ? ' affected of ' + fmt(r.volume) + ' in the region' : '') +
      '</div>' + (msg ? '<div class="volume-count" style="margin-top:6px">' + msg + '</div>' : '');
  }

  function limitWarnings(r, affected) {
    var w = [];
    if (!r.known) return w;
    var n = affected === undefined ? r.volume : affected;
    if (n > BLOCK_LIMIT) {
      w.push({ level: 'error', text: fmt(n) + ' blocks is over the ' + fmt(BLOCK_LIMIT) + '-block limit for one command. The game will refuse it. Split the region, or raise commandModificationBlockLimit.' });
    } else if (n > LARGE) {
      w.push({ text: fmt(n) + ' blocks — a big change. Stand outside the region and expect the game to pause briefly.' });
    }
    return w;
  }

  function blockArg(id, stateId) {
    var b = v(id) || 'stone';
    var s = stateId ? v(stateId) : '';
    return b + (s ? '[' + s.replace(/^\[|\]$/g, '') + ']' : '');
  }

  /* ═══════════ BUILDERS ═══════════ */
  var B = {};

  B.fill = function () {
    var r = region('fill');
    var mode = v('fill-mode');
    var useFilter = chk('fill-usefilter');
    var block = blockArg('fill-block', 'fill-state');
    var w = [];

    var affected = r.known ? r.volume : undefined;
    if (r.known && (mode === 'hollow' || mode === 'outline')) affected = shellVolume(r.size);
    renderVolume('fill', r, affected);
    w = w.concat(limitWarnings(r, mode === 'hollow' || mode === 'outline' ? r.volume : affected));

    var cmd = '/fill ' + r.from.join(' ') + ' ' + r.to.join(' ') + ' ' + block;
    if (useFilter) {
      cmd += ' replace ' + (v('fill-filter') || 'air');
      w.push({ text: 'Only ' + (v('fill-filter') || 'air').replace(/_/g, ' ') + ' will change — everything else in the region stays as it is.' });
    } else if (mode !== 'replace') {
      cmd += ' ' + mode;
    }

    if (mode === 'destroy') w.push({ text: 'Destroy breaks blocks properly, so they drop as items. On a large region that is a lot of entities and a lot of lag — replace is usually what you want.' });
    if (mode === 'hollow') w.push({ text: 'Hollow clears the inside to air. Use outline instead if you want to keep what is in there.' });
    if (r.known && r.size[1] === 1) w.push({ text: 'One block tall — this is a flat slab, not a box. Change Y on one corner if you meant a volume.' });
    return { cmd: cmd, warnings: w };
  };

  B.clear = function () {
    var r = region('clr');
    var w = [];
    renderVolume('clr', r);
    w = w.concat(limitWarnings(r));

    var from = r.from.join(' '), to = r.to.join(' ');
    var cmd;

    if (state.clearMode === 'trees') {
      // Leaves first: removing logs first leaves floating leaves behind.
      cmd = '/fill ' + from + ' ' + to + ' air replace #minecraft:leaves\n' +
            '/fill ' + from + ' ' + to + ' air replace #minecraft:logs';
      w.push({ text: 'Two commands — run the leaves one first, or you will be left with floating canopies.' });
      w.push({ text: 'Uses block tags, so it covers every wood type at once. Tags need Java Edition.' });
      if (!MC.isJava()) w.push({ level: 'error', text: 'Bedrock does not support #block tags. Clear each wood type separately instead.' });
    } else if (state.clearMode === 'water') {
      cmd = '/fill ' + from + ' ' + to + ' air replace water\n' +
            '/fill ' + from + ' ' + to + ' air replace lava';
      w.push({ text: 'Removing a water source can leave flowing water pouring in from outside the region. Clear a slightly larger area if that happens.' });
    } else if (state.clearMode === 'one') {
      cmd = '/fill ' + from + ' ' + to + ' air replace ' + (v('clr-block') || 'grass_block');
    } else {
      cmd = '/fill ' + from + ' ' + to + ' air';
      w.push({ level: 'warn', text: 'This removes everything in the region — terrain, water, and anything you have built. Check the corners before running it.' });
    }
    return { cmd: cmd, warnings: w };
  };

  B.replace = function () {
    var r = region('rep');
    var w = [];
    renderVolume('rep', r);
    w = w.concat(limitWarnings(r));
    var from = v('rep-from') || 'cobblestone';
    var to = blockArg('rep-to', 'rep-state');
    if (from === v('rep-to')) w.push({ text: 'The two blocks are the same, so nothing will change.' });
    return {
      cmd: '/fill ' + r.from.join(' ') + ' ' + r.to.join(' ') + ' ' + to + ' replace ' + from,
      warnings: w
    };
  };

  B.setblock = function () {
    var w = [];
    var pos = [v('sb-x') || '~', v('sb-y') || '~', v('sb-z') || '~'];
    var mode = v('sb-mode');
    var cmd = '/setblock ' + pos.join(' ') + ' ' + blockArg('sb-block', 'sb-state');
    if (mode !== 'replace') cmd += ' ' + mode;
    if (pos.every(function (c) { return c === '~'; })) {
      w.push({ text: '~ ~ ~ is the block you are standing in. Use ~ ~-1 ~ for the block under your feet.' });
    }
    var st = v('sb-state');
    if (st && /[{}]/.test(st)) {
      w.push({ level: 'error', text: 'Block states go in square brackets and use = — for example facing=north. Curly braces are for block entity data, which goes after the state.' });
    }
    return { cmd: cmd, warnings: w };
  };

  B.clone = function () {
    var r = region('cln');
    var w = [];
    renderVolume('cln', r);
    w = w.concat(limitWarnings(r));

    var dest = [v('cln-dx') || '~', v('cln-dy') || '~', v('cln-dz') || '~'];
    var mask = v('cln-mask'), mode = v('cln-mode');
    var cmd = '/clone ' + r.from.join(' ') + ' ' + r.to.join(' ') + ' ' + dest.join(' ');

    if (mask === 'filtered') {
      cmd += ' filtered ' + (v('cln-filter') || 'stone');
      if (mode !== 'normal') cmd += ' ' + mode;
    } else {
      if (mask !== 'replace' || mode !== 'normal') cmd += ' ' + mask;
      if (mode !== 'normal') cmd += ' ' + mode;
    }

    var explain = el('clone-explain');
    if (explain) {
      var size = r.known ? r.size[0] + ' × ' + r.size[1] + ' × ' + r.size[2] : 'the selected';
      var what = mask === 'masked' ? 'every block except air' : (mask === 'filtered' ? 'only ' + (v('cln-filter') || 'stone').replace(/_/g, ' ') : 'every block, air included');
      var after = mode === 'move' ? ' The original is cleared to air afterwards.' : ' The original is left where it is.';
      explain.textContent = 'Copies ' + what + ' from a ' + size + ' region, placing the copy so its lowest north-west corner sits at ' + dest.join(' ') + '.' + after;
    }

    if (mode === 'force') w.push({ text: 'Force is only needed when the source and destination overlap. Results in the overlap are unpredictable.' });
    if (mode === 'move') w.push({ level: 'warn', text: 'Move empties the original region. Make sure you have the destination right first.' });
    if (mask === 'replace') w.push({ text: 'Air is copied too, so anything already at the destination is wiped. Choose “Skip air” to paste over the existing terrain.' });
    return { cmd: cmd, warnings: w };
  };

  /* ═══════════ AREA CALCULATOR ═══════════ */
  function updateArea() {
    var r = region('area');
    renderVolume('area', r);
    var box = el('area-results');
    if (!box) return;
    if (!r.known) {
      box.innerHTML = '<div class="muted">Use plain numbers on both corners to calculate a size.</div>';
      el('area-materials').innerHTML = '';
      return;
    }
    var s = r.size, vol = r.volume;
    var floor = s[0] * s[2];
    var walls = (2 * (s[0] + s[2]) - 4) * s[1];
    var shell = shellVolume(s);

    box.innerHTML = [
      ['result', s[0] + ' × ' + s[1] + ' × ' + s[2], 'Dimensions'],
      ['result', fmt(vol), 'Total blocks'],
      ['result', fmt(floor), 'Floor area'],
      ['result', fmt(walls), 'Wall ring'],
      ['result', fmt(shell), 'Hollow shell'],
      ['result', fmt(Math.ceil(vol / 64)), 'Stacks needed'],
    ].map(function (row) {
      return '<div class="' + row[0] + '"><div class="result-val">' + row[1] + '</div><div class="result-lbl">' + row[2] + '</div></div>';
    }).join('');

    var chests = vol / 64 / 27;
    el('area-materials').innerHTML =
      '<div class="mat-row"><span>Solid fill</span><b>' + fmt(vol) + ' blocks · ' + fmt(Math.ceil(vol / 64)) + ' stacks</b></div>' +
      '<div class="mat-row"><span>Hollow box (walls, floor and ceiling)</span><b>' + fmt(shell) + ' blocks</b></div>' +
      '<div class="mat-row"><span>Floor only</span><b>' + fmt(floor) + ' blocks</b></div>' +
      '<div class="mat-row"><span>Storage for a solid fill</span><b>' + (chests < 1 ? 'less than a chest' : chests.toFixed(1) + ' double chests') + '</b></div>' +
      (vol > BLOCK_LIMIT ? '<div class="warn-line" style="margin-top:10px">⚠ Over the ' + fmt(BLOCK_LIMIT) + '-block limit for one /fill. Split it into ' + Math.ceil(vol / BLOCK_LIMIT) + ' sections.</div>' : '');
  }

  window.sendToFill = function () { carryRegion('area', 'fill'); location.href = 'build.php?t=fill'; };
  window.sendToClear = function () { carryRegion('area', 'clr'); location.href = 'build.php?t=clear'; };

  function carryRegion(from, to) {
    try {
      var data = {};
      ['x1', 'y1', 'z1', 'x2', 'y2', 'z2'].forEach(function (k) { data[k] = v(from + '-' + k); });
      sessionStorage.setItem('mc_region_' + to, JSON.stringify(data));
    } catch (e) {}
  }

  function restoreRegion(p) {
    try {
      var raw = sessionStorage.getItem('mc_region_' + p);
      if (!raw) return;
      sessionStorage.removeItem('mc_region_' + p);
      var data = JSON.parse(raw);
      Object.keys(data).forEach(function (k) { if (el(p + '-' + k)) el(p + '-' + k).value = data[k]; });
    } catch (e) {}
  }

  /* ═══════════ COORDINATE HELPER ═══════════ */
  function card(val, label, copyable) {
    return '<div class="result' + (copyable ? ' copyable' : '') + '"' + (copyable ? ' data-copy="' + MC.escapeHtml(val) + '"' : '') +
      '><div class="result-val">' + MC.escapeHtml(val) + '</div><div class="result-lbl">' + label + '</div></div>';
  }

  function updateCoords() {
    var box = el('co-results');
    if (!box) return;
    var a = [num('co-x1', 0), num('co-y1', 0), num('co-z1', 0)];
    var b = [num('co-x2', 0), num('co-y2', 0), num('co-z2', 0)];
    var d = [b[0] - a[0], b[1] - a[1], b[2] - a[2]];
    var size = d.map(function (n) { return Math.abs(n) + 1; });
    var mid = a.map(function (n, i) { return Math.round((n + b[i]) / 2); });
    var dist = Math.sqrt(d[0] * d[0] + d[1] * d[1] + d[2] * d[2]);
    var flat = Math.sqrt(d[0] * d[0] + d[2] * d[2]);

    var dir = [];
    if (d[2] < 0) dir.push('north'); else if (d[2] > 0) dir.push('south');
    if (d[0] > 0) dir.push('east'); else if (d[0] < 0) dir.push('west');
    if (d[1] > 0) dir.push('up'); else if (d[1] < 0) dir.push('down');

    box.innerHTML =
      card(size.join(' × '), 'Size (inclusive)', true) +
      card(fmt(size[0] * size[1] * size[2]), 'Blocks', false) +
      card(mid.join(' '), 'Centre point', true) +
      card(dist.toFixed(1), 'Distance', false) +
      card(flat.toFixed(1), 'Flat distance', false) +
      card(dir.length ? dir.join('-') : 'same spot', 'Direction A → B', false) +
      card(d.map(function (n) { return (n >= 0 ? '+' : '') + n; }).join(' '), 'Offset', true);

    var rel = el('co-rel-results');
    if (!rel) return;
    var p = [num('co-px', 0), num('co-py', 0), num('co-pz', 0)];
    var t = [num('co-tx', 0), num('co-ty', 0), num('co-tz', 0)];
    var tilde = t.map(function (n, i) {
      var diff = n - p[i];
      return diff === 0 ? '~' : '~' + diff;
    }).join(' ');
    rel.innerHTML =
      card(tilde, 'Relative (~)', true) +
      card(t.join(' '), 'Absolute', true) +
      card('/tp @s ' + t.join(' '), 'Teleport there', true) +
      card('/setblock ' + t.join(' ') + ' stone', 'Set a marker block', true);
  }

  /* ═══════════ BUILD PLANNER ═══════════ */
  function updatePlanner() {
    var box = el('pl-results');
    if (!box) return;
    var w = Math.max(3, num('pl-w', 11)), l = Math.max(3, num('pl-l', 9));
    var h = Math.max(2, num('pl-h', 4)), floors = Math.max(1, num('pl-floors', 1));
    var thick = Math.max(1, num('pl-thick', 1)), clear = Math.max(0, num('pl-clear', 3));
    var roof = v('pl-roof');

    var footprint = w * l;
    var totalHeight = h * floors + (roof === 'pitched' ? Math.ceil(h / 2) : (roof === 'flat' ? 1 : 0));
    var perimeter = 2 * (w + l) - 4;
    var wallBlocks = perimeter * h * floors * thick;
    var floorBlocks = footprint * (floors + (roof === 'flat' ? 1 : 0));
    var roofBlocks = roof === 'pitched' ? Math.round(footprint * 1.4) : (roof === 'flat' ? footprint : 0);
    var clearW = w + clear * 2, clearL = l + clear * 2;

    box.innerHTML =
      card(w + ' × ' + l, 'Footprint', false) +
      card(fmt(footprint), 'Floor area', false) +
      card(totalHeight + ' blocks', 'Total height', false) +
      card(clearW + ' × ' + clearL, 'Site to clear', false) +
      card(fmt(clearW * clearL * (totalHeight + 2)), 'Site volume', false) +
      card(fmt(wallBlocks + floorBlocks + roofBlocks), 'Blocks, roughly', false);

    var total = wallBlocks + floorBlocks + roofBlocks;
    el('pl-materials').innerHTML =
      '<div class="mat-row"><span>Walls (' + perimeter + ' per layer × ' + (h * floors) + ' layers' + (thick > 1 ? ' × ' + thick + ' thick' : '') + ')</span><b>' + fmt(wallBlocks) + '</b></div>' +
      '<div class="mat-row"><span>Floors and ceilings</span><b>' + fmt(floorBlocks) + '</b></div>' +
      (roofBlocks ? '<div class="mat-row"><span>Roof (' + roof + ')</span><b>' + fmt(roofBlocks) + '</b></div>' : '') +
      '<div class="mat-row"><span>Windows, doors and detail — add about 15%</span><b>' + fmt(Math.round(total * 0.15)) + '</b></div>' +
      '<div class="mat-row"><span>Gather roughly</span><b>' + fmt(Math.ceil(total * 1.15 / 64)) + ' stacks</b></div>';

    // Suggested palette from the chosen style
    var style = v('pl-style');
    var pool = PALETTE_POOLS[style] || PALETTE_POOLS.medieval;
    var picks = [];
    Object.keys(pool).forEach(function (role) {
      var list = pool[role];
      picks.push({ role: role, id: list[Math.floor(Math.random() * list.length)] });
    });
    el('pl-palette').innerHTML =
      '<div class="swatch-row">' + picks.map(function (p) {
        var b = BLOCK_DATA[p.id] || { name: p.id, hex: '#666' };
        return '<div class="swatch" style="background:' + b.hex + '" title="' + MC.escapeHtml(p.role + ': ' + b.name) + '"></div>';
      }).join('') + '</div>' +
      picks.map(function (p) {
        var b = BLOCK_DATA[p.id] || { name: p.id };
        return '<div class="mat-row"><span>' + p.role.charAt(0).toUpperCase() + p.role.slice(1) + '</span><b>' + MC.escapeHtml(b.name) + '</b></div>';
      }).join('');

    var out = el('out-planner');
    if (out) {
      var half = { x: Math.floor(clearW), z: Math.floor(clearL) };
      MC.setCommand(out,
        '/fill ~ ~ ~ ~' + (half.x - 1) + ' ~' + (totalHeight + 1) + ' ~' + (half.z - 1) + ' air',
        { warnings: limitWarnings({ known: true, size: [half.x, totalHeight + 2, half.z], volume: half.x * (totalHeight + 2) * half.z }), tab: 'planner' });
    }
  }

  /* ── CLEAR AREA: "around me" mode ─────────────
     Writes ~ relative corners into the same clr-* inputs the corner
     mode uses, so region(), renderVolume() and B.clear() stay the one
     implementation — this only decides what the corners should be. */
  function tilde(n) { return n === 0 ? '~' : '~' + n; }

  // Both corners are inclusive, so a span of `size` runs from -half to
  // size-half-1 — which is exactly what axisSize() counts back.
  function spanFrom(size) {
    var half = Math.floor((size - 1) / 2);
    return { from: -half, to: size - half - 1 };
  }

  window.clrQuickApply = function () {
    if (state.clearRegion !== 'quick') return;
    var size = Math.max(1, Math.min(128, Math.round(num('clr-size', 5))));
    var height = Math.max(1, Math.min(128, Math.round(num('clr-height', 3))));
    var dir = v('clr-dir') || 'around';

    var h = spanFrom(size);
    var y = dir === 'up' ? { from: 0, to: height - 1 }
          : dir === 'down' ? { from: -(height - 1), to: 0 }
          : spanFrom(height);

    var set = function (id, val) { var e = el(id); if (e) e.value = val; };
    set('clr-x1', tilde(h.from)); set('clr-y1', tilde(y.from)); set('clr-z1', tilde(h.from));
    set('clr-x2', tilde(h.to));   set('clr-y2', tilde(y.to));   set('clr-z2', tilde(h.to));
    rebuild();
  };

  /* ═══════════ REBUILD ═══════════ */
  window.rebuild = function () {
    var filterWrap = el('fill-filter-wrap');
    if (filterWrap) filterWrap.hidden = !chk('fill-usefilter');
    var oneWrap = el('clr-one-wrap');
    if (oneWrap) oneWrap.hidden = state.clearMode !== 'one';
    var cloneFilter = el('cln-filter-wrap');
    if (cloneFilter) cloneFilter.hidden = v('cln-mask') !== 'filtered';

    Object.keys(B).forEach(function (task) {
      var out = el('out-' + task);
      if (!out) return;
      var panel = out.closest('.panel');
      if (panel && panel.hidden) return;
      var r;
      try { r = B[task](); }
      catch (err) {
        MC.setCommand(out, '', { warnings: [{ level: 'error', text: 'Could not build this command. Check the coordinates — each one needs a number, or ~ for relative.' }] });
        return;
      }
      MC.setCommand(out, r.cmd, { warnings: r.warnings, tab: task });
    });

    updateArea();
    updateCoords();
    updatePlanner();
  };

  document.addEventListener('DOMContentLoaded', function () {
    ['fill', 'clr', 'rep', 'cln', 'area'].forEach(restoreRegion);

    document.querySelectorAll('[data-clear]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        btn.parentNode.querySelectorAll('[data-clear]').forEach(function (b) { b.classList.remove('active'); });
        btn.classList.add('active');
        state.clearMode = btn.dataset.clear;
        rebuild();
        if (window.playClick) playClick();
      });
    });

    document.querySelectorAll('[data-clr-region]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        btn.parentNode.querySelectorAll('[data-clr-region]').forEach(function (b) { b.classList.remove('active'); });
        btn.classList.add('active');
        state.clearRegion = btn.dataset.clrRegion;
        var quick = state.clearRegion === 'quick';
        var quickWrap = el('clr-quick-wrap');
        var corners = document.querySelector('#clr-corners-wrap .region');
        if (quickWrap) quickWrap.hidden = !quick;
        // Only the coordinate grid is hidden — the volume readout below it
        // stays visible, because both modes need it.
        if (corners) corners.hidden = quick;
        if (quick) clrQuickApply(); else rebuild();
        if (window.playClick) playClick();
      });
    });

    document.querySelectorAll('[data-clr-size]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        btn.parentNode.querySelectorAll('[data-clr-size]').forEach(function (b) { b.classList.remove('active'); });
        btn.classList.add('active');
        var input = el('clr-size');
        if (input) input.value = btn.dataset.clrSize;
        clrQuickApply();
        if (window.playClick) playClick();
      });
    });

    // Result cards marked copyable put their value straight on the clipboard.
    document.addEventListener('click', function (e) {
      var c = e.target.closest('[data-copy]');
      if (c) MC.copy(c.dataset.copy);
    });

    if (typeof TASK === 'string') {
      MC.remember({ type: 'tool', icon: '🧱', title: 'Build — ' + TASK, href: 'build.php?t=' + TASK });
    }
    rebuild();
  });
})();
