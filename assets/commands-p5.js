/* ================================================
   commands-p5.js — text, display and advanced builders
   ------------------------------------------------
   Registers into the same builder map as assets/commands.js through
   window.CMD, so these share the task rail, version selector, command
   output and rebuild loop rather than re-implementing them.
   ================================================ */
(function () {
  'use strict';

  var C = window.CMD;
  if (!C) return;
  var el = C.el, v = C.v, chk = C.chk, num = C.num, numf = C.numf, clamp = C.clamp;
  var esc = function (s) { return MC.escapeHtml(s); };

  /* ═══════════════════════════════════════════════
     TELLRAW
     ═══════════════════════════════════════════════ */

  var TR_STYLES = [
    ['bold', 'B', 'Bold'],
    ['italic', 'I', 'Italic'],
    ['underlined', 'U', 'Underlined'],
    ['strikethrough', 'S', 'Strikethrough'],
    ['obfuscated', 'O', 'Obfuscated — scrambling characters']
  ];

  // Only actions whose 1.21.5 field name is confirmed are offered, so the
  // builder never emits a guess. See mc.php > text_event_snake.
  var TR_CLICK = {
    '': 'None',
    'run_command': 'Run a command',
    'suggest_command': 'Put a command in the chat box',
    'open_url': 'Open a link'
  };

  var trSeq = 0;

  window.trAddSegment = function (type) {
    var box = el('tr-segments');
    if (!box) return;
    var id = 'trs' + (++trSeq);
    var row = document.createElement('div');
    row.className = 'seg';
    row.id = id;
    row.innerHTML =
      '<div class="seg-main">' +
        '<select class="seg-type" onchange="trSyncSegment(\'' + id + '\');rebuild()">' +
          '<option value="text">Text</option>' +
          '<option value="selector">Player name</option>' +
          '<option value="score">Score</option>' +
          '<option value="keybind">Key binding</option>' +
        '</select>' +
        '<input class="seg-value" placeholder="Message text" oninput="rebuild()">' +
        '<input class="seg-extra" placeholder="Objective" oninput="rebuild()" hidden>' +
        '<select class="seg-color" onchange="rebuild()">' +
          '<option value="">Default</option>' +
          Object.keys(TR_COLORS).map(function (c) {
            return '<option value="' + c + '">' + c.replace(/_/g, ' ') + '</option>';
          }).join('') +
        '</select>' +
        '<span class="seg-styles">' +
          TR_STYLES.map(function (s) {
            return '<button class="seg-style" data-style="' + s[0] + '" title="' + esc(s[2]) + '">' + s[1] + '</button>';
          }).join('') +
        '</span>' +
        '<button class="seg-del" title="Remove segment" onclick="this.closest(\'.seg\').remove();rebuild()">✕</button>' +
      '</div>' +
      '<details class="seg-adv"><summary>Link &amp; hover</summary><div class="seg-adv-body">' +
        '<div class="row">' +
          '<div class="field"><label>When clicked</label>' +
            '<select class="seg-click" onchange="rebuild()">' +
              Object.keys(TR_CLICK).map(function (a) {
                return '<option value="' + a + '">' + esc(TR_CLICK[a]) + '</option>';
              }).join('') +
            '</select></div>' +
          '<div class="field"><label>Click value</label>' +
            '<input class="seg-click-value" placeholder="/say hello" oninput="rebuild()"></div>' +
        '</div>' +
        '<div class="field"><label>Hover text</label>' +
          '<input class="seg-hover" placeholder="Shown when the mouse is over this segment" oninput="rebuild()"></div>' +
      '</div></details>';
    box.appendChild(row);

    row.querySelectorAll('.seg-style').forEach(function (b) {
      b.addEventListener('click', function () {
        b.classList.toggle('on');
        C.rebuild();
      });
    });

    if (type) {
      row.querySelector('.seg-type').value = type;
      trSyncSegment(id);
    }
    row.querySelector('.seg-value').focus();
    C.rebuild();
  };

  window.trSyncSegment = function (id) {
    var row = el(id);
    if (!row) return;
    var type = row.querySelector('.seg-type').value;
    var value = row.querySelector('.seg-value');
    var extra = row.querySelector('.seg-extra');
    extra.hidden = type !== 'score';
    value.placeholder = {
      text: 'Message text',
      selector: '@p',
      score: 'Score holder — a name or selector',
      keybind: 'key.jump'
    }[type] || 'Message text';
  };

  function trSegments() {
    return Array.prototype.map.call(document.querySelectorAll('#tr-segments .seg'), function (row) {
      var styles = {};
      row.querySelectorAll('.seg-style').forEach(function (b) {
        if (b.classList.contains('on')) styles[b.dataset.style] = true;
      });
      return {
        type: row.querySelector('.seg-type').value,
        value: row.querySelector('.seg-value').value,
        extra: row.querySelector('.seg-extra').value,
        color: row.querySelector('.seg-color').value,
        styles: styles,
        click: row.querySelector('.seg-click').value,
        clickValue: row.querySelector('.seg-click-value').value,
        hover: row.querySelector('.seg-hover').value
      };
    });
  }

  /** One segment as a text component object, in this version's shape. */
  function trComponent(seg, warnings) {
    var snake = MC.has('text_event_snake');
    var c = {};

    if (seg.type === 'selector') c.selector = seg.value || '@p';
    else if (seg.type === 'keybind') c.keybind = seg.value || 'key.jump';
    else if (seg.type === 'score') {
      c.score = { name: seg.value || '@p', objective: seg.extra || '' };
      if (!seg.extra) warnings.push({ level: 'error', text: 'A score segment needs an objective name.' });
    } else c.text = seg.value;

    if (seg.color) c.color = seg.color;
    Object.keys(seg.styles).forEach(function (k) { c[k] = true; });

    if (seg.click && seg.clickValue) {
      var field = seg.click === 'open_url' ? 'url' : 'command';
      if (snake) {
        c.click_event = { action: seg.click };
        c.click_event[field] = seg.clickValue;
      } else {
        c.clickEvent = { action: seg.click, value: seg.clickValue };
      }
      if (seg.click === 'open_url' && !/^https?:\/\//i.test(seg.clickValue)) {
        warnings.push({ level: 'error', text: 'A link must start with http:// or https:// — the game rejects anything else.' });
      }
      if ((seg.click === 'run_command' || seg.click === 'suggest_command') && seg.clickValue.charAt(0) !== '/') {
        warnings.push({ text: 'Click commands usually start with a slash, for example /say hello.' });
      }
    } else if (seg.click && !seg.clickValue) {
      warnings.push({ text: 'A click action is set on one segment but has no value, so it has been left out.' });
    }

    if (seg.hover) {
      var hoverText = { text: seg.hover };
      if (snake) c.hover_event = { action: 'show_text', value: hoverText };
      else c.hoverEvent = { action: 'show_text', contents: hoverText };
    }
    return c;
  }

  function trPreview(segs) {
    var box = el('tr-preview');
    if (!box) return;
    if (!segs.length) {
      box.innerHTML = '<span class="chat-empty">Add a segment to see the message.</span>';
      return;
    }
    box.innerHTML = segs.map(function (s) {
      var text = s.type === 'selector' ? (s.value || '@p')
               : s.type === 'score' ? '12'
               : s.type === 'keybind' ? 'SPACE'
               : s.value;
      if (!text) return '';
      var css = 'color:' + (TR_COLORS[s.color] || '#e8ecf4');
      if (s.styles.bold) css += ';font-weight:700';
      if (s.styles.italic) css += ';font-style:italic';
      var deco = [];
      if (s.styles.underlined) deco.push('underline');
      if (s.styles.strikethrough) deco.push('line-through');
      if (deco.length) css += ';text-decoration:' + deco.join(' ');
      var cls = s.styles.obfuscated ? ' class="obf"' : '';
      var title = s.hover ? ' title="' + esc(s.hover) + '"' : '';
      return '<span style="' + css + '"' + cls + title + '>' + esc(text) + '</span>';
    }).join('');
  }

  C.register('tellraw', function () {
    var w = [], target = v('tr-target') || '@a';

    if (chk('tr-raw')) {
      var raw = v('tr-raw-json');
      if (!raw) {
        return { cmd: '', warnings: [{ text: 'Raw mode is on — paste or type a text component below.' }] };
      }
      try { JSON.parse(raw); }
      catch (err) {
        w.push({ level: 'error', text: 'That is not valid JSON: ' + err.message + '.' +
          (MC.has('text_event_snake') ? ' From 1.21.5 the game also accepts SNBT here, which this check cannot verify.' : '') });
      }
      return { cmd: '/tellraw ' + target + ' ' + raw.replace(/\s*\n\s*/g, ' '), warnings: w };
    }

    var segs = trSegments().filter(function (s) {
      return s.value || s.type === 'score';
    });
    trPreview(segs);

    if (!segs.length) {
      return { cmd: '', warnings: [{ text: 'Add at least one segment to build the message.' }] };
    }

    var parts = segs.map(function (s) { return trComponent(s, w); });
    // A single component is written on its own; several become a list.
    var payload = parts.length === 1 ? parts[0] : parts;

    if (MC.has('text_event_snake')) {
      w.push({ text: 'Written for ' + MC.v().label + ': from 1.21.5 the fields are click_event and hover_event. Older versions need clickEvent and hoverEvent — switch the version above to get those.' });
    }
    if (!MC.isJava()) {
      w.push({ level: 'error', text: 'Bedrock has no /tellraw text components in this form. It uses /tellraw <target> {"rawtext":[...]} instead, which this builder does not generate.' });
    }

    return { cmd: '/tellraw ' + target + ' ' + JSON.stringify(payload), warnings: w };
  });

  C.onInit(function () {
    if (!el('tr-segments')) return;
    C.buildChips('tr-target-chips', 'tr-target');
    trAddSegment();
    var first = document.querySelector('#tr-segments .seg-value');
    if (first && !first.value) first.value = 'Hello world';
  });


  /* ═══════════════════════════════════════════════
     PARTICLE
     ═══════════════════════════════════════════════ */

  var ptSelected = 'flame';

  function ptRenderList(filter) {
    var list = el('pt-list');
    if (!list) return;
    var q = (filter || '').toLowerCase();
    var ids = Object.keys(PARTICLES).filter(function (id) {
      return !q || id.indexOf(q) !== -1 || PARTICLES[id].label.toLowerCase().indexOf(q) !== -1;
    });
    if (!ids.length) {
      list.innerHTML = '<div class="picker-item muted">No particle matches that.</div>';
      return;
    }
    list.innerHTML = ids.map(function (id) {
      var p = PARTICLES[id];
      var opt = PARTICLE_OPTS[id] ? ' <span class="pt-flag">options</span>' : '';
      return '<div class="picker-item' + (id === ptSelected ? ' sel' : '') + '" data-particle="' + id + '">' +
        '<span><i class="pt-dot" style="background:' + esc(p.hex) + '"></i>' + esc(p.label) + opt + '</span>' +
        '<code>' + id + '</code></div>';
    }).join('');
  }

  function ptSyncOptions() {
    var wrap = el('pt-options');
    if (!wrap) return;
    var spec = PARTICLE_OPTS[ptSelected];
    wrap.hidden = !spec;
    if (!spec) return;
    el('pt-options-note').textContent = spec.note;
    var kind = spec.kind;
    var show = {
      color:  kind === 'dust' || kind === 'dust_transition',
      color2: kind === 'dust_transition',
      block:  kind === 'block_state',
      item:   kind === 'item',
      number: kind === 'roll' || kind === 'delay'
    };
    Object.keys(show).forEach(function (k) {
      var row = document.querySelector('[data-pt-opt="' + k + '"]');
      if (row) row.hidden = !show[k];
    });
  }

  /** #rrggbb to the 0–1 float triple Minecraft wants. */
  function ptRgb(hex) {
    var m = /^#?([0-9a-f]{6})$/i.exec(hex || '');
    if (!m) return [1, 0, 0];
    var n = parseInt(m[1], 16);
    return [((n >> 16) & 255) / 255, ((n >> 8) & 255) / 255, (n & 255) / 255];
  }

  var ptNum = function (n) {
    // Minecraft accepts plain decimals; keep them short but unambiguous.
    return (Math.round(n * 1000) / 1000).toString();
  };

  /** Particle argument: id plus options, in this version's shape. */
  function ptArgument(w) {
    var spec = PARTICLE_OPTS[ptSelected];
    if (!spec) return ptSelected;

    var snbt = MC.has('particle_snbt');
    var kind = spec.kind;

    if (kind === 'dust' || kind === 'dust_transition') {
      var c = ptRgb(v('pt-color'));
      var scale = numf('pt-scale', 1);
      if (kind === 'dust') {
        return snbt
          ? ptSelected + '{color:[' + c.map(ptNum).join(',') + '],scale:' + ptNum(scale) + '}'
          : ptSelected + ' ' + c.map(ptNum).join(' ') + ' ' + ptNum(scale);
      }
      var c2 = ptRgb(v('pt-color2'));
      return snbt
        ? ptSelected + '{from_color:[' + c.map(ptNum).join(',') + '],scale:' + ptNum(scale) +
          ',to_color:[' + c2.map(ptNum).join(',') + ']}'
        : ptSelected + ' ' + c.map(ptNum).join(' ') + ' ' + ptNum(scale) + ' ' + c2.map(ptNum).join(' ');
    }

    if (kind === 'block_state') {
      var block = (v('pt-block') || 'stone').replace(/^minecraft:/, '');
      return snbt ? ptSelected + '{block_state:"minecraft:' + block + '"}' : ptSelected + ' ' + block;
    }

    if (kind === 'item') {
      var item = (v('pt-item') || 'apple').replace(/^minecraft:/, '');
      return snbt ? ptSelected + '{item:{id:"minecraft:' + item + '",count:1}}' : ptSelected + ' ' + item;
    }

    var n = numf('pt-number', 1);
    if (kind === 'roll') return snbt ? ptSelected + '{roll:' + ptNum(n) + '}' : ptSelected + ' ' + ptNum(n);
    if (kind === 'delay') return snbt ? ptSelected + '{delay:' + Math.round(n) + '}' : ptSelected + ' ' + Math.round(n);
    return ptSelected;
  }

  function ptPreview(count, spread) {
    var box = el('pt-preview');
    if (!box) return;
    var spec = PARTICLE_OPTS[ptSelected];
    var hex = (spec && (spec.kind === 'dust' || spec.kind === 'dust_transition'))
      ? v('pt-color') : (PARTICLES[ptSelected] || {}).hex || '#ffffff';
    var dots = Math.max(1, Math.min(60, count || 1));
    var reach = Math.max(0, Math.min(1, (spread || 0) / 3));
    var html = '';
    var seed = 11;
    for (var i = 0; i < dots; i++) {
      seed = (seed * 1103515245 + 12345) & 0x7fffffff;
      var x = 50 + ((seed % 1000) / 1000 - 0.5) * 92 * reach;
      seed = (seed * 1103515245 + 12345) & 0x7fffffff;
      var y = 50 + ((seed % 1000) / 1000 - 0.5) * 78 * reach;
      html += '<i style="left:' + x.toFixed(1) + '%;top:' + y.toFixed(1) + '%;background:' + esc(hex) + '"></i>';
    }
    box.innerHTML = html;
  }

  C.register('particle', function () {
    var w = [];
    var pos = [v('pt-x') || '~', v('pt-y') || '~', v('pt-z') || '~'];
    var delta = [numf('pt-dx', 0), numf('pt-dy', 0), numf('pt-dz', 0)];
    var speed = Math.max(0, numf('pt-speed', 0));
    var count = Math.max(0, Math.round(numf('pt-count', 1)));
    var mode = v('pt-mode') || 'normal';
    var viewers = v('pt-viewers');

    ptSyncOptions();
    ptPreview(count, Math.max(delta[0], delta[1], delta[2]));

    var locals = pos.filter(function (c) { return c.charAt(0) === '^'; }).length;
    if (locals > 0 && locals < 3) {
      w.push({ level: 'error', text: 'Local coordinates (^) cannot be mixed with ~ or plain numbers. Use ^ for all three, or none.' });
    }

    var parts = ['/particle', ptArgument(w), pos.join(' ')];
    var needsTail = viewers || mode !== 'normal';
    if (delta.some(function (d) { return d !== 0; }) || speed !== 0 || count !== 1 || needsTail) {
      parts.push(delta.map(ptNum).join(' '), ptNum(speed), String(count));
    }
    if (needsTail) parts.push(mode);
    if (viewers) parts.push(viewers);

    if (count === 0) {
      w.push({ text: 'With a count of 0 the delta becomes a direction and speed becomes how fast the particles travel that way. One particle is spawned per command.' });
      if (speed === 0) w.push({ text: 'Count 0 and speed 0 together produce a single motionless particle.' });
    }
    if (count > 1000) w.push({ text: count.toLocaleString('en-GB') + ' particles in one command will hit frame rate. A few hundred is usually plenty.' });
    if (mode === 'normal' && viewers) {
      w.push({ text: 'Normal mode only shows particles within about 32 blocks, and players on reduced particle settings may not see them at all.' });
    }
    if (!MC.has('particle_snbt') && PARTICLE_OPTS[ptSelected]) {
      w.push({ text: 'On ' + MC.v().label + ' the extra values go after the particle name as separate arguments. From 1.20.5 they moved into braces after the name instead.' });
    }
    if (!MC.isJava()) {
      w.push({ level: 'error', text: 'Bedrock uses /particle <effect> <position> with its own particle ids and no delta, speed or count. This builder generates Java syntax only.' });
    }

    return { cmd: parts.join(' '), warnings: w };
  });

  C.onInit(function () {
    if (!el('pt-list')) return;
    ptRenderList('');
    el('pt-search').addEventListener('input', function () { ptRenderList(this.value); });
    el('pt-list').addEventListener('click', function (e) {
      var row = e.target.closest('[data-particle]');
      if (!row) return;
      ptSelected = row.dataset.particle;
      el('pt-picked').textContent = 'minecraft:' + ptSelected;
      ptRenderList(el('pt-search').value);
      ptSyncOptions();
      C.rebuild();
      if (window.playSelect) playSelect();
    });
    ptSyncOptions();
  });


  /* ═══════════════════════════════════════════════
     PLAYSOUND
     ═══════════════════════════════════════════════ */

  var psSelected = 'block.note_block.pling';

  function psRenderList(filter) {
    var list = el('ps-list');
    if (!list) return;
    var q = (filter || '').toLowerCase();
    var html = '';
    Object.keys(SOUNDS).forEach(function (group) {
      var rows = Object.keys(SOUNDS[group]).filter(function (id) {
        return !q || id.indexOf(q) !== -1 || SOUNDS[group][id].toLowerCase().indexOf(q) !== -1;
      });
      if (!rows.length) return;
      html += '<div class="picker-group">' + esc(group) + '</div>';
      html += rows.map(function (id) {
        return '<div class="picker-item' + (id === psSelected ? ' sel' : '') + '" data-sound="' + esc(id) + '">' +
          '<span>' + esc(SOUNDS[group][id]) + '</span><code>' + esc(id) + '</code></div>';
      }).join('');
    });
    list.innerHTML = html || '<div class="picker-item muted">No sound matches that. Use the custom id box below.</div>';
  }

  C.register('playsound', function () {
    var w = [];
    var custom = v('ps-custom').trim();
    var sound = custom || psSelected;
    var source = v('ps-source') || 'master';
    var target = v('ps-target');
    var usePos = chk('ps-usepos');

    var posRow = el('ps-pos-row');
    if (posRow) posRow.hidden = !usePos;

    if (!target) {
      return { cmd: '', warnings: [{ level: 'error', text: 'Playsound needs a target — who should hear it. Use a player name or a selector like @a.' }] };
    }

    var parts = ['/playsound', sound, source, target];
    var volume = Math.max(0, numf('ps-volume', 1));
    var pitch = numf('ps-pitch', 1);
    var min = Math.max(0, Math.min(1, numf('ps-min', 0)));
    var pos = [v('ps-x') || '~', v('ps-y') || '~', v('ps-z') || '~'];

    // Position must be present before volume, pitch or min can be given.
    var needsTail = volume !== 1 || pitch !== 1 || min !== 0;
    if (usePos || needsTail) {
      if (!usePos && needsTail) {
        w.push({ text: 'Volume, pitch and minimum volume can only be given after a position, so ' +
          pos.join(' ') + ' has been filled in. Tick the position box above to choose it deliberately.' });
      }
      parts.push(pos.join(' '));
    }
    if (needsTail) {
      parts.push(String(volume), String(pitch));
      if (min !== 0) parts.push(String(min));
    }

    if (pitch < 0.5 || pitch > 2) {
      w.push({ text: 'Pitch is clamped by the game to between 0.5 and 2, so ' + pitch + ' will behave as ' +
        Math.max(0.5, Math.min(2, pitch)) + '.' });
    }
    if (volume > 1) {
      w.push({ text: 'Volume above 1 does not make the sound louder — it widens how far it carries, to about ' +
        Math.round(volume * 16) + ' blocks.' });
    }
    if (custom && !/^[a-z0-9_.\-:]+$/.test(custom)) {
      w.push({ level: 'error', text: 'A sound id can only contain lower-case letters, numbers, dots, underscores and a namespace colon.' });
    }
    if (source === 'master') {
      w.push({ text: 'The master category ignores the player\'s individual volume sliders. Pick a more specific one if that matters.' });
    }
    if (!MC.isJava()) {
      w.push({ text: 'Bedrock accepts /playsound too, but its sound ids differ and it has no sound category argument.' });
    }

    return { cmd: parts.join(' '), warnings: w };
  });

  C.onInit(function () {
    if (!el('ps-list')) return;
    psRenderList('');
    el('ps-search').addEventListener('input', function () { psRenderList(this.value); });
    el('ps-list').addEventListener('click', function (e) {
      var row = e.target.closest('[data-sound]');
      if (!row) return;
      psSelected = row.dataset.sound;
      el('ps-picked').textContent = 'minecraft:' + psSelected;
      psRenderList(el('ps-search').value);
      C.rebuild();
      if (window.playSelect) playSelect();
    });
  });

  /* ═══════════════════════════════════════════════
     TEAM
     ═══════════════════════════════════════════════ */

  function tmRenderValue() {
    var sel = el('tm-option');
    var wrap = el('tm-value-wrap');
    if (!sel || !wrap) return;
    var opt = TEAM_OPTIONS[sel.value];
    if (!opt) return;
    el('tm-option-desc').textContent = opt.desc;

    var html = '';
    if (opt.type === 'bool') {
      html = '<label class="check-row"><input type="checkbox" id="tm-value-bool"' +
        (opt.default === 'true' ? ' checked' : '') + ' onchange="rebuild()"><span>Turn this on</span></label>' +
        '<div class="hint">Default: ' + opt.default + '</div>';
    } else if (opt.type === 'color') {
      html = '<div class="field"><label>Colour</label><select id="tm-value-select" onchange="rebuild()">' +
        Object.keys(TR_COLORS).map(function (c) {
          return '<option value="' + c + '">' + c.replace(/_/g, ' ') + '</option>';
        }).join('') + '<option value="reset">reset — no colour</option></select></div>';
    } else if (opt.type === 'visibility' || opt.type === 'collision') {
      var map = opt.type === 'visibility' ? TEAM_VISIBILITY : TEAM_COLLISION;
      html = '<div class="field"><label>Value</label><select id="tm-value-select" onchange="rebuild()">' +
        Object.keys(map).map(function (k) {
          return '<option value="' + k + '"' + (k === opt.default ? ' selected' : '') + '>' + esc(map[k]) + '</option>';
        }).join('') + '</select><div class="hint">Default: ' + opt.default + '</div></div>';
    } else {
      html = '<div class="field"><label>Text</label><input id="tm-value-text" placeholder="Shown to players" oninput="rebuild()"></div>';
    }
    wrap.innerHTML = html;
  }

  function tmSyncFields(op) {
    var show = {
      name:    op !== 'leave',
      display: op === 'add',
      members: op === 'join' || op === 'leave',
      modify:  op === 'modify'
    };
    Object.keys(show).forEach(function (k) {
      var box = document.querySelector('[data-team-field="' + k + '"]');
      if (box) box.hidden = !show[k];
    });
    if (op === 'list') {
      var n = document.querySelector('[data-team-field="name"]');
      if (n) n.hidden = false;
    }
  }

  C.register('team', function () {
    var w = [];
    var op = C.state.teamOp || 'add';
    var name = v('tm-name').trim();
    var members = v('tm-members').trim();
    tmSyncFields(op);

    if (op !== 'leave' && op !== 'list' && !name) {
      return { cmd: '', warnings: [{ level: 'error', text: 'Give the team an id first — that is how every other command refers to it.' }] };
    }
    if (name && /\s/.test(name)) {
      w.push({ level: 'error', text: 'A team id cannot contain spaces. Use the display name for anything with spaces in it.' });
    }

    if (op === 'list') {
      return { cmd: '/team list' + (name ? ' ' + name : ''),
               warnings: [{ text: name ? 'Lists the members of ' + name + '.' : 'Lists every team on the server.' }] };
    }

    if (op === 'add') {
      var display = v('tm-display').trim();
      var cmd = '/team add ' + name;
      if (display) cmd += ' ' + JSON.stringify({ text: display });
      w.push({ text: 'Creating a team does not put anyone in it. Use “Add players” next.' });
      return { cmd: cmd, warnings: w };
    }

    if (op === 'remove') {
      w.push({ level: 'warn', text: 'Deleting a team removes it for everyone in it. There is no undo.' });
      return { cmd: '/team remove ' + name, warnings: w };
    }

    if (op === 'empty') {
      w.push({ text: 'Removes every player from the team but keeps the team itself.' });
      return { cmd: '/team empty ' + name, warnings: w };
    }

    if (op === 'join') {
      if (!members) return { cmd: '', warnings: [{ level: 'error', text: 'Name the players to add, or use a selector like @a.' }] };
      return { cmd: '/team join ' + name + ' ' + members, warnings: w };
    }

    if (op === 'leave') {
      if (!members) return { cmd: '', warnings: [{ level: 'error', text: 'Name the players to remove, or use a selector like @a.' }] };
      w.push({ text: '/team leave does not name a team — players leave whichever team they are on.' });
      return { cmd: '/team leave ' + members, warnings: w };
    }

    // modify
    var option = v('tm-option') || 'displayName';
    var spec = TEAM_OPTIONS[option];
    var value;
    if (spec.type === 'bool') value = chk('tm-value-bool') ? 'true' : 'false';
    else if (spec.type === 'color' || spec.type === 'visibility' || spec.type === 'collision') value = v('tm-value-select');
    else {
      var text = v('tm-value-text');
      if (!text) return { cmd: '', warnings: [{ level: 'error', text: 'Enter the text for ' + spec.label.toLowerCase() + '.' }] };
      value = JSON.stringify({ text: text });
    }

    if (option === 'color') w.push({ text: 'Team colour also decides the outline colour when members glow.' });
    if (option === 'friendlyFire' && value === 'false') w.push({ text: 'Team mates still take splash and explosion damage from each other.' });

    return { cmd: '/team modify ' + name + ' ' + option + ' ' + value, warnings: w };
  });

  C.onInit(function () {
    if (!el('tm-name')) return;
    C.state.teamOp = 'add';
    C.wirePills('team-op', 'teamOp');
    var sel = el('tm-option');
    if (sel) sel.addEventListener('change', function () { tmRenderValue(); C.rebuild(); });
    tmRenderValue();
    tmSyncFields('add');
  });

  /* ═══════════════════════════════════════════════
     BOSS BAR
     ═══════════════════════════════════════════════ */

  function bbSyncFields(op) {
    var show = {
      id:     op !== 'list',
      set:    op === 'set',
      get:    op === 'get',
      detail: op === 'add' || op === 'set'
    };
    Object.keys(show).forEach(function (k) {
      var box = document.querySelector('[data-bb-field="' + k + '"]');
      if (box) box.hidden = !show[k];
    });
  }

  function bbPreview(title, color, style, value, max) {
    var fill = el('bb-preview-fill');
    if (!fill) return;
    el('bb-preview-title').textContent = title || '(no title)';
    var pct = max > 0 ? Math.max(0, Math.min(1, value / max)) : 0;
    fill.style.width = (pct * 100).toFixed(1) + '%';
    fill.style.background = (BOSSBAR_COLORS[color] || {}).hex || '#e8c84a';

    var segments = (BOSSBAR_STYLES[style] || {}).segments || 1;
    var track = el('bb-preview-track');
    track.style.setProperty('--bb-notches', segments > 1
      ? 'repeating-linear-gradient(90deg, transparent, transparent calc(100%/' + segments +
        ' - 2px), rgba(0,0,0,0.85) calc(100%/' + segments + ' - 2px), rgba(0,0,0,0.85) calc(100%/' + segments + '))'
      : 'none');
    el('bb-preview-meta').textContent = value + ' / ' + max + '  ·  ' + Math.round(pct * 100) + '%';
  }

  C.register('bossbar', function () {
    var w = [];
    var op = C.state.bbOp || 'add';
    var id = v('bb-id').trim();
    bbSyncFields(op);

    var title = v('bb-title');
    var color = v('bb-color') || 'yellow';
    var style = v('bb-style') || 'progress';
    var value = Math.max(0, Math.round(numf('bb-value', 0)));
    var max = Math.max(1, Math.round(numf('bb-max', 1)));
    bbPreview(title, color, style, value, max);

    if (op === 'list') return { cmd: '/bossbar list', warnings: [{ text: 'Lists every boss bar that exists.' }] };

    if (!id) {
      return { cmd: '', warnings: [{ level: 'error', text: 'Give the boss bar an id — that is how you refer to it later.' }] };
    }
    if (/\s/.test(id)) {
      w.push({ level: 'error', text: 'A boss bar id cannot contain spaces. Try something like minecraft:my_timer.' });
    }
    if (id.indexOf(':') === -1) {
      w.push({ text: 'No namespace given, so the game will store this as minecraft:' + id + '.' });
    }

    if (op === 'add') {
      if (!title) return { cmd: '', warnings: [{ level: 'error', text: 'A new boss bar needs a title — it is shown above the bar.' }] };
      w.push({ text: 'A new bar starts hidden with value 0 and max 100. Set players, value and visibility next.' });
      return { cmd: '/bossbar add ' + id + ' ' + JSON.stringify({ text: title }), warnings: w };
    }

    if (op === 'remove') {
      w.push({ level: 'warn', text: 'Deleting a boss bar removes it from every screen immediately.' });
      return { cmd: '/bossbar remove ' + id, warnings: w };
    }

    if (op === 'get') {
      return { cmd: '/bossbar get ' + id + ' ' + (v('bb-get') || 'value'), warnings: w };
    }

    // set
    var prop = v('bb-prop') || 'value';
    if (prop === 'name') {
      if (!title) return { cmd: '', warnings: [{ level: 'error', text: 'Enter a title to set.' }] };
      return { cmd: '/bossbar set ' + id + ' name ' + JSON.stringify({ text: title }), warnings: w };
    }
    if (prop === 'value') {
      if (value > max) w.push({ text: 'The value is above the maximum, so the bar will simply show as full.' });
      return { cmd: '/bossbar set ' + id + ' value ' + value, warnings: w };
    }
    if (prop === 'max') {
      return { cmd: '/bossbar set ' + id + ' max ' + max, warnings: w };
    }
    if (prop === 'color') {
      return { cmd: '/bossbar set ' + id + ' color ' + color, warnings: w };
    }
    if (prop === 'style') {
      return { cmd: '/bossbar set ' + id + ' style ' + style, warnings: w };
    }
    if (prop === 'visible') {
      return { cmd: '/bossbar set ' + id + ' visible ' + (v('bb-visible') || 'true'), warnings: w };
    }
    // players
    var players = v('bb-players').trim();
    if (!players) w.push({ text: 'With nobody listed the bar is hidden from everyone until you set players again.' });
    return { cmd: '/bossbar set ' + id + ' players' + (players ? ' ' + players : ''), warnings: w };
  });

  C.onInit(function () {
    if (!el('bb-id')) return;
    C.state.bbOp = 'add';
    C.wirePills('bb-op', 'bbOp');
    bbSyncFields('add');
  });

})();
