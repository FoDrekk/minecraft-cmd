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
     No bundled Minecraft textures exist in this project yet (see the
     PR description for why). Each item gets a material-accurate colour
     tile plus a small hand-drawn glyph for its equipment category —
     visual and instantly recognisable without shipping copyrighted
     art. Swapping in real textures later only means changing renderTile(). */
  var MATERIAL_COLORS = {
    wooden: '#a9825c', stone: '#8a8a8a', golden: '#f2ce4b', iron: '#d8d8dc',
    diamond: '#66e8dc', netherite: '#4a4149', leather: '#8b5a2b', chainmail: '#98a2ac'
  };
  var SPECIAL_COLORS = {
    bow: '#7a5c3a', crossbow: '#5c4a38', trident: '#3fa9c9', mace: '#8a4fd6',
    shield: '#6b5636', elytra: '#c9538a', fishing_rod: '#7a6a4a', turtle_helmet: '#3d8a4a'
  };

  function materialFor(itemId) {
    var m = itemId.match(/^(wooden|stone|golden|iron|diamond|netherite|leather|chainmail)_/);
    if (m) return MATERIAL_COLORS[m[1]];
    return SPECIAL_COLORS[itemId] || '#5a6478';
  }

  var ICONS = {
    Sword: '<path d="M12 2 L14 4 L8.5 18.5 L6.5 20.5 L4 18 L6 16 L4.5 14.5 L6 13 L7.5 14.5z"/><rect x="9.2" y="15.5" width="5.6" height="2.4" rx="0.5" transform="rotate(45 12 16.7)"/>',
    Pickaxe: '<path d="M3.5 8 C7 3 17 3 20.5 8 C17.5 6.7 15 8 13 10 L11 8 C13 8 14 6.5 12.5 5.3 C9 5 5.5 6.2 3.5 8 Z"/><rect x="10.3" y="9" width="2.4" height="13" rx="1" transform="rotate(45 11.5 15.5)"/>',
    Axe: '<path d="M14 2 C19 2 21 6 20 10 C18.5 9.5 16.5 10.5 15 12 L12.5 9.5 C14.5 8 15.3 6 15 4.2 C14.6 3.3 14.2 2.6 14 2Z"/><rect x="10.8" y="9.5" width="2.4" height="13" rx="1" transform="rotate(45 12 16)"/>',
    Shovel: '<path d="M9 2 C13 2 15.5 4.5 15 8.5 C14.7 10.6 13 12 11.5 12 C10 12 8.6 10.8 8.4 9 C8.2 7 9 4.5 9 2Z"/><rect x="10.8" y="11" width="2.4" height="11" rx="1"/>',
    Hoe: '<rect x="6" y="4" width="10" height="2.6" rx="1"/><rect x="10.8" y="5" width="2.4" height="17" rx="1" transform="rotate(20 12 13.5)"/>',
    Bow: '<path d="M8 2.5 C4.5 6 4.5 18 8 21.5" fill="none" stroke="currentColor" stroke-width="1.8"/><line x1="8" y1="2.5" x2="8" y2="21.5" stroke="currentColor" stroke-width="1" stroke-dasharray="1.5 1.3"/>',
    Crossbow: '<rect x="3" y="10.5" width="18" height="2" rx="1"/><rect x="10.8" y="6" width="2.4" height="16" rx="1"/><path d="M5 11.5 C7 8.5 9.5 8.5 11 11.5 M13 11.5 C14.5 8.5 17 8.5 19 11.5" fill="none" stroke="currentColor" stroke-width="1.4"/>',
    Trident: '<rect x="10.8" y="8" width="2.4" height="14" rx="1"/><path d="M7 2 L7 9 M12 2 L12 8 M17 2 L17 9 M7 9 L12 12 L17 9" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
    'Fishing Rod': '<line x1="4" y1="20" x2="18" y2="4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M18 4 C21 8 19 12 16 12" fill="none" stroke="currentColor" stroke-width="1.2"/><circle cx="16" cy="12" r="1.1"/>',
    Shield: '<path d="M12 2 L20 5 V11 C20 17 16.5 20.5 12 22 C7.5 20.5 4 17 4 11 V5 Z"/>',
    Helmet: '<path d="M4 15 C4 7 8 3 12 3 C16 3 20 7 20 15 Z"/><rect x="4" y="14" width="16" height="3" rx="1"/>',
    Chestplate: '<path d="M8 3 L12 5 L16 3 L19 6 L17 9 L18 21 L6 21 L7 9 L5 6 Z"/>',
    Leggings: '<path d="M6 3 H18 V11 L14.5 11 L14 21 L10.7 21 L10.3 12 L9.7 21 L6.4 21 L6 11 Z"/>',
    Boots: '<path d="M8 3 H14 V13 L20 15.5 C21 16.5 20.5 19 18 19 H8 C7 19 6.5 18 6.5 17 V4 C6.5 3.4 7.4 3 8 3Z"/>',
    Elytra: '<path d="M12 3 C9 7 2 9 3 17 C6 15 9.5 13 12 12 C14.5 13 18 15 21 17 C22 9 15 7 12 3Z"/>',
    Mace: '<rect x="10.8" y="12" width="2.4" height="10" rx="1"/><circle cx="12" cy="7" r="5"/><path d="M12 0 L12 3.4 M17.9 4.1 L15.5 6.1 M6.1 4.1 L8.5 6.1 M17.9 9.9 L15.5 7.9 M6.1 9.9 L8.5 7.9" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>'
  };

  function tileHtml(slot, itemId) {
    var bg = materialFor(itemId);
    var glyph = ICONS[slot] || ICONS.Sword;
    return '<div class="eh-tile" style="background:' + bg + '">' +
      '<svg viewBox="0 0 24 24" fill="rgba(255,255,255,.92)" stroke="rgba(255,255,255,.92)">' + glyph + '</svg></div>';
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
    el('eh-selected-head').innerHTML = tileHtml(state.slot, state.itemId) +
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

  /* ── BUILD + PREVIEW ──────────────────────────── */
  window.ehRebuild = function () {
    var out = el('out-ench');
    var vnote = el('eh-version-note');
    vnote.textContent = 'Compatible: Minecraft ' + (MC.v().label || '') + (MC.isJava() ? ' (Java)' : ' (Bedrock)');

    if (!state.itemId) {
      MC.setCommand(out, '', { warnings: [] });
      return;
    }

    var target = (el('eh-target').value || '@p').trim();
    var count = Math.max(1, Math.min(6400, parseInt(el('eh-count').value, 10) || 1));
    var name = (el('eh-name').value || '').trim();
    var lore = state.loreLines.map(function (l) { return l.trim(); }).filter(Boolean)
      .map(function (l) { return MC.text(l, { color: 'gray', italic: false }); });
    var enchants = Object.keys(state.enabled).map(function (id) { return { id: id, lvl: state.enabled[id] }; });

    var w = [];
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

    document.addEventListener('mc:version', function () {
      renderItemGrid();
      if (state.itemId) { renderRecommended(); renderCustomize(); }
      ehRebuild();
    });
  });
})();
