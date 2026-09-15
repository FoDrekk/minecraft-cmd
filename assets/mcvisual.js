/* ================================================
   mcvisual.js — centralized Minecraft visual renderer
   ------------------------------------------------
   No bundled Minecraft textures exist in this project (no asset
   pipeline, and downloading Mojang's copyrighted texture packs was
   out of scope — see the PR description). Every page that needs to
   show an item or block uses THIS renderer instead of inventing its
   own: a material-accurate colour tile plus a small hand-drawn SVG
   glyph for equipment, or the Material Library's own approximate
   block colour for blocks. Swapping in real textures later means
   changing the two render functions here — nothing else.
   ================================================ */
(function (global) {
  'use strict';
  var MC = global.MC;
  if (!MC) return;

  function esc(s) { return MC.escapeHtml(s); }

  /* ── EQUIPMENT (enchantable items) ──────────────
     One glyph per equipment category, coloured by material tier. */
  var MATERIAL_COLORS = {
    wooden: '#a9825c', stone: '#8a8a8a', golden: '#f2ce4b', iron: '#d8d8dc',
    diamond: '#66e8dc', netherite: '#4a4149', leather: '#8b5a2b', chainmail: '#98a2ac'
  };
  var SPECIAL_COLORS = {
    bow: '#7a5c3a', crossbow: '#5c4a38', trident: '#3fa9c9', mace: '#8a4fd6',
    shield: '#6b5636', elytra: '#c9538a', fishing_rod: '#7a6a4a', turtle_helmet: '#3d8a4a'
  };

  function equipmentColorFor(itemId) {
    var m = itemId.match(/^(wooden|stone|golden|iron|diamond|netherite|leather|chainmail)_/);
    if (m) return MATERIAL_COLORS[m[1]];
    return SPECIAL_COLORS[itemId] || '#5a6478';
  }

  var EQUIPMENT_ICONS = {
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

  /**
   * A tile representing an equipment item (weapon/tool/armour).
   * category: one of the EQUIPMENT_ICONS keys (a "slot", e.g. "Sword").
   * size: 'lg' | 'md' | 'sm' — maps to the .mc-tile-* CSS classes (style.php).
   */
  function equipmentTile(category, itemId, size) {
    var bg = equipmentColorFor(itemId);
    var glyph = EQUIPMENT_ICONS[category] || EQUIPMENT_ICONS.Sword;
    return '<div class="mc-tile mc-tile-' + (size || 'lg') + '" style="background:' + bg + '">' +
      '<svg viewBox="0 0 24 24" fill="rgba(255,255,255,.92)" stroke="rgba(255,255,255,.92)">' + glyph + '</svg></div>';
  }

  /* ── BLOCKS (Material Library) ───────────────────
     Blocks already carry a real approximate colour in
     lib/data/blocks.php ($hex) — this just renders it consistently. */
  function blockTile(hex, size, title) {
    return '<div class="mc-block-tile mc-tile-' + (size || 'md') + '" style="background:' + esc(hex || '#3a3a3a') + '"' +
      (title ? ' title="' + esc(title) + '"' : '') + '></div>';
  }

  /* ── TEXTURE RESOLUTION ──────────────────────────
     One lookup path for every Minecraft id in the app. The manifest and
     the block palette both come from the server (lib/textures.php and
     lib/data/blocks.php via MC_DATA), so "what does this id look like?"
     is answered in exactly one place instead of per page.

     Order, most authentic first:
       1. asset      — a real file dropped in assets/textures/
       2. alias      — a real file found under a second common id
       3. equipment  — our own drawn glyph, tinted by material tier
       4. block      — the Material Library's approximate colour
       5. fallback   — neutral tile, so nothing ever renders broken

     `source` is carried through to the DOM so callers (and tests) can
     tell an authentic texture from a stand-in. Nothing here pretends a
     fallback is a real Minecraft texture. */
  var TEXTURES = (MC.data && MC.data.textures) || {};
  var BLOCK_HEX = (MC.data && MC.data.blockHex) || {};

  var ALIASES = {
    grass: 'grass_block',
    redstone_dust: 'redstone',
    wood: 'oak_planks',
    planks: 'oak_planks',
    slab: 'oak_slab',
    fence: 'oak_fence',
    door: 'spruce_door'
  };

  function normaliseId(id) {
    return String(id == null ? '' : id)
      .toLowerCase().trim()
      .replace(/^minecraft:/, '')
      .replace(/[^a-z0-9_]+/g, '_')
      .replace(/^_+|_+$/g, '');
  }

  /** Which equipment glyph an id should use, if any. */
  function categoryForId(id) {
    // pickaxe before axe: alternation is tried left to right, so
    // diamond_pickaxe must not come back as an Axe.
    var m = id.match(/_(sword|pickaxe|axe|shovel|hoe|helmet|chestplate|leggings|boots)$/);
    if (m) return m[1].charAt(0).toUpperCase() + m[1].slice(1);
    if (id === 'bow') return 'Bow';
    if (id === 'crossbow') return 'Crossbow';
    if (id === 'trident') return 'Trident';
    if (id === 'mace') return 'Mace';
    if (id === 'shield') return 'Shield';
    if (id === 'elytra') return 'Elytra';
    if (id === 'fishing_rod') return 'Fishing Rod';
    return null;
  }

  /**
   * Resolve one Minecraft id to something renderable.
   * opts.category forces an equipment glyph (the Enchantment Hub knows
   * the slot already, so it does not need to be re-derived from the id).
   * Returns { id, src, hex, category, source }.
   */
  function resolve(id, opts) {
    opts = opts || {};
    var key = normaliseId(id);

    if (TEXTURES[key]) return { id: key, src: TEXTURES[key], hex: null, category: null, source: 'asset' };

    var alias = ALIASES[key];
    if (alias && TEXTURES[alias]) {
      return { id: alias, src: TEXTURES[alias], hex: null, category: null, source: 'alias' };
    }

    var category = opts.category || categoryForId(key);
    if (category) {
      return { id: key, src: null, hex: equipmentColorFor(key), category: category, source: 'equipment' };
    }

    var hex = BLOCK_HEX[key] || (alias ? BLOCK_HEX[alias] : null);
    if (hex) return { id: key, src: null, hex: hex, category: null, source: 'block' };

    return { id: key, src: null, hex: null, category: null, source: 'fallback' };
  }

  /**
   * Markup for one Minecraft id at a given size.
   * size: 'sm' | 'md' | 'lg' | 'cell'. `label` becomes the accessible
   * name; without one the tile is decorative and hidden from readers,
   * because in every current caller the name is already adjacent text.
   */
  function render(id, opts) {
    opts = opts || {};
    var r = resolve(id, opts);
    var size = opts.size || 'md';
    var label = opts.label || '';
    var a11y = label ? ' role="img" aria-label="' + esc(label) + '"' : ' aria-hidden="true"';
    var title = label ? ' title="' + esc(label) + '"' : '';
    var base = 'mc-visual mc-visual-' + esc(size) + ' mc-visual-' + r.source;

    if (r.source === 'asset' || r.source === 'alias') {
      return '<span class="' + base + '"' + a11y + title + '>' +
        '<img src="' + esc(r.src) + '" alt="" loading="lazy" decoding="async"></span>';
    }
    if (r.source === 'equipment') {
      var glyph = EQUIPMENT_ICONS[r.category] || EQUIPMENT_ICONS.Sword;
      return '<span class="' + base + '" style="background:' + esc(r.hex) + '"' + a11y + title + '>' +
        '<svg viewBox="0 0 24 24" fill="rgba(255,255,255,.92)" stroke="rgba(255,255,255,.92)">' + glyph + '</svg></span>';
    }
    if (r.source === 'block') {
      return '<span class="' + base + '" style="background:' + esc(r.hex) + '"' + a11y + title + '></span>';
    }
    return '<span class="' + base + '"' + a11y + title + '></span>';
  }

  MC.visual = {
    equipmentTile: equipmentTile,
    equipmentColorFor: equipmentColorFor,
    blockTile: blockTile,
    EQUIPMENT_ICONS: EQUIPMENT_ICONS,
    normaliseId: normaliseId,
    resolve: resolve,
    render: render
  };
})(window);
