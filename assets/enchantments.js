/* ================================================
   enchantments.js — Enchantment Hub
   ------------------------------------------------
   Select item -> Recommended -> Customize -> Conflict check ->
   Command preview. Command generation goes through the shared
   MC.item() builder (assets/mccmd.js) so it stays byte-for-byte
   consistent with every other generator across versions/editions —
   this file only decides *what* to put in the spec, never how to
   render it into NBT/components.
   ================================================ */
(function () {
  'use strict';

  var NAME_COLORS = ['white', 'gray', 'gold', 'yellow', 'green', 'aqua', 'blue', 'light_purple', 'red', 'dark_purple'];

  var state = {
    itemId: null,
    slot: null,
    enabled: {},   // enchant id -> level
    loreLines: ['']
  };

  function el(id) { return document.getElementById(id); }
  function esc(s) { return MC.escapeHtml(s); }
  function stars(n) { return '★'.repeat(n) + '☆'.repeat(5 - n); }

  /* ── VISUALS ──────────────────────────────────
     Delegates to the shared renderer (assets/mcvisual.js) so this is
     not a second copy of the same icon/colour logic. */
  function tileHtml(slot, itemId, size) {
    return MC.visual.equipmentTile(slot, itemId, size || 'lg');
  }

  /* ── ITEM PICKER ──────────────────────────────── */
  var curCat = '';

  function itemGateOk(item) {
    return item.slot !== 'Mace' || MC.has('mace');
  }

  function renderItemGrid() {
    var q = (el('eh-search').value || '').trim().toLowerCase();
    var grid = el('eh-item-grid');
    var rows = [];
    Object.keys(ENCH_ITEM_GROUPS).forEach(function (cat) {
      if (curCat && curCat !== cat) return;
      ENCH_ITEM_GROUPS[cat].forEach(function (item) {
        if (q && item.name.toLowerCase().indexOf(q) === -1) return;
        rows.push(item);
      });
    });
    if (!rows.length) {
      grid.innerHTML = '<div class="eh-empty-side" style="grid-column:1/-1">No items match.</div>';
      return;
    }
    grid.innerHTML = rows.map(function (item) {
      var locked = !itemGateOk(item);
      var sel = item.id === state.itemId ? ' sel' : '';
      return '<div class="eh-item-card' + (locked ? ' locked' : sel) + '" data-eh-item="' + item.id + '" data-eh-slot="' + item.slot + '">' +
        tileHtml(item.slot, item.id) +
        '<div class="eh-item-name">' + esc(item.name) + '</div>' +
        (locked ? '<div class="eh-item-lock">Needs Java 1.21+</div>' : '') +
        '</div>';
    }).join('');
  }

  function selectItem(itemId, slot) {
    state.itemId = itemId;
    state.slot = slot;
    state.enabled = {};
    el('eh-picked-wrap').hidden = false;
    renderItemGrid();
    renderSelectedHead();
    renderRecommended();
    renderCustomize();
    ehRebuild();
  }

  function renderSelectedHead() {
    var item = findItem(state.itemId);
    if (!item) return;
    el('eh-selected-head').innerHTML = tileHtml(state.slot, state.itemId, 'md') +
      '<div><div class="eh-selected-name">' + esc(item.name) + '</div>' +
      '<div class="eh-selected-slot">' + esc(state.slot) + '</div></div>';
  }

  function findItem(itemId) {
    for (var cat in ENCH_ITEM_GROUPS) {
      var hit = ENCH_ITEM_GROUPS[cat].find(function (i) { return i.id === itemId; });
      if (hit) return hit;
    }
    return null;
  }

  /* ── RECOMMENDED ──────────────────────────────── */
  function renderRecommended() {
    var ids = (ENCH_RECOMMENDED[state.slot] || []).filter(gateOk);
    el('eh-rec-grid').innerHTML = ids.map(function (id) {
      var m = ENCH_META[id];
      var on = state.enabled[id] !== undefined;
      return '<button type="button" class="eh-rec-card' + (on ? ' on' : '') + '" data-eh-rec="' + id + '">' +
        '<div class="eh-rec-name">' + esc(m.name) + '</div>' +
        '<div class="eh-rec-stars">' + stars(m.tier) + '</div>' +
        '<div class="eh-rec-why">' + esc(m.desc) + '</div></button>';
    }).join('');
  }

  function gateOk(id) {
    var m = ENCH_META[id];
    return !m.gate || MC.has(m.gate);
  }

  function slotEnchants() {
    return (ENCH_SLOTS[state.slot] || []).filter(function (row) { return gateOk(row[0]); });
  }

  /* ── CUSTOMIZE + CONFLICTS ────────────────────── */
  function conflictGroupsFor(ids) {
    return ENCH_CONFLICT_GROUPS.filter(function (g) {
      return ids.filter(function (id) { return g.indexOf(id) !== -1; }).length > 1;
    });
  }

  function currentConflicts() {
    var ids = Object.keys(state.enabled);
    var groups = conflictGroupsFor(ids);
    var lines = [], flagged = {};
    groups.forEach(function (g) {
      var involved = ids.filter(function (id) { return g.indexOf(id) !== -1; });
      involved.forEach(function (id) { flagged[id] = true; });
      var names = involved.map(function (id) { return ENCH_META[id].name; });
      lines.push(names.join(' and ') + ' cannot normally be combined.');
    });
    return { lines: lines, flagged: flagged };
  }

  function renderCustomize() {
    var rows = slotEnchants();
    var conf = currentConflicts();
    var advanced = el('eh-advanced').checked;

    var banner = el('eh-conflict-banner');
    if (conf.lines.length) {
      banner.hidden = false;
      banner.className = 'eh-conflict-banner';
      banner.innerHTML = '<div class="title">⚠ Conflict</div>' +
        conf.lines.map(function (l) { return '<div class="eh-conflict-line">' + esc(l) + '</div>'; }).join('') +
        '<button class="btn btn-red btn-sm" style="margin-top:8px" onclick="ehFixConflicts()">Fix conflicts</button>';
    } else {
      banner.hidden = true;
      banner.innerHTML = '';
    }

    el('eh-ench-list').innerHTML = rows.map(function (row) {
      var id = row[0], max = row[1];
      var m = ENCH_META[id];
      var level = state.enabled[id];
      var checked = level !== undefined;
      var cap = advanced ? 255 : max;
      return '<div class="eh-ench-row' + (conf.flagged[id] ? ' conflict' : '') + '">' +
        '<input type="checkbox" id="eh-ck-' + id + '" ' + (checked ? 'checked' : '') + ' onchange="ehToggle(\'' + id + '\',' + max + ')">' +
        '<div class="eh-ench-main"><label for="eh-ck-' + id + '"><div class="eh-ench-name">' + esc(m.name) +
        ' <span class="eh-ench-stars">' + stars(m.tier) + '</span></div>' +
        '<div class="eh-ench-desc">' + esc(m.desc) + '</div></label></div>' +
        '<div class="eh-ench-level">' +
        '<input type="number" min="1" max="' + cap + '" value="' + (checked ? level : max) + '" ' + (checked ? '' : 'disabled') +
        ' oninput="ehSetLevel(\'' + id + '\',this.value,' + cap + ')">' +
        '<span class="max">/ ' + cap + '</span></div></div>';
    }).join('');
  }

  window.ehToggleAdvanced = function () {
    // Every row's level cap is baked into its rendered oninput handler, so
    // flipping "advanced" needs a full re-render — not just a rebuild —
    // or an already-open row keeps enforcing the old (normal) maximum.
    renderCustomize();
    ehRebuild();
  };

  window.ehToggle = function (id, max) {
    if (state.enabled[id] !== undefined) delete state.enabled[id];
    else state.enabled[id] = max;
    renderRecommended();
    renderCustomize();
    ehRebuild();
  };

  window.ehSetLevel = function (id, value, cap) {
    var n = Math.max(1, Math.min(cap, parseInt(value, 10) || 1));
    state.enabled[id] = n;
    ehRebuild();
  };

  window.ehFixConflicts = function () {
    var ids = Object.keys(state.enabled);
    var groups = conflictGroupsFor(ids);
    var removed = [];
    groups.forEach(function (g) {
      var involved = ids.filter(function (id) { return g.indexOf(id) !== -1 && state.enabled[id] !== undefined; });
      // Keep the first (highest in the list, i.e. the one already picked
      // earliest), drop the rest — and say exactly what changed.
      involved.slice(1).forEach(function (id) {
        if (state.enabled[id] !== undefined) { delete state.enabled[id]; removed.push(ENCH_META[id].name); }
      });
    });
    renderRecommended();
    renderCustomize();
    ehRebuild();
    if (removed.length) MC.toast('Removed: ' + removed.join(', '), 'var(--red)');
  };

  document.addEventListener('click', function (e) {
    var rec = e.target.closest('[data-eh-rec]');
    if (rec) {
      var id = rec.dataset.ehRec;
      var max = (ENCH_SLOTS[state.slot] || []).find(function (r) { return r[0] === id; });
      window.ehToggle(id, max ? max[1] : 1);
      return;
    }
    var card = e.target.closest('[data-eh-item]');
    if (card && !card.classList.contains('locked')) {
      selectItem(card.dataset.ehItem, card.dataset.ehSlot);
      return;
    }
    var chip = e.target.closest('[data-eh-target]');
    if (chip) {
      el('eh-target').value = chip.dataset.ehTarget;
      ehRebuild();
    }
  });

  /* ── NAME / LORE ──────────────────────────────── */
  window.ehAddLore = function () {
    if (state.loreLines.length >= 4) return;
    state.loreLines.push('');
    renderLore();
  };

  function renderLore() {
    el('eh-lore-lines').innerHTML = state.loreLines.map(function (line, i) {
      return '<input type="text" placeholder="Lore line ' + (i + 1) + '" value="' + esc(line) + '" ' +
        'oninput="ehSetLore(' + i + ',this.value)" maxlength="60">';
    }).join('');
  }

  window.ehSetLore = function (i, value) { state.loreLines[i] = value; ehRebuild(); };

  /* ── TARGET ───────────────────────────────────── */
  function renderTargetSays(check) {
    var says = el('eh-target-says');
    if (!says) return;
    // The shared validation component, so this reads identically to a
    // validation message anywhere else in the app.
    var kind = check.level === 'error' ? 'error' : (check.level === 'warn' ? 'warn' : 'ok');
    says.innerHTML = MC.ui.validation(kind, check.says);

    // A quick chip stays lit only while the field still holds its value.
    var current = (el('eh-target').value || '').trim();
    document.querySelectorAll('[data-eh-target]').forEach(function (b) {
      b.classList.toggle('active', b.dataset.ehTarget === current);
    });
  }

  /* ── BUILD + PREVIEW ──────────────────────────── */
  window.ehRebuild = function () {
    var out = el('out-ench');
    var vnote = el('eh-version-note');
    vnote.textContent = 'Compatible: Minecraft ' + (MC.v().label || '') + (MC.isJava() ? ' (Java)' : ' (Bedrock)');

    if (!state.itemId) {
      MC.setCommand(out, '', { warnings: [] });
      return;
    }

    var target = (el('eh-target').value || '').trim();
    var targetCheck = MC.validateTarget(target);
    renderTargetSays(targetCheck);
    if (!targetCheck.ok) {
      MC.setCommand(out, '', { warnings: [{ level: 'error', text: targetCheck.says }], tab: 'enchant' });
      return;
    }
    var count = Math.max(1, Math.min(6400, parseInt(el('eh-count').value, 10) || 1));
    var name = (el('eh-name').value || '').trim();
    var lore = state.loreLines.map(function (l) { return l.trim(); }).filter(Boolean)
      .map(function (l) { return MC.text(l, { color: 'gray', italic: false }); });
    var enchants = Object.keys(state.enabled).map(function (id) { return { id: id, lvl: state.enabled[id] }; });

    var w = [];
    if (targetCheck.level === 'warn') w.push({ text: targetCheck.says });
    var conf = currentConflicts();
    conf.lines.forEach(function (l) { w.push({ text: l + ' The command below still works, but this could not be produced with an anvil or enchanting table.' }); });

    var custom = !!(name || lore.length || enchants.length);
    if (!MC.isJava() && custom) {
      w.push({ level: 'error', text: 'Bedrock /give cannot set custom names, lore or enchantments. Switch to a Java version, or apply these in-game with an anvil and enchanting table.' });
    }

    enchants.forEach(function (e) {
      var slotRow = (ENCH_SLOTS[state.slot] || []).find(function (r) { return r[0] === e.id; });
      var normalMax = slotRow ? slotRow[1] : e.lvl;
      if (e.lvl > normalMax) {
        w.push({ text: ENCH_META[e.id].name + ' ' + e.lvl + ' is above the normal maximum of ' + normalMax + '. It works from a command, but not from an enchanting table or anvil.' });
      }
    });

    var spec = {};
    if (name) spec.name = MC.text(name, { color: el('eh-name-color').value || undefined, italic: false });
    if (lore.length) spec.lore = lore;
    if (enchants.length) spec.enchants = enchants;

    var cmd = '/give ' + target + ' ' + MC.item(state.itemId, spec) + (count > 1 ? ' ' + count : '');
    MC.setCommand(out, cmd, { warnings: w, tab: 'enchant' });
  };

  /* ── INIT ─────────────────────────────────────── */
  document.addEventListener('DOMContentLoaded', function () {
    var colorSel = el('eh-name-color');
    colorSel.innerHTML = '<option value="">Default</option>' + NAME_COLORS.map(function (c) {
      return '<option value="' + c + '">' + c.replace(/_/g, ' ') + '</option>';
    }).join('');

    el('eh-search').addEventListener('input', renderItemGrid);
    document.getElementById('eh-cat-tabs').addEventListener('click', function (e) {
      var btn = e.target.closest('[data-eh-cat]');
      if (!btn) return;
      curCat = btn.dataset.ehCat;
      document.querySelectorAll('[data-eh-cat]').forEach(function (b) { b.classList.toggle('active', b === btn); });
      renderItemGrid();
    });

    renderLore();
    renderItemGrid();

    // Deep link from Knowledge -> Items ("Enchant this ->").
    var params = new URLSearchParams(location.search);
    var preselect = params.get('item');
    if (preselect) {
      var item = findItem(preselect);
      if (item && itemGateOk(item)) selectItem(item.id, item.slot);
    }

    document.addEventListener('mc:version', function () {
      renderItemGrid();
      if (state.itemId) { renderRecommended(); renderCustomize(); }
      ehRebuild();
    });
  });
})();
