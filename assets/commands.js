/* ================================================
   commands.js — the command builders
   ------------------------------------------------
   Each builder returns { cmd, warnings }. Warnings explain the
   problem in plain language rather than quoting the parser.
   ================================================ */
(function () {
  'use strict';

  var state = {
    giveItem: 'diamond_sword',
    gm: 'survival', effMode: 'give',
    xpOp: 'add', xpUnit: 'levels',
    tpMode: 'coords', timeOp: 'set', weather: 'clear',
    diff: 'normal', locate: 'structure',
    kill: '@e[type=item]'
  };

  function el(id) { return document.getElementById(id); }
  function v(id) { var e = el(id); return e ? e.value.trim() : ''; }
  function chk(id) { var e = el(id); return e ? e.checked : false; }
  function num(id, dflt) { var n = parseInt(v(id), 10); return isNaN(n) ? dflt : n; }
  function clamp(n, lo, hi) { return Math.max(lo, Math.min(hi, n)); }
  function bedrock() { return !MC.isJava(); }

  var bedrockNote = { level: 'error', text: 'Bedrock Edition uses different syntax for this command. Switch the version selector to a Java version, or check the in-game autocomplete.' };

  /* ── TARGET CHIPS ──────────────────────────── */
  var SELECTORS = [['@p', 'nearest player'], ['@a', 'all players'], ['@s', 'yourself'], ['@r', 'random player'], ['@e', 'all entities']];

  function buildChips(containerId, inputId) {
    var box = el(containerId);
    if (!box) return;
    box.innerHTML = SELECTORS.map(function (s) {
      return '<button class="pill" data-sel="' + s[0] + '" title="' + s[1] + '">' + s[0] + '</button>';
    }).join('');
    box.addEventListener('click', function (e) {
      var b = e.target.closest('[data-sel]');
      if (!b) return;
      el(inputId).value = b.dataset.sel;
      rebuild();
    });
  }

  /* ── PILL GROUPS ───────────────────────────── */
  function wirePills(attr, key, after) {
    document.querySelectorAll('[data-' + attr + ']').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var group = btn.parentNode;
        group.querySelectorAll('[data-' + attr + ']').forEach(function (b) { b.classList.remove('active'); });
        btn.classList.add('active');
        state[key] = btn.dataset[attr.replace(/-([a-z])/g, function (m, c) { return c.toUpperCase(); })];
        if (after) after();
        rebuild();
        if (window.playClick) playClick();
      });
    });
  }

  /* ═══════════ ITEM PICKER ═══════════ */
  function renderItemList(filter) {
    var list = el('give-list');
    if (!list) return;
    var q = (filter || '').toLowerCase();
    var rows = ITEMS.filter(function (i) {
      return !q || i.id.indexOf(q) !== -1 || i.name.toLowerCase().indexOf(q) !== -1;
    }).slice(0, 120);
    if (!rows.length) {
      list.innerHTML = '<div class="picker-item muted">No item matches that. Try a shorter word.</div>';
      return;
    }
    list.innerHTML = rows.map(function (i) {
      return '<div class="picker-item' + (i.id === state.giveItem ? ' sel' : '') + '" data-item="' + i.id + '">' +
        '<span>' + MC.escapeHtml(i.name) + '</span><code>' + i.id + '</code></div>';
    }).join('');
  }

  function initGive() {
    if (!el('give-list')) return;
    renderItemList('');
    el('give-search').addEventListener('input', function () { renderItemList(this.value); });
    el('give-list').addEventListener('click', function (e) {
      var row = e.target.closest('[data-item]');
      if (!row) return;
      state.giveItem = row.dataset.item;
      el('give-picked').textContent = 'minecraft:' + state.giveItem;
      renderItemList(el('give-search').value);
      rebuild();
      if (window.playSelect) playSelect();
    });
    buildChips('give-target-chips', 'give-target');
  }

  /* ── LORE + ENCHANT ROWS ───────────────────── */
  var COLORS = ['white', 'gray', 'dark_gray', 'gold', 'yellow', 'green', 'aqua', 'blue', 'light_purple', 'red'];

  window.addLore = function () {
    var box = el('give-lore');
    var row = document.createElement('div');
    row.className = 'rowitem';
    row.innerHTML =
      '<input placeholder="Lore line" oninput="rebuild()">' +
      '<select onchange="rebuild()">' + COLORS.map(function (c) {
        return '<option value="' + c + '"' + (c === 'gray' ? ' selected' : '') + '>' + c.replace(/_/g, ' ') + '</option>';
      }).join('') + '</select>' +
      '<button title="Remove" onclick="this.parentNode.remove();rebuild()">✕</button>';
    box.appendChild(row);
    row.querySelector('input').focus();
  };

  window.addEnch = function () {
    var box = el('give-ench');
    var row = document.createElement('div');
    row.className = 'rowitem';
    row.innerHTML =
      '<select onchange="rebuild()">' + Object.keys(ENCHANTS).map(function (id) {
        return '<option value="' + id + '">' + id.replace(/_/g, ' ') + '</option>';
      }).join('') + '</select>' +
      '<input type="number" min="1" max="255" value="1" oninput="rebuild()">' +
      '<button title="Remove" onclick="this.parentNode.remove();rebuild()">✕</button>';
    box.appendChild(row);
    rebuild();
  };

  function collectLore() {
    return Array.prototype.map.call(document.querySelectorAll('#give-lore .rowitem'), function (r) {
      var text = r.querySelector('input').value;
      if (!text.trim()) return null;
      return MC.text(text, { color: r.querySelector('select').value, italic: false });
    }).filter(Boolean);
  }

  function collectEnch() {
    return Array.prototype.map.call(document.querySelectorAll('#give-ench .rowitem'), function (r) {
      return { id: r.querySelector('select').value, lvl: clamp(parseInt(r.querySelector('input').value, 10) || 1, 1, 255) };
    });
  }

  /* ═══════════ BUILDERS ═══════════ */
  var B = {};

  B.give = function () {
    var w = [], target = v('give-target') || '@p';
    var count = clamp(num('give-count', 1), 1, 6400);
    var name = v('give-name'), lore = collectLore(), ench = collectEnch();
    var unbreakable = chk('give-unbreakable'), hide = chk('give-hide');
    var custom = !!(name || lore.length || ench.length || unbreakable || hide);

    var spec = {};
    if (name) spec.name = MC.text(name, { color: v('give-name-color') || undefined, italic: false });
    if (lore.length) spec.lore = lore;
    if (ench.length) spec.enchants = ench;
    if (unbreakable) spec.unbreakable = true;
    if (hide) spec.hideFlags = true;

    if (bedrock() && custom) {
      w.push({ level: 'error', text: 'Bedrock /give cannot set custom names, lore or enchantments. Use an anvil and an enchanting table, or switch to a Java version.' });
    }
    if (count > 64) w.push({ text: 'More than one stack — the game will hand it over as ' + Math.ceil(count / 64) + ' stacks.' });

    ench.forEach(function (e) {
      var max = ENCHANTS[e.id];
      if (max && e.lvl > max) {
        w.push({ text: e.id.replace(/_/g, ' ') + ' ' + e.lvl + ' is above the normal maximum of ' + max + '. It works from a command, but not from an enchanting table or anvil.' });
      }
    });

    return { cmd: '/give ' + target + ' ' + MC.item(state.giveItem, spec) + (count > 1 ? ' ' + count : ''), warnings: w };
  };

  B.clear = function () {
    var w = [], target = v('clr-target') || '@p', item = v('clr-item'), max = num('clr-max', -1);
    var cmd = '/clear ' + target;
    if (item) {
      cmd += ' ' + item;
      if (max !== -1) cmd += ' ' + max;
    } else if (max !== -1) {
      w.push({ text: 'A maximum only applies when you name an item, so it has been left out.' });
    }
    if (!item) {
      w.push({ level: target === '@a' || target === '@e' ? 'error' : 'warn',
               text: 'No item chosen — this empties the entire inventory of ' + target + '. There is no undo.' });
    }
    if (max === 0) w.push({ text: 'A maximum of 0 counts matching items without removing any. Useful for testing.' });
    return { cmd: cmd, warnings: w };
  };

  B.gamemode = function () {
    var target = v('gm-target');
    return { cmd: '/gamemode ' + state.gm + (target ? ' ' + target : ''), warnings: [] };
  };

  B.effect = function () {
    var w = [], target = v('eff-target') || '@p', effect = v('eff-type');

    if (state.effMode === 'clear') {
      if (bedrock()) return { cmd: '/effect ' + target + ' clear', warnings: [{ text: 'Bedrock clears effects with /effect <target> clear rather than /effect clear.' }] };
      return { cmd: '/effect clear ' + target + (effect ? ' ' + effect : ''), warnings: [] };
    }

    var infinite = chk('eff-infinite');
    var dur = clamp(num('eff-dur', 30), 0, 1000000);
    var level = clamp(num('eff-level', 1), 1, 256);
    var amp = level - 1;
    var hide = chk('eff-hide');

    if (bedrock()) {
      if (infinite) w.push({ level: 'error', text: 'Bedrock has no infinite duration. Use a long duration in seconds instead.' });
      var bcmd = '/effect ' + target + ' ' + effect + ' ' + (infinite ? 99999 : dur) + ' ' + amp + (hide ? ' true' : '');
      w.push({ text: 'Bedrock syntax — no give subcommand, and the maximum duration is much lower than on Java.' });
      return { cmd: bcmd, warnings: w };
    }

    var parts = ['/effect give', target, effect];
    var durArg = infinite ? 'infinite' : String(dur);
    if (infinite || dur !== 30 || amp > 0 || hide) parts.push(durArg);
    if (amp > 0 || hide) parts.push(String(amp));
    if (hide) parts.push('true');

    if (dur === 0 && !infinite) w.push({ text: 'A duration of 0 removes the effect instead of applying it.' });
    if (level > 1) w.push({ text: 'Level ' + level + ' is written as amplifier ' + amp + ' — the game counts from zero.' });
    if (effect === 'instant_health' || effect === 'instant_damage') {
      w.push({ text: 'Instant effects ignore duration entirely; only the level matters.' });
    }
    return { cmd: parts.join(' '), warnings: w };
  };

  B.experience = function () {
    var w = [], target = v('xp-target') || '@p', amount = num('xp-amount', 30), unit = state.xpUnit;
    if (bedrock()) {
      w.push({ text: 'Bedrock uses /xp <amount>[L] <player>. L means levels; without it you are adding points.' });
      return { cmd: '/xp ' + amount + (unit === 'levels' ? 'L' : '') + ' ' + target, warnings: w };
    }
    if (state.xpOp === 'query') return { cmd: '/experience query ' + target + ' ' + unit, warnings: [] };
    if (state.xpOp === 'set' && amount < 0) {
      w.push({ level: 'error', text: 'Set needs a value of 0 or more. Use Add with a negative number to take experience away.' });
    }
    if (state.xpOp === 'add' && amount < 0) w.push({ text: 'A negative amount removes experience.' });
    return { cmd: '/experience ' + state.xpOp + ' ' + target + ' ' + amount + ' ' + unit, warnings: w };
  };

  B.teleport = function () {
    var w = [], target = v('tp-target') || '@s';
    if (state.tpMode === 'entity') {
      var dest = v('tp-dest') || '@p';
      return { cmd: '/tp ' + target + ' ' + dest, warnings: [] };
    }
    var x = v('tp-x') || '~', y = v('tp-y') || '~', z = v('tp-z') || '~';
    var coords = [x, y, z];
    var locals = coords.filter(function (c) { return c.charAt(0) === '^'; }).length;
    if (locals > 0 && locals < 3) {
      w.push({ level: 'error', text: 'Local coordinates (^) cannot be mixed with world or relative ones. Use ^ for all three, or none.' });
    }
    var cmd = '/tp ' + target + ' ' + coords.join(' ');
    var facing = v('tp-facing');
    if (facing) {
      cmd = '/teleport ' + target + ' ' + coords.join(' ') + ' facing ' + facing;
    } else {
      var yaw = v('tp-yaw'), pitch = v('tp-pitch');
      if (yaw || pitch) cmd += ' ' + (yaw || '~') + ' ' + (pitch || '~');
    }
    var yNum = parseFloat(y);
    if (y.charAt(0) !== '~' && y.charAt(0) !== '^' && !isNaN(yNum) && yNum < -64) {
      w.push({ text: 'Y below -64 is under the world floor in modern Overworld generation. You will land in the void.' });
    }
    return { cmd: cmd, warnings: w };
  };

  B.time = function () {
    var w = [], ticks = num('time-ticks', 1000);
    if (state.timeOp === 'query') return { cmd: '/time query daytime', warnings: [] };
    if (state.timeOp === 'add') return { cmd: '/time add ' + ticks, warnings: [] };
    var named = { 1000: 'day', 6000: 'noon', 13000: 'night', 18000: 'midnight' }[ticks];
    if (named) w.push({ text: '/time set ' + named + ' does exactly the same thing and is easier to remember.' });
    return { cmd: '/time set ' + ticks, warnings: w };
  };

  B.weather = function () {
    var dur = v('weather-dur');
    var w = [];
    if (dur && state.weather === 'clear') w.push({ text: 'A duration on clear weather sets how long the good weather lasts.' });
    return { cmd: '/weather ' + state.weather + (dur ? ' ' + dur : ''), warnings: w };
  };

  B.gamerule = function () {
    var sel = el('rule-select');
    if (!sel || !sel.value) return { cmd: '', warnings: [] };
    var opt = sel.options[sel.selectedIndex];
    var type = opt.dataset.type;
    var w = [];
    var value;
    if (type === 'bool') {
      var b = el('rule-bool');
      value = b && b.checked ? 'true' : 'false';
    } else {
      value = String(num('rule-int', parseInt(opt.dataset.default, 10) || 0));
      if (sel.value === 'randomTickSpeed' && parseInt(value, 10) > 100) {
        w.push({ text: 'Very high tick speeds make crops grow instantly but will lag or crash the world. Anything above about 100 is risky.' });
      }
    }
    return { cmd: '/gamerule ' + sel.value + ' ' + value, warnings: w };
  };

  B.difficulty = function () {
    var w = [];
    if (state.diff === 'peaceful') w.push({ text: 'Peaceful despawns every hostile mob immediately and stops them spawning. Mob farms will stop producing.' });
    return { cmd: '/difficulty ' + state.diff, warnings: w };
  };

  B.locate = function () {
    var w = [];
    if (bedrock()) w.push({ text: 'Bedrock uses different structure and biome ids. Check the in-game autocomplete if this one is rejected.' });
    if (state.locate === 'biome') {
      var biome = v('loc-biome');
      w.push({ text: 'Biome searches can take a moment and only look within a limited radius.' });
      return { cmd: '/locate biome ' + biome, warnings: w };
    }
    var s = v('loc-structure');
    if (s.charAt(0) === '#') w.push({ text: 'This is a tag, so it finds the nearest of any structure in that group.' });
    if (s === 'stronghold') w.push({ text: 'Strongholds are always found — there are a fixed number per world.' });
    if (['fortress', 'bastion_remnant', 'nether_fossil', 'ruined_portal_nether'].indexOf(s) !== -1) {
      w.push({ text: 'This structure only exists in the Nether. Run the command there.' });
    }
    if (s === 'end_city') w.push({ text: 'End cities only exist in the End, on the outer islands.' });
    return { cmd: '/locate structure ' + s, warnings: w };
  };

  B.summon = function () {
    var w = [], entity = v('sum-entity');
    var pos = [v('sum-x') || '~', v('sum-y') || '~', v('sum-z') || '~'];
    var nbt = {};
    var name = v('sum-name');

    if (bedrock()) {
      w.push(bedrockNote);
      return { cmd: '/summon ' + entity + ' ' + pos.join(' ') + (name ? ' ' + JSON.stringify(name) : ''), warnings: w };
    }

    if (name) nbt.CustomName = MC.raw(MC.nbtText(MC.text(name)));
    if (chk('sum-namevisible')) nbt.CustomNameVisible = MC.byte(1);
    if (chk('sum-noai')) nbt.NoAI = MC.byte(1);
    if (chk('sum-silent')) nbt.Silent = MC.byte(1);
    if (chk('sum-invuln')) nbt.Invulnerable = MC.byte(1);
    if (chk('sum-nogravity')) nbt.NoGravity = MC.byte(1);
    if (chk('sum-glow')) nbt.Glowing = MC.byte(1);
    if (chk('sum-baby')) nbt.IsBaby = MC.byte(1);
    if (chk('sum-charged')) {
      nbt.powered = MC.byte(1);
      if (entity !== 'creeper') w.push({ text: 'Charged only applies to creepers — it will be ignored on a ' + entity.replace(/_/g, ' ') + '.' });
    }
    if (chk('sum-baby') && ['zombie', 'zombie_villager', 'husk', 'drowned', 'piglin', 'hoglin', 'zoglin', 'zombified_piglin'].indexOf(entity) === -1) {
      w.push({ text: 'IsBaby works on zombie-type and piglin-type mobs. Animals use Age instead.' });
    }
    if (entity === 'wither' || entity === 'ender_dragon') {
      w.push({ level: 'error', text: 'This is a boss. It will attack immediately and destroy the area around it.' });
    }

    var body = MC.snbt(nbt);
    return { cmd: '/summon ' + entity + ' ' + pos.join(' ') + (body === '{}' ? '' : ' ' + body), warnings: w };
  };

  B.kill = function () {
    var w = [], target = v('kill-target') || '@s';
    var bare = target.replace(/\s/g, '');
    if (bare === '@e') {
      w.push({ level: 'error', text: 'This removes every entity in loaded chunks — item frames, paintings, armour stands, boats, minecarts and named pets included. Almost always narrow it with [type=…].' });
    } else if (bare === '@a') {
      w.push({ level: 'error', text: 'This kills every player on the server, including you.' });
    } else if (bare.indexOf('type=!player') !== -1) {
      w.push({ text: 'This spares players but still removes item frames, paintings, armour stands and boats.' });
    }
    if (bare.indexOf('distance') === -1 && bare.charAt(0) === '@' && bare !== '@s') {
      w.push({ text: 'No distance limit — this reaches every loaded chunk, not just what you can see. Add distance=..30 to keep it local.' });
    }
    return { cmd: '/kill ' + target, warnings: w };
  };

  /* ═══════════ GAMERULE VALUE CONTROL ═══════════ */
  function renderRuleValue() {
    var sel = el('rule-select');
    if (!sel || !sel.value) return;
    var opt = sel.options[sel.selectedIndex];
    var wrap = el('rule-value-wrap');
    var desc = el('rule-desc');
    desc.textContent = opt.dataset.desc || '';
    if (opt.dataset.type === 'bool') {
      var on = opt.dataset.default === 'true';
      wrap.innerHTML = '<label class="check-row"><input type="checkbox" id="rule-bool"' + (on ? ' checked' : '') +
        ' onchange="rebuild()"><span>Turn this on</span></label>' +
        '<div class="hint">Default in a new world: ' + opt.dataset.default + '</div>';
    } else {
      wrap.innerHTML = '<div class="field"><label>Value</label>' +
        '<input id="rule-int" type="number" value="' + opt.dataset.default + '" oninput="rebuild()">' +
        '<div class="hint">Default in a new world: ' + opt.dataset.default + '</div></div>';
    }
  }

  /* ═══════════ SHOW / HIDE SUB-SECTIONS ═══════════ */
  function syncVisibility() {
    document.querySelectorAll('[data-eff-give]').forEach(function (e) { e.hidden = state.effMode !== 'give'; });
    var c = document.querySelector('[data-tp-coords]'), en = document.querySelector('[data-tp-entity]');
    if (c) c.hidden = state.tpMode !== 'coords';
    if (en) en.hidden = state.tpMode !== 'entity';
    var ls = document.querySelector('[data-locate-structure]'), lb = document.querySelector('[data-locate-biome]');
    if (ls) ls.hidden = state.locate !== 'structure';
    if (lb) lb.hidden = state.locate !== 'biome';
  }

  /* ═══════════ REBUILD ═══════════ */
  window.rebuild = function () {
    syncVisibility();
    Object.keys(B).forEach(function (task) {
      var out = el('out-' + task);
      if (!out) return;
      var panel = out.closest('.panel');
      if (panel && panel.hidden) return;      // only the visible task
      var r;
      try { r = B[task](); }
      catch (err) {
        MC.setCommand(out, '', { warnings: [{ level: 'error', text: 'Something went wrong building this command. Check the options above and try again.' }] });
        return;
      }
      MC.setCommand(out, r.cmd, { warnings: r.warnings, tab: task });
    });
  };

  window.tpPreset = function (x, y, z) {
    el('tp-x').value = x; el('tp-y').value = y; el('tp-z').value = z;
    rebuild();
  };

  window.setTime = function (t) {
    el('time-ticks').value = t;
    state.timeOp = 'set';
    document.querySelectorAll('[data-time-op]').forEach(function (b) {
      b.classList.toggle('active', b.dataset.timeOp === 'set');
    });
    rebuild();
  };

  /* ═══════════ INIT ═══════════ */
  document.addEventListener('DOMContentLoaded', function () {
    initGive();
    buildChips('gm-target-chips', 'gm-target');

    wirePills('gm', 'gm');
    wirePills('eff-mode', 'effMode');
    wirePills('xp-op', 'xpOp');
    wirePills('xp-unit', 'xpUnit');
    wirePills('tp-mode', 'tpMode');
    wirePills('time-op', 'timeOp');
    wirePills('weather', 'weather');
    wirePills('diff', 'diff');
    wirePills('locate', 'locate');

    document.querySelectorAll('[data-kill]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        btn.parentNode.querySelectorAll('[data-kill]').forEach(function (b) { b.classList.remove('active'); });
        btn.classList.add('active');
        el('kill-target').value = btn.dataset.kill;
        rebuild();
      });
    });

    ['eff-type', 'loc-structure', 'loc-biome', 'sum-entity', 'rule-select'].forEach(function (id) {
      if (el(id)) MC.versionedSelect(id);
    });

    var ruleSel = el('rule-select'), ruleSearch = el('rule-search');
    if (ruleSel) {
      ruleSel.addEventListener('change', function () { renderRuleValue(); rebuild(); });
      renderRuleValue();
    }
    if (ruleSearch) {
      ruleSearch.addEventListener('input', function () {
        var q = this.value.toLowerCase();
        var first = null;
        Array.prototype.forEach.call(ruleSel.options, function (o) {
          var hit = !q || o.textContent.toLowerCase().indexOf(q) !== -1 || o.value.toLowerCase().indexOf(q) !== -1;
          o.hidden = !hit;
          if (hit && !first) first = o;
        });
        if (first && ruleSel.options[ruleSel.selectedIndex].hidden) {
          ruleSel.value = first.value;
          renderRuleValue();
          rebuild();
        }
      });
    }

    // The version picker rebuilds version-filtered selects, then this.
    document.addEventListener('mc:version', function () { renderRuleValue(); });

    if (typeof TASK === 'string') {
      MC.remember({ type: 'tool', icon: '⚡', title: 'Commands — ' + TASK, href: 'commands.php?t=' + TASK });
    }

    rebuild();
  });
})();
