/* ================================================
   mystuff.js — library, history, palettes
   ================================================ */
(function () {
  'use strict';

  function el(id) { return document.getElementById(id); }

  function filter() {
    var search = el('ms-search');
    if (!search) return;
    var q = search.value.trim().toLowerCase();
    var cat = el('ms-cat') ? el('ms-cat').value : '';
    var rows = document.querySelectorAll('#ms-list .entry');
    var shown = 0;
    rows.forEach(function (r) {
      var hit = (!q || r.dataset.search.indexOf(q) !== -1) && (!cat || r.dataset.category === cat);
      r.hidden = !hit;
      if (hit) shown++;
    });
    var count = el('ms-count');
    if (count) count.textContent = (q || cat) ? shown + ' of ' + rows.length : rows.length + ' saved';
  }

  document.addEventListener('DOMContentLoaded', function () {
    ['ms-search', 'ms-cat'].forEach(function (id) {
      var e = el(id);
      if (!e) return;
      e.addEventListener('input', filter);
      e.addEventListener('change', filter);
    });
    filter();

    var clear = el('hist-clear');
    if (clear) {
      clear.addEventListener('click', function () {
        if (!confirm('Delete every command in your history? Saved library commands are not affected.')) return;
        MC.api('history_clear').then(function () { location.reload(); });
      });
    }

    document.addEventListener('click', function (e) {
      var copy = e.target.closest('[data-copy]');
      if (copy) { MC.copy(copy.dataset.copy); return; }

      var delFav = e.target.closest('[data-del-fav]');
      if (delFav) {
        if (!confirm('Remove this from your library?')) return;
        MC.api('fav_delete', { id: delFav.dataset.delFav })
          .then(function () { delFav.closest('.entry').remove(); filter(); });
        return;
      }

      var delHist = e.target.closest('[data-del-hist]');
      if (delHist) {
        MC.api('history_delete', { id: delHist.dataset.delHist })
          .then(function () { delHist.closest('.entry').remove(); filter(); });
        return;
      }

      var delPal = e.target.closest('[data-del-pal]');
      if (delPal) {
        if (!confirm('Delete this palette?')) return;
        MC.api('palette_delete', { id: delPal.dataset.delPal })
          .then(function () { delPal.closest('.entry').remove(); });
        return;
      }

      var saveFav = e.target.closest('[data-save-fav]');
      if (saveFav) {
        MC.api('fav_add', { command: saveFav.dataset.saveFav, tab: saveFav.dataset.tab || 'cmd' })
          .then(function (r) {
            MC.toast(r.ok ? 'Saved to library' : (r.error || 'Could not save'), r.ok ? null : 'var(--red)');
          });
        return;
      }

      var edit = e.target.closest('[data-edit]');
      if (edit) {
        var entry = edit.closest('.entry');
        var nameEl = entry.querySelector('.entry-name');
        var name = prompt('Name this command:', nameEl ? nameEl.textContent : '');
        if (name === null) return;
        var note = prompt('Note (optional):', (entry.querySelector('.entry-note') || {}).textContent || '');
        if (note === null) note = '';
        var cat = prompt('Category (' + MS_CATEGORIES.join(', ') + '):',
                         entry.dataset.category || '');
        MC.api('fav_update', { id: edit.dataset.edit, name: name, note: note.replace(/^📝\s*/, ''), category: cat || '' })
          .then(function (r) {
            if (r.ok) location.reload();
            else MC.toast(r.error || 'Could not save the change', 'var(--red)');
          });
      }
    });
  });
})();
