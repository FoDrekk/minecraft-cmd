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
  var el = C.el, v = C.v, chk = C.chk, num = C.num, clamp = C.clamp;
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

})();
