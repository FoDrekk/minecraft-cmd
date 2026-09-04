/* ================================================
   mccmd.js — shared client runtime for Minecraft CMD
   ------------------------------------------------
   Everything version-aware on the client goes through MC.*.
   MC_DATA is injected by lib/ui.php from lib/mc.php, so the
   version table has exactly one source of truth.
   ================================================ */
(function (global) {
  'use strict';

  var D = global.MC_DATA || { versions: {}, syntax: {}, features: {}, current: '26.2' };

  var MC = {
    versions: D.versions,
    features: D.features,
    _cur: D.current
  };

  /* ── VERSION STATE ───────────────────────────── */
  MC.ver = function () { return MC._cur; };
  MC.v   = function () { return MC.versions[MC._cur] || {}; };
  MC.syn = function () { return D.syntax[MC.v().syntax] || {}; };
  MC.isJava = function () { return MC.v().edition !== 'bedrock'; };

  /** Feature gate — "does the selected version have this?" */
  MC.has = function (feature) {
    var min = MC.features[feature];
    if (min === undefined) return false;
    return (MC.v().rank || 0) >= min;
  };

  MC.setVersion = function (id) {
    if (!MC.versions[id] || id === MC._cur) return;
    MC._cur = id;
    document.cookie = 'mc_version=' + encodeURIComponent(id) + ';path=/;max-age=31536000;samesite=lax';
    try { localStorage.setItem('mc_version', id); } catch (e) {}
    document.dispatchEvent(new CustomEvent('mc:version', { detail: { version: id } }));
  };

  /* ── SNBT ────────────────────────────────────────
     Minecraft's stringified NBT. Raw literals (byte
     suffixes, int arrays) are passed through with
     MC.raw() so callers keep full control.          */
  MC.raw = function (s) { return { __snbt: String(s) }; };

  function snbtString(s, quote) {
    quote = quote || '"';
    var out = String(s).replace(/\\/g, '\\\\');
    out = quote === '"' ? out.replace(/"/g, '\\"') : out.replace(/'/g, "\\'");
    return quote + out + quote;
  }

  var BARE_KEY = /^[A-Za-z0-9_.+-]+$/;

  MC.snbt = function (val) {
    if (val === null || val === undefined) return '';
    if (typeof val === 'object' && val.__snbt !== undefined) return val.__snbt;
    if (typeof val === 'boolean') return val ? 'true' : 'false';
    if (typeof val === 'number') return String(val);
    if (typeof val === 'string') return snbtString(val);
    if (Array.isArray(val)) return '[' + val.map(MC.snbt).join(',') + ']';
    var parts = [];
    for (var k in val) {
      if (!Object.prototype.hasOwnProperty.call(val, k)) continue;
      if (val[k] === undefined) continue;
      parts.push((BARE_KEY.test(k) ? k : snbtString(k)) + ':' + MC.snbt(val[k]));
    }
    return '{' + parts.join(',') + '}';
  };

  /** Int array literal, e.g. colours: [I;16711680,255] */
  MC.intArray = function (nums) { return MC.raw('[I;' + nums.join(',') + ']'); };
  MC.byte = function (n) { return MC.raw(n + 'b'); };

  /* ── TEXT COMPONENTS ─────────────────────────────
     A text component written *inside* NBT changed in
     1.21.5: it used to be a quoted JSON string, now
     it is a real SNBT compound. Command arguments
     (/tellraw, /title) still accept JSON either way. */

  /** Build a text component object, dropping empty styling keys. */
  MC.text = function (str, opts) {
    opts = opts || {};
    var t = { text: String(str == null ? '' : str) };
    ['color', 'font'].forEach(function (k) { if (opts[k]) t[k] = opts[k]; });
    ['bold', 'italic', 'underlined', 'strikethrough', 'obfuscated'].forEach(function (k) {
      if (opts[k] !== undefined && opts[k] !== null) t[k] = !!opts[k];
    });
    if (opts.extra && opts.extra.length) t.extra = opts.extra;
    if (opts.clickEvent) t.clickEvent = opts.clickEvent;
    if (opts.hoverEvent) t.hoverEvent = opts.hoverEvent;
    return t;
  };

  /** A text component encoded for the NBT/component position. */
  MC.nbtText = function (component) {
    if (MC.syn().text_in_nbt === 'snbt') return MC.snbt(component);
    return snbtString(JSON.stringify(component), "'");
  };

  /** A text component encoded as a command argument (/tellraw, /title). */
  MC.argText = function (component) { return JSON.stringify(component); };

  /* ── ITEM BUILDER ────────────────────────────────
     Returns the item argument for /give, /setblock …
     honouring the selected version's syntax era.

     spec: { name, lore[], enchants[{id,lvl}], unbreakable,
             hideFlags, components{}, nbt{} }             */
  MC.item = function (id, spec) {
    spec = spec || {};
    var syn = MC.syn(), comps = {}, nbt = {}, hidden = [];

    if (syn.items === 'bedrock') return id;                 // Bedrock: plain id

    var name = spec.name && spec.name.text !== undefined ? spec.name
             : (spec.name ? MC.text(spec.name) : null);

    if (syn.items === 'components') {
      if (name) comps['custom_name'] = MC.raw(MC.nbtText(name));
      if (spec.lore && spec.lore.length) {
        comps['lore'] = MC.raw('[' + spec.lore.map(function (l) { return MC.nbtText(l); }).join(',') + ']');
      }
      if (spec.enchants && spec.enchants.length) {
        var lv = {};
        spec.enchants.forEach(function (e) { lv[e.id] = e.lvl; });
        comps['enchantments'] = syn.enchant === 'flat' ? lv : { levels: lv };
        if (spec.hideFlags && syn.hide === 'show_in_tooltip') comps['enchantments'].show_in_tooltip = false;
        if (spec.hideFlags && syn.hide === 'tooltip_display') hidden.push('minecraft:enchantments');
      }
      if (spec.unbreakable) {
        comps['unbreakable'] = (spec.hideFlags && syn.hide === 'show_in_tooltip') ? { show_in_tooltip: false } : {};
        if (spec.hideFlags && syn.hide === 'tooltip_display') hidden.push('minecraft:unbreakable');
      }
      if (spec.hideFlags && syn.hide === 'show_in_tooltip') comps['hide_additional_tooltip'] = {};
      if (hidden.length) comps['tooltip_display'] = { hidden_components: hidden };
      Object.assign(comps, spec.components || {});

      var body = MC.snbt(comps);
      return id + (body === '{}' ? '' : '[' + body.slice(1, -1) + ']');
    }

    /* legacy_nbt (≤ 1.20.4) */
    var display = {};
    if (name) display['Name'] = MC.raw(MC.nbtText(name));
    if (spec.lore && spec.lore.length) {
      display['Lore'] = MC.raw('[' + spec.lore.map(function (l) { return MC.nbtText(l); }).join(',') + ']');
    }
    if (Object.keys(display).length) nbt['display'] = display;
    if (spec.enchants && spec.enchants.length) {
      nbt['Enchantments'] = spec.enchants.map(function (e) {
        return { id: 'minecraft:' + String(e.id).replace(/^minecraft:/, ''), lvl: MC.raw(e.lvl + 's') };
      });
    }
    if (spec.unbreakable) nbt['Unbreakable'] = MC.byte(1);
    if (spec.hideFlags) nbt['HideFlags'] = 63;
    Object.assign(nbt, spec.nbt || {});

    var tag = MC.snbt(nbt);
    return id + (tag === '{}' ? '' : tag);
  };

  /* ── CLIPBOARD / TOAST ───────────────────────── */
  MC.toast = function (msg, color) {
    if (typeof global.showToast === 'function') return global.showToast(msg, color);
    var t = document.getElementById('toast');
    if (!t) { t = document.createElement('div'); t.id = 'toast'; t.className = 'toast'; document.body.appendChild(t); }
    t.textContent = msg;
    t.style.borderColor = color || 'rgba(93,190,122,0.35)';
    t.style.color = color || 'var(--green)';
    t.classList.add('show');
    clearTimeout(t._t);
    t._t = setTimeout(function () { t.classList.remove('show'); }, 2200);
  };

  MC.copy = function (text) {
    if (!text || !text.trim() || text.trim().indexOf('//') === 0) {
      MC.toast('Nothing to copy yet', 'var(--red)');
      return false;
    }
    var done = function () { if (global.playCopy) global.playCopy(); MC.toast('Copied'); };
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(done, function () { fallbackCopy(text); done(); });
    } else { fallbackCopy(text); done(); }
    return true;
  };

  function fallbackCopy(text) {
    var ta = document.createElement('textarea');
    ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
    document.body.appendChild(ta); ta.select();
    try { document.execCommand('copy'); } catch (e) {}
    document.body.removeChild(ta);
  }

  /* ── API ─────────────────────────────────────── */
  MC.api = function (action, body) {
    var fd = new FormData();
    fd.append('action', action);
    Object.keys(body || {}).forEach(function (k) { fd.append(k, body[k]); });
    return fetch('api.php', { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .catch(function (e) { return { ok: false, error: e.message }; });
  };

  /* ── RECENTS ─────────────────────────────────────
     Lightweight, per-browser. Powers Home > Recently
     used and Continue. Saved commands live in the DB. */
  var RECENT_KEY = 'mc_recent_v1', RECENT_MAX = 24;

  MC.recents = function () {
    try { return JSON.parse(localStorage.getItem(RECENT_KEY) || '[]'); } catch (e) { return []; }
  };

  MC.remember = function (entry) {
    if (!entry || !entry.href) return;
    try {
      var list = MC.recents().filter(function (r) { return r.href !== entry.href || r.title !== entry.title; });
      entry.at = Date.now();
      list.unshift(entry);
      localStorage.setItem(RECENT_KEY, JSON.stringify(list.slice(0, RECENT_MAX)));
    } catch (e) {}
  };

  MC.forgetAll = function () { try { localStorage.removeItem(RECENT_KEY); } catch (e) {} };

  /* ── COMMAND OUTPUT WIDGET ───────────────────────
     One consistent Copy / Copy+Save surface for every
     generator. Markup lives in lib/ui.php.           */
  MC.setCommand = function (root, text, opts) {
    root = typeof root === 'string' ? document.getElementById(root) : root;
    if (!root) return;
    opts = opts || {};
    var body = root.querySelector('[data-cmd]');
    var warn = root.querySelector('[data-warn]');
    var meta = root.querySelector('[data-meta]');
    root._cmd = text;
    root._tab = opts.tab || root.dataset.tab || 'cmd';
    if (body) body.textContent = text;
    if (meta) meta.textContent = (text || '').length + ' chars · ' + (MC.v().label || '');
    if (warn) {
      var msgs = (opts.warnings || []).filter(Boolean);
      warn.innerHTML = msgs.map(function (m) {
        return '<div class="warn-line ' + (m.level === 'error' ? 'is-error' : '') + '">' +
               (m.level === 'error' ? '⛔ ' : '⚠ ') + escapeHtml(m.text || m) + '</div>';
      }).join('');
      warn.hidden = msgs.length === 0;
    }
  };

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
  MC.escapeHtml = escapeHtml;

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-act]');
    if (!btn) return;
    var root = btn.closest('[data-cmdout]');
    if (!root) return;
    var cmd = root._cmd || (root.querySelector('[data-cmd]') || {}).textContent || '';
    var act = btn.dataset.act;

    if (act === 'copy' || act === 'copysave') {
      if (!cmd.trim()) { MC.toast('Nothing to copy yet', 'var(--red)'); return; }
      MC.copy(cmd);
      MC.remember({ type: 'command', title: cmd, href: location.pathname.split('/').pop() + location.search, cmd: cmd });
    }
    if (act === 'save' || act === 'copysave') {
      if (!cmd.trim()) return;
      MC.api('history_add', { command: cmd, tab: root._tab || 'cmd' });
    }
    if (act === 'fav') {
      if (!cmd.trim()) return;
      MC.api('fav_add', { command: cmd, tab: root._tab || 'cmd', note: '' }).then(function (r) {
        MC.toast(r.ok ? 'Saved to library' : (r.error || 'Could not save'), r.ok ? null : 'var(--red)');
      });
    }
  });

  /* ── VERSION PICKER WIRING ───────────────────── */
  document.addEventListener('DOMContentLoaded', function () {
    var sel = document.getElementById('mc-version-select');
    if (sel) {
      sel.value = MC._cur;
      sel.addEventListener('change', function () { MC.setVersion(sel.value); });
    }
    document.addEventListener('mc:version', function () {
      var b = document.getElementById('mc-version-note');
      if (b) b.textContent = MC.syn().label || '';
      if (typeof global.rebuild === 'function') global.rebuild();
    });
    var b = document.getElementById('mc-version-note');
    if (b) b.textContent = MC.syn().label || '';
  });

  global.MC = MC;
})(window);
