/* ================================================
   farms.js — material checklists that remember
   ------------------------------------------------
   Ticked materials persist per farm in this browser, so a
   shopping list survives closing the tab mid-build.
   ================================================ */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-checklist]').forEach(function (list) {
      var key = 'mc_check_' + list.dataset.checklist;
      var boxes = list.querySelectorAll('input[type=checkbox]');
      var saved = {};
      try { saved = JSON.parse(localStorage.getItem(key) || '{}'); } catch (e) {}

      boxes.forEach(function (box) {
        box.checked = !!saved[box.dataset.ck];
        box.addEventListener('change', function () {
          saved[box.dataset.ck] = box.checked;
          try { localStorage.setItem(key, JSON.stringify(saved)); } catch (e) {}
          progress();
          if (window.playClick) playClick();
        });
      });

      function progress() {
        var el = document.getElementById('check-progress');
        if (!el) return;
        var done = 0;
        boxes.forEach(function (b) { if (b.checked) done++; });
        el.textContent = done === 0 ? boxes.length + ' items to gather'
          : (done === boxes.length ? '✅ Everything gathered' : done + ' of ' + boxes.length + ' gathered');
      }
      progress();
    });

    if (FARM_ID) {
      var title = document.querySelector('.guide h1');
      MC.remember({ type: 'guide', icon: '🌾', title: 'Farm — ' + (title ? title.textContent : FARM_ID),
                    href: 'farms.php?farm=' + encodeURIComponent(FARM_ID) });
    }
  });
})();
