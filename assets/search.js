/* ================================================
   search.js — global search overlay
   Opens with "/" or Ctrl/Cmd-K, queries api.php?action=search.
   ================================================ */
(function () {
  'use strict';

  var overlay, input, results, btn;
  var timer = null, sel = 0, rows = [];

  function open() {
    if (!overlay) return;
    overlay.hidden = false;
    input.value = '';
    sel = 0;
    renderSuggestions();
    setTimeout(function () { input.focus(); }, 10);
  }

  function close() { if (overlay) overlay.hidden = true; }

  function renderSuggestions() {
    var recents = (window.MC && MC.recents) ? MC.recents().slice(0, 5) : [];
    if (!recents.length) {
      results.innerHTML =
        '<div class="empty-state"><div class="empty-icon">🔍</div>' +
        '<div class="empty-title">Search everything</div>' +
        '<div class="empty-sub">Tools, commands, blocks, palettes, building tips, build ideas and farms.<br>' +
        'Try “clear trees”, “automatic iron”, or “make everyone creative”.</div></div>';
      rows = [];
      return;
    }
    rows = recents.map(function (r) {
      return { icon: '🕘', title: r.title, desc: 'Recently used', href: r.href, cat: 'recent' };
    });
    paint();
  }

  function paint() {
    if (!rows.length) {
      results.innerHTML = '<div class="empty-state"><div class="empty-icon">🤔</div>' +
        '<div class="empty-title">No matches</div>' +
        '<div class="empty-sub">Try describing the task instead of the command name.</div></div>';
      return;
    }
    results.innerHTML = rows.map(function (r, i) {
      return '<a class="search-hit' + (i === sel ? ' sel' : '') + '" href="' + esc(r.href) + '">' +
        '<span class="search-hit-icon">' + (r.icon || '•') + '</span>' +
        '<span><span class="search-hit-title">' + esc(r.title) + '</span><br>' +
        '<span class="search-hit-desc">' + esc(r.desc || '') + '</span></span>' +
        '<span class="search-hit-cat">' + esc(r.cat || '') + '</span></a>';
    }).join('');
  }

  // Was a second, independent copy of the same escaping rules already in
  // mccmd.js (loaded before this file, so it's always available here).
  function esc(s) { return MC.escapeHtml(s); }

  function query() {
    var q = input.value.trim();
    if (!q) { renderSuggestions(); return; }
    fetch('api.php?action=search&q=' + encodeURIComponent(q))
      .then(function (r) { return r.json(); })
      .then(function (d) {
        rows = (d && d.ok && d.rows) ? d.rows : [];
        sel = 0;
        paint();
      })
      .catch(function () {
        results.innerHTML = '<div class="empty-state"><div class="empty-icon">⚠️</div>' +
          '<div class="empty-title">Search is unavailable</div>' +
          '<div class="empty-sub">The page could not reach the server. Check that the site is running, then try again.</div></div>';
      });
  }

  document.addEventListener('DOMContentLoaded', function () {
    overlay = document.getElementById('search-overlay');
    input   = document.getElementById('global-search-input');
    results = document.getElementById('global-search-results');
    btn     = document.getElementById('global-search-btn');
    if (!overlay || !input || !results) return;

    if (btn) btn.addEventListener('click', open);
    overlay.addEventListener('click', function (e) { if (e.target === overlay) close(); });

    input.addEventListener('input', function () {
      clearTimeout(timer);
      timer = setTimeout(query, 130);
    });

    input.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') { close(); return; }
      if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
        e.preventDefault();
        if (!rows.length) return;
        sel = (sel + (e.key === 'ArrowDown' ? 1 : rows.length - 1)) % rows.length;
        paint();
        var el = results.querySelector('.search-hit.sel');
        if (el) el.scrollIntoView({ block: 'nearest' });
      }
      if (e.key === 'Enter' && rows[sel]) { location.href = rows[sel].href; }
    });

    document.addEventListener('keydown', function (e) {
      var tag = (e.target.tagName || '').toLowerCase();
      var typing = tag === 'input' || tag === 'textarea' || tag === 'select' || e.target.isContentEditable;
      if ((e.key === 'k' || e.key === 'K') && (e.metaKey || e.ctrlKey)) { e.preventDefault(); open(); return; }
      if (e.key === '/' && !typing && overlay.hidden) { e.preventDefault(); open(); }
      if (e.key === 'Escape' && !overlay.hidden) close();
    });
  });
})();
