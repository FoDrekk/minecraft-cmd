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

    var bpMount = document.getElementById('bp-viewport-farm');
    var bpApi = null;
    if (bpMount && window.FARM_BLUEPRINT && window.MC && MC.blueprint) {
      bpApi = MC.blueprint.mount(bpMount, window.FARM_BLUEPRINT);
    }

    // "View in blueprint" buttons on the prose step list jump the
    // mounted blueprint to that step's layer and highlight.
    document.querySelectorAll('[data-step-jump]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        if (bpApi) bpApi.gotoStep(+btn.dataset.stepJump);
      });
    });

    if (window.FARM_RATE) fcCalc();
  });

  /* Farm Calculator — only rendered when the guide states a real
     numeric range (see lib/data/farms.php 'rate'). Scales that stated
     range by hours; never invents a rate for a farm that has none. */
  window.fcCalc = function () {
    var r = window.FARM_RATE;
    var out = document.getElementById('fc-result');
    if (!r || !out) return;
    var hours = Math.max(0, parseFloat(document.getElementById('fc-hours').value) || 0);
    var low = Math.round(r.low * hours);
    var high = Math.round(r.high * hours);
    out.textContent = 'Estimated ' + low.toLocaleString() + '–' + high.toLocaleString() + ' ' + r.unit +
      ' over ' + hours + ' hour' + (hours === 1 ? '' : 's') + ' (estimate).';
  };
})();
